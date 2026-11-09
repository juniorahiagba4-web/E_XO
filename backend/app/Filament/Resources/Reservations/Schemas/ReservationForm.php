<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Models\Reservation;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

/**
 * The form follows the kind of order: a rental shows its period, the days
 * billed and the delivery and collection times; a purchase shows its purchase
 * day and, when delivered, the delivery day and time.
 */
class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        $isRental = fn (Get $get) => $get('type') === 'rental';
        $isPurchase = fn (Get $get) => $get('type') === 'purchase';
        $isDelivered = fn (Get $get) => $get('delivery_method') === 'delivery';

        return $schema
            ->components([
                Section::make(fn (Get $get) => $get('type') === 'purchase' ? 'Commande (achat)' : 'Réservation (location)')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Référence')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('status')
                            ->label('Statut')
                            ->options(Reservation::STATUS_LABELS)
                            ->helperText('Confirmé : le client a payé tout ou partie. Terminé : mobilier livré (et récupéré pour une location).')
                            ->required()
                            ->native(false),
                        Select::make('type')
                            ->label('Type')
                            ->options(Reservation::TYPE_LABELS)
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'email')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->email})")
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->required(),
                        Textarea::make('notes')
                            ->label('Message du client')
                            ->columnSpanFull(),
                    ]),

                Section::make('Période de location')
                    ->visible($isRental)
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('event_start_date')
                            ->label('Début')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->required($isRental)
                            ->live(),
                        DatePicker::make('event_end_date')
                            ->label('Fin')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->required($isRental)
                            ->afterOrEqual('event_start_date')
                            ->live(),
                        TextEntry::make('days')
                            ->label('Jours facturés')
                            ->state(function (Get $get) {
                                $days = static::days($get('event_start_date'), $get('event_end_date'));

                                return $days ? $days.' '.($days > 1 ? 'jours' : 'jour') : '—';
                            }),
                    ]),

                Section::make('Achat')
                    ->visible($isPurchase)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('purchase_date')
                            ->label("Date d'achat")
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ]),

                Section::make('Livraison')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('delivery_method')
                            ->label('Mode')
                            ->options(Reservation::DELIVERY_METHOD_LABELS)
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('delivery_address')
                            ->label('Adresse de livraison')
                            ->visible($isDelivered),

                        // Rental: the shop delivers on the first day and comes
                        // back on the last one, at the time the customer chose.
                        Fieldset::make('Aller : livraison du mobilier')
                            ->visible(fn (Get $get) => $isRental($get) && $isDelivered($get))
                            ->schema([
                                TextEntry::make('outbound_day')
                                    ->label('Date')
                                    ->state(fn (Get $get) => static::formatDay($get('event_start_date'))),
                                TimePicker::make('delivery_time')
                                    ->label('Heure (souhaitée par le client)')
                                    ->seconds(false)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old) {
                                        // The return follows the delivery time unless
                                        // it was deliberately set to another one.
                                        if (blank($get('return_time')) || $get('return_time') === $old) {
                                            $set('return_time', $state);
                                        }
                                    }),
                            ]),
                        Fieldset::make('Retour : récupération du mobilier')
                            ->visible(fn (Get $get) => $isRental($get) && $isDelivered($get))
                            ->schema([
                                TextEntry::make('return_day')
                                    ->label('Date')
                                    ->state(fn (Get $get) => static::formatDay($get('event_end_date'))),
                                TimePicker::make('return_time')
                                    ->label('Heure')
                                    ->seconds(false)
                                    ->helperText('Reprend l\'heure de livraison, modifiable si besoin.'),
                            ]),

                        // Purchase: a single delivery, no collection.
                        DatePicker::make('delivery_date')
                            ->label('Date de livraison')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->visible(fn (Get $get) => $isPurchase($get) && $isDelivered($get)),
                        TimePicker::make('delivery_time')
                            ->key('purchase_delivery_time')
                            ->label('Heure de livraison (souhaitée par le client)')
                            ->seconds(false)
                            ->visible(fn (Get $get) => $isPurchase($get) && $isDelivered($get)),

                        TextEntry::make('pickup_note')
                            ->hiddenLabel()
                            ->state('Le client vient chercher le mobilier en boutique.')
                            ->visible(fn (Get $get) => ! $isDelivered($get))
                            ->columnSpanFull(),
                    ]),

                Section::make('Montants')
                    ->description('Calculés automatiquement à partir des articles réservés. Pour un article « sur devis », saisissez son prix dans « Articles réservés » plus bas : le total et le PDF se mettent à jour.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Sous-total')
                            ->state(fn (?Reservation $record) => static::money($record?->subtotal)),
                        TextInput::make('discount')
                            ->label('Remise')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->default(0),
                        TextEntry::make('total')
                            ->label('Total')
                            ->weight('bold')
                            ->state(fn (?Reservation $record) => static::money($record?->total)),
                    ]),
            ]);
    }

    public static function days(mixed $start, mixed $end): ?int
    {
        if (blank($start) || blank($end)) {
            return null;
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        return $end->lt($start) ? null : max(1, (int) round($start->diffInDays($end)));
    }

    private static function formatDay(mixed $date): string
    {
        if (blank($date)) {
            return '—';
        }

        return ($date instanceof CarbonInterface ? $date : Carbon::parse($date))->format('d/m/Y');
    }

    private static function money(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ').' FCFA';
    }
}

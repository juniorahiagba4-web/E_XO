<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Models\Item;
use App\Models\Reservation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * Order taken by phone or in the shop: the team picks the customer, the
 * kind of order, the dates, the delivery and the items. Prices are filled in
 * from the catalogue and can be changed (or left empty for a "sur devis"
 * purchase).
 */
class ManualOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $isRental = fn (Get $get) => $get('type') === 'rental';
        $isPurchase = fn (Get $get) => $get('type') === 'purchase';
        $isDelivered = fn (Get $get) => $get('delivery_method') === 'delivery';

        return $schema
            ->components([
                Section::make('Commande')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        ToggleButtons::make('type')
                            ->label('Type')
                            ->options(Reservation::TYPE_LABELS)
                            ->icons(['rental' => 'heroicon-o-calendar-days', 'purchase' => 'heroicon-o-shopping-bag'])
                            ->default('rental')
                            ->inline()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::refreshLinePrices($get, $set)),
                        Select::make('status')
                            ->label('Statut')
                            ->options(Reservation::STATUS_LABELS)
                            ->default('confirmed')
                            ->helperText('Envoyé : en attente de confirmation (annulé tout seul au bout de 48 h pour une location). Confirmé : le client a payé tout ou partie.')
                            ->required()
                            ->native(false),
                        Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'last_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.($record->phone ?: $record->email).')')
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->preload()
                            ->required()
                            ->createOptionModalHeading('Nouveau client')
                            ->createOptionForm([
                                TextInput::make('first_name')->label('Prénom')->required(),
                                TextInput::make('last_name')->label('Nom')->required(),
                                TextInput::make('phone')->label('Téléphone / WhatsApp')->tel()->required(),
                                TextInput::make('email')->label('Adresse email')->email()->helperText('Facultatif.'),
                            ])
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
                                $days = ReservationForm::days($get('event_start_date'), $get('event_end_date'));

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
                            ->native(false)
                            ->default(now())
                            ->required($isPurchase),
                    ]),

                Section::make('Livraison')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('delivery_method')
                            ->label('Mode')
                            ->options(Reservation::DELIVERY_METHOD_LABELS)
                            ->default('pickup')
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('delivery_address')
                            ->label('Adresse de livraison')
                            ->required($isDelivered)
                            ->visible($isDelivered),
                        DatePicker::make('delivery_date')
                            ->label('Date de livraison')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->visible(fn (Get $get) => $isPurchase($get) && $isDelivered($get)),
                        TimePicker::make('delivery_time')
                            ->label(fn (Get $get) => $isRental($get) ? 'Heure de livraison (aller)' : 'Heure de livraison')
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old) {
                                if (blank($get('return_time')) || $get('return_time') === $old) {
                                    $set('return_time', $state);
                                }
                            })
                            ->visible($isDelivered),
                        TimePicker::make('return_time')
                            ->label('Heure de récupération (retour)')
                            ->seconds(false)
                            ->helperText('Reprend l\'heure de livraison, modifiable si besoin.')
                            ->visible(fn (Get $get) => $isRental($get) && $isDelivered($get)),
                    ]),

                Section::make('Articles')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('lines')
                            ->hiddenLabel()
                            ->addActionLabel('Ajouter un article')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(5)
                            ->live()
                            ->schema([
                                Select::make('item_id')
                                    ->label('Article')
                                    ->options(fn (Get $get) => static::itemOptions($get('../../type')))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set, $state) => $set(
                                        'unit_price',
                                        static::catalogPrice($state, $get('../../type')),
                                    ))
                                    ->columnSpan(2),
                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('unit_price')
                                    ->label(fn (Get $get) => $get('../../type') === 'purchase' ? 'Prix unitaire' : 'Prix / jour')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('FCFA')
                                    ->required(fn (Get $get) => $get('../../type') === 'rental')
                                    ->placeholder(fn (Get $get) => $get('../../type') === 'purchase' ? 'Sur devis' : null)
                                    ->live(onBlur: true),
                                TextEntry::make('line_total')
                                    ->label('Sous-total')
                                    ->state(fn (Get $get) => static::money(static::lineTotal(
                                        $get('quantity'),
                                        $get('unit_price'),
                                        $get('../../type'),
                                        ReservationForm::days($get('../../event_start_date'), $get('../../event_end_date')),
                                    ))),
                            ]),
                    ]),

                Section::make('Montants')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('preview_subtotal')
                            ->label('Sous-total')
                            ->state(fn (Get $get) => static::money(static::subtotal($get))),
                        TextInput::make('discount')
                            ->label('Remise')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->default(0)
                            ->live(onBlur: true),
                        TextEntry::make('preview_total')
                            ->label('Total')
                            ->weight('bold')
                            ->state(fn (Get $get) => static::money(max(0, static::subtotal($get) - (float) $get('discount')))),
                        Textarea::make('notes')
                            ->label('Remarques')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, string> */
    private static function itemOptions(?string $type): array
    {
        return Item::query()
            ->where('is_active', true)
            ->when($type === 'purchase', fn ($query) => $query->where(
                fn ($q) => $q->whereNotNull('sale_price')->orWhere('sale_price_on_request', true)
            ))
            ->orderBy('name_fr')
            ->get()
            ->mapWithKeys(fn (Item $item) => [$item->id => $item->name_fr])
            ->all();
    }

    private static function catalogPrice(mixed $itemId, ?string $type): ?string
    {
        $item = $itemId ? Item::find($itemId) : null;

        return $type === 'purchase' ? $item?->sale_price : $item?->rental_price_per_day;
    }

    /** Switching between rental and purchase swaps every line's price. */
    private static function refreshLinePrices(Get $get, Set $set): void
    {
        $lines = $get('lines') ?? [];
        foreach ($lines as $key => $line) {
            $lines[$key]['unit_price'] = static::catalogPrice($line['item_id'] ?? null, $get('type'));
        }
        $set('lines', $lines);
    }

    private static function lineTotal(mixed $quantity, mixed $price, ?string $type, ?int $days): float
    {
        return (float) $price * (int) $quantity * ($type === 'rental' ? ($days ?? 1) : 1);
    }

    private static function subtotal(Get $get): float
    {
        $days = ReservationForm::days($get('event_start_date'), $get('event_end_date'));

        return collect($get('lines') ?? [])
            ->sum(fn ($line) => static::lineTotal($line['quantity'] ?? 0, $line['unit_price'] ?? 0, $get('type'), $days));
    }

    private static function money(float $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}

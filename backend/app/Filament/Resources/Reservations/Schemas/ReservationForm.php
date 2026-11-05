<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Models\Reservation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Réservation')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')
                            ->label('Référence')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Select::make('status')
                            ->label('Statut')
                            ->options(Reservation::STATUS_LABELS)
                            ->required()
                            ->native(false),
                        Select::make('type')
                            ->label('Type')
                            ->options(Reservation::TYPE_LABELS)
                            ->required()
                            ->native(false),
                        Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'email')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->email})")
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->required(),
                        DatePicker::make('event_start_date')
                            ->label('Début de l\'événement')
                            ->displayFormat('d/m/Y')
                            ->helperText('Sans objet pour un achat.'),
                        DatePicker::make('event_end_date')
                            ->label('Fin de l\'événement')
                            ->displayFormat('d/m/Y')
                            ->helperText('Sans objet pour un achat.'),
                        Textarea::make('notes')
                            ->label('Message du client')
                            ->columnSpanFull(),
                    ]),

                Section::make('Livraison')
                    ->columns(2)
                    ->schema([
                        Select::make('delivery_method')
                            ->label('Mode')
                            ->options(Reservation::DELIVERY_METHOD_LABELS)
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('delivery_address')
                            ->label('Adresse de livraison')
                            ->visible(fn ($get) => $get('delivery_method') === 'delivery'),
                        DatePicker::make('delivery_date')
                            ->label('Date de livraison')
                            ->displayFormat('d/m/Y'),
                        TimePicker::make('delivery_time')
                            ->label('Heure de livraison souhaitée')
                            ->seconds(false)
                            ->helperText('Saisie par le client dans sa demande.')
                            ->visible(fn ($get) => $get('delivery_method') === 'delivery'),
                        Select::make('delivery_slot_template_id')
                            ->label('Créneau de livraison')
                            ->relationship('deliverySlot', 'label'),
                        Select::make('return_slot_template_id')
                            ->label('Créneau de retour')
                            ->relationship('returnSlot', 'label'),
                        DatePicker::make('return_date')
                            ->label('Date de retour')
                            ->displayFormat('d/m/Y'),
                    ]),

                Section::make('Montants (FCFA)')
                    ->columns(3)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Sous-total')
                            ->required()
                            ->numeric()
                            ->suffix('FCFA')
                            ->default(0),
                        TextInput::make('discount')
                            ->label('Remise')
                            ->required()
                            ->numeric()
                            ->suffix('FCFA')
                            ->default(0),
                        TextInput::make('total')
                            ->label('Total')
                            ->required()
                            ->numeric()
                            ->suffix('FCFA')
                            ->default(0),
                    ]),
            ]);
    }
}

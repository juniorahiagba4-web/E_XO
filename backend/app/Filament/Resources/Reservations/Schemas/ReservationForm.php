<?php

namespace App\Filament\Resources\Reservations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Select::make('status')
                            ->options([
                                'draft' => 'Brouillon',
                                'quote_sent' => 'Devis envoyé',
                                'confirmed' => 'Confirmée',
                                'deposit_paid' => 'Acompte payé',
                                'ongoing' => 'En cours',
                                'completed' => 'Terminée',
                                'cancelled' => 'Annulée',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'email')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->email})")
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->required(),
                        Select::make('delivery_method')
                            ->label('Mode de livraison')
                            ->options(['delivery' => 'Livraison', 'pickup' => 'Retrait'])
                            ->required()
                            ->native(false),
                        DatePicker::make('event_start_date')
                            ->label('Début événement')
                            ->required(),
                        DatePicker::make('event_end_date')
                            ->label('Fin événement')
                            ->required(),
                        TextInput::make('delivery_address')
                            ->label('Adresse de livraison')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),

                Section::make('Logistique')
                    ->columns(2)
                    ->schema([
                        Select::make('delivery_slot_template_id')
                            ->label('Créneau de livraison')
                            ->relationship('deliverySlot', 'label'),
                        DatePicker::make('delivery_date')
                            ->label('Date de livraison'),
                        Select::make('return_slot_template_id')
                            ->label('Créneau de retour')
                            ->relationship('returnSlot', 'label'),
                        DatePicker::make('return_date')
                            ->label('Date de retour'),
                    ]),

                Section::make('Montants')
                    ->columns(2)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Sous-total')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('deposit_required')
                            ->label('Caution requise')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('discount')
                            ->label('Remise')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('total')
                            ->label('Total')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('currency')
                            ->label('Devise')
                            ->required()
                            ->default('XOF'),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\CompanyInfo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The company's phone numbers, WhatsApp, e-mail, address and social pages,
 * shown on the website (WhatsApp button, contact page, footer, Google data)
 * and on the quote PDF. Changing them here needs no code change.
 */
class CompanyContact extends Page
{
    /** Form field => settings key. */
    private const KEYS = [
        'phone' => 'company_phone',
        'whatsapp' => 'company_whatsapp',
        'email' => 'company_email',
        'address' => 'company_address',
        'tiktok' => 'company_tiktok_url',
        'facebook' => 'company_facebook_url',
        'instagram' => 'company_instagram_url',
    ];

    // Spaces around the number are tolerated: they are trimmed when saving.
    private const PHONE_RULE = 'regex:/^\s*\+?[0-9 ().-]{8,20}\s*$/';

    private const PHONE_MESSAGE = ['regex' => 'Numéro invalide : chiffres, espaces et « + » uniquement.'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|\UnitEnum|null $navigationGroup = 'Paramètres';

    protected static ?int $navigationSort = -1;

    protected static ?string $navigationLabel = "Coordonnées de l'entreprise";

    protected static ?string $title = "Coordonnées de l'entreprise";

    protected static ?string $slug = 'coordonnees';

    /** @var array<string, mixed> | null */
    public ?array $data = [];

    public function mount(): void
    {
        $values = array_map(fn (string $key) => CompanyInfo::value($key), self::KEYS);
        $values['other_phones'] = CompanyInfo::otherPhones();

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Téléphones')
                    ->description('Affichés sur le site (page Contact, bas de page), sur les devis PDF et transmis à Google. Format conseillé : +228 90 00 00 00.')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Téléphone principal (appels)')
                            ->tel()
                            ->required()
                            ->rule(self::PHONE_RULE)
                            ->validationMessages(self::PHONE_MESSAGE),
                        TextInput::make('whatsapp')
                            ->label('Numéro WhatsApp')
                            ->helperText('Le bouton vert du site et les demandes de devis ouvrent une discussion avec ce numéro.')
                            ->tel()
                            ->required()
                            ->rule(self::PHONE_RULE)
                            ->validationMessages(self::PHONE_MESSAGE),
                        Repeater::make('other_phones')
                            ->label('Autres numéros')
                            ->helperText('Facultatif : un second téléphone, le service livraison… Ils s\'affichent sur la page Contact et sur les devis PDF.')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Intitulé')
                                    ->placeholder('Ex. : Service livraison')
                                    ->maxLength(40),
                                TextInput::make('number')
                                    ->label('Numéro')
                                    ->tel()
                                    ->required()
                                    ->rule(self::PHONE_RULE)
                                    ->validationMessages(self::PHONE_MESSAGE),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un numéro')
                            ->reorderable()
                            ->maxItems(6),
                    ]),
                Section::make('E-mail et adresse')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mail de contact')
                            ->email()
                            ->required(),
                        TextInput::make('address')
                            ->label('Adresse de la boutique')
                            ->required()
                            ->maxLength(150),
                    ]),
                Section::make('Réseaux sociaux')
                    ->description('Adresse complète de chaque page (https://…). Laissez vide pour masquer l\'icône sur le site.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('tiktok')->label('TikTok')->url()->placeholder('https://www.tiktok.com/@...'),
                        TextInput::make('facebook')->label('Facebook')->url()->placeholder('https://www.facebook.com/...'),
                        TextInput::make('instagram')->label('Instagram')->url()->placeholder('https://www.instagram.com/...'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Enregistrer')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::KEYS as $field => $key) {
            $value = $state[$field] ?? null;
            // An emptied field is stored as "" so it stays empty instead of
            // falling back to the default value.
            Setting::set($key, is_string($value) ? trim($value) : '');
        }

        $others = collect($state['other_phones'] ?? [])
            ->map(fn (array $row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'number' => trim((string) $row['number']),
            ])
            ->values()
            ->all();
        Setting::set('company_other_phones', json_encode($others, JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->title('Coordonnées mises à jour')
            ->body('Le site affiche les nouvelles coordonnées d\'ici une minute, les prochains devis PDF aussi.')
            ->success()
            ->send();
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
 * Lets the admin edit the homepage hero (hook title, text and background
 * image) without touching the code. Values are stored as settings and read
 * by the storefront through /api/v1/site-settings; an empty field falls back
 * to the default text shipped with the site.
 */
class HomepageSettings extends Page
{
    /** Form field => settings key. */
    private const KEYS = [
        'title_fr' => 'homepage_hero_title_fr',
        'subtitle_fr' => 'homepage_hero_subtitle_fr',
        'title_en' => 'homepage_hero_title_en',
        'subtitle_en' => 'homepage_hero_subtitle_en',
        'image' => 'homepage_hero_image',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|\UnitEnum|null $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = "Page d'accueil";

    protected static ?string $title = "Page d'accueil";

    protected static ?string $slug = 'page-accueil';

    /** @var array<string, mixed> | null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(array_map(fn (string $key) => Setting::get($key), self::KEYS));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make("Texte d'accroche — français")
                    ->description('Affiché en haut de la page d\'accueil. Laissez vide pour garder le texte par défaut du site.')
                    ->schema([
                        TextInput::make('title_fr')
                            ->label('Titre')
                            ->placeholder('Vous créez le décor. Nous fournissons le mobilier.')
                            ->maxLength(120),
                        Textarea::make('subtitle_fr')
                            ->label('Texte sous le titre')
                            ->placeholder('Chaises Napoléon et VIP, tables, fauteuils, accessoires...')
                            ->rows(3)
                            ->maxLength(300),
                    ]),
                Section::make("Texte d'accroche — anglais")
                    ->description('Version affichée quand le visiteur choisit EN.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('title_en')
                            ->label('Title')
                            ->placeholder('You create the setting. We supply the furniture.')
                            ->maxLength(120),
                        Textarea::make('subtitle_en')
                            ->label('Text under the title')
                            ->rows(3)
                            ->maxLength(300),
                    ]),
                Section::make('Image de fond')
                    ->description('Facultative. Sans image, l\'accroche s\'affiche sur un fond clair.')
                    ->schema([
                        FileUpload::make('image')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120),
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
            Setting::set($key, is_string($value) ? trim($value) : $value);
        }

        Notification::make()
            ->title("Page d'accueil mise à jour")
            ->body('Les changements sont visibles sur le site immédiatement.')
            ->success()
            ->send();
    }
}

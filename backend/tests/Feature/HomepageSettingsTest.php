<?php

namespace Tests\Feature;

use App\Filament\Pages\HomepageSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hero_text_is_null_until_the_admin_sets_one(): void
    {
        $this->getJson('/api/v1/site-settings')
            ->assertOk()
            ->assertJsonPath('data.homepage_hero_title', null)
            ->assertJsonPath('data.homepage_hero_subtitle', null);
    }

    public function test_the_hero_text_follows_the_visitor_language(): void
    {
        Setting::set('homepage_hero_title_fr', 'Titre français');
        Setting::set('homepage_hero_title_en', 'English title');

        $this->getJson('/api/v1/site-settings', ['Accept-Language' => 'fr'])
            ->assertJsonPath('data.homepage_hero_title', 'Titre français');
        $this->getJson('/api/v1/site-settings', ['Accept-Language' => 'en'])
            ->assertJsonPath('data.homepage_hero_title', 'English title');
    }

    public function test_the_admin_page_saves_the_hero_text(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());

        Livewire::test(HomepageSettings::class)
            ->fillForm([
                'title_fr' => '  Nouveau titre  ',
                'subtitle_fr' => 'Nouveau texte',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->getJson('/api/v1/site-settings', ['Accept-Language' => 'fr'])
            ->assertJsonPath('data.homepage_hero_title', 'Nouveau titre')
            ->assertJsonPath('data.homepage_hero_subtitle', 'Nouveau texte');
    }
}

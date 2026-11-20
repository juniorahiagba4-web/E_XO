<?php

namespace Tests\Feature;

use App\Filament\Pages\CompanyContact;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyContactTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    public function test_the_website_gets_the_default_details_until_the_admin_changes_them(): void
    {
        $this->getJson('/api/v1/site-settings')
            ->assertOk()
            ->assertJsonPath('data.company.phone', '+228 93 36 07 70')
            ->assertJsonPath('data.company.whatsapp_digits', '22893040103')
            ->assertJsonPath('data.company.other_phones', [])
            ->assertJsonPath('data.company.social.tiktok', 'https://www.tiktok.com/@laperledor.togo');
    }

    public function test_the_admin_page_saves_every_number(): void
    {
        $this->admin();
        $undo = Repeater::fake();

        Livewire::test(CompanyContact::class)
            ->fillForm([
                'phone' => '+228 90 11 22 33 ',
                'whatsapp' => '91 22 33 44',
                'other_phones' => [
                    ['label' => 'Service livraison', 'number' => '+228 92 00 00 01'],
                ],
                'email' => 'contact@laperledor-togo.com',
                'address' => 'Cité Millénium, Lomé',
                'tiktok' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $undo();

        $this->getJson('/api/v1/site-settings')
            ->assertJsonPath('data.company.phone', '+228 90 11 22 33')
            ->assertJsonPath('data.company.whatsapp', '91 22 33 44')
            // A local 8-digit WhatsApp number gets the Togo prefix for wa.me links.
            ->assertJsonPath('data.company.whatsapp_digits', '22891223344')
            ->assertJsonPath('data.company.other_phones.0.label', 'Service livraison')
            ->assertJsonPath('data.company.other_phones.0.number', '+228 92 00 00 01')
            ->assertJsonPath('data.company.address', 'Cité Millénium, Lomé')
            // An emptied social page stays hidden instead of coming back.
            ->assertJsonPath('data.company.social.tiktok', null);
    }

    public function test_a_wrong_phone_number_is_refused(): void
    {
        $this->admin();

        Livewire::test(CompanyContact::class)
            ->fillForm(['phone' => 'appelez-nous'])
            ->call('save')
            ->assertHasFormErrors(['phone']);
    }

    public function test_the_quote_pdf_shows_the_company_numbers(): void
    {
        $this->admin();
        $undo = Repeater::fake();
        Livewire::test(CompanyContact::class)
            ->fillForm([
                'phone' => '+228 90 11 22 33',
                'whatsapp' => '+228 91 22 33 44',
                'other_phones' => [['label' => 'Livraison', 'number' => '+228 92 00 00 01']],
                'email' => 'contact@laperledor-togo.com',
                'address' => 'Cité Millénium, Lomé',
            ])
            ->call('save');
        $undo();

        $reservation = Reservation::factory()->create(['customer_id' => Customer::factory()->create()->id]);
        $html = view('pdf.quote', ['reservation' => $reservation->load('items.item', 'customer')])->render();

        $this->assertStringContainsString('+228 90 11 22 33', $html);
        $this->assertStringContainsString('WhatsApp : +228 91 22 33 44', $html);
        $this->assertStringContainsString('Livraison : +228 92 00 00 01', $html);
        $this->assertStringContainsString('contact@laperledor-togo.com', $html);
    }
}

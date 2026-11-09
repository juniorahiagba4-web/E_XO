<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\RelationManagers\ItemsRelationManager;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function order(array $overrides, ?Item $item = null): Reservation
    {
        $user = User::factory()->create();
        Customer::factory()->create(['user_id' => $user->id, 'phone' => '90000000']);
        $this->actingAs($user, 'sanctum');
        $item ??= Item::factory()->create(['total_stock' => 10, 'rental_price_per_day' => 1000, 'sale_price' => 5000]);

        $this->postJson('/api/v1/reservations', array_merge([
            'type' => 'rental',
            'event_start_date' => now()->addDays(3)->toDateString(),
            'event_end_date' => now()->addDays(6)->toDateString(),
            'delivery_method' => 'delivery',
            'delivery_address' => 'Bè Kpota, Lomé',
            'delivery_time' => '09:00',
            'items' => [['item_id' => $item->id, 'quantity' => 2]],
        ], $overrides))->assertCreated();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());

        return Reservation::query()->latest('id')->firstOrFail();
    }

    public function test_a_rental_is_collected_back_at_the_delivery_time(): void
    {
        $reservation = $this->order([]);

        $this->assertSame('09:00', $reservation->return_time);
        $this->assertSame(3, $reservation->rentalDays());
        $this->assertStringContainsString('Retour du mobilier : le '.now()->addDays(6)->format('d/m/Y').' à 09h00', $reservation->whatsappMessage());
    }

    public function test_the_form_shows_rental_fields_for_a_rental(): void
    {
        $reservation = $this->order([]);

        Livewire::test(EditReservation::class, ['record' => $reservation->getRouteKey()])
            ->assertFormFieldVisible('event_start_date')
            ->assertFormFieldVisible('return_time')
            ->assertFormFieldHidden('purchase_date')
            ->assertFormFieldHidden('delivery_date')
            ->assertSee('Jours facturés');
    }

    public function test_the_form_shows_purchase_fields_for_a_purchase(): void
    {
        $reservation = $this->order(['type' => 'purchase', 'event_start_date' => null, 'event_end_date' => null]);
        $this->assertSame(now()->toDateString(), $reservation->purchase_date->toDateString());

        Livewire::test(EditReservation::class, ['record' => $reservation->getRouteKey()])
            ->assertFormFieldVisible('purchase_date')
            ->assertFormFieldVisible('delivery_date')
            ->assertFormFieldHidden('event_start_date')
            ->assertFormFieldHidden('return_time');
    }

    public function test_new_rental_dates_recompute_the_days_and_the_total(): void
    {
        $reservation = $this->order([]);
        $this->assertSame('6000.00', $reservation->total);

        Livewire::test(EditReservation::class, ['record' => $reservation->getRouteKey()])
            ->fillForm(['event_end_date' => now()->addDays(8)->toDateString()])
            ->call('save')
            ->assertHasNoFormErrors();

        $reservation->refresh();
        $this->assertSame('10000.00', $reservation->total);
        $this->assertSame(now()->addDays(8)->toDateString(), $reservation->return_date->toDateString());
        $this->assertSame('10000.00', $reservation->items()->first()->subtotal);
    }

    public function test_a_price_typed_for_a_sur_devis_item_fills_the_line_the_total_and_the_pdf(): void
    {
        $item = Item::factory()->create(['total_stock' => 10, 'sale_price' => null, 'sale_price_on_request' => true]);
        $reservation = $this->order(['type' => 'purchase', 'event_start_date' => null, 'event_end_date' => null], $item);
        $this->assertSame('0.00', $reservation->total);
        $line = $reservation->items()->first();
        $generatedAt = $reservation->quote_generated_at;

        $this->travel(1)->minutes();
        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $reservation, 'pageClass' => EditReservation::class])
            ->callTableAction('set_price', $line, ['price' => 25000])
            ->assertHasNoTableActionErrors();

        $reservation->refresh();
        $this->assertSame('25000.00', $line->fresh()->unit_sale_price);
        $this->assertSame('50000.00', $line->fresh()->subtotal);
        $this->assertSame('50000.00', $reservation->total);
        $this->assertTrue($reservation->quote_generated_at->gt($generatedAt));
        Storage::disk('public')->assertExists($reservation->quote_pdf_path);
    }

    public function test_the_review_link_only_works_once_the_order_is_finished(): void
    {
        $open = $this->order([]);
        $done = $this->order([]);
        $done->update(['status' => 'completed']);

        Livewire::test(ListReservations::class)
            ->assertSee('Demander un avis')
            ->assertSee(htmlspecialchars($done->fresh()->reviewWhatsappUrl()), escape: false)
            ->assertDontSee(htmlspecialchars($open->reviewWhatsappUrl()), escape: false);
    }

    public function test_the_customers_list_has_no_company_or_city(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());

        Livewire::test(ListCustomers::class)
            ->assertTableColumnExists('phone')
            ->assertTableColumnDoesNotExist('company_name')
            ->assertTableColumnDoesNotExist('city');
    }

    public function test_confirming_stamps_the_confirmation_date(): void
    {
        $reservation = $this->order([]);
        $reservation->update(['status' => 'confirmed']);

        $this->assertNotNull($reservation->fresh()->confirmed_at);
    }
}

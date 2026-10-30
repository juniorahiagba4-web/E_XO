<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemReview;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedOrder(string $status = 'completed'): array
    {
        $customer = Customer::factory()->create(['first_name' => 'Kossi', 'last_name' => 'Amegah']);
        $reservation = Reservation::factory()->create(['customer_id' => $customer->id, 'status' => $status]);
        $item = Item::factory()->create();
        ReservationItem::factory()->create(['reservation_id' => $reservation->id, 'item_id' => $item->id]);

        $reservation->reviewUrl();

        return [$reservation->fresh(), $item];
    }

    public function test_the_review_page_lists_the_ordered_items(): void
    {
        [$reservation, $item] = $this->completedOrder();

        $this->getJson("/api/v1/reviews/{$reservation->reference}?t={$reservation->review_token}")
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Kossi')
            ->assertJsonPath('data.items.0.item_id', $item->id)
            ->assertJsonPath('data.items.0.reviewed', false);
    }

    public function test_a_wrong_token_is_rejected(): void
    {
        [$reservation] = $this->completedOrder();

        $this->getJson("/api/v1/reviews/{$reservation->reference}?t=wrong")->assertNotFound();
    }

    public function test_an_unfinished_order_cannot_be_reviewed(): void
    {
        [$reservation] = $this->completedOrder('confirmed');

        $this->getJson("/api/v1/reviews/{$reservation->reference}?t={$reservation->review_token}")->assertNotFound();
    }

    public function test_a_submitted_review_waits_for_moderation(): void
    {
        [$reservation, $item] = $this->completedOrder();

        $this->postJson("/api/v1/reviews/{$reservation->reference}", [
            't' => $reservation->review_token,
            'reviews' => [['item_id' => $item->id, 'rating' => 5, 'comment' => 'Chaises impeccables']],
        ])->assertCreated();

        $review = ItemReview::sole();
        $this->assertSame('pending', $review->status);
        $this->assertSame('Kossi A.', $review->author_name);
        $this->assertSame($reservation->id, $review->reservation_id);

        // Not visible on the site until an admin approves it.
        $this->getJson("/api/v1/items/{$item->slug}")
            ->assertJsonPath('data.rating_count', 0)
            ->assertJsonCount(0, 'data.reviews');

        $review->update(['status' => 'approved']);

        $this->getJson("/api/v1/items/{$item->slug}")
            ->assertJsonPath('data.rating_count', 1)
            ->assertJsonPath('data.reviews.0.verified', true);
    }

    public function test_an_item_can_only_be_reviewed_once_per_order(): void
    {
        [$reservation, $item] = $this->completedOrder();
        $payload = ['t' => $reservation->review_token, 'reviews' => [['item_id' => $item->id, 'rating' => 4]]];

        $this->postJson("/api/v1/reviews/{$reservation->reference}", $payload)->assertCreated();
        $this->postJson("/api/v1/reviews/{$reservation->reference}", $payload)->assertStatus(422);

        $this->assertSame(1, ItemReview::count());
    }

    public function test_items_outside_the_order_cannot_be_reviewed(): void
    {
        [$reservation] = $this->completedOrder();
        $other = Item::factory()->create();

        $this->postJson("/api/v1/reviews/{$reservation->reference}", [
            't' => $reservation->review_token,
            'reviews' => [['item_id' => $other->id, 'rating' => 5]],
        ])->assertStatus(422);
    }

    public function test_the_whatsapp_link_carries_the_review_url(): void
    {
        [$reservation] = $this->completedOrder();
        $reservation->customer->update(['phone' => '90 12 34 56']);

        $url = $reservation->fresh()->reviewWhatsappUrl();

        $this->assertStringStartsWith('https://wa.me/22890123456?text=', $url);
        $this->assertStringContainsString(rawurlencode($reservation->review_token), $url);
    }
}

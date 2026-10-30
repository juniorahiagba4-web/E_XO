<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemReview;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Customer reviews, reachable only through the private link sent once an
 * order is completed (see Reservation::reviewUrl()). New reviews wait for
 * moderation in the admin panel before they appear on the site.
 */
class ReviewController extends Controller
{
    public function show(Request $request, string $reference): JsonResponse
    {
        $reservation = $this->findReviewable($request, $reference);
        $reviewedItemIds = $reservation->reviews()->pluck('item_id')->all();

        return response()->json([
            'data' => [
                'reference' => $reservation->reference,
                'first_name' => $reservation->customer ? Str::title($reservation->customer->first_name) : null,
                'items' => $this->reviewableLines($reservation)->map(fn ($line) => [
                    'item_id' => $line->item->id,
                    'name' => $line->item->getLocalized('name'),
                    'image_url' => $line->item->image_url,
                    'reviewed' => in_array($line->item->id, $reviewedItemIds, true),
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request, string $reference): JsonResponse
    {
        $reservation = $this->findReviewable($request, $reference);

        $validated = $request->validate([
            'reviews' => ['required', 'array', 'min:1'],
            'reviews.*.item_id' => ['required', 'integer', 'distinct'],
            'reviews.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviews.*.comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $orderedItemIds = $this->reviewableLines($reservation)->pluck('item.id')->all();
        $reviewedItemIds = $reservation->reviews()->pluck('item_id')->all();
        $customer = $reservation->customer;
        $authorName = trim(Str::title($customer?->first_name ?? '').' '.Str::upper(Str::substr($customer?->last_name ?? '', 0, 1)).'.');

        $created = 0;
        foreach ($validated['reviews'] as $review) {
            if (! in_array($review['item_id'], $orderedItemIds, true)) {
                throw ValidationException::withMessages([
                    'reviews' => 'Cet article ne fait pas partie de votre commande.',
                ]);
            }
            if (in_array($review['item_id'], $reviewedItemIds, true)) {
                continue;
            }

            ItemReview::create([
                'item_id' => $review['item_id'],
                'reservation_id' => $reservation->id,
                'customer_id' => $customer?->id,
                'author_name' => $authorName !== '.' ? $authorName : 'Client',
                'rating' => $review['rating'],
                'comment' => filled($review['comment'] ?? null) ? trim($review['comment']) : null,
                'status' => 'pending',
            ]);
            $created++;
        }

        if ($created === 0) {
            throw ValidationException::withMessages([
                'reviews' => 'Vous avez déjà donné votre avis sur ces articles.',
            ]);
        }

        return response()->json(['created' => $created], 201);
    }

    private function findReviewable(Request $request, string $reference): Reservation
    {
        $token = (string) $request->query('t', $request->input('t', ''));

        $reservation = Reservation::query()
            ->with(['customer', 'items.item'])
            ->where('reference', $reference)
            ->whereNotNull('review_token')
            ->first();

        abort_unless(
            $reservation && $token !== '' && hash_equals($reservation->review_token, $token) && $reservation->canBeReviewed(),
            404,
        );

        return $reservation;
    }

    /** One line per distinct item still in the catalogue. */
    private function reviewableLines(Reservation $reservation)
    {
        return $reservation->items
            ->filter(fn ($line) => $line->item !== null)
            ->unique('item_id');
    }
}

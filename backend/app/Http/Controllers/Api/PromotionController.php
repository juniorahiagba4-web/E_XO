<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromotionResource;
use App\Models\Item;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromotionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $promotions = Promotion::query()
            ->with(['category', 'item.category'])
            ->currentlyActive()
            ->orderBy('ends_at')
            ->get();

        return PromotionResource::collection($promotions);
    }

    /**
     * Validate a promo code against the items currently in the customer's
     * cart. This only previews whether the code applies — the actual
     * discount is always recomputed server-side when the order is created,
     * never trusted from the client.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:items,id'],
        ]);

        $promotion = Promotion::query()
            ->with(['category', 'item.category'])
            ->currentlyActive()
            ->forCode($data['code'])
            ->first();

        if (! $promotion) {
            return response()->json(['message' => 'Code promo invalide ou expiré.'], 404);
        }

        $items = Item::query()->whereIn('id', $data['item_ids'])->get();

        if (! $items->contains(fn (Item $item) => $promotion->appliesToItem($item))) {
            return response()->json(['message' => "Ce code promo ne s'applique à aucun article de votre panier."], 422);
        }

        return response()->json(['data' => new PromotionResource($promotion)]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $request->user()->favoriteItems()->with('category')->latest('favorites.created_at')->get();

        return ItemResource::collection($items);
    }

    public function toggle(Request $request, Item $item): JsonResponse
    {
        $user = $request->user();
        $alreadyFavorited = $user->favoriteItems()->where('item_id', $item->id)->exists();

        if ($alreadyFavorited) {
            $user->favoriteItems()->detach($item->id);
        } else {
            $user->favoriteItems()->syncWithoutDetaching([$item->id]);
        }

        return response()->json(['favorited' => ! $alreadyFavorited]);
    }
}

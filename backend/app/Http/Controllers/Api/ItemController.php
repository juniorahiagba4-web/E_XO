<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ItemController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function index(Request $request)
    {
        $items = Item::query()
            ->withSum('reservationItems as popularity', 'quantity')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with('category')
            ->where('is_active', true)
            ->when($request->string('category')->isNotEmpty(), function ($query) use ($request) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
            })
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q->where('name_fr', 'like', $search)->orWhere('name_en', 'like', $search));
            })
            ->when($request->filled('min_price'), fn ($query) => $query->where('rental_price_per_day', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn ($query) => $query->where('rental_price_per_day', '<=', $request->float('max_price')));

        match ($request->string('sort')->toString()) {
            'price_asc' => $items->orderBy('rental_price_per_day'),
            'price_desc' => $items->orderByDesc('rental_price_per_day'),
            'popular' => $items->orderByDesc('popularity'),
            'newest' => $items->orderByDesc('created_at'),
            default => $items->orderBy('name_fr'),
        };

        $items = $items->paginate(24);

        return ItemResource::collection($items);
    }

    public function show(string $slug)
    {
        $item = Item::query()
            ->with(['category', 'reviews'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $related = Item::query()
            ->with('category')
            ->where('is_active', true)
            ->where('id', '!=', $item->id)
            ->when($item->category_id, fn ($query) => $query->where('category_id', $item->category_id))
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return (new ItemResource($item))->additional([
            'related' => ItemResource::collection($related),
        ]);
    }

    public function availability(Request $request, Item $item)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        $available = $this->availability->availableQuantity($item, $start, $end);

        return response()->json([
            'item_id' => $item->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_stock' => $item->total_stock,
            'reserved_quantity' => $item->total_stock - $available,
            'available_quantity' => $available,
        ]);
    }
}

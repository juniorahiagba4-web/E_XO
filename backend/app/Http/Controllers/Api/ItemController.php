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
            ->with('category')
            ->where('is_active', true)
            ->when($request->string('category')->isNotEmpty(), function ($query) use ($request) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
            })
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q->where('name_fr', 'like', $search)->orWhere('name_en', 'like', $search));
            })
            ->orderBy('name_fr')
            ->paginate(24);

        return ItemResource::collection($items);
    }

    public function show(string $slug)
    {
        $item = Item::query()->with('category')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return new ItemResource($item);
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

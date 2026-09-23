<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    /**
     * Publicly exposes a whitelisted subset of settings the storefront
     * reads — never the whole key/value table.
     */
    public function index(): JsonResponse
    {
        $heroImagePath = Setting::get('homepage_hero_image');

        return response()->json([
            'data' => [
                'homepage_hero_image_url' => $heroImagePath ? Storage::disk('public')->url($heroImagePath) : null,
            ],
        ]);
    }
}

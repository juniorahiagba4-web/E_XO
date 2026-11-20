<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** Readable names for the keys the site knows, shown in the admin list. */
    public const LABELS = [
        'homepage_hero_image' => 'Accueil — image de fond',
        'homepage_hero_title_fr' => 'Accueil — titre (français)',
        'homepage_hero_subtitle_fr' => "Accueil — texte d'accroche (français)",
        'homepage_hero_title_en' => 'Accueil — titre (anglais)',
        'homepage_hero_subtitle_en' => "Accueil — texte d'accroche (anglais)",
        'company_phone' => 'Entreprise — téléphone principal',
        'company_whatsapp' => 'Entreprise — numéro WhatsApp',
        'company_other_phones' => 'Entreprise — autres numéros',
        'company_email' => 'Entreprise — e-mail',
        'company_address' => 'Entreprise — adresse',
        'company_tiktok_url' => 'Entreprise — page TikTok',
        'company_facebook_url' => 'Entreprise — page Facebook',
        'company_instagram_url' => 'Entreprise — page Instagram',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget("setting.{$setting->key}"));
        static::deleted(fn (Setting $setting) => Cache::forget("setting.{$setting->key}"));
    }
}

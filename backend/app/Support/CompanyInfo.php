<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The company's contact details (phones, WhatsApp, e-mail, address, social
 * pages), edited in the admin "Coordonnées de l'entreprise" page and read by
 * the website, the quote PDF and the API. Stored as settings; a missing value
 * falls back to the default below.
 */
class CompanyInfo
{
    /** Settings key => default value. */
    public const DEFAULTS = [
        'company_phone' => '+228 93 36 07 70',
        'company_whatsapp' => '+228 93 04 01 03',
        'company_other_phones' => '[]',
        'company_email' => 'contact@laperledor-togo.com',
        'company_address' => 'Cité Millénium, face restaurant Le Beffcut, Lomé, Togo',
        'company_tiktok_url' => 'https://www.tiktok.com/@laperledor.togo',
        'company_facebook_url' => null,
        'company_instagram_url' => null,
    ];

    public static function value(string $key): mixed
    {
        $value = Setting::get($key);

        // null = never saved: use the default. "" = deliberately emptied.
        return $value === null ? self::DEFAULTS[$key] : ($value === '' ? null : $value);
    }

    /** @return list<array{label: string, number: string}> */
    public static function otherPhones(): array
    {
        $decoded = json_decode((string) self::value('company_other_phones'), true);

        return collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($row) => is_array($row) && trim((string) ($row['number'] ?? '')) !== '')
            ->map(fn (array $row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'number' => trim((string) $row['number']),
            ])
            ->values()
            ->all();
    }

    /** Digits only, with the Togo prefix added to a local 8-digit number (for wa.me links). */
    public static function whatsappDigits(): string
    {
        $digits = preg_replace('/\D+/', '', (string) self::value('company_whatsapp'));

        return strlen($digits) === 8 ? '228'.$digits : $digits;
    }

    /** Everything the website needs, in one array. */
    public static function forWebsite(): array
    {
        return [
            'phone' => self::value('company_phone'),
            'whatsapp' => self::value('company_whatsapp'),
            'whatsapp_digits' => self::whatsappDigits(),
            'other_phones' => self::otherPhones(),
            'email' => self::value('company_email'),
            'address' => self::value('company_address'),
            'social' => [
                'tiktok' => self::value('company_tiktok_url'),
                'facebook' => self::value('company_facebook_url'),
                'instagram' => self::value('company_instagram_url'),
            ],
        ];
    }
}

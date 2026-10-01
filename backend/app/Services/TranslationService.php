<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fills the `_en` half of the site's bilingual admin fields (item/category
 * name and description) automatically from the `_fr` value via the DeepL
 * API, so staff only ever type the French text. Only fields left blank by
 * the admin are translated — anything typed manually in English is always
 * left untouched. Silently does nothing (no error surfaced to the admin) if
 * no API key is configured or the API call fails, since a missing English
 * translation is never worse than blocking the save.
 */
class TranslationService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function fillMissingItemTranslations(array $data): array
    {
        foreach (['name', 'description'] as $field) {
            $frKey = "{$field}_fr";
            $enKey = "{$field}_en";

            if (empty($data[$enKey] ?? null) && ! empty($data[$frKey] ?? null)) {
                $translated = $this->translateToEnglish($data[$frKey]);

                if ($translated !== null) {
                    $data[$enKey] = $translated;
                }
            }
        }

        return $data;
    }

    public function translateToEnglish(string $text): ?string
    {
        $apiKey = config('services.deepl.key');

        if (! $apiKey) {
            return null;
        }

        $endpoint = config('services.deepl.free', true)
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                ->post($endpoint, [
                    'text' => $text,
                    'source_lang' => 'FR',
                    'target_lang' => 'EN',
                ]);

            if (! $response->successful()) {
                Log::warning('DeepL translation request failed', ['status' => $response->status()]);

                return null;
            }

            return $response->json('translations.0.text');
        } catch (Throwable $e) {
            Log::warning('DeepL translation request threw', ['exception' => $e->getMessage()]);

            return null;
        }
    }
}

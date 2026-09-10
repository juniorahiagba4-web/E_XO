<?php

namespace App\Models\Concerns;

/**
 * Simple two-locale (fr/en) field support, backed by plain `{field}_fr` /
 * `{field}_en` columns instead of a JSON translations column. Filament 4 has
 * no maintained translatable-fields plugin yet, so this keeps admin forms
 * and API resources straightforward: two ordinary text columns per field.
 */
trait HasLocalizedFields
{
    public function getLocalized(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $column = "{$field}_{$locale}";

        return $this->{$column} ?: $this->{"{$field}_fr"};
    }
}

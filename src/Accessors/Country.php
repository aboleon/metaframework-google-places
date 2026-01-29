<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Accessors;

use Illuminate\Support\Facades\Cache;

class Country
{
    /** @var array<string, array<string, string>> */
    private static array $countriesCache = [];

    public static function resetCache(): void
    {
        self::$countriesCache = [];
    }

    /**
     * @return array<mixed>
     */
    public static function orderedCodeNameArray(): array
    {
        $countries = self::getCountriesByLocale(app()->getLocale());
        if (!$countries) {
            return [];
        }

        asort($countries, SORT_NATURAL | SORT_FLAG_CASE);

        return $countries;
    }

    public static function getCountryNameByCode(?string $code = null): string
    {
        return self::getCountryNameByCodeAndLocale($code, app()->getLocale());
    }

    public static function getCountryNameByCodeAndLocale(?string $code = null, ?string $locale = null): string
    {
        if (empty($code)) {
            return '';
        }

        $countries = self::getCountriesByLocale($locale);
        if (!$countries) {
            return '';
        }

        $code = strtoupper($code);

        return $countries[$code] ?? '';
    }

    /**
     * @return array<string, string>
     */
    private static function getCountriesByLocale(?string $locale = null): array
    {
        $locale = self::resolveLocale($locale ?: app()->getLocale());

        if (isset(self::$countriesCache[$locale])) {
            return self::$countriesCache[$locale];
        }

        return self::$countriesCache[$locale] = Cache::rememberForever('countries_' . $locale, function () use ($locale): array {
            $path = __DIR__ . '/../../publishable/countries/' . $locale . '/countries.json';
            if (!is_file($path)) {
                return [];
            }

            $decoded = json_decode((string) file_get_contents($path), true);
            if (!is_array($decoded)) {
                return [];
            }

            return $decoded;
        });
    }

    private static function normalizeLocale(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));
        if ($locale === '') {
            return 'en';
        }

        $normalized = preg_split('/[-_]/', $locale);

        return $normalized[0] ?? 'en';
    }

    private static function resolveLocale(?string $locale): string
    {
        $locale = self::normalizeLocale($locale);
        $dir = __DIR__ . '/../../publishable/countries/' . $locale;
        if (!is_dir($dir)) {
            return 'en';
        }

        return $locale;
    }
}

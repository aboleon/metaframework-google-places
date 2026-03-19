<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Accessors;

class Address
{
    /**
     * @return array<int, string>
     */
    public static function normalizedLines(object $address, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return array_values(array_filter([
            trim(implode(' ', array_filter([
                $address->street_number ?? null,
                self::translatedField($address, 'route', $locale),
            ], static fn ($value) => $value !== null && $value !== ''))),
            trim(implode(' ', array_filter([
                $address->postal_code ?? null,
                self::translatedField($address, 'locality', $locale),
            ], static fn ($value) => $value !== null && $value !== ''))),
            Country::getCountryNameByCodeAndLocale($address->country_code ?? null, $locale),
        ], static fn ($value) => is_string($value) && trim($value) !== ''));
    }

    public static function printNormalizedAddress(object $address, ?string $locale = null): string
    {
        return implode(PHP_EOL, self::normalizedLines($address, $locale));
    }

    private static function translatedField(object $address, string $field, string $locale): ?string
    {
        if (method_exists($address, 'getTranslation')) {
            $value = $address->getTranslation($field, $locale);

            return is_string($value) ? $value : null;
        }

        if (method_exists($address, 'getTranslations')) {
            $translations = $address->getTranslations($field);
            if (is_array($translations) && array_key_exists($locale, $translations)) {
                return is_string($translations[$locale]) ? $translations[$locale] : null;
            }
        }

        $value = $address->{$field} ?? null;
        if (is_array($value)) {
            $value = $value[$locale] ?? null;
        }

        return is_string($value) ? $value : null;
    }
}

# MetaFramework Google Places

[![Tests](https://github.com/aboleon/metaframework-google-places/actions/workflows/tests.yml/badge.svg)](https://github.com/aboleon/metaframework-google-places/actions)
[![codecov](https://codecov.io/gh/aboleon/metaframework-google-places/graph/badge.svg)](https://codecov.io/gh/aboleon/metaframework-google-places)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/aboleon/metaframework-google-places.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-google-places)
[![Total Downloads](https://img.shields.io/packagist/dt/aboleon/metaframework-google-places.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-google-places)
[![PHP Version](https://img.shields.io/packagist/php-v/aboleon/metaframework-google-places.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-google-places)
[![License](https://img.shields.io/packagist/l/aboleon/metaframework-google-places.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-google-places)

Google Places address autocomplete component for Laravel

## Requirements

- PHP ^8.3
- Laravel ^11.0 | ^12.0
- MetaFramework Inputable

## Installation

```bash
composer require aboleon/metaframework-google-places
```

Publish assets/config/translations:

```bash
php artisan vendor:publish --tag=mfw-google-places-config
php artisan vendor:publish --tag=mfw-google-places-translations
php artisan vendor:publish --tag=mfw-google-places-assets
```

## Configuration

`config/mfw-google-places.php`:

```php
return [
    'google' => [
        'places_api_key' => env('MFW_GOOGLE_PLACES_KEY', ''),
    ],
    'countries_resolver' => null,
];
```

Resolver usage:
- Set `countries_resolver` to a class that implements `getCountryNameByCode($code)` (and optional locale-aware `getCountryNameByCodeAndLocale`).
- Example: `MetaFramework\GooglePlaces\Accessors\Country::class` to resolve country names from ISO-2 codes and locale files.

## Usage

```blade
<x-google-places
    :model="$address"
    field="address"
    label="Address"
    :params="['required' => ['route', 'postal_code', 'locality']]"
    :hidden="['administrative_area_level_2']"
    :showCoords="true"
/>
```

## Artisan Commands

```bash
php artisan mfw-google-places:make-geo-model
php artisan mfw-google-places:make-geo-for-model {model?}
php artisan mfw-google-places:publish-component [--force]
php artisan mfw-google-places:publish-request [--force]
```

- `mfw-google-places:make-geo-model` generates a new Geo model and migration with all Google Places fields.
- `mfw-google-places:make-geo-for-model` adds a Google Places Geo relation and migration to an existing model (optionally provide the model class).
- `mfw-google-places:publish-component` publishes the component view to `resources/views/vendor/mfw-google-places/components/google-places.blade.php` for customization (use `--force` to overwrite).
- `mfw-google-places:publish-request` publishes a `GooglePlacesRequest` to `app/Http/Requests` for customization (use `--force` to overwrite).

Validation example:

```php
use MetaFramework\GooglePlaces\Validation\GoogleAddressValidation;

$rules = (new GoogleAddressValidation())
    ->setPrefix('address')
    ->setRequiredFields(['route', 'postal_code', 'locality'])
    ->rules();
```

Customize the published request:

```bash
php artisan mfw-google-places:publish-request
```

```php
// app/Http/Requests/GooglePlacesRequest.php
public function rules(): array
{
    return (new GoogleAddressValidation())
        ->setPrefix('address')
        ->setRequiredFields(['route', 'postal_code', 'locality'])
        ->rules();
}
```

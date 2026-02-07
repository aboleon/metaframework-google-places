<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Console\Concerns;

trait InteractsWithGooglePlacesFields
{
    private const GOOGLE_PLACES_FILLABLE_FIELDS = [
        'text_address',
        'street_number',
        'route',
        'postal_code',
        'locality',
        'administrative_area_level_1',
        'administrative_area_level_1_short',
        'administrative_area_level_2',
        'country_code',
        'lat',
        'lon',
        'place_id',
    ];

    private const GOOGLE_PLACES_MIGRATION_FIELDS = [
        "\$table->text('text_address')->nullable();",
        "\$table->string('street_number')->nullable();",
        "\$table->longtext('route')->nullable();",
        "\$table->string('postal_code')->nullable();",
        "\$table->longtext('locality')->nullable();",
        "\$table->longtext('administrative_area_level_1')->nullable();",
        "\$table->longtext('administrative_area_level_1_short')->nullable();",
        "\$table->longtext('administrative_area_level_2')->nullable();",
        "\$table->string('country_code', 10)->nullable();",
        "\$table->decimal('lat', 10, 7)->nullable();",
        "\$table->decimal('lon', 10, 7)->nullable();",
        "\$table->string('place_id')->nullable();",
    ];

    protected function buildGooglePlacesFillableString(int $indentSpaces = 8): string
    {
        $indent = str_repeat(' ', $indentSpaces);

        return implode(
            ",\n",
            array_map(
                static fn (string $field): string => "{$indent}'{$field}'",
                self::GOOGLE_PLACES_FILLABLE_FIELDS
            )
        );
    }

    protected function buildGooglePlacesMigrationFieldsString(int $indentSpaces = 12): string
    {
        $indent = str_repeat(' ', $indentSpaces);

        return implode(
            "\n",
            array_map(
                static fn (string $line): string => $indent . $line,
                self::GOOGLE_PLACES_MIGRATION_FIELDS
            )
        );
    }
}

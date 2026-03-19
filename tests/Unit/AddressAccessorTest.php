<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Unit;

use MetaFramework\GooglePlaces\Accessors\Address;
use MetaFramework\GooglePlaces\Tests\TestCase;

class AddressAccessorTest extends TestCase
{
    public function test_it_formats_a_normalized_multiline_address(): void
    {
        $address = new class
        {
            public string $street_number = '18';

            public string $postal_code = '95150';

            public string $country_code = 'FR';

            public function getTranslation(string $field, string $locale): ?string
            {
                return match ($field . ':' . $locale) {
                    'route:fr' => 'Rue Phanie Leleu',
                    'locality:fr' => 'Taverny',
                    default => null,
                };
            }
        };

        $formatted = Address::printNormalizedAddress($address, 'fr');

        $this->assertSame(
            implode(PHP_EOL, ['18 Rue Phanie Leleu', '95150 Taverny', 'France']),
            $formatted
        );
    }
}

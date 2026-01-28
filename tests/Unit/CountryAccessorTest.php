<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use MetaFramework\GooglePlaces\Accessors\Country;
use MetaFramework\GooglePlaces\Tests\TestCase;

class CountryAccessorTest extends TestCase
{
    public function test_it_resolves_country_name_from_locale(): void
    {
        app()->setLocale('en');

        $name = Country::getCountryNameByCode('fr');

        $this->assertSame('France', $name);
        $this->assertTrue(Cache::has('countries_en'));
    }

    public function test_it_falls_back_to_en_when_locale_is_missing(): void
    {
        app()->setLocale('xx');

        $name = Country::getCountryNameByCode('fr');

        $this->assertSame('France', $name);
        $this->assertTrue(Cache::has('countries_en'));
    }
}

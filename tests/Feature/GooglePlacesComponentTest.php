<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use MetaFramework\GooglePlaces\Components\Form;
use MetaFramework\GooglePlaces\Tests\TestCase;

class GooglePlacesComponentTest extends TestCase
{
    public function test_component_renders_model_values(): void
    {
        Blade::componentNamespace(
            'MetaFramework\\GooglePlaces\\Tests\\Stubs\\Components',
            'mfw-inputable'
        );
        View::share('errors', new ViewErrorBag);

        $publishedView = resource_path('views/components/google-places.blade.php');
        if (File::exists($publishedView)) {
            File::delete($publishedView);
        }

        $model = (object) [
            'text_address' => '123 Main St',
            'street_number' => '123',
            'route' => 'Main St',
            'postal_code' => '12345',
            'locality' => 'Springfield',
            'administrative_area_level_1' => 'State',
            'administrative_area_level_1_short' => 'ST',
            'administrative_area_level_2' => 'County',
            'country' => 'United States',
            'country_code' => 'US',
            'lat' => 1.23,
            'lon' => 4.56,
            'place_id' => 'place-123',
        ];

        $html = Blade::render('<x-mfw-google-places::form :model="$model" />', [
            'model' => $model,
        ]);

        $this->assertStringContainsString('name="mfw_google_places[text_address]"', $html);
        $this->assertStringContainsString('value="123 Main St"', $html);
        $this->assertStringContainsString('name="mfw_google_places[place_id]"', $html);
        $this->assertStringContainsString('value="place-123"', $html);
    }

    public function test_component_sets_error_when_model_missing(): void
    {
        $component = new Form;

        $this->assertSame(__('mfw-google-places.missing_model'), $component->error);
    }
}

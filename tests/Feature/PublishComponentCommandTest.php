<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Feature;

use Illuminate\Support\Facades\File;
use MetaFramework\GooglePlaces\Tests\TestCase;

class PublishComponentCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $destinationComponent = app_path('View/Components/GooglePlaces.php');
        $destinationView = resource_path('views/components/google-places.blade.php');

        if (File::exists($destinationComponent)) {
            File::delete($destinationComponent);
        }

        if (File::exists($destinationView)) {
            File::delete($destinationView);
        }
    }

    public function test_it_publishes_component_view(): void
    {
        $this->artisan('mfw-google-places:publish-component')
            ->assertExitCode(0);

        $expectedComponent = app_path('View/Components/GooglePlaces.php');
        $expectedView = resource_path('views/components/google-places.blade.php');
        $this->assertFileExists($expectedComponent);
        $this->assertFileExists($expectedView);
    }

    public function test_it_does_not_overwrite_without_force(): void
    {
        $destinationComponent = app_path('View/Components/GooglePlaces.php');
        $destinationView = resource_path('views/components/google-places.blade.php');
        File::ensureDirectoryExists(dirname($destinationComponent));
        File::ensureDirectoryExists(dirname($destinationView));
        File::put($destinationComponent, 'original component');
        File::put($destinationView, 'original view');

        $this->artisan('mfw-google-places:publish-component')
            ->assertExitCode(0);

        $this->assertSame('original component', File::get($destinationComponent));
        $this->assertSame('original view', File::get($destinationView));
    }
}

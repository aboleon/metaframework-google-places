<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Feature;

use Illuminate\Support\Facades\File;
use MetaFramework\GooglePlaces\Tests\TestCase;

class PublishComponentCommandTest extends TestCase
{
    public function test_it_publishes_component_view(): void
    {
        $this->artisan('mfw-google-places:publish-component')
            ->assertExitCode(0);

        $expected = resource_path('views/vendor/mfw-google-places/components/google-places.blade.php');
        $this->assertFileExists($expected);
    }

    public function test_it_does_not_overwrite_without_force(): void
    {
        $destination = resource_path('views/vendor/mfw-google-places/components/google-places.blade.php');
        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, 'original');

        $this->artisan('mfw-google-places:publish-component')
            ->assertExitCode(0);

        $this->assertSame('original', File::get($destination));
    }
}

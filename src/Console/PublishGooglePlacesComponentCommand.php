<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishGooglePlacesComponentCommand extends Command
{
    protected $signature = 'mfw-google-places:publish-component {--force : Overwrite the published view if it already exists}';

    protected $description = 'Publish the Google Places component view for customization';

    public function handle(): int
    {
        $source = __DIR__ . '/../resources/views/components/google-places.blade.php';
        $destination = resource_path('views/vendor/mfw-google-places/components/google-places.blade.php');

        if (!is_file($source)) {
            $this->error('Source component view not found.');
            $this->line('Expected: ' . $source);

            return self::FAILURE;
        }

        if (is_file($destination) && !$this->option('force')) {
            $this->warn('Component view already published. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $filesystem = new Filesystem;
        $filesystem->ensureDirectoryExists(dirname($destination));
        $filesystem->copy($source, $destination);

        $this->info('Published: ' . $destination);
        $this->line('You can now customize it while still using <x-google-places>.');

        return self::SUCCESS;
    }
}

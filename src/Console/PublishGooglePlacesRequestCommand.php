<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishGooglePlacesRequestCommand extends Command
{
    protected $signature = 'mfw-google-places:publish-request {--force : Overwrite the published request if it already exists}';

    protected $description = 'Publish the Google Places request class for customization';

    public function handle(): int
    {
        $source = __DIR__ . '/../Http/Requests/GooglePlacesRequest.php';
        $destination = app_path('Http/Requests/GooglePlacesRequest.php');

        if (!is_file($source)) {
            $this->error('Source request class not found.');
            $this->line('Expected: ' . $source);

            return self::FAILURE;
        }

        if (is_file($destination) && !$this->option('force')) {
            $this->warn('Request class already published. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $filesystem = new Filesystem;
        $filesystem->ensureDirectoryExists(dirname($destination));
        $filesystem->copy($source, $destination);

        $content = $filesystem->get($destination);
        $content = str_replace(
            'namespace MetaFramework\\GooglePlaces\\Http\\Requests;',
            'namespace App\\Http\\Requests;',
            $content
        );
        $filesystem->put($destination, $content);

        $this->info('Published: ' . $destination);
        $this->line('Customize it and use App\\Http\\Requests\\GooglePlacesRequest in your controllers.');

        return self::SUCCESS;
    }
}

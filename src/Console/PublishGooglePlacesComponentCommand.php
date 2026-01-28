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
        $sourceComponent = __DIR__ . '/../Components/Form.php';
        $sourceView = __DIR__ . '/../resources/views/components/form.blade.php';
        $destinationComponent = app_path('View/Components/GooglePlaces.php');
        $destinationView = resource_path('views/components/google-places.blade.php');

        if (!is_file($sourceComponent) || !is_file($sourceView)) {
            $this->error('Source component files not found.');
            $this->line('Expected: ' . $sourceComponent);
            $this->line('Expected: ' . $sourceView);

            return self::FAILURE;
        }

        if ((is_file($destinationComponent) || is_file($destinationView)) && !$this->option('force')) {
            $this->warn('Component already published. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $filesystem = new Filesystem;
        $filesystem->ensureDirectoryExists(dirname($destinationComponent));
        $filesystem->ensureDirectoryExists(dirname($destinationView));

        $componentContents = $filesystem->get($sourceComponent);
        $componentContents = str_replace(
            [
                'namespace MetaFramework\\GooglePlaces\\Components;',
                'class Form extends Component',
                "return view('mfw-google-places::components.form');",
            ],
            [
                'namespace App\\View\\Components;',
                'class GooglePlaces extends Component',
                "return view('components.google-places');",
            ],
            $componentContents
        );

        $filesystem->put($destinationComponent, $componentContents);
        $filesystem->copy($sourceView, $destinationView);

        $this->info('Published: ' . $destinationComponent);
        $this->info('Published: ' . $destinationView);
        $this->line('You can now customize it while still using <x-google-places>.');

        return self::SUCCESS;
    }
}

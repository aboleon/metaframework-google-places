<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use MetaFramework\GooglePlaces\Components\GooglePlaces;
use MetaFramework\GooglePlaces\Console\MakeGeoForModelCommand;
use MetaFramework\GooglePlaces\Console\MakeGooglePlacesModelCommand;
use MetaFramework\GooglePlaces\Console\PublishGooglePlacesComponentCommand;
use MetaFramework\GooglePlaces\Console\PublishGooglePlacesRequestCommand;

class GooglePlacesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'mfw-google-places');
        $appLangPath = lang_path();

        $this->loadTranslationsFrom(__DIR__ . '/../publishable/lang', 'mfw-google-places');

        if (is_dir($appLangPath)) {
            $this->loadTranslationsFrom($appLangPath, 'mfw-google-places');
        }

        Blade::componentNamespace('MetaFramework\GooglePlaces\Components', 'mfw-google-places');
        Blade::component('google-places', GooglePlaces::class);

        $this->publishes([
            __DIR__ . '/../publishable/config/mfw-google-places.php' => config_path('mfw-google-places.php'),
        ], 'mfw-google-places-config');

        $this->publishes([
            __DIR__ . '/../publishable/lang' => lang_path(),
        ], 'mfw-google-places-translations');

        $this->publishes([
            __DIR__ . '/../publishable/assets' => public_path('vendor/mfw-google-places'),
        ], 'mfw-google-places-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeGooglePlacesModelCommand::class,
                MakeGeoForModelCommand::class,
                PublishGooglePlacesComponentCommand::class,
                PublishGooglePlacesRequestCommand::class,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests;

use MetaFramework\GooglePlaces\GooglePlacesServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public static function applicationBasePath()
    {
        $root = realpath(__DIR__ . '/../');
        $target = sys_get_temp_dir() . '/mfw-google-places-testbench/laravel';
        $source = $root . '/vendor/orchestra/testbench-core/laravel';

        if (!is_dir($target . '/bootstrap')) {
            self::copyDirectory($source, $target);
        }

        return $target;
    }

    protected function setUp(): void
    {
        $base = static::applicationBasePath();
        $cache = $base . '/bootstrap/cache';

        if (!is_dir($cache) && !mkdir($cache, 0777, true) && !is_dir($cache)) {
            throw new \RuntimeException('Could not create testbench bootstrap cache directory.');
        }

        parent::setUp();

        $this->app['cache']->flush();
    }

    protected function getPackageProviders($app): array
    {
        return [
            GooglePlacesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            throw new \RuntimeException('Testbench source skeleton not found.');
        }

        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new \RuntimeException('Could not create testbench base directory.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0777, true) && !is_dir($targetPath)) {
                    throw new \RuntimeException('Could not create testbench directory: ' . $targetPath);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }
}

<?php

namespace Developerabod\LaravelContactExporter\Providers;

use Illuminate\Support\ServiceProvider;
use Developerabod\LaravelContactExporter\VCardExporter;

class VCardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/vcard-exporter.php',
            'vcard-exporter'
        );

        // bind (not singleton) — fresh instance on every Facade resolve
        // required because VCardExporter holds state and must not be shared between requests
        $this->app->bind(VCardExporter::class, fn () => new VCardExporter());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/vcard-exporter.php' => config_path('vcard-exporter.php'),
            ], 'vcard-exporter-config');
        }
    }
}
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Knp\Snappy\Pdf;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Snappy PDF wrapper
        $this->app->bind('snappy.pdf.wrapper', function ($app) {
            $binary = config('snappy.pdf.binary', '/usr/local/bin/wkhtmltopdf');
            $options = config('snappy.pdf.options', []);

            $snappy = new Pdf($binary, $options);
            $snappy->setTimeout(config('snappy.pdf.timeout', 60));

            return new \App\Services\SnappyPdfWrapper($snappy);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

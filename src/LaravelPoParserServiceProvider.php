<?php namespace EricLagarda\LaravelPoParser;

use App;
use Illuminate\Support\ServiceProvider;

class LaravelPoParserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('poparser', function()
        {
            return new \EricLagarda\LaravelPoParser\ClassPoParser;
        });
    }
}

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
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('laravel-po-parser.php')
        ]);
        
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

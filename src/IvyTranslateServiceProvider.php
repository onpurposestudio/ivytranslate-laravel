<?php

namespace IvyTranslate;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use IvyTranslate\Commands\StatusCommand;

class IvyTranslateServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ivytranslate.php',
            'ivytranslate',
        );
    }

    public function boot()
    {
        AboutCommand::add('Ivy Translate', fn () => [
            'Version' => '0.0.1',
        ]);

        $this->publishes([
            __DIR__.'/../config/ivytranslate.php' => config_path('ivytranslate.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                StatusCommand::class,
            ]);
        }
    }
}

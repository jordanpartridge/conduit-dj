<?php

declare(strict_types=1);

namespace JordanPartridge\ConduitDj;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JordanPartridge\ConduitDj\Commands\InitCommand;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InitCommand::class
            ]);
        }
    }
}
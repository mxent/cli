<?php

namespace Mxent\CLI\Providers;

use Illuminate\Support\ServiceProvider;

class CLIServiceProvider extends ServiceProvider
{

    /**
     * Register
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register Commands
     */
    protected function registerCommands()
    {
        $this->commands([
            \Mxent\CLI\Commands\StartCommand::class,
            \Mxent\CLI\Commands\NpmDiffCommand::class,
        ]);
    }

    /**
     * Register helpers.php
     */
    protected function registerHelpers()
    {
        require_once __DIR__.'/../helpers.php';
    }
}
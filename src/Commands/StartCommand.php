<?php

namespace Mxent\CLI\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StartCommand extends Command
{
    protected $signature = 'mxent:start';
    protected $description = 'Convert this project to a module';

    public function handle()
    {
        $proceed = $this->confirm('This will convert this project to a module. Make sure you do this in a fresh project. Do you want to proceed?');
        if(! $proceed) {
            $this->info('Aborted');
            return;
        }

        $package = $this->ask('What is the name of the package?');
        if(! $package) {
            $this->error('Package name is required');
            return;
        }

        $packageBits = explode('/', $package);
        if(count($packageBits) != 2) {
            $this->error('Invalid package name. Please use the format vendor/package-name');
            return;
        }

        $package = Str::lower($package);
        $vendor = Str::studly($packageBits[0]);
        $vendorSnake = Str::snake($vendor, '-');
        $vendorLower = Str::lower($vendor);
        $name = Str::studly($packageBits[1]);
        $nameSnake = Str::snake($name, '-');
        $nameLower = Str::lower($name);

        $replaces = [
            'VendorName' => $vendor,
            'vendor-name' => $vendorSnake,
            'vendorname' => $vendorLower,
            'ModuleName' => $name,
            'module-name' => $nameSnake,
            'modulename' => $nameLower,
            'App\\' => $vendor.'\\'.$name.'\\',
            'AppServiceProvider' => $name.'ServiceProvider',
        ];

        $renames = [
            // 
        ];

        $empty = [
            'app/Models',
            'app/Providers',
            'config',
            'database/migrations',
            'database/seeders',
            'database/factories',
            'routes',
            'resources/views',
            'resources/js',
            'resources/css',
        ];

        $deletes = [
            'database/database.sqlite',
        ];

        $composerRequires = [
            'inertiajs/inertia-laravel' => null,
            'tightenco/ziggy' => null,
        ];

        $npmDevInstalls = [
            '@inertiajs/vue3' => null,
            '@vitejs/plugin-vue' => null,
        ];

        foreach($renames as $from => $to) {
            rename(base_path($from), base_path($to));
        }

        foreach($empty as $path) {
            $files = scandir(base_path($path));
            foreach($files as $file) {
                if($file == '.' || $file == '..') {
                    continue;
                }

                $fullPath = $path.'/'.$file;

                if(is_file(base_path($fullPath))) {
                    unlink(base_path($fullPath));
                }
            }
        }

        foreach($deletes as $path) {
            if(file_exists(base_path($path))) {
                unlink(base_path($path));
            }
        }

        $this->recursiveStubs(__DIR__.'/../../stubs', base_path(), $replaces);
        $this->recursiveReplace(base_path(), $replaces);

        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        if(! isset($composerJson['extra']['laravel']['providers'])) {
            $composerJson['extra']['laravel']['providers'] = [];
        }
        $composerJson['name'] = $package;
        $composerJson['type'] = 'library';
        $composerJson['extra']['laravel']['providers'][] = $vendor.'\\'.$name.'\\Providers\\'.$name.'ServiceProvider';
        file_put_contents(base_path('composer.json'), json_encode($composerJson, JSON_PRETTY_PRINT));

        $allComposerRequires = [];
        foreach($composerRequires as $packageName => $version) {
            $allComposerRequires[] = $packageName.($version ? ':'.$version : '');
        }
        passthru('composer require '.implode(' ', $allComposerRequires));

        $allNpmDevInstalls = [];
        foreach($npmDevInstalls as $packageName => $version) {
            $allNpmDevInstalls[] = $packageName.($version ? '@'.$version : '');
        }
        passthru('npm install --save-dev '.implode(' ', $allNpmDevInstalls));

        $this->info('Module '.$package.' created');
    }

    private function recursiveStubs($path, $destination, $replaces)
    {
        $files = scandir($path);

        foreach($files as $file) {
            if(
                $file == '.' ||
                $file == '..'
            ) {
                continue;
            }

            $fullPath = $path.'/'.$file;
            $fullDestination = $destination.'/'.str_replace('.stub', '', $file);
            $fullDestination = str_replace(array_keys($replaces), array_values($replaces), $fullDestination);
            
            if(is_dir($fullPath)) {
                if(! is_dir($fullDestination)) {
                    mkdir($fullDestination);
                }

                $this->recursiveStubs($fullPath, $fullDestination, $replaces);
            } else {
                $contents = file_get_contents($fullPath);

                foreach($replaces as $search => $replace) {
                    $contents = str_replace($search, $replace, $contents);
                }
                
                file_put_contents($fullDestination, $contents);
            }
        }
    }

    private function recursiveReplace($path, $replaces)
    {
        $excludes = [
            'vendor',
            'node_modules',
        ];
        
        $gitignores = base_path('.gitignore');
        $gitignores = file_exists($gitignores) ? file($gitignores) : [];

        $files = scandir($path);

        foreach($files as $file) {
            if(
                $file == '.' ||
                $file == '..' ||
                in_array($file, $excludes) ||
                in_array($file, $gitignores) ||
                substr($file, 0, 1) == '.'
            ) {
                continue;
            }

            $fullPath = $path.'/'.$file;

            if(is_dir($fullPath)) {
                $this->recursiveReplace($fullPath, $replaces);
            } else {

                $contents = file_get_contents($fullPath);
                $isJson = in_array(pathinfo($fullPath, PATHINFO_EXTENSION), ['json', 'lock']);

                foreach($replaces as $search => $replace) {
                    if($isJson) {
                        $search = str_replace('\\', '\\\\', $search);
                        $replace = str_replace('\\', '\\\\', $replace);
                    }

                    $contents = str_replace($search, $replace, $contents);
                }

                file_put_contents($fullPath, $contents);
            }
        }
    }

}
<?php

namespace Mxent\CLI\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InitReactCommand extends Command
{
    protected $signature = 'mxent:init-react {--force}';

    protected $description = 'Convert this project into a react module';

    public function handle()
    {

        $force = $this->option('force');
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        if (
            ! $force &&
            (
                $composerJson['name'] != 'laravel/laravel' ||
                $composerJson['type'] != 'project'
            )
        ) {
            $this->error('Please use this command in a fresh Laravel project');

            return;
        }

        $this->components->info('This will convert this project to a module. Make sure you do this in a fresh project.');
        $proceed = $this->confirm('Do you want to proceed?');
        if (! $proceed) {
            $this->components->info('Aborted');

            return;
        }

        $package = $this->ask('What is the name of the package?');
        if (! $package) {
            $this->error('Package name is required');

            return;
        }

        $packageBits = explode('/', $package);
        if (count($packageBits) != 2) {
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
            'inertiajs/inertia-laravel' => '2.x-dev',
        ];

        $npmDevInstalls = [
            '@inertiajs/react' => null,
            '@vitejs/plugin-react' => null,
            'react' => null,
            'react-dom' => null,
            '@types/node' => null,
            '@types/react' => null,
            '@types/react-dom' => null,
            '@commitlint/cli' => null,
            '@commitlint/config-conventional' => null,
            'husky' => null,
            'lint-staged' => null,
            'prettier' => null,
            'eslint' => null,
            'globals' => null,
            '@eslint/js' => null,
            'typescript-eslint' => null,
            'eslint-plugin-react' => null,
        ];

        $npmInstalls = [
            '@radix-ui/react-dropdown-menu' => null,
            '@radix-ui/react-slot' => null,
            'class-variance-authority' => null,
            'clsx' => null,
            'lucide-react' => null,
            'non.geist' => null,
            'tailwind-merge' => null,
            'tailwindcss-animate' => null,
        ];

        $npmUninstalls = [
            'axios',
        ];

        foreach ($renames as $from => $to) {
            rename(base_path($from), base_path($to));
        }

        foreach ($empty as $path) {
            if (! is_dir(base_path($path))) {
                continue;
            }

            $files = scandir(base_path($path));
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $fullPath = $path.'/'.$file;

                if (is_file(base_path($fullPath))) {
                    unlink(base_path($fullPath));
                }
            }
        }

        foreach ($deletes as $path) {
            if (file_exists(base_path($path))) {
                unlink(base_path($path));
            }
        }

        $this->recursiveStubs(__DIR__.'/../../stubs/react', base_path(), $replaces);
        $this->recursiveReplace(base_path(), $replaces);

        if (! isset($composerJson['extra']['laravel']['providers'])) {
            $composerJson['extra']['laravel']['providers'] = [];
        }
        $composerJson['name'] = $package;
        $composerJson['type'] = 'library';
        $composerJson['description'] = 'The skeleton module created using mxent/cli.';
        $composerJson['keywords'] = [$vendorSnake, $nameSnake];
        $composerJson['extra']['laravel']['providers'][] = $vendor.'\\'.$name.'\\Providers\\'.$name.'ServiceProvider';
        $composerJson['autoload']['psr-4'][$vendor.'\\'.$name.'\\'] = 'app/';
        unset($composerJson['autoload']['psr-4']['App\\']);

        $composerJsonClean = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('composer.json'), $composerJsonClean);

        if (! isset($packageJson['workspaces'])) {
            $packageJson['workspaces'] = [];
        }
        if (! in_array('vendor/'.$vendorSnake.'/*', $packageJson['workspaces'])) {
            $packageJson['workspaces'][] = 'vendor/'.$vendorSnake.'/*';
        }

        if (! isset($packageJson['scripts']['test'])) {
            $packageJson['scripts']['test'] = '';
        }

        if (! isset($packageJson['lint-staged'])) {
            $packageJson['lint-staged'] = [];
        }
        $packageJson['lint-staged']['**/*.{ts,js,tsx,jsx}'] = ['prettier --write', 'eslint --fix'];

        $packageJsonClean = json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(base_path('package.json'), $packageJsonClean);

        $passThru = [];

        $allComposerRequires = [];
        foreach ($composerRequires as $packageName => $version) {
            $allComposerRequires[] = $packageName.($version ? ':'.$version : '');
        }

        $allNpmUninstalls = [];
        foreach ($npmUninstalls as $packageName) {
            $allNpmUninstalls[] = $packageName;
        }
        $allNpmDevInstalls = [];
        foreach ($npmDevInstalls as $packageName => $version) {
            $allNpmDevInstalls[] = $packageName.($version ? '@'.$version : '');
        }
        $allNpmInstalls = [];
        foreach ($npmInstalls as $packageName => $version) {
            $allNpmInstalls[] = $packageName.($version ? '@'.$version : '');
        }

        $passThru[] = 'composer require '.implode(' ', $allComposerRequires);
        $passThru[] = 'npm uninstall '.implode(' ', $allNpmUninstalls);
        $passThru[] = 'npm install --save-dev '.implode(' ', $allNpmDevInstalls);
        $passThru[] = 'echo "export default { extends: [\'@commitlint/config-conventional\'] };" > commitlint.config.js';
        $passThru[] = 'git init';
        $passThru[] = 'npx husky init';
        $passThru[] = 'echo "npx --no -- commitlint --edit \$1" > .husky/commit-msg';
        passthru(implode(' && ', $passThru));

        $huskyPreCommit = file_get_contents(base_path('.husky/pre-commit'));
        $huskyPreCommit = 'vendor/bin/pint'.PHP_EOL.'npx lint-staged'.PHP_EOL.$huskyPreCommit;
        file_put_contents(base_path('.husky/pre-commit'), $huskyPreCommit);

        passthru('git add .');

        $this->components->info('Module '.$package.' created');

    }

    private function recursiveStubs($path, $destination, $replaces)
    {
        $files = scandir($path);

        foreach ($files as $file) {
            if (
                $file == '.' ||
                $file == '..'
            ) {
                continue;
            }

            $fullPath = $path.'/'.$file;
            $fullDestination = $destination.'/'.str_replace('.stub', '', $file);
            $fullDestination = str_replace(array_keys($replaces), array_values($replaces), $fullDestination);

            if (is_dir($fullPath)) {
                if (! is_dir($fullDestination)) {
                    mkdir($fullDestination);
                }

                $this->recursiveStubs($fullPath, $fullDestination, $replaces);
            } else {
                $contents = file_get_contents($fullPath);

                foreach ($replaces as $search => $replace) {
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

        foreach ($files as $file) {
            if (
                $file == '.' ||
                $file == '..' ||
                in_array($file, $excludes) ||
                in_array($file, $gitignores) ||
                substr($file, 0, 1) == '.'
            ) {
                continue;
            }

            $fullPath = $path.'/'.$file;

            if (is_dir($fullPath)) {
                $this->recursiveReplace($fullPath, $replaces);
            } else {

                $contents = file_get_contents($fullPath);
                $isJson = in_array(pathinfo($fullPath, PATHINFO_EXTENSION), ['json', 'lock']);

                foreach ($replaces as $search => $replace) {
                    if ($isJson) {
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
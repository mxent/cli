<?php

namespace Mxent\CLI\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class NpmDiffCommand extends Command
{
    protected $signature = 'mxent:npm-diff';
    protected $description = 'Check npm dependencies for differences';

    public function handle()
    {

        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $allRequires = array_merge($composerJson['require'], $composerJson['require-dev']);
        $dependenciesMissing = [];
        $devDependenciesMissing = [];
        foreach ($allRequires as $package => $version) {
            $packageBits = explode('/', $package);
            $projectNameBits = explode('/', $composerJson['name']);
            if($packageBits[0] == $projectNameBits[1]) {
                $vendorPackageFile = base_path('vendor/'.$package.'/package.json');
                if(file_exists($vendorPackageFile)) {
                    $vendorPackageJson = json_decode(file_get_contents($vendorPackageFile), true);
                    $packageJsonDifferences = array_diff_key($packageJson['dependencies'], $vendorPackageJson['dependencies']);
                    $devPackageJsonDifferences = array_diff_key($packageJson['devDependencies'], $vendorPackageJson['devDependencies']);
                    if(count($packageJsonDifferences) > 0) {
                        $dependenciesMissing[$package] = array_keys($packageJsonDifferences);
                    }
                    if(count($devPackageJsonDifferences) > 0) {
                        $devDependenciesMissing[$package] = array_keys($devPackageJsonDifferences);
                    }
                }
            }
        }

        if(count($dependenciesMissing) > 0) {
            $this->info('Found the following packages that have dependencies missing in package.json:');
            foreach ($dependenciesMissing as $package => $missing) {
                $this->info($package.': '.implode(' ', $missing));
            }
        }

        if(count($devDependenciesMissing) > 0) {
            $this->info('Found the following packages that have devDependencies missing in package.json:');
            foreach ($devDependenciesMissing as $package => $missing) {
                $this->info($package.': '.implode(' ', $missing));
            }
        }
    }
}
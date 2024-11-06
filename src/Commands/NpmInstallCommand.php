<?php

namespace Mxent\CLI\Commands;

use Illuminate\Console\Command;

class NpmInstallCommand extends Command
{
    protected $signature = 'mxent:npmi';
    protected $description = 'Install npm missing dependencies';

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
            if($packageBits[0] == $projectNameBits[0]) {
                $vendorPackageFile = base_path('vendor/'.$package.'/package.json');
                if(file_exists($vendorPackageFile)) {
                    $vendorPackageJson = json_decode(file_get_contents($vendorPackageFile), true);
                    $packageJsonDifferences = isset($packageJson['dependencies']) && isset($vendorPackageJson['dependencies']) ? array_diff_key($packageJson['dependencies'], $vendorPackageJson['dependencies']) : (isset($vendorPackageJson['dependencies']) ? $vendorPackageJson['dependencies'] : []);
                    $devPackageJsonDifferences = isset($packageJson['devDependencies']) && isset($vendorPackageJson['devDependencies']) ? array_diff_key($packageJson['devDependencies'], $vendorPackageJson['devDependencies']) : (isset($vendorPackageJson['devDependencies']) ? $vendorPackageJson['devDependencies'] : []);
                    if(count($packageJsonDifferences) > 0) {
                        $dependenciesMissing[$package] = array_map(function($version, $package) {
                            return $package.'@'.$version;
                        }, $packageJsonDifferences, array_keys($packageJsonDifferences));
                    }
                    if(count($devPackageJsonDifferences) > 0) {
                        $devDependenciesMissing[$package] = array_map(function($version, $package) {
                            return $package.'@'.$version;
                        }, $devPackageJsonDifferences, array_keys($devPackageJsonDifferences));
                    }
                }
            }
        }

        if(count($dependenciesMissing) > 0) {
            $dependenciesInstallCommand = 'npm install '.implode(' ', array_map(function($package, $missing) {
                return implode(' ', $missing);
            }, array_keys($dependenciesMissing), $dependenciesMissing));
            $this->info('Running: '.$dependenciesInstallCommand);
            passthru($dependenciesInstallCommand);
        }

        if(count($devDependenciesMissing) > 0) {
            $devDependenciesInstallCommand = 'npm install '.implode(' ', array_map(function($package, $missing) {
                return implode(' ', $missing);
            }, array_keys($devDependenciesMissing), $devDependenciesMissing));
            $this->info('Running: '.$devDependenciesInstallCommand);
            passthru($devDependenciesInstallCommand);
        }

        if(count($dependenciesMissing) == 0 && count($devDependenciesMissing) == 0) {
            $this->components->info('No missing dependencies found');
        } else {
            $this->components->info('Missing dependencies installed');
        }

    }
}
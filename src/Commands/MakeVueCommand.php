<?php

namespace Mxent\CLI\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeVueCommand extends Command
{

    protected $signature = 'make:vue {name=ComponentName}';
    protected $description = 'Create a new Vue component';

    public function handle()
    {

        $name = $this->argument('name');
        if($name == 'ComponentName') {
            $name = $this->ask('What is the name of the component?');
        }
        if(! $name) {
            $this->error('Component name is required');
            return;
        }

        $nameBits = explode('/', $name);
        $originalName = Str::studly($nameBits[count($nameBits) - 1]);
        $vueStub = file_get_contents(__DIR__.'/../../stubs/resources/js/Pages/ModuleName/Index.vue.stub');
        $replaces = [
            'ModuleName' => $originalName,
        ];
        $vueStub = str_replace(array_keys($replaces), array_values($replaces), $vueStub);
        $path = base_path('resources/js');
        if(count($nameBits) > 1) {
            $path .= '/'.implode('/', array_slice($nameBits, 0, count($nameBits) - 1));
        }
        $path .= '/'.$originalName.'.vue';
        file_put_contents($path, $vueStub);

        $this->components->info('Vue component created successfully');

    }

}
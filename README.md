# Mxent CLI

There's so much more we can do with Laravel!

Our CLI provides additional artisan commands that might be useful for your Laravel development.

## Installation

    composer require mxent/cli

## Modular

Our opinionated modular setup for Laravel.

While there are awesome laravel packages out there to make your laravel project modular, we believe that none of them solves our problem of reusable modules.

In our case, we are maintaining multiple laravel projects with the same login and user management modules and we don't want to keep updating those modules each project, instead we make it a composer package and here we go...

### Usage

    php artisan mxent:init

Once done, you may now publish it to your preferred repo and require it to each project that needs it.
<?php

namespace TripleInternetSolutions\FakerPhpImageProvider;

use Faker\Generator;
use Illuminate\Support\ServiceProvider;

class FakerPhpImageProviderServiceProvider extends ServiceProvider
{
    public function register() {
        $this->app->afterResolving(function ($obj, $app) {
            if (!($obj instanceof Generator)) return;

            $obj->addProvider(new Image($obj));
        });
    }
}
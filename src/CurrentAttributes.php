<?php

namespace SilvertipSoftware\LaravelSupport;

use Illuminate\Contracts\Foundation\Application;

class CurrentAttributes extends FluentModel {

    protected $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }
}

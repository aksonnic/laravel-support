<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Illuminate\Support\Facades\Redirect;

class RedirectHelpers {

    public static function register() {
        Redirect::macro('url', function (...$models) {
            return $this->to(URL::url(...$models));
        });

        Redirect::macro('path', function (...$models) {
            return $this->to(URL::path(...$models));
        });
    }
}

<?php

namespace SilvertipSoftware\LaravelSupport\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SilvertipSoftware\LaravelSupport\Http\Middleware\DetectDesiredResponseFormat;
use SilvertipSoftware\LaravelSupport\Http\Middleware\SealInFreshness;
use SilvertipSoftware\LaravelSupport\Http\Mixins\RequestAcceptsHelpers;
use SilvertipSoftware\LaravelSupport\Http\Mixins\RequestFreshnessHelpers;
use SilvertipSoftware\LaravelSupport\Routing\UrlHelpers;

class LaravelSupportProvider extends ServiceProvider {

    public function boot() {
        RequestAcceptsHelpers::register();
        RequestFreshnessHelpers::register();
        UrlHelpers::register();

        Route::aliasMiddleware('formats', DetectDesiredResponseFormat::class);
        Route::aliasMiddleware('freshness', SealInFreshness::class);
    }
}

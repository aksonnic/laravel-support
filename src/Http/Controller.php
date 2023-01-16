<?php

namespace SilvertipSoftware\LaravelSupport\Http;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use Concerns\AutoResponds,
        Concerns\ConditionalGet,
        Concerns\EasierMiddleware,
        Concerns\Resourceful,
        Concerns\Routing,
        Concerns\StrongParameters;
}

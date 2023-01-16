<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

// Not needed for Laravel above 5.6
trait HasTraits {

    protected static $traitInitializers = [];

    protected static function bootTraits() {
        $class = static::class;

        $booted = [];
        $traitInitializers = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($class, $method) && !in_array($method, $booted)) {
                forward_static_call([$class, $method]);

                $booted[] = $method;
            }

            if (method_exists($class, $method = 'initialize' . class_basename($trait))) {
                $traitInitializers[] = $method;
            }
        }

        static::$traitInitializers[$class] = array_unique($traitInitializers);
    }

    protected function initializeTraits() {
        foreach (static::$traitInitializers[static::class] as $method) {
            $this->{$method}();
        }
    }
}

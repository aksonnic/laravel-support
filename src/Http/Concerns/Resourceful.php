<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

trait Resourceful {

    protected $modelRootNamespace = 'App\\Models';

    protected function url(...$args) {
        return URL::url(...$args);
    }

    protected function path(...$args) {
        return URL::path(...$args);
    }

    protected function redirect(...$args) {
        return redirect($this->url(...$args), 303);
    }

    protected function getSubjectResourceTag() {
        return isset($this->resourceTag)
            ? $this->resourceTag
            : Str::singular(Str::snake(Str::replaceLast('Controller', '', class_basename($this))));
    }

    protected function getSubjectResourceClass() {
        return isset($this->resourceClass)
            ? $this->resourceClass
            : $this->getActualClassNameFromTag($this->getSubjectResourceTag());
    }

    protected function getActualClassNameFromTag($tag) {
        $clz = $this->modelRootNamespace . '\\' . Str::studly($tag);

        if (class_exists($clz)) {
            return $clz;
        }

        return Model::getActualClassNameForMorph($tag);
    }
}

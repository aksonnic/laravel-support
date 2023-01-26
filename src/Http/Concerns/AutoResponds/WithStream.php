<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Facades\View;

trait WithStream {

    protected function createStreamResponse($status = 200) {
        View::share(get_object_vars($this));
        $content = view($this->viewNameForRoute())->render();

        return $this->makeStreamResponseFrom(response($content, $status));
    }

    protected function makeStreamResponseFrom($response) {
        return $response
            ->header('Content-Type', 'text/vnd.turbo-stream.html');
    }

    protected function mapRedirectForStream($response) {
        return $this->mapRedirectForJs($response);
    }
}

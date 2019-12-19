<?php

namespace App\Providers;

use App\ExchangeRequest;
use mmghv\LumenRouteBinding\RouteBindingServiceProvider;

class RouteServiceProvider extends RouteBindingServiceProvider{

    public function boot()
    {
        $this->binder->bind('exchange_request', ExchangeRequest::class);
    }

}
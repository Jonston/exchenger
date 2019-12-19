<?php

namespace App\Events;

use App\ExchangeRequest;

class ExchangeEvent extends Event
{
    public $request;

    /**
     * ExchangeEvent constructor.
     * @param ExchangeRequest $request
     */
    public function __construct(ExchangeRequest $request)
    {
        $this->request = $request;
    }
}

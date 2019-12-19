<?php

namespace App\Listeners;

use App\Events\ExchangeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ExchangeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ExchangeEvent  $event
     * @return void
     */
    public function handle(ExchangeEvent $event)
    {

    }
}

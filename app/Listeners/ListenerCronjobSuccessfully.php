<?php

namespace App\Listeners;

class ListenerCronjobSuccessfully
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $cronjob = $event->cronjob;
        logger('Success listener with cronjob ' . $cronjob->id);
    }
}

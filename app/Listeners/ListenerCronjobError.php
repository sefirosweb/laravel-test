<?php

namespace App\Listeners;

class ListenerCronjobError
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
        $error = $event->error;
        logger('Success listener errorcronjob ' . $cronjob->id . ' ' . $error);
    }
}

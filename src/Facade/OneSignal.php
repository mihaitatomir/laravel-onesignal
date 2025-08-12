<?php

namespace Webident\OneSignal\Facade;

use Illuminate\Support\Facades\Facade;

class OneSignal extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Webident\OneSignal\OneSignal::class;
    }
}

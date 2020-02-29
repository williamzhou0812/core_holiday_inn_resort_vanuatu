<?php

namespace JBG\TheCore\Facades;

use Illuminate\Support\Facades\Facade;

class TheCore extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'thecore';
    }
}

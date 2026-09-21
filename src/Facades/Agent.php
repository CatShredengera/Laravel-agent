<?php

namespace CatShredengera\Agent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CatShredengera\Agent\Agent
 */
class Agent extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'agent';
    }
}

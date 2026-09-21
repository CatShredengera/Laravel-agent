<?php

namespace CatShredengera\Agent\Tests;

use CatShredengera\Agent\AgentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            AgentServiceProvider::class,
        ];
    }
}

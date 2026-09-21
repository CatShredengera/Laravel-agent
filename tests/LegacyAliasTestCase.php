<?php

namespace CatShredengera\Agent\Tests;

class LegacyAliasTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Orchestra Testbench calls this before service providers boot, so
        // by the time AgentServiceProvider::boot() runs, config('agent.alias_legacy_namespace')
        // already reflects this — exercising the same path a real app gets
        // from config/agent.php or the AGENT_ALIAS_LEGACY_NAMESPACE env var.
        $app['config']->set('agent.alias_legacy_namespace', true);
    }
}

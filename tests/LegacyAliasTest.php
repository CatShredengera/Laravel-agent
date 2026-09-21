<?php

namespace CatShredengera\Agent\Tests;

use CatShredengera\Agent\Agent;
use CatShredengera\Agent\Facades\Agent as AgentFacade;

/**
 * A classic PHPUnit-style test class, not a Pest `test()`/`it()` file: it
 * needs `agent.alias_legacy_namespace` set before the app boots, which means
 * a different TestCase than the one tests/Pest.php applies to the rest of
 * this directory. Extending LegacyAliasTestCase directly sidesteps Pest's
 * directory-wide `uses()` binding instead of fighting it.
 */
class LegacyAliasTest extends LegacyAliasTestCase
{
    public function test_legacy_jenssegers_agent_class_resolves_to_this_packages_agent(): void
    {
        $this->assertTrue(class_exists(\Jenssegers\Agent\Agent::class));
        $this->assertTrue(is_a(\Jenssegers\Agent\Agent::class, Agent::class, true));

        $legacyAgent = new \Jenssegers\Agent\Agent;
        $legacyAgent->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');

        $this->assertTrue($legacyAgent->isDesktop());
        $this->assertSame('Chrome', $legacyAgent->browser());
    }

    public function test_legacy_jenssegers_agent_facade_resolves_to_this_packages_facade(): void
    {
        $this->assertTrue(class_exists(\Jenssegers\Agent\Facades\Agent::class));
        $this->assertTrue(is_a(\Jenssegers\Agent\Facades\Agent::class, AgentFacade::class, true));
    }
}

<?php

use CatShredengera\Agent\Agent;
use CatShredengera\Agent\Facades\Agent as AgentFacade;

test('the agent facade resolves to a bound Agent singleton', function () {
    $this->app['request']->server->set('HTTP_USER_AGENT', 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25');

    expect(app('agent'))->toBeInstanceOf(Agent::class);
    expect(AgentFacade::isMobile())->toBeTrue();
    expect(AgentFacade::isPhone())->toBeTrue();
    expect(AgentFacade::browser())->toBe('Safari');
});

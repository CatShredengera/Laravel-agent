<?php

use CatShredengera\Agent\Agent;
use CatShredengera\Agent\Facades\Agent as AgentFacade;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;

const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25';
const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36';

beforeEach(function () {
    Route::get('/agent-probe', fn () => response()->json([
        'isMobile' => AgentFacade::isMobile(),
        'isDesktop' => AgentFacade::isDesktop(),
        'browser' => AgentFacade::browser(),
    ]));
});

test('the agent facade resolves to a bound Agent singleton', function () {
    $this->get('/agent-probe', ['User-Agent' => IPHONE_UA])
        ->assertJson(['isMobile' => true, 'isDesktop' => false, 'browser' => 'Safari']);

    expect(app('agent'))->toBeInstanceOf(Agent::class);
});

test('a real request parses the User-Agent header, not a manually-set one', function () {
    // This is the promise the ported AgentTest.php suite can't check on its
    // own: that suite drives Agent::setUserAgent() directly, so it verifies
    // the *parser* without ever going through a real request/response
    // cycle. This confirms the facade -> service provider -> $request wiring
    // actually reads the header a real client sends.
    $this->get('/agent-probe', ['User-Agent' => DESKTOP_UA])
        ->assertJson(['isMobile' => false, 'isDesktop' => true, 'browser' => 'Chrome']);
});

test('the agent binding does not go stale across requests under Octane', function () {
    // `scoped()` bindings behave exactly like `singleton()` within a single
    // request, and the container instance persists across these two
    // `$this->get()` calls the same way it would persist across two
    // requests handled by one Octane worker. `forgetScopedInstances()` is
    // what Octane's Flush listener calls between requests to clear scoped
    // container bindings — calling it here reproduces that half of the
    // reset without needing an actual Octane process.
    //
    // Octane's Flush listener also calls Facade::clearResolvedInstances()
    // every request, for every facade project-wide — that's Octane's job,
    // not this package's, so it's simulated here too rather than worked
    // around in AgentServiceProvider. Without *both* resets this test still
    // fails, because Facade::resolveFacadeInstance() has its own static
    // cache on top of the container: the exact bug that would exist if
    // `agent` were bound `singleton()` (as jenssegers/agent's own provider
    // did) is duplicated one layer up by the facade unless Octane clears it.
    $this->get('/agent-probe', ['User-Agent' => IPHONE_UA])
        ->assertJson(['isMobile' => true, 'browser' => 'Safari']);

    $this->app->forgetScopedInstances();
    Facade::clearResolvedInstances();

    $this->get('/agent-probe', ['User-Agent' => DESKTOP_UA])
        ->assertJson(['isMobile' => false, 'browser' => 'Chrome']);
});

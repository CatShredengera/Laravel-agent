<?php

namespace CatShredengera\Agent;

use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * Bound `scoped()`, not `singleton()`: under Laravel Octane and other
     * long-running workers a singleton is built once from the *first*
     * request's headers and then served, stale, to every request after
     * that. `scoped()` instances are flushed by Octane between requests
     * (see `Container::forgetScopedInstances()`), so each request gets an
     * `Agent` built from its own headers. On classic PHP-FPM, where the
     * container is thrown away after every request anyway, this behaves
     * identically to a singleton — no cost either way.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agent.php', 'agent');

        $this->app->scoped('agent', function ($app) {
            return new Agent($app['request']->server());
        });

        $this->app->alias('agent', Agent::class);
    }

    /**
     * Bootstrap the service provider.
     *
     * Deliberately not deferred: the legacy-namespace alias below has to be
     * registered before any application code runs, including code that
     * `new`s up `Jenssegers\Agent\Agent` directly and never touches the
     * container — which a deferred provider would never see resolved.
     */
    public function boot(): void
    {
        if ($this->app['config']->get('agent.alias_legacy_namespace')) {
            $this->aliasLegacyNamespace();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/agent.php' => $this->app->configPath('agent.php'),
            ], 'agent-config');
        }
    }

    /**
     * Make the old `Jenssegers\Agent\*` class names resolve to this
     * package's classes, so code that imports them directly (rather than
     * going through the container/facade) keeps working unmodified.
     *
     * Uses autoloading `class_exists()` checks so this is a no-op — rather
     * than a fatal "cannot redeclare class" — if jenssegers/agent is still
     * actually installed alongside this package during a transition.
     */
    protected function aliasLegacyNamespace(): void
    {
        // Plain string literals, not ::class: jenssegers/agent isn't a
        // dependency, so these classes are never expected to exist, and
        // ::class on them would make static analysis go looking for them.
        $legacyAgent = 'Jenssegers\Agent\Agent';
        $legacyFacade = 'Jenssegers\Agent\Facades\Agent';

        if (! class_exists($legacyAgent)) {
            class_alias(Agent::class, $legacyAgent);
        }

        if (! class_exists($legacyFacade)) {
            class_alias(Facades\Agent::class, $legacyFacade);
        }
    }
}

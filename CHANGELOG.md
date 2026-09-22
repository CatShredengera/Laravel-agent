# Changelog

All notable changes to `laravel-agent` will be documented in this file.

## v1.0.0 — drop-in successor to jenssegers/agent - 2026-09-22

### What this is

`catshredengera/laravel-agent` is a drop-in replacement for `jenssegers/agent`,
which has been unmaintained since v2.6.4 (June 2020) and is pinned to a
Mobile-Detect release that no longer installs cleanly on current PHP. This
package keeps the exact same `Agent` class, facade, and method names —
migrating is a Composer swap, not a code change.

### Migrating

    composer remove jenssegers/agent
    composer require catshredengera/laravel-agent
    
Anything going through `Agent::isMobile()`, `Agent::browser()`, the facade, or
the `is*()` magic methods keeps working unchanged. If your codebase imports the
concrete `Jenssegers\Agent\Agent` class directly, opt into the legacy-namespace
alias so that import resolves too:

    // config/agent.php
    'alias_legacy_namespace' => true, // or AGENT_ALIAS_LEGACY_NAMESPACE=true
    
### Compatibility

- **PHP** 8.3+
- **Laravel** 11, 12, 13
- **Mobile-Detect** v2.x **and** v4.x — the right code path is selected at
  runtime by a small driver layer, so the v2 → v4 breaking changes never reach
  your code
- Robot detection via `jaybizzle/crawler-detect`

### Tested

- The original `jenssegers/agent` test suite — its golden set of real-world
  user-agent strings — is ported and passes in full.
- **Both ends of the matrix are covered**: the suite runs green against the
  lowest supported Mobile-Detect (2.8.34) and the current 4.x line, so the dual
  compatibility above is verified, not assumed.
- **Octane-safe**: the container binding is request-scoped, so the parsed agent
  never goes stale across requests.
- PHPStan clean.

### Credits

Built on the work of [`jenssegers/agent`](https://github.com/jenssegers/agent),
[Mobile-Detect](https://github.com/serbanghita/Mobile-Detect), and
[Crawler-Detect](https://github.com/JayBizzle/Crawler-Detect). MIT licensed.

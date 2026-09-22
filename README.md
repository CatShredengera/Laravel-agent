# Laravel Agent

[![Latest Version on Packagist](https://img.shields.io/packagist/v/catshredengera/laravel-agent.svg?style=flat-square)](https://packagist.org/packages/catshredengera/laravel-agent)
[![GitHub Tests Action Status](https://github.com/catshredengera/laravel-agent/actions/workflows/run-tests.yml/badge.svg)](https://github.com/catshredengera/laravel-agent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/catshredengera/laravel-agent.svg?style=flat-square)](https://packagist.org/packages/catshredengera/laravel-agent)

Drop-in replacement for [`jenssegers/agent`](https://github.com/jenssegers/agent), updated for Laravel 12/13 and PHP 8.3.

`jenssegers/agent` isn't archived, just unmaintained: its last release was v2.6.4 in June 2020, pinned to `mobiledetect/mobiledetectlib:^2.7.6` — a constraint composer can no longer resolve to anything with current PHP-compatibility patches. This package keeps the exact same `Agent` class, facade and method names — only the Composer package name changes, so migrating is a `composer require`/`composer remove` swap, not a rewrite.

Under the hood it supports **both** major lines of `mobiledetect/mobiledetectlib`: the actively-developed v4.x (`Detection\MobileDetect`, a reworked API), and the still-patched v2.x (the global `Mobile_Detect` class jenssegers/agent itself depended on). Agent detects whichever one composer actually resolved and adapts to it, so it works whether your project pulls in the latest Mobile-Detect or something else in your dependency tree pins the legacy line.

## Migrating from jenssegers/agent

```bash
composer remove jenssegers/agent
composer require catshredengera/laravel-agent
```

That's it for anything going through `Agent::isMobile()`, `Agent::browser()`, the facade, or the `is*()` magic methods — no code changes.

If something in your codebase still does `use Jenssegers\Agent\Agent;` directly (bypassing the facade, so Laravel's container never gets a chance to substitute anything), that import will fatal on its own — no package provides that class name anymore. Either fix the import, or opt into a compatibility shim while you track down the remaining ones:

```bash
php artisan vendor:publish --tag=agent-config
```

```php
// config/agent.php
'alias_legacy_namespace' => true, // or AGENT_ALIAS_LEGACY_NAMESPACE=true
```

With that on, `Jenssegers\Agent\Agent` and `Jenssegers\Agent\Facades\Agent` resolve to this package's classes via `class_alias()`. Turn it off once you've swept the old import out.

## Installation

You can install the package via composer:

```bash
composer require catshredengera/laravel-agent
```

The service provider and `Agent` facade are auto-discovered — nothing to register manually.

`composer.json` requires `mobiledetect/mobiledetectlib:^2.8.34||^4.0`, so whichever one composer resolves — the latest v4.x by default, or v2.x if something else in your project pins it — just works.

## Usage

```php
use CatShredengera\Agent\Facades\Agent;

Agent::isMobile();
Agent::isTablet();
Agent::isDesktop();
Agent::isPhone();

Agent::isRobot();
Agent::robot(); // Googlebot, Bingbot, ...

Agent::device();    // iPhone, Nexus One, Macintosh, ...
Agent::platform();  // Windows, AndroidOS, OS X, ...
Agent::browser();   // Chrome, Safari, IE, ...
Agent::version('Chrome'); // 79.0.3945.29

Agent::languages(); // ['en-us', 'en']

// Any device / platform / browser name also works as a magic isX() method:
Agent::isChrome();
Agent::isWindows();
Agent::isiPhone();
```

Safe under Octane: the `agent` binding is registered `scoped()`, not `singleton()`, so each request gets an `Agent` built from its own headers instead of every request after the first reusing whichever User-Agent the worker saw first.

You can also instantiate `Agent` directly, outside of a Laravel request:

```php
use CatShredengera\Agent\Agent;

$agent = new Agent();
$agent->setUserAgent($userAgentString);

$agent->isMobile();
```

## Testing

```bash
composer test
```

The test suite is ported from `jenssegers/agent`'s own tests: the same set of real-world user-agent strings the original package was validated against.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Issues and pull requests are welcome.

## Credits

- [jenssegers/agent](https://github.com/jenssegers/agent) — the original package this one keeps alive
- [Mobile-Detect](https://github.com/serbanghita/Mobile-Detect)
- [Crawler-Detect](https://github.com/JayBizzle/Crawler-Detect)
- [CatShredengera](https://github.com/CatShredengera)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

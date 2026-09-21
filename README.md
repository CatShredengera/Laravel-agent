# Laravel Agent

[![Latest Version on Packagist](https://img.shields.io/packagist/v/catshredengera/laravel-agent.svg?style=flat-square)](https://packagist.org/packages/catshredengera/laravel-agent)
[![GitHub Tests Action Status](https://github.com/catshredengera/laravel-agent/actions/workflows/run-tests.yml/badge.svg)](https://github.com/catshredengera/laravel-agent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/catshredengera/laravel-agent.svg?style=flat-square)](https://packagist.org/packages/catshredengera/laravel-agent)

Drop-in replacement for the abandoned [`jenssegers/agent`](https://github.com/jenssegers/agent), updated for Laravel 12/13 and PHP 8.3.

`jenssegers/agent` hasn't seen a release since it was archived, and it's pinned to `mobiledetect/mobiledetectlib` v2, which doesn't install on current PHP. This package keeps the exact same `Agent` class, facade and method names — only the Composer package name changes, so migrating is a `composer require`/`composer remove` swap, not a rewrite. Under the hood it runs on [Mobile-Detect v4](https://github.com/serbanghita/Mobile-Detect) and [`jaybizzle/crawler-detect`](https://github.com/JayBizzle/Crawler-Detect), adapted so the v2 → v4 breaking changes never reach your code.

## Migrating from jenssegers/agent

```bash
composer remove jenssegers/agent
composer require catshredengera/laravel-agent
```

That's it — no code changes. `Agent::isMobile()`, `Agent::browser()`, the `is*()` magic methods, all of it works exactly as before.

## Installation

You can install the package via composer:

```bash
composer require catshredengera/laravel-agent
```

The service provider and `Agent` facade are auto-discovered — nothing to register manually.

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

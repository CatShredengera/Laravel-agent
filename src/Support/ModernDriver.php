<?php

namespace CatShredengera\Agent\Support;

/**
 * Wraps mobiledetect/mobiledetectlib v4.x's `Detection\MobileDetect`, which
 * reworked the API jenssegers/agent was built against: the constructor
 * takes a PSR-16 cache instead of headers, `isMobile()`/`isTablet()` lost
 * their `$userAgent`/`$httpHeaders` overrides, and `match()`'s `$userAgent`
 * became required instead of optional. This driver absorbs those
 * differences so Agent doesn't have to know about them.
 *
 * The underlying class name is only ever referenced as a string, never a
 * `use` import or type-hint, so this class stays loadable — just never
 * instantiated — in an environment where mobiledetect/mobiledetectlib v2
 * is what's actually installed instead (see LegacyDriver).
 */
class ModernDriver implements Driver
{
    private const DETECTOR_CLASS = 'Detection\MobileDetect';

    private object $detector;

    public function __construct(?array $httpHeaders = null, ?string $userAgent = null)
    {
        $class = self::DETECTOR_CLASS;

        $this->detector = new $class(config: ['autoInitOfHttpHeaders' => false]);

        if ($httpHeaders !== null) {
            // Callers (e.g. our service provider) pass the full, unfiltered
            // $request->server() / $_SERVER array, which under CLI/testing
            // contexts can contain non-scalar entries such as 'argv'. Those
            // aren't real HTTP headers, and Mobile-Detect's cache-key
            // builder blows up (`Array to string conversion`) if it sees one.
            $this->detector->setHttpHeaders(array_filter($httpHeaders, 'is_scalar'));
        } else {
            $this->detector->autoInitKnownHttpHeaders();
        }

        if ($userAgent !== null) {
            $this->detector->setUserAgent($userAgent);
        }

        if (! $this->detector->hasUserAgent()) {
            $this->detector->setUserAgent('');
        }
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->detector->setUserAgent($userAgent ?? '');
    }

    public function getUserAgent(): ?string
    {
        return $this->detector->getUserAgent();
    }

    public function setHttpHeaders(array $httpHeaders): void
    {
        $this->detector->setHttpHeaders(array_filter($httpHeaders, 'is_scalar'));
    }

    public function getHttpHeader(string $header): ?string
    {
        return $this->detector->getHttpHeader($header);
    }

    public function match(string $regex, ?string $userAgent = null): bool
    {
        return $this->detector->match($regex, $userAgent ?? ($this->getUserAgent() ?? ''));
    }

    public function getMatches(): array
    {
        return $this->detector->getMatchesArray() ?? [];
    }

    public function isMobile(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        if ($httpHeaders !== null) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent !== null) {
            $this->setUserAgent($userAgent);
        }

        return $this->detector->hasUserAgent() && $this->detector->isMobile();
    }

    public function isTablet(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        if ($httpHeaders !== null) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent !== null) {
            $this->setUserAgent($userAgent);
        }

        return $this->detector->hasUserAgent() && $this->detector->isTablet();
    }

    public static function phoneDevices(): array
    {
        $class = self::DETECTOR_CLASS;

        return $class::getPhoneDevices();
    }

    public static function tabletDevices(): array
    {
        $class = self::DETECTOR_CLASS;

        return $class::getTabletDevices();
    }

    public static function operatingSystems(): array
    {
        $class = self::DETECTOR_CLASS;

        return $class::getOperatingSystems();
    }

    public static function browsers(): array
    {
        $class = self::DETECTOR_CLASS;

        return $class::getBrowsers();
    }

    public static function properties(): array
    {
        $class = self::DETECTOR_CLASS;

        return $class::getProperties();
    }
}

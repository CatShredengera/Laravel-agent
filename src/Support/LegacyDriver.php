<?php

namespace CatShredengera\Agent\Support;

/**
 * Wraps mobiledetect/mobiledetectlib v2.x's global `Mobile_Detect` class —
 * the version jenssegers/agent itself originally depended on, and which
 * still receives compatibility patches today. Its public API already
 * matches what Agent needs almost method-for-method (optional `$userAgent`/
 * `$httpHeaders` args on `isMobile()`/`isTablet()`, a nullable `$userAgent`
 * on `match()`, public static rule getters), so this driver is mostly a
 * thin pass-through.
 *
 * The underlying class name is only ever referenced as a string, never a
 * `use` import or type-hint: `Mobile_Detect` doesn't exist at all when
 * mobiledetect/mobiledetectlib v4 is the one actually installed (see
 * ModernDriver), and this class must still be loadable — just never
 * instantiated — in that case.
 */
class LegacyDriver implements Driver
{
    private const DETECTOR_CLASS = 'Mobile_Detect';

    private object $detector;

    public function __construct(?array $httpHeaders = null, ?string $userAgent = null)
    {
        $class = self::DETECTOR_CLASS;

        $this->detector = new $class($httpHeaders, $userAgent);
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->detector->setUserAgent($userAgent);
    }

    public function getUserAgent(): ?string
    {
        return $this->detector->getUserAgent();
    }

    public function setHttpHeaders(array $httpHeaders): void
    {
        $this->detector->setHttpHeaders($httpHeaders);
    }

    public function getHttpHeader(string $header): ?string
    {
        return $this->detector->getHttpHeader($header);
    }

    public function match(string $regex, ?string $userAgent = null): bool
    {
        return $this->detector->match($regex, $userAgent);
    }

    public function getMatches(): array
    {
        return $this->detector->getMatchesArray() ?? [];
    }

    public function isMobile(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        return $this->detector->isMobile($userAgent, $httpHeaders);
    }

    public function isTablet(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        return $this->detector->isTablet($userAgent, $httpHeaders);
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

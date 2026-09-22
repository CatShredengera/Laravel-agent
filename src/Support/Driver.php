<?php

namespace CatShredengera\Agent\Support;

/**
 * The slice of mobiledetect/mobiledetectlib's public API that Agent needs,
 * unified across the library's v2.x line (the global `Mobile_Detect` class
 * jenssegers/agent originally depended on, still patched today) and its
 * v4.x line (`Detection\MobileDetect`, a reworked API) — so Agent itself
 * never has to know which one is actually installed.
 *
 * The rule-dictionary getters (phoneDevices, tabletDevices,
 * operatingSystems, browsers, properties) are declared `static` here too —
 * Agent calls them via `DriverFactory::driverClass()::phoneDevices()`
 * rather than through an instance, since building a whole Driver just to
 * read a rule dictionary would be wasteful.
 */
interface Driver
{
    public function setUserAgent(?string $userAgent): void;

    public function getUserAgent(): ?string;

    public function setHttpHeaders(array $httpHeaders): void;

    public function getHttpHeader(string $header): ?string;

    public function match(string $regex, ?string $userAgent = null): bool;

    /**
     * The capture groups from the most recent successful match(), if any.
     *
     * @return array<int|string, string>
     */
    public function getMatches(): array;

    public function isMobile(?string $userAgent = null, ?array $httpHeaders = null): bool;

    public function isTablet(?string $userAgent = null, ?array $httpHeaders = null): bool;

    /** @return array<string, string|array<string>> */
    public static function phoneDevices(): array;

    /** @return array<string, string|array<string>> */
    public static function tabletDevices(): array;

    /** @return array<string, string|array<string>> */
    public static function operatingSystems(): array;

    /** @return array<string, string|array<string>> */
    public static function browsers(): array;

    /** @return array<string, string|array<string>> */
    public static function properties(): array;
}

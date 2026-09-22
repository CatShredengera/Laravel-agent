<?php

namespace CatShredengera\Agent\Support;

/**
 * Picks which generation of mobiledetect/mobiledetectlib is actually
 * installed — v2.x's global `Mobile_Detect` class, or v4.x's namespaced
 * `Detection\MobileDetect` — and returns the matching Driver.
 *
 * `Mobile_Detect` existing is the only reliable signal: it's a class name
 * unique to the v2.x line, present only when that's what got resolved
 * (composer.json's version constraint allows either `^2.7` or `^4.0`, so
 * either one legitimately can be). Everything else in this package treats
 * "not v2" as "v4" rather than trying to enumerate every possible version.
 */
final class DriverFactory
{
    /**
     * @return class-string<Driver>
     */
    public static function driverClass(): string
    {
        return class_exists('Mobile_Detect') ? LegacyDriver::class : ModernDriver::class;
    }

    public static function make(?array $httpHeaders = null, ?string $userAgent = null): Driver
    {
        $class = self::driverClass();

        return new $class($httpHeaders, $userAgent);
    }
}

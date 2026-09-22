<?php

namespace CatShredengera\Agent;

use BadMethodCallException;
use CatShredengera\Agent\Support\Driver;
use CatShredengera\Agent\Support\DriverFactory;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 * Reproduces jenssegers/agent's public `Agent` API on top of whichever
 * generation of mobiledetect/mobiledetectlib is actually installed:
 * v2.x's `Mobile_Detect` (what jenssegers/agent itself depended on, still
 * patched today) or v4.x's reworked `Detection\MobileDetect`. See
 * Support\Driver, Support\LegacyDriver and Support\ModernDriver — this
 * class holds a Driver instead of extending either underlying class
 * directly, since which one is even loadable depends on which got
 * installed (composer.json allows `^2.7 || ^4.0`).
 */
class Agent
{
    /**
     * List of desktop devices.
     */
    protected static array $desktopDevices = [
        'Macintosh' => 'Macintosh',
    ];

    /**
     * List of additional operating systems.
     */
    protected static array $additionalOperatingSystems = [
        'Windows' => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X' => 'Mac OS X',
        'Debian' => 'Debian',
        'Ubuntu' => 'Ubuntu',
        'Macintosh' => 'PPC',
        'OpenBSD' => 'OpenBSD',
        'Linux' => 'Linux',
        'ChromeOS' => 'CrOS',
    ];

    /**
     * List of additional browsers.
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera' => 'Opera|OPR',
        'Edge' => 'Edge|Edg',
        'Coc Coc' => 'coc_coc_browser',
        'UCBrowser' => 'UCBrowser',
        'Vivaldi' => 'Vivaldi',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'IE' => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape' => 'Netscape',
        'Mozilla' => 'Mozilla',
        'WeChat' => 'MicroMessenger',
    ];

    /**
     * List of additional properties.
     */
    protected static array $additionalProperties = [
        // Operating systems
        'Windows' => 'Windows NT [VER]',
        'Windows NT' => 'Windows NT [VER]',
        'OS X' => 'OS X [VER]',
        'BlackBerryOS' => ['BlackBerry[\w]+/[VER]', 'BlackBerry.*Version/[VER]', 'Version/[VER]'],
        'AndroidOS' => 'Android [VER]',
        'ChromeOS' => 'CrOS x86_64 [VER]',

        // Browsers
        'Opera Mini' => 'Opera Mini/[VER]',
        'Opera' => [' OPR/[VER]', 'Opera Mini/[VER]', 'Version/[VER]', 'Opera [VER]'],
        'Netscape' => 'Netscape/[VER]',
        'Mozilla' => 'rv:[VER]',
        'IE' => ['IEMobile/[VER];', 'IEMobile [VER]', 'MSIE [VER];', 'rv:[VER]'],
        'Edge' => ['Edge/[VER]', 'Edg/[VER]'],
        'Vivaldi' => 'Vivaldi/[VER]',
        'Coc Coc' => 'coc_coc_browser/[VER]',
    ];

    /**
     * The placeholder `version()` substitutes with a version-number regex.
     * Stable across every mobiledetect/mobiledetectlib generation (it's
     * `self::VER`/`VERSION_REGEX` there) — hardcoded here so version()
     * doesn't need driver-specific branching too.
     */
    protected const VER = '([\w._\+]+)';

    protected static ?CrawlerDetect $crawlerDetect = null;

    protected Driver $driver;

    /**
     * @param  array|null  $httpHeaders  PHP-flavored HTTP headers (e.g. Laravel's `$request->server()`).
     *                                   Pass null to auto-detect from PHP's superglobals, like `new Agent()` did.
     */
    public function __construct(?array $httpHeaders = null, ?string $userAgent = null)
    {
        $this->driver = DriverFactory::make($httpHeaders, $userAgent);
    }

    public function __call(string $name, array $arguments): bool
    {
        if (! str_starts_with($name, 'is')) {
            throw new BadMethodCallException("No such method exists: $name");
        }

        return $this->is(substr($name, 2));
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->driver->setUserAgent($userAgent);
    }

    public function getUserAgent(): ?string
    {
        return $this->driver->getUserAgent();
    }

    public function setHttpHeaders(array $httpHeaders): void
    {
        $this->driver->setHttpHeaders($httpHeaders);
    }

    public function getHttpHeader(string $header): ?string
    {
        return $this->driver->getHttpHeader($header);
    }

    /**
     * Some detection rules are relative (not standard), because of the
     * diversity of devices, vendors and their conventions in representing
     * the User-Agent or the HTTP headers. This method checks a custom
     * regex against the User-Agent string.
     */
    public function match(string $regex, ?string $userAgent = null): bool
    {
        return $this->driver->match($regex, $userAgent);
    }

    /**
     * Merge multiple rule arrays into one, combining regexes for keys that
     * appear in more than one array instead of letting later ones win.
     */
    protected static function mergeRules(array ...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                } elseif (is_array($merged[$key]) || is_array($value)) {
                    // Flatten rather than nest: Mobile-Detect v4 stores some
                    // rules (e.g. $properties['Opera']) as an array of
                    // alternative regexes, where jenssegers/agent's own
                    // additions assumed a flat list of strings. Nesting an
                    // array inside $merged[$key] here breaks every caller
                    // that expects a flat list of regex alternatives.
                    $merged[$key] = array_merge((array) $merged[$key], (array) $value);
                } else {
                    $merged[$key] .= '|'.$value;
                }
            }
        }

        return $merged;
    }

    /**
     * Get all detection rules used by named/magic `is*()` checks: the
     * standard mobile/tablet/OS/browser rules plus Agent's own desktop and
     * "additional" OS/browser rules.
     */
    public static function getDetectionRulesExtended(): array
    {
        $driver = DriverFactory::driverClass();

        return static::mergeRules(
            static::$desktopDevices,
            $driver::phoneDevices(),
            $driver::tabletDevices(),
            $driver::operatingSystems(),
            static::$additionalOperatingSystems,
            $driver::browsers(),
            static::$additionalBrowsers
        );
    }

    public function getCrawlerDetect(): CrawlerDetect
    {
        if (static::$crawlerDetect === null) {
            static::$crawlerDetect = new CrawlerDetect;
        }

        return static::$crawlerDetect;
    }

    public static function getBrowsers(): array
    {
        $driver = DriverFactory::driverClass();

        return static::mergeRules(
            static::$additionalBrowsers,
            $driver::browsers()
        );
    }

    public static function getOperatingSystems(): array
    {
        $driver = DriverFactory::driverClass();

        return static::mergeRules(
            $driver::operatingSystems(),
            static::$additionalOperatingSystems
        );
    }

    public static function getPlatforms(): array
    {
        return static::getOperatingSystems();
    }

    public static function getDesktopDevices(): array
    {
        return static::$desktopDevices;
    }

    public static function getProperties(): array
    {
        $driver = DriverFactory::driverClass();

        return static::mergeRules(
            static::$additionalProperties,
            $driver::properties()
        );
    }

    /**
     * Get accept languages.
     */
    public function languages(?string $acceptLanguage = null): array
    {
        if ($acceptLanguage === null) {
            $acceptLanguage = $this->getHttpHeader('HTTP_ACCEPT_LANGUAGE');
        }

        if (! $acceptLanguage) {
            return [];
        }

        $languages = [];

        // Parse accept language string.
        foreach (explode(',', $acceptLanguage) as $piece) {
            $parts = explode(';', $piece);
            $language = strtolower($parts[0]);
            $priority = empty($parts[1]) ? 1. : (float) str_replace('q=', '', $parts[1]);

            $languages[$language] = $priority;
        }

        // Sort languages by priority.
        arsort($languages);

        return array_keys($languages);
    }

    /**
     * Match a detection rule and return the matched key.
     */
    protected function findDetectionRulesAgainstUA(array $rules, ?string $userAgent = null): string|bool
    {
        $userAgent ??= $this->getUserAgent() ?? '';

        // Loop given rules
        foreach ($rules as $key => $regex) {
            if (empty($regex)) {
                continue;
            }

            if (is_array($regex)) {
                $regex = implode('|', $regex);
            }

            // Check match
            if ($this->match($regex, $userAgent)) {
                $matches = $this->driver->getMatches();

                return $key !== '' ? $key : (reset($matches) ?: false);
            }
        }

        return false;
    }

    /**
     * Get the browser name.
     */
    public function browser(?string $userAgent = null): string|bool
    {
        return $this->findDetectionRulesAgainstUA(static::getBrowsers(), $userAgent);
    }

    /**
     * Get the platform name.
     */
    public function platform(?string $userAgent = null): string|bool
    {
        return $this->findDetectionRulesAgainstUA(static::getPlatforms(), $userAgent);
    }

    /**
     * Get the device name.
     */
    public function device(?string $userAgent = null): string|bool
    {
        $driver = DriverFactory::driverClass();

        $rules = static::mergeRules(
            static::getDesktopDevices(),
            $driver::phoneDevices(),
            $driver::tabletDevices()
        );

        return $this->findDetectionRulesAgainstUA($rules, $userAgent);
    }

    /**
     * Check if the device is mobile.
     */
    public function isMobile(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        return $this->driver->isMobile($userAgent, $httpHeaders);
    }

    /**
     * Check if the device is a tablet.
     */
    public function isTablet(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        return $this->driver->isTablet($userAgent, $httpHeaders);
    }

    /**
     * Check if the device is a desktop computer.
     */
    public function isDesktop(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        if ($httpHeaders !== null) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent !== null) {
            $this->setUserAgent($userAgent);
        }

        // Check specifically for CloudFront headers if the user agent is 'Amazon CloudFront'.
        if ($this->getUserAgent() === 'Amazon CloudFront') {
            $isDesktopViewer = $this->getHttpHeader('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER');
            if ($isDesktopViewer !== null) {
                return $isDesktopViewer === 'true';
            }
        }

        return ! $this->isMobile() && ! $this->isTablet() && ! $this->isRobot();
    }

    /**
     * Check if the device is a mobile phone.
     */
    public function isPhone(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        return $this->isMobile($userAgent, $httpHeaders) && ! $this->isTablet($userAgent, $httpHeaders);
    }

    /**
     * Get the robot name.
     */
    public function robot(?string $userAgent = null): string|bool
    {
        if ($this->getCrawlerDetect()->isCrawler($userAgent ?: $this->getUserAgent())) {
            return ucfirst($this->getCrawlerDetect()->getMatches());
        }

        return false;
    }

    /**
     * Check if device is a robot.
     */
    public function isRobot(?string $userAgent = null): bool
    {
        return $this->getCrawlerDetect()->isCrawler($userAgent ?: $this->getUserAgent());
    }

    /**
     * Get the device type.
     */
    public function deviceType(?string $userAgent = null, ?array $httpHeaders = null): string
    {
        if ($this->isDesktop($userAgent, $httpHeaders)) {
            return 'desktop';
        } elseif ($this->isPhone($userAgent, $httpHeaders)) {
            return 'phone';
        } elseif ($this->isTablet($userAgent, $httpHeaders)) {
            return 'tablet';
        } elseif ($this->isRobot($userAgent)) {
            return 'robot';
        }

        return 'other';
    }

    /**
     * Get the version of the given browser/platform property.
     */
    public function version(string $propertyName, string $type = 'text'): float|bool|string
    {
        if (empty($propertyName)) {
            return false;
        }

        if ($type !== 'text' && $type !== 'float') {
            $type = 'text';
        }

        $properties = static::getProperties();

        if (! isset($properties[$propertyName])) {
            return false;
        }

        foreach ((array) $properties[$propertyName] as $propertyMatchString) {
            if (is_array($propertyMatchString)) {
                $propertyMatchString = implode('|', $propertyMatchString);
            }

            $propertyPattern = str_replace('[VER]', static::VER, $propertyMatchString);

            preg_match(sprintf('#%s#is', $propertyPattern), (string) $this->getUserAgent(), $match);

            if (! empty($match[1])) {
                return $type === 'float' ? $this->prepareVersionNo($match[1]) : $match[1];
            }
        }

        return false;
    }

    /**
     * Prepare the version number, e.g. "2_0" -> 2.0, "4.3.1" -> 4.31.
     */
    protected function prepareVersionNo(string $ver): float
    {
        $ver = str_replace(['_', ' ', '/'], '.', $ver);
        $parts = explode('.', $ver, 2);

        if (isset($parts[1])) {
            $parts[1] = str_replace('.', '', $parts[1]);
        }

        return (float) implode('.', $parts);
    }

    /**
     * Checks if a rule (e.g. `Windows`, `iPhone`, `IE`) matches its regex
     * against the User-Agent, searching Agent's extended rule set (the
     * standard rules plus desktop devices and additional OS/browsers) —
     * this is what the magic `is*()` methods and named `is()` calls used in
     * jenssegers/agent for things like `isWindows()` or `is('Chrome')`.
     */
    public function is(string $ruleName): bool
    {
        $userAgent = $this->getUserAgent();
        if (! $userAgent) {
            return false;
        }

        $rules = array_change_key_case(static::getDetectionRulesExtended());
        $ruleName = strtolower($ruleName);

        if (empty($rules[$ruleName])) {
            return false;
        }

        $regex = $rules[$ruleName];
        if (is_array($regex)) {
            $regex = implode('|', $regex);
        }

        return $this->match($regex, $userAgent);
    }
}

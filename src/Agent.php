<?php

namespace CatShredengera\Agent;

use Detection\MobileDetect;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 * Adapts Mobile-Detect v4's `Detection\MobileDetect` (which reworked the
 * constructor, dropped `Mobile_Detect`'s legacy method names and removed the
 * mobile/extended detection-type split) back to the public API of
 * jenssegers/agent's `Agent`, so calling code doesn't need to change.
 */
class Agent extends MobileDetect
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

    protected static ?CrawlerDetect $crawlerDetect = null;

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
     * @param  array|null  $httpHeaders  PHP-flavored HTTP headers (e.g. Laravel's `$request->server()`).
     *                                   Pass null to auto-detect from PHP's superglobals, like `new Agent()` did.
     */
    public function __construct(?array $httpHeaders = null, ?string $userAgent = null)
    {
        parent::__construct(config: ['autoInitOfHttpHeaders' => false]);

        if ($httpHeaders !== null) {
            // Callers (e.g. our service provider) pass the full, unfiltered
            // $request->server() / $_SERVER array, which under CLI/testing
            // contexts can contain non-scalar entries such as 'argv'. Those
            // aren't real HTTP headers, and Mobile-Detect's cache-key
            // builder blows up (`Array to string conversion`) if it sees one.
            $this->setHttpHeaders(array_filter($httpHeaders, 'is_scalar'));
        } else {
            $this->autoInitKnownHttpHeaders();
        }

        if ($userAgent !== null) {
            $this->setUserAgent($userAgent);
        }

        if (! $this->hasUserAgent()) {
            $this->setUserAgent('');
        }
    }

    /**
     * Get all detection rules used by named/magic `is*()` checks: the
     * standard mobile/tablet/OS/browser rules plus Agent's own desktop and
     * "additional" OS/browser rules.
     */
    public static function getDetectionRulesExtended(): array
    {
        static $rules;

        if (! $rules) {
            $rules = static::mergeRules(
                static::$desktopDevices,
                static::$phoneDevices,
                static::$tabletDevices,
                static::$operatingSystems,
                static::$additionalOperatingSystems,
                static::$browsers,
                static::$additionalBrowsers
            );
        }

        return $rules;
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
        return static::mergeRules(
            static::$additionalBrowsers,
            static::$browsers
        );
    }

    public static function getOperatingSystems(): array
    {
        return static::mergeRules(
            static::$operatingSystems,
            static::$additionalOperatingSystems
        );
    }

    public static function getPlatforms(): array
    {
        return static::mergeRules(
            static::$operatingSystems,
            static::$additionalOperatingSystems
        );
    }

    public static function getDesktopDevices(): array
    {
        return static::$desktopDevices;
    }

    public static function getProperties(): array
    {
        return static::mergeRules(
            static::$additionalProperties,
            static::$properties
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
                return $key !== '' ? $key : (reset($this->matchesArray) ?: false);
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
        $rules = static::mergeRules(
            static::getDesktopDevices(),
            static::getPhoneDevices(),
            static::getTabletDevices()
        );

        return $this->findDetectionRulesAgainstUA($rules, $userAgent);
    }

    /**
     * Check if the device is mobile. Kept accepting the (deprecated)
     * `$userAgent`/`$httpHeaders` overrides jenssegers/agent exposed, even
     * though Mobile-Detect v4's `isMobile()` no longer takes them itself.
     */
    public function isMobile(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        if ($httpHeaders !== null) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent !== null) {
            $this->setUserAgent($userAgent);
        }

        return $this->hasUserAgent() && parent::isMobile();
    }

    /**
     * Check if the device is a tablet.
     */
    public function isTablet(?string $userAgent = null, ?array $httpHeaders = null): bool
    {
        if ($httpHeaders !== null) {
            $this->setHttpHeaders($httpHeaders);
        }

        if ($userAgent !== null) {
            $this->setUserAgent($userAgent);
        }

        return $this->hasUserAgent() && parent::isTablet();
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
     * Checks if a rule (e.g. `Windows`, `iPhone`, `IE`) matches its regex
     * against the User-Agent, searching Agent's extended rule set (the
     * standard rules plus desktop devices and additional OS/browsers) —
     * this is what the magic `is*()` methods and named `is()` calls used in
     * jenssegers/agent for things like `isWindows()` or `is('Chrome')`.
     */
    public function is(string $ruleName): bool
    {
        if (! $this->hasUserAgent() || $this->isUserAgentEmpty()) {
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

        return $this->match($regex, (string) $this->getUserAgent());
    }
}

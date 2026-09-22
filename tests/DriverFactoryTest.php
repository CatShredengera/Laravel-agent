<?php

use CatShredengera\Agent\Support\DriverFactory;
use CatShredengera\Agent\Support\LegacyDriver;
use CatShredengera\Agent\Support\ModernDriver;

test('DriverFactory resolves to the driver matching whichever mobiledetect generation is actually installed', function () {
    // This is the invariant phpstan.neon.dist's ModernDriver ignore rules
    // lean on: ModernDriver is only reachable when `Detection\MobileDetect`
    // is v4.x's real class — not v2.x's own namespaced/Detection/MobileDetect.php
    // shim (`class MobileDetect extends \Mobile_Detect {}`), which exists
    // too and is what PHPStan resolves that name to when v2.x is installed.
    // Break the selector — e.g. make it prefer `Detection\MobileDetect`
    // over checking for `Mobile_Detect` first — and this fails instead of
    // silently constructing ModernDriver against v2.x's shim class.
    //
    // Under the prefer-lowest CI leg (mobiledetect/mobiledetectlib resolves
    // to its 2.8.34 floor) this proves LegacyDriver wins and ModernDriver is
    // unreachable; under prefer-stable it proves the reverse. Either way,
    // the class this test expects and the class actually constructed must
    // match the mobiledetect generation really sitting in vendor/ right now.
    $expectedClass = class_exists('Mobile_Detect') ? LegacyDriver::class : ModernDriver::class;

    expect(DriverFactory::driverClass())->toBe($expectedClass);
    expect(DriverFactory::make())->toBeInstanceOf($expectedClass);
});

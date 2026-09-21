<?php

use CatShredengera\Agent\Agent;

// Covers the parts of the v2 -> v4 adaptation that the ported AgentTest.php
// suite (which mirrors jenssegers/agent's own tests) never exercised:
// deviceType() and the Amazon CloudFront branch of isDesktop().

test('deviceType reports desktop, phone, tablet and robot', function () {
    $agent = new Agent;

    $agent->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');
    expect($agent->deviceType())->toBe('desktop');

    $agent->setUserAgent('Mozilla/5.0 (iPhone; U; ru; CPU iPhone OS 4_2_1 like Mac OS X; ru) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5');
    expect($agent->deviceType())->toBe('phone');

    $agent->setUserAgent('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25');
    expect($agent->deviceType())->toBe('tablet');

    $agent->setUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
    expect($agent->deviceType())->toBe('robot');
});

test('isDesktop trusts the Amazon CloudFront desktop-viewer header when present', function () {
    $agent = new Agent;
    $agent->setUserAgent('Amazon CloudFront');

    $agent->setHttpHeaders(['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER' => 'true']);
    expect($agent->isDesktop())->toBeTrue();

    $agent->setHttpHeaders(['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER' => 'false']);
    expect($agent->isDesktop())->toBeFalse();
});

test('isDesktop falls back to normal detection for CloudFront without a desktop-viewer header', function () {
    $agent = new Agent;
    $agent->setUserAgent('Amazon CloudFront');

    // No HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER header set: 'Amazon CloudFront'
    // itself isn't a mobile, tablet or robot user agent, so this falls
    // through to the same "not mobile, not tablet, not robot" check every
    // other isDesktop() call uses.
    expect($agent->isDesktop())->toBeTrue();
});

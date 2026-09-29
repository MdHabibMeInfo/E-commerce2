<?php

use App\Services\TotpService;

it('matches the RFC 6238 SHA-1 test vector', function () {
    $service = new TotpService;

    expect($service->codeAt('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59, 8))
        ->toBe('94287082');
});

it('accepts a valid code and rejects malformed or incorrect codes', function () {
    $service = new TotpService;
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    expect($service->verifyCode($secret, '94287082', 59, 0, 8))->toBeTrue();
    expect($service->verifyCode($secret, '94287083', 59, 0, 8))->toBeFalse();
    expect($service->verifyCode($secret, 'not-a-code', 59, 0, 8))->toBeFalse();
});

it('generates a base32 secret and single-use recovery code candidates', function () {
    $service = new TotpService;
    $secret = $service->generateSecret();
    $codes = $service->generateRecoveryCodes();

    expect($secret)->toMatch('/\A[A-Z2-7]{32}\z/');
    expect($codes)->toHaveCount(8);

    foreach ($codes as $code) {
        expect($code)->toMatch('/\A[A-F0-9]{4}(?:-[A-F0-9]{4}){3}\z/');
    }
});

it('renders an authenticator QR image without embedding the TOTP secret as text', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $provisioningUri = app(TotpService::class)->provisioningUri($secret, 'owner@example.com', 'Store');
    $dataUri = app(TotpService::class)->qrCodeDataUri($provisioningUri);
    $svg = base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')), true);

    expect($dataUri)->toStartWith('data:image/svg+xml;base64,')
        ->and($svg)->toBeString()
        ->and($svg)->toContain('<svg')
        ->and($svg)->not->toContain($secret)
        ->and($svg)->not->toContain($provisioningUri);
});

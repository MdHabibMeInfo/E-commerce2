<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->encodeBase32(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    public function qrCodeDataUri(string $provisioningUri): string
    {
        $renderer = new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($provisioningUri);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function codeAt(string $secret, int $timestamp, int $digits = 6): string
    {
        if (! in_array($digits, [6, 8], true)) {
            throw new InvalidArgumentException('TOTP codes must contain 6 or 8 digits.');
        }

        $key = $this->decodeBase32($secret);
        $counter = intdiv($timestamp, 30);
        $message = pack('N2', ($counter >> 32) & 0xFFFFFFFF, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $message, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function verifyCode(string $secret, string $code, ?int $timestamp = null, int $window = 1, int $digits = 6): bool
    {
        return $this->matchingTimestamp($secret, $code, $timestamp, $window, $digits) !== null;
    }

    public function matchingTimestamp(string $secret, string $code, ?int $timestamp = null, int $window = 1, int $digits = 6): ?int
    {
        if ($window < 0 || ! in_array($digits, [6, 8], true) || preg_match('/\A\d{'.$digits.'}\z/', $code) !== 1) {
            return null;
        }

        $timestamp ??= time();
        $currentStepTimestamp = intdiv($timestamp, 30) * 30;

        try {
            for ($offset = -$window; $offset <= $window; $offset++) {
                $stepTimestamp = $currentStepTimestamp + ($offset * 30);

                if (hash_equals($this->codeAt($secret, $stepTimestamp, $digits), $code)) {
                    return $stepTimestamp;
                }
            }
        } catch (InvalidArgumentException) {
            return null;
        }

        return null;
    }

    public function consumeTotpCode(User $user, string $code, ?int $timestamp = null): bool
    {
        return DB::transaction(function () use ($user, $code, $timestamp): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $secret = $lockedUser->two_factor_secret;

            if (! is_string($secret)) {
                return false;
            }

            $matchedTimestamp = $this->matchingTimestamp($secret, $code, $timestamp);
            $lastUsedTimestamp = $lockedUser->two_factor_last_used_at?->timestamp;

            if ($matchedTimestamp === null || ($lastUsedTimestamp !== null && $matchedTimestamp <= $lastUsedTimestamp)) {
                return false;
            }

            $lockedUser->two_factor_last_used_at = now()->setTimestamp($matchedTimestamp);
            $lockedUser->save();

            return true;
        });
    }

    /** @return list<string> */
    public function generateRecoveryCodes(): array
    {
        return array_map(
            static fn (): string => strtoupper(implode('-', str_split(bin2hex(random_bytes(8)), 4))),
            range(1, 8),
        );
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $normalizedCode = strtoupper(str_replace('-', '', trim($code)));

        if (preg_match('/\A[A-F0-9]{16}\z/', $normalizedCode) !== 1) {
            return false;
        }

        return DB::transaction(function () use ($user, $normalizedCode): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $recoveryCodes = $lockedUser->two_factor_recovery_codes ?? [];

            foreach ($recoveryCodes as $index => $hashedCode) {
                if (Hash::check($normalizedCode, $hashedCode)) {
                    unset($recoveryCodes[$index]);
                    $lockedUser->two_factor_recovery_codes = array_values($recoveryCodes);
                    $lockedUser->save();

                    return true;
                }
            }

            return false;
        });
    }

    private function encodeBase32(string $value): string
    {
        $bits = '';

        foreach (unpack('C*', $value) as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private function decodeBase32(string $secret): string
    {
        $secret = strtoupper(rtrim($secret, '='));

        if ($secret === '' || preg_match('/\A[A-Z2-7]+\z/', $secret) !== 1) {
            throw new InvalidArgumentException('The TOTP secret is invalid.');
        }

        $bits = '';

        foreach (str_split($secret) as $character) {
            $value = strpos(self::BASE32_ALPHABET, $character);
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}

<?php

namespace App\Services;

use App\Models\Setting;

class AppleOAuthConfiguration
{
    public function nativeClientId(): string
    {
        return trim((string) (
            config('services.apple.bundle_id')
            ?: Setting::getVal('apple_bundle_id')
        ));
    }

    public function audiences(): array
    {
        $configured = config('services.apple.client_ids', []);
        $audiences = is_array($configured) ? $configured : explode(',', (string) $configured);
        $audiences[] = $this->nativeClientId();
        $audiences[] = config('services.apple.services_id');
        $audiences[] = Setting::getVal('apple_services_id');

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $audiences
        ))));
    }

    public function teamId(): string
    {
        return trim((string) config('services.apple.team_id'));
    }

    public function keyId(): string
    {
        return trim((string) config('services.apple.key_id'));
    }

    public function privateKey(): string
    {
        $privateKey = trim((string) config('services.apple.private_key'));
        if ($privateKey !== '') {
            return str_replace('\\n', "\n", $privateKey);
        }

        $path = trim((string) config('services.apple.private_key_path'));
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return '';
        }

        $contents = file_get_contents($path);

        return is_string($contents) ? trim($contents) : '';
    }

    public function redirectUri(): string
    {
        return trim((string) config('services.apple.redirect_uri'));
    }

    public function servicesId(): string
    {
        return trim((string) (
            config('services.apple.services_id')
            ?: Setting::getVal('apple_services_id')
        ));
    }

    public function tokenExchangeRedirectUri(string $clientId): string
    {
        $nativeClientId = $this->nativeClientId();
        if ($nativeClientId !== '' && hash_equals($nativeClientId, trim($clientId))) {
            return '';
        }

        return $this->redirectUri();
    }

    public function issues(): array
    {
        $issues = [];

        if ($this->nativeClientId() === '') {
            $issues[] = 'APPLE_BUNDLE_ID chưa được cấu hình cho native iOS.';
        }
        if (! preg_match('/^[A-Z0-9]{10}$/', $this->teamId())) {
            $issues[] = 'APPLE_TEAM_ID phải gồm đúng 10 ký tự chữ hoa hoặc số.';
        }
        if (! preg_match('/^[A-Z0-9]{10}$/', $this->keyId())) {
            $issues[] = 'APPLE_KEY_ID phải gồm đúng 10 ký tự chữ hoa hoặc số.';
        }

        $privateKey = $this->privateKey();
        if ($privateKey === '') {
            $issues[] = 'APPLE_PRIVATE_KEY hoặc APPLE_PRIVATE_KEY_PATH chưa đọc được.';
        } elseif (! $this->isValidAppleSigningKey($privateKey)) {
            $issues[] = 'Khóa Apple phải là private key EC P-256 hợp lệ từ file AuthKey_*.p8.';
        }

        $redirectUri = $this->redirectUri();
        if ($redirectUri !== '' && ! str_starts_with($redirectUri, 'https://')) {
            $issues[] = 'APPLE_REDIRECT_URI phải dùng HTTPS hoặc để trống cho native iOS flow.';
        }

        return $issues;
    }

    public function isReady(): bool
    {
        return $this->issues() === [];
    }

    private function isValidAppleSigningKey(string $privateKey): bool
    {
        $key = @openssl_pkey_get_private($privateKey);
        if ($key === false) {
            return false;
        }

        $details = openssl_pkey_get_details($key);

        if (! is_array($details) || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_EC) {
            return false;
        }

        $curveName = strtolower((string) ($details['ec']['curve_name'] ?? ''));
        $curveOid = (string) ($details['ec']['curve_oid'] ?? '');

        return in_array($curveName, ['prime256v1', 'secp256r1'], true)
            || $curveOid === '1.2.840.10045.3.1.7';
    }
}

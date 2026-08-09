<?php

namespace App\Services;

use App\Exceptions\NativeOAuthVerificationException;
use App\Models\Setting;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class NativeOAuthTokenVerifier
{
    private ?string $lastAppleRefreshToken = null;

    public function verifyGoogle(string $idToken): array
    {
        $audience = trim((string) (Setting::getVal('google_client_id') ?: config('services.google.client_id')));
        if ($audience === '') {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        $claims = $this->decode(
            $idToken,
            'google',
            (string) config('services.google.jwks_url', 'https://www.googleapis.com/oauth2/v3/certs')
        );

        if (! in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            || ! $this->audienceMatches($claims['aud'] ?? null, $audience)
            || ! $this->hasValidExpiryAndSubject($claims)
        ) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        if (is_array($claims['aud'] ?? null)
            && count($claims['aud']) > 1
            && ! hash_equals($audience, (string) ($claims['azp'] ?? ''))
        ) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        return [
            'sub' => (string) $claims['sub'],
            'email' => $this->normalizedEmail($claims['email'] ?? null),
            'email_verified' => $this->claimIsTrue($claims['email_verified'] ?? false),
            'name' => $this->nullableString($claims['name'] ?? null),
            'picture' => $this->nullableString($claims['picture'] ?? null),
        ];
    }

    public function verifyApple(string $identityToken, string $nonce, string $authorizationCode): array
    {
        $this->lastAppleRefreshToken = null;

        $audiences = $this->appleAudiences();
        if ($audiences === []) {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        $claims = $this->decode(
            $identityToken,
            'apple',
            (string) config('services.apple.jwks_url', 'https://appleid.apple.com/auth/keys')
        );

        $audience = $this->matchingAudience($claims['aud'] ?? null, $audiences);
        if (($claims['iss'] ?? null) !== 'https://appleid.apple.com'
            || $audience === null
            || ! $this->hasValidExpiryAndSubject($claims)
            || ! hash_equals((string) ($claims['nonce'] ?? ''), hash('sha256', $nonce))
        ) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        $exchangedClaims = $this->exchangeAppleAuthorizationCode($authorizationCode, $audience);
        if (($exchangedClaims['iss'] ?? null) !== 'https://appleid.apple.com'
            || ! $this->audienceMatches($exchangedClaims['aud'] ?? null, $audience)
            || ! $this->hasValidExpiryAndSubject($exchangedClaims)
            || ! hash_equals((string) $claims['sub'], (string) $exchangedClaims['sub'])
        ) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        $expiresAt = now()->setTimestamp(max(time() + 1, (int) $claims['exp']));
        if (! Cache::add('oauth_apple_nonce:'.hash('sha256', $nonce), true, $expiresAt)
            || ! Cache::add('oauth_apple_code:'.hash('sha256', $authorizationCode), true, $expiresAt)
        ) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_REPLAYED', 409);
        }

        return [
            'sub' => (string) $claims['sub'],
            'email' => $this->normalizedEmail($claims['email'] ?? null),
            'email_verified' => $this->claimIsTrue($claims['email_verified'] ?? false),
            'audience' => $audience,
            // Kept in memory only so account deletion can revoke the Apple grant.
            // NativeOAuthController never serializes this provider credential.
            'provider_refresh_token' => $this->lastAppleRefreshToken,
        ];
    }

    /**
     * Revoke an Apple refresh token before deleting the local account.
     *
     * Apple returns invalid_grant when a grant was already revoked; that is
     * treated as success because the provider is already disconnected.
     */
    public function revokeAppleRefreshToken(string $refreshToken, ?string $clientId = null): void
    {
        $refreshToken = trim($refreshToken);
        $audiences = $this->appleAudiences();
        $clientId = trim((string) ($clientId ?: ($audiences[0] ?? '')));
        $teamId = trim((string) config('services.apple.team_id'));
        $keyId = trim((string) config('services.apple.key_id'));
        $privateKey = $this->applePrivateKey();
        $revokeUrl = trim((string) config('services.apple.revoke_url', 'https://appleid.apple.com/auth/revoke'));

        if ($refreshToken === '' || $clientId === '' || $teamId === '' || $keyId === '' || $privateKey === '' || $revokeUrl === '') {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        try {
            $issuedAt = time();
            $clientSecret = JWT::encode([
                'iss' => $teamId,
                'iat' => $issuedAt,
                'exp' => $issuedAt + 300,
                'aud' => 'https://appleid.apple.com',
                'sub' => $clientId,
            ], $privateKey, 'ES256', $keyId);

            $response = Http::asForm()->acceptJson()->timeout(5)->post($revokeUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'token' => $refreshToken,
                'token_type_hint' => 'refresh_token',
            ]);
        } catch (Throwable) {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        if ($response->successful()) {
            return;
        }

        if ($response->status() === 400 && $response->json('error') === 'invalid_grant') {
            return;
        }

        if ($response->serverError() || in_array($response->status(), [408, 425, 429], true)) {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
    }

    private function decode(string $token, string $provider, string $jwksUrl): array
    {
        if ($token === '' || $jwksUrl === '') {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        try {
            $header = $this->tokenHeader($token);
            if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null) || $header['kid'] === '') {
                throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
            }

            $jwks = $this->jwks($provider, $jwksUrl);
            if (! $this->jwksContainsKey($jwks, $header['kid'])) {
                Cache::forget("oauth_jwks:{$provider}");
                $jwks = $this->jwks($provider, $jwksUrl);
            }

            if (! $this->jwksContainsKey($jwks, $header['kid'])) {
                throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
            }

            return (array) JWT::decode($token, JWK::parseKeySet($jwks, 'RS256'));
        } catch (NativeOAuthVerificationException $e) {
            throw $e;
        } catch (Throwable) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }
    }

    private function jwks(string $provider, string $jwksUrl): array
    {
        try {
            return Cache::remember("oauth_jwks:{$provider}", now()->addHours(6), function () use ($jwksUrl): array {
                $response = Http::acceptJson()->timeout(5)->get($jwksUrl);
                if (! $response->successful() || ! is_array($response->json())) {
                    throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
                }

                return $response->json();
            });
        } catch (NativeOAuthVerificationException $e) {
            throw $e;
        } catch (Throwable) {
            // Network, DNS and timeout failures are provider outages, not bad user credentials.
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }
    }

    private function tokenHeader(string $token): array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        $header = JWT::jsonDecode(JWT::urlsafeB64Decode($segments[0]));

        return is_object($header) ? (array) $header : [];
    }

    private function jwksContainsKey(array $jwks, string $keyId): bool
    {
        foreach ($jwks['keys'] ?? [] as $key) {
            if (is_array($key) && hash_equals($keyId, (string) ($key['kid'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    private function exchangeAppleAuthorizationCode(string $authorizationCode, string $clientId): array
    {
        $teamId = trim((string) config('services.apple.team_id'));
        $keyId = trim((string) config('services.apple.key_id'));
        $privateKey = $this->applePrivateKey();
        $tokenUrl = trim((string) config('services.apple.token_url', 'https://appleid.apple.com/auth/token'));
        if ($teamId === '' || $keyId === '' || $privateKey === '' || $tokenUrl === '') {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        try {
            $issuedAt = time();
            $clientSecret = JWT::encode([
                'iss' => $teamId,
                'iat' => $issuedAt,
                'exp' => $issuedAt + 300,
                'aud' => 'https://appleid.apple.com',
                'sub' => $clientId,
            ], $privateKey, 'ES256', $keyId);
        } catch (Throwable) {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        try {
            $payload = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $authorizationCode,
                'grant_type' => 'authorization_code',
            ];
            $redirectUri = trim((string) config('services.apple.redirect_uri'));
            if ($redirectUri !== '') {
                $payload['redirect_uri'] = $redirectUri;
            }

            $response = Http::asForm()->acceptJson()->timeout(5)->post($tokenUrl, $payload);
        } catch (Throwable) {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        if ($response->serverError() || in_array($response->status(), [408, 425, 429], true)) {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }
        if (! $response->successful()) {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        $refreshToken = $response->json('refresh_token');
        $this->lastAppleRefreshToken = is_string($refreshToken) && trim($refreshToken) !== ''
            ? trim($refreshToken)
            : null;
        $exchangedIdentityToken = $response->json('id_token');
        if (! is_string($exchangedIdentityToken) || $exchangedIdentityToken === '') {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        return $this->decode(
            $exchangedIdentityToken,
            'apple',
            (string) config('services.apple.jwks_url', 'https://appleid.apple.com/auth/keys')
        );
    }

    private function applePrivateKey(): string
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

    private function appleAudiences(): array
    {
        $configured = config('services.apple.client_ids', []);
        $audiences = is_array($configured) ? $configured : explode(',', (string) $configured);
        $audiences[] = Setting::getVal('apple_services_id');
        $audiences[] = Setting::getVal('apple_bundle_id');

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $audiences
        ))));
    }

    private function audienceMatches(mixed $claim, string $expected): bool
    {
        if (is_string($claim)) {
            return hash_equals($expected, $claim);
        }

        return is_array($claim) && in_array($expected, $claim, true);
    }

    private function matchingAudience(mixed $claim, array $expected): ?string
    {
        foreach ($expected as $audience) {
            if ($this->audienceMatches($claim, $audience)) {
                return $audience;
            }
        }

        return null;
    }

    private function hasValidExpiryAndSubject(array $claims): bool
    {
        return isset($claims['exp'])
            && is_numeric($claims['exp'])
            && (int) $claims['exp'] > time()
            && is_string($claims['sub'] ?? null)
            && trim($claims['sub']) !== '';
    }

    private function claimIsTrue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    private function normalizedEmail(mixed $value): ?string
    {
        $email = $this->nullableString($value);

        return $email === null ? null : strtolower($email);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

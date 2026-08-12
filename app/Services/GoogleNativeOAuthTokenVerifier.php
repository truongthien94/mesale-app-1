<?php

namespace App\Services;

use App\Exceptions\NativeOAuthVerificationException;
use App\Models\Setting;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleNativeOAuthTokenVerifier
{
    public function verify(string $idToken): array
    {
        $audience = trim((string) (Setting::getVal('google_client_id') ?: config('services.google.client_id')));
        if ($audience === '') {
            throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
        }

        $claims = $this->decode($idToken);
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

    private function decode(string $token): array
    {
        if ($token === '') {
            throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
        }

        try {
            $header = $this->tokenHeader($token);
            if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null) || $header['kid'] === '') {
                throw new NativeOAuthVerificationException('OAUTH_CREDENTIAL_INVALID');
            }

            $jwks = $this->jwks();
            if (! $this->jwksContainsKey($jwks, $header['kid'])) {
                Cache::forget('oauth_jwks:google');
                $jwks = $this->jwks();
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

    private function jwks(): array
    {
        $url = (string) config('services.google.jwks_url', 'https://www.googleapis.com/oauth2/v3/certs');

        try {
            return Cache::remember('oauth_jwks:google', now()->addHours(6), function () use ($url): array {
                $response = Http::acceptJson()->timeout(5)->get($url);
                if (! $response->successful() || ! is_array($response->json())) {
                    throw new NativeOAuthVerificationException('OAUTH_PROVIDER_UNAVAILABLE', 503);
                }

                return $response->json();
            });
        } catch (NativeOAuthVerificationException $e) {
            throw $e;
        } catch (Throwable) {
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

    private function audienceMatches(mixed $claim, string $expected): bool
    {
        if (is_string($claim)) {
            return hash_equals($expected, $claim);
        }

        return is_array($claim) && in_array($expected, $claim, true);
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

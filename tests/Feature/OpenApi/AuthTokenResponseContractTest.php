<?php

namespace Tests\Feature\OpenApi;

use App\Http\Controllers\Api\V1\AuthController;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class AuthTokenResponseContractTest extends TestCase
{
    public function test_session_token_payload_exposes_canonical_access_token_and_legacy_alias(): void
    {
        $user = new User();
        $user->forceFill([
            'id' => 42,
            'name' => 'Mobile Contract User',
            'email' => 'mobile-contract@example.test',
            'phone' => null,
            'avatar' => null,
            'balance' => 100000,
            'total_cashback' => 25000,
            'total_referral_earned' => 5000,
            'total_withdrawn' => 10000,
            'referral_code' => 'REFTEST',
            'status' => 'active',
            'email_verified_at' => null,
            'created_at' => null,
        ]);

        $controller = new class extends AuthController
        {
            public function tokenResponseDataForTest(User $user, string $plainToken, ?string $expiresAt): array
            {
                return $this->tokenResponseData($user, $plainToken, $expiresAt);
            }
        };

        $payload = $controller->tokenResponseDataForTest(
            $user,
            'session-token-value',
            '2030-01-01T00:00:00+00:00'
        );

        $this->assertSame('session-token-value', $payload['access_token']);
        $this->assertSame($payload['access_token'], $payload['token']);
        $this->assertSame('Bearer', $payload['token_type']);
        $this->assertSame('2030-01-01T00:00:00+00:00', $payload['expires_at']);
        $this->assertSame(42, $payload['user']['id']);
        $this->assertArrayNotHasKey('api_token', $payload['user']);
    }
}

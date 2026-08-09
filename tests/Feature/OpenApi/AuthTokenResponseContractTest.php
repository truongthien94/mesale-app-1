<?php

namespace Tests\Feature\OpenApi;

use App\Models\ApiLog;
use App\Models\ApiToken;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthTokenResponseContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createIsolatedAuthSchema();
        $this->enableOpenApiAuth();
    }

    public function test_login_returns_a_revocable_bearer_session_token_and_integer_vnd_values(): void
    {
        $user = $this->createUser([
            'balance' => '100000.00',
            'total_cashback' => '25000.00',
            'total_referral_earned' => '5000.00',
            'total_withdrawn' => '10000.00',
        ]);
        Setting::setVal('openapi_token_ttl_days', '30');

        $response = $this->postJson('/api/v1/openapi/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'HTTP Contract Test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.api_token');

        $data = $response->json('data');
        $this->assertIsString($data['access_token']);
        $this->assertSame($data['access_token'], $data['token']);
        $this->assertNotSame('', $data['access_token']);
        $this->assertIsString($data['expires_at']);

        foreach (['balance', 'total_cashback', 'total_referral_earned', 'total_withdrawn'] as $field) {
            $this->assertIsInt($data['user'][$field], "{$field} must be an integer VND amount");
        }

        $this->assertSame(100000, $data['user']['balance']);
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'token' => ApiToken::hashToken($data['access_token']),
            'name' => 'HTTP Contract Test',
        ]);
        $this->assertDatabaseMissing('api_tokens', ['token' => $data['access_token']]);

        $apiLog = ApiLog::query()->latest('id')->firstOrFail();
        $this->assertSame(200, $apiLog->status_code);
        $this->assertStringNotContainsString('password', (string) $apiLog->request_data);
        $this->assertStringNotContainsString('correct-password', (string) $apiLog->request_data);
    }

    public function test_register_returns_the_same_session_token_contract(): void
    {
        $response = $this->postJson('/api/v1/openapi/auth/register', [
            'name' => 'Registered Mobile User',
            'email' => 'registered-mobile@example.test',
            'password' => 'registered-password',
            'password_confirmation' => 'registered-password',
            'device_name' => 'Registration Contract Test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'registered-mobile@example.test')
            ->assertJsonPath('data.user.balance', 0)
            ->assertJsonMissingPath('data.user.api_token');

        $accessToken = $response->json('data.access_token');
        $this->assertIsString($accessToken);
        $this->assertSame($accessToken, $response->json('data.token'));
        $this->assertDatabaseHas('api_tokens', ['token' => ApiToken::hashToken($accessToken)]);
        $this->assertDatabaseHas('users', ['email' => 'registered-mobile@example.test']);
    }

    public function test_account_endpoint_restores_the_bearer_session_user_with_integer_vnd_wallet_values(): void
    {
        $user = $this->createUser([
            'balance' => '123456.00',
            'total_cashback' => '34567.00',
            'total_referral_earned' => '4567.00',
            'total_withdrawn' => '12000.00',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Session Restore Test', 30, '127.0.0.1');

        $response = $this->withToken($plainToken)->getJson('/api/v1/openapi/account');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.wallet.currency', 'VND');

        $wallet = $response->json('data.wallet');
        $expected = [
            'balance' => 123456,
            'total_cashback' => 34567,
            'total_referral_earned' => 4567,
            'total_withdrawn' => 12000,
        ];

        foreach ($expected as $field => $amount) {
            $this->assertIsInt($wallet[$field], "{$field} must be restored as integer VND");
            $this->assertSame($amount, $wallet[$field]);
        }
    }

    public function test_two_factor_login_defers_token_issuance_until_the_challenge_is_verified(): void
    {
        $user = $this->createUser(['email_otp_enabled' => true]);

        $challengeResponse = $this->postJson('/api/v1/openapi/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $challengeResponse->assertOk()
            ->assertJsonPath('data.two_factor_required', true)
            ->assertJsonPath('data.methods.0', 'email_otp')
            ->assertJsonMissingPath('data.access_token');
        $this->assertDatabaseCount('api_tokens', 0);

        $user->forceFill([
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $verifiedResponse = $this->postJson('/api/v1/openapi/auth/login/2fa', [
            'challenge_token' => $challengeResponse->json('data.challenge_token'),
            'email_otp_code' => '123456',
            'device_name' => '2FA Contract Test',
        ]);

        $verifiedResponse->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id);
        $this->assertIsString($verifiedResponse->json('data.access_token'));
        $this->assertDatabaseCount('api_tokens', 1);
        $this->assertNull($user->fresh()->otp_code);
    }

    public function test_auth_middleware_requires_bearer_token_and_logout_revokes_only_the_current_token(): void
    {
        $user = $this->createUser();
        [$plainToken] = ApiToken::generateFor($user, 'Logout Contract Test', 30, '127.0.0.1');

        $this->postJson('/api/v1/openapi/auth/logout')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('api_tokens', ['token' => ApiToken::hashToken($plainToken)]);
    }

    public function test_api_enabled_middleware_blocks_login_before_the_controller_when_disabled(): void
    {
        Setting::setVal('openapi_status', '0');

        $this->postJson('/api/v1/openapi/auth/login', [
            'email' => 'nobody@example.test',
            'password' => 'irrelevant-password',
        ])->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'API_DISABLED');

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_invalid_credentials_never_issue_a_session_token(): void
    {
        $user = $this->createUser();

        $this->postJson('/api/v1/openapi/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonMissingPath('data.access_token');

        $this->assertDatabaseCount('api_tokens', 0);
    }

    private function enableOpenApiAuth(): void
    {
        foreach ([
            'openapi_status' => '1',
            'openapi_auth_status' => '1',
            'registration_enabled' => '1',
            'email_verification_enabled' => '0',
            'referral_enabled' => '0',
            'smtp_status' => '0',
            'ip_register_limit' => '5',
        ] as $key => $value) {
            Setting::setVal($key, $value);
        }
    }

    private function createUser(array $attributes = []): User
    {
        $user = new User;
        $user->forceFill(array_merge([
            'name' => 'Mobile Contract User',
            'email' => 'mobile-contract@example.test',
            'phone' => null,
            'password' => Hash::make('correct-password'),
            'balance' => '0.00',
            'total_cashback' => '0.00',
            'total_referral_earned' => '0.00',
            'total_withdrawn' => '0.00',
            'referral_code' => 'REFTEST',
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
            'google2fa_enabled' => false,
            'email_otp_enabled' => false,
        ], $attributes));
        $user->save();

        return $user;
    }

    private function createIsolatedAuthSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_cashback', 15, 2)->default(0);
            $table->decimal('total_referral_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->unsignedInteger('referral_clicks')->default(0);
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->text('google2fa_secret')->nullable();
            $table->boolean('google2fa_enabled')->default(false);
            $table->boolean('email_otp_enabled')->default(false);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('google_id')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('country', 100)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('bot_zalo_chat_id', 64)->nullable();
            $table->string('bot_telegram_chat_id', 64)->nullable();
            $table->string('api_token', 64)->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->string('token', 64)->unique();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('activity');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('cashback_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
        });

        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
        });

        Schema::create('api_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->string('api_group', 20);
            $table->smallInteger('status_code')->nullable();
            $table->text('request_data')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}

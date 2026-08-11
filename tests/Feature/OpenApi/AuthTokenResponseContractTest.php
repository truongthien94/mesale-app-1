<?php

namespace Tests\Feature\OpenApi;

use App\Models\ActivityLog;
use App\Models\ApiLog;
use App\Models\ApiToken;
use App\Models\Setting;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        $this->assertSame('[REDACTED]', json_decode($apiLog->request_data, true, flags: JSON_THROW_ON_ERROR)['password']);
        $this->assertStringNotContainsString('correct-password', (string) $apiLog->request_data);
    }

    public function test_api_logging_recursively_redacts_sensitive_fields_and_key_variants(): void
    {
        $secrets = [
            'top-password-secret',
            'confirmation-secret',
            'plain-token-secret',
            'access-token-secret',
            'refresh-token-secret',
            'challenge-token-secret',
            'google-2fa-secret',
            'email-otp-secret',
            'otp-secret',
            'account-number-secret',
            'camel-account-secret',
            'private-key-secret',
        ];

        $this->postJson('/api/v1/openapi/auth/login', [
            'email' => 'unknown@example.test',
            'password' => $secrets[0],
            'password_confirmation' => $secrets[1],
            'security' => [
                'token' => $secrets[2],
                'accessToken' => $secrets[3],
                'refresh_token' => $secrets[4],
                'challenge-token' => $secrets[5],
                'google2fa_code' => $secrets[6],
                'emailOtpCode' => $secrets[7],
                'otp' => $secrets[8],
            ],
            'payment' => [
                'account_number' => $secrets[9],
                'bankAccountNumber' => $secrets[10],
            ],
            'nested' => [
                ['privateKey' => $secrets[11], 'safe_value' => 'retained-context'],
            ],
        ])->assertUnauthorized();

        $rawLog = ApiLog::query()->latest('id')->firstOrFail()->request_data;
        $logged = json_decode($rawLog, true, flags: JSON_THROW_ON_ERROR);

        foreach ([
            'password',
            'password_confirmation',
            'security.token',
            'security.accessToken',
            'security.refresh_token',
            'security.challenge-token',
            'security.google2fa_code',
            'security.emailOtpCode',
            'security.otp',
            'payment.account_number',
            'payment.bankAccountNumber',
            'nested.0.privateKey',
        ] as $path) {
            $this->assertSame('[REDACTED]', data_get($logged, $path), "{$path} was not redacted");
        }

        $this->assertSame('retained-context', data_get($logged, 'nested.0.safe_value'));
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $rawLog);
        }
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

    public function test_registration_ignores_inline_referral_and_starts_the_new_account_pending(): void
    {
        Setting::setVal('referral_enabled', '1');
        $referrer = $this->createUser([
            'email' => 'registration-referrer@example.test',
            'referral_code' => 'REGREF123',
            'ip_address' => '198.51.100.10',
            'referral_prompt_decided_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/openapi/auth/register', [
            'name' => 'Pending Referral User',
            'email' => 'pending-referral@example.test',
            'password' => 'registered-password',
            'password_confirmation' => 'registered-password',
            'referral_code' => $referrer->referral_code,
            'device_name' => 'Referral Registration Test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.referral_prompt_pending', true);

        $registeredUser = User::query()->where('email', 'pending-referral@example.test')->firstOrFail();
        $this->assertNull($registeredUser->referred_by);
        $this->assertNull($registeredUser->referral_prompt_decided_at);
        $this->assertDatabaseCount('referrals', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_referral_prompt_applies_a_valid_code_once_then_rejects_replay(): void
    {
        Setting::setVal('referral_enabled', '1');
        Setting::setVal('block_same_ip_referral', '1');

        $referrer = $this->createUser([
            'email' => 'valid-referrer@example.test',
            'referral_code' => 'VALIDREF1',
            'ip_address' => '198.51.100.20',
            'referral_prompt_decided_at' => now(),
        ]);
        $user = $this->createUser([
            'email' => 'valid-referred@example.test',
            'referral_code' => 'NEWUSER1',
            'referral_prompt_decided_at' => null,
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Referral Apply Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/referral-code', ['referral_code' => 'VALIDREF1'])
            ->assertOk()
            ->assertJsonPath('data.referral_prompt_pending', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'referred_by' => $referrer->id,
        ]);
        $this->assertNotNull($user->refresh()->referral_prompt_decided_at);
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $referrer->id]);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/referral-code', ['skip' => true])
            ->assertStatus(409)
            ->assertJsonPath('code', 'REFERRAL_PROMPT_ALREADY_DECIDED');

        $this->assertDatabaseCount('referrals', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_same_ip_referral_rejection_leaves_the_prompt_pending(): void
    {
        Setting::setVal('referral_enabled', '1');
        Setting::setVal('block_same_ip_referral', '1');

        $referrer = $this->createUser([
            'email' => 'same-ip-referrer@example.test',
            'referral_code' => 'SAMEIP01',
            'ip_address' => '127.0.0.1',
            'referral_prompt_decided_at' => now(),
        ]);
        $user = $this->createUser([
            'email' => 'same-ip-referred@example.test',
            'referral_code' => 'NEWUSER2',
            'referral_prompt_decided_at' => null,
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Referral Same IP Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/referral-code', ['referral_code' => 'UNKNOWN1'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'REFERRAL_CODE_INVALID');
        $this->assertNull($user->refresh()->referral_prompt_decided_at);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/referral-code', ['referral_code' => $referrer->referral_code])
            ->assertStatus(422)
            ->assertJsonPath('code', 'REFERRAL_CODE_INVALID');

        $this->assertNull($user->refresh()->referral_prompt_decided_at);
        $this->assertNull($user->referred_by);
        $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/account')
            ->assertOk()
            ->assertJsonPath('data.referral_prompt_pending', true);
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_skipping_referral_prompt_decides_it_without_side_effects(): void
    {
        Setting::setVal('referral_enabled', '1');
        $user = $this->createUser([
            'email' => 'skip-referral@example.test',
            'referral_code' => 'NEWUSER3',
            'referral_prompt_decided_at' => null,
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Referral Skip Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/referral-code', ['skip' => true])
            ->assertOk()
            ->assertJsonPath('data.referral_prompt_pending', false);

        $this->assertNotNull($user->refresh()->referral_prompt_decided_at);
        $this->assertNull($user->referred_by);
        $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/account')
            ->assertOk()
            ->assertJsonPath('data.referral_prompt_pending', false);
        $this->assertDatabaseCount('referrals', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_referral_prompt_migration_backfills_existing_members_as_decided(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('referral_prompt_decided_at');
        });

        $existingUser = $this->createUser([
            'email' => 'existing-before-migration@example.test',
            'referral_code' => 'EXISTING1',
            'created_at' => now()->subYear(),
        ]);

        $migration = require database_path('migrations/2026_08_10_000001_add_referral_prompt_decided_at_to_users_table.php');
        $migration->up();

        $this->assertNotNull(
            DB::table('users')->where('id', $existingUser->id)->value('referral_prompt_decided_at')
        );

        Setting::setVal('referral_enabled', '1');
        [$plainToken] = ApiToken::generateFor($existingUser->refresh(), 'Backfill Test', 30, '127.0.0.1');
        $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/account')
            ->assertOk()
            ->assertJsonPath('data.referral_prompt_pending', false);

        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'referral_prompt_decided_at'));
    }

    public function test_account_endpoint_restores_the_bearer_session_user_with_integer_vnd_wallet_values(): void
    {
        $user = $this->createUser([
            'balance' => '123456.00',
            'total_cashback' => '34567.00',
            'total_referral_earned' => '4567.00',
            'total_withdrawn' => '12000.00',
        ]);
        $otherUser = $this->createUser([
            'email' => 'other-wallet-user@example.test',
            'referral_code' => 'OTHERWALLET',
        ]);

        DB::table('cashback_histories')->insert([
            ['user_id' => $user->id, 'status' => 'pending', 'cashback_amount' => '1200.49'],
            ['user_id' => $user->id, 'status' => 'pending', 'cashback_amount' => '300.51'],
            ['user_id' => $user->id, 'status' => 'approved', 'cashback_amount' => '9000.51'],
            ['user_id' => $otherUser->id, 'status' => 'pending', 'cashback_amount' => '750000.00'],
            ['user_id' => $otherUser->id, 'status' => 'approved', 'cashback_amount' => '900000.00'],
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Session Restore Test', 30, '127.0.0.1');

        $response = $this->withToken($plainToken)->getJson('/api/v1/openapi/account');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.preferences.locale', 'vi')
            ->assertJsonPath('data.preferences.currency', 'VND')
            ->assertJsonPath('data.wallet.currency', 'VND')
            ->assertJsonPath('data.stats.orders_total', 3)
            ->assertJsonPath('data.stats.orders_pending', 2)
            ->assertJsonPath('data.stats.orders_approved', 1)
            ->assertJsonPath('data.stats.orders_rejected', 0);

        $wallet = $response->json('data.wallet');
        $expected = [
            'balance' => 123456,
            'pending_cashback' => 1501,
            'approved_cashback' => 9001,
            'total_cashback' => 34567,
            'total_referral_earned' => 4567,
            'total_withdrawn' => 12000,
        ];

        foreach ($expected as $field => $amount) {
            $this->assertIsInt($wallet[$field], "{$field} must be restored as integer VND");
            $this->assertSame($amount, $wallet[$field]);
        }
    }

    public function test_orders_endpoint_returns_only_the_authenticated_users_unrecorded_clicks_without_duplicates(): void
    {
        Setting::setVal('cashback_show_pending_clicks', '1');
        $user = $this->createUser();
        $otherUser = $this->createUser([
            'email' => 'other-orders-user@example.test',
            'referral_code' => 'OTHERORDERS',
        ]);

        $this->createOrderRecord($user, [
            'trans_id' => 'MATCHED-TRANS',
            'order_id' => 'ORDER-MATCHED',
        ]);
        $this->createClickRecord($user, [
            'trans_id' => 'MATCHED-TRANS',
            'product_name' => 'Already recorded click',
        ]);
        $clickId = $this->createClickRecord($user, [
            'trans_id' => 'UNRECORDED-OWN',
            'platform' => 'tiktok',
            'product_name' => 'Own unrecorded product',
            'original_price' => '250000.49',
            'cashback_amount' => '12000.51',
            'commission_amount' => '20000.49',
            'cashback_rate' => '4.80',
            'affiliate_url' => 'https://mesale.vn/own-unrecorded',
        ]);
        $this->createClickRecord($otherUser, [
            'trans_id' => 'UNRECORDED-OTHER',
            'product_name' => 'Other user private click',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Orders Unrecorded Test', 30, '127.0.0.1');

        $response = $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/orders?status=unrecorded&per_page=10');

        $response->assertOk()
            ->assertJsonPath('data.meta.show_unrecorded', true)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $clickId)
            ->assertJsonPath('data.items.0.record_type', 'unrecorded')
            ->assertJsonPath('data.items.0.order_id', null)
            ->assertJsonPath('data.items.0.trans_id', 'UNRECORDED-OWN')
            ->assertJsonPath('data.items.0.status', 'unrecorded')
            ->assertJsonPath('data.items.0.approved_at', null)
            ->assertJsonPath('data.items.0.rejected_reason', null);

        $item = $response->json('data.items.0');
        foreach (['original_price', 'commission_amount', 'cashback_amount'] as $field) {
            $this->assertIsInt($item[$field]);
        }
        $this->assertSame(250000, $item['original_price']);
        $this->assertSame(20000, $item['commission_amount']);
        $this->assertSame(12001, $item['cashback_amount']);
        $this->assertSame('https://mesale.vn/own-unrecorded', $item['affiliate_url']);
        $this->assertStringNotContainsString('Other user private click', json_encode($response->json(), JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Already recorded click', json_encode($response->json(), JSON_THROW_ON_ERROR));
    }

    public function test_orders_endpoint_hides_unrecorded_capability_and_falls_back_to_recorded_orders_when_disabled(): void
    {
        Setting::setVal('cashback_show_pending_clicks', '0');
        $user = $this->createUser();
        $orderId = $this->createOrderRecord($user, [
            'order_id' => 'ORDER-FLAG-OFF',
            'product_name' => 'Recorded order remains visible',
        ]);
        $this->createClickRecord($user, [
            'trans_id' => 'CLICK-FLAG-OFF',
            'product_name' => 'Hidden pending click',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Orders Flag Off Test', 30, '127.0.0.1');

        $response = $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/orders?status=unrecorded');

        $response->assertOk()
            ->assertJsonPath('data.meta.show_unrecorded', false)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $orderId)
            ->assertJsonPath('data.items.0.record_type', 'order')
            ->assertJsonPath('data.items.0.order_id', 'ORDER-FLAG-OFF');
        $this->assertStringNotContainsString('Hidden pending click', json_encode($response->json(), JSON_THROW_ON_ERROR));
    }

    public function test_bot_orders_remain_recorded_only_when_open_api_exposes_unrecorded_clicks(): void
    {
        Setting::setVal('cashback_show_pending_clicks', '1');
        $user = $this->createUser();
        $this->createOrderRecord($user, [
            'order_id' => 'BOT-RECORDED-ORDER',
            'product_name' => 'Bot recorded order',
        ]);
        $this->createClickRecord($user, [
            'trans_id' => 'BOT-UNRECORDED-CLICK',
            'product_name' => 'Bot hidden click',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Bot Orders Contract Test', 30, '127.0.0.1');

        $response = $this->withToken($plainToken)
            ->getJson('/api/v1/bot/orders?status=unrecorded');

        $response->assertOk()
            ->assertJsonPath('data.meta.show_unrecorded', false)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.record_type', 'order')
            ->assertJsonPath('data.items.0.order_id', 'BOT-RECORDED-ORDER')
            ->assertJsonMissingPath('data.items.0.id');
        $this->assertStringNotContainsString('Bot hidden click', json_encode($response->json(), JSON_THROW_ON_ERROR));
    }

    public function test_orders_endpoint_merges_sources_with_stable_tie_breaking_and_pagination(): void
    {
        Setting::setVal('cashback_show_pending_clicks', '1');
        $user = $this->createUser();
        $timestamp = '2026-08-11 12:00:00';

        $orderOne = $this->createOrderRecord($user, ['order_id' => 'ORDER-ONE', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        $orderTwo = $this->createOrderRecord($user, ['order_id' => 'ORDER-TWO', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        $clickOne = $this->createClickRecord($user, ['trans_id' => 'CLICK-ONE', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        $clickTwo = $this->createClickRecord($user, ['trans_id' => 'CLICK-TWO', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        [$plainToken] = ApiToken::generateFor($user, 'Orders Pagination Test', 30, '127.0.0.1');

        $pages = [];
        foreach ([1, 2] as $page) {
            $response = $this->withToken($plainToken)
                ->getJson("/api/v1/openapi/orders?per_page=2&page={$page}")
                ->assertOk()
                ->assertJsonPath('data.pagination.total', 4)
                ->assertJsonPath('data.pagination.last_page', 2);
            $pages[$page] = array_map(
                fn (array $item): string => $item['record_type'].':'.$item['id'],
                $response->json('data.items')
            );
        }

        $this->assertSame(["order:{$orderTwo}", "order:{$orderOne}"], $pages[1]);
        $this->assertSame(["unrecorded:{$clickTwo}", "unrecorded:{$clickOne}"], $pages[2]);
        $this->assertCount(4, array_unique(array_merge($pages[1], $pages[2])));

        $repeat = $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/orders?per_page=2&page=1')
            ->assertOk();
        $this->assertSame($pages[1], array_map(
            fn (array $item): string => $item['record_type'].':'.$item['id'],
            $repeat->json('data.items')
        ));
    }

    public function test_orders_endpoint_supports_lazada_search_and_date_filters(): void
    {
        Setting::setVal('cashback_show_pending_clicks', '1');
        $user = $this->createUser();
        $matchingId = $this->createOrderRecord($user, [
            'order_id' => 'LZD-NEEDLE-001',
            'platform' => 'lazada',
            'product_name' => 'Needle Lazada product',
            'created_at' => '2026-08-10 09:00:00',
            'updated_at' => '2026-08-10 09:00:00',
        ]);
        $this->createOrderRecord($user, [
            'order_id' => 'LZD-NEEDLE-OLD',
            'platform' => 'lazada',
            'product_name' => 'Needle outside range',
            'created_at' => '2026-08-01 09:00:00',
            'updated_at' => '2026-08-01 09:00:00',
        ]);
        $this->createOrderRecord($user, [
            'order_id' => 'SHP-NEEDLE-001',
            'platform' => 'shopee',
            'product_name' => 'Needle Shopee product',
            'created_at' => '2026-08-10 09:00:00',
            'updated_at' => '2026-08-10 09:00:00',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Orders Lazada Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/orders?status=pending&platform=lazada&search=Needle&start_date=2026-08-10&end_date=2026-08-10')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $matchingId)
            ->assertJsonPath('data.items.0.platform', 'lazada')
            ->assertJsonPath('data.items.0.record_type', 'order');
    }

    public function test_account_preferences_normalize_and_persist_active_codes_without_changing_wallet_currency(): void
    {
        $user = $this->createUser();
        [$plainToken] = ApiToken::generateFor($user, 'Preference Persistence Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/preferences', [
                'locale' => ' en ',
                'currency' => 'usd',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.currency', 'USD');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'en',
            'currency' => 'USD',
        ]);

        $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/account')
            ->assertOk()
            ->assertJsonPath('data.preferences.locale', 'en')
            ->assertJsonPath('data.preferences.currency', 'USD')
            ->assertJsonPath('data.wallet.currency', 'VND');
    }

    public function test_account_preferences_partial_update_preserves_the_omitted_preference(): void
    {
        $user = $this->createUser([
            'locale' => 'en',
            'currency' => 'USD',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Preference Partial Update Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/preferences', ['locale' => 'vi'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'vi')
            ->assertJsonPath('data.currency', 'USD');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'vi',
            'currency' => 'USD',
        ]);
    }

    public function test_account_preferences_fall_back_when_stored_codes_are_inactive(): void
    {
        $user = $this->createUser([
            'locale' => 'en',
            'currency' => 'USD',
        ]);
        DB::table('languages')->where('code', 'en')->update(['is_active' => false]);
        DB::table('currencies')->where('code', 'USD')->update(['is_active' => false]);
        [$plainToken] = ApiToken::generateFor($user, 'Preference Fallback Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->getJson('/api/v1/openapi/account')
            ->assertOk()
            ->assertJsonPath('data.preferences.locale', 'vi')
            ->assertJsonPath('data.preferences.currency', 'VND');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_account_preferences_reject_empty_unknown_and_inactive_values_without_mutation(): void
    {
        $user = $this->createUser([
            'locale' => 'vi',
            'currency' => 'VND',
        ]);
        DB::table('languages')->where('code', 'en')->update(['is_active' => false]);
        DB::table('currencies')->where('code', 'USD')->update(['is_active' => false]);
        [$plainToken] = ApiToken::generateFor($user, 'Preference Validation Test', 30, '127.0.0.1');

        foreach ([
            [[], 'locale'],
            [['locale' => 'unknown'], 'locale'],
            [['locale' => 'en'], 'locale'],
            [['currency' => 'unknown'], 'currency'],
            [['currency' => 'usd'], 'currency'],
        ] as [$payload, $errorField]) {
            $this->withToken($plainToken)
                ->postJson('/api/v1/openapi/account/preferences', $payload)
                ->assertUnprocessable()
                ->assertJsonPath('code', 'VALIDATION_ERROR')
                ->assertJsonStructure(['errors' => [$errorField]]);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'locale' => 'vi',
                'currency' => 'VND',
            ]);
        }
    }

    public function test_account_preferences_require_authentication_and_respect_the_profile_feature_flag(): void
    {
        $this->postJson('/api/v1/openapi/account/preferences', ['locale' => 'vi'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $user = $this->createUser();
        [$plainToken] = ApiToken::generateFor($user, 'Preference Feature Flag Test', 30, '127.0.0.1');
        Setting::setVal('openapi_profile_status', '0');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/preferences', ['locale' => 'vi'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ENDPOINT_DISABLED');

        $this->assertNull($user->fresh()->locale);
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

    public function test_account_deletion_feature_flag_still_blocks_the_mutation(): void
    {
        $user = $this->createUser();
        [$plainToken] = ApiToken::generateFor($user, 'Deletion Flag Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['password' => 'correct-password'])
            ->assertForbidden()
            ->assertJsonPath('code', 'FEATURE_DISABLED');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_oauth_link_does_not_bypass_password_for_an_account_with_a_local_password(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        $user = $this->createUser(['google_id' => 'linked-google-identity']);
        [$plainToken] = ApiToken::generateFor($user, 'Linked OAuth Deletion Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['confirmation' => 'DELETE'])
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_OR_PROVIDER_REAUTHENTICATION_REQUIRED');

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['password' => 'correct-password'])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_passwordless_account_deletion_requires_server_verified_provider_reauthentication(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        $user = $this->createUser([
            'password' => null,
            'google_id' => 'passwordless-google-identity',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Passwordless Deletion Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['confirmation' => 'DELETE'])
            ->assertForbidden()
            ->assertJsonPath('code', 'PROVIDER_REAUTHENTICATION_REQUIRED');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_google_reauthentication_deletes_a_linked_provider_account(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $user = $this->createUser([
            'google_id' => 'google-subject-123',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Google Deletion Test', 30, '127.0.0.1');
        [$idToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'google-subject-123',
            'email' => $user->email,
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ]);
        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks),
        ]);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['google_id_token' => $idToken])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $apiLog = ApiLog::query()->latest('id')->firstOrFail();
        $this->assertSame(
            '[REDACTED]',
            data_get(json_decode($apiLog->request_data, true, flags: JSON_THROW_ON_ERROR), 'google_id_token')
        );
        $this->assertStringNotContainsString($idToken, (string) $apiLog->request_data);
    }

    public function test_google_reauthentication_cannot_delete_a_different_linked_identity(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $user = $this->createUser([
            'password' => null,
            'google_id' => 'expected-google-subject',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Google Mismatch Test', 30, '127.0.0.1');
        [$idToken, $jwks] = $this->signedProviderToken([
            'iss' => 'accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'different-google-subject',
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ]);
        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks),
        ]);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['google_id_token' => $idToken])
            ->assertForbidden()
            ->assertJsonPath('code', 'OAUTH_IDENTITY_MISMATCH');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_password_confirmation_cannot_delete_an_apple_linked_account_without_fresh_apple_reauthentication(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        $user = $this->createUser([
            'apple_id' => 'apple-password-bypass-subject',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Apple Password Bypass Test', 30, '127.0.0.1');

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['password' => 'correct-password'])
            ->assertForbidden()
            ->assertJsonPath('code', 'APPLE_REAUTH_REQUIRED_FOR_DELETION');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_native_oauth_feature_flags_are_default_off(): void
    {
        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => 'not-evaluated'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ENDPOINT_DISABLED');

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => 'not-evaluated',
            'authorization_code' => 'not-evaluated',
            'nonce' => str_repeat('n', 16),
        ])->assertForbidden()
            ->assertJsonPath('code', 'ENDPOINT_DISABLED');
    }

    public function test_google_native_exchange_returns_the_canonical_token_for_an_existing_subject(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $user = $this->createUser(['google_id' => 'existing-google-subject']);
        [$idToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'existing-google-subject',
            'email' => $user->email,
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ]);
        Http::fake(['https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks)]);

        $response = $this->postJson('/api/v1/openapi/auth/oauth/google', [
            'id_token' => $idToken,
            'device_name' => 'Native Google Test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.google_id');
        $this->assertSame($response->json('data.access_token'), $response->json('data.token'));
    }

    public function test_google_native_exchange_never_auto_merges_an_email_only_match(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $user = $this->createUser();
        [$idToken, $jwks] = $this->signedProviderToken([
            'iss' => 'accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'unlinked-google-subject',
            'email' => $user->email,
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ]);
        Http::fake(['https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks)]);

        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => $idToken])
            ->assertConflict()
            ->assertJsonPath('code', 'ACCOUNT_LINK_REQUIRED');

        $this->assertNull($user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_google_native_exchange_fails_closed_for_duplicate_provider_subjects(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $this->createUser([
            'email' => 'duplicate-google-one@example.test',
            'referral_code' => 'REFGOOG1',
            'google_id' => 'duplicate-google-subject',
        ]);
        $this->createUser([
            'email' => 'duplicate-google-two@example.test',
            'referral_code' => 'REFGOOG2',
            'google_id' => 'duplicate-google-subject',
        ]);
        [$idToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'duplicate-google-subject',
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ]);
        Http::fake(['https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks)]);

        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => $idToken])
            ->assertConflict()
            ->assertJsonPath('code', 'OAUTH_IDENTITY_AMBIGUOUS');
        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_google_native_exchange_refreshes_a_stale_jwks_once_for_a_rotated_key(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $user = $this->createUser(['google_id' => 'rotated-google-subject']);
        [, $staleJwks] = $this->signedProviderToken([], 'stale-key');
        [$idToken, $freshJwks] = $this->signedProviderToken([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'rotated-google-subject',
            'email' => $user->email,
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'rotated-key');
        Cache::put('oauth_jwks:google', $staleJwks, now()->addHours(6));
        Http::fake(['https://www.googleapis.com/oauth2/v3/certs' => Http::response($freshJwks)]);

        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => $idToken])->assertOk();

        Http::assertSentCount(1);
    }

    public function test_google_native_exchange_rejects_invalid_issuer_audience_and_expiry(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        $baseClaims = [
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'strict-google-subject',
            'email' => 'strict-google@example.test',
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ];
        $invalidClaims = [
            array_merge($baseClaims, ['iss' => 'https://untrusted.example.test']),
            array_merge($baseClaims, ['aud' => 'different-client.test']),
            array_merge($baseClaims, ['exp' => now()->subMinute()->timestamp]),
        ];

        foreach ($invalidClaims as $index => $claims) {
            Cache::forget('oauth_jwks:google');
            [$idToken, $jwks] = $this->signedProviderToken($claims, "strict-google-key-{$index}");
            Http::fake(['https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks)]);

            $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => $idToken])
                ->assertUnprocessable()
                ->assertJsonPath('code', 'OAUTH_CREDENTIAL_INVALID');
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_native_oauth_registration_respects_the_existing_registration_flag(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        Setting::setVal('registration_enabled', '0');
        [$idToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'registration-disabled-subject',
            'email' => 'registration-disabled@example.test',
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'registration-disabled-key');
        Http::fake(['https://www.googleapis.com/oauth2/v3/certs' => Http::response($jwks)]);

        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => $idToken])
            ->assertForbidden()
            ->assertJsonPath('code', 'REGISTRATION_DISABLED');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_native_exchange_maps_jwks_connection_failure_to_retryable_503(): void
    {
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        [$idToken] = $this->signedProviderToken([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-mobile-client.test',
            'sub' => 'google-provider-outage-subject',
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'google-provider-outage-key');
        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => fn () => throw new ConnectionException('JWKS timeout'),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => $idToken])
            ->assertStatus(503)
            ->assertJsonPath('code', 'OAUTH_PROVIDER_UNAVAILABLE');
    }

    public function test_apple_native_exchange_maps_transient_token_provider_failure_to_retryable_503(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $nonce = 'apple-provider-outage-nonce-12345';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-provider-outage-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-provider-outage-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response(['error' => 'temporarily_unavailable'], 429),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => 'apple-provider-outage-code',
            'nonce' => $nonce,
        ])->assertStatus(503)
            ->assertJsonPath('code', 'OAUTH_PROVIDER_UNAVAILABLE');
    }

    public function test_apple_native_exchange_supports_private_relay_and_rejects_replay(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $nonce = 'apple-native-nonce-123456789';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-private-relay-subject',
            'email' => 'relay@privaterelay.appleid.com',
            'email_verified' => 'true',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-test-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response(['id_token' => $identityToken]),
        ]);

        $payload = [
            'identity_token' => $identityToken,
            'authorization_code' => 'apple-one-time-code',
            'nonce' => $nonce,
            'device_name' => 'Native Apple Test',
        ];
        $this->postJson('/api/v1/openapi/auth/oauth/apple', $payload)
            ->assertOk()
            ->assertJsonPath('data.user.email', 'relay@privaterelay.appleid.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->postJson('/api/v1/openapi/auth/oauth/apple', $payload)
            ->assertConflict()
            ->assertJsonPath('code', 'OAUTH_CREDENTIAL_REPLAYED');

        $this->assertDatabaseHas('users', [
            'apple_id' => 'apple-private-relay-subject',
            'email' => 'relay@privaterelay.appleid.com',
        ]);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://appleid.apple.com/auth/token'
            && $request['client_id'] === 'apple-services.test'
            && $request['code'] === 'apple-one-time-code'
            && $request['grant_type'] === 'authorization_code'
            && $request['redirect_uri'] === 'https://mobile.example.test/auth/apple/callback'
            && count(explode('.', (string) $request['client_secret'])) === 3);
    }

    public function test_apple_native_exchange_supports_a_missing_email_without_duplicate_accounts(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $nonce = 'apple-null-email-nonce-12345';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-null-email-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-null-email-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response(['id_token' => $identityToken]),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => 'apple-null-email-code',
            'nonce' => $nonce,
        ])->assertOk()
            ->assertJsonPath('data.user.email', null);

        $this->assertDatabaseHas('users', ['apple_id' => 'apple-null-email-subject', 'email' => null]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_apple_native_exchange_never_auto_merges_an_email_only_match(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $user = $this->createUser();
        $nonce = 'apple-email-collision-nonce-123';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'unlinked-apple-subject',
            'email' => $user->email,
            'email_verified' => true,
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-email-collision-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response(['id_token' => $identityToken]),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => 'apple-email-collision-code',
            'nonce' => $nonce,
        ])->assertConflict()
            ->assertJsonPath('code', 'ACCOUNT_LINK_REQUIRED');

        $this->assertNull($user->fresh()->apple_id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_apple_native_exchange_rejects_nonce_mismatch_and_redacts_reauth_fields(): void
    {
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-invalid-nonce-subject',
            'nonce' => hash('sha256', 'the-correct-apple-nonce'),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-invalid-nonce-key');
        Http::fake(['https://appleid.apple.com/auth/keys' => Http::response($jwks)]);

        $authorizationCode = 'apple-sensitive-authorization-code';
        $nonce = 'different-apple-nonce-12345';
        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => $authorizationCode,
            'nonce' => $nonce,
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'OAUTH_CREDENTIAL_INVALID');

        $apiLog = ApiLog::query()->latest('id')->firstOrFail();
        $logged = json_decode($apiLog->request_data, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('[REDACTED]', $logged['identity_token']);
        $this->assertSame('[REDACTED]', $logged['authorization_code']);
        $this->assertSame('[REDACTED]', $logged['nonce']);
        $this->assertStringNotContainsString($authorizationCode, (string) $apiLog->request_data);
        $this->assertStringNotContainsString($nonce, (string) $apiLog->request_data);
    }

    public function test_apple_native_exchange_fails_closed_when_server_exchange_config_is_missing(): void
    {
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        config()->set([
            'services.apple.team_id' => null,
            'services.apple.key_id' => null,
            'services.apple.private_key' => null,
            'services.apple.private_key_path' => null,
        ]);
        $nonce = 'apple-missing-config-nonce-123';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-missing-config-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-missing-config-key');
        Http::fake(['https://appleid.apple.com/auth/keys' => Http::response($jwks)]);

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => 'apple-missing-config-code',
            'nonce' => $nonce,
        ])->assertStatus(503)
            ->assertJsonPath('code', 'OAUTH_PROVIDER_UNAVAILABLE');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_apple_native_exchange_requires_the_exchanged_subject_to_match(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $nonce = 'apple-subject-match-nonce-1234';
        [$identityToken, $identityJwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-original-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-original-key');
        [$exchangedToken, $exchangeJwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-different-subject',
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-exchange-key');
        $jwks = ['keys' => array_merge($identityJwks['keys'], $exchangeJwks['keys'])];
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response(['id_token' => $exchangedToken]),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => 'apple-subject-mismatch-code',
            'nonce' => $nonce,
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'OAUTH_CREDENTIAL_INVALID');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_apple_native_exchange_fails_closed_for_duplicate_provider_subjects(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $this->createUser([
            'email' => 'duplicate-apple-one@example.test',
            'referral_code' => 'REFAPPL1',
            'apple_id' => 'duplicate-apple-subject',
        ]);
        $this->createUser([
            'email' => 'duplicate-apple-two@example.test',
            'referral_code' => 'REFAPPL2',
            'apple_id' => 'duplicate-apple-subject',
        ]);
        $nonce = 'duplicate-apple-nonce-12345';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'duplicate-apple-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'duplicate-apple-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response(['id_token' => $identityToken]),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'authorization_code' => 'duplicate-apple-code',
            'nonce' => $nonce,
        ])->assertConflict()
            ->assertJsonPath('code', 'OAUTH_IDENTITY_AMBIGUOUS');
        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_apple_reauthentication_deletes_the_linked_account(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('allow_self_delete_account', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $user = $this->createUser(['apple_id' => 'apple-deletion-subject']);
        [$plainToken] = ApiToken::generateFor($user, 'Apple Deletion Test', 30, '127.0.0.1');
        $nonce = 'apple-deletion-nonce-123456';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-deletion-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-deletion-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response([
                'id_token' => $identityToken,
                'refresh_token' => 'apple-deletion-refresh-token',
            ]),
            'https://appleid.apple.com/auth/revoke' => Http::response([], 200),
        ]);

        $this->withToken($plainToken)->postJson('/api/v1/openapi/account/delete', [
            'apple_identity_token' => $identityToken,
            'apple_authorization_code' => 'apple-deletion-code',
            'apple_nonce' => $nonce,
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://appleid.apple.com/auth/revoke'
            && $request['token'] === 'apple-deletion-refresh-token'
            && $request['token_type_hint'] === 'refresh_token'
            && $request['client_id'] === 'apple-services.test');
    }

    public function test_apple_reauthentication_does_not_delete_when_provider_disconnect_fails(): void
    {
        $this->configureAppleExchange();
        Setting::setVal('allow_self_delete_account', '1');
        Setting::setVal('apple_services_id', 'apple-services.test');
        $user = $this->createUser(['apple_id' => 'apple-disconnect-outage-subject']);
        [$plainToken] = ApiToken::generateFor($user, 'Apple Disconnect Outage Test', 30, '127.0.0.1');
        $nonce = 'apple-disconnect-outage-nonce-123456';
        [$identityToken, $jwks] = $this->signedProviderToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'apple-services.test',
            'sub' => 'apple-disconnect-outage-subject',
            'nonce' => hash('sha256', $nonce),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], 'apple-disconnect-outage-key');
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response($jwks),
            'https://appleid.apple.com/auth/token' => Http::response([
                'id_token' => $identityToken,
                'refresh_token' => 'apple-disconnect-outage-refresh-token',
            ]),
            'https://appleid.apple.com/auth/revoke' => Http::response(['error' => 'temporarily_unavailable'], 503),
        ]);

        $this->withToken($plainToken)->postJson('/api/v1/openapi/account/delete', [
            'apple_identity_token' => $identityToken,
            'apple_authorization_code' => 'apple-disconnect-outage-code',
            'apple_nonce' => $nonce,
        ])->assertStatus(503)
            ->assertJsonPath('code', 'APPLE_PROVIDER_DISCONNECT_FAILED');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_account_deletion_removes_related_auth_data_without_logging_raw_email_or_balance(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        $user = $this->createUser([
            'email' => 'privacy-deletion@example.test',
            'balance' => '98765.00',
        ]);
        [$plainToken] = ApiToken::generateFor($user, 'Privacy Deletion Test', 30, '127.0.0.1');
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'hashed-reset-token',
            'created_at' => now(),
        ]);
        DB::table('sessions')->insert([
            'id' => 'deletion-session',
            'user_id' => $user->id,
        ]);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['password' => 'correct-password'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);

        $auditLog = ActivityLog::query()->latest('id')->firstOrFail();
        $this->assertNull($auditLog->user_id);
        $this->assertStringNotContainsString($user->email, $auditLog->activity);
        $this->assertStringNotContainsString('98765', $auditLog->activity);

        $apiLog = ApiLog::query()->latest('id')->firstOrFail();
        $this->assertNull($apiLog->user_id);
        $this->assertSame('/api/v1/openapi/account/delete', $apiLog->endpoint);
        $this->assertSame(
            '[REDACTED]',
            data_get(json_decode($apiLog->request_data, true, flags: JSON_THROW_ON_ERROR), 'password')
        );
        $this->assertStringNotContainsString('correct-password', $apiLog->request_data);
    }

    public function test_audit_log_failure_after_committed_deletion_does_not_change_success_to_http_500(): void
    {
        Setting::setVal('allow_self_delete_account', '1');
        $user = $this->createUser(['email' => 'audit-outage@example.test']);
        [$plainToken] = ApiToken::generateFor($user, 'Audit Outage Test', 30, '127.0.0.1');

        DB::statement(<<<'SQL'
            CREATE TRIGGER reject_account_deletion_audit
            BEFORE INSERT ON activity_logs
            BEGIN
                SELECT RAISE(ABORT, 'audit sink unavailable');
            END
            SQL);

        $this->withToken($plainToken)
            ->postJson('/api/v1/openapi/account/delete', ['password' => 'correct-password'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    private function enableOpenApiAuth(): void
    {
        foreach ([
            'openapi_status' => '1',
            'openapi_auth_status' => '1',
            'openapi_orders_status' => '1',
            'registration_enabled' => '1',
            'email_verification_enabled' => '0',
            'referral_enabled' => '0',
            'smtp_status' => '0',
            'ip_register_limit' => '5',
        ] as $key => $value) {
            Setting::setVal($key, $value);
        }
    }

    private function createOrderRecord(User $user, array $attributes = []): int
    {
        return DB::table('cashback_histories')->insertGetId(array_merge([
            'user_id' => $user->id,
            'order_id' => 'ORDER-'.strtoupper(bin2hex(random_bytes(4))),
            'trans_id' => null,
            'platform' => 'shopee',
            'product_name' => 'Recorded cashback order',
            'product_image' => null,
            'original_price' => '100000.00',
            'cashback_amount' => '5000.00',
            'cashback_rate' => '5.00',
            'commission_amount' => '10000.00',
            'affiliate_url' => 'https://mesale.vn/recorded-order',
            'status' => 'pending',
            'rejected_reason' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function createClickRecord(User $user, array $attributes = []): int
    {
        return DB::table('cashback_clicks')->insertGetId(array_merge([
            'user_id' => $user->id,
            'trans_id' => 'CLICK-'.strtoupper(bin2hex(random_bytes(4))),
            'platform' => 'shopee',
            'product_name' => 'Unrecorded cashback click',
            'product_image' => null,
            'original_price' => '100000.00',
            'cashback_amount' => '5000.00',
            'cashback_rate' => '5.00',
            'commission_amount' => '10000.00',
            'affiliate_url' => 'https://mesale.vn/unrecorded-click',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
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

    private function signedProviderToken(array $claims, string $keyId = 'test-oauth-key'): array
    {
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $portableConfig = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
        if (is_file($portableConfig)) {
            $options['config'] = $portableConfig;
        }

        $key = openssl_pkey_new($options);
        $this->assertNotFalse($key);

        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($key, $privateKey, null, $options));
        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);

        $jwks = ['keys' => [[
            'kty' => 'RSA',
            'kid' => $keyId,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
            'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
        ]]];

        return [JWT::encode($claims, $privateKey, 'RS256', $keyId), $jwks];
    }

    private function configureAppleExchange(): void
    {
        $options = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];
        $portableConfig = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
        if (is_file($portableConfig)) {
            $options['config'] = $portableConfig;
        }

        $key = openssl_pkey_new($options);
        $this->assertNotFalse($key);
        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($key, $privateKey, null, $options));

        config()->set([
            'services.apple.team_id' => 'TESTTEAM123',
            'services.apple.key_id' => 'TESTKEY123',
            'services.apple.private_key' => $privateKey,
            'services.apple.private_key_path' => null,
            'services.apple.redirect_uri' => 'https://mobile.example.test/auth/apple/callback',
            'services.apple.token_url' => 'https://appleid.apple.com/auth/token',
            'services.apple.revoke_url' => 'https://appleid.apple.com/auth/revoke',
        ]);
    }

    private function createIsolatedAuthSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('locale', 10)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_cashback', 15, 2)->default(0);
            $table->decimal('total_referral_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->timestamp('referral_prompt_decided_at')->nullable();
            $table->unsignedInteger('referral_clicks')->default(0);
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->text('google2fa_secret')->nullable();
            $table->boolean('google2fa_enabled')->default(false);
            $table->boolean('email_otp_enabled')->default(false);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('google_id')->nullable();
            $table->string('apple_id')->nullable();
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

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('flag')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 15, 4)->default(1);
            $table->string('symbol_position')->default('after');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::table('languages')->insert([
            [
                'name' => 'Tiếng Việt',
                'code' => 'vi',
                'is_active' => true,
                'is_default' => true,
                'order' => 0,
            ],
            [
                'name' => 'English',
                'code' => 'en',
                'is_active' => true,
                'is_default' => false,
                'order' => 1,
            ],
        ]);

        DB::table('currencies')->insert([
            [
                'name' => 'Vietnamese Dong',
                'code' => 'VND',
                'symbol' => 'VND',
                'exchange_rate' => 1,
                'symbol_position' => 'after',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 25000,
                'symbol_position' => 'before',
                'is_active' => true,
                'is_default' => false,
            ],
        ]);

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
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('activity');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->longText('payload')->nullable();
        });

        Schema::create('cashback_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('order_id')->nullable();
            $table->string('trans_id')->nullable();
            $table->string('platform')->nullable();
            $table->text('product_name')->nullable();
            $table->text('product_image')->nullable();
            $table->decimal('original_price', 15, 2)->default(0);
            $table->decimal('cashback_amount', 15, 2)->default(0);
            $table->decimal('cashback_rate', 5, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->text('affiliate_url')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejected_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cashback_clicks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('trans_id')->unique();
            $table->string('platform')->nullable();
            $table->text('product_name')->nullable();
            $table->text('product_image')->nullable();
            $table->decimal('original_price', 15, 2)->default(0);
            $table->decimal('cashback_amount', 15, 2)->default(0);
            $table->decimal('cashback_rate', 5, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->text('affiliate_url')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
        });

        Schema::create('referrals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->unsignedBigInteger('referred_id')->unique();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
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
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
}

<?php

namespace Tests\Feature\OpenApi;

use App\Models\ApiLog;
use App\Models\ApiToken;
use App\Models\BalanceLog;
use App\Models\CashbackHistory;
use App\Models\Gift;
use App\Models\GiftRedemption;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FinancialIdempotencyContractTest extends TestCase
{
    private User $user;

    private string $plainToken;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createIsolatedFinancialSchema();
        $this->enableFinancialOpenApi();
        $this->user = $this->createUser();
        [$this->plainToken] = ApiToken::generateFor($this->user, 'Financial Contract Test', 30, '127.0.0.1');
    }

    public function test_financial_mutations_require_an_idempotency_key_before_any_business_side_effect(): void
    {
        $this->postWithdrawal(null)
            ->assertStatus(400)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');

        $gift = $this->createGift();
        $this->postGiftRedemption($gift, null)
            ->assertStatus(400)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->assertSame(100000, (int) $this->user->fresh()->balance);
        $this->assertDatabaseCount('withdrawals', 0);
        $this->assertDatabaseCount('gift_redemptions', 0);
        $this->assertDatabaseCount('balance_logs', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
        $this->assertSame(3, $gift->fresh()->stock);
    }

    public function test_invalid_idempotency_key_is_rejected_without_side_effects(): void
    {
        $this->postWithdrawal('invalid key')
            ->assertStatus(422)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_INVALID');

        $this->assertSame(100000, (int) $this->user->fresh()->balance);
        $this->assertDatabaseCount('withdrawals', 0);
        $this->assertDatabaseCount('balance_logs', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_withdrawal_replay_returns_the_same_resource_without_repeating_side_effects(): void
    {
        $key = 'withdrawal-replay-0001';

        $first = $this->postWithdrawal($key);
        $first->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.idempotent_replay', false);

        $replay = $this->postWithdrawal($key);
        $replay->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('data.code', $first->json('data.code'));

        $this->assertIsInt($first->json('data.amount'));
        $this->assertIsInt($first->json('data.fee'));
        $this->assertIsInt($first->json('data.real_amount'));
        $this->assertSame(75000, (int) $this->user->fresh()->balance);
        $this->assertDatabaseCount('withdrawals', 1);
        $this->assertDatabaseCount('balance_logs', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);

        $apiLog = ApiLog::query()->latest('id')->firstOrFail();
        $loggedRequest = json_decode((string) $apiLog->request_data, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('[REDACTED]', $loggedRequest['account_number']);
        $this->assertStringNotContainsString('0123456789', (string) $apiLog->request_data);
    }

    public function test_withdrawal_reusing_a_key_with_a_different_payload_returns_conflict(): void
    {
        $key = 'withdrawal-conflict-0001';
        $first = $this->postWithdrawal($key);
        $first->assertCreated();

        $this->postWithdrawal($key, ['amount' => 26000])
            ->assertStatus(409)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');

        $this->assertSame(75000, (int) $this->user->fresh()->balance);
        $this->assertDatabaseCount('withdrawals', 1);
        $this->assertDatabaseCount('balance_logs', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_gift_replay_returns_the_same_redemption_without_repeating_side_effects(): void
    {
        $gift = $this->createGift();
        $key = 'gift-replay-0001';

        $first = $this->postGiftRedemption($gift, $key);
        $first->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.idempotent_replay', false);

        $replay = $this->postGiftRedemption($gift, $key);
        $replay->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.code', $first->json('data.code'));

        $this->assertIsInt($first->json('data.amount'));
        $this->assertSame(75000, (int) $this->user->fresh()->balance);
        $this->assertSame(2, $gift->fresh()->stock);
        $this->assertDatabaseCount('gift_redemptions', 1);
        $this->assertDatabaseCount('balance_logs', 1);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_gift_reusing_a_key_with_a_different_payload_returns_conflict(): void
    {
        $gift = $this->createGift();
        $key = 'gift-conflict-0001';
        $this->postGiftRedemption($gift, $key)->assertOk();

        $this->postGiftRedemption($gift, $key, ['notes' => 'different payload'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');

        $this->assertSame(75000, (int) $this->user->fresh()->balance);
        $this->assertSame(2, $gift->fresh()->stock);
        $this->assertDatabaseCount('gift_redemptions', 1);
        $this->assertDatabaseCount('balance_logs', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_otp_lock_cleanup_survives_the_rolled_back_idempotent_transaction(): void
    {
        Setting::setVal('withdraw_otp_required', '1');
        $this->user->forceFill([
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $attemptKey = 'api-otp-verify:'.$this->user->id;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            RateLimiter::hit($attemptKey, 600);
        }

        $this->postWithdrawal('withdrawal-otp-lock-0001', ['otp_code' => '123456'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'OTP_LOCKED');

        $freshUser = $this->user->fresh();
        $this->assertNull($freshUser->otp_code);
        $this->assertNull($freshUser->otp_expires_at);
        $this->assertSame(0, RateLimiter::attempts($attemptKey));
        $this->assertSame(100000, (int) $freshUser->balance);
        $this->assertDatabaseCount('withdrawals', 0);
        $this->assertDatabaseCount('balance_logs', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_database_unique_constraint_rejects_duplicate_user_operation_and_key_hash(): void
    {
        $row = [
            'user_id' => $this->user->id,
            'operation' => 'withdrawal.create',
            'key_hash' => hash('sha256', 'duplicate-key-0001'),
            'request_hash' => hash('sha256', 'payload'),
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('idempotency_keys')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('idempotency_keys')->insert($row);
    }

    public function test_financial_get_endpoints_return_integer_vnd_and_preserve_decimal_rates(): void
    {
        BalanceLog::create([
            'user_id' => $this->user->id,
            'amount_before' => '100.49',
            'amount_change' => '0.02',
            'amount_after' => '100.51',
            'type' => 'fractional_fixture',
            'description' => 'Fractional legacy fixture',
        ]);

        Withdrawal::create([
            'user_id' => $this->user->id,
            'code' => 'WDRGET001',
            'amount' => '50001.00',
            'fee' => '1000.00',
            'real_amount' => '49001.00',
            'payment_method' => 'bank',
            'account_number' => '0123456789',
            'account_name' => 'MOBILE USER',
            'bank_name' => 'Vietcombank',
            'status' => 'pending',
        ]);

        $gift = $this->createGift(['price' => '25000.51']);
        GiftRedemption::create([
            'code' => 'GFTGET001',
            'user_id' => $this->user->id,
            'gift_id' => $gift->id,
            'amount' => '25000.51',
            'shipping_info' => [],
            'status' => 'pending',
        ]);

        $order = CashbackHistory::create([
            'user_id' => $this->user->id,
            'platform' => 'shopee',
            'order_id' => 'ORDER-GET-001',
            'product_name' => 'Money contract product',
            'original_price' => '100000.49',
            'commission_amount' => '1200.51',
            'cashback_amount' => '600.51',
            'cashback_rate' => '1.25',
            'status' => 'approved',
        ]);

        $balanceResponse = $this->getFinancial('/api/v1/openapi/balance-logs')->assertOk();
        $balanceItem = $balanceResponse->json('data.items.0');
        $this->assertIntegerFields($balanceItem, ['amount_before', 'amount_change', 'amount_after']);
        $this->assertSame(
            $balanceItem['amount_after'],
            $balanceItem['amount_before'] + $balanceItem['amount_change']
        );

        $withdrawalItem = $this->getFinancial('/api/v1/openapi/withdrawals')
            ->assertOk()
            ->json('data.items.0');
        $this->assertIntegerFields($withdrawalItem, ['amount', 'fee', 'real_amount']);

        $giftItem = $this->getFinancial('/api/v1/openapi/gifts')
            ->assertOk()
            ->json('data.items.0');
        $this->assertIntegerFields($giftItem, ['price']);

        $redemptionItem = $this->getFinancial('/api/v1/openapi/gifts/redemptions')
            ->assertOk()
            ->json('data.items.0');
        $this->assertIntegerFields($redemptionItem, ['amount']);

        $orderListItem = $this->getFinancial('/api/v1/openapi/orders')
            ->assertOk()
            ->json('data.items.0');
        $this->assertIntegerFields($orderListItem, ['original_price', 'commission_amount', 'cashback_amount']);
        $this->assertIsFloat($orderListItem['cashback_rate']);
        $this->assertSame(1.25, $orderListItem['cashback_rate']);

        $orderDetail = $this->getFinancial('/api/v1/openapi/orders/'.$order->id)
            ->assertOk()
            ->json('data');
        $this->assertIntegerFields($orderDetail, ['original_price', 'commission_amount', 'cashback_amount']);
        $this->assertIsFloat($orderDetail['cashback_rate']);
        $this->assertSame(1.25, $orderDetail['cashback_rate']);
    }

    private function postWithdrawal(?string $key, array $overrides = []): TestResponse
    {
        $request = $this->withToken($this->plainToken);
        if ($key !== null) {
            $request = $request->withHeader('Idempotency-Key', $key);
        }

        return $request->postJson('/api/v1/openapi/withdrawals', array_merge([
            'amount' => 25000,
            'payment_method' => 'bank',
            'account_number' => '0123456789',
            'account_name' => 'Mobile Contract User',
            'bank_name' => 'Vietcombank',
        ], $overrides));
    }

    private function postGiftRedemption(Gift $gift, ?string $key, array $overrides = []): TestResponse
    {
        $request = $this->withToken($this->plainToken);
        if ($key !== null) {
            $request = $request->withHeader('Idempotency-Key', $key);
        }

        return $request->postJson('/api/v1/openapi/gifts/redeem', array_merge([
            'gift_id' => $gift->id,
            'fullname' => 'Mobile Contract User',
            'phone' => '0900000000',
            'email' => 'financial-contract@example.test',
            'notes' => null,
        ], $overrides));
    }

    private function getFinancial(string $uri): TestResponse
    {
        return $this->withToken($this->plainToken)->getJson($uri);
    }

    private function assertIntegerFields(array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $payload);
            $this->assertIsInt($payload[$field], "{$field} must be an integer VND amount");
        }
    }

    private function createGift(array $attributes = []): Gift
    {
        return Gift::create(array_merge([
            'title' => 'Contract Gift',
            'description' => 'Gift used by the financial contract test.',
            'price' => '25000.00',
            'stock' => 3,
            'type' => 'voucher',
            'status' => true,
        ], $attributes));
    }

    private function createUser(): User
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Financial Contract User',
            'email' => 'financial-contract@example.test',
            'password' => Hash::make('correct-password'),
            'balance' => '100000.00',
            'total_cashback' => '100000.00',
            'total_referral_earned' => '0.00',
            'total_withdrawn' => '0.00',
            'referral_code' => 'REFFINANCIAL',
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function enableFinancialOpenApi(): void
    {
        foreach ([
            'openapi_status' => '1',
            'openapi_withdraw_status' => '1',
            'openapi_gifts_status' => '1',
            'openapi_balance_logs_status' => '1',
            'openapi_orders_status' => '1',
            'withdrawal_enabled' => '1',
            'withdraw_bank_enabled' => '1',
            'withdraw_wallet_enabled' => '1',
            'withdraw_otp_required' => '0',
            'withdraw_unique_account' => '0',
            'min_withdraw' => '10000',
            'withdrawal_fee_type' => 'fixed',
            'withdrawal_fee_value' => '1000',
            'allowed_banks' => 'Vietcombank,Techcombank',
            'allowed_wallets' => 'Momo,ZaloPay',
            'gift_redemption_enabled' => '1',
            'smtp_status' => '0',
            'telegram_status' => '0',
            'fcm_service_account' => '',
        ] as $key => $value) {
            Setting::setVal($key, $value);
        }
    }

    private function createIsolatedFinancialSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_cashback', 15, 2)->default(0);
            $table->decimal('total_referral_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('api_token', 64)->nullable()->unique();
            $table->timestamp('last_seen_at')->nullable();
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

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('symbol');
            $table->decimal('exchange_rate', 15, 4)->default(1);
            $table->string('symbol_position')->default('after');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        DB::table('currencies')->insert([
            'name' => 'Vietnam Dong',
            'code' => 'VND',
            'symbol' => 'VND',
            'exchange_rate' => 1,
            'symbol_position' => 'after',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('operation', 100);
            $table->char('key_hash', 64);
            $table->char('request_hash', 64);
            $table->string('status', 20)->default('processing');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'operation', 'key_hash'], 'idempotency_user_operation_key_unique');
        });

        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable()->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('real_amount', 15, 2)->default(0);
            $table->string('payment_method');
            $table->string('account_number');
            $table->string('account_name');
            $table->string('bank_name')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('gifts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->string('type');
            $table->string('tag')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('gift_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('gift_id');
            $table->decimal('amount', 15, 2);
            $table->text('shipping_info')->nullable();
            $table->string('status')->default('pending');
            $table->text('gift_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('gift_id')->references('id')->on('gifts')->cascadeOnDelete();
        });

        Schema::create('balance_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount_before', 15, 2);
            $table->decimal('amount_change', 15, 2);
            $table->decimal('amount_after', 15, 2);
            $table->string('type');
            $table->text('description')->nullable();
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

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('cashback_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('platform')->nullable();
            $table->string('order_id')->nullable()->unique();
            $table->string('trans_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('shop_name')->nullable();
            $table->string('product_image')->nullable();
            $table->decimal('original_price', 15, 2)->default(0);
            $table->decimal('cashback_amount', 15, 2)->default(0);
            $table->decimal('cashback_rate', 5, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->text('affiliate_url')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
}

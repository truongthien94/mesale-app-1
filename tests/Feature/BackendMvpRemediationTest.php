<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\CouponController as ApiCouponController;
use App\Http\Controllers\CouponController as WebCouponController;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use App\Models\ApiToken;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\AppleOAuthConfiguration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackendMvpRemediationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createIsolatedSchema();
    }

    public function test_blog_share_route_records_the_share_and_increments_the_counter(): void
    {
        $this->withoutMiddleware();

        $author = $this->createUser();
        $post = Post::create([
            'author_id' => $author->id,
            'title' => 'Mobile migration update',
            'slug' => 'mobile-migration-update',
            'content' => 'Test content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('blog.share', $post->id), [
            'platform' => 'facebook',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('shares', 1);

        $this->assertDatabaseHas('post_shares', [
            'post_id' => $post->id,
            'platform' => 'facebook',
        ]);
        $this->assertSame(1, $post->fresh()->share_count);
    }

    public function test_blog_share_rejects_unknown_platforms_and_throttles_spam(): void
    {
        $this->withoutMiddleware();

        $author = $this->createUser();
        $post = Post::create([
            'author_id' => $author->id,
            'title' => 'Share protection',
            'slug' => 'share-protection',
            'content' => 'Test content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->postJson(route('blog.share', $post->id), [
            'platform' => str_repeat('x', 128),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('platform');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson(route('blog.share', $post->id), ['platform' => 'copy'])->assertOk();
        }

        $this->postJson(route('blog.share', $post->id), ['platform' => 'copy'])
            ->assertStatus(429)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('post_shares', 20);
        $this->assertSame(20, $post->fresh()->share_count);
    }

    public function test_coupon_queries_support_all_platforms_and_explicit_platform_filters(): void
    {
        Coupon::create([
            'platform' => 'shopee',
            'code' => 'SHOP10',
            'title' => 'Shopee discount',
            'category' => 'fashion',
        ]);
        Coupon::create([
            'platform' => 'tiktok',
            'code' => 'TIKTOK10',
            'title' => 'TikTok discount',
            'category' => 'beauty',
        ]);
        Coupon::create([
            'platform' => 'lazada',
            'code' => 'LAZADA10',
            'title' => 'Lazada discount',
            'category' => 'electronics',
        ]);

        $webAll = (new WebCouponController)->index(Request::create('/coupons'));
        $this->assertEqualsCanonicalizing(
            ['shopee', 'tiktok', 'lazada'],
            $webAll->getData()['coupons']->pluck('platform')->all(),
        );

        $webLazada = (new WebCouponController)->index(Request::create('/coupons', 'GET', ['platform' => 'lazada']));
        $this->assertSame(['lazada'], $webLazada->getData()['coupons']->pluck('platform')->all());
        $this->assertSame(['electronics'], $webLazada->getData()['categories']);

        $apiAll = (new ApiCouponController)->index(Request::create('/api/v1/openapi/coupons'));
        $apiAllData = $apiAll->getData(true)['data'];
        $this->assertEqualsCanonicalizing(
            ['shopee', 'tiktok', 'lazada'],
            array_column($apiAllData['items'], 'platform'),
        );

        $apiLazada = (new ApiCouponController)->index(Request::create('/api/v1/openapi/coupons', 'GET', ['platform' => 'lazada']));
        $apiLazadaData = $apiLazada->getData(true)['data'];
        $this->assertSame(['lazada'], array_column($apiLazadaData['items'], 'platform'));
        $this->assertSame(['electronics'], $apiLazadaData['categories']);
    }

    public function test_config_exposes_lazada_cashback_settings(): void
    {
        Setting::setVal('lazada_status', '1');
        Setting::setVal('lazada_cashback_rate', '37.5');
        Setting::setVal('hp_cashback_notice_lazada', 'Lazada cashback notice');

        $response = (new ConfigController)->show(Request::create('/api/v1/openapi/config'));
        $cashback = $response->getData(true)['data']['cashback'];

        $this->assertTrue($cashback['lazada_enabled']);
        $this->assertSame(37.5, $cashback['lazada_rate']);
        $this->assertSame('Lazada cashback notice', $cashback['lazada_notice']);

        $features = $response->getData(true)['data']['features'];
        $this->assertFalse($features['api_auth_oauth_google']);
        $this->assertFalse($features['api_auth_oauth_apple']);

        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('openapi_auth_oauth_apple_status', '1');
        $this->mock(AppleOAuthConfiguration::class)
            ->shouldReceive('isReady')
            ->andReturnTrue();
        $enabledFeatures = (new ConfigController)->show(Request::create('/api/v1/openapi/config'))
            ->getData(true)['data']['features'];
        $this->assertTrue($enabledFeatures['api_auth_oauth_google']);
        $this->assertTrue($enabledFeatures['api_auth_oauth_apple']);
    }

    public function test_admin_suspension_revokes_all_mobile_api_tokens(): void
    {
        $this->withoutMiddleware([
            SetLocale::class,
            SetCurrency::class,
        ]);

        $admin = $this->createUser(['email' => 'admin@example.test', 'role' => 'admin']);
        $user = $this->createUser(['email' => 'suspend@example.test']);
        ApiToken::generateFor($user, 'Device one', 30, '127.0.0.1');
        ApiToken::generateFor($user, 'Device two', 30, '127.0.0.1');

        $response = $this->actingAs($admin)->post(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'user',
            'status' => 'suspended',
            'email_verified' => '1',
        ]);

        $response->assertRedirect(route('admin.users.edit', $user));
        $this->assertSame('suspended', $user->fresh()->status);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $user->id]);
    }

    public function test_admin_password_and_two_factor_resets_revoke_all_mobile_api_tokens(): void
    {
        $this->withoutMiddleware([
            SetLocale::class,
            SetCurrency::class,
        ]);

        $admin = $this->createUser(['email' => 'credential-admin@example.test', 'role' => 'admin']);
        $passwordUser = $this->createUser(['email' => 'password-reset@example.test']);
        ApiToken::generateFor($passwordUser, 'Password reset device', 30, '127.0.0.1');

        $this->actingAs($admin)->post(route('admin.users.update', $passwordUser), [
            'name' => $passwordUser->name,
            'email' => $passwordUser->email,
            'role' => 'user',
            'status' => 'active',
            'email_verified' => '1',
            'password' => 'new-secure-password',
        ])->assertRedirect(route('admin.users.edit', $passwordUser));

        $this->assertTrue(Hash::check('new-secure-password', $passwordUser->fresh()->password));
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $passwordUser->id]);

        $twoFactorUser = $this->createUser([
            'email' => 'two-factor-reset@example.test',
            'google2fa_enabled' => true,
            'google2fa_secret' => 'encrypted-test-secret',
        ]);
        ApiToken::generateFor($twoFactorUser, 'Two-factor reset device', 30, '127.0.0.1');

        $this->actingAs($admin)->post(route('admin.users.update', $twoFactorUser), [
            'name' => $twoFactorUser->name,
            'email' => $twoFactorUser->email,
            'role' => 'user',
            'status' => 'active',
            'email_verified' => '1',
            'reset_2fa' => '1',
        ])->assertRedirect(route('admin.users.edit', $twoFactorUser));

        $twoFactorUser->refresh();
        $this->assertFalse((bool) $twoFactorUser->google2fa_enabled);
        $this->assertNull($twoFactorUser->google2fa_secret);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $twoFactorUser->id]);
    }

    public function test_admin_single_and_bulk_deletion_revoke_mobile_api_tokens_without_relying_on_cascade(): void
    {
        $this->withoutMiddleware([
            SetLocale::class,
            SetCurrency::class,
        ]);

        $admin = $this->createUser(['email' => 'delete-admin@example.test', 'role' => 'admin']);
        $single = $this->createUser(['email' => 'single-delete@example.test']);
        [$singlePlainToken] = ApiToken::generateFor($single, 'Single delete device', 30, '127.0.0.1');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $single))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('api_tokens', ['token' => ApiToken::hashToken($singlePlainToken)]);

        $bulkOne = $this->createUser(['email' => 'bulk-one@example.test']);
        $bulkTwo = $this->createUser(['email' => 'bulk-two@example.test']);
        ApiToken::generateFor($bulkOne, 'Bulk device one', 30, '127.0.0.1');
        ApiToken::generateFor($bulkTwo, 'Bulk device two', 30, '127.0.0.1');

        $this->actingAs($admin)
            ->deleteJson(route('admin.users.bulk_destroy'), [
                'user_ids' => "{$bulkOne->id},{$bulkTwo->id}",
                'confirm_checkbox' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('api_tokens', ['user_id' => $bulkOne->id]);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $bulkTwo->id]);
    }

    private function createUser(array $attributes = []): User
    {
        $user = new User;
        $user->forceFill(array_merge([
            'name' => 'Backend Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('correct-password'),
            'status' => 'active',
            'role' => 'user',
            'email_verified_at' => now(),
        ], $attributes));
        $user->save();

        return $user;
    }

    private function createIsolatedSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('user');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('status')->default('active');
            $table->boolean('google2fa_enabled')->default(false);
            $table->text('google2fa_secret')->nullable();
            $table->boolean('email_otp_enabled')->default(false);
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

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_sticky')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_commentable')->default(true);
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->timestamps();
        });

        Schema::create('post_shares', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('platform');
            $table->string('ip_address', 45);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 50)->default('shopee');
            $table->string('code', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->integer('min_spend')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('discount_percentage')->default(0);
            $table->integer('clicks')->default(0);
            $table->dateTime('expired_at')->nullable();
            $table->text('redirect_link')->nullable();
            $table->string('source')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'code']);
        });

        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('image_url');
            $table->string('link')->nullable();
            $table->string('title')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}

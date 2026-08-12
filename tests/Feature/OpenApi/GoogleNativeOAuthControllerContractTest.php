<?php

namespace Tests\Feature\OpenApi;

use App\Http\Controllers\Api\V1\GoogleNativeOAuthController;
use App\Http\Middleware\ApiEndpointEnabled;
use App\Http\Middleware\LogApiRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\GoogleNativeOAuthTokenVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class GoogleNativeOAuthControllerContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ApiEndpointEnabled::class, LogApiRequest::class]);
        Setting::setVal('openapi_auth_oauth_google_status', '1');
        Setting::setVal('google_client_id', 'google-mobile-client.test');
        Setting::setVal('registration_enabled', '1');

        if (! Schema::hasColumn('users', 'referral_prompt_decided_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('referral_prompt_decided_at')->nullable();
                $table->timestamp('referral_code_eligible_until')->nullable();
            });
        }
    }

    public function test_route_uses_the_google_only_controller(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/openapi/auth/oauth/google'
        );

        $this->assertNotNull($route);
        $this->assertSame(GoogleNativeOAuthController::class, $route->getActionName());
    }

    public function test_google_only_verifier_is_bound_and_rejects_an_invalid_token(): void
    {
        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response(['keys' => []]),
        ]);

        $this->postJson('/api/v1/openapi/auth/oauth/google', ['id_token' => 'invalid-token'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'OAUTH_CREDENTIAL_INVALID');

        $this->assertInstanceOf(GoogleNativeOAuthTokenVerifier::class, app(GoogleNativeOAuthTokenVerifier::class));
    }

    public function test_email_only_match_still_fails_closed_without_linking(): void
    {
        $user = User::create([
            'name' => 'Existing Member',
            'email' => 'existing@example.test',
            'password' => Hash::make('correct-password'),
            'referral_code' => 'REF'.strtoupper(Str::random(6)),
            'status' => 'active',
        ]);

        $controller = app(GoogleNativeOAuthController::class);
        $method = new \ReflectionMethod($controller, 'resolveGoogleUser');
        $request = request()->create('/api/v1/openapi/auth/oauth/google', 'POST');
        $response = $method->invoke($controller, [
            'sub' => 'new-google-subject',
            'email' => $user->email,
            'email_verified' => true,
            'name' => 'Existing Member',
            'picture' => null,
        ], $request);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('ACCOUNT_LINK_REQUIRED', $response->getData(true)['code']);
        $this->assertNull($user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_verified_google_identity_creates_a_user_and_returns_a_canonical_bearer_session(): void
    {
        $verifier = Mockery::mock(GoogleNativeOAuthTokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturn([
            'sub' => 'google-subject-new-member',
            'email' => 'new-google-member@example.test',
            'email_verified' => true,
            'name' => 'Google Member',
            'picture' => null,
        ]);
        $this->app->instance(GoogleNativeOAuthTokenVerifier::class, $verifier);

        $response = $this->postJson('/api/v1/openapi/auth/oauth/google', [
            'id_token' => 'signed-google-token',
            'device_name' => 'Android test device',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'new-google-member@example.test')
            ->assertJsonPath('data.user.referral_prompt_pending', true)
            ->assertJsonMissingPath('data.user.google_id');

        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertSame($response->json('data.access_token'), $response->json('data.token'));

        $user = User::where('email', 'new-google-member@example.test')->firstOrFail();
        $this->assertSame('google-subject-new-member', $user->google_id);
        $this->assertNotNull($user->referral_code_eligible_until);
        $this->assertTrue($user->referral_code_eligible_until->between(now()->addHours(71), now()->addHours(73)));
    }
}

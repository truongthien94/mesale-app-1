<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\ConfigController;
use App\Models\Setting;
use App\Services\AppleOAuthConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AppleOAuthConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.apple.bundle_id', null);
        config()->set('services.apple.services_id', null);
        config()->set('services.apple.client_ids', []);
        config()->set('services.apple.team_id', null);
        config()->set('services.apple.key_id', null);
        config()->set('services.apple.private_key', null);
        config()->set('services.apple.private_key_path', null);
        config()->set('services.apple.redirect_uri', null);
    }

    public function test_incomplete_native_apple_configuration_is_not_ready(): void
    {
        $configuration = app(AppleOAuthConfiguration::class);

        $this->assertFalse($configuration->isReady());
        $this->assertNotEmpty($configuration->issues());
    }

    public function test_native_bundle_and_valid_apple_signing_key_are_ready(): void
    {
        $this->configureValidAppleOAuth();

        $configuration = app(AppleOAuthConfiguration::class);

        $this->assertTrue($configuration->isReady());
        $this->assertContains('vn.mesale.app', $configuration->audiences());
    }

    public function test_non_p256_ec_private_key_is_rejected(): void
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp384r1',
        ]);
        $this->assertNotFalse($key);
        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($key, $privateKey));

        config()->set('services.apple.bundle_id', 'vn.mesale.app');
        config()->set('services.apple.team_id', 'TEAMID1234');
        config()->set('services.apple.key_id', 'KEYID12345');
        config()->set('services.apple.private_key', $privateKey);

        $configuration = app(AppleOAuthConfiguration::class);

        $this->assertFalse($configuration->isReady());
        $this->assertContains(
            'Khóa Apple phải là private key EC P-256 hợp lệ từ file AuthKey_*.p8.',
            $configuration->issues()
        );
    }

    public function test_native_exchange_omits_the_web_redirect_uri(): void
    {
        $this->configureValidAppleOAuth();
        config()->set('services.apple.services_id', 'web.mesale.test');
        config()->set('services.apple.redirect_uri', 'https://mesale.test/auth/apple/callback');

        $configuration = app(AppleOAuthConfiguration::class);

        $this->assertSame('', $configuration->tokenExchangeRedirectUri('vn.mesale.app'));
        $this->assertSame(
            'https://mesale.test/auth/apple/callback',
            $configuration->tokenExchangeRedirectUri('web.mesale.test')
        );
    }

    public function test_preflight_command_requires_the_feature_flag_only_when_requested(): void
    {
        $this->configureValidAppleOAuth();

        $this->artisan('oauth:apple:check')->assertSuccessful();
        $this->artisan('oauth:apple:check', ['--require-enabled' => true])->assertExitCode(1);

        Setting::setVal('openapi_auth_oauth_apple_status', '1');

        $this->artisan('oauth:apple:check', ['--require-enabled' => true])->assertSuccessful();
    }

    public function test_enabled_endpoint_stays_unavailable_when_server_credentials_are_missing(): void
    {
        Setting::setVal('openapi_status', '1');
        Setting::setVal('openapi_auth_status', '1');
        Setting::setVal('openapi_auth_oauth_apple_status', '1');

        $this->postJson('/api/v1/openapi/auth/oauth/apple', [
            'identity_token' => 'not-evaluated',
            'authorization_code' => 'not-evaluated',
            'nonce' => str_repeat('n', 16),
        ])->assertServiceUnavailable()
            ->assertJsonPath('code', 'OAUTH_PROVIDER_UNAVAILABLE');
    }

    public function test_public_config_only_advertises_apple_when_credentials_are_ready(): void
    {
        Setting::setVal('openapi_auth_oauth_apple_status', '1');

        $features = (new ConfigController)->show(Request::create('/api/v1/openapi/config'))
            ->getData(true)['data']['features'];
        $this->assertFalse($features['api_auth_oauth_apple']);

        $this->configureValidAppleOAuth();

        $readyFeatures = (new ConfigController)->show(Request::create('/api/v1/openapi/config'))
            ->getData(true)['data']['features'];
        $this->assertTrue($readyFeatures['api_auth_oauth_apple']);
    }

    private function configureValidAppleOAuth(): void
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $this->assertNotFalse($key);
        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($key, $privateKey));

        config()->set('services.apple.bundle_id', 'vn.mesale.app');
        config()->set('services.apple.team_id', 'TEAMID1234');
        config()->set('services.apple.key_id', 'KEYID12345');
        config()->set('services.apple.private_key', $privateKey);
    }
}

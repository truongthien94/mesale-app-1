<?php

namespace Tests\Feature;

use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StoreCompliancePublicPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutMiddleware([SetLocale::class, SetCurrency::class]);
    }

    public function test_store_legal_support_and_deletion_pages_are_publicly_reachable(): void
    {
        $pages = [
            ['legal.privacy', 'Chính sách bảo mật', 'Dữ liệu được thu thập'],
            ['legal.terms', 'Điều khoản dịch vụ', 'Cashback, số dư và rút tiền'],
            ['support', 'Hỗ trợ và liên hệ', 'Thông tin cần cung cấp'],
            ['account-deletion', 'Hướng dẫn xóa tài khoản', 'Xóa trong ứng dụng'],
            ['account-deletion.legacy', 'Hướng dẫn xóa tài khoản', 'Xóa trong ứng dụng'],
        ];

        foreach ($pages as [$routeName, $title, $content]) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($title)
                ->assertSee($content);
        }

        $this->assertGuest();
    }

    public function test_support_page_uses_an_explicit_owner_placeholder_when_no_verified_contact_exists(): void
    {
        $this->get(route('support'))
            ->assertOk()
            ->assertSee('OWNER INPUT REQUIRED');
    }

    public function test_well_known_association_documents_are_public_json(): void
    {
        $apple = $this->get('/.well-known/apple-app-site-association');
        $apple->assertOk();
        $this->assertStringStartsWith('application/json', (string) $apple->headers->get('Content-Type'));
        $apple->assertJsonPath('applinks.details.0.appID', 'TEAMID.vn.mesale.app')
            ->assertJsonPath('applinks.details.0.paths.0', '/home');

        $android = $this->get('/.well-known/assetlinks.json');
        $android->assertOk();
        $this->assertStringStartsWith('application/json', (string) $android->headers->get('Content-Type'));
        $android->assertJsonPath('0.target.package_name', 'vn.mesale.app')
            ->assertJsonPath(
                '0.target.sha256_cert_fingerprints.0',
                '3E:C2:23:72:37:0E:47:7A:1C:F1:9C:93:74:41:5B:5E:96:96:FB:2F:89:73:19:AD:54:7C:9C:4B:F3:DE:B9:A7'
            );
    }

    public function test_android_app_link_uses_the_canonical_account_deletion_path(): void
    {
        $config = json_decode(
            (string) file_get_contents(base_path('mobile/app.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $paths = collect($config['expo']['android']['intentFilters'])
            ->flatMap(static fn (array $filter): array => $filter['data'] ?? [])
            ->where('host', 'mesale.vn')
            ->pluck('pathPrefix');

        $this->assertTrue($paths->contains('/account-deletion'));
        $this->assertFalse($paths->contains('/account/delete'));
        $this->assertSame(
            'https://mesale.vn/account-deletion',
            $config['expo']['extra']['storeUrls']['accountDeletion']
        );
    }
}

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Page;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Console Command: sitemap:generate
| Tự động quét toàn bộ hệ thống để sinh tệp sitemap.xml hỗ trợ Google Search Console SEO.
|--------------------------------------------------------------------------
*/
Artisan::command('sitemap:generate', function () {
    $this->info('Đang bắt đầu quét các trang để tạo sitemap.xml...');

    // Khởi tạo đối tượng Sitemap từ package spatie/laravel-sitemap
    $sitemap = Sitemap::create();

    // 1. Thêm trang chủ (Home)
    $sitemap->add(Url::create(route('home'))
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        ->setPriority(1.0));

    // 1b. Thêm trang Săn Mã Giảm Giá Shopee (Coupons)
    $sitemap->add(Url::create(route('coupons.index'))
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        ->setPriority(0.9));

    // 1c. Thêm trang Hướng dẫn sử dụng Bot Hoàn Tiền (Bot Guide)
    $sitemap->add(Url::create(route('bot.guide'))
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
        ->setPriority(0.8));

    // 2. Thêm trang danh sách blog chính
    $sitemap->add(Url::create(route('blog.index'))
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        ->setPriority(0.8));

    // 3. Thêm các bài viết đã xuất bản công khai
    Post::published()->get()->each(function (Post $post) use ($sitemap) {
        $sitemap->add(Url::create(route('blog.show', $post->slug))
            ->setLastModificationDate($post->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.7));
    });

    // 4. Thêm các danh mục tin tức đang hiển thị công khai
    PostCategory::where('is_visible', true)->get()->each(function (PostCategory $category) use ($sitemap) {
        $sitemap->add(Url::create(route('blog.category', $category->slug))
            ->setLastModificationDate($category->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.5));
    });

    // 5. Thêm các nhãn bài viết (Tags)
    PostTag::all()->each(function (PostTag $tag) use ($sitemap) {
        $sitemap->add(Url::create(route('blog.tag', $tag->slug))
            ->setLastModificationDate($tag->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.3));
    });

    // 6. Thêm các trang tĩnh (Điều khoản, chính sách, giới thiệu...) loại trừ các trang chặn hiển thị (is_noindex = 1)
    Page::published()->where('is_noindex', false)->get()->each(function (Page $page) use ($sitemap) {
        $sitemap->add(Url::create(route('page.show', $page->slug))
            ->setLastModificationDate($page->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.5));
    });

    // Ghi file ra thư mục public để công cụ tìm kiếm có thể truy cập được
    $sitemap->writeToFile(public_path('sitemap.xml'));

    $this->info('Đã hoàn tất tạo sitemap.xml tại public/sitemap.xml!');

    // Ghi lại file robots.txt kèm khai báo Sitemap theo tên miền hiện tại (URL tuyệt đối chuẩn SEO)
    $robotsContent = implode("\n", [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /dashboard',
        'Disallow: /install',
        '',
        'Sitemap: ' . url('/sitemap.xml'),
    ]) . "\n";
    file_put_contents(public_path('robots.txt'), $robotsContent);

    $this->info('Đã cập nhật robots.txt kèm khai báo Sitemap!');
})->purpose('Tự động tạo sitemap.xml cho toàn bộ website phục vụ tối ưu hóa tìm kiếm (SEO)');


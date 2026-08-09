<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Post;
use App\Models\Comment;
use App\Models\SeoMeta;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BlogCmsSeeder extends Seeder
{
    /**
     * Chạy seeding dữ liệu Blog CMS mẫu.
     */
    public function run(): void
    {
        // 0. Tắt khóa ngoại và làm sạch các bảng blog để chạy seeding không lỗi trùng
        Schema::disableForeignKeyConstraints();
        DB::table('comments')->truncate();
        DB::table('post_tag_maps')->truncate();
        DB::table('post_tags')->truncate();
        DB::table('seo_metas')->truncate();
        DB::table('posts')->truncate();
        DB::table('post_categories')->truncate();
        Schema::enableForeignKeyConstraints();

        // Lấy tài khoản admin làm tác giả mặc định
        $admin = User::where('role', 'admin')->first();
        $authorId = $admin ? $admin->id : 1;

        // 1. Tạo các danh mục mẫu (Categories)
        $categoriesData = [
            [
                'name' => 'Bí quyết săn Sale Shopee',
                'description' => 'Tổng hợp các mẹo, bí quyết săn mã giảm giá và coupon tốt nhất trên sàn Shopee.',
                'icon' => 'shopping-bag',
            ],
            [
                'name' => 'Mẹo hoàn tiền & Tiết kiệm',
                'description' => 'Hướng dẫn chi tiết cách thức nhận cashback tối đa khi mua sắm online.',
                'icon' => 'wallet',
            ],
            [
                'name' => 'Tin tức Thương mại điện tử',
                'description' => 'Cập nhật các chương trình khuyến mãi lớn và xu hướng thương mại điện tử mới nhất.',
                'icon' => 'newspaper',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $item) {
            $cat = PostCategory::create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'description' => $item['description'],
                'icon' => $item['icon'],
                'is_visible' => true,
                'order' => count($categories) + 1,
            ]);
            $categories[] = $cat;

            // Tạo SEO Meta cho danh mục
            SeoMeta::create([
                'seoable_type' => PostCategory::class,
                'seoable_id' => $cat->id,
                'meta_title' => $cat->name . ' - Hoàn Tiền Shopee',
                'meta_description' => $cat->description,
                'meta_keywords' => 'săn sale, shoppee, hoàn tiền, tiết kiệm, ' . strtolower($cat->name),
            ]);
        }

        // 2. Tạo các thẻ mẫu (Tags)
        $tagsData = ['Shopee Cashback', 'Săn Mã Giảm Giá', 'Kinh Nghiệm Mua Sắm', 'Tiết Kiệm Chi Phí', 'Mẹo MMO'];
        $tags = [];
        foreach ($tagsData as $name) {
            $tag = PostTag::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
            $tags[] = $tag;

            // SEO Meta cho tag
            SeoMeta::create([
                'seoable_type' => PostTag::class,
                'seoable_id' => $tag->id,
                'meta_title' => 'Tổng hợp bài viết về ' . $tag->name,
                'meta_description' => 'Xem tất cả các bài chia sẻ kinh nghiệm, hướng dẫn liên quan đến chủ đề ' . $tag->name,
                'meta_keywords' => strtolower($tag->name) . ', cashback, hoàn tiền shopee',
            ]);
        }

        // 3. Tạo các bài viết mẫu (Posts)
        $postsData = [
            [
                'title' => 'Cách săn mã giảm giá Shopee 1 triệu đồng cực dễ dàng',
                'summary' => 'Hướng dẫn chi tiết từng bước giúp bạn canh giờ săn các voucher giá trị lớn từ Shopee để mua sắm siêu tiết kiệm.',
                'content' => '<h2>1. Chuẩn bị trước giờ G</h2><p>Để săn được các mã giảm giá Shopee lớn, đặc biệt là mã 1 triệu đồng, bạn cần chuẩn bị thiết bị di động có tốc độ mạng ổn định và cài đặt sẵn múi giờ chuẩn của Shopee. Hãy thêm sản phẩm cần mua vào giỏ hàng từ trước.</p><h2>2. Canh thời gian load trang và nhấn áp dụng mã</h2><p>Mã lớn thường xuất hiện vào các khung giờ vàng như 0h, 9h, 12h, 15h, 18h, và 21h. Hãy sử dụng cơ chế đếm ngược giây chuẩn xác. Ngay khi đồng hồ điểm sang giây thứ 00 của khung giờ săn sale, hãy nhấn nút "Đặt hàng" thật nhanh.</p><h2>3. Kết hợp mua sắm qua cổng Hoàn Tiền</h2><p>Đặc biệt, nếu bạn thực hiện mua hàng thông qua liên kết affiliate hoàn tiền của chúng tôi, bạn sẽ được hoàn trả thêm tới 70% hoa hồng Shopee nữa. Tiết kiệm chồng tiết kiệm!</p>',
                'category_id' => $categories[0]->id,
                'thumbnail' => '/uploads/blog/default-thumbnail.jpg',
                'is_featured' => true,
                'is_sticky' => true,
                'view_count' => 1250,
                'like_count' => 120,
                'share_count' => 45,
            ],
            [
                'title' => 'Cashback Shopee là gì? Hướng dẫn nhận hoàn tiền mua sắm 2 tầng F1, F2',
                'summary' => 'Bài viết phân tích mô hình hoàn tiền Shopee đang làm mưa làm gió. Cách đăng ký tài khoản và mời bạn bè tham gia nhận hoa hồng thụ động.',
                'content' => '<h2>1. Định nghĩa Cashback Shopee</h2><p>Khi các nhà bán hàng trên Shopee chi trả hoa hồng tiếp thị liên kết (Affiliate) để quảng bá sản phẩm, hệ thống của chúng tôi sẽ nhận khoản tiền đó và chia sẻ lại tối đa 70% cho chính bạn dưới dạng hoàn tiền (Cashback).</p><h2>2. Cơ chế giới thiệu 2 tầng độc đáo</h2><p>Bên cạnh việc tự mua sắm để nhận tiền hoàn, bạn có thể gửi link giới thiệu cho bạn bè:</p><ul><li>Khi F1 (người bạn giới thiệu trực tiếp) mua hàng và nhận hoàn tiền, bạn được hưởng thêm 5% tính trên số tiền hoàn of F1.</li><li>Khi F2 (người do F1 giới thiệu) mua hàng, bạn tiếp tục nhận thêm 2% hoa hồng gián tiếp.</li></ul><h2>3. Hướng dẫn rút tiền về tài khoản ngân hàng</h2><p>Chỉ cần tích lũy tối thiểu 50.000đ, bạn có thể thực hiện lệnh rút tiền nhanh qua ATM/Ngân hàng hoặc Ví Momo. Admin duyệt lệnh rút nhanh chóng trong vòng 24 giờ làm việc.</p>',
                'category_id' => $categories[1]->id,
                'thumbnail' => '/uploads/blog/default-thumbnail.jpg',
                'is_featured' => true,
                'is_sticky' => false,
                'view_count' => 840,
                'like_count' => 95,
                'share_count' => 30,
            ],
            [
                'title' => 'Top 5 ngành hàng có tỷ lệ hoàn tiền Shopee cao nhất hiện nay',
                'summary' => 'Khám phá các nhóm ngành hàng có tỷ lệ chia sẻ hoa hồng cực tốt từ nhà bán hàng Shopee như Thời trang, Mỹ phẩm và Điện tử.',
                'content' => '<h2>1. Ngành Thời Trang và Phụ Kiện</h2><p>Thời trang là ngành hàng có biên lợi nhuận cao nhất và tỷ lệ tiếp thị liên kết thường dao động từ 8% đến 12%. Nếu mua sắm các mẫu váy, quần jean hoặc giày thể thao, bạn sẽ nhận về khoản tiền hoàn rất hời.</p><h2>2. Ngành Mỹ Phẩm & Chăm Sóc Sắc Đẹp</h2><p>Các thương hiệu mỹ phẩm lớn liên tục tung ra các chương trình kích cầu mua sắm với hoa hồng affiliate lên đến 15%. Đây là mỏ vàng để tích lũy cashback.</p><h2>3. Thiết bị Điện gia dụng & Phụ kiện công nghệ</h2><p>Dù tỷ lệ phần trăm thấp hơn (từ 3% - 5%), nhưng do giá trị đơn hàng điện tử cao (vài triệu đến chục triệu), số tiền hoàn tuyệt đối bạn nhận lại được là vô cùng đáng kể.</p>',
                'category_id' => $categories[2]->id,
                'thumbnail' => '/uploads/blog/default-thumbnail.jpg',
                'is_featured' => false,
                'is_sticky' => false,
                'view_count' => 450,
                'like_count' => 32,
                'share_count' => 12,
            ],
        ];

        foreach ($postsData as $index => $data) {
            $post = Post::create([
                'author_id' => $authorId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'summary' => $data['summary'],
                'content' => $data['content'],
                'thumbnail' => $data['thumbnail'],
                'status' => 'published',
                'published_at' => now()->subDays($index * 2),
                'is_sticky' => $data['is_sticky'],
                'is_featured' => $data['is_featured'],
                'is_commentable' => true,
                'view_count' => $data['view_count'],
                'like_count' => $data['like_count'],
                'share_count' => $data['share_count'],
            ]);

            // Gắn tags cho bài viết
            $post->tags()->attach([$tags[0]->id, $tags[1]->id, $tags[2]->id]);

            // Tạo SEO Meta
            SeoMeta::create([
                'seoable_type' => Post::class,
                'seoable_id' => $post->id,
                'meta_title' => $post->title . ' | Hướng dẫn Cashback',
                'meta_description' => $post->summary,
                'meta_keywords' => 'hoàn tiền, shopee, affiliate, mua sắm tiết kiệm, ' . strtolower($post->title),
            ]);

            // Tạo một số bình luận mẫu
            Comment::create([
                'post_id' => $post->id,
                'user_id' => null,
                'author_name' => 'Nguyễn Minh Hải',
                'author_email' => 'minhhai@gmail.com',
                'content' => 'Bài viết rất hữu ích, mình đã áp dụng và săn được mã giảm giá 200k. Kết hợp hoàn tiền nhận lại được thêm 45k nữa, quá đã!',
                'status' => 'approved',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subDays(1),
            ]);

            Comment::create([
                'post_id' => $post->id,
                'user_id' => null,
                'author_name' => 'Lê Thị Thuỳ',
                'author_email' => 'thuythuy@gmail.com',
                'content' => 'Cho mình hỏi tiền hoàn từ các đơn hàng này thường sau bao lâu thì được duyệt rút về tài khoản ngân hàng vậy ạ?',
                'status' => 'approved',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subHours(12),
            ]);
        }
    }
}

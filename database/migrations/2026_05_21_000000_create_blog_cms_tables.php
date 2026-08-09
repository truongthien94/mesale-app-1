<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chạy migration để tạo các bảng dữ liệu Blog CMS chuyên nghiệp.
     */
    public function up(): void
    {
        // 1. Bảng danh mục bài viết (Post Categories)
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Liên kết tới danh mục cha');
            $table->string('name')->comment('Tên danh mục');
            $table->string('slug')->unique()->comment('Slug URL thân thiện');
            $table->text('description')->nullable()->comment('Mô tả danh mục');
            $table->string('image')->nullable()->comment('Ảnh đại diện danh mục');
            $table->string('icon')->nullable()->comment('Icon đại diện danh mục');
            $table->integer('order')->default(0)->comment('Thứ tự sắp xếp hiển thị');
            $table->boolean('is_visible')->default(true)->comment('Trạng thái ẩn/hiện');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('post_categories')->onDelete('set null');
        });

        // 2. Bảng nhãn bài viết (Post Tags)
        Schema::create('post_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Tên nhãn');
            $table->string('slug')->unique()->comment('Slug nhãn thân thiện');
            $table->timestamps();
        });

        // 3. Bảng bài viết (Posts)
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id')->comment('Mã tác giả (liên kết bảng users)');
            $table->unsignedBigInteger('category_id')->nullable()->comment('Mã danh mục chính');
            $table->string('title')->comment('Tiêu đề bài viết');
            $table->string('slug')->unique()->comment('Slug URL thân thiện');
            $table->text('summary')->nullable()->comment('Mô tả ngắn gọn/Tóm tắt');
            $table->longText('content')->comment('Nội dung HTML chi tiết');
            $table->string('thumbnail')->nullable()->comment('Ảnh đại diện bài viết');
            $table->text('gallery')->nullable()->comment('Danh sách ảnh gallery (định dạng JSON)');
            $table->string('video_url')->nullable()->comment('Đường dẫn video nhúng YouTube/TikTok');
            $table->string('status')->default('draft')->comment('Trạng thái: draft (nháp), pending (chờ duyệt), published (xuất bản), private (riêng tư)');
            $table->timestamp('published_at')->nullable()->comment('Thời gian đăng bài/hẹn giờ đăng bài');
            $table->boolean('is_sticky')->default(false)->comment('Bài viết được ghim lên đầu');
            $table->boolean('is_featured')->default(false)->comment('Bài viết nổi bật');
            $table->boolean('is_commentable')->default(true)->comment('Cho phép bình luận không');
            $table->integer('view_count')->default(0)->comment('Tổng lượt xem');
            $table->integer('share_count')->default(0)->comment('Tổng lượt chia sẻ');
            $table->integer('like_count')->default(0)->comment('Tổng lượt thích');
            $table->integer('comment_count')->default(0)->comment('Tổng lượt bình luận');
            $table->text('faq_schema')->nullable()->comment('Dữ liệu Schema FAQ (định dạng JSON)');
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('post_categories')->onDelete('set null');
            $table->index('status');
            $table->index('is_featured');
            $table->index('is_sticky');
        });

        // 4. Bảng liên kết trung gian Post - Tag (Post Tag Maps)
        Schema::create('post_tag_maps', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->comment('Liên kết bài viết');
            $table->unsignedBigInteger('tag_id')->comment('Liên kết nhãn');

            $table->primary(['post_id', 'tag_id']);
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('post_tags')->onDelete('cascade');
        });

        // 5. Bảng bình luận (Comments)
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->comment('Liên kết bài viết');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Liên kết tài khoản thành viên (nếu có)');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Liên kết bình luận cha (để trả lời bình luận)');
            $table->string('author_name')->nullable()->comment('Tên tác giả bình luận (dành cho guest)');
            $table->string('author_email')->nullable()->comment('Email tác giả bình luận (dành cho guest)');
            $table->text('content')->comment('Nội dung bình luận');
            $table->string('status')->default('pending')->comment('Trạng thái: pending (chờ duyệt), approved (đã duyệt), spam (thư rác), deleted (đã xóa)');
            $table->integer('like_count')->default(0)->comment('Tổng lượt thích bình luận');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP của người bình luận');
            $table->text('user_agent')->nullable()->comment('User Agent thiết bị');
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
            $table->index('status');
        });

        // 6. Bảng quản lý thư viện Media (Media Library)
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->comment('Tên tệp gốc');
            $table->string('path')->comment('Đường dẫn lưu trữ tệp');
            $table->string('folder')->default('/')->comment('Thư mục lưu trữ trong media');
            $table->integer('size')->comment('Dung lượng tệp (bytes)');
            $table->string('extension', 10)->comment('Đuôi mở rộng tệp');
            $table->string('mime_type')->comment('Định dạng tệp mime');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Người tải lên tệp');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 7. Bảng nhật ký lượt xem bài viết (Post Views Log)
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->comment('Liên kết bài viết');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Liên kết thành viên (nếu đã đăng nhập)');
            $table->string('ip_address', 45)->comment('Địa chỉ IP của độc giả');
            $table->text('user_agent')->nullable()->comment('Thông tin trình duyệt');
            $table->timestamp('viewed_at')->useCurrent()->comment('Thời gian xem');

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 8. Bảng lượt thích bài viết (Post Likes Log)
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->comment('Liên kết bài viết');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Liên kết thành viên (nếu đã đăng nhập)');
            $table->string('ip_address', 45)->comment('Địa chỉ IP');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['post_id', 'user_id', 'ip_address']);
        });

        // 9. Bảng lượt chia sẻ bài viết (Post Shares Log)
        Schema::create('post_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->comment('Liên kết bài viết');
            $table->string('platform')->comment('Nền tảng chia sẻ (facebook, twitter, telegram, copy...)');
            $table->string('ip_address', 45)->comment('Địa chỉ IP');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });

        // 10. Bảng thông tin SEO Meta (SEO Meta)
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type')->comment('Loại Model liên kết (Post, Category, Tag)');
            $table->unsignedBigInteger('seoable_id')->comment('Mã ID Model liên kết');
            $table->string('meta_title')->nullable()->comment('Tiêu đề tối ưu SEO');
            $table->text('meta_description')->nullable()->comment('Mô tả tối ưu SEO');
            $table->string('meta_keywords')->nullable()->comment('Từ khóa SEO');
            $table->string('og_image')->nullable()->comment('Ảnh Open Graph Facebook');
            $table->string('twitter_title')->nullable()->comment('Tiêu đề Twitter');
            $table->text('twitter_description')->nullable()->comment('Mô tả Twitter');
            $table->string('twitter_image')->nullable()->comment('Ảnh Twitter');
            $table->string('canonical_url')->nullable()->comment('Đường dẫn gốc canonical');
            $table->text('schema_data')->nullable()->comment('Cấu trúc Schema JSON-LD tùy chỉnh');
            $table->timestamps();

            $table->index(['seoable_type', 'seoable_id']);
        });

        // 11. Bảng lịch sử chỉnh sửa bài viết (Post Revisions)
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->comment('Liên kết bài viết');
            $table->unsignedBigInteger('user_id')->comment('Người thực hiện chỉnh sửa');
            $table->string('title')->comment('Tiêu đề phiên bản cũ');
            $table->text('summary')->nullable()->comment('Mô tả ngắn phiên bản cũ');
            $table->longText('content')->comment('Nội dung phiên bản cũ');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('seo_metas');
        Schema::dropIfExists('post_shares');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('post_views');
        Schema::dropIfExists('media');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('post_tag_maps');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_tags');
        Schema::dropIfExists('post_categories');
    }
};

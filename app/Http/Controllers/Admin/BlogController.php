<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\Comment;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\SitemapRefresher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BlogController extends Controller
{
    /**
     * Danh sách slug bị cấm đặt cho bài viết vì trùng với các route hệ thống
     * đã khai báo trước trong nhóm route /blog (search, category, tag, feed).
     */
    private const RESERVED_POST_SLUGS = ['search', 'category', 'tag', 'feed'];

    // ==========================================
    // 1. QUẢN LÝ BÀI VIẾT (POSTS)
    // ==========================================

    /**
     * Danh sách bài viết trong trang quản trị
     */
    public function postsIndex(Request $request)
    {
        $query = Post::with(['category', 'author'])->orderBy('created_at', 'desc');

        // Tìm kiếm theo tiêu đề
        if ($request->has('search') && !empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Lọc theo danh mục
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        $posts = $query->paginate(15)->withQueryString();
        $categories = PostCategory::all();

        return view('admin.blog.posts.index', compact('posts', 'categories'));
    }

    /**
     * Form tạo bài viết mới
     */
    public function postCreate()
    {
        $categories = PostCategory::all();
        $tags = PostTag::all();
        $aiEnabled = Setting::getVal('ai_status', '0') === '1';
        return view('admin.blog.posts.create', compact('categories', 'tags', 'aiEnabled'));
    }

    /**
     * API AJAX: Tạo nhanh nội dung bài viết blog bằng AI.
     * Trả về JSON { success, summary?, content?, seo_title?, seo_description?, seo_keywords?, message? }.
     */
    public function postGenerateAi(Request $request)
    {
        // Chặn gọi API thực tế khi đang ở chế độ demo để tránh lạm dụng tài nguyên
        if (config('app.demo', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng tạo nội dung bằng AI bị vô hiệu hóa trong phiên bản thử nghiệm (Demo).'),
            ], 422);
        }

        // Kiểm tra dịch vụ AI đã được bật trong cài đặt hệ thống chưa
        if (Setting::getVal('ai_status', '0') !== '1') {
            return response()->json([
                'success' => false,
                'message' => __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong Cài đặt > AI.'),
            ], 422);
        }

        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
            'title'  => 'nullable|string|max:255',
            'tone'   => 'nullable|string|max:50',
        ], [
            'prompt.required' => __('Vui lòng mô tả nội dung bài viết bạn muốn tạo.'),
        ]);

        $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $title    = $validated['title'] ?? '';
        $tone     = $validated['tone'] ?? 'than-thien';

        $toneMap = [
            'than-thien'    => 'thân thiện, gần gũi',
            'chuyen-nghiep' => 'chuyên nghiệp, trang trọng',
            'huong-dan'     => 'hướng dẫn chi tiết, dễ hiểu từng bước',
            'review'        => 'đánh giá khách quan, có chính kiến',
        ];
        $toneText = $toneMap[$tone] ?? 'thân thiện, gần gũi';

        // System prompt: yêu cầu AI trả về đúng định dạng JSON để dễ phân tích
        $systemPrompt = <<<SYS
Bạn là chuyên gia viết blog content marketing cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: viết một BÀI VIẾT BLOG bằng TIẾNG VIỆT theo yêu cầu của người dùng (chủ đề mua sắm, săn sale, hoàn tiền, mẹo tiêu dùng...).

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"title": "tiêu đề gợi ý", "summary": "tóm tắt dưới 400 ký tự", "content": "mã HTML nội dung bài viết", "seo_title": "tiêu đề SEO", "seo_description": "mô tả SEO dưới 160 ký tự", "seo_keywords": "các từ khoá, cách nhau bởi dấu phẩy"}.
- Trường "content" là HTML sạch dùng thẻ ngữ nghĩa: <h2>, <h3>, <p>, <ul>, <li>, <strong>, <a>... KHÔNG kèm thẻ <html>, <head><meta charset="utf-8">, <body>, KHÔNG inline CSS rườm rà, KHÔNG dùng <script>.
- Nội dung mạch lạc, chia mục rõ ràng, chuẩn SEO, hấp dẫn người đọc và phù hợp ngữ cảnh website hoàn tiền Shopee.
- Giọng điệu: {$toneText}.
SYS;

        $userPrompt = $title !== ''
            ? "Tiêu đề bài viết: {$title}\nYêu cầu nội dung: {$validated['prompt']}"
            : "Yêu cầu nội dung: {$validated['prompt']}";

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ], [
                'max_tokens'  => 4000,
                'temperature' => 0.8,
            ]);

            // AIService trả về chuỗi lỗi đã chuẩn hóa thay vì ném exception khi gặp sự cố
            if (str_contains($response, 'Lỗi khi kết nối với AI API') || str_contains($response, 'Dịch vụ AI hiện đang')) {
                return response()->json(['success' => false, 'message' => $response], 422);
            }

            // Loại bỏ rào ``` nếu AI lỡ bọc kết quả trong code fence
            $clean = trim($response);
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);

            // Cắt lấy đoạn JSON nằm giữa dấu { đầu tiên và } cuối cùng
            $start = strpos($clean, '{');
            $end   = strrpos($clean, '}');
            $data  = [];

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $data = $parsed;
                }
            }

            // Nếu không phân tích được JSON, coi toàn bộ phản hồi là nội dung HTML
            if (empty($data['content'])) {
                $data['content'] = $clean;
            }

            ActivityLog::log(__('Tạo nội dung bài viết blog bằng AI'), auth()->id());

            return response()->json([
                'success'         => true,
                'title'           => $data['title'] ?? null,
                'summary'         => $data['summary'] ?? null,
                'content'         => $data['content'] ?? null,
                'seo_title'       => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'seo_keywords'    => $data['seo_keywords'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Sinh slug duy nhất cho bài viết từ chuỗi nguồn (slug tùy chỉnh hoặc tiêu đề).
     * Nếu bị trùng với bài viết khác hoặc trùng route hệ thống thì tự thêm hậu tố số.
     *
     * @param string   $source   Chuỗi nguồn để tạo slug
     * @param int|null $ignoreId ID bài viết cần bỏ qua khi kiểm tra trùng (lúc cập nhật)
     */
    private function generateUniquePostSlug(string $source, ?int $ignoreId = null): string
    {
        $slug = Str::slug($source);

        // Trường hợp chuỗi nguồn toàn ký tự đặc biệt, dùng slug mặc định
        if ($slug === '') {
            $slug = 'bai-viet';
        }

        $originalSlug = $slug;
        $count = 1;

        // Lặp cho tới khi tìm được slug chưa dùng và không trùng route hệ thống
        while (
            in_array($slug, self::RESERVED_POST_SLUGS, true)
            || Post::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Lưu bài viết mới
     */
    public function postStore(Request $request)
    {
        // Chuẩn hóa slug tùy chỉnh do người dùng nhập (bỏ dấu, viết thường, nối gạch ngang)
        // trước khi kiểm tra hợp lệ để tránh báo lỗi oan khi admin gõ có dấu hoặc viết hoa
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $request->validate([
            'title' => 'required|string|max:255|unique:posts,title',
            'slug' => 'nullable|string|max:255|unique:posts,slug|not_in:' . implode(',', self::RESERVED_POST_SLUGS),
            'summary' => 'required|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:post_categories,id',
            'status' => 'required|in:draft,published,scheduled',
            'thumbnail' => 'nullable|string|max:1000',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
            'seo_keywords' => 'nullable|string|max:255',
        ], [
            'title.required' => 'Tiêu đề bài viết bắt buộc phải nhập.',
            'title.unique' => 'Tiêu đề bài viết này đã tồn tại.',
            'slug.unique' => 'Đường dẫn (slug) này đã được sử dụng cho bài viết khác.',
            'slug.not_in' => 'Đường dẫn (slug) này trùng với đường dẫn hệ thống, vui lòng chọn đường dẫn khác.',
            'summary.required' => 'Tóm tắt bài viết bắt buộc phải nhập.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'category_id.required' => 'Vui lòng chọn danh mục bài viết.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'thumbnail_file.image' => 'Ảnh tải lên phải là định dạng hình ảnh.',
            'thumbnail_file.max' => 'Ảnh đại diện dung lượng tối đa 2MB.',
        ]);

        try {
            DB::beginTransaction();

            // Ưu tiên slug tùy chỉnh admin nhập, nếu bỏ trống thì tự sinh từ tiêu đề
            $slug = $this->generateUniquePostSlug($request->filled('slug') ? $request->input('slug') : $request->title);

            // Xử lý upload ảnh đại diện
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail_file')) {
                $file = $request->file('thumbnail_file');
                
                // Lấy extension an toàn và sinh tên tệp ngẫu nhiên
                $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
                if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'htaccess', 'js'])) {
                    $extension = 'png';
                }
                $filename = time() . '_' . Str::random(10) . '.' . $extension;

                // Lưu vào thư mục public/uploads/blog
                $destinationPath = public_path('uploads/blog');
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $thumbnailPath = '/uploads/blog/' . $filename;
            } elseif ($request->filled('thumbnail') && is_string($request->thumbnail)) {
                // Nếu chọn từ thư viện (dạng đường dẫn URL)
                $path = parse_url($request->thumbnail, PHP_URL_PATH);
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($extension, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
                    $thumbnailPath = $path;
                }
            }

            // Lưu bài viết
            $post = Post::create([
                'title' => $request->title,
                'slug' => $slug,
                'summary' => $request->summary,
                'content' => $request->content,
                'category_id' => $request->category_id,
                'author_id' => auth()->id(),
                'status' => $request->status,
                'thumbnail' => $thumbnailPath,
                'is_featured' => $request->has('is_featured') ? true : false,
                'is_sticky' => $request->has('is_sticky') ? true : false,
                'published_at' => $request->status === 'published' ? now() : ($request->status === 'scheduled' ? $request->published_at : null),
            ]);

            // Đồng bộ tags
            if ($request->has('tags') && is_array($request->tags)) {
                $post->tags()->sync($request->tags);
            }

            // Lưu SEO Meta
            $post->seoMeta()->create([
                'meta_title' => $request->filled('seo_title') ? trim($request->input('seo_title')) : null,
                'meta_description' => $request->filled('seo_description') ? trim($request->input('seo_description')) : null,
                'meta_keywords' => $request->filled('seo_keywords') ? trim($request->input('seo_keywords')) : null,
            ]);

            // Ghi log
            ActivityLog::log("Tạo bài viết mới: {$post->title}", auth()->id());

            DB::commit();
            SitemapRefresher::afterResponse();

            return redirect()->route('admin.blog.posts.index')->with('success', 'Thêm bài viết mới thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Form chỉnh sửa bài viết
     */
    public function postEdit(Post $post)
    {
        $categories = PostCategory::all();
        $tags = PostTag::all();
        $seo = $post->seoMeta;
        $postTags = $post->tags->pluck('id')->toArray();

        return view('admin.blog.posts.edit', compact('post', 'categories', 'tags', 'seo', 'postTags'));
    }

    /**
     * Cập nhật bài viết
     */
    public function postUpdate(Request $request, Post $post)
    {
        // Chuẩn hóa slug tùy chỉnh do người dùng nhập trước khi kiểm tra hợp lệ
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $request->validate([
            'title' => 'required|string|max:255|unique:posts,title,' . $post->id,
            'slug' => 'nullable|string|max:255|unique:posts,slug,' . $post->id . '|not_in:' . implode(',', self::RESERVED_POST_SLUGS),
            'summary' => 'required|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:post_categories,id',
            'status' => 'required|in:draft,published,scheduled',
            'thumbnail' => 'nullable|string|max:1000',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
            'seo_keywords' => 'nullable|string|max:255',
        ], [
            'title.required' => 'Tiêu đề bài viết bắt buộc phải nhập.',
            'title.unique' => 'Tiêu đề bài viết này đã tồn tại.',
            'slug.unique' => 'Đường dẫn (slug) này đã được sử dụng cho bài viết khác.',
            'slug.not_in' => 'Đường dẫn (slug) này trùng với đường dẫn hệ thống, vui lòng chọn đường dẫn khác.',
            'summary.required' => 'Tóm tắt bài viết bắt buộc phải nhập.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'category_id.required' => 'Vui lòng chọn danh mục bài viết.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'thumbnail_file.image' => 'Ảnh tải lên phải là định dạng hình ảnh.',
            'thumbnail_file.max' => 'Ảnh đại diện dung lượng tối đa 2MB.',
        ]);

        try {
            DB::beginTransaction();

            // Giữ nguyên slug admin nhập để không phá vỡ URL đã được index,
            // chỉ khi bỏ trống ô slug hệ thống mới tự sinh lại từ tiêu đề mới
            $slug = $this->generateUniquePostSlug(
                $request->filled('slug') ? $request->input('slug') : $request->title,
                $post->id
            );

            // Xử lý upload ảnh đại diện mới
            $thumbnailPath = $post->thumbnail;
            if ($request->hasFile('thumbnail_file')) {
                // Xóa ảnh cũ nếu là ảnh upload cục bộ
                if ($post->thumbnail && File::exists(public_path($post->thumbnail)) && str_contains($post->thumbnail, '/uploads/blog/')) {
                    File::delete(public_path($post->thumbnail));
                }

                $file = $request->file('thumbnail_file');
                
                // Lấy extension an toàn và sinh tên tệp ngẫu nhiên
                $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
                if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'htaccess', 'js'])) {
                    $extension = 'png';
                }
                $filename = time() . '_' . Str::random(10) . '.' . $extension;

                $destinationPath = public_path('uploads/blog');
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $thumbnailPath = '/uploads/blog/' . $filename;
            } elseif ($request->filled('thumbnail') && is_string($request->thumbnail)) {
                // Nếu chọn từ thư viện (dạng đường dẫn URL)
                $path = parse_url($request->thumbnail, PHP_URL_PATH);
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($extension, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
                    if ($path !== $post->thumbnail) {
                        // Xóa ảnh cũ nếu là ảnh upload cục bộ trước đó
                        if ($post->thumbnail && File::exists(public_path($post->thumbnail)) && str_contains($post->thumbnail, '/uploads/blog/')) {
                            File::delete(public_path($post->thumbnail));
                        }
                        $thumbnailPath = $path;
                    }
                }
            } else {
                // Nếu người dùng chọn xóa ảnh (trường thumbnail trống)
                if (empty($request->thumbnail) && !($request->hasFile('thumbnail_file'))) {
                    if ($post->thumbnail && File::exists(public_path($post->thumbnail)) && str_contains($post->thumbnail, '/uploads/blog/')) {
                        File::delete(public_path($post->thumbnail));
                    }
                    $thumbnailPath = null;
                }
            }

            // Cập nhật bài viết
            $post->update([
                'title' => $request->title,
                'slug' => $slug,
                'summary' => $request->summary,
                'content' => $request->content,
                'category_id' => $request->category_id,
                'status' => $request->status,
                'thumbnail' => $thumbnailPath,
                'is_featured' => $request->has('is_featured') ? true : false,
                'is_sticky' => $request->has('is_sticky') ? true : false,
                'published_at' => $request->status === 'published' ? ($post->published_at ?: now()) : ($request->status === 'scheduled' ? $request->published_at : null),
            ]);

            // Đồng bộ tags
            if ($request->has('tags') && is_array($request->tags)) {
                $post->tags()->sync($request->tags);
            } else {
                $post->tags()->detach();
            }

            // Cập nhật SEO Meta
            $post->seoMeta()->updateOrCreate(
                ['seoable_id' => $post->id, 'seoable_type' => Post::class],
                [
                    'meta_title' => $request->filled('seo_title') ? trim($request->input('seo_title')) : null,
                    'meta_description' => $request->filled('seo_description') ? trim($request->input('seo_description')) : null,
                    'meta_keywords' => $request->filled('seo_keywords') ? trim($request->input('seo_keywords')) : null,
                ]
            );

            // Ghi log
            ActivityLog::log("Cập nhật bài viết: {$post->title}", auth()->id());

            DB::commit();
            SitemapRefresher::afterResponse();

            return redirect()->route('admin.blog.posts.index')->with('success', 'Cập nhật bài viết thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Xóa bài viết
     */
    public function postDestroy(Post $post)
    {
        try {
            DB::beginTransaction();

            $title = $post->title;

            // Xóa ảnh thumbnail
            if ($post->thumbnail && File::exists(public_path($post->thumbnail))) {
                File::delete(public_path($post->thumbnail));
            }

            // Xóa liên kết tags, seo, comments, views, likes, shares và revisions
            $post->tags()->detach();
            $post->seoMeta()->delete();
            $post->comments()->delete();
            $post->views()->delete();
            $post->likes()->delete();
            $post->shares()->delete();
            $post->revisions()->delete();

            $post->delete();

            // Ghi log
            ActivityLog::log("Xóa bài viết: {$title}", auth()->id());

            DB::commit();
            SitemapRefresher::afterResponse();

            return redirect()->route('admin.blog.posts.index')->with('success', 'Xóa bài viết thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Bật/Tắt ghim bài viết (Sticky) qua AJAX
     */
    public function postToggleSticky(Post $post)
    {
        $post->is_sticky = !$post->is_sticky;
        $post->save();

        ActivityLog::log("Thay đổi trạng thái ghim bài viết: {$post->title}", auth()->id());

        return response()->json([
            'success' => true,
            'is_sticky' => $post->is_sticky,
            'message' => 'Cập nhật trạng thái ghim thành công!'
        ]);
    }

    // ==========================================
    // 2. QUẢN LÝ DANH MỤC (CATEGORIES)
    // ==========================================

    /**
     * Danh sách chuyên mục
     */
    public function categoriesIndex()
    {
        $categories = PostCategory::withCount('posts')->orderBy('order', 'asc')->get();
        return view('admin.blog.categories.index', compact('categories'));
    }

    /**
     * Lưu chuyên mục mới
     */
    public function categoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:post_categories,name',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ], [
            'name.required' => 'Tên chuyên mục không được để trống.',
            'name.unique' => 'Tên chuyên mục đã tồn tại trong hệ thống.',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (PostCategory::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $category = PostCategory::create([
            'name' => $request->name,
            'slug' => $slug,
            'icon' => $request->icon ?: 'folder',
            'description' => $request->description,
            'order' => $request->sort_order ?: 0,
            'is_visible' => true,
        ]);

        ActivityLog::log("Tạo chuyên mục Blog: {$category->name}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.blog.categories.index')->with('success', 'Thêm chuyên mục mới thành công!');
    }

    /**
     * Cập nhật chuyên mục
     */
    public function categoryUpdate(Request $request, PostCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:post_categories,name,' . $category->id,
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ], [
            'name.required' => 'Tên chuyên mục không được để trống.',
            'name.unique' => 'Tên chuyên mục đã tồn tại.',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (PostCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $category->update([
            'name' => $request->name,
            'slug' => $slug,
            'icon' => $request->icon ?: 'folder',
            'description' => $request->description,
            'order' => $request->sort_order ?: 0,
        ]);

        ActivityLog::log("Cập nhật chuyên mục Blog: {$category->name}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.blog.categories.index')->with('success', 'Cập nhật chuyên mục thành công!');
    }

    /**
     * Xóa chuyên mục
     */
    public function categoryDestroy(PostCategory $category)
    {
        // Ràng buộc: Không xóa danh mục nếu đang có bài viết tham chiếu
        if ($category->posts()->count() > 0) {
            return back()->with('error', 'Không thể xóa chuyên mục này vì đang có bài viết thuộc về nó.');
        }

        $name = $category->name;
        $category->delete();

        ActivityLog::log("Xóa chuyên mục Blog: {$name}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.blog.categories.index')->with('success', 'Xóa chuyên mục thành công!');
    }

    // ==========================================
    // 3. QUẢN LÝ THỂ TAG (TAGS)
    // ==========================================

    /**
     * Danh sách thẻ tags
     */
    public function tagsIndex()
    {
        $tags = PostTag::withCount('posts')->orderBy('name', 'asc')->get();
        return view('admin.blog.tags.index', compact('tags'));
    }

    /**
     * Lưu tag mới
     */
    public function tagStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:post_tags,name',
        ], [
            'name.required' => 'Tên thẻ tag không được để trống.',
            'name.unique' => 'Tên thẻ tag đã tồn tại.',
        ]);

        $slug = Str::slug($request->name);
        $tag = PostTag::create([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        ActivityLog::log("Tạo thẻ tag Blog: {$tag->name}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.blog.tags.index')->with('success', 'Thêm thẻ tag thành công!');
    }

    /**
     * Cập nhật tag
     */
    public function tagUpdate(Request $request, PostTag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:post_tags,name,' . $tag->id,
        ], [
            'name.required' => 'Tên thẻ tag không được để trống.',
            'name.unique' => 'Tên thẻ tag đã tồn tại.',
        ]);

        $tag->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        ActivityLog::log("Cập nhật thẻ tag Blog: {$tag->name}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.blog.tags.index')->with('success', 'Cập nhật thẻ tag thành công!');
    }

    /**
     * Xóa tag
     */
    public function tagDestroy(PostTag $tag)
    {
        $tag->posts()->detach();
        $name = $tag->name;
        $tag->delete();

        ActivityLog::log("Xóa thẻ tag Blog: {$name}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.blog.tags.index')->with('success', 'Xóa thẻ tag thành công!');
    }

    // ==========================================
    // 4. QUẢN LÝ BÌNH LUẬN (COMMENTS)
    // ==========================================

    /**
     * Danh sách bình luận
     */
    public function commentsIndex(Request $request)
    {
        $query = Comment::with('post')->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $comments = $query->paginate(15)->withQueryString();

        return view('admin.blog.comments.index', compact('comments'));
    }

    /**
     * Duyệt/Ẩn bình luận
     */
    public function commentToggleStatus(Comment $comment)
    {
        $comment->status = $comment->status === 'approved' ? 'pending' : 'approved';
        $comment->save();

        ActivityLog::log("Thay đổi trạng thái bình luận ID: {$comment->id} sang {$comment->status}", auth()->id());

        return response()->json([
            'success' => true,
            'status' => $comment->status,
            'message' => 'Thay đổi trạng thái bình luận thành công!'
        ]);
    }

    /**
     * Trả lời bình luận từ quản trị viên
     */
    public function commentReply(Request $request, Comment $comment)
    {
        $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        // Tạo bình luận phản hồi từ Admin
        Comment::create([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'author_email' => auth()->user()->email,
            'content' => $request->content,
            'status' => 'approved', // Mặc định admin trả lời là duyệt luôn
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        ActivityLog::log("Phản hồi bình luận ID: {$comment->id}", auth()->id());

        return redirect()->route('admin.blog.comments.index')->with('success', 'Đã gửi phản hồi bình luận thành công!');
    }

    /**
     * Xóa bình luận
     */
    public function commentDestroy(Comment $comment)
    {
        // Xóa các bình luận con
        Comment::where('parent_id', $comment->id)->delete();
        $comment->delete();

        ActivityLog::log("Xóa bình luận ID: {$comment->id}", auth()->id());

        return redirect()->route('admin.blog.comments.index')->with('success', 'Xóa bình luận thành công!');
    }

    // ==========================================
    // 5. CẤU HÌNH BLOG CMS (SETTINGS)
    // ==========================================

    /**
     * Trang cấu hình
     */
    public function settingsIndex()
    {
        $settings = DB::table('settings')->where('key', 'like', 'blog_%')->pluck('value', 'key');
        return view('admin.blog.settings.index', compact('settings'));
    }

    /**
     * Lưu cấu hình Blog
     */
    public function settingsStore(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            // Chỉ cập nhật các key bắt đầu bằng blog_
            if (Str::startsWith($key, 'blog_')) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'updated_at' => now()]
                );
            }
        }

        ActivityLog::log("Cập nhật cấu hình Blog CMS", auth()->id());

        return redirect()->route('admin.blog.settings.index')->with('success', 'Cập nhật cấu hình Blog thành công!');
    }
}

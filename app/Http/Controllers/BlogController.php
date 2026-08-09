<?php

namespace App\Http\Controllers;

use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostView;
use App\Models\PostLike;
use App\Models\PostShare;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BlogController extends Controller implements HasMiddleware
{
    /**
     * Khai báo middleware cho Controller này theo chuẩn Laravel 11/12/13.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                $isBlogEnabled = Setting::where('key', 'blog_enabled')->first();
                if ($isBlogEnabled && $isBlogEnabled->value === '0') {
                    abort(404, 'Blog hiện đang tạm khóa.');
                }
                return $next($request);
            }),
        ];
    }

    /**
     * Hiển thị trang danh sách bài viết (Blog Index)
     * URL: /blog
     */
    public function index(Request $request)
    {
        // Lấy số bài viết mỗi trang từ cấu hình, mặc định là 9 bài
        $limitSetting = Setting::where('key', 'blog_posts_per_page')->first();
        $limit = $limitSetting ? intval($limitSetting->value) : 9;

        // Sử dụng Cache để tăng tốc độ tải trang
        $page = $request->get('page', 1);
        $posts = Cache::remember("blog_posts_page_{$page}", 60, function () use ($limit) {
            return Post::published()
                ->with(['category', 'author'])
                ->orderBy('is_sticky', 'desc')
                ->orderBy('published_at', 'desc')
                ->paginate($limit);
        });

        // Lấy bài viết nổi bật (Featured Posts)
        $featuredPosts = Cache::remember('blog_featured_posts', 300, function () {
            return Post::published()
                ->featured()
                ->with('category')
                ->limit(3)
                ->get();
        });

        // Lấy danh sách bài viết xem nhiều nhất (Popular Posts)
        $popularPosts = Cache::remember('blog_popular_posts', 300, function () {
            return Post::published()
                ->orderBy('view_count', 'desc')
                ->limit(5)
                ->get();
        });

        // Lấy danh sách danh mục blog để hiển thị ở sidebar
        $categories = Cache::remember('blog_categories_list', 300, function () {
            return PostCategory::where('is_visible', true)
                ->withCount('posts')
                ->orderBy('order', 'asc')
                ->get();
        });

        // Lấy danh sách tag phổ biến
        $tags = Cache::remember('blog_tags_list', 300, function () {
            return PostTag::withCount('posts')
                ->orderBy('posts_count', 'desc')
                ->limit(15)
                ->get();
        });

        return view('blog.index', compact('posts', 'featuredPosts', 'popularPosts', 'categories', 'tags'));
    }

    /**
     * Hiển thị trang chi tiết bài viết (Blog Show)
     * URL: /blog/{slug}
     */
    public function show(Request $request, $slug)
    {
        // Khách chỉ được truy cập bài đã publish và đến thời điểm công khai.
        // Admin vẫn có thể mở URL trực tiếp để xem trước bài nháp/hẹn giờ.
        $canPreviewUnpublished = auth()->check() && auth()->user()->role === 'admin';
        $postQuery = Post::where('slug', $slug);

        if (!$canPreviewUnpublished) {
            $postQuery->published();
        }

        $post = $postQuery
            ->with(['category', 'author', 'tags', 'seoMeta'])
            ->firstOrFail();

        // Tăng lượt xem bài viết (sử dụng Session để tránh F5 spam lượt xem liên tục)
        $viewedSessionKey = 'viewed_post_' . $post->id;
        if (!$request->session()->has($viewedSessionKey)) {
            $request->session()->put($viewedSessionKey, true);
            
            // Tăng trực tiếp trong DB
            Post::withoutTimestamps(function () use ($post): void {
                $post->increment('view_count');
            });

            // Lưu log vào bảng post_views
            PostView::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'viewed_at' => now(),
            ]);
        }

        // Tạo mục lục tự động (Table of Contents - TOC)
        $toc = $this->generateTableOfContents($post->content);

        // Lấy bài viết liên quan (Cùng danh mục, loại trừ bài viết hiện tại)
        $relatedPosts = Post::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get();

        // Lấy danh sách bình luận đã duyệt của bài viết
        $comments = Comment::where('post_id', $post->id)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with('replies')
            ->orderBy('created_at', 'desc')
            ->get();

        // Tạo JSON-LD Schema cho SEO
        $schemaJson = $this->generateArticleSchema($post);

        // Lấy danh sách danh mục & tag cho sidebar
        $categories = PostCategory::where('is_visible', true)->get();
        $tags = PostTag::limit(15)->get();

        return view('blog.show', compact('post', 'toc', 'relatedPosts', 'comments', 'schemaJson', 'categories', 'tags'));
    }

    /**
     * Hiển thị bài viết theo danh mục (Category page)
     * URL: /blog/category/{slug}
     */
    public function category($slug)
    {
        $category = PostCategory::where('slug', $slug)->firstOrFail();

        $posts = Post::published()
            ->where('category_id', $category->id)
            ->with(['author', 'category'])
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        $categories = PostCategory::where('is_visible', true)->get();

        return view('blog.category', compact('category', 'posts', 'categories'));
    }

    /**
     * Hiển thị bài viết theo thẻ tag (Tag page)
     * URL: /blog/tag/{slug}
     */
    public function tag($slug)
    {
        $tag = PostTag::where('slug', $slug)->firstOrFail();

        $posts = $tag->posts()
            ->published()
            ->with(['author', 'category'])
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        $categories = PostCategory::where('is_visible', true)->get();

        return view('blog.tag', compact('tag', 'posts', 'categories'));
    }

    /**
     * Tìm kiếm bài viết (Search page)
     * URL: /blog/search
     */
    public function search(Request $request)
    {
        // Lọc sạch các thẻ HTML và khoảng trắng để tránh lỗi cú pháp hoặc XSS khi tìm kiếm
        $query = trim(strip_tags($request->input('q')));

        $posts = Post::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('summary', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->with(['author', 'category'])
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        $categories = PostCategory::where('is_visible', true)->get();

        return view('blog.search', compact('posts', 'query', 'categories'));
    }

    /**
     * Thích bài viết qua AJAX
     * URL: /blog/{id}/like
     */
    public function like(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $ip = $request->ip();
        $userId = auth()->id();

        // Kiểm tra xem đã like chưa
        $existingLike = PostLike::where('post_id', $post->id)
            ->where(function ($q) use ($userId, $ip) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('ip_address', $ip);
                }
            })->first();

        if ($existingLike) {
            // Nếu đã like rồi thì Unlike
            $existingLike->delete();
            Post::withoutTimestamps(function () use ($post): void {
                $post->decrement('like_count');
            });
            return response()->json([
                'success' => true,
                'liked' => false,
                'likes' => $post->like_count
            ]);
        }

        // Nếu chưa like thì lưu lượt like mới
        PostLike::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'ip_address' => $ip,
        ]);

        Post::withoutTimestamps(function () use ($post): void {
            $post->increment('like_count');
        });

        return response()->json([
            'success' => true,
            'liked' => true,
            'likes' => $post->like_count
        ]);
    }

    /**
     * Ghi nhận lượt chia sẻ qua AJAX
     * URL: /blog/{id}/share
     */
    public function share(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $request->merge([
            'platform' => strtolower(trim((string) $request->input('platform', 'copy'))),
        ]);
        $validated = $request->validate([
            'platform' => 'required|string|max:32|in:facebook,twitter,telegram,copy',
        ]);

        $throttleKey = 'blog-share:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 20)) {
            return response()->json([
                'success' => false,
                'message' => __('Bạn đã ghi nhận quá nhiều lượt chia sẻ. Vui lòng thử lại sau.'),
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        PostShare::create([
            'post_id' => $post->id,
            'platform' => $validated['platform'],
            'ip_address' => $request->ip(),
        ]);

        Post::withoutTimestamps(function () use ($post): void {
            $post->increment('share_count');
        });

        return response()->json([
            'success' => true,
            'shares' => $post->share_count
        ]);
    }

    /**
     * Gửi bình luận bài viết (POST)
     * URL: /blog/{id}/comment
     */
    public function comment(Request $request, $id)
    {
        // Chống spam bình luận: Giới hạn tối đa 5 bình luận/phút từ cùng 1 IP
        $throttleKey = 'blog-comment:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Bạn đã bình luận quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        // Kiểm tra cấu hình có bật bình luận không
        $commentSetting = Setting::where('key', 'blog_comments_enabled')->first();
        if ($commentSetting && $commentSetting->value === '0') {
            return back()->with('error', 'Chức năng bình luận hiện đang tắt.');
        }

        $post = Post::findOrFail($id);
        if (!$post->is_commentable) {
            return back()->with('error', 'Bài viết này không cho phép bình luận.');
        }

        // Quy tắc validate
        $rules = [
            'content' => 'required|string|max:1000',
        ];

        // Nếu khách vãng lai bình luận, yêu cầu nhập tên và email
        if (!auth()->check()) {
            // Kiểm tra cấu hình có cho phép khách bình luận không
            $guestSetting = Setting::where('key', 'blog_guest_comments_enabled')->first();
            if ($guestSetting && $guestSetting->value === '0') {
                return back()->with('error', 'Chỉ thành viên mới được quyền bình luận.');
            }
            $rules['author_name'] = 'required|string|max:100';
            $rules['author_email'] = 'required|email|max:100';
        }

        $request->validate($rules);

        // Lọc sạch nội dung bình luận của người dùng, loại bỏ hoàn toàn các thẻ HTML để chống tấn công XSS
        $content = trim(strip_tags($request->input('content')));
        $blacklistSetting = Setting::where('key', 'blog_comment_blacklist')->first();
        if ($blacklistSetting && !empty($blacklistSetting->value)) {
            $words = explode(',', $blacklistSetting->value);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word) && stripos($content, $word) !== false) {
                    return back()->with('error', 'Nội dung bình luận chứa từ ngữ không phù hợp.');
                }
            }
        }

        // Xác định trạng thái duyệt bình luận mặc định
        // Thành viên đăng nhập có thể được duyệt thẳng, khách vãng lai chờ duyệt
        $status = 'pending';
        if (auth()->check()) {
            $status = 'approved'; // Thành viên duyệt luôn
        }

        // Chỉ chấp nhận parent_id nếu bình luận cha thực sự tồn tại và thuộc đúng bài viết này,
        // tránh việc tạo bình luận trả lời trỏ tới bình luận của bài khác qua thao tác chỉnh sửa request.
        $parentId = null;
        if ($request->filled('parent_id')) {
            $parentId = Comment::where('id', intval($request->input('parent_id')))
                ->where('post_id', $post->id)
                ->value('id');
        }

        Comment::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'author_name' => auth()->check() ? auth()->user()->name : trim(strip_tags($request->input('author_name'))),
            'author_email' => auth()->check() ? auth()->user()->email : trim(strip_tags($request->input('author_email'))),
            'content' => $content,
            'status' => $status,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Cập nhật số comment nếu được duyệt luôn
        if ($status === 'approved') {
            Post::withoutTimestamps(function () use ($post): void {
                $post->increment('comment_count');
            });
        }

        $message = $status === 'approved' ? 'Bình luận của bạn đã được đăng thành công!' : 'Bình luận của bạn đã gửi và đang chờ quản trị viên duyệt.';
        return back()->with('success', $message);
    }

    /**
     * Tạo mục lục tự động (TOC) từ nội dung bài viết.
     * Giải mã các thực thể HTML (HTML entities) để hiển thị tiêu đề tiếng Việt chuẩn ở giao diện,
     * đồng thời giữ lại tiêu đề thô (raw title) phục vụ cho việc khớp Regex chèn ID anchor trong view.
     */
    private function generateTableOfContents($content)
    {
        $toc = [];
        // Sử dụng biểu thức chính quy để tìm các thẻ h2, h3 trong nội dung bài viết
        preg_match_all('/<h([2-3])([^>]*)>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = $match[1];
            $rawTitle = strip_tags($match[3]);
            // Giải mã thực thể HTML (ví dụ: &Agrave; -> À) để hiển thị chữ tiếng Việt có dấu chuẩn trên giao diện mục lục
            $title = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Tạo slug từ tiêu đề đã được giải mã để làm anchor link thân thiện
            $slug = Str::slug($title);
            
            $toc[] = [
                'level' => $level,
                'title' => $title,
                'raw_title' => $rawTitle, // Lưu tiêu đề thô chứa HTML Entities để khớp chính xác trong View
                'slug' => $slug
            ];
        }

        return $toc;
    }

    /**
     * Tạo mã JSON-LD Schema Article cho bài viết để tối ưu hiển thị trên Google Search
     */
    private function generateArticleSchema(Post $post)
    {
        // Lấy logo website từ Settings thay vì hardcode path
        $siteLogo = Setting::getVal('site_logo');
        $siteName = Setting::getVal('site_name', config('app.name', 'Cashback Shopee'));
        $siteOgImage = Setting::getVal('site_og_image');

        // Tính số từ trong nội dung bài viết (quan trọng cho Google đánh giá content quality)
        $wordCount = str_word_count(strip_tags($post->content ?? ''));

        // Lấy danh sách tags của bài viết làm keywords
        $keywords = $post->tags->pluck('name')->toArray();

        // URL chính thức của bài viết
        $articleUrl = route('blog.show', $post->slug);

        // Dùng cùng nguồn ảnh với Open Graph và chuẩn hóa đường dẫn nội bộ thành URL tuyệt đối.
        $articleImage = $post->thumbnail ?: $siteOgImage ?: asset('assets/images/default-thumbnail.jpg');
        $articleImageUrl = filter_var($articleImage, FILTER_VALIDATE_URL)
            ? $articleImage
            : asset(ltrim($articleImage, '/'));

        // Schema chính: Article + BreadcrumbList + WebSite (chuẩn Google Rich Results)
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                // 1. Article Schema: giúp Google hiển thị Rich Snippet cho bài viết
                [
                    '@type' => 'Article',
                    '@id' => $articleUrl . '#article',
                    'isPartOf' => ['@id' => $articleUrl . '#webpage'],
                    'headline' => $post->title,
                    'description' => $post->summary ?: Str::limit(strip_tags($post->content), 160, '...'),
                    'image' => [
                        '@type' => 'ImageObject',
                        'url' => $articleImageUrl,
                    ],
                    'datePublished' => $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String(),
                    'dateModified' => $post->updated_at->toIso8601String(),
                    'wordCount' => $wordCount,
                    'commentCount' => $post->comment_count ?? 0,
                    'author' => [
                        '@type' => 'Person',
                        'name' => $post->author->name ?? 'Admin',
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        '@id' => url('/') . '#organization',
                        'name' => $siteName,
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => !empty($siteLogo) ? $siteLogo : asset('assets/images/logo.png'),
                        ],
                    ],
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id' => $articleUrl . '#webpage',
                    ],
                    'articleSection' => $post->category->name ?? 'Tin tức',
                    'keywords' => !empty($keywords) ? implode(', ', $keywords) : null,
                    'inLanguage' => 'vi-VN',
                ],
                // 2. WebPage Schema: liên kết bài viết với website tổng
                [
                    '@type' => 'WebPage',
                    '@id' => $articleUrl . '#webpage',
                    'url' => $articleUrl,
                    'name' => $post->title . ' - ' . $siteName,
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'datePublished' => $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String(),
                    'dateModified' => $post->updated_at->toIso8601String(),
                    'inLanguage' => 'vi-VN',
                ],
                // 3. BreadcrumbList Schema: giúp Google hiển thị breadcrumb trong kết quả tìm kiếm
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => array_values(array_filter([
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Trang chủ',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Blog',
                            'item' => route('blog.index'),
                        ],
                        // Thêm danh mục vào breadcrumb nếu bài viết có danh mục
                        $post->category ? [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $post->category->name,
                            'item' => route('blog.category', $post->category->slug),
                        ] : null,
                        [
                            '@type' => 'ListItem',
                            'position' => $post->category ? 4 : 3,
                            'name' => $post->title,
                            'item' => $articleUrl,
                        ],
                    ])),
                ],
            ],
        ];

        // Loại bỏ các key null để JSON sạch hơn (ví dụ keywords khi không có tag)
        $articleIndex = 0;
        foreach ($schema['@graph'][$articleIndex] as $key => $value) {
            if ($value === null) {
                unset($schema['@graph'][$articleIndex][$key]);
            }
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Tạo RSS Feed cho Blog
     * URL: /blog/feed
     */
    public function feed()
    {
        $posts = Post::published()
            ->with(['author', 'category'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $lastBuildDate = $posts->max(fn (Post $post) => $post->updated_at) ?? now();

        return response()->view('blog.feed', compact('posts', 'lastBuildDate'), 200)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}

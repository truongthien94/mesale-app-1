<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Trang tĩnh (Điều khoản, Chính sách bảo mật, Giới thiệu...).
 * Cần thiết để App hiển thị nội dung pháp lý — yêu cầu bắt buộc khi duyệt App Store / Google Play.
 */
class PageController extends ApiController
{
    /**
     * GET /api/v1/openapi/pages
     * Danh sách các trang đã xuất bản (chỉ metadata, không kèm nội dung).
     */
    public function index(Request $request): JsonResponse
    {
        $pages = Page::published()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['slug', 'title', 'sort_order', 'updated_at']);

        return $this->ok([
            'items' => $pages->map(fn (Page $p) => [
                'slug' => $p->slug,
                'title' => $p->title,
                'updated_at' => optional($p->updated_at)->toIso8601String(),
            ]),
        ]);
    }

    /**
     * GET /api/v1/openapi/pages/{slug}
     * Chi tiết nội dung một trang tĩnh đã xuất bản.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->first();
        if (!$page) {
            return $this->fail(__('Không tìm thấy trang.'), 404, 'PAGE_NOT_FOUND');
        }

        return $this->ok([
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'meta_description' => $page->meta_description,
            'updated_at' => optional($page->updated_at)->toIso8601String(),
        ]);
    }
}

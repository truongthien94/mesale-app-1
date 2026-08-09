<?php

namespace App\Http\Controllers;

use App\Models\Page;

/**
 * Controller hiển thị các trang nội dung tĩnh (Page) cho người dùng frontend.
 * Chỉ hiển thị các trang có trạng thái đã xuất bản (published).
 */
class PageController extends Controller
{
    /**
     * Hiển thị trang nội dung tĩnh theo slug.
     * Trả về 404 nếu slug không tồn tại hoặc trang chưa được xuất bản.
     */
    public function show(string $slug)
    {
        // Chỉ lấy trang đã xuất bản, trả về 404 nếu không tìm thấy
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return view('pages.show', compact('page'));
    }
}

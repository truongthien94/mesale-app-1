<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\SitemapRefresher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controller quản lý các trang nội dung tĩnh (Page) từ Admin Panel.
 * Hỗ trợ CRUD hoàn chỉnh: thêm, xem danh sách, chỉnh sửa, xóa.
 */
class PageController extends Controller
{
    /**
     * Hiển thị danh sách tất cả các trang nội dung.
     */
    public function index()
    {
        // Sắp xếp theo thứ tự sort_order tăng dần, sau đó theo ngày tạo mới nhất
        $pages = Page::orderBy('sort_order')->orderByDesc('created_at')->get();
        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Hiển thị form tạo trang mới.
     */
    public function create()
    {
        $aiEnabled = Setting::getVal('ai_status', '0') === '1';
        return view('admin.pages.create', compact('aiEnabled'));
    }

    /**
     * Lưu trang mới vào database.
     */
    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:pages,slug',
            'content'          => 'nullable|string',
            'status'           => 'required|in:published,draft',
            'sort_order'       => 'nullable|integer|min:0',
            'meta_description' => 'nullable|string|max:300',
            'is_noindex'       => 'nullable|boolean',
        ], [
            'title.required' => 'Tiêu đề trang không được để trống.',
            'slug.unique'    => 'Đường dẫn (slug) này đã tồn tại, vui lòng chọn slug khác.',
        ]);

        // Tự động sinh slug từ tiêu đề nếu Admin không tự nhập
        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);

        // Đảm bảo slug là duy nhất bằng cách thêm hậu tố số nếu trùng
        $originalSlug = $slug;
        $counter = 1;
        $query = Page::query();
        while ($query->clone()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        // Tạo bản ghi trang mới kèm theo tùy chọn SEO noindex
        $page = Page::create([
            'title'            => $request->title,
            'slug'             => $slug,
            'content'          => $request->content,
            'status'           => $request->status,
            'sort_order'       => $request->sort_order ?? 0,
            'meta_description' => $request->meta_description,
            'is_noindex'       => $request->boolean('is_noindex'),
        ]);

        // Ghi lại lịch sử hoạt động của Admin
        ActivityLog::log("Tạo trang mới: {$page->title}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.pages.index')->with('success', 'Tạo trang "' . $page->title . '" thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa trang.
     */
    public function edit(Page $page)
    {
        $aiEnabled = Setting::getVal('ai_status', '0') === '1';
        return view('admin.pages.edit', compact('page', 'aiEnabled'));
    }

    /**
     * Cập nhật trang đã tồn tại.
     */
    public function update(Request $request, Page $page)
    {
        // Xác thực dữ liệu, loại trừ slug hiện tại khỏi ràng buộc unique
        $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'content'          => 'nullable|string',
            'status'           => 'required|in:published,draft',
            'sort_order'       => 'nullable|integer|min:0',
            'meta_description' => 'nullable|string|max:300',
            'is_noindex'       => 'nullable|boolean',
        ], [
            'title.required' => 'Tiêu đề trang không được để trống.',
            'slug.unique'    => 'Đường dẫn (slug) này đã tồn tại, vui lòng chọn slug khác.',
        ]);

        // Tự động sinh slug từ tiêu đề nếu Admin không tự nhập
        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);

        // Đảm bảo slug là duy nhất (loại trừ bản ghi hiện tại)
        $originalSlug = $slug;
        $counter = 1;
        $query = Page::query();
        while ($query->clone()->where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        // Cập nhật thông tin trang bao gồm tùy chọn SEO noindex
        $page->update([
            'title'            => $request->title,
            'slug'             => $slug,
            'content'          => $request->content,
            'status'           => $request->status,
            'sort_order'       => $request->sort_order ?? 0,
            'meta_description' => $request->meta_description,
            'is_noindex'       => $request->boolean('is_noindex'),
        ]);

        // Ghi lại lịch sử hoạt động
        ActivityLog::log("Cập nhật trang: {$page->title}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.pages.index')->with('success', 'Cập nhật trang "' . $page->title . '" thành công!');
    }

    /**
     * API AJAX: Tạo nhanh nội dung trang (HTML + meta description) bằng AI.
     * Trả về JSON { success: bool, content?: string, meta_description?: string, message?: string }.
     */
    public function generateAi(Request $request)
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
            'prompt.required' => __('Vui lòng mô tả nội dung trang bạn muốn tạo.'),
        ]);

        $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $title    = $validated['title'] ?? '';
        $tone     = $validated['tone'] ?? 'chuyen-nghiep';

        $toneMap = [
            'than-thien'    => 'thân thiện, gần gũi',
            'chuyen-nghiep' => 'chuyên nghiệp, trang trọng',
            'phap-ly'       => 'pháp lý, rõ ràng, chặt chẽ (phù hợp điều khoản/chính sách)',
            'marketing'     => 'hấp dẫn, lôi cuốn (phù hợp giới thiệu/landing)',
        ];
        $toneText = $toneMap[$tone] ?? 'chuyên nghiệp, trang trọng';

        // System prompt: yêu cầu AI trả về đúng định dạng JSON để dễ phân tích
        $systemPrompt = <<<SYS
Bạn là chuyên gia biên tập nội dung web cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: viết nội dung cho một TRANG TĨNH (static page) bằng TIẾNG VIỆT theo yêu cầu của người dùng (ví dụ: Điều khoản dịch vụ, Chính sách bảo mật, Giới thiệu, Câu hỏi thường gặp...).

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"content": "mã HTML nội dung trang", "meta_description": "mô tả SEO ngắn gọn dưới 160 ký tự"}.
- Trường "content" là HTML sạch dùng các thẻ ngữ nghĩa: <h2>, <h3>, <p>, <ul>, <li>, <strong>, <a>... KHÔNG kèm thẻ <html>, <head><meta charset="utf-8">, <body>.
- KHÔNG dùng inline CSS rườm rà, KHÔNG dùng <script>, để giao diện website tự định kiểu.
- Nội dung mạch lạc, chia mục rõ ràng, đầy đủ và phù hợp ngữ cảnh website hoàn tiền Shopee.
- Giọng điệu: {$toneText}.
SYS;

        $userPrompt = $title !== ''
            ? "Tiêu đề trang: {$title}\nYêu cầu nội dung: {$validated['prompt']}"
            : "Yêu cầu nội dung: {$validated['prompt']}";

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ], [
                'max_tokens'  => 4000,
                'temperature' => 0.7,
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
            $content  = null;
            $metaDesc = null;

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $content  = $parsed['content'] ?? null;
                    $metaDesc = $parsed['meta_description'] ?? null;
                }
            }

            // Nếu không phân tích được JSON, coi toàn bộ phản hồi là nội dung HTML
            if (empty($content)) {
                $content = $clean;
            }

            ActivityLog::log(__('Tạo nội dung trang bằng AI'), auth()->id());

            return response()->json([
                'success'          => true,
                'content'          => $content,
                'meta_description' => $metaDesc,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Xóa trang khỏi hệ thống.
     */
    public function destroy(Page $page)
    {
        $pageTitle = $page->title;
        $page->delete();

        // Ghi log hoạt động
        ActivityLog::log("Xóa trang: {$pageTitle}", auth()->id());
        SitemapRefresher::afterResponse();

        return redirect()->route('admin.pages.index')->with('success', 'Xóa trang "' . $pageTitle . '" thành công!');
    }
}

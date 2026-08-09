<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Hiển thị danh sách tất cả các menu, được nhóm theo vị trí hiển thị.
     */
    public function index(Request $request)
    {
        $query = Menu::with('parent');

        // Lọc theo vị trí nếu có
        if ($request->has('position') && !empty($request->position)) {
            $query->where('position', $request->position);
        }

        // Lọc theo từ khóa tìm kiếm nếu có
        if ($request->has('search') && !empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $menus = $query->orderBy('position')
            ->orderBy('order', 'asc')
            ->get();

        // Lấy danh sách menu cha (parent_id = null) để làm danh sách lựa chọn trong form
        $parentMenus = Menu::whereNull('parent_id')
            ->orderBy('position')
            ->orderBy('order', 'asc')
            ->get();

        // Lấy danh sách tất cả các vị trí menu hiện có để làm bộ lọc nhanh
        $existingPositions = Menu::select('position')
            ->distinct()
            ->pluck('position')
            ->toArray();

        // Một số vị trí gợi ý mặc định, bao gồm cả bottom (menu di động dưới đáy)
        $suggestedPositions = array_unique(array_merge(['header', 'footer', 'user_dropdown', 'bottom'], $existingPositions));

        // Lấy danh sách trang tĩnh đang hoạt động để làm gợi ý liên kết nhanh cho Admin
        $staticPages = Page::published()
            ->orderBy('title', 'asc')
            ->get(['title', 'slug']);

        // Lấy danh sách danh mục tin tức đang hoạt động để làm gợi ý liên kết nhanh cho Admin
        $blogCategories = PostCategory::where('is_visible', true)
            ->orderBy('name', 'asc')
            ->get(['name', 'slug']);

        // Lấy danh sách 50 bài viết tin tức mới nhất đã xuất bản để làm gợi ý liên kết nhanh cho Admin
        $blogPosts = Post::published()
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get(['title', 'slug']);

        return view('admin.menus.index', compact(
            'menus', 
            'parentMenus', 
            'suggestedPositions', 
            'staticPages', 
            'blogCategories', 
            'blogPosts'
        ));
    }

    /**
     * Lưu menu mới vào cơ sở dữ liệu.
     */
    public function store(Request $request)
    {
        // Xác thực dữ liệu nhập vào
        $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'position' => 'required|string|max:100',
            'target' => 'required|string|in:_self,_blank',
            'order' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'parent_id' => 'nullable|exists:menus,id',
            'auth_rule' => 'required|string|in:all,auth,guest',
        ], [
            'title.required' => 'Tiêu đề menu không được để trống.',
            'position.required' => 'Vị trí hiển thị không được để trống.',
            'target.in' => 'Cách mở liên kết không hợp lệ.',
            'order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'parent_id.exists' => 'Menu cha đã chọn không hợp lệ.',
            'auth_rule.in' => 'Quyền hiển thị không hợp lệ.',
        ]);

        // Chuẩn hóa vị trí (chuyển sang chữ thường, không khoảng trắng để quản lý đồng bộ)
        $position = strtolower(trim($request->position));

        $menu = Menu::create([
            'title' => $request->title,
            'url' => $request->url,
            'icon' => $request->icon,
            'position' => $position,
            'target' => $request->target,
            'order' => $request->order,
            'status' => $request->status,
            'parent_id' => $request->parent_id,
            'auth_rule' => $request->auth_rule,
        ]);

        // Ghi log hoạt động của admin
        ActivityLog::log("Tạo menu mới: {$menu->title} tại vị trí [{$menu->position}]", auth()->id());

        return redirect()->route('admin.menus.index', $request->query())->with('success', 'Thêm menu mới thành công!');
    }

    /**
     * Cập nhật thông tin của một menu.
     */
    public function update(Request $request, Menu $menu)
    {
        // Xác thực dữ liệu nhập vào
        $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'position' => 'required|string|max:100',
            'target' => 'required|string|in:_self,_blank',
            'order' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'parent_id' => 'nullable|exists:menus,id',
            'auth_rule' => 'required|string|in:all,auth,guest',
        ], [
            'title.required' => 'Tiêu đề menu không được để trống.',
            'position.required' => 'Vị trí hiển thị không được để trống.',
            'target.in' => 'Cách mở liên kết không hợp lệ.',
            'order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'parent_id.exists' => 'Menu cha đã chọn không hợp lệ.',
            'auth_rule.in' => 'Quyền hiển thị không hợp lệ.',
        ]);

        // Ràng buộc bảo mật: Không được chọn chính mình làm menu cha
        if ($request->parent_id && $request->parent_id == $menu->id) {
            return back()->with('error', 'Không thể chọn chính menu này làm menu cha của nó.');
        }

        // Chuẩn hóa vị trí
        $position = strtolower(trim($request->position));

        $menu->update([
            'title' => $request->title,
            'url' => $request->url,
            'icon' => $request->icon,
            'position' => $position,
            'target' => $request->target,
            'order' => $request->order,
            'status' => $request->status,
            'parent_id' => $request->parent_id,
            'auth_rule' => $request->auth_rule,
        ]);

        // Ghi log hoạt động
        ActivityLog::log("Cập nhật menu ID #{$menu->id}: {$menu->title}", auth()->id());

        return redirect()->route('admin.menus.index', $request->query())->with('success', 'Cập nhật menu thành công!');
    }

    /**
     * Xóa một menu khỏi cơ sở dữ liệu.
     */
    public function destroy(Request $request, Menu $menu)
    {
        $title = $menu->title;
        $menu->delete();

        // Ghi log hoạt động
        ActivityLog::log("Xóa menu: {$title}", auth()->id());

        return redirect()->route('admin.menus.index', $request->query())->with('success', 'Xóa menu thành công!');
    }

    /**
     * Cập nhật nhanh thứ tự menu bằng AJAX kéo thả hoặc nhập liệu.
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:menus,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->orders as $item) {
            Menu::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật thứ tự menu thành công!'
        ]);
    }

    /**
     * Bật tắt trạng thái menu nhanh bằng AJAX checkbox.
     */
    public function toggleStatus(Request $request, Menu $menu)
    {
        $newStatus = !$menu->status;
        $menu->update(['status' => $newStatus]);

        // Ghi log hoạt động của admin
        $statusStr = $newStatus ? 'Đang bật' : 'Tạm ẩn';
        \App\Models\ActivityLog::log("Thay đổi trạng thái menu #{$menu->id} ({$menu->title}) sang: {$statusStr}", auth()->id());

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật trạng thái menu thành công!',
            'menu_status' => $newStatus
        ]);
    }
}

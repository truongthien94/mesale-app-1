<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Hiển thị danh sách Banner quảng cáo.
     */
    public function index()
    {
        $banners = Banner::orderBy('order', 'asc')->get();
        return view('admin.banners.index', compact('banners'));
    }

    /**
     * Tạo thêm Banner mới.
     * Hỗ trợ nhập URL trực tiếp hoặc chọn ảnh từ thư viện CKFinder.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // Cho phép cả URL tuyệt đối lẫn đường dẫn tương đối từ thư viện CKFinder
            'image_url' => 'required|string|max:2048',
            'link' => 'nullable|url',
            'title' => 'nullable|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['order'] = $request->input('order', 0);

        Banner::create($data);

        ActivityLog::log("Tạo mới Banner quảng cáo", auth()->id());

        return back()->with('success', 'Thêm mới banner quảng cáo thành công!');
    }

    /**
     * Cập nhật thông tin Banner.
     * Hỗ trợ nhập URL trực tiếp hoặc chọn ảnh từ thư viện CKFinder.
     */
    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            // Cho phép cả URL tuyệt đối lẫn đường dẫn tương đối từ thư viện CKFinder
            'image_url' => 'required|string|max:2048',
            'link' => 'nullable|url',
            'title' => 'nullable|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['order'] = $request->input('order', 0);

        $banner->update($data);

        ActivityLog::log("Cập nhật Banner quảng cáo ID {$banner->id}", auth()->id());

        return back()->with('success', 'Cập nhật banner quảng cáo thành công!');
    }

    /**
     * Xoá bỏ Banner khỏi hệ thống.
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();

        ActivityLog::log("Xoá bỏ Banner quảng cáo ID {$banner->id}", auth()->id());

        return back()->with('success', 'Xoá banner quảng cáo thành công!');
    }
}

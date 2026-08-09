<?php

namespace App\Http\Controllers;

use App\Models\SavedProduct;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedProductController extends Controller
{
    /**
     * Hiển thị danh sách sản phẩm đã lưu của thành viên.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Lấy danh sách sản phẩm đã lưu, phân trang 12 sản phẩm mỗi trang
        $savedProducts = SavedProduct::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('dashboard.saved_products', compact('savedProducts'));
    }

    /**
     * Lưu sản phẩm mới để mua sau (AJAX POST).
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate dữ liệu đầu vào, bổ sung kiểm tra trường platform
        $request->validate([
            'platform' => 'nullable|string|max:50',
            'name' => 'required|string|max:500',
            'image' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cashback_amount' => 'required|numeric|min:0',
            'affiliate_url' => 'required|string',
            'product_url' => 'nullable|string',
        ]);

        // Quy tắc nghiệp vụ: Không lưu lại các sản phẩm khi lấy link bị thiếu thông tin giá bán
        // (giá bằng 0 là sản phẩm dạng ước tính do API sàn lỗi hoặc phải fallback sang cào HTML)
        if ((float)$request->price <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('Sản phẩm này chưa lấy được đầy đủ thông tin giá bán nên không thể lưu lại. Vui lòng thử lấy link lại sau.')
            ]);
        }

        // Kiểm tra xem sản phẩm đã được lưu chưa (tránh trùng lặp)
        $exists = SavedProduct::where('user_id', $user->id)
            ->where(function ($query) use ($request) {
                if ($request->filled('product_url')) {
                    $query->where('product_url', $request->product_url);
                } else {
                    $query->where('affiliate_url', $request->affiliate_url);
                }
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => __('Sản phẩm này đã có trong danh sách lưu trữ của bạn.')
            ]);
        }

        // Tạo bản ghi sản phẩm đã lưu, mặc định nền tảng là shopee nếu để trống
        $savedProduct = SavedProduct::create([
            'user_id' => $user->id,
            'platform' => $request->input('platform', 'shopee'),
            'name' => $request->name,
            'image' => $request->image,
            'price' => $request->price,
            'cashback_amount' => $request->cashback_amount,
            'affiliate_url' => $request->affiliate_url,
            'product_url' => $request->product_url,
        ]);

        // Ghi log hoạt động
        ActivityLog::log("Lưu sản phẩm để mua sau: " . $savedProduct->name, $user->id);

        return response()->json([
            'success' => true,
            'message' => __('Đã lưu sản phẩm thành công!')
        ]);
    }

    /**
     * Xóa sản phẩm khỏi danh sách đã lưu.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $savedProduct = SavedProduct::where('user_id', $user->id)->findOrFail($id);
        
        // Ghi log hoạt động trước khi xóa
        ActivityLog::log("Xóa sản phẩm đã lưu: " . $savedProduct->name, $user->id);
        
        $savedProduct->delete();

        return redirect()->back()->with('success', __('Đã xóa sản phẩm khỏi danh sách lưu trữ thành công.'));
    }
}

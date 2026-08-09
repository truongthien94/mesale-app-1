<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CurrencyController extends Controller
{
    /**
     * Hiển thị danh sách các tiền tệ trong Admin Panel.
     * Sắp xếp theo trạng thái hoạt động và tiền tệ mặc định lên đầu.
     */
    public function index()
    {
        $currencies = Currency::orderBy('is_default', 'desc')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.currencies.index', compact('currencies'));
    }

    /**
     * Lưu tiền tệ mới vào hệ thống.
     * Thực hiện validate dữ liệu đầu vào tiếng Việt và xóa cache tiền tệ.
     */
    public function store(Request $request)
    {
        // 1. Xác thực dữ liệu đầu vào
        $request->validate([
            'name' => 'required|string|max:100|unique:currencies,name',
            'code' => 'required|string|max:10|alpha|unique:currencies,code',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'symbol_position' => 'required|in:before,after',
        ], [
            'name.required' => 'Tên tiền tệ không được để trống.',
            'name.unique' => 'Tên tiền tệ này đã tồn tại.',
            'code.required' => 'Mã tiền tệ không được để trống.',
            'code.unique' => 'Mã tiền tệ này đã được sử dụng.',
            'code.alpha' => 'Mã tiền tệ chỉ được chứa các chữ cái (VND, USD, EUR...).',
            'symbol.required' => 'Ký hiệu tiền tệ không được để trống.',
            'exchange_rate.required' => 'Tỷ giá quy đổi không được để trống.',
            'exchange_rate.numeric' => 'Tỷ giá quy đổi phải là một số hợp lệ.',
            'exchange_rate.min' => 'Tỷ giá quy đổi phải lớn hơn 0.',
            'symbol_position.required' => 'Vị trí ký hiệu không được để trống.',
            'symbol_position.in' => 'Vị trí ký hiệu phải là "trước" hoặc "sau" số tiền.',
        ]);

        $data = [
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'symbol' => $request->symbol,
            'exchange_rate' => $request->exchange_rate,
            'symbol_position' => $request->symbol_position,
            'is_active' => $request->has('is_active'),
            'is_default' => $request->has('is_default'),
        ];

        // 2. Tạo tiền tệ mới trong CSDL
        $currency = Currency::create($data);

        // 3. Xóa các cache liên quan để hệ thống cập nhật tức thì
        Cache::forget('system_default_currency');
        Cache::forget('active_currencies_list');

        // 4. Ghi nhật ký hoạt động của quản trị viên
        ActivityLog::log("Thêm mới tiền tệ thành công: {$currency->name} ({$currency->code}) với tỷ giá {$currency->exchange_rate}", auth()->id());

        return redirect()->route('admin.currencies.index')->with('success', 'Thêm mới tiền tệ thành công!');
    }

    /**
     * Cập nhật thông tin tiền tệ hiện có.
     * Thực hiện validate dữ liệu đầu vào và xử lý các ràng buộc logic của tiền tệ mặc định.
     */
    public function update(Request $request, Currency $currency)
    {
        // 1. Xác thực dữ liệu cập nhật
        $request->validate([
            'name' => 'required|string|max:100|unique:currencies,name,' . $currency->id,
            'code' => 'required|string|max:10|alpha|unique:currencies,code,' . $currency->id,
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'symbol_position' => 'required|in:before,after',
        ], [
            'name.required' => 'Tên tiền tệ không được để trống.',
            'name.unique' => 'Tên tiền tệ này đã tồn tại.',
            'code.required' => 'Mã tiền tệ không được để trống.',
            'code.unique' => 'Mã tiền tệ này đã được sử dụng.',
            'code.alpha' => 'Mã tiền tệ chỉ được chứa các chữ cái.',
            'symbol.required' => 'Ký hiệu tiền tệ không được để trống.',
            'exchange_rate.required' => 'Tỷ giá quy đổi không được để trống.',
            'exchange_rate.numeric' => 'Tỷ giá quy đổi phải là một số hợp lệ.',
            'exchange_rate.min' => 'Tỷ giá quy đổi phải lớn hơn 0.',
            'symbol_position.required' => 'Vị trí ký hiệu không được để trống.',
            'symbol_position.in' => 'Vị trí ký hiệu không hợp lệ.',
        ]);

        $data = [
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'symbol' => $request->symbol,
            'exchange_rate' => $request->exchange_rate,
            'symbol_position' => $request->symbol_position,
            'is_active' => $request->has('is_active'),
            'is_default' => $request->has('is_default'),
        ];

        // 2. Bảo vệ hệ thống: Không cho phép vô hiệu hóa hoặc tắt trạng thái mặc định của tiền tệ mặc định duy nhất
        if ($currency->is_default) {
            $data['is_active'] = true;
            $data['is_default'] = true;
            $data['exchange_rate'] = 1.0000; // Tiền gốc luôn bằng 1
        }

        // 3. Cập nhật Model trong DB
        $currency->update($data);

        // 4. Xóa cache hệ thống liên quan
        Cache::forget('system_default_currency');
        Cache::forget('active_currencies_list');

        // 5. Ghi nhật ký hoạt động
        ActivityLog::log("Cập nhật tiền tệ ID {$currency->id}: {$currency->name} ({$currency->code})", auth()->id());

        return redirect()->route('admin.currencies.index')->with('success', 'Cập nhật tiền tệ thành công!');
    }

    /**
     * Xóa tiền tệ khỏi hệ thống.
     * Ngăn chặn việc xóa tiền tệ mặc định.
     */
    public function destroy(Currency $currency)
    {
        // Ràng buộc bảo mật: Không được xóa tiền tệ mặc định để tránh lỗi tính toán
        if ($currency->is_default) {
            return redirect()->route('admin.currencies.index')->with('error', 'Không thể xóa tiền tệ mặc định của hệ thống.');
        }

        $currencyName = $currency->name;
        $currencyCode = $currency->code;
        $currency->delete();

        // Xóa cache hệ thống liên quan
        Cache::forget('system_default_currency');
        Cache::forget('active_currencies_list');

        // Ghi nhật ký hoạt động
        ActivityLog::log("Xóa tiền tệ khỏi hệ thống: {$currencyName} ({$currencyCode})", auth()->id());

        return redirect()->route('admin.currencies.index')->with('success', 'Xóa tiền tệ thành công!');
    }
}

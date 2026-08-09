<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    // Bản đồ phân nhóm các quyền hạn tiếng Việt trực quan phục vụ giao diện chọn quyền
    public static array $permissionsGroup = [
        'Hệ thống & Cấu hình' => [
            'view_dashboard'       => 'Xem bảng điều khiển (Dashboard)',
            'view_system_status'   => 'Xem trạng thái hệ thống',
            'manage_system_cleanup'=> 'Dọn dẹp & Sao lưu hệ thống',
            'manage_settings'      => 'Cấu hình hệ thống',
            'manage_custom_code'   => 'Quản lý mã nhúng tùy biến (Custom JS/CSS)',
            'manage_roles'         => 'Quản lý vai trò & Phân quyền (Roles)',
            'manage_languages'     => 'Quản lý đa ngôn ngữ (Languages)',
            'manage_currencies'    => 'Quản lý đa tiền tệ (Currencies)',
            'manage_media'         => 'Quản lý Media (CKFinder)',
            'manage_tools'         => 'Quản lý Công cụ (Tools)',
            'manage_appearance'    => 'Tùy chỉnh giao diện trang chủ',
            'manage_updates'       => 'Cập nhật phiên bản',
        ],
        'Quản lý thành viên' => [
            'manage_users'             => 'Xem & Chỉnh sửa thành viên, Gửi thông báo',
            'delete_users'             => 'Xóa thành viên',
            'manage_email_campaigns'   => 'Quản lý Email Campaign (Email Marketing)',
        ],
        'Sản phẩm & Giao dịch' => [
            'manage_products'    => 'Quản lý sản phẩm',
            'manage_cashbacks'   => 'Quản lý đơn hoàn tiền',
            'manage_withdrawals' => 'Quản lý rút tiền',
            'manage_gifts'       => 'Quản lý quà tặng & đổi thưởng',
            'manage_gift_codes'  => 'Quản lý Giftcode (mã nhập nhận thưởng)',
            'manage_tasks'       => 'Quản lý Nhiệm vụ nhận thưởng',
            'manage_coupons'     => 'Quản lý Mã giảm giá (thêm/sửa/xóa thủ công)',
        ],
        'Nhật ký & Logs' => [
            'view_balance_logs'         => 'Xem nhật ký biến động số dư',
            'view_activity_logs'        => 'Xem nhật ký hoạt động của thành viên',
            'view_checkin_logs'         => 'Xem nhật ký điểm danh',
            'view_referral_commissions' => 'Xem nhật ký hoa hồng Affiliate',
            'view_cashback_click_logs'  => 'Xem nhật ký lượt click hoàn tiền',
            'view_short_links'          => 'Xem nhật ký liên kết rút gọn (Short Links)',
            'view_email_queue_logs'     => 'Xem hàng đợi email hệ thống',
            'view_telegram_queue_logs'  => 'Xem hàng đợi Telegram hệ thống',
            'view_notification_logs'    => 'Xem nhật ký thông báo đã gửi cho user',
            'view_saved_products'       => 'Xem sản phẩm đã lưu của User',
            'view_api_logs'             => 'Xem nhật ký gọi API',
            'view_system_logs'          => 'Xem nhật ký lỗi hệ thống',
        ],
        'Quản lý nội dung' => [
            'manage_blog'        => 'Quản lý Blog CMS',
            'manage_pages'       => 'Quản lý Page nội dung tĩnh',
            'manage_menus'       => 'Quản lý Menu hệ thống',
        ],
        'Quản lý Bot' => [
            'manage_bots' => 'Quản lý Bot Zalo & Telegram (cấu hình, xem tin nhắn)',
        ]
    ];

    public static array $permissionsMap = [
        'view_dashboard'            => 'Xem bảng điều khiển (Dashboard)',
        'view_system_status'        => 'Xem trạng thái hệ thống',
        'manage_system_cleanup'     => 'Dọn dẹp & Sao lưu hệ thống',
        'manage_settings'           => 'Cấu hình hệ thống',
        'manage_custom_code'        => 'Quản lý mã nhúng tùy biến (Custom JS/CSS)',
        'manage_users'              => 'Xem & Chỉnh sửa thành viên, Gửi thông báo',
        'delete_users'             => 'Xóa thành viên',
        'manage_email_campaigns'   => 'Quản lý Email Campaign (Email Marketing)',
        'manage_products'           => 'Quản lý sản phẩm',
        'manage_cashbacks'          => 'Quản lý đơn hoàn tiền',
        'manage_withdrawals'        => 'Quản lý rút tiền',
        'manage_gifts'              => 'Quản lý quà tặng & đổi thưởng',
        'manage_gift_codes'         => 'Quản lý Giftcode (mã nhập nhận thưởng)',
        'manage_tasks'              => 'Quản lý Nhiệm vụ nhận thưởng',
        'manage_coupons'            => 'Quản lý Mã giảm giá (thêm/sửa/xóa thủ công)',
        'manage_roles'              => 'Quản lý vai trò (Roles)',
        'manage_languages'          => 'Quản lý đa ngôn ngữ (Languages)',
        'manage_currencies'         => 'Quản lý đa tiền tệ (Currencies)',
        'view_balance_logs'         => 'Xem nhật ký số dư',
        'view_activity_logs'        => 'Xem nhật ký hoạt động',
        'view_checkin_logs'         => 'Xem nhật ký điểm danh',
        'view_referral_commissions' => 'Xem nhật ký hoa hồng Affiliate',
        'view_cashback_click_logs'  => 'Xem nhật ký lượt click hoàn tiền',
        'view_short_links'          => 'Xem nhật ký liên kết rút gọn',
        'view_email_queue_logs'     => 'Xem hàng đợi email',
        'view_telegram_queue_logs'  => 'Xem hàng đợi Telegram',
        'view_notification_logs'    => 'Xem nhật ký thông báo đã gửi cho user',
        'view_saved_products'       => 'Xem sản phẩm đã lưu của User',
        'view_api_logs'             => 'Xem nhật ký gọi API',
        'view_system_logs'          => 'Xem nhật ký lỗi hệ thống',
        'manage_blog'           => 'Quản lý Blog CMS',
        'manage_pages'          => 'Quản lý Page nội dung tĩnh',
        'manage_menus'          => 'Quản lý Menu hệ thống',
        'manage_media'          => 'Quản lý Media (CKFinder)',
        'manage_tools'          => 'Quản lý Công cụ (Tools)',
        'manage_appearance'     => 'Tùy chỉnh giao diện trang chủ',
        'manage_updates'        => 'Cập nhật phiên bản',
        'manage_bots'           => 'Quản lý Bot Zalo & Telegram',
    ];

    /**
     * Hiển thị danh sách vai trò phân quyền.
     */
    public function index()
    {
        // Lấy danh sách kèm theo số lượng user đang được gán vai trò này
        $roles = Role::withCount('users')->get();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Hiển thị giao diện tạo mới vai trò.
     */
    public function create()
    {
        $permissions = self::$permissionsGroup;
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Lưu trữ vai trò mới vào database.
     */
    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array'
        ], [
            'name.required' => 'Tên vai trò không được để trống.',
            'name.unique' => 'Tên vai trò này đã tồn tại trong hệ thống.'
        ]);

        // Tạo bản ghi vai trò mới
        $role = Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'permissions' => $request->permissions ?? []
        ]);

        // Ghi log hoạt động
        ActivityLog::log("Tạo vai trò mới: {$role->name}", auth()->id());

        return redirect()->route('admin.roles.index')->with('success', 'Thêm vai trò mới thành công!');
    }

    /**
     * Giao diện chỉnh sửa vai trò.
     */
    public function edit(Role $role)
    {
        $permissions = self::$permissionsGroup;
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Cập nhật thông tin vai trò.
     */
    public function update(Request $request, Role $role)
    {
        // Xác thực dữ liệu
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array'
        ], [
            'name.required' => 'Tên vai trò không được để trống.',
            'name.unique' => 'Tên vai trò này đã tồn tại trong hệ thống.'
        ]);

        // Cập nhật thông tin và danh sách quyền
        $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'permissions' => $request->permissions ?? []
        ]);

        // Ghi log
        ActivityLog::log("Cập nhật vai trò: {$role->name}", auth()->id());

        return redirect()->route('admin.roles.index')->with('success', 'Cập nhật vai trò thành công!');
    }

    /**
     * Xóa vai trò khỏi hệ thống.
     */
    public function destroy(Role $role)
    {
        // Ràng buộc bảo mật: Không xóa vai trò nếu đang có tài khoản admin được gán vai trò này
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Không thể xóa vai trò này vì đang có tài khoản admin sử dụng.');
        }

        $roleName = $role->name;
        $role->delete();

        // Ghi log
        ActivityLog::log("Xóa vai trò: {$roleName}", auth()->id());

        return redirect()->route('admin.roles.index')->with('success', 'Xóa vai trò thành công!');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends Model
{
    use HasFactory;

    // Danh sách các cột được phép nạp dữ liệu hàng loạt
    protected $fillable = [
        'name',
        'code',
        'flag',
        'is_active',
        'is_default',
        'order'
    ];

    // Ép kiểu dữ liệu các trường boolean
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    /**
     * Boot function để xử lý các ràng buộc nghiệp vụ tự động.
     * Đảm bảo hệ thống luôn chỉ có tối đa một ngôn ngữ mặc định.
     */
    protected static function boot()
    {
        parent::boot();

        // Trước khi lưu bản ghi
        static::saving(function ($language) {
            // Nếu ngôn ngữ này được thiết lập làm mặc định (is_default = true)
            if ($language->is_default) {
                // Chuyển tất cả các ngôn ngữ khác về không mặc định
                static::where('id', '!=', $language->id)->update(['is_default' => false]);
            }
        });

        // Sau khi xóa bản ghi
        static::deleted(function ($language) {
            // Nếu ngôn ngữ bị xóa là ngôn ngữ mặc định, tự động chỉ định ngôn ngữ hoạt động đầu tiên khác làm mặc định
            if ($language->is_default) {
                $nextDefault = static::where('is_active', true)->first();
                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true]);
                }
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory;

    // Danh sách các cột được phép nạp dữ liệu hàng loạt
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'exchange_rate',
        'symbol_position',
        'is_active',
        'is_default'
    ];

    // Ép kiểu dữ liệu các trường
    protected $casts = [
        'exchange_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    /**
     * Boot function để xử lý các ràng buộc nghiệp vụ tự động.
     * Đảm bảo hệ thống luôn chỉ có tối đa một tiền tệ mặc định.
     */
    protected static function boot()
    {
        parent::boot();

        // Trước khi lưu bản ghi
        static::saving(function ($currency) {
            // Nếu tiền tệ này được thiết lập làm mặc định (is_default = true)
            if ($currency->is_default) {
                // Chuyển tất cả các tiền tệ khác về không mặc định
                static::where('id', '!=', $currency->id)->update(['is_default' => false]);
            }
        });

        // Sau khi xóa bản ghi
        static::deleted(function ($currency) {
            // Nếu tiền tệ bị xóa là tiền tệ mặc định, tự động chỉ định tiền tệ hoạt động đầu tiên khác làm mặc định
            if ($currency->is_default) {
                $nextDefault = static::where('is_active', true)->first();
                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true]);
                }
            }
        });
    }
}

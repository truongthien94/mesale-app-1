<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model đại diện cho một "block" trong trình dựng trang (Page Builder) kiểu WordPress.
 * Mỗi block là một khối nội dung độc lập (Hero, Timeline, Điểm nổi bật, Gallery, HTML...)
 * có thể kéo-thả sắp xếp thứ tự, bật/tắt và chỉnh sửa nội dung riêng biệt qua trang Appearance.
 */
class PageBlock extends Model
{
    protected $fillable = [
        'page',
        'type',
        'name',
        'settings',
        'sort_order',
        'enabled',
    ];

    protected $casts = [
        'settings' => 'array',
        'enabled'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Lấy danh sách block của một trang, đã sắp xếp theo thứ tự hiển thị.
     */
    public static function forPage(string $page = 'home')
    {
        return static::where('page', $page)->orderBy('sort_order')->get();
    }
}

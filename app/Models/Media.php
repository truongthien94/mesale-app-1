<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    // Chỉ định tên bảng rõ ràng (vì Laravel tự động nhận diện dạng số nhiều nhưng đảm bảo độ chuẩn)
    protected $table = 'media';

    // Các trường được phép gán dữ liệu hàng loạt
    protected $fillable = [
        'filename',
        'path',
        'folder',
        'size',
        'extension',
        'mime_type',
        'user_id'
    ];

    /**
     * Mối quan hệ với người dùng tải lên (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper lấy URL tuyệt đối của tệp tin media
     */
    public function getUrlAttribute()
    {
        return asset($this->path);
    }
}

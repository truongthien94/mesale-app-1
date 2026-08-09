<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Cho phép gán hàng loạt các trường
    protected $fillable = [
        'name',
        'slug',
        'permissions'
    ];

    /**
     * Cast trường permissions (JSON từ database) thành kiểu array trong PHP để thao tác dễ dàng.
     */
    protected $casts = [
        'permissions' => 'array'
    ];

    /**
     * Mối quan hệ một-nhiều: Một vai trò phân quyền (Role) có thể được gán cho nhiều tài khoản (User).
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}

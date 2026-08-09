<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    use HasFactory;

    // Chỉ định tên bảng dạng số nhiều do quy tắc đặt tên Laravel
    protected $table = 'seo_metas';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'schema_data',
    ];

    protected $casts = [
        'schema_data' => 'array', // Tự động ép cấu trúc JSON Schema thành dạng mảng PHP
    ];

    /**
     * Mối quan hệ đa hình đại diện (Polymorphic Relation)
     * Trả về model thực tế liên kết (Post, PostCategory hoặc PostTag)
     */
    public function seoable()
    {
        return $this->morphTo();
    }
}

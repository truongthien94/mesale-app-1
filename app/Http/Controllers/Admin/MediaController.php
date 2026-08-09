<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Hiển thị giao diện quản lý hình ảnh và tệp tin (Media).
     * Sử dụng CKFinder để duyệt và upload hình ảnh.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Trả về giao diện quản lý Media của Admin
        return view('admin.media.index');
    }
}

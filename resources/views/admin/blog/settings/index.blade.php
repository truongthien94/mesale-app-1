@extends('layouts.admin')

@section('title', 'Cấu Hình Blog CMS - Trang Quản Trị')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cấu Hình Blog CMS</h1>
        <p class="text-sm text-gray-500">Cấu hình các tham số SEO, hoạt động bình luận và tích hợp mã script cho phân hệ Blog.</p>
    </div>

    <!-- Settings Form -->
    <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm p-6">
        <form action="{{ route('admin.blog.settings.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- PHÂN KHU 1: THÔNG TIN & TRẠNG THÁI BLOG -->
            <div class="border-b border-gray-150 dark:border-slate-800 pb-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                    Thông tin & Hoạt động Blog
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Tên hiển thị Blog (Blog Title)</label>
                        <input type="text" 
                               name="blog_title" 
                               value="{{ $settings['blog_title'] ?? 'Blog Tin Tức & Khuyến Mãi' }}" 
                               placeholder="Nhập tiêu đề Blog..." 
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">
                        <span class="text-[10px] text-gray-400 mt-1 block">Tên hiển thị chính trên thanh tiêu đề trang danh mục Blog.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2.5">Trạng thái phân hệ Blog</label>
                        <div x-data="{ enabled: {{ ($settings['blog_enabled'] ?? '1') == '1' ? 'true' : 'false' }} }" class="flex items-center gap-3 mt-1.5">
                            <input type="hidden" name="blog_enabled" :value="enabled ? '1' : '0'">
                            <button type="button" 
                                    @click="enabled = !enabled" 
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="enabled ? 'bg-shopee' : 'bg-gray-200 dark:bg-slate-850'">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                      :class="enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-xs font-bold transition-colors duration-200" :class="enabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-slate-500'" x-text="enabled ? 'Đang hoạt động' : 'Tạm dừng hoạt động'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Số bài viết mỗi trang</label>
                        <input type="number" 
                               name="blog_posts_per_page" 
                               value="{{ $settings['blog_posts_per_page'] ?? '9' }}" 
                               min="1" 
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Mô tả SEO của Blog (Meta Description)</label>
                        <textarea name="blog_description" 
                                  rows="2" 
                                  placeholder="Nhập mô tả tóm tắt Blog phục vụ SEO..." 
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">{{ $settings['blog_description'] ?? 'Cập nhật tin tức khuyến mãi Shopee, kinh nghiệm mua sắm hoàn tiền thông minh.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- PHÂN KHU 2: CẤU HÌNH BÌNH LUẬN -->
            <div class="border-b border-gray-150 dark:border-slate-800 pb-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-4 h-4 text-shopee"></i>
                    Thiết lập Bình luận
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Tính năng bình luận</label>
                        <select name="blog_comments_enabled" class="w-full px-3 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">
                            <option value="1" {{ ($settings['blog_comments_enabled'] ?? '1') == '1' ? 'selected' : '' }}>Cho phép bình luận tự do</option>
                            <option value="0" {{ ($settings['blog_comments_enabled'] ?? '1') == '0' ? 'selected' : '' }}>Khóa chức năng bình luận</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Bình luận của khách vãng lai</label>
                        <select name="blog_guest_comments_enabled" class="w-full px-3 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">
                            <option value="1" {{ ($settings['blog_guest_comments_enabled'] ?? '1') == '1' ? 'selected' : '' }}>Khách vãng lai được bình luận</option>
                            <option value="0" {{ ($settings['blog_guest_comments_enabled'] ?? '1') == '0' ? 'selected' : '' }}>Yêu cầu đăng nhập tài khoản</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Từ khóa bị chặn (Blacklist)</label>
                        <textarea name="blog_comment_blacklist" 
                                  rows="2" 
                                  placeholder="Ví dụ: tu_tuc, scam, lua_dao, spam" 
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">{{ $settings['blog_comment_blacklist'] ?? '' }}</textarea>
                        <span class="text-[10px] text-gray-400 mt-1 block">Các từ khóa bị chặn cách nhau bằng dấu phẩy (,). Hệ thống sẽ tự động chặn các bình luận chứa những từ khóa này.</span>
                    </div>
                </div>
            </div>

            <!-- PHÂN KHU 3: MÃ NHÚNG TÙY CHỈNH -->
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="code" class="w-4 h-4 text-shopee"></i>
                    Mã nhúng tuỳ chỉnh (Code Injection)
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Mã nhúng Header (Header Code Injection)</label>
                        <textarea name="blog_code_injection_header" 
                                  rows="3" 
                                  placeholder="Chèn thẻ <script> hoặc <style> tùy chỉnh vào header trang Blog..." 
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white font-mono text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">{{ $settings['blog_code_injection_header'] ?? '' }}</textarea>
                        <span class="text-[10px] text-gray-400 mt-1 block">Mã này sẽ chỉ được tải khi người dùng truy cập các trang thuộc phân hệ Blog (VD: Google Tag Manager, Facebook Pixel).</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Mã nhúng Footer (Footer Code Injection)</label>
                        <textarea name="blog_code_injection_footer" 
                                  rows="3" 
                                  placeholder="Chèn thẻ <script> hoặc mã chat bên thứ ba vào chân trang Blog..." 
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white font-mono text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">{{ $settings['blog_code_injection_footer'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-4 border-t border-gray-50 dark:border-slate-850 flex justify-end gap-3">
                <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-md transition-all">
                    <i data-lucide="check" class="w-4.5 h-4.5"></i>
                    Lưu cấu hình
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', __('Gửi Thông Báo Hệ Thống') . ' - ' . $siteName)

@section('content')
<!-- Khởi tạo AlpineJS scope để quản lý ẩn/hiện trường nhập Email của người nhận -->
<div class="space-y-6" x-data="notificationForm()">
    <!-- Tiêu đề và mô tả chức năng -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ __('Gửi Thông Báo Hệ Thống') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Gửi tin nhắn thông báo vào hộp thư của tất cả hoặc một thành viên cụ thể') }}</p>
        </div>
    </div>

    <!-- Hiển thị thông báo thành công từ Session -->
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-2xl bg-green-50 border border-green-200 flex items-center gap-2 dark:bg-green-950/20 dark:border-green-900 dark:text-green-400">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Cột trái: Danh sách lịch sử thông báo đã gửi (7 phần) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 dark:border-slate-800 pb-4">
                <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-shopee"></i>
                    {{ __('Lịch sử gửi thông báo') }}
                </h3>
            </div>

            <!-- Bộ lọc tìm kiếm thông báo sử dụng phương thức GET truyền thống để đồng bộ hệ thống -->
            <form action="{{ route('admin.notifications.index') }}" method="GET" class="bg-gray-55/70 dark:bg-slate-950/40 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Ô tìm kiếm từ khóa (Tiêu đề, nội dung hoặc email người nhận) -->
                    <div class="relative">
                        <input type="text" 
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="{{ __('Tìm tiêu đề, email...') }}" 
                               class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 dark:text-slate-200">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                    </div>

                    <!-- Lọc theo loại tin nhắn (Chung / Cá nhân) -->
                    <div>
                        <select name="type" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 dark:text-slate-200">
                            <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>{{ __('Tất cả loại tin') }}</option>
                            <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>{{ __('Thông báo chung') }}</option>
                            <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>{{ __('Thông báo cá nhân') }}</option>
                        </select>
                    </div>

                    <!-- Lọc theo trạng thái đọc (Đã xem / Chưa xem) -->
                    <div>
                        <select name="status" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 dark:text-slate-200">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>{{ __('Tất cả trạng thái') }}</option>
                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>{{ __('Đã xem') }}</option>
                            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>{{ __('Chưa xem') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Các nút tác vụ lọc -->
                <div class="flex justify-end gap-2">
                    @if(request('search') || (request('type') && request('type') !== 'all') || (request('status') && request('status') !== 'all'))
                        <a href="{{ route('admin.notifications.index') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-slate-400 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-750 transition-all">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            {{ __('Xóa bộ lọc') }}
                        </a>
                    @endif
                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-sm">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        {{ __('Lọc dữ liệu') }}
                    </button>
                </div>
            </form>
            
            <!-- Danh sách lịch sử thông báo render từ view partials -->
            <div id="history-list-container" class="transition-all duration-300">
                @include('admin.notifications.partials.history_list')
            </div>
        </div>

        <!-- Cột phải: Soạn thông báo mới (5 phần) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                    <i data-lucide="send-to-back" class="w-4 h-4 text-shopee"></i>
                    {{ __('Soạn thông báo mới') }}
                </h3>
                <button type="button" @click="openAiModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-lg transition-all shadow-sm shadow-violet-500/20">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    {{ __('Tạo bằng AI') }}
                </button>
            </div>
            
            <form action="{{ route('admin.notifications.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Chọn đối tượng nhận thông báo (Tất cả / Một người dùng) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2 font-semibold">{{ __('Đối tượng nhận thông báo') }}</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="border p-2.5 rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none transition-all dark:border-slate-700"
                               :class="recipientType === 'all' ? 'border-shopee bg-orange-50/20 font-bold dark:bg-orange-950/10 dark:border-shopee' : 'border-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800'">
                            <input type="radio" name="send_to" value="all" x-model="recipientType" class="text-shopee focus:ring-shopee">
                            <span class="text-xs text-gray-700 dark:text-slate-300">{{ __('Tất cả thành viên') }}</span>
                        </label>
                        <label class="border p-2.5 rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none transition-all dark:border-slate-700"
                               :class="recipientType === 'single' ? 'border-shopee bg-orange-50/20 font-bold dark:bg-orange-950/10 dark:border-shopee' : 'border-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800'">
                            <input type="radio" name="send_to" value="single" x-model="recipientType" class="text-shopee focus:ring-shopee">
                            <span class="text-xs text-gray-700 dark:text-slate-300">{{ __('Một thành viên') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Địa chỉ Email người nhận (chỉ hiển thị khi chọn gửi cho một người) -->
                <div x-show="recipientType === 'single'" x-transition x-cloak class="space-y-1">
                    <label for="user_email" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Địa chỉ Email người nhận') }}</label>
                    <input type="email" 
                           name="user_email" 
                           id="user_email" 
                           value="{{ old('user_email') }}"
                           placeholder="{{ __('nhập-email-thanh-vien@example.com...') }}"
                           class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                    @error('user_email')
                        <p class="text-[10px] text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tiêu đề thông báo -->
                <div class="space-y-1">
                    <label for="title" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tiêu đề thông báo') }}</label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           required 
                           x-model="title"
                           placeholder="{{ __('Nhập tiêu đề...') }}"
                           class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                    @error('title')
                        <p class="text-[10px] text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nội dung chi tiết thông báo -->
                <div class="space-y-1">
                    <label for="content" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Nội dung chi tiết') }}</label>
                    <textarea name="content" 
                              id="content" 
                              required 
                              x-model="content"
                              placeholder="{{ __('Nhập nội dung thông báo...') }}" 
                              rows="5" 
                              class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200"></textarea>
                    @error('content')
                        <p class="text-[10px] text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tùy chọn gửi kèm email qua hệ thống hàng đợi SMTP -->
                <div class="p-3 bg-blue-50/50 dark:bg-blue-950/10 rounded-xl border border-blue-100 dark:border-blue-900/30" x-data="{ sendEmail: false }">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="send_email" 
                               value="1"
                               x-model="sendEmail"
                               class="mt-0.5 text-blue-600 focus:ring-blue-500 rounded border-gray-300 dark:bg-slate-800 dark:border-slate-700">
                        <div>
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 block">{{ __('Gửi kèm thông báo qua Email') }}</span>
                            <span class="text-[10px] text-gray-500 dark:text-slate-400 leading-relaxed block mt-0.5">
                                Email sẽ được đưa vào hàng đợi và gửi dần qua Cron Job (tránh quá tải SMTP server).
                            </span>
                        </div>
                    </label>
                    <!-- Cảnh báo khi gửi email số lượng lớn cho tất cả thành viên -->
                    <div x-show="sendEmail && recipientType === 'all'" x-transition x-cloak
                         class="mt-2.5 p-2 bg-amber-50 dark:bg-amber-950/20 rounded-lg border border-amber-200 dark:border-amber-900/30 flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5"></i>
                        <span class="text-[10px] text-amber-700 dark:text-amber-400 leading-relaxed">
                            Lưu ý: Hệ thống sẽ đưa email vào hàng đợi và gửi tuần tự (mỗi phút ~10 email) thông qua Cron Job.
                            Đảm bảo đã cấu hình SMTP và Cron Job chính xác trong <strong>Cấu hình hệ thống</strong>.
                        </span>
                    </div>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    {{ __('Gửi thông báo ngay') }}
                </button>
            </form>
        </div>

    </div>

    {{-- Teleport modal tạo nội dung bằng AI ra body để backdrop hiển thị full màn hình --}}
    <template x-teleport="body">
        <div x-show="showAiModal" x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-4"
             @keydown.escape.window="showAiModal = false">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAiModal = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                {{-- Lớp phủ loader khi AI đang xử lý: phủ toàn bộ modal với hiệu ứng xoay --}}
                <div x-show="aiLoading" x-cloak
                     class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-2xl">
                    <div class="w-10 h-10 border-[3px] border-violet-200 dark:border-violet-900 border-t-violet-500 rounded-full animate-spin"></div>
                    <p class="text-xs font-semibold text-violet-600 dark:text-violet-400 animate-pulse">{{ __('AI đang soạn nội dung...') }}</p>
                </div>
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-800 shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4 text-violet-500"></i>
                            {{ __('Tạo nội dung thông báo bằng AI') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ __('Mô tả ý tưởng, AI sẽ soạn tiêu đề và nội dung thông báo cho bạn') }}</p>
                    </div>
                    <button @click="showAiModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    {{-- Thông báo lỗi --}}
                    <div x-show="aiError" x-cloak class="p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 rounded-xl flex gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-red-600 dark:text-red-400 leading-relaxed" x-text="aiError"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Mô tả nội dung mong muốn') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="aiPrompt" rows="4"
                                  placeholder="{{ __('VD: Thông báo hoàn tiền shopee đã được cộng vào số dư ví, khuyên người dùng tích cực mua sắm...') }}"
                                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Giọng điệu') }}
                        </label>
                        <select x-model="aiTone"
                                class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                            <option value="than-thien">{{ __('Thân thiện, gần gũi') }}</option>
                            <option value="chuyen-nghiep">{{ __('Chuyên nghiệp, trang trọng') }}</option>
                            <option value="khan-cap">{{ __('Khẩn cấp, thúc đẩy hành động') }}</option>
                            <option value="hao-hung">{{ __('Hào hứng, nhiều cảm xúc') }}</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-2.5 p-3 border border-gray-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                        <input type="checkbox" x-model="aiWithSubject" class="text-violet-500 rounded focus:ring-violet-500">
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Tự động tạo cả tiêu đề thông báo') }}</span>
                    </label>

                    {{-- Chia sẻ mã giảm giá: AI sẽ chèn các mã được chọn vào thông báo --}}
                    <div class="rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                        <label class="flex items-center gap-2.5 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                            <input type="checkbox" x-model="aiShareCoupons" class="text-violet-500 rounded focus:ring-violet-500">
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i data-lucide="ticket-percent" class="w-3.5 h-3.5 text-violet-500"></i>
                                    {{ __('Chia sẻ mã giảm giá cho khách') }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500 block mt-0.5">{{ __('AI chèn các mã bạn chọn vào thông báo để nhắc khách nhớ tới website') }}</span>
                            </div>
                        </label>

                        <div x-show="aiShareCoupons" x-cloak class="border-t border-gray-100 dark:border-slate-800 p-3 space-y-2">
                            {{-- Không có mã nào khả dụng --}}
                            <template x-if="coupons.length === 0">
                                <p class="text-[11px] text-gray-400 dark:text-slate-500 italic text-center py-2">
                                    {{ __('Chưa có mã giảm giá nào còn hiệu lực. Hãy đồng bộ mã từ trang Mã giảm giá.') }}
                                </p>
                            </template>

                            <div x-show="coupons.length > 0" class="flex items-center justify-between">
                                <button type="button" @click="toggleAllCoupons()"
                                        class="text-[10px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                    <span x-text="selectedCoupons.length === coupons.length ? '{{ __('Bỏ chọn tất cả') }}' : '{{ __('Chọn tất cả') }}'"></span>
                                </button>
                                <span class="text-[10px] font-semibold text-gray-500 dark:text-slate-400"
                                      x-text="selectedCoupons.length + ' {{ __('mã đã chọn') }}'"></span>
                            </div>

                            <div x-show="coupons.length > 0" class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                <template x-for="c in coupons" :key="c.id">
                                    <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition-all"
                                           :class="selectedCoupons.includes(c.id) ? 'border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-950/20' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                                        <input type="checkbox" :value="c.id" x-model.number="selectedCoupons"
                                               class="mt-0.5 text-violet-500 rounded focus:ring-violet-500">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-mono font-bold text-violet-700 dark:text-violet-300 truncate" x-text="c.code"></p>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate" x-text="c.title"></p>
                                            <p class="text-[9px] text-gray-400 dark:text-slate-500" x-show="c.expired_at" x-text="'{{ __('HSD') }}: ' + c.expired_at"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Chia sẻ giftcode: AI sẽ chèn các mã quà tặng (cộng tiền vào ví) được chọn --}}
                    <div class="rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                        <label class="flex items-center gap-2.5 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                            <input type="checkbox" x-model="aiShareGiftCodes" class="text-violet-500 rounded focus:ring-violet-500">
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i data-lucide="gift" class="w-3.5 h-3.5 text-violet-500"></i>
                                    {{ __('Chia sẻ Giftcode cho khách') }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500 block mt-0.5">{{ __('AI chèn các mã quà tặng bạn chọn để khách nhập nhận tiền vào ví') }}</span>
                            </div>
                        </label>

                        <div x-show="aiShareGiftCodes" x-cloak class="border-t border-gray-100 dark:border-slate-800 p-3 space-y-2">
                            {{-- Không có giftcode nào khả dụng --}}
                            <template x-if="giftCodes.length === 0">
                                <p class="text-[11px] text-gray-400 dark:text-slate-500 italic text-center py-2">
                                    {{ __('Chưa có Giftcode nào còn hiệu lực. Hãy tạo mã ở trang Giftcode.') }}
                                </p>
                            </template>

                            <div x-show="giftCodes.length > 0" class="flex items-center justify-between">
                                <button type="button" @click="toggleAllGiftCodes()"
                                        class="text-[10px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                    <span x-text="selectedGiftCodes.length === giftCodes.length ? '{{ __('Bỏ chọn tất cả') }}' : '{{ __('Chọn tất cả') }}'"></span>
                                </button>
                                <span class="text-[10px] font-semibold text-gray-500 dark:text-slate-400"
                                      x-text="selectedGiftCodes.length + ' {{ __('mã đã chọn') }}'"></span>
                            </div>

                            <div x-show="giftCodes.length > 0" class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                <template x-for="g in giftCodes" :key="g.id">
                                    <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition-all"
                                           :class="selectedGiftCodes.includes(g.id) ? 'border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-950/20' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                                        <input type="checkbox" :value="g.id" x-model.number="selectedGiftCodes"
                                               class="mt-0.5 text-violet-500 rounded focus:ring-violet-500">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-mono font-bold text-violet-700 dark:text-violet-300 truncate" x-text="g.code"></p>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate">
                                                <span x-show="g.title" x-text="g.title + ' · '"></span>
                                                <span class="font-semibold text-green-600 dark:text-green-400" x-text="g.reward_label"></span>
                                            </p>
                                            <p class="text-[9px] text-gray-400 dark:text-slate-500" x-show="g.expires_at" x-text="'{{ __('HSD') }}: ' + g.expires_at"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-violet-50 dark:bg-violet-950/20 rounded-xl border border-violet-100 dark:border-violet-900/30 flex gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-violet-500 shrink-0 mt-0.5"></i>
                        <p class="text-[10px] text-violet-700 dark:text-violet-400 leading-relaxed">
                            {{ __('Nội dung hiện tại trong khung soạn thảo sẽ bị thay thế bằng kết quả AI tạo ra.') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-200 dark:border-slate-800 shrink-0">
                    <button @click="showAiModal = false" type="button"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-700 transition-all">
                        {{ __('Huỷ') }}
                    </button>
                    <button @click="generateWithAi()" type="button" :disabled="aiLoading"
                            class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-xl transition-all shadow-sm shadow-violet-500/20 disabled:opacity-60 disabled:cursor-not-allowed">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5" :class="{ 'animate-spin': aiLoading }"></i>
                        <span x-text="aiLoading ? '{{ __('Đang tạo...') }}' : '{{ __('Tạo nội dung') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@section('scripts')
<script>
function notificationForm() {
    return {
        recipientType: '{{ old('send_to', 'all') }}',
        title: @json(old('title', '')),
        content: @json(old('content', '')),

        // ===== Trạng thái tính năng tạo nội dung bằng AI =====
        aiEnabled: {{ $aiEnabled ? 'true' : 'false' }},
        showAiModal: false,
        aiPrompt: '',
        aiTone: 'than-thien',
        aiWithSubject: true,
        aiLoading: false,
        aiError: '',

        // Danh sách coupons & giftcodes từ backend
        coupons: @json($aiCoupons),
        giftCodes: @json($aiGiftCodes),
        aiShareCoupons: false,
        selectedCoupons: [],
        aiShareGiftCodes: false,
        selectedGiftCodes: [],

        openAiModal() {
            if (!this.aiEnabled) {
                alert("{{ __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong Cài đặt > AI.') }}");
                return;
            }
            this.aiError = '';
            this.showAiModal = true;
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },

        toggleAllCoupons() {
            if (this.selectedCoupons.length === this.coupons.length) {
                this.selectedCoupons = [];
            } else {
                this.selectedCoupons = this.coupons.map(c => c.id);
            }
        },

        toggleAllGiftCodes() {
            if (this.selectedGiftCodes.length === this.giftCodes.length) {
                this.selectedGiftCodes = [];
            } else {
                this.selectedGiftCodes = this.giftCodes.map(g => g.id);
            }
        },

        generateWithAi() {
            if (this.aiLoading) return;
            if (!this.aiPrompt.trim()) {
                this.aiError = "{{ __('Vui lòng mô tả nội dung thông báo bạn muốn tạo.') }}";
                return;
            }
            this.aiLoading = true;
            this.aiError = '';
            fetch('{{ route('admin.notifications.generate_ai') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    prompt: this.aiPrompt,
                    tone: this.aiTone,
                    with_subject: this.aiWithSubject,
                    coupon_ids: this.aiShareCoupons ? this.selectedCoupons : [],
                    giftcode_ids: this.aiShareGiftCodes ? this.selectedGiftCodes : [],
                })
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    this.aiError = data.message || "{{ __('Không thể tạo nội dung. Vui lòng thử lại.') }}";
                    return;
                }
                // Điền tiêu đề và nội dung thông báo nhận được
                if (this.aiWithSubject && data.title) {
                    this.title = data.title;
                }
                if (data.content) {
                    this.content = data.content;
                }
                this.showAiModal = false;
            })
            .catch(() => {
                this.aiError = "{{ __('Lỗi kết nối. Vui lòng thử lại.') }}";
            })
            .finally(() => { this.aiLoading = false; });
        }
    }
}
</script>
@endsection

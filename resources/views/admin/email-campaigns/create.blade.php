@extends('layouts.admin')

@section('title', __('Tạo Chiến Dịch Email') . ' - ' . $siteName)

@section('styles')
{{-- TinyMCE: trình soạn thảo trực quan (WYSIWYG) cho nội dung email --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // Mở cửa sổ elFinder để chọn ảnh từ thư viện media của hệ thống
    function openElfinderPopup(inputId) {
        var width = 900, height = 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        var url = '{{ url("elfinder/popup") }}/' + inputId;
        window.open(url, 'elfinderPicker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=no');
    }
    // Callback toàn cục được elFinder gọi sau khi người dùng chọn ảnh xong
    window.processSelectedFile = function(fileUrl, inputId) {
        if (inputId === 'tinymce_image' && window.tinymceFilePickerCallback) {
            window.tinymceFilePickerCallback(fileUrl, { alt: 'Ảnh email' });
            window.tinymceFilePickerCallback = null;
        }
    };
</script>
@endsection

@section('content')
<div class="space-y-6" x-data="campaignForm()">

    {{-- Tiêu đề --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.email_campaigns.index') }}"
           class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ __('Tạo Chiến Dịch Email Mới') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Soạn thảo và cấu hình chiến dịch email marketing') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.email_campaigns.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- ===== CỘT TRÁI: NỘI DUNG EMAIL ===== --}}
            <div class="lg:col-span-8 space-y-5">

                {{-- Thông tin cơ bản --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thông tin chiến dịch') }}
                    </h3>

                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Tên chiến dịch (chỉ dùng nội bộ)') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               placeholder="{{ __('VD: Khuyến mãi tháng 6 - Flash Sale...') }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        @error('name') <p class="text-[10px] text-red-500 font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Tiêu đề email (Subject)') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" x-model="subject" required
                               placeholder="{{ __('VD: 🎁 Ưu đãi đặc biệt dành riêng cho bạn!') }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        @error('subject') <p class="text-[10px] text-red-500 font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="from_name" class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                                {{ __('Tên người gửi') }}
                            </label>
                            <input type="text" id="from_name" name="from_name" value="{{ old('from_name', $siteName) }}"
                                   placeholder="{{ $siteName }}"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        </div>
                        <div>
                            <label for="from_email" class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                                {{ __('Email người gửi') }}
                            </label>
                            <input type="email" id="from_email" name="from_email" value="{{ old('from_email', $siteEmail) }}"
                                   placeholder="{{ $siteEmail ?: 'noreply@domain.com' }}"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        </div>
                    </div>
                </div>

                {{-- Soạn nội dung HTML --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2">
                            <i data-lucide="code-2" class="w-4 h-4 text-shopee"></i>
                            {{ __('Nội dung email (HTML)') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openAiModal()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-lg transition-all shadow-sm shadow-violet-500/20">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                {{ __('Tạo bằng AI') }}
                            </button>
                            <button type="button" @click="previewEmail()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 transition-all">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                {{ __('Xem trước') }}
                            </button>
                        </div>
                    </div>

                    {{-- Variables helper --}}
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/20 rounded-xl border border-amber-100 dark:border-amber-900/30">
                        <p class="text-xs font-bold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-1.5">
                            <i data-lucide="variable" class="w-3.5 h-3.5"></i>
                            {{ __('Biến có thể dùng trong nội dung:') }}
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @php $varBtnClass = 'inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono font-bold text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/30 rounded border border-amber-200 dark:border-amber-800/50 hover:bg-amber-200 dark:hover:bg-amber-800/40 transition-all cursor-pointer'; @endphp
                            <button type="button" @click="insertVariable('@{{name}}')" class="{{ $varBtnClass }}" title="{{ __('Tên thành viên') }}">@{{name}}</button>
                            <button type="button" @click="insertVariable('@{{email}}')" class="{{ $varBtnClass }}" title="{{ __('Email thành viên') }}">@{{email}}</button>
                            <button type="button" @click="insertVariable('@{{balance}}')" class="{{ $varBtnClass }}" title="{{ __('Số dư ví') }}">@{{balance}}</button>
                            <button type="button" @click="insertVariable('@{{referral_code}}')" class="{{ $varBtnClass }}" title="{{ __('Mã giới thiệu') }}">@{{referral_code}}</button>
                            <button type="button" @click="insertVariable('@{{site_name}}')" class="{{ $varBtnClass }}" title="{{ __('Tên website') }}">@{{site_name}}</button>
                        </div>
                    </div>

                    {{-- TinyMCE thay thế textarea này thành trình soạn thảo trực quan; vẫn giữ name="body" để gửi form --}}
                    <textarea id="body" name="body" rows="18" class="w-full">{{ old('body') }}</textarea>
                    @error('body') <p class="text-[10px] text-red-500 font-semibold mt-1">{{ $message }}</p> @enderror

                    {{-- Template mẫu --}}
                    <div class="pt-2 border-t border-gray-100 dark:border-slate-800">
                        <p class="text-xs font-bold text-gray-500 dark:text-slate-400 mb-2">{{ __('Chèn template mẫu:') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="loadTemplate('basic')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-all">
                                <i data-lucide="layout-template" class="w-3 h-3"></i>
                                {{ __('Template cơ bản') }}
                            </button>
                            <button type="button" @click="loadTemplate('promo')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold text-shopee bg-shopee/10 dark:bg-shopee/20 rounded-lg hover:bg-shopee/20 dark:hover:bg-shopee/30 transition-all">
                                <i data-lucide="tag" class="w-3 h-3"></i>
                                {{ __('Template khuyến mãi') }}
                            </button>
                            <button type="button" @click="loadTemplate('welcome')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-950/30 transition-all">
                                <i data-lucide="hand-wave" class="w-3 h-3"></i>
                                {{ __('Template chào mừng') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== CỘT PHẢI: SETTINGS ===== --}}
            <div class="lg:col-span-4 space-y-5">

                {{-- Đối tượng nhắm mục tiêu --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <i data-lucide="users" class="w-4 h-4 text-shopee"></i>
                        {{ __('Đối tượng nhắm mục tiêu') }}
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Nhóm người nhận') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="target_audience" x-model="audience" @change="fetchRecipientCount()"
                                class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                            @foreach($audiences as $val => $label)
                                <option value="{{ $val }}" {{ old('target_audience') === $val ? 'selected' : '' }}>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter bổ sung --}}
                    <div class="space-y-3 p-3 bg-gray-50 dark:bg-slate-950/30 rounded-xl border border-gray-100 dark:border-slate-800">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-wider">{{ __('Lọc bổ sung (tuỳ chọn)') }}</p>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-slate-400 mb-1">{{ __('Số dư tối thiểu (đ)') }}</label>
                            <input type="number" name="min_balance" value="{{ old('min_balance') }}" min="0" @change="fetchRecipientCount()"
                                   x-ref="minBalance"
                                   placeholder="0"
                                   class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 dark:text-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-slate-400 mb-1">{{ __('Đăng ký sau ngày') }}</label>
                            <input type="date" name="registered_after" value="{{ old('registered_after') }}" @change="fetchRecipientCount()"
                                   x-ref="registeredAfter"
                                   class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 dark:text-slate-200">
                        </div>
                    </div>

                    {{-- Ước tính người nhận --}}
                    <div class="flex items-center justify-between p-3 bg-shopee/5 dark:bg-shopee/10 rounded-xl border border-shopee/10 dark:border-shopee/20">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users-2" class="w-4 h-4 text-shopee"></i>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Ước tính người nhận') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span x-show="loadingCount" class="text-xs text-gray-400 animate-pulse">{{ __('Đang đếm...') }}</span>
                            <span x-show="!loadingCount" class="text-lg font-black text-shopee" x-text="recipientCount.toLocaleString()"></span>
                        </div>
                    </div>
                    <button type="button" @click="fetchRecipientCount()"
                            class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-700 transition-all">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="{ 'animate-spin': loadingCount }"></i>
                        {{ __('Cập nhật ước tính') }}
                    </button>
                </div>

                {{-- Lên lịch gửi --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <i data-lucide="calendar-clock" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thời gian gửi') }}
                    </h3>

                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-all"
                               :class="scheduleType === 'now' ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                            <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="text-shopee focus:ring-shopee">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Gửi ngay sau khi tạo') }}</p>
                                <p class="text-[10px] text-gray-500 dark:text-slate-400">{{ __('Email được đưa vào hàng đợi ngay lập tức') }}</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-all"
                               :class="scheduleType === 'scheduled' ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                            <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="text-shopee focus:ring-shopee">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Lưu nháp để gửi sau') }}</p>
                                <p class="text-[10px] text-gray-500 dark:text-slate-400">{{ __('Chiến dịch sẽ được lưu ở trạng thái Nháp') }}</p>
                            </div>
                        </label>
                    </div>

                    <div x-show="scheduleType === 'now'" x-cloak
                         class="p-3 bg-amber-50 dark:bg-amber-950/10 rounded-xl border border-amber-100 dark:border-amber-900/20 flex gap-2">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5"></i>
                        <p class="text-[10px] text-amber-700 dark:text-amber-400 leading-relaxed">
                            {{ __('Email sẽ được đưa vào hàng đợi ngay. Cron Job xử lý ~10 email/phút. Đảm bảo đã cấu hình SMTP đúng.') }}
                        </p>
                    </div>

                    <input type="hidden" name="send_now" value="1" x-show="scheduleType === 'now'">
                </div>

                {{-- Nút submit --}}
                <div class="space-y-2">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-5 py-3 text-sm font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shadow-shopee/20">
                        <i data-lucide="send" class="w-4 h-4" x-show="scheduleType === 'now'"></i>
                        <i data-lucide="save" class="w-4 h-4" x-show="scheduleType !== 'now'"></i>
                        <span x-text="scheduleType === 'now' ? '{{ __('Tạo & Gửi ngay') }}' : '{{ __('Lưu nháp') }}'"></span>
                    </button>
                    <a href="{{ route('admin.email_campaigns.index') }}"
                       class="w-full flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                        {{ __('Huỷ') }}
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Teleport modal xem trước email ra body để backdrop hiển thị full màn hình --}}
    <template x-teleport="body">
        <div x-show="showPreview" x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-4"
             @keydown.escape.window="showPreview = false">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPreview = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col z-10">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-800 shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                            <i data-lucide="eye" class="w-4 h-4 text-shopee"></i>
                            {{ __('Xem trước email') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ __('Nội dung render cho tài khoản Admin của bạn') }}</p>
                    </div>
                    <button @click="showPreview = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5">
                    <div class="mb-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                        <p class="text-xs text-gray-500 dark:text-slate-400"><span class="font-bold">Subject:</span> <span x-text="previewSubject"></span></p>
                    </div>
                    <div class="border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden">
                        <iframe id="preview-iframe" class="w-full min-h-[400px]" frameborder="0" srcdoc=""></iframe>
                    </div>
                </div>
            </div>
        </div>
    </template>

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
                            {{ __('Tạo nội dung email bằng AI') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ __('Mô tả ý tưởng, AI sẽ soạn email HTML hoàn chỉnh cho bạn') }}</p>
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
                                  placeholder="{{ __('VD: Thông báo chương trình hoàn tiền 50% nhân dịp 6/6, khuyến khích thành viên mua sắm và mời bạn bè...') }}"
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
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Tự động tạo cả tiêu đề email') }}</span>
                    </label>

                    {{-- Chia sẻ mã giảm giá: AI sẽ chèn các mã được chọn vào email --}}
                    <div class="rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                        <label class="flex items-center gap-2.5 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                            <input type="checkbox" x-model="aiShareCoupons" class="text-violet-500 rounded focus:ring-violet-500">
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i data-lucide="ticket-percent" class="w-3.5 h-3.5 text-violet-500"></i>
                                    {{ __('Chia sẻ mã giảm giá cho khách') }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ __('AI chèn các mã bạn chọn vào email để nhắc khách nhớ tới website') }}</span>
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
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i data-lucide="gift" class="w-3.5 h-3.5 text-violet-500"></i>
                                    {{ __('Chia sẻ Giftcode cho khách') }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ __('AI chèn các mã quà tặng bạn chọn để khách nhập nhận tiền vào ví') }}</span>
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

                    {{-- Chia sẻ nhiệm vụ: AI sẽ chèn các nhiệm vụ được chọn vào email --}}
                    <div class="rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                        <label class="flex items-center gap-2.5 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                            <input type="checkbox" x-model="aiShareTasks" class="text-violet-500 rounded focus:ring-violet-500">
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5 text-violet-500"></i>
                                    {{ __('Chia sẻ Nhiệm vụ cho khách') }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ __('AI chèn các nhiệm vụ bạn chọn để khách tham gia nhận tiền thưởng') }}</span>
                            </div>
                        </label>

                        <div x-show="aiShareTasks" x-cloak class="border-t border-gray-100 dark:border-slate-800 p-3 space-y-2">
                            {{-- Không có nhiệm vụ nào khả dụng --}}
                            <template x-if="tasks.length === 0">
                                <p class="text-[11px] text-gray-400 dark:text-slate-500 italic text-center py-2">
                                    {{ __('Chưa có Nhiệm vụ nào hoạt động. Hãy tạo nhiệm vụ ở trang Quản lý nhiệm vụ.') }}
                                </p>
                            </template>

                            <div x-show="tasks.length > 0" class="flex items-center justify-between">
                                <button type="button" @click="toggleAllTasks()"
                                        class="text-[10px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                    <span x-text="selectedTasks.length === tasks.length ? '{{ __('Bỏ chọn tất cả') }}' : '{{ __('Chọn tất cả') }}'"></span>
                                </button>
                                <span class="text-[10px] font-semibold text-gray-500 dark:text-slate-400"
                                      x-text="selectedTasks.length + ' {{ __('nhiệm vụ đã chọn') }}'"></span>
                            </div>

                            <div x-show="tasks.length > 0" class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                <template x-for="t in tasks" :key="t.id">
                                    <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition-all"
                                           :class="selectedTasks.includes(t.id) ? 'border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-950/20' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                                        <input type="checkbox" :value="t.id" x-model.number="selectedTasks"
                                               class="mt-0.5 text-violet-500 rounded focus:ring-violet-500">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-bold text-gray-800 dark:text-slate-200 truncate" x-text="t.title"></p>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate" x-text="t.description || '{{ __('Không có mô tả') }}'"></p>
                                            <p class="text-[9px] mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 font-semibold" x-text="t.type_label"></span>
                                                <span class="ml-1 text-green-600 dark:text-green-400 font-bold" x-text="'+' + t.reward_amount"></span>
                                            </p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-violet-50 dark:bg-violet-950/20 rounded-xl border border-violet-100 dark:border-violet-900/30 flex gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-violet-500 shrink-0 mt-0.5"></i>
                        <p class="text-[10px] text-violet-700 dark:text-violet-400 leading-relaxed">
                            {{ __('Nội dung hiện tại trong trình soạn thảo sẽ bị thay thế bằng kết quả AI tạo ra.') }}
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
function campaignForm() {
    return {
        audience: '{{ old('target_audience', 'all') }}',
        scheduleType: '{{ old('schedule_type', 'now') }}',
        recipientCount: 0,
        loadingCount: false,
        subject: @json(old('subject', '')),
        body: @json(old('body', '')),
        showPreview: false,
        previewSubject: '',
        editor: null,

        // ===== Trạng thái tính năng tạo nội dung bằng AI =====
        aiEnabled: {{ $aiEnabled ? 'true' : 'false' }},
        showAiModal: false,
        aiPrompt: '',
        aiTone: 'than-thien',
        aiWithSubject: true,
        aiLoading: false,
        aiError: '',
        // Chia sẻ mã giảm giá: danh sách mã khả dụng + các mã Admin chọn
        coupons: @json($aiCoupons),
        aiShareCoupons: false,
        selectedCoupons: [],
        // Chia sẻ giftcode: danh sách mã quà tặng khả dụng + các mã Admin chọn
        giftCodes: @json($aiGiftCodes),
        aiShareGiftCodes: false,
        selectedGiftCodes: [],
        // Chia sẻ nhiệm vụ: danh sách nhiệm vụ khả dụng + các nhiệm vụ Admin chọn
        tasks: @json($aiTasks),
        aiShareTasks: false,
        selectedTasks: [],

        init() {
            this.fetchRecipientCount();
            this.initEditor();
        },

        // Khởi tạo trình soạn thảo TinyMCE và đồng bộ nội dung 2 chiều với biến body của Alpine
        initEditor() {
            const self = this;
            const isDark = document.documentElement.classList.contains('dark');
            tinymce.init({
                selector: '#body',
                height: 480,
                menubar: false,
                language: 'vi',
                branding: false,
                promotion: false,
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                toolbar: 'undo redo | blocks fontsize | bold italic forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | removeformat code preview fullscreen',
                content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }',
                skin: isDark ? 'oxide-dark' : 'oxide',
                content_css: isDark ? 'dark' : 'default',
                // Giữ nguyên toàn bộ HTML/inline-style cho email, không để TinyMCE lọc bỏ
                valid_elements: '*[*]',
                verify_html: false,
                file_picker_callback: function (callback, value, meta) {
                    if (meta.filetype === 'image') {
                        window.tinymceFilePickerCallback = callback;
                        openElfinderPopup('tinymce_image');
                    }
                },
                setup: function (editor) {
                    self.editor = editor;
                    editor.on('init', function () {
                        if (self.body) editor.setContent(self.body);
                    });
                    // Mỗi khi nội dung thay đổi, đồng bộ ngược về biến body của Alpine
                    editor.on('change keyup undo redo SetContent', function () {
                        self.body = editor.getContent();
                    });
                }
            });
        },

        fetchRecipientCount() {
            this.loadingCount = true;
            const params = new URLSearchParams({
                target_audience: this.audience,
                min_balance: this.$refs.minBalance?.value || '',
                registered_after: this.$refs.registeredAfter?.value || '',
            });
            fetch('{{ route('admin.email_campaigns.count_recipients') }}?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => { this.recipientCount = data.count; this.loadingCount = false; })
            .catch(() => { this.loadingCount = false; });
        },

        insertVariable(variable) {
            // Chèn biến vào vị trí con trỏ trong trình soạn thảo TinyMCE
            if (this.editor) {
                this.editor.insertContent(variable);
                this.body = this.editor.getContent();
                this.editor.focus();
            }
        },

        loadTemplate(type) {
            const siteName = @json($siteName);
            const templates = {
                basic: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
  <div style="text-align: center; padding: 20px 0; border-bottom: 2px solid #ee4d2d;">
    <h1 style="color: #ee4d2d; margin: 0;">${siteName}</h1>
  </div>
  <div style="padding: 30px 0;">
    <p>Xin chào <strong>@{{name}}</strong>,</p>
    <p>Nội dung email của bạn ở đây...</p>
  </div>
  <div style="text-align: center; padding: 20px 0; border-top: 1px solid #eee; color: #999; font-size: 12px;">
    <p>© ${new Date().getFullYear()} ${siteName}. Mã giới thiệu của bạn: <strong>@{{referral_code}}</strong></p>
  </div>
</div>`,
                promo: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <div style="background: linear-gradient(135deg, #ee4d2d, #ff6b35); padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0;">
    <h1 style="color: white; margin: 0; font-size: 28px;">🎁 Ưu Đãi Đặc Biệt!</h1>
    <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">Dành riêng cho @{{name}}</p>
  </div>
  <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px; border: 1px solid #eee; border-top: none;">
    <p style="font-size: 16px;">Chào <strong>@{{name}}</strong>,</p>
    <p>Chúng tôi có một ưu đãi cực khủng dành riêng cho bạn!</p>
    <div style="background: #fff5f1; border: 2px dashed #ee4d2d; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0;">
      <p style="color: #ee4d2d; font-size: 24px; font-weight: bold; margin: 0;">50% HOÀN TIỀN</p>
      <p style="color: #666; font-size: 14px; margin: 8px 0 0;">Áp dụng cho đơn hàng tiếp theo của bạn</p>
    </div>
    <p>Số dư hiện tại của bạn: <strong>@{{balance}}</strong></p>
    <div style="text-align: center; margin: 25px 0;">
      <a href="#" style="background: #ee4d2d; color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px;">Mua Sắm Ngay →</a>
    </div>
    <p style="color: #999; font-size: 12px; text-align: center;">© ${new Date().getFullYear()} @{{site_name}}. Email: @{{email}}</p>
  </div>
</div>`,
                welcome: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <div style="background: #ee4d2d; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
    <h1 style="color: white; margin: 0;">👋 Chào Mừng!</h1>
  </div>
  <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px; border: 1px solid #eee; border-top: none;">
    <p>Xin chào <strong>@{{name}}</strong>,</p>
    <p>Chúng tôi rất vui khi bạn đã tham gia vào cộng đồng <strong>@{{site_name}}</strong>!</p>
    <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; margin: 20px 0;">
      <p style="margin: 0 0 8px;"><strong>📧 Email đăng ký:</strong> @{{email}}</p>
      <p style="margin: 0;"><strong>🔗 Mã giới thiệu của bạn:</strong> <span style="color: #ee4d2d; font-size: 18px; font-weight: bold;">@{{referral_code}}</span></p>
    </div>
    <p>Chia sẻ mã giới thiệu để nhận hoa hồng khi người khác đăng ký và mua sắm!</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="color: #999; font-size: 12px; text-align: center;">© ${new Date().getFullYear()} @{{site_name}}</p>
  </div>
</div>`,
            };
            if (confirm('{{ __('Bạn có muốn chèn template này? Nội dung hiện tại sẽ bị thay thế.') }}')) {
                const html = templates[type] || '';
                this.body = html;
                if (this.editor) this.editor.setContent(html);
            }
        },

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

        toggleAllTasks() {
            if (this.selectedTasks.length === this.tasks.length) {
                this.selectedTasks = [];
            } else {
                this.selectedTasks = this.tasks.map(t => t.id);
            }
        },

        generateWithAi() {
            if (this.aiLoading) return;
            if (!this.aiPrompt.trim()) {
                this.aiError = "{{ __('Vui lòng mô tả nội dung email bạn muốn tạo.') }}";
                return;
            }
            if (this.aiShareCoupons && this.selectedCoupons.length === 0) {
                this.aiError = "{{ __('Vui lòng chọn ít nhất một mã giảm giá để chia sẻ, hoặc tắt tùy chọn này.') }}";
                return;
            }
            if (this.aiShareGiftCodes && this.selectedGiftCodes.length === 0) {
                this.aiError = "{{ __('Vui lòng chọn ít nhất một Giftcode để chia sẻ, hoặc tắt tùy chọn này.') }}";
                return;
            }
            if (this.aiShareTasks && this.selectedTasks.length === 0) {
                this.aiError = "{{ __('Vui lòng chọn ít nhất một Nhiệm vụ để chia sẻ, hoặc tắt tùy chọn này.') }}";
                return;
            }
            this.aiLoading = true;
            this.aiError = '';
            fetch('{{ route('admin.email_campaigns.generate_ai') }}', {
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
                    task_ids: this.aiShareTasks ? this.selectedTasks : [],
                })
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    this.aiError = data.message || "{{ __('Không thể tạo nội dung. Vui lòng thử lại.') }}";
                    return;
                }
                // Điền tiêu đề (nếu có) và đổ body vào trình soạn thảo TinyMCE
                if (this.aiWithSubject && data.subject) {
                    this.subject = data.subject;
                }
                if (data.body) {
                    this.body = data.body;
                    if (this.editor) this.editor.setContent(data.body);
                }
                this.showAiModal = false;
            })
            .catch(() => {
                this.aiError = "{{ __('Lỗi kết nối. Vui lòng thử lại.') }}";
            })
            .finally(() => { this.aiLoading = false; });
        },

        previewEmail() {
            // Đồng bộ nội dung mới nhất từ TinyMCE trước khi xem trước
            if (this.editor) this.body = this.editor.getContent();
            if (!this.body.trim()) {
                alert("{{ __('Vui lòng nhập nội dung email trước khi xem trước.') }}");
                return;
            }
            fetch('{{ route('admin.email_campaigns.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ subject: this.subject, body: this.body })
            })
            .then(r => {
                if (!r.ok) throw new Error('Preview failed: ' + r.status);
                return r.json();
            })
            .then(data => {
                this.previewSubject = data.subject ?? '';
                this.showPreview = true;
                this.$nextTick(() => {
                    const iframe = document.getElementById('preview-iframe');
                    if (iframe) iframe.srcdoc = data.body ?? '';
                });
            })
            .catch(err => {
                console.error(err);
                alert("{{ __('Không thể tải xem trước. Vui lòng thử lại.') }}");
            });
        },
    }
}
</script>
@endsection

{{--
    Partial View: Modal sao chép nhanh Khóa API Token dành cho thành viên
    Mục đích: Giúp người dùng sao chép API Token cực nhanh khi truy cập link có tham số ?open=apitoken
             hoặc khi click vào nút xem/sao chép API Token tại trang Profile.
    Quy tắc: 100% sử dụng đa ngôn ngữ __(), comment tiếng Việt rõ ràng, AlpineJS reactive state.
--}}
<template x-teleport="body">
    <div x-show="showApiTokenModal"
        @click.self="showApiTokenModal = false"
        @keydown.escape.window="showApiTokenModal = false"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/65 backdrop-blur-md p-4"
        x-cloak 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden relative" @click.stop>
            
            <!-- Đèn hiệu trang trí hiệu ứng gradient phía trên modal -->
            <div class="h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-shopee"></div>

            <!-- Tiêu đề modal & nút đóng -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 ring-4 ring-emerald-50/50 dark:ring-emerald-950/20">
                        <i data-lucide="key-round" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-gray-900 dark:text-white text-base leading-tight">
                            {{ __('Mã Khóa API Token') }}
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-slate-400 mt-0.5">
                            {{ __('Dùng để kết nối & tích hợp vào hệ thống khác') }}
                        </p>
                    </div>
                </div>
                <button type="button" 
                    @click="showApiTokenModal = false" 
                    class="w-8 h-8 rounded-full bg-gray-100 dark:bg-slate-800 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Nội dung chính của Modal -->
            <div class="p-6 space-y-5">
                <!-- Hướng dẫn ngắn -->
                <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                    <div class="leading-relaxed">
                        {{ __('Đây là khóa truy cập bảo mật cá nhân của bạn. Không chia sẻ mã này cho bất kỳ ai không tin tưởng để bảo vệ tài khoản.') }}
                    </div>
                </div>

                <!-- Khung hiển thị API Token & Nút ẩn/hiện -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('Khóa API Token cá nhân') }}
                        </label>
                        <!-- Nút Ẩn/Hiện Token -->
                        <button type="button" 
                            @click="revealTokenModal = !revealTokenModal" 
                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5" x-show="!revealTokenModal"></i>
                            <i data-lucide="eye-off" class="w-3.5 h-3.5" x-show="revealTokenModal" x-cloak></i>
                            <span x-text="revealTokenModal ? '{{ __('Che bớt mã') }}' : '{{ __('Hiển thị đầy đủ') }}'"></span>
                        </button>
                    </div>

                    <div class="relative">
                        <input type="text" 
                            readonly 
                            :value="revealTokenModal ? '{{ $user->api_token }}' : '{{ Str::limit($user->api_token, 10, '••••••••••••••••••••••••') }}'" 
                            class="block w-full px-4 py-3.5 border border-emerald-300 dark:border-emerald-800/80 rounded-2xl text-sm bg-emerald-50/40 dark:bg-slate-950 text-emerald-900 dark:text-emerald-300 font-mono font-semibold tracking-wide select-all focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                    </div>
                </div>

                <!-- Nút sao chép bự nổi bật (Primary Call-to-Action) -->
                <button type="button"
                    @click="navigator.clipboard.writeText('{{ $user->api_token }}').then(() => { copiedModal = true; setTimeout(() => copiedModal = false, 2500) })"
                    class="w-full py-3.5 px-5 rounded-2xl font-extrabold text-sm text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-600 active:scale-[0.98] transition-all shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2.5 cursor-pointer">
                    
                    <!-- Icon chuyển đổi theo trạng thái sao chép -->
                    <template x-if="!copiedModal">
                        <div class="flex items-center gap-2">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                            <span>{{ __('SAO CHÉP API TOKEN') }}</span>
                        </div>
                    </template>
                    <template x-if="copiedModal">
                        <div class="flex items-center gap-2 text-white">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-white animate-bounce"></i>
                            <span>{{ __('ĐÃ SAO CHÉP VÀO BỘ NHỚ TẠM!') }}</span>
                        </div>
                    </template>
                </button>

                <!-- Toast thông báo thành công khi vừa copy -->
                <div x-show="copiedModal" 
                    x-cloak 
                    x-transition
                    class="p-3 rounded-2xl bg-emerald-500 text-white text-center text-xs font-bold shadow-md flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>{{ __('Mã API Token đã sẵn sàng để dán vào ứng dụng tích hợp!') }}</span>
                </div>

                <!-- Các liên kết tiện ích phụ -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-slate-800 text-xs">
                    @if(\App\Models\Setting::getVal('api_docs_enabled', '1') === '1')
                    <button type="button" 
                        @click="showApiTokenModal = false; showApiDocs = true;" 
                        class="text-shopee dark:text-shopee-light hover:underline font-bold flex items-center gap-1.5">
                        <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                        {{ __('Xem hướng dẫn & Tài liệu API') }}
                    </button>
                    @else
                    <div></div>
                    @endif

                    <!-- Nút tạo lại mã API Token mới -->
                    <form action="{{ route('profile.regenerate_api_key') }}" method="POST" onsubmit="return confirm('{{ __('Sếp có chắc chắn muốn tạo lại Mã API Key mới? Mã cũ trên các ứng dụng sẽ ngưng hoạt động.') }}')">
                        @csrf
                        <button type="submit" class="text-rose-500 dark:text-rose-400 hover:underline font-bold flex items-center gap-1">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                            {{ __('Tạo lại mã mới') }}
                        </button>
                    </form>
                </div>

            </div>

            <!-- Footer nút đóng -->
            <div class="bg-gray-50 dark:bg-slate-950/50 px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end">
                <button type="button" 
                    @click="showApiTokenModal = false" 
                    class="px-5 py-2 rounded-xl text-xs font-bold text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-800 transition-colors">
                    {{ __('Đóng') }}
                </button>
            </div>

        </div>
    </div>
</template>

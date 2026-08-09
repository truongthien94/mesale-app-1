@extends('layouts.app')

@section('title', __('Nhập Giftcode Nhận Thưởng') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-5 sm:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Nội dung -->
        <div class="lg:col-span-9 space-y-5 sm:space-y-8">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- Tiêu đề trang -->
            <div class="flex items-center gap-3 sm:gap-3.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center bg-gradient-to-tr from-shopee/10 to-shopee-light/10 text-shopee rounded-2xl dark:from-shopee/20 dark:to-shopee-light/20 dark:text-shopee-light shrink-0 shadow-sm">
                    <i data-lucide="ticket" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Nhập Giftcode') }}
                    </h1>
                    <p class="hidden sm:block text-xs text-gray-400 dark:text-slate-400 mt-0.5">{{ __('Nhập mã quà tặng để nhận thưởng cộng thẳng vào số dư ví khả dụng') }}</p>
                </div>
            </div>

            <!-- Khối nhập mã (Hero) -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-8 items-start">

                <!-- Card nhập mã -->
                <div class="xl:col-span-7 relative bg-gradient-to-tr from-shopee to-shopee-light text-white p-6 sm:p-8 rounded-2xl shadow-xl shadow-shopee/20 overflow-hidden"
                     x-data="{
                        code: {{ \Illuminate\Support\Js::from(old('code', '')) }},
                        submitting: false,
                        redeem() {
                            if (this.submitting) return;
                            if (!this.code || !this.code.trim()) {
                                window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng nhập mã Giftcode.') }}', type: 'error' } }));
                                return;
                            }
                            this.submitting = true;
                            fetch('{{ route('giftcode.redeem') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                                body: JSON.stringify({ code: this.code })
                            })
                            .then(r => r.json())
                            .then(d => {
                                window.dispatchEvent(new CustomEvent('toast', { detail: { text: d.message, type: d.success ? 'success' : 'error' } }));
                                if (d.success) {
                                    this.code = '';
                                    // Tải lại để cập nhật số dư, tổng thưởng và lịch sử đổi mã
                                    setTimeout(() => window.location.reload(), 900);
                                } else {
                                    this.submitting = false;
                                }
                            })
                            .catch(() => {
                                this.submitting = false;
                                window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Có lỗi xảy ra, vui lòng thử lại sau.') }}', type: 'error' } }));
                            });
                        }
                     }">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="absolute -left-8 -bottom-12 w-36 h-36 bg-black/5 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative">
                        <div class="flex items-center gap-2 mb-1.5">
                            <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl ring-1 ring-white/20">
                                <i data-lucide="gift" class="w-5 h-5 text-white"></i>
                            </div>
                            <h2 class="text-base sm:text-lg font-extrabold tracking-tight">{{ __('Đổi mã quà tặng') }}</h2>
                        </div>
                        <p class="text-[11px] sm:text-xs text-orange-50/90 leading-relaxed mb-5 max-w-md">
                            {{ $intro ?: __('Nhập mã Giftcode được phát từ sự kiện, minigame hoặc admin để nhận thưởng ngay vào ví khả dụng của bạn.') }}
                        </p>

                        <form @submit.prevent="redeem()" class="space-y-3">
                            @csrf
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-shopee">
                                    <i data-lucide="ticket" class="w-5 h-5"></i>
                                </span>
                                <input type="text" name="code" x-model="code" :disabled="submitting" autocomplete="off" autofocus
                                       @input="code = code.toUpperCase()"
                                       placeholder="{{ __('NHẬP MÃ TẠI ĐÂY') }}"
                                       class="block w-full pl-12 pr-4 py-3.5 sm:py-4 rounded-2xl text-sm sm:text-base font-mono font-bold tracking-widest text-gray-800 placeholder:text-gray-300 placeholder:tracking-normal placeholder:font-sans bg-white border-0 focus:outline-none focus:ring-4 focus:ring-white/40 shadow-lg disabled:opacity-70">
                            </div>
                            <button type="submit" :disabled="submitting"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-bold text-shopee bg-white hover:bg-orange-50 rounded-2xl shadow-lg transition-all active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed">
                                <i data-lucide="sparkles" class="w-5 h-5" x-show="!submitting"></i>
                                <svg x-show="submitting" style="display:none" class="animate-spin w-5 h-5 text-shopee" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="submitting ? '{{ __('Đang xử lý...') }}' : '{{ __('Nhận thưởng ngay') }}'">{{ __('Nhận thưởng ngay') }}</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Card thống kê & hướng dẫn -->
                <div class="xl:col-span-5 space-y-5">
                    <!-- Tổng đã nhận -->
                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-slate-400">{{ __('Tổng thưởng từ Giftcode') }}</span>
                            <div class="p-2 bg-amber-50 dark:bg-amber-950/30 rounded-xl shrink-0">
                                <i data-lucide="coins" class="w-5 h-5 text-amber-500"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ \App\Helpers\CurrencyHelper::format($totalEarned) }}</p>
                        <span class="text-[10px] sm:text-xs font-semibold text-amber-600 dark:text-amber-400 block mt-3">{{ __('Đã cộng vào số dư khả dụng') }}</span>
                    </div>

                    <!-- Hướng dẫn nhanh -->
                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800">
                        <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-4 h-4 text-shopee"></i> {{ __('Lưu ý khi đổi mã') }}
                        </h3>
                        <ul class="space-y-2.5 text-xs text-gray-500 dark:text-slate-400">
                            <li class="flex items-start gap-2">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i>
                                <span>{{ __('Mỗi mã có điều kiện và số lượt sử dụng riêng.') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i>
                                <span>{{ __('Phần thưởng được cộng ngay vào ví khả dụng và có thể rút.') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i>
                                <span>{{ __('Mã không phân biệt chữ hoa/thường, vui lòng nhập chính xác.') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Lịch sử đổi mã -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800 overflow-hidden">
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                    <h2 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-shopee"></i>
                        {{ __('Lịch Sử Đổi Mã') }}
                    </h2>
                    @if($redemptions->total() > 0)
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __(':n lượt', ['n' => $redemptions->total()]) }}</span>
                    @endif
                </div>

                @if($redemptions->isEmpty())
                    <div class="py-14 text-center">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center">
                            <i data-lucide="ticket" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                        </div>
                        <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">{{ __('Bạn chưa đổi mã Giftcode nào.') }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ __('Nhập mã đầu tiên của bạn ở khung phía trên nhé!') }}</p>
                    </div>
                @else
                    <!-- Desktop -->
                    <div class="hidden sm:block">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/70 dark:bg-slate-800/40 text-[10px] uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-3 font-bold">{{ __('Mã đã đổi') }}</th>
                                    <th class="px-6 py-3 font-bold text-right">{{ __('Tiền thưởng') }}</th>
                                    <th class="px-6 py-3 font-bold text-right">{{ __('Thời gian') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                                @foreach($redemptions as $r)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30">
                                        <td class="px-6 py-3.5">
                                            <span class="font-mono font-bold text-shopee">{{ $r->code }}</span>
                                            @if($r->giftCode && $r->giftCode->title)
                                                <span class="block text-[11px] text-gray-400 mt-0.5">{{ $r->giftCode->title }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 text-right font-extrabold text-green-600 dark:text-green-400">+{{ \App\Helpers\CurrencyHelper::format($r->amount) }}</td>
                                        <td class="px-6 py-3.5 text-right text-gray-500 dark:text-slate-400">{{ $r->created_at->format('H:i d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- Mobile -->
                    <div class="sm:hidden divide-y divide-gray-100 dark:divide-slate-800">
                        @foreach($redemptions as $r)
                            <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                                <div class="min-w-0">
                                    <span class="font-mono font-bold text-shopee text-sm block">{{ $r->code }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $r->created_at->format('H:i d/m/Y') }}</span>
                                </div>
                                <span class="font-extrabold text-green-600 dark:text-green-400 text-sm shrink-0">+{{ \App\Helpers\CurrencyHelper::format($r->amount) }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($redemptions->hasPages())
                        <div class="px-5 sm:px-6 py-4 border-t border-gray-100 dark:border-slate-800">{{ $redemptions->links() }}</div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

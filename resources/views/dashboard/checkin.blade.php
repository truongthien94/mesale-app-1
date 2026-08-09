@extends('layouts.app')

@section('title', __('Điểm Danh Hằng Ngày Nhận Quà') . ' - ' . $siteName)

@section('styles')
<link href="{{ asset('css/checkin.css') }}?v=1.0.1" rel="stylesheet">
@endsection

@section('content')
@php
    $checkinConfigData = [
        'hasCheckedIn'    => (bool) $hasCheckedInToday,
        'streak'          => (int)  $currentStreak,
        'checkinRoute'    => route('checkin.post'),
        'redirectEnabled' => (bool) $checkinRedirectEnabled,
        'redirectDevice'  => $checkinRedirectDevice,
        'redirectUrl'     => $checkinRedirectUrl,
        'deviceAllowed'   => empty($deviceWarning),
        'orderAllowed'    => empty($orderWarning),
        'emailAllowed'    => empty($emailWarning),
        'accountAgeAllowed' => empty($accountAgeWarning),
    ];
@endphp
<script>
var __checkinConfig = @json($checkinConfigData);
</script>
<div class="px-4 mx-auto max-w-[480px] md:max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10 relative z-10" x-data="checkinHandler(__checkinConfig)">
    <!-- Optional Background Gradient -->
    <div class="fixed top-0 left-0 w-full h-[40vh] bg-gradient-to-b from-orange-50 to-transparent dark:from-slate-900/50 dark:to-transparent -z-10 pointer-events-none"></div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 items-start">
        
        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết -->
        <div class="lg:col-span-9 space-y-5 md:space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')
            
            <!-- Tiêu đề trang (Header from Image) -->
            <div class="flex items-center gap-4 pb-1">
                <div class="w-[52px] h-[52px] flex items-center justify-center bg-orange-100 text-orange-500 rounded-xl shrink-0 shadow-sm border border-orange-200 dark:bg-orange-900/30 dark:border-orange-800/50">
                    <i data-lucide="calendar-check" class="w-7 h-7"></i>
                </div>
                <div>
                    <h1 class="text-[18px] sm:text-[22px] font-black text-gray-900 dark:text-white uppercase tracking-tight">
                        {{ __('Điểm Danh Hằng Ngày') }}
                    </h1>
                    <p class="text-[13px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">
                        {{ __('Điểm danh mỗi ngày để nhận thưởng hấp dẫn') }}
                    </p>
                </div>
            </div>

            @if (!empty($deviceWarning))
                <!-- Cảnh báo thiết bị không hợp lệ -->
                <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-300 rounded-2xl flex items-start gap-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                    <div class="text-xs font-semibold">
                        {{ $deviceWarning }}
                    </div>
                </div>
            @endif

            @if (!empty($orderWarning))
                <!-- Cảnh báo thiếu đơn hàng trong tháng -->
                <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-300 rounded-2xl flex items-start gap-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                    <div class="text-xs font-semibold">
                        {{ $orderWarning }}
                    </div>
                </div>
            @endif

            @if (!empty($emailWarning))
                <!-- Cảnh báo chưa xác minh email -->
                <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-300 rounded-2xl flex items-start gap-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                    <div class="text-xs font-semibold">
                        {{ $emailWarning }}
                    </div>
                </div>
            @endif

            @if (!empty($accountAgeWarning))
                <!-- Cảnh báo tài khoản chưa đủ số ngày đăng ký tối thiểu -->
                <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-300 rounded-2xl flex items-start gap-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                    <div class="text-xs font-semibold">
                        {{ $accountAgeWarning }}
                    </div>
                </div>
            @endif

            <!-- Main Dark Banner (Mở quà tặng may mắn hôm nay) -->
            <div class="bg-[#24262B] dark:bg-slate-900 rounded-2xl p-6 sm:p-8 relative overflow-hidden text-white shadow-xl min-h-[280px] flex flex-col justify-center">
                <!-- Sparkles Background Decoration -->
                <div class="absolute inset-0 z-0 opacity-40 pointer-events-none" style="background-image: radial-gradient(#FF7B3B 1px, transparent 1px); background-size: 40px 40px; transform: rotate(-15deg) scale(1.5);"></div>
                
                <div class="relative z-10 w-full sm:w-2/3 md:w-3/5">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#3D3328] border border-[#523A25] text-orange-500 text-[11px] font-black uppercase tracking-wider mb-5 shadow-sm">
                        <i data-lucide="flame" class="w-3 h-3 fill-orange-500"></i>
                        <span>{{ __('Chuỗi điểm danh hiện tại:') }} <span x-text="streak"><span class="inline-block w-5 h-3 bg-orange-500/30 animate-pulse rounded align-middle mt-[-2px]"></span></span> {{ __('ngày') }}</span>
                    </div>
                    
                    <h2 class="text-[22px] sm:text-3xl font-black mb-2.5 tracking-tight text-white">{{ __('Mở quà tặng may mắn hôm nay') }}</h2>
                    
                    <p class="text-[13px] text-gray-300 font-medium mb-1">{{ __('Phần thưởng có thể nhận:') }}</p>
                    <p class="text-3xl sm:text-[40px] font-black text-orange-500 mb-3 leading-none">
                        {{ \App\Helpers\CurrencyHelper::format($rewardCoins) }}
                    </p>
                    
                    <p class="text-xs text-gray-400 mb-6 max-w-[220px] leading-[1.6]">
                        {{ __('Duy trì chuỗi điểm danh liên tục để nhận thêm cực kỳ hấp dẫn!') }}
                    </p>
                    
                    <!-- Nút Điểm Danh -->
                    <button @click="performCheckin"
                            :disabled="hasCheckedIn || loading || !deviceAllowed || !orderAllowed || !emailAllowed || !accountAgeAllowed"
                            class="inline-flex items-center justify-center gap-2 checkin-btn-gradient text-white px-5 sm:px-6 py-3.5 rounded-full font-black shadow-lg shadow-orange-500/30 transition-all duration-300 disabled:opacity-60 disabled:cursor-not-allowed uppercase text-[12px] sm:text-[13px] tracking-widest relative group btn-attention hover:scale-[1.03] active:scale-[0.97]">
                        
                        <i data-lucide="gift" class="w-5 h-5 group-hover:rotate-12 transition-transform"></i>
                        <span x-text="loading ? '{{ __('ĐANG XỬ LÝ...') }}' : (hasCheckedIn ? '{{ __('ĐÃ ĐIỂM DANH') }}' : '{{ __('ĐIỂM DANH NGAY') }}')"><span class="inline-block w-24 h-4 bg-white/20 animate-pulse rounded align-middle mt-[-2px]"></span></span>
                        
                        <!-- Circular Chevron Right -->
                        <div class="w-6 h-6 bg-white rounded-full flex items-center justify-center ml-1 text-[#FF4D6D] group-hover:translate-x-1 transition-transform">
                            <i data-lucide="chevron-right" class="w-4 h-4 stroke-[3]"></i>
                        </div>
                    </button>
                </div>
                
                <!-- Right Side Image (3D Gift Box) -->
                <div class="absolute right-[-25px] sm:right-[-30px] bottom-[-20px] sm:bottom-[-25px] w-[210px] sm:w-[260px] md:w-[300px] pointer-events-none z-10 gift-bounce">
                    <!-- Ensure the image path matches the instruction -->
                    <img src="{{ asset('assets/images/checkin-gift.webp') }}" alt="Gift Box" class="w-full h-auto drop-shadow-[0_20px_20px_rgba(0,0,0,0.4)] object-contain object-bottom" onerror="this.src='https://hoantienshopee.ddev.site/assets/images/checkin-gift.webp'; this.onerror=null;">
                </div>
            </div>

            <!-- Giao Diện Chuỗi Điểm Danh (White Box) -->
            <div class="bg-white dark:bg-slate-900 px-5 pt-6 pb-8 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 relative z-0">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/20 text-[#FF6600] rounded-xl flex items-center justify-center border border-orange-100 dark:border-orange-900/40 relative shrink-0">
                        <i data-lucide="calendar" class="w-6 h-6 stroke-[2]"></i>
                        <i data-lucide="star" class="w-2.5 h-2.5 text-[#FF6600] fill-[#FF6600] absolute top-[18px] left-[18px]"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide flex items-center gap-1.5">
                            {{ __('GIAO DIỆN CHUỖI ĐIỂM DANH') }} 
                            <i data-lucide="sparkles" class="w-4 h-4 text-amber-400 fill-amber-400"></i>
                        </h3>
                        <p class="text-[12px] font-medium text-gray-500 dark:text-slate-400">{{ __('Điểm danh mỗi ngày để nhận thưởng hấp dẫn') }}</p>
                    </div>
                </div>
                
                @php
                    $targetDay = $hasCheckedInToday ? $currentStreak : ($currentStreak + 1);
                    $startDay = floor(($targetDay - 1) / 7) * 7 + 1;
                @endphp

                <div class="relative w-full timeline-container overflow-x-auto pb-4 md:pb-0">
                    <div class="min-w-[500px] h-[150px] relative">
                        <!-- Connecting Line (Dashed) -->
                        <div class="absolute top-[50px] left-[7%] right-[7%] h-[2px] border-t-[2.5px] border-dashed border-[#FFDAB9] dark:border-orange-900/50 z-0"></div>
                        
                        <div class="flex justify-between absolute inset-0 z-10 px-2">
                            @for ($i = 0; $i < 7; $i++)
                                @php
                                    $dayNum = $startDay + $i;
                                    $isCompleted = ($dayNum < $targetDay) || ($dayNum == $targetDay && $hasCheckedInToday);
                                    $hasMilestone = isset($milestones[$dayNum]);
                                    $milestoneBonus = $hasMilestone ? $milestones[$dayNum] : 0;
                                    $dayReward = $rewardCoins + $milestoneBonus;
                                    
                                    // Custom short format for rewards (+500đ, +1k, etc)
                                    $displayReward = $dayReward >= 1000 ? rtrim(rtrim(number_format($dayReward/1000, 1, '.', ''), '0'), '.') . 'k' : number_format($dayReward) . 'đ';
                                @endphp
                                
                                <div class="flex-1 relative group">
                                    @if ($isCompleted)
                                        <!-- Completed Day Pill -->
                                        <div class="absolute top-[21px] left-1/2 -translate-x-1/2 w-[72px] h-[106px] bg-white dark:bg-slate-800 rounded-2xl flex flex-col items-center justify-start border-[1.5px] border-orange-200 dark:border-orange-900/50 shadow-sm shadow-orange-100 dark:shadow-none pt-2 z-10 group-hover:-translate-y-1 transition-transform">
                                            <div class="absolute inset-0 bg-orange-50/50 dark:bg-orange-900/10 rounded-2xl"></div>
                                            <div class="w-[42px] h-[42px] bg-gradient-to-tr from-[#FF7A37] to-[#FF5500] rounded-full flex items-center justify-center shrink-0 mb-1 z-10 shadow-md shadow-orange-500/30 ring-[4px] ring-white dark:ring-slate-800 relative">
                                                <i data-lucide="check" class="w-5 h-5 text-white stroke-[4]"></i>
                                                <!-- Sparkles -->
                                                <i data-lucide="sparkle" class="absolute -top-1 -right-2 w-3 h-3 text-amber-400 fill-amber-400"></i>
                                                <i data-lucide="sparkle" class="absolute bottom-0 -left-2 w-2 h-2 text-amber-400 fill-amber-400"></i>
                                            </div>
                                            <div class="flex flex-col items-center gap-[2px] w-full z-10 mt-1">
                                                <span class="text-[11px] font-bold leading-none text-[#FF6600] dark:text-orange-400">Ngày {{ $dayNum }}</span>
                                                <span class="text-[12px] font-black leading-none text-[#FF6600] dark:text-orange-500">+{!! str_replace('₫', 'đ', \App\Helpers\CurrencyHelper::format($dayReward)) !!}</span>
                                            </div>
                                        </div>
                                    @elseif ($hasMilestone)
                                        <!-- Special Milestone Day Box (Day 7) -->
                                        <div class="absolute top-[14px] left-1/2 -translate-x-1/2 w-[76px] h-[116px] bg-white dark:bg-slate-800 rounded-2xl flex flex-col items-center justify-start border-[1.5px] border-orange-200 dark:border-orange-900/50 shadow-sm shadow-orange-100 dark:shadow-none pt-4 z-20 group-hover:-translate-y-1 transition-transform">
                                            <div class="absolute inset-0 bg-orange-50/50 dark:bg-orange-900/10 rounded-2xl"></div>
                                            <!-- Ribbon -->
                                            <div class="absolute -top-[10px] left-1/2 -translate-x-1/2 w-[86px] h-[22px] bg-gradient-to-r from-[#FF7A37] via-[#FF5500] to-[#FF7A37] rounded flex items-center justify-center shadow-sm z-30">
                                                <!-- Ribbon folds -->
                                                <div class="absolute -left-1.5 -bottom-1 w-0 h-0 border-t-[4px] border-t-orange-800 border-l-[6px] border-l-transparent"></div>
                                                <div class="absolute -right-1.5 -bottom-1 w-0 h-0 border-t-[4px] border-t-orange-800 border-r-[6px] border-r-transparent"></div>
                                                <span class="text-white text-[9px] font-black uppercase tracking-wider relative z-10 drop-shadow-md">THƯỞNG LỚN</span>
                                            </div>
                                            
                                            <!-- Gift Icon Image -->
                                            <div class="w-10 h-10 flex items-center justify-center z-10 relative">
                                                <img src="{{ asset('assets/images/gift_coins_3d.webp') }}" class="w-full h-full object-contain drop-shadow-md animate-pulse" onerror="this.src='https://hoantienshopee.ddev.site/assets/images/gift_coins_3d.webp'; this.onerror=null;">
                                                <i data-lucide="sparkle" class="absolute top-0 -right-1 w-2.5 h-2.5 text-amber-400 fill-amber-400"></i>
                                                <i data-lucide="sparkle" class="absolute bottom-1 -left-1 w-2 h-2 text-amber-400 fill-amber-400"></i>
                                            </div>
                                            <div class="flex flex-col items-center gap-[2px] absolute bottom-3 w-full z-10">
                                                <span class="text-[11px] font-bold leading-none text-[#FF6600] dark:text-orange-400">Ngày {{ $dayNum }}</span>
                                                <span class="text-[12px] font-black leading-none text-[#FF6600] dark:text-orange-500">+{!! str_replace('₫', 'đ', \App\Helpers\CurrencyHelper::format($dayReward)) !!}</span>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Inactive Future Day Node -->
                                        <div class="absolute top-[26px] left-1/2 -translate-x-1/2 flex flex-col items-center w-[60px]">
                                            <div class="w-12 h-12 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center relative z-10 ring-[6px] ring-white dark:ring-slate-900">
                                                <!-- Light gray circle with lock inside -->
                                                <div class="w-[38px] h-[38px] bg-[#F4F6F8] dark:bg-slate-800 rounded-full flex items-center justify-center border border-gray-200/60 dark:border-slate-700 shadow-inner">
                                                    <i data-lucide="lock" class="w-4 h-4 text-gray-400 dark:text-slate-500 stroke-[2.5]"></i>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-center gap-[2px] mt-2 w-full">
                                                <span class="text-[11px] font-semibold leading-none text-gray-500 dark:text-slate-400">Ngày {{ $dayNum }}</span>
                                                <span class="text-[12px] font-black leading-none text-gray-800 dark:text-slate-300">+{!! str_replace('₫', 'đ', \App\Helpers\CurrencyHelper::format($dayReward)) !!}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mốc Thưởng Tiếp Theo (Progress Bar) -->
            @php
                $nextMilestoneDay = null;
                $nextMilestoneBonus = 0;
                ksort($milestones);
                foreach ($milestones as $day => $bonus) {
                    if ($day > $currentStreak) {
                        $nextMilestoneDay = $day;
                        $nextMilestoneBonus = $bonus;
                        break;
                    }
                }
            @endphp
            
            @if ($nextMilestoneDay)
                @php
                    $progressPercent = min(($currentStreak / $nextMilestoneDay) * 100, 100);
                @endphp
                <div class="bg-[#FFF8F3] dark:bg-slate-800/50 p-4 sm:p-5 rounded-2xl border border-orange-100 dark:border-slate-700 mt-6 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#FFECE0] dark:bg-orange-900/30 text-[#FF6600] rounded-full flex items-center justify-center shrink-0">
                                <i data-lucide="flame" class="w-5 h-5 fill-current"></i>
                            </div>
                            <div>
                                <h4 class="text-[13px] sm:text-[14px] font-bold text-gray-800 dark:text-slate-200">
                                    {{ __('Mốc thưởng chuỗi tiếp theo:') }} <span class="text-[#FF6600]">{{ $nextMilestoneDay }} {{ __('ngày') }}</span>
                                </h4>
                                <p class="text-[11px] sm:text-[12px] text-gray-500 dark:text-slate-400 mt-0.5">
                                    {{ __('Nhận ngay thêm') }} <strong class="text-[#10B981]">+{!! str_replace('₫', 'đ', \App\Helpers\CurrencyHelper::format($nextMilestoneBonus)) !!}</strong> {{ __('khi hoàn thành chuỗi') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-[12px] font-bold text-[#FF6600] shrink-0 ml-2">
                            {{ $currentStreak }} / {{ $nextMilestoneDay }} {{ __('ngày') }}
                        </div>
                    </div>
                    
                    <!-- Smooth Progress Bar -->
                    <div class="w-full bg-[#FFECE0] dark:bg-slate-700 h-[6px] rounded-full overflow-hidden relative z-10">
                        <div class="bg-gradient-to-r from-[#FF9D66] to-[#FF6600] h-full rounded-full transition-all duration-1000 ease-out" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-slate-900 p-6 sm:p-7 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-green-50 dark:bg-green-900/20 text-green-500 rounded-2xl flex items-center justify-center shrink-0 shadow-sm border border-green-100">
                            <i data-lucide="award" class="w-7 h-7 stroke-[3]"></i>
                        </div>
                        <div>
                            <h4 class="text-[15px] font-black text-gray-900 dark:text-slate-100">
                                {{ __('Bạn đã đạt mốc chuỗi tối đa!') }}
                            </h4>
                            <p class="text-[12px] font-medium text-gray-500 dark:text-slate-400 mt-1">
                                {{ __('Hãy tiếp tục duy trì chuỗi điểm danh mỗi ngày để nhận phần thưởng nhé!') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Danh sách tất cả các mốc thưởng -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 mt-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <!-- Icon Aura -->
                        <div class="w-12 h-12 rounded-full bg-orange-100/50 dark:bg-orange-900/20 flex items-center justify-center shrink-0 relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-tr from-orange-200 to-transparent dark:from-orange-800/30 opacity-50"></div>
                            <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center text-[#FF6600] shadow-sm z-10 relative">
                                <i data-lucide="gift" class="w-4 h-4 stroke-[2.5]"></i>
                            </div>
                            <i data-lucide="sparkles" class="w-3 h-3 text-amber-400 fill-amber-400 absolute top-2 left-2 z-10 opacity-70"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-[15px] sm:text-[17px] text-gray-900 dark:text-white uppercase tracking-tight">
                                {{ __('TẤT CẢ MỐC THƯỞNG TÍCH LŨY') }}
                            </h3>
                            <p class="text-[12px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">
                                {{ __('Điểm danh liên tục để nhận thưởng lớn hơn') }}
                            </p>
                        </div>
                    </div>
                    

                </div>

                <!-- Grid Data -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                    @php
                        $colors = [
                            [
                                'theme' => 'orange',
                                'border' => 'border-orange-100 dark:border-orange-900/30',
                                'bg' => 'bg-orange-50/40 dark:bg-orange-900/10',
                                'text' => 'text-[#FF6600] dark:text-orange-400',
                                'ribbon' => 'bg-gradient-to-b from-[#FF7A37] to-[#FF5500]',
                                'pill_bg' => 'bg-orange-100/60 dark:bg-orange-900/30',
                                'pill_text' => 'text-[#FF6600] dark:text-orange-400',
                                'filter' => 'hue-rotate-0',
                            ],
                            [
                                'theme' => 'purple',
                                'border' => 'border-purple-100 dark:border-purple-900/30',
                                'bg' => 'bg-purple-50/40 dark:bg-purple-900/10',
                                'text' => 'text-purple-600 dark:text-purple-400',
                                'ribbon' => 'bg-gradient-to-b from-purple-400 to-purple-600',
                                'pill_bg' => 'bg-purple-100/60 dark:bg-purple-900/30',
                                'pill_text' => 'text-purple-600 dark:text-purple-400',
                                'filter' => 'hue-rotate-[240deg]',
                            ],
                            [
                                'theme' => 'blue',
                                'border' => 'border-blue-100 dark:border-blue-900/30',
                                'bg' => 'bg-blue-50/40 dark:bg-blue-900/10',
                                'text' => 'text-blue-500 dark:text-blue-400',
                                'ribbon' => 'bg-gradient-to-b from-blue-400 to-blue-600',
                                'pill_bg' => 'bg-blue-100/60 dark:bg-blue-900/30',
                                'pill_text' => 'text-blue-600 dark:text-blue-400',
                                'filter' => 'hue-rotate-[180deg]',
                            ],
                            [
                                'theme' => 'green',
                                'border' => 'border-emerald-100 dark:border-emerald-900/30',
                                'bg' => 'bg-emerald-50/40 dark:bg-emerald-900/10',
                                'text' => 'text-emerald-600 dark:text-emerald-400',
                                'ribbon' => 'bg-gradient-to-b from-emerald-400 to-emerald-600',
                                'pill_bg' => 'bg-emerald-100/60 dark:bg-emerald-900/30',
                                'pill_text' => 'text-emerald-600 dark:text-emerald-400',
                                'filter' => 'hue-rotate-[90deg]',
                            ]
                        ];
                    @endphp
                    @foreach ($milestones as $day => $bonus)
                        @php
                            $isReached = $currentStreak >= $day;
                            $color = $colors[$loop->index % count($colors)];
                        @endphp
                        <div class="relative aspect-square p-3 sm:p-5 rounded-xl sm:rounded-2xl border {{ $color['border'] }} {{ $color['bg'] }} flex flex-col items-center justify-center text-center transition-all duration-300 overflow-hidden group hover:shadow-md">
                            
                            <!-- Ribbon -->
                            <div class="absolute top-0 left-3 sm:left-5 w-9 sm:w-11 flex flex-col items-center">
                                <div class="w-full h-11 sm:h-12 {{ $color['ribbon'] }} flex flex-col items-center justify-start pt-1 sm:pt-1.5 relative z-10" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%);">
                                    <span class="text-white font-black text-[14px] sm:text-[16px] leading-none">{{ $day }}</span>
                                    <span class="text-white/90 font-bold text-[7px] sm:text-[8px] uppercase mt-0.5">{{ __('Ngày') }}</span>
                                </div>
                            </div>
                            
                            <!-- Checkmark if reached -->
                            @if ($isReached)
                                <div class="absolute top-3 right-3 sm:top-4 sm:right-4 w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-green-100 dark:bg-green-900/40 border border-green-200 dark:border-green-900/50 flex items-center justify-center shadow-sm">
                                    <i data-lucide="check" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-green-600 dark:text-green-400"></i>
                                </div>
                            @endif

                            <!-- 3D Gift Box -->
                            <div class="relative w-16 h-16 sm:w-24 sm:h-24 mb-1 sm:mb-2 flex items-center justify-center group-hover:scale-105 transition-transform duration-500 mt-2">
                                <div class="absolute inset-0 bg-gradient-radial from-white/80 to-transparent dark:from-white/10 rounded-full blur-xl scale-110"></div>
                                <img src="{{ asset('assets/images/checkin-gift.webp') }}" class="w-full h-full object-contain relative z-10 {{ $color['filter'] }} drop-shadow-xl" alt="Milestone Gift">
                                
                                <!-- Floating sparkles -->
                                <i data-lucide="sparkle" class="absolute top-1 sm:top-2 right-1 sm:right-2 w-3 h-3 sm:w-4 sm:h-4 text-yellow-400 fill-yellow-400 opacity-60"></i>
                                <i data-lucide="sparkle" class="absolute bottom-2 sm:bottom-4 left-0 w-2 h-2 sm:w-3 sm:h-3 text-orange-300 fill-orange-300 opacity-80"></i>
                            </div>

                            <span class="text-[11px] sm:text-[14px] font-black text-gray-900 dark:text-white mt-1 sm:mt-0">{{ __('Chuỗi :day ngày', ['day' => $day]) }}</span>
                            
                            <span class="text-[16px] sm:text-[24px] font-black mt-0.5 sm:mt-1 {{ $color['text'] }} tracking-tight drop-shadow-sm">
                                +{!! str_replace('₫', 'đ', \App\Helpers\CurrencyHelper::format($bonus)) !!}
                            </span>
                            
                            <div class="mt-3 sm:mt-4">
                                @if ($isReached)
                                    <span class="inline-flex items-center gap-1 sm:gap-1.5 text-[8px] sm:text-[10px] font-black px-2.5 sm:px-4 py-1 sm:py-1.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 uppercase tracking-widest border border-green-200 dark:border-green-900/50">
                                        <i data-lucide="check-circle-2" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
                                        {{ __('Đã đạt') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 sm:gap-1.5 text-[8px] sm:text-[10px] font-black px-2.5 sm:px-4 py-1 sm:py-1.5 rounded-full {{ $color['pill_bg'] }} {{ $color['pill_text'] }} uppercase tracking-widest">
                                        <i data-lucide="clock" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
                                        {{ __('Chưa đạt') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer Banner -->
                <div class="mt-5 bg-orange-50/80 dark:bg-orange-900/20 rounded-2xl p-4 flex items-center justify-center gap-2 border border-orange-100 dark:border-orange-900/30">
                    <i data-lucide="target" class="w-5 h-5 text-[#FF6600]"></i>
                    <span class="text-[13px] text-gray-700 dark:text-slate-300 font-medium">
                        {{ __('Duy trì chuỗi điểm danh để nhận') }} <strong class="text-[#FF6600]">{{ __('phần thưởng hấp dẫn') }}</strong> {{ __('hơn!') }}
                    </span>
                </div>
            </div>

            <!-- Bảng xếp hạng và Lịch sử điểm danh -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-6">
                
                <!-- Bảng xếp hạng chuyên cần (F0) -->
                @if ($showLeaderboard)
                <div class="xl:col-span-5 bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 flex flex-col">
                    
                    <!-- Header -->
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-amber-50 dark:bg-amber-900/20 text-amber-500 rounded-xl flex items-center justify-center border border-amber-100 dark:border-amber-900/40 relative shrink-0">
                            <i data-lucide="trophy" class="w-6 h-6 stroke-[2]"></i>
                            <i data-lucide="star" class="w-2.5 h-2.5 text-amber-500 fill-amber-500 absolute top-[18px] left-[18px]"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide flex items-center gap-1.5">
                                {{ __('BẢNG VÀNG CHUYÊN CẦN') }}
                            </h3>
                            <p class="text-[12px] font-medium text-gray-500 dark:text-slate-400">{{ __('Top thành viên điểm danh chăm chỉ nhất') }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        @forelse($leaderboard as $index => $item)
                            <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-gray-100 dark:border-slate-700/50 bg-white dark:bg-slate-800/50 hover:border-orange-100 dark:hover:border-orange-900/30 hover:bg-orange-50/30 dark:hover:bg-orange-900/10 transition-all duration-300 group">
                                <div class="flex items-center gap-3.5">
                                    <!-- Vị trí hạng -->
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-[12px] shrink-0 relative
                                        @if($index === 0) bg-gradient-to-br from-amber-100 to-yellow-200 text-amber-700 dark:from-amber-900/40 dark:to-yellow-700/40 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/50 shadow-sm
                                        @elseif($index === 1) bg-gradient-to-br from-slate-100 to-gray-200 text-slate-600 dark:from-slate-700 dark:to-slate-600 dark:text-slate-300 border border-slate-200/50 dark:border-slate-600/50 shadow-sm
                                        @elseif($index === 2) bg-gradient-to-br from-orange-100 to-amber-200 text-orange-700 dark:from-orange-900/40 dark:to-amber-800/40 dark:text-orange-400 border border-orange-200/50 dark:border-orange-800/50 shadow-sm
                                        @else bg-gray-50 text-gray-500 dark:bg-slate-800 dark:text-slate-400 border border-gray-200/50 dark:border-slate-700 @endif">
                                        @if($index === 0)
                                            <i data-lucide="crown" class="absolute -top-2.5 -right-2 w-4 h-4 text-amber-500 fill-amber-500 -rotate-[20deg] drop-shadow-sm"></i>
                                        @endif
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[13px] sm:text-[14px] font-bold text-gray-800 dark:text-slate-200 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">{{ $item->user ? Str::mask($item->user->name, '*', 2, 8) : 'Thành viên' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 bg-orange-50/80 dark:bg-orange-900/20 px-3 py-1.5 rounded-xl border border-orange-100/50 dark:border-orange-900/30">
                                    <i data-lucide="flame" class="w-4 h-4 fill-orange-500 text-orange-500"></i>
                                    <span class="text-[12px] sm:text-[13px] font-black text-orange-600 dark:text-orange-400">
                                        {{ $item->max_streak }} <span class="text-[10px] sm:text-[11px] font-bold">{{ __('ngày') }}</span>
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center flex flex-col items-center justify-center h-full">
                                <div class="w-12 h-12 rounded-full bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <i data-lucide="trophy" class="w-6 h-6 text-gray-400 dark:text-slate-500"></i>
                                </div>
                                <p class="text-[13px] font-medium text-gray-500 dark:text-slate-400">{{ __('Chưa có dữ liệu xếp hạng.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                @endif

                <!-- Lịch sử điểm danh cá nhân -->
                <div class="{{ $showLeaderboard ? 'xl:col-span-7' : 'xl:col-span-12' }} bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800">
                    
                    <!-- Header -->
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/20 text-[#FF6600] rounded-xl flex items-center justify-center border border-orange-100 dark:border-orange-900/40 relative shrink-0">
                            <i data-lucide="clock" class="w-6 h-6 stroke-[2]"></i>
                            <i data-lucide="star" class="w-2.5 h-2.5 text-[#FF6600] fill-[#FF6600] absolute top-[18px] left-[18px]"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide flex items-center gap-1.5">
                                {{ __('LỊCH SỬ NHẬN THƯỞNG') }}
                            </h3>
                            <p class="text-[12px] font-medium text-gray-500 dark:text-slate-400">{{ __('Theo dõi các phần thưởng bạn đã nhận') }}</p>
                        </div>
                    </div>

                    <!-- Custom Table List -->
                    <div class="border border-gray-100 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 shadow-sm overflow-x-auto">
                        <div class="min-w-[450px]">
                            <!-- Header Row -->
                            <div class="grid grid-cols-3 bg-gradient-to-r from-[#FF7A37] to-[#FF5500] px-3 sm:px-5 py-3.5">
                                <div class="flex items-center gap-2 text-white font-black text-[11px] sm:text-[12px] uppercase tracking-wider">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    <span>{{ __('Ngày nhận') }}</span>
                                </div>
                                <div class="flex items-center justify-center gap-2 text-white font-black text-[11px] sm:text-[12px] uppercase tracking-wider">
                                    <i data-lucide="gift" class="w-4 h-4"></i>
                                    <span>{{ __('Xu nhận') }}</span>
                                </div>
                                <div class="flex items-center justify-end gap-2 text-white font-black text-[11px] sm:text-[12px] uppercase tracking-wider pr-4">
                                    <i data-lucide="flame" class="w-4 h-4 fill-white"></i>
                                    <span>{{ __('Chuỗi') }}</span>
                                </div>
                            </div>

                            <!-- Data Rows -->
                            <div class="flex flex-col divide-y divide-gray-100 dark:divide-slate-800">
                                @forelse($checkins as $checkin)
                                    <div class="grid grid-cols-3 items-center p-3 sm:p-5 hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                        
                                        <!-- Col 1: Date & Time -->
                                        <div class="flex items-center gap-3 pr-2 sm:pr-4 border-r border-dashed border-gray-200 dark:border-slate-700 h-full">
                                            <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-[#FF6600] flex items-center justify-center shrink-0 border border-orange-100 dark:border-orange-900/30">
                                                <i data-lucide="calendar-days" class="w-5 h-5"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[13px] font-black text-gray-800 dark:text-slate-200 leading-tight">
                                                    {{ \Carbon\Carbon::parse($checkin->checked_in_date)->format('d/m/Y') }}
                                                </span>
                                                <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-500 mt-0.5 uppercase">
                                                    {{ \Carbon\Carbon::parse($checkin->created_at)->format('h:i A') }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Col 2: Reward -->
                                        <div class="flex items-center justify-center border-r border-dashed border-gray-200 dark:border-slate-700 h-full">
                                            <div class="inline-flex items-center gap-1.5 bg-green-50 dark:bg-green-900/20 px-3 py-1.5 rounded-xl border border-green-100 dark:border-green-900/40">
                                                <i data-lucide="coins" class="w-4 h-4 text-green-500"></i>
                                                <span class="text-[13px] font-black text-green-600 dark:text-green-400">
                                                    +{!! str_replace('₫', 'đ', \App\Helpers\CurrencyHelper::format($checkin->coins_earned)) !!}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Col 3: Streak -->
                                        <div class="flex items-center justify-end pl-2 sm:pl-4 h-full">
                                            <div class="inline-flex items-center gap-1.5 bg-orange-50 dark:bg-orange-900/20 px-4 py-1.5 rounded-xl border border-orange-100 dark:border-orange-900/40">
                                                <i data-lucide="flame" class="w-4 h-4 text-[#FF6600] fill-[#FF6600]"></i>
                                                <span class="text-[13px] font-black text-[#FF6600] dark:text-orange-400">
                                                    {{ $checkin->streak_days }} {{ __('ngày') }}
                                                </span>
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="py-10 text-center flex flex-col items-center justify-center">
                                        <i data-lucide="inbox" class="w-10 h-10 text-gray-300 mb-3"></i>
                                        <p class="text-xs font-medium text-gray-400">
                                            {{ __('Bạn chưa điểm danh lần nào. Hãy nhấn nút để nhận thưởng đầu tiên!') }}
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    
                    @if($checkins->hasPages())
                        <div class="mt-5">
                            {{ $checkins->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Điểm Danh Thành Công -->
            <div x-show="showSuccessModal" 
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4" 
                 style="display: none;">
                <!-- Lớp phủ nền mờ và làm tối phía sau (Overlay) -->
                <div x-show="showSuccessModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/60 backdrop-blur-md"
                     @click="closeSuccessModal()"></div>

                <!-- Khung Modal chính -->
                <div x-show="showSuccessModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                     class="relative bg-white dark:bg-slate-900 rounded-2xl p-8 max-w-sm w-full shadow-2xl border border-gray-100 dark:border-slate-800 text-center z-10 transform transition-all select-none">
                    
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-24 h-24 bg-gradient-to-tr from-orange-400 to-pink-500 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-[0_10px_25px_-5px_rgba(255,107,0,0.5)] rotate-12">
                        <i data-lucide="gift" class="w-12 h-12 text-white -rotate-12 animate-pulse"></i>
                    </div>

                    <div class="mt-12">
                        <h3 class="text-[22px] font-black text-gray-900 dark:text-white tracking-tight mb-1">
                            {{ __('Điểm danh thành công!') }}
                        </h3>
                        <p class="text-[13px] text-gray-500 dark:text-slate-400 font-medium mb-6">
                            {!! __('Bạn đang ở ngày thứ :day của chu kỳ.', ['day' => '<span x-text="modalStreakDays" class="font-extrabold text-orange-500"></span>']) !!}
                        </p>

                        <div class="bg-orange-50/50 dark:bg-slate-800 border border-orange-100/50 dark:border-slate-700/50 rounded-2xl p-4 mb-6 shadow-inner">
                            <span class="block text-[10px] tracking-widest font-black text-orange-500 uppercase mb-1">
                                {{ __('Phần thưởng nhận được') }}
                            </span>
                            <span class="block text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                                +<span x-text="new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(modalEarnedCoins)"></span>đ
                            </span>
                        </div>

                        <button @click="closeSuccessModal()" 
                                class="w-full py-4 checkin-btn-gradient active:scale-[0.98] transition-all duration-200 text-white font-black rounded-2xl text-[13px] tracking-widest uppercase shadow-lg shadow-orange-500/30 focus:outline-none">
                            {{ __('TUYỆT VỜI') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/checkin.js') }}?v=1.0.8"></script>
@endsection

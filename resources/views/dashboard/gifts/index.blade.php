@extends('layouts.app')

@section('title', __('Cửa Hàng Quà Tặng & Đổi Thưởng') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết cửa hàng quà tặng -->
        <div class="lg:col-span-9 space-y-6" x-data="giftStoreHandler()">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')
            
            <!-- Tiêu đề trang với thiết kế tinh tế -->
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-slate-800/80">
                <div class="w-10 h-10 flex items-center justify-center bg-gradient-to-tr from-shopee/10 to-shopee-light/10 text-shopee rounded-xl dark:from-shopee/20 dark:to-shopee-light/20 dark:text-shopee-light shrink-0 shadow-sm">
                    <i data-lucide="gift" class="w-5.5 h-5.5"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Cửa Hàng Quà Tặng & Đổi Thưởng') }}
                    </h1>
                    <p class="hidden sm:block text-[11px] text-gray-400 dark:text-slate-400 mt-0.5">{{ __('Dùng số dư ví hoàn tiền của bạn để quy đổi thẻ cào điện thoại, mã giảm giá Shopee hoặc các phần quà hấp dẫn') }}</p>
                </div>
            </div>

            <!-- Box số dư khả dụng được thiết kế dạng Thẻ thành viên VIP sang trọng -->
            <div class="relative p-6 sm:p-8 bg-gradient-to-br from-shopee via-shopee/95 to-shopee-light rounded-2xl text-white shadow-lg overflow-hidden border border-white/10 group">
                <!-- Họa tiết trang trí lượn sóng mờ ảo phía background -->
                <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-white/5 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
                <div class="absolute right-4 top-4 opacity-10">
                    <i data-lucide="wallet" class="w-20 h-20"></i>
                </div>
                
                <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-6 z-10">
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[8px] tracking-widest font-black bg-white/20 text-white rounded uppercase">{{ __('Shopping VIP Card') }}</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider opacity-80 block">{{ __('Số dư khả dụng của bạn') }}</span>
                            <h3 class="text-3xl sm:text-4xl font-black font-mono tracking-tight flex items-center gap-1.5 drop-shadow-sm">
                                <i data-lucide="coins" class="w-8 h-8 text-yellow-300 shrink-0"></i>
                                {{ number_format(auth()->user()->balance) }}đ
                            </h3>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 max-w-md">
                        <p class="text-xs opacity-90 leading-relaxed">
                            {{ __('Số dư tích lũy từ hoạt động mua sắm hoàn tiền và giới thiệu tuyến dưới F1/F2 của bạn được đồng bộ trực tiếp tại đây để đổi thẻ cào, voucher hoặc quà tặng.') }}
                        </p>
                        <!-- Nút hành động nhanh kích thích dòng tiền tuần hoàn -->
                        <div class="flex">
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white rounded-xl text-[10px] font-bold transition-all duration-200 backdrop-blur-sm active:scale-95">
                                <i data-lucide="arrow-right-left" class="w-3.5 h-3.5"></i>
                                {{ __('Tiếp tục mua sắm tích luỹ') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc quà tặng nâng cao -->
            <form id="gift-filter-form" action="{{ route('gifts.index') }}" method="GET"
                  @submit.prevent="submitFilter()"
                  class="relative bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl shadow-sm mb-6 overflow-hidden">

                <!-- Dải gradient nhấn nhẹ phía trên tạo điểm nhận diện thương hiệu -->
                <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-shopee via-shopee-light to-transparent"></span>

                <div class="p-3 sm:p-5 space-y-3">
                    <!-- Hàng tìm kiếm chính: ô nhập rộng + nút Tìm liền kề -->
                    <div class="flex gap-2">
                        <div class="relative flex-grow group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-shopee transition-colors">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Nhập tên quà tặng...') }}"
                                class="block w-full pl-9 pr-3 py-2.5 text-sm font-medium border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/60 dark:bg-slate-850 focus:bg-white dark:focus:bg-slate-850 text-gray-800 dark:text-white placeholder:text-gray-400 placeholder:font-normal transition-all">
                        </div>
                        <button type="submit" class="shrink-0 px-3.5 sm:px-6 py-2.5 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 text-white font-bold rounded-xl text-xs shadow-md shadow-shopee/20 transition-all flex items-center justify-center gap-1.5 active:scale-95">
                            <svg x-show="loading" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <i x-show="!loading" data-lucide="search" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">{{ __('Tìm kiếm') }}</span>
                        </button>
                    </div>

                    <!-- Hàng bộ lọc: Tag · Loại quà · Sắp xếp (chia đều theo lưới, gọn trên mobile) -->
                    <div class="grid grid-cols-2 sm:flex sm:flex-wrap sm:items-center gap-2">
                        <span class="hidden sm:inline-flex items-center gap-1 text-[10px] font-extrabold text-gray-400 dark:text-slate-500 uppercase tracking-wider pr-1">
                            <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                            {{ __('Lọc theo') }}
                        </span>

                        <!-- Tag -->
                        <div class="relative">
                            <i data-lucide="tag" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                            <select name="tag" class="w-full appearance-none cursor-pointer pl-7 pr-6 py-2 text-xs font-semibold border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/60 dark:bg-slate-850 hover:border-shopee/40 text-gray-700 dark:text-slate-200 transition-all truncate">
                                <option value="all">{{ __('Tất cả tag') }}</option>
                                @foreach($availableTags as $t)
                                    <option value="{{ $t }}" {{ request('tag') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                        </div>

                        <!-- Loại quà -->
                        <div class="relative">
                            <i data-lucide="layers" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                            <select name="type" class="w-full appearance-none cursor-pointer pl-7 pr-6 py-2 text-xs font-semibold border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/60 dark:bg-slate-850 hover:border-shopee/40 text-gray-700 dark:text-slate-200 transition-all truncate">
                                <option value="all">{{ __('Tất cả các loại') }}</option>
                                <option value="voucher" {{ request('type') == 'voucher' ? 'selected' : '' }}>{{ __('Voucher / Mã giảm giá') }}</option>
                                <option value="phone_card" {{ request('type') == 'phone_card' ? 'selected' : '' }}>{{ __('Thẻ cào điện thoại') }}</option>
                                <option value="giftcode" {{ request('type') == 'giftcode' ? 'selected' : '' }}>{{ __('Giftcode game') }}</option>
                                <option value="physical" {{ request('type') == 'physical' ? 'selected' : '' }}>{{ __('Quà tặng vật lý') }}</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                        </div>

                        <!-- Sắp xếp -->
                        <div class="relative col-span-2 sm:col-auto">
                            <i data-lucide="arrow-up-down" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                            <select name="sort" class="w-full appearance-none cursor-pointer pl-7 pr-6 py-2 text-xs font-semibold border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/60 dark:bg-slate-850 hover:border-shopee/40 text-gray-700 dark:text-slate-200 transition-all truncate">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('Mới nhất') }}</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>{{ __('Giá: Thấp đến Cao') }}</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>{{ __('Giá: Cao đến Thấp') }}</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                        </div>

                        <!-- Nút Đặt lại chỉ hiện khi có bộ lọc đang áp dụng -->
                        <button type="button" @click="resetFilters()"
                                x-show="hasActiveFilters" x-cloak
                                class="col-span-2 sm:col-auto sm:ml-auto inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold text-gray-500 dark:text-slate-400 hover:text-shopee dark:hover:text-shopee-light hover:bg-shopee/5 rounded-xl transition-all active:scale-95">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            {{ __('Đặt lại') }}
                        </button>
                    </div>
                </div>
            </form>

            <!-- Container danh sách quà tặng (AJAX Container) -->
            <div id="gift-list-container" class="space-y-4">
                @include('dashboard.gifts.partials.gift_list')
            </div>

            <!-- Lịch sử quy đổi -->
            <div class="space-y-4 pt-6">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-4 bg-shopee rounded-full"></span>
                    <h2 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Lịch Sử Quy Đổi') }}</h2>
                </div>

                <!-- Thanh tìm kiếm và bộ lọc trạng thái lịch sử quy đổi -->
                <div class="bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Tìm kiếm -->
                        <div class="relative flex-grow">
                            <input type="text"
                                   id="redemption-search-input"
                                   placeholder="{{ __('Tìm theo tên quà hoặc mã đơn...') }}"
                                   value="{{ request('redemption_search') }}"
                                   @keydown.enter.prevent="loadRedemptions()"
                                   class="block w-full pl-9 pr-4 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-800 dark:text-white transition-all">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Trạng thái -->
                        <div class="relative sm:w-48 shrink-0">
                            <select id="redemption-status-select"
                                    @change="loadRedemptions()"
                                    class="block w-full pl-9 pr-8 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-805 dark:text-white transition-all appearance-none">
                                <option value="all">{{ __('Tất cả trạng thái') }}</option>
                                <option value="pending">{{ __('Chờ xử lý') }}</option>
                                <option value="approved">{{ __('Thành công') }}</option>
                                <option value="rejected">{{ __('Bị từ chối') }}</option>
                            </select>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </div>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>

                        <!-- Nút Đặt lại bộ lọc -->
                        <button type="button" @click="resetRedemptionFilters()"
                                x-show="hasActiveRedemptionFilters" x-cloak
                                class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-400 bg-gray-150/80 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all flex items-center gap-1 shrink-0 active:scale-95">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            {{ __('Đặt lại') }}
                        </button>

                        <!-- Nút Tìm kiếm -->
                        <button type="button" @click="loadRedemptions()"
                                class="px-4 py-2 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 text-white font-bold rounded-xl text-xs shadow-md shadow-shopee/10 transition-all flex items-center justify-center gap-1.5 shrink-0 active:scale-95">
                            <svg x-show="loadingRedemptions" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <i x-show="!loadingRedemptions" data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Tìm kiếm') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Container bảng lịch sử quy đổi (AJAX Container) -->
                <div id="redemption-list-container" class="bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
                    @include('dashboard.gifts.partials.redemption_list')
                </div>
            </div>

            <!-- MODAL XÁC NHẬN QUY ĐỔI QUÀ TẶNG -->
            <template x-teleport="body">
                <div x-show="exchangeModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full shadow-2xl border border-gray-100/80 dark:border-slate-850 overflow-hidden" @click.away="exchangeModalOpen = false" x-transition>
                        
                        <!-- Header modal -->
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800/80 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                <div class="w-7 h-7 bg-shopee/10 rounded-lg flex items-center justify-center text-shopee">
                                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                </div>
                                {{ __('Xác nhận quy đổi quà tặng') }}
                            </h3>
                            <button @click="exchangeModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                                <i data-lucide="x" class="w-4.5 h-4.5"></i>
                            </button>
                        </div>
                        
                        <!-- Form nhập thông tin đổi quà -->
                        <form action="{{ route('gifts.store') }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            <input type="hidden" name="gift_id" :value="selectedGift.id">

                            <!-- Tóm tắt thông tin phần quà đã chọn -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-850 flex items-center gap-3.5 shadow-inner">
                                <div class="w-14 h-14 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 overflow-hidden flex items-center justify-center shrink-0">
                                    <template x-if="selectedGift.image">
                                        <img :src="selectedGift.image" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!selectedGift.image">
                                        <div class="w-full h-full bg-gradient-to-tr from-shopee/5 to-shopee-light/5 flex items-center justify-center text-shopee/40 dark:text-shopee-light/40">
                                            <i data-lucide="gift" class="w-6 h-6"></i>
                                        </div>
                                    </template>
                                </div>
                                <div class="space-y-0.5 min-w-0">
                                    <h4 class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm truncate" x-text="selectedGift.title"></h4>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-xs sm:text-sm font-black text-shopee dark:text-shopee-light font-mono" x-text="formatCurrency(selectedGift.price)"></span>
                                        <span class="text-[9px] text-gray-450 dark:text-slate-550">| {{ __('Kho') }}: <strong x-text="selectedGift.stock"></strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Thông tin giao nhận hàng -->
                            <div class="space-y-3.5 pt-1">
                                <div class="flex items-center gap-1.5 border-b border-gray-100 dark:border-slate-800 pb-1.5">
                                    <span class="w-1 h-3 bg-shopee rounded-full"></span>
                                    <h5 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Thông tin nhận quà') }}</h5>
                                </div>
                                
                                <!-- Họ tên người nhận -->
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-gray-450 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                        {{ __('Họ và tên người nhận') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="fullname" required value="{{ old('fullname', auth()->user()->name) }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-800 dark:text-white transition-all">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Số điện thoại nhận quà -->
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-gray-450 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                            {{ __('Số điện thoại') }} <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="phone" required value="{{ old('phone', auth()->user()->phone) }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-800 dark:text-white transition-all">
                                    </div>

                                    <!-- Email nhận thông tin -->
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-gray-450 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                            {{ __('Email liên hệ') }} <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" name="email" required value="{{ old('email', auth()->user()->email) }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-850 dark:text-white transition-all">
                                    </div>
                                </div>

                                <!-- Địa chỉ giao nhận (Chỉ hiển thị bắt buộc khi đổi sản phẩm vật lý) -->
                                <div x-show="selectedGift.type === 'physical'" x-transition class="space-y-1">
                                    <label class="block text-[10px] font-bold text-gray-450 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        {{ __('Địa chỉ nhận hàng') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="address" :required="selectedGift.type === 'physical'" placeholder="{{ __('Số nhà, ngõ/đường, xã/phường, quận/huyện, tỉnh...') }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-800 dark:text-white transition-all">
                                </div>

                                <!-- Ghi chú yêu cầu của người đổi -->
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-gray-450 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                        {{ __('Lời nhắn / Ghi chú thêm') }}
                                    </label>
                                    <textarea name="notes" rows="2" placeholder="{{ __('Ví dụ: Lấy thẻ Viettel, hoặc chọn size M, màu xanh,...') }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-800 dark:text-white transition-all"></textarea>
                                </div>
                            </div>

                            <!-- Footer của form quy đổi -->
                            <div class="pt-3 border-t border-gray-100 dark:border-slate-800/80 flex justify-end gap-2">
                                <button type="button" @click="exchangeModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-150/80 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all active:scale-95">{{ __('Huỷ') }}</button>
                                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-shopee/10 flex items-center gap-1.5 active:scale-95">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    {{ __('Xác nhận đổi') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            <!-- MODAL CHI TIẾT ĐƠN ĐỔI QUÀ VÀ PHẢN HỒI -->
            <template x-teleport="body">
                <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full shadow-2xl border border-gray-100/80 dark:border-slate-850 overflow-hidden" @click.away="detailModalOpen = false" x-transition>
                        
                        <!-- Header modal -->
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800/80 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                <div class="w-7 h-7 bg-shopee/10 rounded-lg flex items-center justify-center text-shopee">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                </div>
                                {{ __('Chi tiết đơn đổi quà') }}
                            </h3>
                            <button @click="detailModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                                <i data-lucide="x" class="w-4.5 h-4.5"></i>
                            </button>
                        </div>
                        
                        <div class="p-6 space-y-5">
                            <!-- Trạng thái đơn hàng -->
                            <div class="flex justify-between items-center border-b border-gray-100 dark:border-slate-800/60 pb-3">
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 dark:text-slate-500 tracking-wider block">{{ __('Mã giao dịch') }}</span>
                                    <span class="font-mono font-bold text-gray-700 dark:text-gray-300 text-xs" x-text="activeRedemption.code || '#' + activeRedemption.id"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] uppercase font-bold text-gray-400 dark:text-slate-500 tracking-wider block">{{ __('Trạng thái') }}</span>
                                    <template x-if="activeRedemption.status === 'pending'">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 dark:bg-yellow-950/20 dark:text-yellow-400 dark:border-yellow-900/30">{{ __('Chờ xử lý') }}</span>
                                    </template>
                                    <template x-if="activeRedemption.status === 'approved'">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Thành công') }}</span>
                                    </template>
                                    <template x-if="activeRedemption.status === 'rejected'">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30">{{ __('Bị từ chối') }}</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Tóm tắt phần quà đã đổi -->
                            <div class="flex items-center gap-3 bg-slate-50/50 dark:bg-slate-800/10 p-3 rounded-xl border border-gray-150/45 dark:border-slate-800/40">
                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-900 border border-gray-205 dark:border-slate-800 overflow-hidden flex items-center justify-center shrink-0 shadow-inner">
                                    <template x-if="activeRedemption.gift?.image">
                                        <img :src="activeRedemption.gift?.image" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!activeRedemption.gift?.image">
                                        <div class="w-full h-full bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-gray-400">
                                            <i data-lucide="gift" class="w-5 h-5"></i>
                                        </div>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm truncate" x-text="activeRedemption.gift?.title"></h4>
                                    <p class="text-[10px] text-gray-400 font-medium mt-0.5 flex items-center gap-1.5">
                                        <span>{{ __('Phí đổi:') }} <strong class="text-shopee dark:text-shopee-light" x-text="formatCurrency(activeRedemption.amount)"></strong></span>
                                        <span class="text-gray-300">|</span>
                                        <span x-text="activeRedemption.created_at_formatted" class="font-mono"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- PHẢN HỒI GỬI QUÀ TỪ ADMIN -->
                            <template x-if="activeRedemption.status === 'approved' && (activeRedemption.gift_data || activeRedemption.notes)">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-1.5 border-b border-gray-100 dark:border-slate-850 pb-1.5">
                                        <span class="w-1 h-3 bg-green-550 rounded-full"></span>
                                        <h5 class="text-xs font-bold text-green-700 dark:text-green-400 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="gift" class="w-3.5 h-3.5"></i>
                                            {{ __('Thông tin quà tặng gửi tới bạn') }}
                                        </h5>
                                    </div>
                                    
                                    <!-- Hiển thị Code quà tặng/Link nhận -->
                                    <template x-if="activeRedemption.gift_data">
                                        <div class="p-4 bg-emerald-500/[0.06] dark:bg-emerald-500/[0.04] border border-emerald-500/20 rounded-xl space-y-2.5">
                                            <span class="text-[9px] uppercase font-bold text-emerald-650 dark:text-emerald-400 tracking-wider block flex items-center gap-1">
                                                <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                                {{ __('Mã quà tặng / Link nhận') }}
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <div class="bg-white dark:bg-slate-900/80 border border-emerald-200/60 dark:border-emerald-900/50 px-3.5 py-2 rounded-xl text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400 select-all break-all flex-grow shadow-inner whitespace-pre-line" x-text="activeRedemption.gift_data"></div>
                                                <button @click="copyText(activeRedemption.gift_data)" class="p-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-sm transition-all shrink-0 active:scale-95" :title="__('Sao chép')">
                                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- Ghi chú phản hồi đi kèm -->
                                    <template x-if="activeRedemption.notes">
                                        <div class="bg-gray-50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800/60 p-4 rounded-xl space-y-1">
                                            <span class="text-[9px] uppercase font-bold text-gray-400 dark:text-slate-500 tracking-wider block flex items-center gap-1">
                                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                                {{ __('Ghi chú từ hệ thống') }}
                                            </span>
                                            <p class="text-xs text-gray-650 dark:text-gray-300 leading-relaxed" x-text="activeRedemption.notes"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- PHẢN HỒI KHI ĐƠN BỊ TỪ CHỐI -->
                            <template x-if="activeRedemption.status === 'rejected'">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-1.5 border-b border-gray-100 dark:border-slate-850 pb-1.5">
                                        <span class="w-1 h-3 bg-red-500 rounded-full"></span>
                                        <h5 class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                            {{ __('Lý do từ chối quy đổi') }}
                                        </h5>
                                    </div>
                                    <div class="p-4 bg-red-500/[0.06] dark:bg-red-500/[0.04] border border-red-500/20 rounded-xl space-y-1">
                                        <span class="text-[9px] uppercase font-bold text-red-650 dark:text-red-405 tracking-wider block flex items-center gap-1">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                            {{ __('Nội dung từ chối') }}
                                        </span>
                                        <p class="text-xs text-red-700 dark:text-red-400 leading-relaxed font-bold" x-text="activeRedemption.notes"></p>
                                    </div>
                                    <p class="text-[10px] text-gray-400 dark:text-slate-500 italic">{{ __('Lưu ý: Số dư phí quy đổi đã được hoàn lại đầy đủ vào số dư ví khả dụng của bạn.') }}</p>
                                </div>
                            </template>

                            <!-- Thông tin người nhận hàng (Hiển thị chi tiết) -->
                            <template x-if="activeRedemption.shipping_info && activeRedemption.shipping_info.fullname">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-1.5 border-b border-gray-100 dark:border-slate-850 pb-1.5">
                                        <span class="w-1 h-3 bg-shopee rounded-full"></span>
                                        <h5 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                            {{ __('Thông tin nhận quà') }}
                                        </h5>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800/60 p-4 rounded-xl text-xs text-gray-650 dark:text-gray-300 space-y-2.5">
                                        <div class="flex justify-between border-b border-gray-100/50 dark:border-slate-800/50 pb-1.5">
                                            <span class="text-gray-400">{{ __('Họ và tên:') }}</span>
                                            <strong class="text-gray-800 dark:text-white" x-text="activeRedemption.shipping_info.fullname"></strong>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100/50 dark:border-slate-800/50 pb-1.5">
                                            <span class="text-gray-400">{{ __('Số điện thoại:') }}</span>
                                            <strong class="text-gray-800 dark:text-white font-mono" x-text="activeRedemption.shipping_info.phone"></strong>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100/50 dark:border-slate-800/50 pb-1.5">
                                            <span class="text-gray-400">{{ __('Email nhận:') }}</span>
                                            <span class="font-mono text-gray-800 dark:text-white" x-text="activeRedemption.shipping_info.email"></span>
                                        </div>
                                        <template x-if="activeRedemption.shipping_info.address">
                                            <div class="space-y-0.5 pt-1">
                                                <span class="text-gray-400 block">{{ __('Địa chỉ nhận hàng:') }}</span>
                                                <span class="text-gray-850 dark:text-white font-semibold block leading-relaxed" x-text="activeRedemption.shipping_info.address"></span>
                                            </div>
                                        </template>
                                        <template x-if="activeRedemption.shipping_info.notes">
                                            <div class="space-y-0.5 border-t border-gray-150/40 dark:border-slate-800/55 pt-2 mt-1.5">
                                                <span class="text-gray-400 block">{{ __('Lời nhắn của bạn:') }}</span>
                                                <span class="text-gray-700 dark:text-gray-300 italic block" x-text="'&ldquo;' + activeRedemption.shipping_info.notes + '&rdquo;'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex justify-end">
                                <button type="button" @click="detailModalOpen = false" class="px-5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Đóng') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('giftStoreHandler', () => ({
            exchangeModalOpen: false,
            detailModalOpen: false,
            selectedTag: 'all',
            loading: false,
            // Trạng thái đang tải lịch sử đổi quà - dùng để hiển thị spinner
            loadingRedemptions: false,
            // Trạng thái có bộ lọc đang hoạt động hay không - dùng để hiển thị/ẩn nút Đặt lại (quà tặng)
            hasActiveFilters: false,
            // Trạng thái bộ lọc lịch sử đổi quà có đang áp dụng không - dùng để hiển thị/ẩn nút Đặt lại (lịch sử)
            hasActiveRedemptionFilters: false,
            selectedGift: {
                id: null,
                title: '',
                price: 0,
                stock: 0,
                type: 'voucher',
                image: ''
            },
            activeRedemption: {
                id: null,
                code: '',
                gift: {},
                amount: 0,
                status: 'pending',
                created_at_formatted: '',
                shipping_info: {},
                gift_data: '',
                notes: ''
            },

            // Khởi tạo các sự kiện lắng nghe chuyển trang bằng AJAX và popstate của trình duyệt
            init() {
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);

                // Expose instance ra window để partial view (render qua AJAX) có thể gọi openDetailModal
                window._giftHandler = this;

                // Tính hasActiveFilters từ URL hiện tại khi trang load lần đầu
                this.updateActiveFiltersState(window.location.href);

                // Lắng nghe phân trang danh sách quà tặng qua AJAX
                const giftContainer = document.getElementById('gift-list-container');
                if (giftContainer) {
                    giftContainer.addEventListener('click', (e) => {
                        const link = e.target.closest('a');
                        if (link && link.getAttribute('href')) {
                            const href = link.getAttribute('href');
                            // Chỉ bắt các link chuyển trang của quà tặng (chứa page= nhưng không chứa redemptions_page=)
                            if (href.includes('page=') && !href.includes('redemptions_page=')) {
                                e.preventDefault();
                                e.stopPropagation();
                                this.loadGifts(href);
                            }
                        }
                    });
                }

                // Lắng nghe phân trang bảng lịch sử đổi quà qua AJAX
                const redemptionContainer = document.getElementById('redemption-list-container');
                if (redemptionContainer) {
                    redemptionContainer.addEventListener('click', (e) => {
                        const link = e.target.closest('a');
                        if (link && link.getAttribute('href')) {
                            const href = link.getAttribute('href');
                            // Bắt link phân trang của lịch sử (chứa page=)
                            if (href.includes('page=')) {
                                e.preventDefault();
                                e.stopPropagation();
                                // Đọc giá trị từ URL và gọi lại loadRedemptions với page tương ứng
                                const urlObj = new URL(href, window.location.origin);
                                const page = urlObj.searchParams.get('page') || 1;
                                this.loadRedemptions(page);
                            }
                        }
                    });
                }

                // Lắng nghe sự kiện popstate để chuyển đổi mượt mà khi người dùng nhấn nút Back/Forward
                window.addEventListener('popstate', () => {
                    this.loadGifts(window.location.href, false);
                });
            },

            // Kiểm tra xem URL hiện tại có chứa bộ lọc tìm kiếm nào không
            updateActiveFiltersState(url) {
                try {
                    const urlObj = new URL(url, window.location.origin);
                    const searchVal = urlObj.searchParams.get('search') || '';
                    const tagVal = urlObj.searchParams.get('tag') || 'all';
                    const typeVal = urlObj.searchParams.get('type') || 'all';
                    const sortVal = urlObj.searchParams.get('sort') || 'newest';
                    // Có bộ lọc khi bất kỳ tham số nào khác giá trị mặc định
                    this.hasActiveFilters = searchVal !== '' || tagVal !== 'all' || typeVal !== 'all' || sortVal !== 'newest';
                } catch (e) {
                    this.hasActiveFilters = false;
                }
            },

            // Mở modal thông tin chi tiết đơn đổi quà
            openDetailModal(redemption, createdAtFormatted) {
                this.activeRedemption = { ...redemption };
                this.activeRedemption.created_at_formatted = createdAtFormatted;
                this.detailModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận quy đổi quà tặng
            openExchangeModal(gift) {
                this.selectedGift = { ...gift };
                this.exchangeModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Gửi dữ liệu lọc tìm kiếm quà tặng qua AJAX
            submitFilter() {
                const form = document.getElementById('gift-filter-form');
                if (!form) return;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                const actionUrl = form.getAttribute('action') || window.location.pathname;
                const fullUrl = `${actionUrl}?${params.toString()}`;
                this.loadGifts(fullUrl);
            },

            // Tải lịch sử đổi quà qua AJAX - có tìm kiếm và lọc trạng thái
            loadRedemptions(page = 1) {
                this.loadingRedemptions = true;
                const container = document.getElementById('redemption-list-container');

                const searchInput = document.getElementById('redemption-search-input');
                const statusSelect = document.getElementById('redemption-status-select');

                const searchVal = searchInput ? searchInput.value.trim() : '';
                const statusVal = statusSelect ? statusSelect.value : 'all';

                // Cập nhật trạng thái hiển thị nút Đặt lại - có lọc khi search khác rỗng hoặc status khác 'all'
                this.hasActiveRedemptionFilters = searchVal !== '' || statusVal !== 'all';

                const params = new URLSearchParams();
                // Đọc giá trị từ các input tìm kiếm
                if (searchVal) {
                    params.set('redemption_search', searchVal);
                }
                if (statusVal !== 'all') {
                    params.set('redemption_status', statusVal);
                }
                if (page > 1) {
                    params.set('page', page);
                }

                const url = `{{ route('gifts.redemptions') }}?${params.toString()}`;

                axios.get(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (container) {
                        container.innerHTML = response.data;
                        // Khởi chạy lại các chỉ thị của AlpineJS trên HTML mới chèn qua AJAX
                        if (window.Alpine) {
                            window.Alpine.initTree(container);
                        }
                    }
                    // Khởi tạo lại Lucide icons trong nội dung mới AJAX
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .catch(error => {
                    console.error('Lỗi khi tải lịch sử đổi quà qua AJAX:', error);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: 'Không thể tải lịch sử. Vui lòng thử lại!', type: 'error' }
                    }));
                })
                .finally(() => {
                    this.loadingRedemptions = false;
                });
            },

            // Đặt lại bộ lọc quà tặng và tải lại danh sách
            resetFilters() {
                const form = document.getElementById('gift-filter-form');
                if (form) {
                    form.reset();
                    const searchInput = form.querySelector('input[name="search"]');
                    const tagSelect = form.querySelector('select[name="tag"]');
                    const typeSelect = form.querySelector('select[name="type"]');
                    const sortSelect = form.querySelector('select[name="sort"]');
                    if (searchInput) searchInput.value = '';
                    if (tagSelect) tagSelect.value = 'all';
                    if (typeSelect) typeSelect.value = 'all';
                    if (sortSelect) sortSelect.value = 'newest';
                }
                this.loadGifts('{{ route('gifts.index') }}');
            },

            // Đặt lại bộ lọc lịch sử đổi quà và tải lại danh sách
            resetRedemptionFilters() {
                const searchInput = document.getElementById('redemption-search-input');
                const statusSelect = document.getElementById('redemption-status-select');
                // Xóa giá trị các ô input về mặc định
                if (searchInput) searchInput.value = '';
                if (statusSelect) statusSelect.value = 'all';
                // Reload danh sách không có bộ lọc
                this.loadRedemptions();
            },

            // Tải danh sách quà tặng qua axios bất đồng bộ
            loadGifts(url, pushState = true) {
                this.loading = true;
                const container = document.getElementById('gift-list-container');
                if (container) {
                    container.innerHTML = this.getSkeletonHTML();
                }

                axios.get(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (container) {
                        container.innerHTML = response.data;
                        // Khởi chạy lại các chỉ thị của AlpineJS trên HTML mới chèn qua AJAX
                        if (window.Alpine) {
                            window.Alpine.initTree(container);
                        }
                    }
                    this.syncFormFields(url);
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                    if (pushState) {
                        window.history.pushState(null, '', url);
                    }
                })
                .catch(error => {
                    console.error('Lỗi khi tải danh sách quà tặng qua AJAX:', error);
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: 'Không thể tải danh sách quà tặng. Vui lòng thử lại!', type: 'error' } 
                    }));
                })
                .finally(() => {
                    this.loading = false;
                });
            },

            // Đồng bộ lại các trường của form và cập nhật trạng thái hasActiveFilters sau mỗi lần AJAX load
            syncFormFields(url) {
                const form = document.getElementById('gift-filter-form');
                if (!form) return;
                try {
                    const urlObj = new URL(url, window.location.origin);
                    const searchVal = urlObj.searchParams.get('search') || '';
                    const tagVal = urlObj.searchParams.get('tag') || 'all';
                    const typeVal = urlObj.searchParams.get('type') || 'all';
                    const sortVal = urlObj.searchParams.get('sort') || 'newest';

                    const searchInput = form.querySelector('input[name="search"]');
                    const tagSelect = form.querySelector('select[name="tag"]');
                    const typeSelect = form.querySelector('select[name="type"]');
                    const sortSelect = form.querySelector('select[name="sort"]');

                    if (searchInput) searchInput.value = searchVal;
                    if (tagSelect) tagSelect.value = tagVal;
                    if (typeSelect) typeSelect.value = typeVal;
                    if (sortSelect) sortSelect.value = sortVal;

                    // Cập nhật lại trạng thái nút Đặt lại sau khi đồng bộ form
                    this.updateActiveFiltersState(url);
                } catch (e) {
                    console.error('Lỗi khi đồng bộ các trường form:', e);
                }
            },

            // Hiệu ứng skeleton loading đẹp mắt khi tải quà tặng
            getSkeletonHTML() {
                return `
                    <div class="space-y-4 animate-pulse">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
                            <div class="h-4 bg-gray-200 dark:bg-slate-800 rounded w-32"></div>
                            <div class="flex gap-1.5">
                                <div class="h-6 bg-gray-200 dark:bg-slate-850 rounded-xl w-14"></div>
                                <div class="h-6 bg-gray-200 dark:bg-slate-850 rounded-xl w-14"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            ${Array(4).fill(0).map(() => `
                                <div class="bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                                    <div class="flex gap-4">
                                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gray-100 dark:bg-slate-800 shrink-0"></div>
                                        <div class="space-y-3 flex-1 min-w-0">
                                            <div class="h-3 bg-gray-200 dark:bg-slate-800 rounded w-16"></div>
                                            <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                                            <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded w-full"></div>
                                            <div class="h-3.5 bg-gray-200 dark:bg-slate-700 rounded w-24"></div>
                                        </div>
                                    </div>
                                    <div class="h-9 bg-gray-150 dark:bg-slate-800 rounded-2xl w-full"></div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            },

            formatCurrency(value) {
                if (value === undefined || value === null) return '0đ';
                return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value) + 'đ';
            },

            copyText(text) {
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { 
                            text: 'Đã sao chép mã thành công!', 
                            type: 'success' 
                        } 
                    }));
                }).catch(err => {
                    console.error('Không thể sao chép: ', err);
                });
            }
        }));
    });
</script>
@endsection

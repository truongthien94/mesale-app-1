@extends('layouts.admin')

@section('title', __('Quản Lý Thành Viên') . ' - ' . $siteName)

@php
    $selectableUserIds = $users->reject(fn($u) => $u->id === auth()->id())->pluck('id')->toArray();
    $hasFilter = request('search') || request('utm_source') || request('ip_address') || request('user_agent') || request('role') || request('status') || request('is_online') || request('referred_by') || request('is_referred') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="userAdminHandler()">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Quản Lý Thành Viên') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Xem thông tin thành viên, điều chỉnh ví tiền và phân quyền tài khoản') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút bật/tắt bộ lọc -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white hover:bg-gray-50 text-gray-700'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>
            <!-- Nút mở modal thống kê đăng ký thành viên -->
            <button @click="openStatsModal()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                {{ __('Thống kê') }}
            </button>
            <!-- Nút mở modal tuỳ chọn cột trước khi xuất file CSV -->
            <button @click="openExportModal()" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md">
                <i data-lucide="download" class="w-4 h-4"></i>
                {{ __('Xuất file CSV') }}
            </button>
        </div>
    </div>

    <!-- Widget thống kê tổng quan thành viên -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Thành viên có phát sinh đơn hàng -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center gap-4">
            <div class="w-12 h-12 shrink-0 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <i data-lucide="user-check" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 leading-tight">{{ number_format($stats['active']) }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ __('Đã phát sinh đơn') }}</p>
            </div>
        </div>

        <!-- Thành viên đang online -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center gap-4">
            <div class="w-12 h-12 shrink-0 rounded-2xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <i data-lucide="wifi" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 leading-tight">{{ number_format($stats['online']) }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ __('Đang online') }}</p>
            </div>
        </div>

        <!-- Quản trị viên -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center gap-4">
            <div class="w-12 h-12 shrink-0 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 leading-tight">{{ number_format($stats['admin']) }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ __('Quản trị viên') }}</p>
            </div>
        </div>

        <!-- Tài khoản bị khoá -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center gap-4">
            <div class="w-12 h-12 shrink-0 rounded-2xl bg-red-50 dark:bg-red-950/40 flex items-center justify-center text-red-600 dark:text-red-400">
                <i data-lucide="lock" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 leading-tight">{{ number_format($stats['suspended']) }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ __('Tài khoản bị khoá') }}</p>
            </div>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm -->
    <div x-show="showFilter"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-white p-4 rounded-3xl shadow-sm border border-gray-200"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form @submit.prevent="fetchUsers()" id="user-filter-form" action="{{ route('admin.users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Lưu vết cột sắp xếp và hướng sắp xếp hiện tại để AJAX gửi lên controller -->
            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">
            <div>
                <select name="limit" @change="fetchUsers()" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('Hiển thị 15 dòng') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('Hiển thị 30 dòng') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('Hiển thị 50 dòng') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('Hiển thị 100 dòng') }}</option>
                    <option value="200" {{ request('limit') == 200 ? 'selected' : '' }}>{{ __('Hiển thị 200 dòng') }}</option>
                    <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>{{ __('Hiển thị 500 dòng') }}</option>
                </select>
            </div>

            <div>
                <select name="role" @change="fetchUsers()" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Chọn vai trò') }}</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Quản trị viên (Admin)') }}</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>{{ __('Thành viên (User)') }}</option>
                </select>
            </div>

            <div>
                <select name="status" @change="fetchUsers()" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Trạng thái') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Đang hoạt động') }}</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>{{ __('Bị khoá') }}</option>
                </select>
            </div>

            <div>
                <select name="is_online" @change="fetchUsers()" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Trạng thái Online') }}</option>
                    <option value="online" {{ request('is_online') === 'online' ? 'selected' : '' }}>{{ __('Đang online') }}</option>
                    <option value="offline" {{ request('is_online') === 'offline' ? 'selected' : '' }}>{{ __('Đang offline') }}</option>
                </select>
            </div>

            <div>
                <select name="is_referred" @change="fetchUsers()" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Nguồn giới thiệu') }}</option>
                    <option value="yes" {{ request('is_referred') === 'yes' ? 'selected' : '' }}>{{ __('Được giới thiệu') }}</option>
                    <option value="no" {{ request('is_referred') === 'no' ? 'selected' : '' }}>{{ __('Đăng ký trực tiếp') }}</option>
                </select>
            </div>

            <div class="relative lg:col-span-2">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="{{ __('Tìm theo tên, email, số điện thoại...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Tìm kiếm theo UTM Source -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="compass" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="utm_source" 
                       value="{{ request('utm_source') }}"
                       placeholder="{{ __('Nguồn UTM...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Tìm kiếm theo IP -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="ip_address" 
                       value="{{ request('ip_address') }}"
                       placeholder="{{ __('Địa chỉ IP...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Tìm kiếm theo thiết bị (User Agent) -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="monitor" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_agent" 
                       value="{{ request('user_agent') }}"
                       placeholder="{{ __('Thiết bị...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Lọc theo Người giới thiệu -->
            <div class="relative lg:col-span-2">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="referred_by" 
                       value="{{ request('referred_by') }}"
                       placeholder="{{ __('Người giới thiệu (ID, Email, Mã GT)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Trống để cân bằng layout -->
            <div class="hidden lg:block lg:col-span-2"></div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                
                <button type="button" @click="resetFilter()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-350 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm shrink-0 flex items-center justify-center gap-1">
                    <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i>
                    <span>{{ __('Xóa lọc') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Bảng thành viên -->
    <div id="user-table-container" class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        @include('admin.users.partials.table')
    </div>

    <!-- MODAL: CỘNG TRỪ SỐ DƯ VÍ -->
    <template x-teleport="body">
        <div x-show="balanceModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="balanceModalOpen = false">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-950 border-b border-gray-100 dark:border-slate-850 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-slate-200 text-sm">{{ __('Điều chỉnh ví thành viên') }}</h3>
                    <button @click="balanceModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form :action="'/' + window.adminPrefix + '/users/' + activeUser.id + '/adjust-balance'" method="POST" class="p-6 space-y-4">
                    @csrf
                    
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-2xl text-[10px] text-indigo-800 dark:text-indigo-300">
                        {{ __('Thành viên:') }} <strong class="dark:text-indigo-200" x-text="activeUser.name"></strong> (<span class="dark:text-indigo-300" x-text="activeUser.email"></span>) <br>
                        {{ __('Số dư hiện tại:') }} <strong class="dark:text-indigo-200" x-text="formatCurrency(activeUser.balance)"></strong>
                    </div>
    
                    <!-- Cộng hay Trừ -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">{{ __('Loại điều chỉnh') }}</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="border dark:border-slate-800 p-2.5 rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none transition-all"
                                   :class="balanceType === 'add' ? 'border-shopee bg-orange-50/20 dark:bg-shopee/10 font-bold text-shopee dark:text-shopee-light' : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-850 text-gray-700 dark:text-slate-300'">
                                <input type="radio" name="type" value="add" x-model="balanceType" class="text-shopee focus:ring-shopee">
                                <span class="text-xs">{{ __('Cộng tiền (+)') }}</span>
                            </label>
                            <label class="border dark:border-slate-800 p-2.5 rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none transition-all"
                                   :class="balanceType === 'subtract' ? 'border-shopee bg-orange-50/20 dark:bg-shopee/10 font-bold text-shopee dark:text-shopee-light' : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-850 text-gray-700 dark:text-slate-300'">
                                <input type="radio" name="type" value="subtract" x-model="balanceType" class="text-shopee focus:ring-shopee">
                                <span class="text-xs">{{ __('Trừ tiền (-)') }}</span>
                            </label>
                        </div>
                    </div>
    
                    <!-- Số tiền -->
                    <div>
                        <label for="adjust_amount" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Số tiền (VNĐ)') }}</label>
                        <input type="number" name="amount" id="adjust_amount" required min="1" placeholder="{{ __('Nhập số tiền...') }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">
                    </div>
    
                    <!-- Lý do -->
                    <div>
                        <label for="adjust_reason" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Lý do điều chỉnh') }}</label>
                        <textarea name="reason" id="adjust_reason" required placeholder="{{ __('Lý do điều chỉnh để lưu log hoạt động...') }}" rows="3" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee"></textarea>
                    </div>
    
                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="balanceModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">{{ __('Thực thi giao dịch') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL: XÁC NHẬN XÓA THÀNH VIÊN -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="deleteModalOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa tài khoản') }}
                    </h3>
                    <button @click="deleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Avatar & User Info Card -->
                    <div class="flex items-center gap-3.5 p-4 bg-gray-50 dark:bg-slate-950/40 border border-gray-100 dark:border-slate-800/80 rounded-2xl">
                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-950/50 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0 shadow-sm border border-red-200/40 dark:border-red-900/30">
                            <i data-lucide="user-minus" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs font-black text-gray-800 dark:text-slate-200 truncate" x-text="activeUser.name"></h4>
                            <p class="text-[10px] font-semibold text-gray-400 dark:text-slate-450 truncate mt-0.5" x-text="activeUser.email"></p>
                        </div>
                    </div>

                    <!-- Financial Summary Grid -->
                    <div class="grid grid-cols-2 gap-3 text-[10px]">
                        <div class="p-3 bg-gray-50 dark:bg-slate-950/20 border border-gray-100 dark:border-slate-800/50 rounded-xl flex items-center gap-2.5">
                            <div class="p-1.5 rounded-lg bg-orange-50 dark:bg-orange-950/20 text-shopee shrink-0">
                                <i data-lucide="wallet" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-gray-450 dark:text-slate-500 block text-[9px] font-bold uppercase tracking-wider mb-0.5">{{ __('Số dư ví') }}</span>
                                <strong class="text-gray-800 dark:text-slate-200 font-extrabold" x-text="formatCurrency(activeUser.balance || 0)"></strong>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-slate-950/20 border border-gray-100 dark:border-slate-800/50 rounded-xl flex items-center gap-2.5">
                            <div class="p-1.5 rounded-lg bg-green-50 dark:bg-green-950/20 text-green-600 shrink-0">
                                <i data-lucide="arrow-down-to-line" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-gray-450 dark:text-slate-500 block text-[9px] font-bold uppercase tracking-wider mb-0.5">{{ __('Tổng đã rút') }}</span>
                                <strong class="text-gray-800 dark:text-slate-200 font-extrabold" x-text="formatCurrency(activeUser.total_withdrawn || 0)"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Destructive Checklist -->
                    <div class="p-4 bg-red-50/40 dark:bg-red-950/10 border border-red-100/50 dark:border-red-900/20 rounded-2xl space-y-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            <span class="text-[9px] font-black text-red-800 dark:text-red-300 uppercase tracking-widest">{{ __('Ảnh hưởng dữ liệu liên kết:') }}</span>
                        </div>
                        <ul class="grid grid-cols-2 gap-x-4 gap-y-2 text-[10px] text-gray-500 dark:text-slate-400 font-medium">
                            <li class="flex items-center gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0"></i>
                                <span>{{ __('Đơn hoàn tiền Cashback') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0"></i>
                                <span>{{ __('Yêu cầu rút tiền') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0"></i>
                                <span>{{ __('Biến động số dư ví') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0"></i>
                                <span>{{ __('Nhật ký hoạt động') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0"></i>
                                <span>{{ __('Hoa hồng tiếp thị MLM') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0"></i>
                                <span>{{ __('Link rút gọn & Click logs') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Safeguard Input Verification -->
                    <div class="pt-4 border-t border-gray-150 dark:border-slate-800/80">
                        <label class="flex items-center gap-3 p-3.5 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn tài khoản này') }}
                            </span>
                        </label>
                    </div>

                    <!-- Footer Action Buttons -->
                    <form :action="'/' + window.adminPrefix + '/users/' + activeUser.id" method="POST" class="flex justify-end gap-2 pt-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deleteModalOpen = false" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy bỏ') }}</button>
                        <button type="submit" :disabled="!confirmDeleteCheckbox" class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center gap-1.5">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 inline"></i>
                            {{ __('Đồng ý xóa') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- FLOATING BULK ACTIONS TOOLBAR -->
    <div x-show="selectedUsers.length > 0" x-cloak class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white/85 dark:bg-slate-900/95 backdrop-blur-md px-6 py-4 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800/80 flex items-center gap-4 transition-all duration-300 transform" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-shopee opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-shopee"></span>
            </span>
            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Đã chọn:') }} <strong class="text-shopee dark:text-shopee-light" x-text="selectedUsers.length"></strong> {{ __('thành viên') }}</span>
        </div>
        <div class="h-6 w-[1px] bg-gray-200 dark:bg-slate-800"></div>
        <div class="flex items-center gap-2">
            <!-- Nút điều chỉnh số dư hàng loạt -->
            <button type="button" @click="openBulkBalanceModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                {{ __('Cộng/Trừ tiền') }}
            </button>

            <!-- Nút xóa hàng loạt -->
            @if(auth()->user()->hasPermission('delete_users'))
            <button type="button" @click="openBulkDeleteModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                {{ __('Xóa hàng loạt') }}
            </button>
            @endif
        </div>
    </div>

    <!-- MODAL: CỘNG TRỪ SỐ DƯ VÍ HÀNG LOẠT -->
    <template x-teleport="body">
        <div x-show="bulkBalanceModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="bulkBalanceModalOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-950/40 border-b border-gray-100 dark:border-slate-850 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-slate-200 text-sm flex items-center gap-2">
                        <i data-lucide="coins" class="w-4.5 h-4.5 text-indigo-600 dark:text-indigo-400"></i>
                        {{ __('Điều chỉnh ví hàng loạt') }}
                    </h3>
                    <button @click="bulkBalanceModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form @submit.prevent="submitBulkBalance()" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="user_ids" :value="selectedUsers.join(',')">
                    
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-2xl text-[10px] text-indigo-800 dark:text-indigo-300 font-medium">
                        {{ __('Đang thực thi điều chỉnh số dư cho:') }} <strong class="dark:text-indigo-200 text-indigo-950" x-text="selectedUsers.length"></strong> {{ __('thành viên đã chọn.') }}
                    </div>
     
                    <!-- Cộng hay Trừ -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">{{ __('Loại điều chỉnh') }}</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="border dark:border-slate-800 p-2.5 rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none transition-all"
                                   :class="bulkBalanceType === 'add' ? 'border-shopee bg-orange-50/20 dark:bg-shopee/10 font-bold text-shopee dark:text-shopee-light' : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-850 text-gray-700 dark:text-slate-300'">
                                <input type="radio" name="type" value="add" x-model="bulkBalanceType" class="text-shopee focus:ring-shopee">
                                <span class="text-xs">{{ __('Cộng tiền (+)') }}</span>
                            </label>
                            <label class="border dark:border-slate-800 p-2.5 rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none transition-all"
                                   :class="bulkBalanceType === 'subtract' ? 'border-shopee bg-orange-50/20 dark:bg-shopee/10 font-bold text-shopee dark:text-shopee-light' : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-850 text-gray-700 dark:text-slate-300'">
                                <input type="radio" name="type" value="subtract" x-model="bulkBalanceType" class="text-shopee focus:ring-shopee">
                                <span class="text-xs">{{ __('Trừ tiền (-)') }}</span>
                            </label>
                        </div>
                    </div>
     
                    <!-- Số tiền -->
                    <div>
                        <label for="bulk_adjust_amount" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Số tiền (VNĐ)') }}</label>
                        <input type="number" name="amount" id="bulk_adjust_amount" x-model="bulkAmountInput" required min="1" placeholder="{{ __('Nhập số tiền áp dụng cho mỗi thành viên...') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee">
                    </div>
     
                    <!-- Lý do -->
                    <div>
                        <label for="bulk_adjust_reason" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Lý do điều chỉnh') }}</label>
                        <textarea name="reason" id="bulk_adjust_reason" x-model="bulkReasonInput" required placeholder="{{ __('Lý do điều chỉnh hàng loạt để lưu log hoạt động...') }}" rows="3" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee"></textarea>
                    </div>
     
                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="bulkBalanceModalOpen = false" :disabled="isSubmitting" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Huỷ') }}</button>
                        <button type="submit" :disabled="isSubmitting" class="px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md flex items-center justify-center min-w-[140px] disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                {{ __('Thực thi hàng loạt') }}
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Đang xử lý...') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL: XÁC NHẬN XÓA HÀNG LOẠT -->
    <template x-teleport="body">
        <div x-show="bulkDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="bulkDeleteModalOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa hàng loạt') }}
                    </h3>
                    <button @click="bulkDeleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/80 dark:border-red-900/30 rounded-2xl text-xs text-red-800 dark:text-red-300 space-y-3">
                        <p class="font-bold text-red-900 dark:text-red-200">{{ __('Cảnh báo hủy diệt dữ liệu hàng loạt:') }}</p>
                        <p class="leading-relaxed text-gray-700 dark:text-slate-350">
                            {{ __('Hành động này sẽ xóa vĩnh viễn') }} <strong class="text-red-600 dark:text-red-400 font-extrabold" x-text="selectedUsers.length"></strong> {{ __('thành viên đã chọn khỏi hệ thống.') }}
                        </p>
                        <p class="leading-relaxed text-[11px] text-red-750/90 dark:text-red-350/80 border-t border-red-100/50 dark:border-red-900/20 pt-2">
                            {{ __('Mọi dữ liệu liên quan bao gồm: đơn hàng hoàn tiền, lịch sử rút tiền, hoa hồng tiếp thị liên kết, biến động số dư, nhật ký hoạt động... cũng sẽ bị xóa sạch khỏi hệ thống và không thể khôi phục!') }}
                        </p>
                    </div>

                    <!-- Safeguard Input Verification -->
                    <div class="pt-4 border-t border-gray-150 dark:border-slate-800/80">
                        <label class="flex items-center gap-3 p-3.5 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmBulkDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn các thành viên đã chọn') }}
                            </span>
                        </label>
                    </div>

                    <!-- Footer Action Buttons -->
                    <form @submit.prevent="submitBulkDelete()" class="flex justify-end gap-2 pt-2">
                        @csrf
                        <input type="hidden" name="user_ids" :value="selectedUsers.join(',')">
                        <input type="hidden" name="confirm_checkbox" :value="confirmBulkDeleteCheckbox ? 1 : 0">
                        
                        <button type="button" @click="bulkDeleteModalOpen = false" :disabled="isSubmitting" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Hủy bỏ') }}</button>
                        <button type="submit" :disabled="!confirmBulkDeleteCheckbox || isSubmitting" class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center justify-center min-w-[160px]">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                {{ __('Đồng ý xóa hàng loạt') }}
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Đang xóa...') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: THỐNG KÊ ĐĂNG KÝ THÀNH VIÊN (Chart.js) -->
    <template x-teleport="body">
        <div x-show="statsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" 
                 @click.away="statsModalOpen = false"
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100" 
                 x-transition:leave="transition ease-in duration-150" 
                 x-transition:leave-start="opacity-100 scale-100" 
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Thống kê đăng ký thành viên') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium">{{ __('Biểu đồ đăng ký thành viên theo thời gian, thiết bị & quốc gia') }}</p>
                        </div>
                    </div>
                    <button @click="statsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Hàng điều khiển: Tabs khoảng thời gian + Tabs loại biểu đồ -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <!-- Tabs khoảng thời gian -->
                        <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
                            <button @click="loadStats('week')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="statsPeriod === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'">
                                {{ __('Tuần') }}
                            </button>
                            <button @click="loadStats('month')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="statsPeriod === 'month' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'">
                                {{ __('Tháng') }}
                            </button>
                            <button @click="loadStats('year')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="statsPeriod === 'year' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'">
                                {{ __('Năm') }}
                            </button>
                        </div>
                        <!-- Tabs loại biểu đồ: Thời gian / Thiết bị / Quốc gia / UTM -->
                        <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
                            <button @click="switchChartView('time')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="statsChartView === 'time' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="calendar" class="w-3 h-3"></i> {{ __('Thời gian') }}
                            </button>
                            <button @click="switchChartView('device')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="statsChartView === 'device' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="monitor" class="w-3 h-3"></i> {{ __('Thiết bị') }}
                            </button>
                            <button @click="switchChartView('utm')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="statsChartView === 'utm' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="compass" class="w-3 h-3"></i> {{ __('Nguồn UTM') }}
                            </button>
                            <button @click="switchChartView('country')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="statsChartView === 'country' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="globe" class="w-3 h-3"></i> {{ __('Quốc gia') }}
                            </button>
                        </div>
                        <!-- Tổng số đăng ký trong khoảng thời gian -->
                        <div class="text-right">
                            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Tổng đăng ký') }}</p>
                            <p class="text-lg font-black text-indigo-600 dark:text-indigo-400" x-text="statsTotal">0</p>
                        </div>
                    </div>

                    <!-- Biểu đồ -->
                    <div class="relative bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50" style="min-height: 300px;">
                        <!-- Loading spinner khi đang tải dữ liệu -->
                        <div x-show="statsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 rounded-2xl z-10">
                            <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <canvas id="registrationStatsChart" height="280"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: TUỲ CHỌN & SẮP XẾP CỘT TRƯỚC KHI XUẤT FILE CSV -->
    <template x-teleport="body">
        <div x-show="exportModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-3xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden"
                 @click.away="exportModalOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-950/30 dark:to-green-950/30 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Tuỳ chọn xuất danh sách thành viên') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium">{{ __('Chọn cột dữ liệu và kéo thả để sắp xếp thứ tự cột trong file CSV') }}</p>
                        </div>
                    </div>
                    <button @click="exportModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Hàng cấu hình: Phạm vi dữ liệu & Ký tự phân cách -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Phạm vi: Toàn bộ thành viên -->
                        <label class="cursor-pointer">
                            <input type="radio" value="all" x-model="exportScope" class="sr-only peer">
                            <div class="h-full p-3 rounded-2xl border-2 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/60 dark:peer-checked:bg-emerald-950/20 border-gray-200 dark:border-slate-800 hover:border-gray-300 dark:hover:border-slate-700">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <i data-lucide="users" class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400"></i>
                                    <span class="text-[11px] font-bold text-gray-800 dark:text-slate-200">{{ __('Toàn bộ thành viên') }}</span>
                                </div>
                                <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium" x-text="'{{ __('Khoảng') }} ' + totalUsers + ' {{ __('bản ghi') }}'"></p>
                            </div>
                        </label>

                        <!-- Phạm vi: Theo bộ lọc đang áp dụng -->
                        <label class="cursor-pointer">
                            <input type="radio" value="filtered" x-model="exportScope" class="sr-only peer">
                            <div class="h-full p-3 rounded-2xl border-2 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/60 dark:peer-checked:bg-emerald-950/20 border-gray-200 dark:border-slate-800 hover:border-gray-300 dark:hover:border-slate-700">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <i data-lucide="filter" class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400"></i>
                                    <span class="text-[11px] font-bold text-gray-800 dark:text-slate-200">{{ __('Theo bộ lọc hiện tại') }}</span>
                                </div>
                                <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium" x-text="filteredTotal + ' {{ __('bản ghi khớp bộ lọc') }}'"></p>
                            </div>
                        </label>

                        <!-- Ký tự phân cách của file CSV -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-450 uppercase tracking-wider mb-1.5">{{ __('Ký tự phân cách') }}</label>
                            <select x-model="exportDelimiter" class="block w-full px-3 py-2.5 border border-gray-200 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                <option value="comma">{{ __('Dấu phẩy (,) - Chuẩn CSV') }}</option>
                                <option value="semicolon">{{ __('Dấu chấm phẩy (;) - Excel VN') }}</option>
                                <option value="tab">{{ __('Tab - Dán vào Google Sheets') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Khu vực chọn & sắp xếp cột -->
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <p class="text-[11px] font-bold text-gray-700 dark:text-slate-300">{{ __('Cột dữ liệu xuất file') }}</p>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold"
                                      x-text="selectedExportCount + '/' + exportColumns.length"></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="toggleAllExportColumns(true)" class="px-2.5 py-1 text-[10px] font-bold text-gray-600 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-lg transition-all">{{ __('Chọn tất cả') }}</button>
                                <button type="button" @click="toggleAllExportColumns(false)" class="px-2.5 py-1 text-[10px] font-bold text-gray-600 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-lg transition-all">{{ __('Bỏ chọn') }}</button>
                                <button type="button" @click="resetExportColumns()" class="px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 rounded-lg transition-all flex items-center gap-1">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>{{ __('Mặc định') }}
                                </button>
                            </div>
                        </div>

                        <!-- Danh sách cột kéo thả (SortableJS) -->
                        <div x-ref="exportColumnList" class="max-h-[320px] overflow-y-auto rounded-2xl border border-gray-200 dark:border-slate-800 divide-y divide-gray-100 dark:divide-slate-800 bg-gray-50/40 dark:bg-slate-950/20">
                            <template x-for="(column, index) in exportColumns" :key="column.key">
                                <div class="flex items-center gap-3 px-3 py-2 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800/60 transition-colors"
                                     :class="!column.checked && 'opacity-50'">
                                    <!-- Tay cầm kéo thả -->
                                    <span class="export-col-drag cursor-grab active:cursor-grabbing text-gray-300 dark:text-slate-600 hover:text-gray-500 dark:hover:text-slate-400 shrink-0">
                                        <i data-lucide="grip-vertical" class="w-4 h-4 pointer-events-none"></i>
                                    </span>
                                    <!-- Số thứ tự cột trong file -->
                                    <span class="w-6 h-6 shrink-0 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 text-[10px] font-bold flex items-center justify-center"
                                          x-text="column.checked ? (selectedOrderOf(column.key)) : '–'"></span>
                                    <!-- Chọn / bỏ chọn cột -->
                                    <label class="flex-1 flex items-center gap-2.5 cursor-pointer min-w-0">
                                        <input type="checkbox" x-model="column.checked" class="rounded border-gray-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500/40 w-4 h-4 shrink-0">
                                        <span class="text-xs font-semibold text-gray-800 dark:text-slate-200 truncate" x-text="column.label"></span>
                                    </label>
                                    <!-- Nhóm dữ liệu -->
                                    <span class="hidden sm:inline-block shrink-0 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-450 text-[9px] font-bold" x-text="column.group"></span>
                                </div>
                            </template>
                        </div>
                        <p class="mt-2 text-[10px] text-gray-400 dark:text-slate-500 font-medium flex items-center gap-1">
                            <i data-lucide="info" class="w-3 h-3"></i>
                            {{ __('Kéo thả biểu tượng bên trái để đổi thứ tự cột. Cấu hình sẽ được ghi nhớ cho lần xuất file sau.') }}
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-950/40 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="exportModalOpen = false" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Huỷ') }}</button>
                    <button type="button" @click="submitExport()" :disabled="selectedExportCount === 0"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        {{ __('Tải file CSV') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    // Khai báo biến statsChartInstance ở phạm vi ngoài để tránh việc AlpineJS bọc Proxy
    // lên thực thể Chart.js, giúp ngăn chặn lỗi trắng biểu đồ khi chuyển đổi qua lại giữa các tab.
    let statsChartInstance = null;

    // Khoá lưu cấu hình cột xuất file CSV vào localStorage của trình duyệt (ghi nhớ cho lần xuất sau)
    const USER_EXPORT_PREF_KEY = 'admin_users_export_columns_v1';

    function userAdminHandler() {
        return {
            showFilter: @json($hasFilter),
            balanceModalOpen: false,
            deleteModalOpen: false,
            bulkBalanceModalOpen: false,
            bulkDeleteModalOpen: false,
            activeUser: {},
            balanceType: 'add',
            bulkBalanceType: 'add',
            confirmDeleteCheckbox: false,
            confirmBulkDeleteCheckbox: false,
            
            // Các trường nhập dữ liệu cho chức năng cộng trừ hàng loạt
            bulkAmountInput: '',
            bulkReasonInput: '',
            
            // Trạng thái đang gửi yêu cầu để kích hoạt hiệu ứng xoay loading và khoá nút bấm
            isSubmitting: false,
            
            selectedUsers: [],
            allSelected: false,
            selectableUserIds: @json($selectableUserIds),

            // Biến trạng thái cho modal tuỳ chọn xuất file CSV danh sách thành viên
            exportModalOpen: false,
            exportScope: 'all',
            exportDelimiter: 'comma',
            // Danh sách cột lấy từ controller, bổ sung thuộc tính checked theo cấu hình mặc định
            exportColumns: @json($exportColumns).map(col => ({ ...col, checked: !!col.default })),
            // Tổng số thành viên toàn hệ thống và tổng số bản ghi khớp bộ lọc hiện tại
            totalUsers: {{ $stats['total'] }},
            filteredTotal: {{ $users->total() }},

            // Biến trạng thái cho modal thống kê đăng ký thành viên
            statsModalOpen: false,
            statsPeriod: 'week',
            statsTotal: 0,
            statsLoading: false,
            statsChartView: 'time',
            statsRawData: null,

            // Hàm xử lý việc thay đổi cột và hướng sắp xếp (tăng/giảm) danh sách thành viên
            changeSort(field) {
                const sortByInput = document.querySelector('input[name="sort_by"]');
                const sortOrderInput = document.querySelector('input[name="sort_order"]');
                if (!sortByInput || !sortOrderInput) return;

                let currentSort = sortByInput.value;
                let currentOrder = sortOrderInput.value;

                if (currentSort === field) {
                    // Nếu click lại cột đang sắp xếp -> đảo chiều (tăng dần/giảm dần)
                    sortOrderInput.value = currentOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    // Nếu click sang cột mới -> mặc định sắp xếp giảm dần (desc)
                    sortByInput.value = field;
                    sortOrderInput.value = 'desc';
                }
                
                // Tải lại danh sách bắt đầu từ trang 1
                this.fetchUsers(1);
            },

            // Hàm khởi tạo AlpineJS component để cấu hình bắt sự kiện AJAX
            init() {
                // Sử dụng Event Delegation để lắng nghe sự kiện click trên các link phân trang trong bảng
                // Hỗ trợ cả trường hợp tìm theo class .pagination hoặc thẻ a chứa tham số page để tránh lỗi khi render view pagination khác nhau
                document.addEventListener('click', (e) => {
                    const paginationLink = e.target.closest('#user-table-container .pagination a') 
                                        || e.target.closest('#user-table-container a[href*="page="]');
                    if (paginationLink) {
                        e.preventDefault();
                        const url = new URL(paginationLink.href);
                        const page = url.searchParams.get('page') || 1;
                        this.fetchUsers(page);
                    }
                });

                // Tự động mở modal tương ứng khi truy cập bằng đường dẫn có hash từ thanh tìm kiếm nhanh (Ctrl + K)
                this.$nextTick(() => {
                    if (window.location.hash === '#stats') this.openStatsModal();
                    if (window.location.hash === '#export') this.openExportModal();
                });
            },

            // Tải danh sách thành viên bằng AJAX
            async fetchUsers(page = 1) {
                const container = document.getElementById('user-table-container');
                const loadingOverlay = document.getElementById('table-loading');
                if (loadingOverlay) loadingOverlay.classList.remove('hidden');

                // Lấy tất cả dữ liệu từ form bộ lọc
                const form = document.getElementById('user-filter-form');
                const formData = new FormData(form);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (value.trim() !== '') {
                        params.append(key, value);
                    }
                }
                params.set('page', page);

                // Cập nhật URL trên thanh địa chỉ trình duyệt để đồng bộ bộ lọc
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.pushState({}, '', newUrl);

                try {
                    const response = await fetch(newUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();

                    // Cập nhật HTML bảng thành viên
                    container.innerHTML = data.html;
                    // Cập nhật danh sách ID có thể chọn hàng loạt
                    this.selectableUserIds = data.selectableUserIds || [];
                    // Cập nhật tổng số bản ghi khớp bộ lọc để modal xuất file CSV hiển thị đúng
                    if (typeof data.total !== 'undefined') this.filteredTotal = data.total;
                    
                    // Reset lại lựa chọn
                    this.selectedUsers = [];
                    this.allSelected = false;

                    // Khởi tạo lại các icon Lucide
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                } catch (error) {
                    console.error('Fetch users error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi tải dữ liệu') }}",
                        text: "{{ __('Không thể làm mới danh sách thành viên. Vui lòng tải lại trang.') }}"
                    });
                } finally {
                    const loading = document.getElementById('table-loading');
                    if (loading) loading.classList.add('hidden');
                }
            },

            // Reset bộ lọc về mặc định
            resetFilter() {
                const form = document.getElementById('user-filter-form');
                if (form) {
                    form.reset();
                    // Đưa các thẻ input/select ẩn/hiện về rỗng
                    form.querySelectorAll('input, select').forEach(input => {
                        if (input.name === 'limit') {
                            input.value = '15';
                        } else {
                            input.value = '';
                        }
                    });
                }
                this.fetchUsers(1);
            },

            // Chọn hoặc bỏ chọn tất cả thành viên trong danh sách
            toggleAll() {
                if (this.allSelected) {
                    this.selectedUsers = [...this.selectableUserIds];
                } else {
                    this.selectedUsers = [];
                }
            },

            // Mở modal điều chỉnh số dư của 1 thành viên
            openBalanceModal(user) {
                this.activeUser = { ...user };
                this.balanceModalOpen = true;
                this.balanceType = 'add';
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa 1 thành viên
            openDeleteModal(user) {
                this.activeUser = { ...user };
                this.confirmDeleteCheckbox = false;
                this.deleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal điều chỉnh số dư hàng loạt cho các thành viên được chọn
            openBulkBalanceModal() {
                this.bulkBalanceType = 'add';
                this.bulkAmountInput = '';
                this.bulkReasonInput = '';
                this.bulkBalanceModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa hàng loạt thành viên được chọn
            openBulkDeleteModal() {
                this.confirmBulkDeleteCheckbox = false;
                this.bulkDeleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // ===== NHÓM HÀM XỬ LÝ MODAL TUỲ CHỌN CỘT TRƯỚC KHI XUẤT FILE CSV =====

            // Đếm số cột đang được chọn để xuất file
            get selectedExportCount() {
                return this.exportColumns.filter(col => col.checked).length;
            },

            // Lấy vị trí thực tế (thứ tự) của một cột trong file CSV sẽ xuất ra
            selectedOrderOf(key) {
                return this.exportColumns.filter(col => col.checked).findIndex(col => col.key === key) + 1;
            },

            // Mở modal xuất file: khôi phục cấu hình đã lưu và khởi tạo kéo thả sắp xếp cột
            openExportModal() {
                this.restoreExportPreference();
                // Mặc định chọn phạm vi "theo bộ lọc" nếu quản trị viên đang áp dụng bộ lọc tìm kiếm
                this.exportScope = this.hasActiveFilter() ? 'filtered' : 'all';
                this.exportModalOpen = true;
                this.$nextTick(() => {
                    this.initExportSortable();
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            // Kiểm tra xem form bộ lọc có đang nhập/chọn tiêu chí nào hay không
            hasActiveFilter() {
                const form = document.getElementById('user-filter-form');
                if (!form) return false;
                const skipFields = ['limit', 'sort_by', 'sort_order'];
                return Array.from(new FormData(form).entries())
                    .some(([key, value]) => !skipFields.includes(key) && String(value).trim() !== '');
            },

            // Khởi tạo kéo thả sắp xếp thứ tự cột bằng SortableJS (chỉ khởi tạo một lần duy nhất)
            initExportSortable() {
                const el = this.$refs.exportColumnList;
                if (!el || !window.Sortable || el.dataset.sortableReady === '1') return;
                el.dataset.sortableReady = '1';

                const self = this;
                window.Sortable.create(el, {
                    handle: '.export-col-drag',
                    animation: 180,
                    ghostClass: 'opacity-40',
                    onEnd(e) {
                        if (e.oldIndex === e.newIndex) return;
                        // Đồng bộ lại mảng dữ liệu theo đúng thứ tự DOM sau khi kéo thả
                        const moved = self.exportColumns.splice(e.oldIndex, 1)[0];
                        self.exportColumns.splice(e.newIndex, 0, moved);
                        self.saveExportPreference();
                        self.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    }
                });
            },

            // Chọn hoặc bỏ chọn toàn bộ cột dữ liệu
            toggleAllExportColumns(state) {
                this.exportColumns.forEach(col => col.checked = state);
                this.saveExportPreference();
            },

            // Khôi phục cấu hình cột về mặc định của hệ thống (cả thứ tự lẫn cột được chọn)
            resetExportColumns() {
                const defaults = @json($exportColumns);
                this.exportColumns = defaults.map(col => ({ ...col, checked: !!col.default }));
                try { localStorage.removeItem(USER_EXPORT_PREF_KEY); } catch (e) {}
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            // Lưu cấu hình cột (thứ tự + trạng thái chọn) vào localStorage để dùng cho lần xuất file sau
            saveExportPreference() {
                try {
                    localStorage.setItem(USER_EXPORT_PREF_KEY, JSON.stringify(
                        this.exportColumns.map(col => ({ key: col.key, checked: col.checked }))
                    ));
                } catch (e) {}
            },

            // Khôi phục cấu hình cột đã lưu, đồng thời tự động bổ sung các cột mới được thêm về sau
            restoreExportPreference() {
                let saved = null;
                try {
                    saved = JSON.parse(localStorage.getItem(USER_EXPORT_PREF_KEY) || 'null');
                } catch (e) {}
                if (!Array.isArray(saved) || saved.length === 0) return;

                const definitions = @json($exportColumns);
                const savedMap = new Map(saved.map(item => [item.key, !!item.checked]));

                // Sắp xếp theo thứ tự đã lưu trước, các cột mới chưa có trong cấu hình cũ sẽ được nối vào cuối
                const ordered = saved
                    .map(item => definitions.find(col => col.key === item.key))
                    .filter(Boolean);
                definitions.forEach(col => {
                    if (!savedMap.has(col.key)) ordered.push(col);
                });

                this.exportColumns = ordered.map(col => ({
                    ...col,
                    checked: savedMap.has(col.key) ? savedMap.get(col.key) : !!col.default
                }));
            },

            // Gửi yêu cầu tải file CSV theo đúng cấu hình cột, phạm vi và ký tự phân cách đã chọn
            submitExport() {
                const selected = this.exportColumns.filter(col => col.checked).map(col => col.key);
                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: "{{ __('Chưa chọn cột dữ liệu') }}",
                        text: "{{ __('Vui lòng chọn ít nhất một cột dữ liệu để xuất file.') }}"
                    });
                    return;
                }

                this.saveExportPreference();

                const params = new URLSearchParams();
                params.set('columns', selected.join(','));
                params.set('delimiter', this.exportDelimiter);
                params.set('scope', this.exportScope);

                // Nếu xuất theo bộ lọc, đính kèm toàn bộ tiêu chí lọc và thứ tự sắp xếp đang áp dụng
                if (this.exportScope === 'filtered') {
                    const form = document.getElementById('user-filter-form');
                    if (form) {
                        for (const [key, value] of new FormData(form).entries()) {
                            // Bỏ qua tham số phân trang vì file xuất ra luôn lấy đầy đủ bản ghi
                            if (key === 'limit') continue;
                            if (String(value).trim() !== '') params.append(key, value);
                        }
                    }
                }

                window.location.href = "{{ route('admin.users.export') }}?" + params.toString();
                this.exportModalOpen = false;
            },

            // Gửi yêu cầu điều chỉnh số dư ví hàng loạt bằng AJAX (Fetch API)
            async submitBulkBalance() {
                if (this.isSubmitting) return;

                if (!this.bulkAmountInput || this.bulkAmountInput <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi kiểm tra dữ liệu') }}",
                        text: "{{ __('Số tiền điều chỉnh tối thiểu là 1đ.') }}"
                    });
                    return;
                }

                if (!this.bulkReasonInput || !this.bulkReasonInput.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi kiểm tra dữ liệu') }}",
                        text: "{{ __('Vui lòng nhập lý do điều chỉnh số dư để lưu nhật ký.') }}"
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    const response = await fetch('{{ route("admin.users.bulk_adjust_balance") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            user_ids: this.selectedUsers.join(','),
                            type: this.bulkBalanceType,
                            amount: this.bulkAmountInput,
                            reason: this.bulkReasonInput
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkBalanceModalOpen = false;
                        this.bulkAmountInput = '';
                        this.bulkReasonInput = '';
                        this.selectedUsers = [];
                        this.allSelected = false;

                        // Thông báo thành công đẹp mắt
                        await Swal.fire({
                            icon: 'success',
                            title: "{{ __('Thành công') }}",
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Tải lại bảng thành viên bằng AJAX thay vì reload trang
                        this.fetchUsers(1);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: "{{ __('Thất bại') }}",
                            text: data.message || "{{ __('Có lỗi xảy ra.') }}"
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi hệ thống') }}",
                        text: "{{ __('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.') }}"
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Gửi yêu cầu xóa hàng loạt thành viên bằng AJAX (Fetch API)
            async submitBulkDelete() {
                if (this.isSubmitting) return;

                if (!this.confirmBulkDeleteCheckbox) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi xác nhận') }}",
                        text: "{{ __('Vui lòng tích chọn ô xác nhận trước khi thực hiện xóa.') }}"
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    // Gọi thẳng phương thức DELETE đúng như khai báo của route.
                    // Lưu ý: KHÔNG dùng POST kèm _method trong body JSON vì Laravel chỉ đọc _method
                    // từ dữ liệu form-encoded hoặc query string, dẫn đến request bị trả về lỗi 405.
                    const response = await fetch('{{ route("admin.users.bulk_destroy") }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            user_ids: this.selectedUsers.join(','),
                            confirm_checkbox: this.confirmBulkDeleteCheckbox ? 1 : 0
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkDeleteModalOpen = false;
                        this.confirmBulkDeleteCheckbox = false;
                        this.selectedUsers = [];
                        this.allSelected = false;

                        // Thông báo thành công
                        await Swal.fire({
                            icon: 'success',
                            title: "{{ __('Thành công') }}",
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Tải lại bảng thành viên bằng AJAX
                        this.fetchUsers(1);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: "{{ __('Thất bại') }}",
                            text: data.message || "{{ __('Không thể thực hiện xóa hàng loạt.') }}"
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi hệ thống') }}",
                        text: "{{ __('Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại.') }}"
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
            },

            // Mở modal thống kê và tải dữ liệu lần đầu (mặc định là tuần)
            openStatsModal() {
                this.statsModalOpen = true;
                this.statsChartView = 'time';
                setTimeout(() => {
                    if (window.lucide) window.lucide.createIcons();
                    this.loadStats('week');
                }, 100);
            },

            // Chuyển đổi tab biểu đồ (Thời gian / Thiết bị / Quốc gia)
            switchChartView(view) {
                this.statsChartView = view;
                if (this.statsRawData) {
                    this.renderChart(this.statsRawData, this.statsPeriod);
                }
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Gọi API lấy dữ liệu thống kê và render biểu đồ Chart.js
            async loadStats(period) {
                this.statsPeriod = period;
                this.statsLoading = true;

                try {
                    const response = await fetch(`{{ route('admin.users.registration_stats') }}?period=${period}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Network error');
                    const result = await response.json();

                    if (result.success) {
                        this.statsTotal = result.total;
                        this.statsRawData = result; // Lưu trữ dữ liệu thô để phục vụ switch tab biểu đồ nhanh
                        this.renderChart(result, period);
                    }
                } catch (error) {
                    console.error('Stats fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi tải dữ liệu') }}",
                        text: "{{ __('Không thể tải dữ liệu thống kê. Vui lòng thử lại.') }}"
                    });
                } finally {
                    this.statsLoading = false;
                }
            },

            // Tạo hoặc cập nhật biểu đồ Chart.js với dữ liệu mới
            // Sử dụng thực thể statsChartInstance nằm ngoài x-data để tránh lỗi Proxy của AlpineJS
            renderChart(data, period) {
                const canvas = document.getElementById('registrationStatsChart');
                if (!canvas) return;

                // Hủy biểu đồ cũ nếu tồn tại để tránh rò rỉ bộ nhớ và không bị đè canvas cũ
                if (statsChartInstance) {
                    statsChartInstance.destroy();
                    statsChartInstance = null;
                }

                const ctx = canvas.getContext('2d');

                // Xác định tiêu đề dựa trên khoảng thời gian đang xem
                const periodLabels = {
                    week: "{{ __('7 ngày gần nhất') }}",
                    month: "{{ __('30 ngày gần nhất') }}",
                    year: "{{ __('12 tháng gần nhất') }}"
                };

                let chartLabels, chartData, chartLabel, chartType, maxBarThickness, indexAxis;
                
                // Cấu hình linh hoạt các trục tọa độ của biểu đồ tùy thuộc vào loại dữ liệu thống kê
                if (this.statsChartView === 'time') {
                    chartLabels = data.labels;
                    chartData = data.data;
                    chartLabel = "{{ __('Số đăng ký') }}";
                    chartType = 'bar';
                    maxBarThickness = 40;
                    indexAxis = 'x'; // Biểu đồ dạng cột đứng cho thời gian
                } else if (this.statsChartView === 'device') {
                    chartLabels = data.device_labels;
                    chartData = data.device_data;
                    chartLabel = "{{ __('Số đăng ký theo thiết bị') }}";
                    chartType = 'bar';
                    maxBarThickness = 32;
                    indexAxis = 'y'; // Biểu đồ dạng thanh nằm ngang giúp đọc tên thiết bị rõ ràng, không bị méo chữ
                } else if (this.statsChartView === 'utm') {
                    chartLabels = data.utm_labels;
                    chartData = data.utm_data;
                    chartLabel = "{{ __('Số đăng ký theo nguồn UTM') }}";
                    chartType = 'bar';
                    maxBarThickness = 32;
                    indexAxis = 'y'; // Biểu đồ dạng thanh nằm ngang giúp đọc tên nguồn UTM dễ dàng
                } else {
                    chartLabels = data.country_labels;
                    chartData = data.country_data;
                    chartLabel = "{{ __('Số đăng ký theo quốc gia') }}";
                    chartType = 'bar';
                    maxBarThickness = 32;
                    indexAxis = 'y'; // Biểu đồ dạng thanh nằm ngang giúp đọc tên quốc gia dễ dàng
                }

                // Khởi tạo thực thể Chart.js mới và gán vào biến lưu trữ ngoài
                statsChartInstance = new Chart(ctx, {
                    type: chartType,
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: chartLabel,
                            data: chartData,
                            backgroundColor: 'rgba(99, 102, 241, 0.7)',
                            hoverBackgroundColor: 'rgba(99, 102, 241, 0.9)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: maxBarThickness,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: indexAxis,
                        animation: {
                            duration: 600,
                            easing: 'easeInOutQuart'
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: periodLabels[period] || '',
                                font: { size: 13, weight: '700' },
                                color: '#6366f1',
                                padding: { bottom: 16 }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { size: 12, weight: '700' },
                                bodyFont: { size: 11 },
                                padding: 12,
                                cornerRadius: 10,
                                displayColors: false,
                                callbacks: {
                                    title: (items) => items[0].label,
                                    label: (item) => `${chartLabel}: ${item.parsed[indexAxis === 'y' ? 'x' : 'y']} {{ __('thành viên') }}`
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 10, weight: '600' },
                                    color: '#94a3b8',
                                    maxRotation: period === 'year' && indexAxis === 'x' ? 45 : 0,
                                    stepSize: indexAxis === 'y' ? 1 : undefined,
                                    precision: indexAxis === 'y' ? 0 : undefined
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.1)'
                                },
                                ticks: {
                                    font: { size: 10, weight: '600' },
                                    color: '#94a3b8',
                                    stepSize: indexAxis === 'x' ? 1 : undefined,
                                    precision: indexAxis === 'x' ? 0 : undefined
                                }
                            }
                        }
                    }
                });
            }
        }
    }
</script>
@endsection

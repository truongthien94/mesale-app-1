@extends('layouts.admin')

@section('title', __('Quản Lý Giftcode') . ' - ' . $siteName)

@php
    $tab = request('tab', 'codes');
    $hasFilter = request('search') || request('state') || (request('limit') && request('limit') != 15);
    // Cấu hình hiển thị ở modal cấu hình
    $giftCodeEnabled = \App\Models\Setting::getVal('gift_code_enabled', '1');
    $giftCodeIntro = \App\Models\Setting::getVal('gift_code_intro', '');
@endphp

@section('content')
<div class="space-y-6" x-data="giftCodeHandler('{{ $tab }}', @json($codes->pluck('id')))">

    <!-- Tiêu đề trang -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-shopee to-shopee-light shrink-0"></span>
                {{ __('Quản Lý Giftcode') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ __('Phát hành mã quà tặng cho người dùng nhập nhận thưởng vào ví, mỗi mã có điều kiện sử dụng riêng biệt.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showFilter = !showFilter"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white dark:bg-slate-900 hover:bg-gray-50 text-gray-700 dark:text-slate-350'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)<span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>@endif
            </button>

            <button @click="openConfig()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="settings" class="w-4 h-4"></i>
                {{ __('Cấu hình') }}
            </button>

            <button x-show="activeTab === 'codes'" @click="openAdd()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('Thêm Mã Mới') }}
            </button>
        </div>
    </div>

    <!-- Thẻ thống kê nhanh -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Tổng số mã') }}</span>
                <div class="p-1.5 bg-shopee/10 rounded-lg"><i data-lucide="ticket" class="w-4 h-4 text-shopee"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['total_codes']) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Đang hiệu lực') }}</span>
                <div class="p-1.5 bg-green-50 dark:bg-green-950/30 rounded-lg"><i data-lucide="badge-check" class="w-4 h-4 text-green-500"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['active_codes']) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Lượt đã đổi') }}</span>
                <div class="p-1.5 bg-blue-50 dark:bg-blue-950/30 rounded-lg"><i data-lucide="repeat" class="w-4 h-4 text-blue-500"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['total_redemptions']) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Đã phát thưởng') }}</span>
                <div class="p-1.5 bg-amber-50 dark:bg-amber-950/30 rounded-lg"><i data-lucide="coins" class="w-4 h-4 text-amber-500"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['total_distributed'], 0, ',', '.') }}đ</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-slate-800">
        <button @click="changeTab('codes')"
                :class="activeTab === 'codes' ? 'border-shopee text-shopee font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-6 py-3 border-b-2 font-medium text-xs transition-all uppercase tracking-wider">
            {{ __('Danh Sách Mã') }}
        </button>
        <button @click="changeTab('redemptions')"
                :class="activeTab === 'redemptions' ? 'border-shopee text-shopee font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-6 py-3 border-b-2 font-medium text-xs transition-all uppercase tracking-wider">
            {{ __('Lịch Sử Đổi Mã') }}
        </button>
    </div>

    <!-- Bộ lọc -->
    <div x-show="showFilter" x-transition class="bg-white dark:bg-slate-900 rounded-3xl p-4 border border-gray-250/50 dark:border-slate-800/80 shadow-sm" style="display:none">
        <form action="{{ route('admin.giftcodes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input type="hidden" name="tab" :value="activeTab">
            <select name="limit" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                @foreach([15,30,50,100,200,500] as $l)
                    <option value="{{ $l }}" {{ request('limit') == $l ? 'selected' : '' }}>{{ __('Hiển thị :n dòng', ['n' => $l]) }}</option>
                @endforeach
            </select>
            <select x-show="activeTab === 'codes'" name="state" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                <option value="">{{ __('Tất cả trạng thái') }}</option>
                <option value="active" {{ request('state') === 'active' ? 'selected' : '' }}>{{ __('Đang hiệu lực') }}</option>
                <option value="paused" {{ request('state') === 'paused' ? 'selected' : '' }}>{{ __('Tạm dừng') }}</option>
                <option value="expired" {{ request('state') === 'expired' ? 'selected' : '' }}>{{ __('Hết hạn') }}</option>
                <option value="sold_out" {{ request('state') === 'sold_out' ? 'selected' : '' }}>{{ __('Hết lượt') }}</option>
            </select>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400"><i data-lucide="search" class="w-4 h-4"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Mã, tên hoặc thành viên...') }}" class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">{{ __('Lọc') }}</button>
                @if($hasFilter)
                    <a href="{{ route('admin.giftcodes.index', ['tab' => $tab]) }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all gap-1"><i data-lucide="x" class="w-3.5 h-3.5"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- ====================== TAB 1: DANH SÁCH MÃ ====================== -->
    <div x-show="activeTab === 'codes'" x-transition class="space-y-4">
        <!-- Thanh hành động hàng loạt -->
        <div x-show="selected.length > 0" x-transition class="flex items-center justify-between gap-3 bg-shopee/5 dark:bg-shopee/10 border border-shopee/20 rounded-2xl px-4 py-2.5" style="display:none">
            <span class="text-xs font-semibold text-shopee" x-text="'{{ __('Đã chọn') }} ' + selected.length + ' {{ __('mã') }}'"></span>
            <button @click="openBulkDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all shadow-sm">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> {{ __('Xóa đã chọn') }}
            </button>
        </div>

        @if($codes->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 py-16 text-center">
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center">
                    <i data-lucide="ticket" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                </div>
                <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">{{ __('Chưa có mã Giftcode nào.') }}</p>
                <button @click="openAdd()" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i> {{ __('Tạo mã đầu tiên') }}
                </button>
            </div>
        @else
            <!-- Bảng Desktop -->
            <div class="hidden md:block bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/70 dark:bg-slate-800/40 text-[10px] uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" @change="toggleAll($event)" :checked="allChecked" class="rounded border-gray-300 text-shopee focus:ring-shopee/30 w-4 h-4">
                            </th>
                            <th class="px-4 py-3 font-bold">{{ __('Mã / Chiến dịch') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('Phần thưởng') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('Lượt dùng') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('Hiệu lực') }}</th>
                            <th class="px-4 py-3 font-bold text-center">{{ __('Trạng thái') }}</th>
                            <th class="px-4 py-3 font-bold text-right">{{ __('Thao tác') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                        @foreach($codes as $code)
                            @include('admin.giftcodes.partials.code_row', ['code' => $code])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Card Mobile -->
            <div class="md:hidden space-y-3">
                @foreach($codes as $code)
                    @include('admin.giftcodes.partials.code_card', ['code' => $code])
                @endforeach
            </div>

            <div>{{ $codes->links() }}</div>
        @endif
    </div>

    <!-- ====================== TAB 2: LỊCH SỬ ĐỔI MÃ ====================== -->
    <div x-show="activeTab === 'redemptions'" x-transition class="space-y-4" style="display:none">
        @if($redemptions->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 py-16 text-center">
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center">
                    <i data-lucide="history" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                </div>
                <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">{{ __('Chưa có lượt đổi mã nào.') }}</p>
            </div>
        @else
            <div class="hidden md:block bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/70 dark:bg-slate-800/40 text-[10px] uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-bold">{{ __('Thành viên') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('Mã đã đổi') }}</th>
                            <th class="px-4 py-3 font-bold text-right">{{ __('Tiền thưởng') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('IP') }}</th>
                            <th class="px-4 py-3 font-bold text-right">{{ __('Thời gian') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                        @foreach($redemptions as $r)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $r->user->name ?? __('Đã xóa') }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $r->user->email ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3"><span class="font-mono font-bold text-shopee">{{ $r->code }}</span></td>
                                <td class="px-4 py-3 text-right font-extrabold text-green-600 dark:text-green-400">+{{ number_format($r->amount, 0, ',', '.') }}đ</td>
                                <td class="px-4 py-3 text-gray-400 font-mono">{{ $r->ip_address ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-500 dark:text-slate-400">{{ $r->created_at->format('H:i d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="md:hidden space-y-3">
                @foreach($redemptions as $r)
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono font-bold text-shopee text-sm">{{ $r->code }}</span>
                            <span class="font-extrabold text-green-600 dark:text-green-400 text-sm">+{{ number_format($r->amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="text-xs text-gray-700 dark:text-slate-300 font-semibold">{{ $r->user->name ?? __('Đã xóa') }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $r->user->email ?? '' }} · {{ $r->created_at->format('H:i d/m/Y') }}</div>
                    </div>
                @endforeach
            </div>
            <div>{{ $redemptions->links() }}</div>
        @endif
    </div>

    @include('admin.giftcodes.partials.form_modal')
    @include('admin.giftcodes.partials.config_modal')
    @include('admin.giftcodes.partials.delete_modals')
</div>
@endsection

@section('scripts')
<script>
    function giftCodeHandler(defaultTab, visibleIds) {
        return {
            activeTab: defaultTab || 'codes',
            showFilter: {{ $hasFilter ? 'true' : 'false' }},
            visibleIds: visibleIds || [],
            selected: [],

            // ----- Modal form thêm/sửa -----
            showFormModal: false,
            formMode: 'add',
            formAction: '',
            form: {},

            // ----- Modal cấu hình & xóa -----
            showConfigModal: false,
            showDeleteModal: false,
            deleteTarget: { code: '', action: '' },
            showBulkModal: false,
            bulkConfirmText: '',

            get allChecked() {
                return this.visibleIds.length > 0 && this.selected.length === this.visibleIds.length;
            },

            changeTab(tab) {
                this.activeTab = tab;
                this.selected = [];
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            },

            toggleAll(e) {
                this.selected = e.target.checked ? [...this.visibleIds] : [];
            },

            defaultForm() {
                return {
                    code: '', title: '', description: '',
                    reward_type: 'fixed', reward_amount: '', reward_min: '', reward_max: '',
                    max_uses: '', per_user_limit: 1,
                    starts_at: '', expires_at: '',
                    require_verified_email: 0, min_total_cashback: '', min_account_age_days: '', new_user_within_days: '',
                    status: 1
                };
            },

            openAdd() {
                this.formMode = 'add';
                this.formAction = '{{ route('admin.giftcodes.store') }}';
                this.form = this.defaultForm();
                this.showFormModal = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },

            openEdit(data) {
                this.formMode = 'edit';
                this.formAction = '{{ route('admin.giftcodes.update', ['giftCode' => '__ID__']) }}'.replace('__ID__', data.id);
                this.form = Object.assign(this.defaultForm(), data);
                this.showFormModal = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },

            openConfig() {
                this.showConfigModal = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },

            generateCode() {
                fetch('{{ route('admin.giftcodes.generate') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(d => { this.form.code = d.code; });
            },

            submitForm(e) {
                // Kiểm tra nhanh trước khi gửi để báo lỗi thân thiện
                if (!this.form.code || !this.form.code.trim()) {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng nhập mã Giftcode.') }}', type: 'error' } }));
                    return;
                }
            },

            openDelete(code, action) {
                this.deleteTarget = { code: code, action: action };
                this.showDeleteModal = true;
            },

            openBulkDelete() {
                this.bulkConfirmText = '';
                this.showBulkModal = true;
            },

            submitBulkDelete() {
                if (this.bulkConfirmText.trim().toUpperCase() !== 'XÓA HÀNG LOẠT') {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Từ khóa xác nhận không chính xác.') }}', type: 'error' } }));
                    return;
                }
                fetch('{{ route('admin.giftcodes.bulk_destroy') }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ ids: this.selected.join(','), confirm_text: this.bulkConfirmText })
                })
                .then(r => r.json())
                .then(d => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: d.message, type: d.status === 'success' ? 'success' : 'error' } }));
                    if (d.status === 'success') setTimeout(() => window.location.reload(), 800);
                });
            }
        };
    }
</script>
@endsection

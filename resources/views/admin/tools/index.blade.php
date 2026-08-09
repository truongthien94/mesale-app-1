@extends('layouts.admin')

@section('title', __('Công cụ Hệ thống') . ' - ' . $siteName)

@section('content')
<div class="space-y-6"
     x-data="{ 
         activeMainTab: '{{ session('import_result') ? 'manual' : 'auto' }}',
         showSyncModal: false,
         syncLoading: false,
         syncData: null,
         syncAccountName: '',
         syncError: null,
         activeTab: 'all',
         searchQuery: '', // Lưu trữ chuỗi tìm kiếm nhanh tên sản phẩm hoặc utm_content trong đối soát
         hasFilteredItems() { // Hàm kiểm tra xem có đơn hàng nào thỏa mãn bộ lọc hiện tại hay không để hiển thị thông báo
             if (!this.syncData || !this.syncData.details) return false;
             return this.syncData.details.some(item => {
                 const matchTab = this.activeTab === 'all' || this.activeTab === item.status_system;
                 const matchSearch = !this.searchQuery || 
                     (item.product_name || '').toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                     (item.utm_content || '').toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                     (item.order_sn || '').toLowerCase().includes(this.searchQuery.toLowerCase());
                 return matchTab && matchSearch;
             });
         },
         startSync(accountId, accountName, url) {
             this.syncAccountName = accountName;
             this.showSyncModal = true;
             this.syncLoading = true;
             this.syncData = null;
             this.syncError = null;
             this.activeTab = 'all';
             this.searchQuery = ''; // Reset chuỗi tìm kiếm khi mở tài khoản đồng bộ mới

             fetch(url, {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 }
             })
             .then(response => {
                 if (!response.ok) {
                     return response.json().then(err => { throw err; });
                 }
                 return response.json();
             })
             .then(data => {
                 this.syncLoading = false;
                 this.syncData = data;
                 // Tải lại các icon của Lucide sau khi dữ liệu hiển thị trong modal
                 setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
             })
             .catch(err => {
                 this.syncLoading = false;
                 this.syncError = err.error || err.message || 'Lỗi kết nối không xác định đến Shopee API.';
                 setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
             });
         },
         closeSyncModal() {
             // Đóng modal đối soát, tự động reload lại trang để cập nhật số dư/trạng thái nếu đã đồng bộ xong
             this.showSyncModal = false;
             if (!this.syncLoading && (this.syncData || this.syncError)) {
                 window.location.reload();
             }
         }
     }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Công cụ hệ thống') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Tự động đối soát đơn hàng hoàn tiền từ file báo cáo chuyển đổi Shopee.') }}</p>
        </div>
    </div>

    @if(config('app.demo'))
        <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-250 dark:border-yellow-900/50 text-yellow-800 dark:text-yellow-400 rounded-3xl p-6 sm:p-8 flex flex-col items-center justify-center text-center space-y-3 py-16">
            <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center text-yellow-500 animate-pulse">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-4">{{ __('Tính năng bị ẩn ở chế độ Demo') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md leading-relaxed">
                {{ __('Để bảo vệ thông tin tài khoản Affiliate và tránh các thao tác đối soát đơn hàng giả mạo, chức năng đồng bộ và đối soát đơn hàng hoàn tiền Shopee không được hiển thị và thực hiện ở chế độ dùng thử (Demo).') }}
            </p>
        </div>
    @else

    <!-- Tabs chính của Công cụ -->
    <div class="flex items-center gap-1.5 border-b border-gray-150 dark:border-slate-800 pb-px mb-6">
        <button type="button"
                @click="activeMainTab = 'auto'"
                :class="activeMainTab === 'auto' ? 'border-shopee text-shopee font-bold' : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="flex items-center gap-2 px-4 py-2.5 text-xs transition-all outline-none border-b-2 -mb-px">
            <i data-lucide="cpu" class="w-4 h-4"></i>
            {{ __('Đồng bộ đơn hàng tự động') }}
        </button>
        <button type="button"
                @click="activeMainTab = 'manual'"
                :class="activeMainTab === 'manual' ? 'border-shopee text-shopee font-bold' : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="flex items-center gap-2 px-4 py-2.5 text-xs transition-all outline-none border-b-2 -mb-px">
            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
            {{ __('Đồng bộ đơn hàng thủ công') }}
        </button>
    </div>

    <!-- Tab 2: Đồng bộ đơn hàng thủ công (Upload File) -->
    <div x-show="activeMainTab === 'manual'" class="space-y-6" style="display: none;" x-transition>
        <!-- Detailed Result Section -->
    @if(session('import_result'))
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800/50 shadow-sm p-6 md:p-8 space-y-6"
         x-data="{ activeTab: 'all' }">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 dark:border-slate-800 pb-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Kết quả đối soát chi tiết') }}</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Báo cáo chi tiết trạng thái cập nhật của lượt import vừa rồi.') }}</p>
            </div>
            
            <!-- Tabs Filter -->
            <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-slate-800 p-1 rounded-xl self-start sm:self-auto">
                <button @click="activeTab = 'all'" 
                        :class="activeTab === 'all' ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all">
                    {{ __('Tất cả') }} ({{ session('import_result.summary.total') }})
                </button>
                <button @click="activeTab = 'approved'" 
                        :class="activeTab === 'approved' ? 'bg-green-500 text-white shadow-sm shadow-green-500/10' : 'text-gray-500 dark:text-gray-400 hover:text-green-500'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all">
                    {{ __('Đã duyệt') }} ({{ session('import_result.summary.approved') }})
                </button>
                <button @click="activeTab = 'rejected'" 
                        :class="activeTab === 'rejected' ? 'bg-red-500 text-white shadow-sm shadow-red-500/10' : 'text-gray-500 dark:text-gray-400 hover:text-red-500'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all">
                    {{ __('Đã hủy') }} ({{ session('import_result.summary.rejected') }})
                </button>
                <button @click="activeTab = 'ignored'" 
                        :class="activeTab === 'ignored' ? 'bg-gray-500 text-white shadow-sm shadow-gray-500/10' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all">
                    {{ __('Bỏ qua') }} ({{ session('import_result.summary.ignored') }})
                </button>
            </div>
        </div>

        <!-- Summary Stats Card -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100 dark:border-slate-800/30">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Tổng số đơn') }}</span>
                <span class="block text-lg font-bold text-gray-800 dark:text-gray-200 mt-1">{{ session('import_result.summary.total') }}</span>
            </div>
            <div class="p-4 bg-green-50/30 dark:bg-green-950/5 rounded-2xl border border-green-100/40 dark:border-green-900/10">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-green-600/75 dark:text-green-500/75">{{ __('Đã phê duyệt') }}</span>
                <span class="block text-lg font-bold text-green-600 dark:text-green-400 mt-1">{{ session('import_result.summary.approved') }}</span>
            </div>
            <div class="p-4 bg-red-50/30 dark:bg-red-950/5 rounded-2xl border border-red-100/40 dark:border-red-900/10">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-red-600/75 dark:text-red-500/75">{{ __('Đã hủy / Từ chối') }}</span>
                <span class="block text-lg font-bold text-red-600 dark:text-red-400 mt-1">{{ session('import_result.summary.rejected') }}</span>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100 dark:border-slate-800/30">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-500/75 dark:text-gray-400/75">{{ __('Bỏ qua / Trùng') }}</span>
                <span class="block text-lg font-bold text-gray-600 dark:text-gray-400 mt-1">{{ session('import_result.summary.ignored') }}</span>
            </div>
        </div>

        <!-- Details Table -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-slate-800">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-slate-800/30 border-b border-gray-100 dark:border-slate-800">
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Mã đơn Shopee') }}</th>
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Tên sản phẩm') }}</th>
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Tiền hoàn F0') }}</th>
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 text-center">{{ __('Trạng thái') }}</th>
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Chi tiết hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                    @foreach(session('import_result.details') as $item)
                    <tr x-show="activeTab === 'all' || activeTab === '{{ $item['status'] }}'"
                        class="hover:bg-gray-50/30 dark:hover:bg-slate-800/10 transition-colors">
                        <td class="p-3 font-mono font-bold text-gray-800 dark:text-gray-200">{{ $item['order_id'] }}</td>
                        <td class="p-3 max-w-[200px] truncate text-gray-600 dark:text-gray-400" title="{{ $item['product_name'] }}">{{ $item['product_name'] }}</td>
                        <td class="p-3 font-bold text-gray-700 dark:text-gray-300">
                            @if($item['amount'] > 0)
                                +{{ number_format($item['amount']) }}đ
                            @else
                                -
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            @if($item['status'] === 'approved')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/20">{{ __('Đã duyệt') }}</span>
                            @elseif($item['status'] === 'rejected')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/20">{{ __('Đã hủy') }}</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-slate-700/30">{{ __('Bỏ qua') }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-gray-500 dark:text-gray-400 font-medium">{{ $item['message'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Main Card Upload -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800/50 shadow-sm overflow-hidden" 
         x-data="{ isDragging: false }">
        <div class="p-6 md:p-8">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4">{{ __('Tải lên file báo cáo đối soát') }}</h3>
            
            <form action="{{ route('admin.tools.import_commission') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <!-- Drag and Drop Area -->
                <div class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all duration-300 group"
                     :class="{
                         'border-shopee bg-shopee/5 dark:bg-shopee/5': isDragging,
                         'border-gray-200 dark:border-slate-800 hover:border-shopee/55 hover:bg-gray-50/50 dark:hover:bg-slate-800/20': !isDragging
                     }"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $dispatch('file-selected')"
                     @file-selected.window="
                         const file = $refs.fileInput.files[0];
                         if (file) {
                             $refs.fileNameText.innerText = file.name;
                             $refs.fileSizeText.innerText = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                             $refs.fileSelectedInfo.classList.remove('hidden');
                             $refs.fileEmptyInfo.classList.add('hidden');
                         }
                     ">
                     
                    <input type="file" 
                           name="csv_file" 
                           id="csv_file" 
                           x-ref="fileInput"
                           accept=".csv,.txt"
                           required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                           @change="$dispatch('file-selected')">
                           
                    <!-- Empty State Info -->
                    <div x-ref="fileEmptyInfo" class="space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-shopee/10 dark:bg-shopee/20 flex items-center justify-center mx-auto text-shopee transition-transform duration-300 group-hover:scale-110">
                            <i data-lucide="cloud-upload" class="w-6 h-6"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ __('Kéo thả tệp tin vào đây, hoặc click để chọn') }}
                            </p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">
                                {{ __('Hỗ trợ file báo cáo chuyển đổi Shopee dạng .csv hoặc .txt (Tối đa 10MB)') }}
                            </p>
                        </div>
                    </div>

                    <!-- Selected State Info -->
                    <div x-ref="fileSelectedInfo" class="hidden space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-green-500/10 dark:bg-green-500/20 flex items-center justify-center mx-auto text-green-500">
                            <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                        </div>
                        <div class="space-y-1">
                            <p x-ref="fileNameText" class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[300px] mx-auto"></p>
                            <p x-ref="fileSizeText" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold"></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit" 
                            class="px-5 py-2.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/15 flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4 animate-spin-hover"></i>
                        {{ __('Bắt đầu đối soát ngay') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Instruction Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800/50 shadow-sm p-6 md:p-8 space-y-6">
        <div>
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Hướng dẫn cấu trúc file CSV') }}</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Để hệ thống đối soát chính xác, file CSV tải lên cần đảm bảo các cấu trúc sau:') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Info: Columns definition -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-slate-800 pb-2">
                    {{ __('Các cột bắt buộc') }}
                </h4>
                
                <ul class="space-y-3">
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-shopee/10 dark:bg-shopee/20 flex items-center justify-center shrink-0 mt-0.5 text-shopee">
                            <i data-lucide="key-round" class="w-3 h-3"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">ID đơn hàng</span>
                            <span class="block text-[10px] text-gray-400 dark:text-gray-500 font-medium">Khớp với mã đơn hàng Shopee đã lưu trên hệ thống web (ví dụ: 2605247BFV6PYK).</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-shopee/10 dark:bg-shopee/20 flex items-center justify-center shrink-0 mt-0.5 text-shopee">
                            <i data-lucide="info" class="w-3 h-3"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Trạng thái đặt hàng</span>
                            <span class="block text-[10px] text-gray-400 dark:text-gray-500 font-medium">Chứa trạng thái đơn trên Shopee. Hệ thống sẽ tự động duyệt hoặc hủy theo trạng thái này.</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-shopee/10 dark:bg-shopee/20 flex items-center justify-center shrink-0 mt-0.5 text-shopee">
                            <i data-lucide="coins" class="w-3 h-3"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Tổng hoa hồng đơn hàng(₫) / Tổng hoa hồng sản phẩm(₫)</span>
                            <span class="block text-[10px] text-gray-400 dark:text-gray-500 font-medium">(Không bắt buộc) Cập nhật số tiền hoa hồng thực tế và tính toán lại tiền hoàn thật cho User.</span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Right Info: Status rules -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-slate-800 pb-2">
                    {{ __('Quy tắc xử lý trạng thái') }}
                </h4>
                
                <div class="space-y-3">
                    <div class="p-3 bg-green-50/50 dark:bg-green-950/10 border border-green-100 dark:border-green-900/30 rounded-2xl flex items-start gap-2.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i>
                        <div class="text-[10px]">
                            <p class="font-bold text-green-800 dark:text-green-400 uppercase tracking-wider mb-0.5">Phê duyệt (Approved)</p>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">Khi cột <strong>Trạng thái đặt hàng</strong> chứa các giá trị: <em>Hoàn thành</em>, <em>Thanh toán</em>, <em>Đối soát</em> hoặc <em>Completed</em>.</p>
                        </div>
                    </div>

                    <div class="p-3 bg-red-50/50 dark:bg-red-950/10 border border-red-100 dark:border-red-900/30 rounded-2xl flex items-start gap-2.5">
                        <i data-lucide="x-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
                        <div class="text-[10px]">
                            <p class="font-bold text-red-800 dark:text-red-400 uppercase tracking-wider mb-0.5">Từ chối (Rejected)</p>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">Khi cột <strong>Trạng thái đặt hàng</strong> chứa các giá trị: <em>Hủy</em> hoặc <em>Cancel</em>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Tab 1: Đồng bộ đơn hàng tự động (Shopee Cookie API) -->
    <div x-show="activeMainTab === 'auto'" class="space-y-6" x-transition>
        <!-- Automatic Cookie Sync Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- List Shopee Accounts -->
        <div class="md:col-span-2 bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800/50 shadow-sm p-6 md:p-8 space-y-6">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Tài khoản Shopee Affiliate tự động') }}</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Danh sách các tài khoản Shopee Affiliate được đồng bộ tự động bằng Cookie.') }}</p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-slate-800">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-slate-800/30 border-b border-gray-100 dark:border-slate-800">
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Tài khoản') }}</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Username (SPC_U)') }}</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Đồng bộ cuối') }}</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 text-center">{{ __('Trạng thái') }}</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 text-right">{{ __('Thao tác') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                        @forelse($shopeeAccounts as $account)
                        <tr class="hover:bg-gray-50/30 dark:hover:bg-slate-800/10 transition-colors">
                            <td class="p-3">
                                <span class="font-bold text-gray-850 dark:text-gray-200">{{ $account->name }}</span>
                            </td>
                            <td class="p-3 font-mono text-gray-600 dark:text-gray-400">{{ $account->username ?: 'N/A' }}</td>
                            <td class="p-3 text-gray-500 dark:text-gray-400">{{ $account->last_sync_at ? $account->last_sync_at->diffForHumans() : __('Chưa đồng bộ') }}</td>
                            <td class="p-3 text-center">
                                @if($account->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/20">{{ __('Đang hoạt động') }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/20" title="{{ $account->error_message }}">{{ __('Lỗi/Hết hạn') }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Nút Đồng bộ ngay -->
                                    <button type="button" 
                                            @click="startSync({{ $account->id }}, '{{ addslashes($account->name) }}', '{{ route('admin.tools.shopee_accounts.sync', $account->id) }}')"
                                            title="{{ __('Đồng bộ báo cáo ngay') }}"
                                            class="w-7 h-7 flex items-center justify-center rounded-xl bg-shopee/10 text-shopee hover:bg-shopee hover:text-white transition-all">
                                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <!-- Nút Xóa tài khoản -->
                                    <form action="{{ route('admin.tools.shopee_accounts.destroy', $account->id) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                title="{{ __('Xóa tài khoản') }}"
                                                class="w-7 h-7 flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @if($account->status === 'expired' && $account->error_message)
                        <tr class="bg-red-50/10 dark:bg-red-950/5">
                            <td colspan="5" class="p-3 text-[10px] text-red-500 border-t-0 font-medium">
                                <strong>Lỗi:</strong> {{ $account->error_message }}
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-slate-700"></i>
                                {{ __('Chưa có tài khoản Shopee Affiliate nào được kết nối.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Shopee Account Form -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800/50 shadow-sm p-6 md:p-8 space-y-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Kết nối tài khoản mới') }}</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Thêm cookie dạng JSON của Shopee Affiliate để tự động đồng bộ hóa.') }}</p>
            </div>

            <form action="{{ route('admin.tools.shopee_accounts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
                  x-data="{ uploadType: 'file' }">
                @csrf
                
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400 mb-1.5">{{ __('Tên gợi nhớ') }} <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           required 
                           value="{{ old('name') }}"
                           placeholder="Ví dụ: Tài khoản phụ 1, Shopee Affiliate..."
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-800 bg-transparent text-xs text-gray-850 dark:text-slate-100 placeholder-gray-400 focus:ring-1 focus:ring-shopee focus:border-shopee focus:outline-none">
                </div>

                <!-- Chuyển đổi cách nhập Cookie -->
                <div class="flex items-center gap-2 border-b border-gray-100 dark:border-slate-850 pb-2">
                    <button type="button" 
                            @click="uploadType = 'file'" 
                            :class="uploadType === 'file' ? 'text-shopee border-b-2 border-shopee font-bold' : 'text-gray-450 dark:text-gray-500 font-medium'"
                            class="text-xs pb-1 px-1 transition-all">
                        {{ __('Tải tệp tin JSON') }}
                    </button>
                    <button type="button" 
                            @click="uploadType = 'text'" 
                            :class="uploadType === 'text' ? 'text-shopee border-b-2 border-shopee font-bold' : 'text-gray-450 dark:text-gray-500 font-medium'"
                            class="text-xs pb-1 px-1 transition-all">
                        {{ __('Dán JSON Cookie') }}
                    </button>
                </div>

                <!-- Chế độ 1: Tải lên File JSON -->
                <div x-show="uploadType === 'file'" class="space-y-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-450 mb-1">{{ __('Chọn tệp tin JSON Cookie') }} <span class="text-red-500">*</span></label>
                    <input type="file" 
                           name="cookie_file" 
                           accept=".json,.txt"
                           :required="uploadType === 'file'"
                           class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-shopee/10 file:text-shopee hover:file:bg-shopee/20 file:cursor-pointer">
                    <p class="text-[9px] text-gray-400 dark:text-gray-500">{{ __('Hỗ trợ các tệp tin xuất ra từ J2TEAM Cookies (.json hoặc .txt)') }}</p>
                </div>

                <!-- Chế độ 2: Dán chuỗi JSON trực tiếp -->
                <div x-show="uploadType === 'text'" class="space-y-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-450 mb-1">{{ __('JSON Cookie (J2TEAM Cookies)') }} <span class="text-red-500">*</span></label>
                    <textarea name="cookie_json" 
                              :required="uploadType === 'text'"
                              rows="6"
                              placeholder='{"url":"https://affiliate.shopee.vn","cookies":[...]}'
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-800 bg-transparent font-mono text-[10px] text-gray-850 dark:text-slate-100 placeholder-gray-400 focus:ring-1 focus:ring-shopee focus:border-shopee focus:outline-none scrollbar-none">{{ old('cookie_json') }}</textarea>
                </div>

                <button type="submit" 
                        class="w-full py-2.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-xl text-xs transition-all shadow-lg shadow-shopee/15 flex items-center justify-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    {{ __('Kết nối & Kiểm tra') }}
                </button>
            </form>
        </div>
    </div>
    </div>

    <!-- Modal Báo cáo đối soát chi tiết từ Shopee API - Được mở rộng kích thước rộng hơn (max-w-[90vw]) để dễ dàng theo dõi thông tin đối soát nhiều cột -->
    <div x-show="showSyncModal" 
         @keydown.escape.window="closeSyncModal()"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop (click nền để đóng modal) -->
        <div @click="closeSyncModal()" class="fixed inset-0 bg-gray-950/40 dark:bg-slate-950/70 backdrop-blur-sm transition-opacity"></div>

        <div @click="closeSyncModal()" class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div @click.stop class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 p-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-[90vw] xl:max-w-[85vw]"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <!-- Close Button -->
                <div class="absolute right-4 top-4">
                    <button @click="closeSyncModal()" 
                            class="rounded-xl p-1 text-gray-450 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-650 dark:hover:text-gray-200 transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Title -->
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
                        {{ __('Báo cáo đối soát Shopee API') }}
                    </h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        {{ __('Tài khoản:') }} <span class="font-bold text-shopee" x-text="syncAccountName"></span>
                    </p>
                </div>

                <!-- State 1: Loading -->
                <div x-show="syncLoading" class="py-12 flex flex-col items-center justify-center space-y-4">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-full border-4 border-shopee/20 border-t-shopee animate-spin"></div>
                    </div>
                    <div class="text-center space-y-1">
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 animate-pulse">{{ __('Đang kết nối đến Shopee API...') }}</p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">{{ __('Hệ thống đang quét các đơn hàng phát sinh trong 30 ngày qua và thực hiện đối soát tự động.') }}</p>
                    </div>
                </div>

                <!-- State 2: Error -->
                <div x-show="syncError" class="py-6 space-y-4" style="display: none;">
                    <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/40 dark:border-red-900/20 rounded-2xl flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center shrink-0">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        </div>
                        <div class="space-y-1 text-xs">
                            <h4 class="font-bold text-red-800 dark:text-red-400">{{ __('Đồng bộ thất bại!') }}</h4>
                            <p class="text-gray-600 dark:text-gray-450 font-medium" x-text="syncError"></p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button @click="closeSyncModal()" 
                                class="px-4 py-2 bg-gray-150 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-750 dark:text-gray-300 font-bold rounded-xl text-xs transition-all">
                            {{ __('Đóng và tải lại trang') }}
                        </button>
                    </div>
                </div>

                <!-- State 3: Success Result -->
                <div x-show="syncData" class="space-y-6" style="display: none;">
                    <!-- Summary Stats Card -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/70 dark:border-slate-800/30">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Quét được') }}</span>
                            <span class="block text-base font-bold text-gray-800 dark:text-gray-200 mt-0.5" x-text="syncData?.summary?.total_scanned">0</span>
                        </div>
                        <div class="p-3 bg-green-50/30 dark:bg-green-950/5 rounded-2xl border border-green-100/40 dark:border-green-900/10">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-green-600/75 dark:text-green-500/75">{{ __('Đã duyệt') }}</span>
                            <span class="block text-base font-bold text-green-600 dark:text-green-400 mt-0.5" x-text="syncData?.summary?.approved">0</span>
                        </div>
                        <div class="p-3 bg-red-50/30 dark:bg-red-950/5 rounded-2xl border border-red-100/40 dark:border-red-900/10">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-red-600/75 dark:text-red-500/75">{{ __('Từ chối / Hủy') }}</span>
                            <span class="block text-base font-bold text-red-650 dark:text-red-400 mt-0.5" x-text="syncData?.summary?.rejected">0</span>
                        </div>
                        <div class="p-3 bg-blue-50/30 dark:bg-blue-950/5 rounded-2xl border border-blue-100/40 dark:border-blue-900/10">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-blue-600/75 dark:text-blue-500/75">{{ __('Đã xử lý trước') }}</span>
                            <span class="block text-base font-bold text-blue-600 dark:text-blue-400 mt-0.5" x-text="syncData?.summary?.already_processed">0</span>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/70 dark:border-slate-800/30">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-gray-500/75 dark:text-gray-400/75">{{ __('Bỏ qua') }}</span>
                            <span class="block text-base font-bold text-gray-600 dark:text-gray-400 mt-0.5" x-text="syncData?.summary?.ignored">0</span>
                        </div>
                    </div>

                    <!-- Details Table -->
                    <div class="space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <h4 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                                {{ __('Danh sách đơn hàng quét được') }}
                            </h4>
                            
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                                <!-- Ô tìm kiếm nhanh bằng Javascript/AlpineJS để lọc nhanh kết quả -->
                                <div class="relative w-full sm:w-64">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 dark:text-gray-500">
                                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <input type="text"
                                           x-model="searchQuery"
                                           placeholder="{{ __('Tìm sản phẩm, utm_content, mã đơn...') }}"
                                           class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-gray-200 dark:border-slate-800 bg-transparent text-xs text-gray-850 dark:text-slate-100 placeholder-gray-400 focus:ring-1 focus:ring-shopee focus:border-shopee focus:outline-none">
                                </div>

                                <!-- Filter Tabs in Modal -->
                                <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 p-0.5 rounded-lg shrink-0 overflow-x-auto">
                                    <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white dark:bg-slate-700 text-gray-800 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Tất cả') }}</button>
                                    <button @click="activeTab = 'approved'" :class="activeTab === 'approved' ? 'bg-green-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Duyệt') }}</button>
                                    <button @click="activeTab = 'rejected'" :class="activeTab === 'rejected' ? 'bg-red-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Hủy') }}</button>
                                    <button @click="activeTab = 'already_processed'" :class="activeTab === 'already_processed' ? 'bg-blue-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Đã xử lý') }}</button>
                                    <button @click="activeTab = 'ignored'" :class="activeTab === 'ignored' ? 'bg-gray-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Bỏ qua') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-slate-800 max-h-[550px] overflow-y-auto">
                            <table class="w-full text-left border-collapse whitespace-nowrap text-nowrap">
                                <thead class="sticky top-0 bg-white dark:bg-slate-900 z-10">
                                    <tr class="bg-gray-50/50 dark:bg-slate-800/30 border-b border-gray-100 dark:border-slate-800 text-[9px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                        <th class="p-2.5">{{ __('Thời gian / Mã đơn') }}</th>
                                        <th class="p-2.5">{{ __('utm_content') }}</th>
                                        <th class="p-2.5">{{ __('Tên sản phẩm') }}</th>
                                        <th class="p-2.5">{{ __('Giá trị / Hoa hồng') }}</th>
                                        <th class="p-2.5">{{ __('Hoàn tiền khách') }}</th>
                                        <th class="p-2.5 text-center">{{ __('Trạng thái Shopee') }}</th>
                                        <th class="p-2.5">{{ __('Hành động đối soát') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-[11px]">
                                    <template x-for="item in syncData?.details" :key="item.order_sn">
                                        <!-- Sử dụng x-show kết hợp lọc theo tab trạng thái và từ khóa searchQuery trên tên sản phẩm, utm_content hoặc mã đơn -->
                                        <tr x-show="(activeTab === 'all' || activeTab === item.status_system) && (!searchQuery || (item.product_name || '').toLowerCase().includes(searchQuery.toLowerCase()) || (item.utm_content || '').toLowerCase().includes(searchQuery.toLowerCase()) || (item.order_sn || '').toLowerCase().includes(searchQuery.toLowerCase()))" 
                                            class="hover:bg-gray-50/30 dark:hover:bg-slate-800/10 transition-colors">
                                            <td class="p-2.5 whitespace-nowrap">
                                                <span class="block text-[9px] text-gray-400" x-text="item.purchase_time"></span>
                                                <span class="block font-mono font-bold text-gray-800 dark:text-gray-200" x-text="item.order_sn"></span>
                                            </td>
                                            <td class="p-2.5 font-mono text-[10px] text-gray-600 dark:text-gray-400" x-text="item.utm_content || '-'"></td>
                                            <td class="p-2.5 max-w-[200px] truncate text-gray-650 dark:text-gray-400" :title="item.product_name" x-text="item.product_name"></td>
                                            <td class="p-2.5 whitespace-nowrap">
                                                <span class="block text-[10px] text-gray-500 dark:text-gray-450">{{ __('Giá trị:') }} <strong class="text-gray-700 dark:text-gray-300" x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.actual_amount)"></strong></span>
                                                <span class="block text-[10px] text-green-600 dark:text-green-500">{{ __('Hoa hồng:') }} <strong x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.commission)"></strong></span>
                                            </td>
                                            <td class="p-2.5 whitespace-nowrap">
                                                <span class="block text-[10px] text-shopee dark:text-orange-500 font-bold" x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.cashback_amount || 0)"></span>
                                            </td>
                                            <td class="p-2.5 text-center whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold" 
                                                      :class="{
                                                          'bg-green-50 dark:bg-green-950/20 text-green-600 border border-green-100 dark:border-green-900/20': 
                                                              (item.status_shopee || '').toLowerCase() === 'completed' || 
                                                              (item.status_shopee || '').toLowerCase() === 'hoàn thành' || 
                                                              (item.status_shopee || '').toLowerCase() === 'đơn hợp lệ' || 
                                                              (item.status_shopee || '').toLowerCase().includes('thanh toán') || 
                                                              (item.status_shopee || '').toLowerCase().includes('đối soát'),
                                                          'bg-red-50 dark:bg-red-950/20 text-red-600 border border-red-100 dark:border-red-900/20': 
                                                              (item.status_shopee || '').toLowerCase().includes('cancel') || 
                                                              (item.status_shopee || '').toLowerCase().includes('hủy'),
                                                          'bg-gray-100 dark:bg-slate-800 text-gray-500 border border-gray-200 dark:border-slate-700/20': 
                                                              !(item.status_shopee || '').toLowerCase().includes('completed') && 
                                                              !(item.status_shopee || '').toLowerCase().includes('hoàn thành') && 
                                                              !(item.status_shopee || '').toLowerCase().includes('đơn hợp lệ') && 
                                                              !(item.status_shopee || '').toLowerCase().includes('thanh toán') && 
                                                              !(item.status_shopee || '').toLowerCase().includes('đối soát') && 
                                                              !(item.status_shopee || '').toLowerCase().includes('cancel') && 
                                                              !(item.status_shopee || '').toLowerCase().includes('hủy')
                                                      }" 
                                                      x-text="item.status_shopee"></span>
                                            </td>
                                            <td class="p-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold shrink-0 text-white"
                                                          :class="{
                                                              'bg-green-500': item.status_system === 'approved',
                                                              'bg-red-500': item.status_system === 'rejected',
                                                              'bg-blue-500': item.status_system === 'already_processed',
                                                              'bg-gray-400': item.status_system === 'ignored',
                                                              'bg-yellow-500': item.status_system === 'pending'
                                                          }"
                                                          x-text="item.status_system === 'approved' ? 'Đã duyệt' : (item.status_system === 'rejected' ? 'Từ chối' : (item.status_system === 'already_processed' ? 'Đã xử lý' : (item.status_system === 'ignored' ? 'Bỏ qua' : 'Chờ tiếp')))"></span>
                                                    <span class="text-[9px] text-gray-500 dark:text-gray-405 font-medium line-clamp-1" x-text="item.message_system"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="syncData?.details && syncData.details.length > 0 && !hasFilteredItems()">
                                        <tr>
                                            <td colspan="7" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                                <i data-lucide="search" class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-slate-700"></i>
                                                {{ __('Không tìm thấy đơn hàng nào khớp với từ khóa tìm kiếm.') }}
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="!syncData?.details || syncData?.details.length === 0">
                                        <tr>
                                            <td colspan="7" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-slate-750"></i>
                                                {{ __('Không quét được đơn hàng nào trong 30 ngày qua từ Shopee API.') }}
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer buttons -->
                    <div class="flex items-center justify-between border-t border-gray-150 dark:border-slate-800/80 pt-4 gap-4">
                        <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium leading-normal">
                            * {{ __('Dữ liệu ví khả dụng và doanh thu hệ thống đã được đồng bộ hóa tương ứng với các đơn đã duyệt / hủy.') }}
                        </span>
                        <button @click="closeSyncModal()" 
                                class="px-5 py-2.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/15 flex items-center gap-2 shrink-0">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            {{ __('Hoàn tất & Tải lại trang') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endif
</div>
@endsection

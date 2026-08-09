    <!-- Bảng Đơn Hàng -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <!-- Bảng hiển thị dạng không xuống dòng tự động (whitespace-nowrap) để giao diện cuộn ngang mượt mà hơn -->
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" x-model="allSelected" @change="toggleAll()" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee">
                        </th>
                        <th class="p-4">{{ __('Đơn hàng') }}</th>
                        <th class="p-4">{{ __('Thành viên') }}</th>
                        <th class="p-4">{{ __('Sản phẩm') }}</th>
                        <th class="p-4 text-right">
                            <button type="button" @click="changeSort('original_price')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold ml-auto group/btn">
                                <span>{{ __('Giá gốc / Hoa hồng') }}</span>
                                @if(request('sort_by', 'created_at') === 'original_price')
                                    @if(request('sort_order', 'desc') === 'asc')
                                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @else
                                        <i data-lucide="arrow-down-wide-narrow" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @endif
                                @else
                                    <i data-lucide="chevrons-up-down" class="w-3 h-3 text-gray-300 dark:text-slate-700 group-hover/btn:text-gray-450 shrink-0 transition-colors"></i>
                                @endif
                            </button>
                        </th>
                        <th class="p-4 text-right">
                            <button type="button" @click="changeSort('cashback_amount')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold ml-auto group/btn">
                                <span>{{ __('Hoàn tiền khách') }}</span>
                                @if(request('sort_by', 'created_at') === 'cashback_amount')
                                    @if(request('sort_order', 'desc') === 'asc')
                                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @else
                                        <i data-lucide="arrow-down-wide-narrow" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @endif
                                @else
                                    <i data-lucide="chevrons-up-down" class="w-3 h-3 text-gray-300 dark:text-slate-700 group-hover/btn:text-gray-450 shrink-0 transition-colors"></i>
                                @endif
                            </button>
                        </th>
                        <th class="p-4 text-right">
                            <button type="button" @click="changeSort('profit')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold ml-auto group/btn">
                                <span>{{ __('Lợi nhuận') }}</span>
                                @if(request('sort_by', 'created_at') === 'profit')
                                    @if(request('sort_order', 'desc') === 'asc')
                                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @else
                                        <i data-lucide="arrow-down-wide-narrow" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @endif
                                @else
                                    <i data-lucide="chevrons-up-down" class="w-3 h-3 text-gray-300 dark:text-slate-700 group-hover/btn:text-gray-450 shrink-0 transition-colors"></i>
                                @endif
                            </button>
                        </th>
                        <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                        <th class="p-4 text-center">
                            <button type="button" @click="changeSort('created_at')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold mx-auto group/btn">
                                <span>{{ __('Ngày tạo') }}</span>
                                @if(request('sort_by', 'created_at') === 'created_at')
                                    @if(request('sort_order', 'desc') === 'asc')
                                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @else
                                        <i data-lucide="arrow-down-wide-narrow" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @endif
                                @else
                                    <i data-lucide="chevrons-up-down" class="w-3 h-3 text-gray-300 dark:text-slate-700 group-hover/btn:text-gray-450 shrink-0 transition-colors"></i>
                                @endif
                            </button>
                        </th>
                        <!-- Tiêu đề cột thời gian cập nhật đơn hàng gần nhất -->
                        <th class="p-4 text-center">
                            <button type="button" @click="changeSort('updated_at')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold mx-auto group/btn">
                                <span>{{ __('Cập nhật') }}</span>
                                @if(request('sort_by', 'created_at') === 'updated_at')
                                    @if(request('sort_order', 'desc') === 'asc')
                                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @else
                                        <i data-lucide="arrow-down-wide-narrow" class="w-3.5 h-3.5 text-shopee shrink-0"></i>
                                    @endif
                                @else
                                    <i data-lucide="chevrons-up-down" class="w-3 h-3 text-gray-300 dark:text-slate-700 group-hover/btn:text-gray-450 shrink-0 transition-colors"></i>
                                @endif
                            </button>
                        </th>
                        <!-- Cố định cột Hành động ở bên phải khi cuộn ngang (sticky right) -->
                        <th class="p-4 text-center sticky right-0 bg-gray-50 dark:bg-slate-900 z-10 border-l border-gray-200/50 dark:border-slate-800/50">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($histories as $item)
                        <tr class="group" :class="selectedCashbacks.includes({{ $item->id }}) ? 'bg-orange-50/40 dark:bg-slate-800/30' : ''">
                            <td class="p-4 text-center">
                                <!-- Dùng modifier .number để mảng lưu ID dạng số, giúp so khớp tô màu dòng đang chọn hoạt động chính xác -->
                                <input type="checkbox" value="{{ $item->id }}" x-model.number="selectedCashbacks" @change="allSelected = (selectableCashbackIds.length > 0 && selectedCashbacks.length === selectableCashbackIds.length)" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee cursor-pointer">
                            </td>
                            <td class="p-4">
                                <!-- Hiển thị mã giao dịch hệ thống (trans_id) ở dòng trên, và mã đơn hàng Shopee (order_id) ở dòng dưới theo yêu cầu sếp Thành -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <div class="font-bold text-gray-900">{{ $item->trans_id ?: '-' }}</div>
                                    @if(($item->platform ?? 'shopee') === 'shopee')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-[#ff5722] text-white border border-[#ff5722] shadow-sm select-none">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 text-white"></i>
                                            {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                        </span>
                                    @elseif(($item->platform ?? 'shopee') === 'tiktok')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-black text-white border border-gray-950 shadow-sm select-none">
                                            <i data-lucide="shopping-cart" class="w-2.5 h-2.5 text-white"></i>
                                            {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                        </span>
                                    @elseif(($item->platform ?? 'shopee') === 'lazada')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-[#0f146d] text-white border border-[#0f146d] shadow-sm select-none">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 text-white"></i>
                                            {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-gray-700 text-white shadow-sm select-none">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 text-white"></i>
                                            {{ strtoupper($item->platform) }}
                                        </span>
                                    @endif
                                </div>
                                @if($item->order_id)
                                    <div class="text-[9px] text-gray-400 font-mono mt-0.5" title="{{ __('Mã đơn Shopee') }}">{{ $item->order_id }}</div>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-between gap-1.5 max-w-[150px]">
                                    <div class="truncate">
                                        <p class="font-bold text-gray-800 truncate" title="{{ $item->user?->name ?? __('Không xác định') }}">{{ $item->user?->name ?? __('Không xác định') }}</p>
                                        <p class="text-[9px] text-gray-400 font-mono truncate" title="{{ $item->user?->email ?? __('Không xác định') }}">{{ $item->user?->email ?? __('Không xác định') }}</p>
                                    </div>
                                    <a href="{{ route('admin.users.edit', $item->user_id) }}" class="text-blue-500 hover:text-blue-700 p-1 hover:bg-blue-50 rounded-lg transition-all shrink-0" title="{{ __('Chỉnh sửa thành viên') }}">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3 max-w-xs">
                                    <img src="{{ $item->product_image }}" alt="" class="w-10 h-10 object-cover rounded-lg bg-gray-50 shrink-0 border border-gray-100">
                                    <div class="truncate">
                                        <p class="font-bold text-gray-900 truncate" title="{{ $item->product_name }}">{{ $item->product_name }}</p>
                                        <a href="{{ $item->affiliate_url }}" target="_blank" class="text-[9px] text-shopee hover:underline flex items-center gap-0.5 mt-0.5">{{ __('Link mua hàng') }} <i data-lucide="external-link" class="w-2.5 h-2.5"></i></a>
                                        @if($item->shop_name)
                                            <p class="text-[9px] text-gray-400 mt-1 flex items-center gap-0.5" title="{{ $item->shop_name }}">
                                                <i data-lucide="store" class="w-2.5 h-2.5 text-orange-500"></i> {{ __('Shop:') }} <strong class="text-gray-600">{{ $item->shop_name }}</strong>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- Hiển thị gộp Giá gốc (dòng trên) và Hoa hồng nhận từ sàn (dòng dưới) --}}
                            <td class="p-4 text-right">
                                <span class="font-semibold text-gray-700 dark:text-slate-350">{{ number_format($item->original_price) }}đ</span>
                                <span class="block text-[10px] text-green-600 font-bold mt-0.5" title="{{ __('Hoa hồng từ sàn') }}">+{{ number_format($item->commission_amount) }}đ</span>
                            </td>
                            {{-- Hoàn tiền cho khách hàng mua (F0) --}}
                            <td class="p-4 text-right">
                                <p class="font-bold text-shopee">{{ number_format($item->cashback_amount) }}đ</p>
                                <span class="text-[9px] text-gray-400 block font-medium">Tỷ lệ: {{ $item->cashback_rate }}%</span>
                            </td>
                            {{-- Tính toán lợi nhuận thực tế (Hoa hồng sàn - Hoàn khách - Hoa hồng MLM F1 & F2) --}}
                            @php
                                $mlmCommission = $item->commissions->sum('amount');
                                $profit = $item->commission_amount - $item->cashback_amount - $mlmCommission;
                            @endphp
                            <td class="p-4 text-right">
                                <p class="font-bold {{ $profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($profit) }}đ</p>
                                @if($mlmCommission > 0)
                                    <span class="text-[9px] text-gray-400 block font-medium">MLM: -{{ number_format($mlmCommission) }}đ</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    @if($item->status === 'pending')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100">{{ __('Chờ duyệt') }}</span>
                                    @elseif($item->status === 'approved')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100">{{ __('Thành công') }}</span>
                                    @else
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100">{{ __('Bị từ chối') }}</span>
                                            @if($item->rejected_reason)
                                                <span class="text-[8px] text-red-400 max-w-[120px] truncate" title="{{ $item->rejected_reason }}">Lý do: {{ $item->rejected_reason }}</span>
                                            @endif
                                            @if($item->fraud_reason)
                                                <span class="text-[8px] text-orange-500 max-w-[120px] truncate font-semibold" title="{{ $item->fraud_reason }}">Shopee: {{ $item->fraud_reason }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if($item->status !== 'pending')
                                        @php
                                            $approveSource = $item->click_metadata['approve_source'] ?? 'manual';
                                        @endphp
                                        @if($approveSource === 'sync')
                                            @php
                                                // Xác định tên API và tiêu đề tooltip tương ứng theo từng platform (Shopee, TikTok Shop, Lazada...)
                                                // nhằm hiển thị chính xác nguồn đối soát tự động thay vì hiển thị nhầm lẫn Shopee API cho các đơn hàng từ nền tảng khác.
                                                $platformName = match($item->platform ?? 'shopee') {
                                                    'shopee' => 'Shopee API',
                                                    'tiktok' => 'TikTok API',
                                                    'lazada' => 'Lazada API',
                                                    default => strtoupper($item->platform) . ' API'
                                                };
                                                $platformTitle = match($item->platform ?? 'shopee') {
                                                    'shopee' => 'Đồng bộ tự động qua Shopee API',
                                                    'tiktok' => 'Đồng bộ tự động qua TikTok Shop API',
                                                    'lazada' => 'Đồng bộ tự động qua Lazada API',
                                                    default => 'Đồng bộ tự động qua ' . strtoupper($item->platform) . ' API'
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[8px] font-bold bg-orange-50 text-orange-600 border border-orange-100" title="{{ __($platformTitle) }}">
                                                <i class="w-2 h-2 shrink-0" data-lucide="refresh-cw"></i> {{ __($platformName) }}
                                            </span>
                                        @elseif($approveSource === 'csv')
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[8px] font-bold bg-purple-50 text-purple-600 border border-purple-100" title="{{ __('Đối soát tự động qua tệp tin CSV') }}">
                                                <i class="w-2 h-2 shrink-0" data-lucide="file-spreadsheet"></i> {{ __('Đối soát CSV') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[8px] font-bold bg-gray-50 text-gray-500 border border-gray-150" title="{{ __('Duyệt thủ công bằng tay') }}">
                                                <i class="w-2 h-2 shrink-0" data-lucide="user"></i> {{ __('Thủ công') }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <!-- Hiển thị ngày tạo đơn hàng kèm timeago -->
                            <td class="p-4 text-center text-[10px]">
                                <span class="font-medium block text-gray-700 dark:text-gray-300">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                                <span class="text-[9px] text-gray-400 dark:text-gray-500 block mt-0.5" title="{{ $item->created_at }}">{{ $item->created_at->diffForHumans() }}</span>
                            </td>
                            <!-- Hiển thị thời gian cập nhật đơn hàng sau cùng (khi duyệt, từ chối hoặc đồng bộ) kèm timeago -->
                            <td class="p-4 text-center text-[10px]">
                                @if($item->updated_at)
                                    <span class="font-medium block text-gray-700 dark:text-gray-300">{{ $item->updated_at->format('d/m/Y H:i') }}</span>
                                    <span class="text-[9px] text-gray-400 dark:text-gray-500 block mt-0.5" title="{{ $item->updated_at }}">{{ $item->updated_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <!-- Ô Hành động - Cố định ở bên phải khi cuộn ngang (sticky right) kèm màu nền tương ứng để không lộ nội dung cuộn bên dưới -->
                            <td class="p-4 text-center shrink-0 sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-gray-100 dark:border-slate-800 group-hover:bg-gray-50/80 dark:group-hover:bg-slate-800/80 transition-colors"
                                :class="selectedCashbacks.includes({{ $item->id }}) ? '!bg-[#fdf8f5] dark:!bg-slate-850' : ''">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Nút Xem chi tiết (Rút gọn hành động tại bảng, chuyển các thao tác duyệt/từ chối vào bên trong modal chi tiết để bảng hiển thị thông thoáng) -->
                                    <button @click="openDetailModal({{ json_encode($item) }})" class="px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-slate-800 dark:text-blue-400 dark:hover:bg-slate-700/80 font-semibold text-[10px] transition-all flex items-center gap-1">
                                        <i data-lucide="eye" class="w-3 h-3"></i> {{ __('Xem') }}
                                    </button>

                                    <!-- Nút xóa đơn hàng hoàn tiền đơn lẻ (Mở modal xác nhận an toàn kèm checkbox theo yêu cầu của sếp Thành) -->
                                    <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 dark:bg-slate-850 dark:text-red-400 dark:hover:bg-slate-800/80 font-semibold text-[10px] transition-all flex items-center gap-1" title="{{ __('Xóa đơn hoàn tiền') }}">
                                        <i data-lucide="trash-2" class="w-3 h-3"></i> {{ __('Xóa') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <!-- Tăng colspan từ 10 lên 11 để vừa với số cột mới sau khi thêm cột Cập nhật -->
                            <td colspan="11" class="p-8 text-center text-gray-400">{{ __('Không tìm thấy đơn hàng nào cần xử lý.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- Thêm tfoot hiển thị Thống kê tổng hợp theo yêu cầu sếp Thành -->
                <tfoot class="text-xs border-t-2 border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/30">
                    @php
                        // Tính toán thống kê trực tiếp từ dữ liệu đang hiển thị trên table (trang hiện tại)
                        $currentPageGMV = $histories->sum('original_price');
                        $currentPageCashback = $histories->sum('cashback_amount');
                        $currentPageProfit = 0;
                        foreach($histories as $item) {
                            $mlmCommission = $item->commissions->sum('amount');
                            $currentPageProfit += ($item->commission_amount - $item->cashback_amount - $mlmCommission);
                        }
                    @endphp
                    <tr class="font-bold text-gray-900 dark:text-slate-100 bg-gray-50/80 dark:bg-slate-900/50">
                        <td colspan="4" class="p-4 text-left border-t border-gray-200 dark:border-slate-800">
                            <span class="inline-flex items-center gap-1.5 text-gray-900 dark:text-slate-100">
                                <i data-lucide="calculator" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Tổng: :count đơn', ['count' => count($histories)]) }}
                            </span>
                        </td>
                        <td class="p-4 text-right border-t border-gray-200 dark:border-slate-800 text-gray-950 dark:text-white">
                            {{ number_format($currentPageGMV) }}đ
                        </td>
                        <td class="p-4 text-right border-t border-gray-200 dark:border-slate-800 text-shopee">
                            {{ number_format($currentPageCashback) }}đ
                        </td>
                        <td class="p-4 text-right border-t border-gray-200 dark:border-slate-800 {{ $currentPageProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($currentPageProfit) }}đ
                        </td>
                        <td colspan="3" class="border-t border-gray-200 dark:border-slate-800"></td>
                        <td class="p-4 sticky right-0 bg-gray-50 dark:bg-slate-900 z-10 border-t border-l border-gray-200/50 dark:border-slate-800/50"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Phân trang -->
        @if($histories->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $histories->links() }}
            </div>
        @endif
    </div>

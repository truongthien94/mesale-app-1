<!-- Bảng hiển thị danh sách Yêu Cầu Rút Tiền -->
<div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                    <!-- Ô tích chọn tất cả các yêu cầu rút tiền đang hiển thị trên trang -->
                    <th class="p-4 w-10 text-center">
                        <input type="checkbox" x-model="allSelected" @change="toggleAll()" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee cursor-pointer" title="{{ __('Chọn tất cả') }}">
                    </th>
                    <th class="p-4">{{ __('Mã đơn') }}</th>
                    <th class="p-4">{{ __('Thành viên') }}</th>
                    <th class="p-4 text-right">
                        <button type="button" @click="changeSort('amount')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold ml-auto group/btn">
                            <span>{{ __('Số tiền rút') }}</span>
                            @if(request('sort_by', 'created_at') === 'amount')
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
                    <th class="p-4">{{ __('Hình thức') }}</th>
                    <th class="p-4">{{ __('Thông tin chuyển khoản') }}</th>
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
                    <th class="p-4 text-center">{{ __('Hành động') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
                @forelse($withdrawals as $item)
                    <tr :class="selectedWithdrawals.includes({{ $item->id }}) ? 'bg-orange-50/40 dark:bg-slate-800/30' : ''">
                        <!-- Ô tích chọn từng yêu cầu rút tiền phục vụ duyệt nhanh / xóa nhanh hàng loạt -->
                        <td class="p-4 text-center">
                            <input type="checkbox" value="{{ $item->id }}" x-model.number="selectedWithdrawals" @change="syncAllSelected()" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee cursor-pointer">
                        </td>
                        <!-- Hiển thị mã đơn rút tiền động, nếu dòng cũ không có mã thì fallback về HTS W{id} -->
                        <td class="p-4 font-mono font-bold text-gray-700 select-all">
                            {{ $item->code ?? ('HTS W' . $item->id) }}
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <p class="font-bold text-gray-800">{{ $item->user?->name ?? __('Không xác định') }}</p>
                                <!-- Nút chỉnh sửa thành viên nhanh kế bên tên -->
                                <a href="{{ route('admin.users.edit', $item->user_id) }}" class="text-shopee hover:text-shopee-dark transition-all" title="{{ __('Chỉnh sửa thành viên') }}">
                                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                                </a>
                            </div>
                            <p class="text-[9px] text-gray-400 font-mono">{{ $item->user?->email ?? __('Không xác định') }}</p>
                        </td>
                        <td class="p-4 text-right font-extrabold text-slate-900 text-sm">
                            <div>{{ number_format($item->amount) }}đ</div>
                            <div class="text-[10px] text-gray-400 font-medium mt-0.5">
                                {{ __('Phí:') }} {{ number_format($item->fee ?? 0) }}đ | {{ __('Thực nhận:') }} <span class="text-shopee font-bold">{{ number_format($item->real_amount ?? $item->amount) }}đ</span>
                            </div>
                        </td>
                        <td class="p-4 font-bold text-gray-700">
                            {{ $item->payment_method === 'momo' ? 'Ví MoMo' : 'Chuyển khoản' }}
                        </td>
                        <td class="p-4">
                            <p class="font-bold text-gray-800">{{ $item->account_name }}</p>
                            <p class="text-[10px] text-gray-400">STK: <strong class="text-gray-600 font-mono">{{ $item->account_number }}</strong> @if($item->bank_name) ({{ $item->bank_name }}) @endif</p>
                        </td>
                        <td class="p-4 text-center">
                            @if($item->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100">{{ __('Chờ duyệt') }}</span>
                            @elseif($item->status === 'approved')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100">{{ __('Đã thanh toán') }}</span>
                            @else
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100">{{ __('Bị từ chối') }}</span>
                                    @if($item->notes)
                                        <span class="text-[8px] text-red-400 max-w-[120px] truncate" title="{{ $item->notes }}">Lý do: {{ $item->notes }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="p-4 text-center text-gray-400 text-[10px]">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4 text-center text-gray-400 text-[10px]">
                            @if($item->updated_at)
                                {{ $item->updated_at->format('d/m/Y H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="p-4 text-center shrink-0">
                            <div class="flex items-center justify-center gap-1.5">
                                <!-- Nút Xem chi tiết lệnh rút và dòng tiền -->
                                <button @click="openDetailsModal({{ $item->id }})" class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 font-semibold text-[10px] transition-all flex items-center gap-1 shadow-sm border border-transparent dark:border-slate-700/50" title="{{ __('Xem chi tiết yêu cầu rút tiền') }}">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    {{ __('Xem') }}
                                </button>

                                <!-- Nút Xem đơn hoàn tiền của user nhằm phục vụ kiểm tra đối soát đơn hàng trước khi duyệt chi -->
                                <a href="{{ route('admin.cashback.index', ['user_search' => $item->user?->email]) }}" class="px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 font-semibold text-[10px] transition-all flex items-center gap-1 shadow-sm border border-transparent dark:border-blue-900/30" title="{{ __('Xem đơn hàng của user') }}">
                                    <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                                    {{ __('Đơn hàng') }}
                                </a>

                                <!-- Nút Chỉnh sửa thông tin thành viên để thay đổi số dư hoặc xem chi tiết tài khoản của họ -->
                                <a href="{{ route('admin.users.edit', $item->user_id) }}" class="px-2.5 py-1.5 rounded-lg bg-yellow-50 dark:bg-yellow-950/40 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-100 dark:hover:bg-yellow-900/40 font-semibold text-[10px] transition-all flex items-center gap-1 shadow-sm border border-transparent dark:border-yellow-900/30" title="{{ __('Chỉnh sửa user') }}">
                                    <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                                    {{ __('Sửa User') }}
                                </a>

                                <!-- Nút Xóa yêu cầu rút tiền (Mở modal xác nhận an toàn kèm checkbox theo yêu cầu sếp Thành) -->
                                <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 font-semibold text-[10px] transition-all flex items-center gap-1 shadow-sm border border-transparent dark:border-red-900/30" title="{{ __('Xóa yêu cầu rút tiền') }}">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    {{ __('Xóa') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center text-gray-400">{{ __('Không tìm thấy yêu cầu rút tiền nào cần xử lý.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Phân trang danh sách -->
    @if($withdrawals->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            {{ $withdrawals->links() }}
        </div>
    @endif
</div>

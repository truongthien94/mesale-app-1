<div class="relative overflow-hidden">
    <!-- Hiệu ứng Spinner khi tải trang bằng AJAX -->
    <div id="table-loading" class="hidden absolute inset-0 z-30 bg-white/70 flex items-center justify-center backdrop-blur-[1px] transition-all duration-200">
        <div class="flex flex-col items-center gap-2">
            <svg class="animate-spin h-8 w-8 text-shopee" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-semibold text-gray-500">{{ __('Đang tải dữ liệu...') }}</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        {{-- Sử dụng class whitespace-nowrap để tránh việc dữ liệu bảng bị rớt dòng, đảm bảo hiển thị thẳng hàng trên các kích thước màn hình --}}
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                    <th class="p-4 w-10 text-center">
                        <input type="checkbox" x-model="allSelected" @change="toggleAll()" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee">
                    </th>
                    <th class="p-4">{{ __('Họ tên / Email') }}</th>
                    <th class="p-4">{{ __('Người giới thiệu') }}</th>
                    <th class="p-4">{{ __('Số điện thoại') }}</th>
                    <th class="p-4 text-right">
                        <button type="button" @click="changeSort('balance')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold ml-auto group/btn">
                            <span>{{ __('Ví số dư') }}</span>
                            @if(request('sort_by', 'created_at') === 'balance')
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
                        <button type="button" @click="changeSort('total_cashback')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-slate-200 font-bold ml-auto group/btn">
                            <span>{{ __('Cashback / Đã rút') }}</span>
                            @if(request('sort_by', 'created_at') === 'total_cashback')
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
                    <th class="p-4 text-center">{{ __('Đơn hàng (Chờ / Hợp lệ)') }}</th>
                    <th class="p-4 text-center">{{ __('Vai trò') }}</th>
                    <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                    <th class="p-4 text-center">{{ __('UTM Source') }}</th>
                    <th class="p-4 text-center">{{ __('IP / Thiết bị') }}</th>
                    <th class="p-4 text-center">{{ __('Online gần nhất') }}</th>
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
                    {{-- Cố định cột hành động bên phải khi cuộn ngang --}}
                    <th class="p-4 text-center sticky right-0 bg-gray-50 dark:bg-slate-800 z-20 shadow-[-4px_0_8px_-4px_rgba(0,0,0,0.1)] dark:shadow-[-4px_0_8px_-4px_rgba(0,0,0,0.3)]">{{ __('Hành động') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
                @forelse($users as $user)
                    {{-- Thêm class group và hover/transition để các ô sticky con thừa hưởng hiệu ứng hover --}}
                    <tr class="group hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all" :class="selectedUsers.includes({{ $user->id }}) ? 'bg-orange-50/5 dark:bg-slate-800/30 selected' : ''">
                        <td class="p-4 text-center">
                            @if($user->id !== auth()->id())
                                <input type="checkbox" value="{{ $user->id }}" x-model="selectedUsers" @change="allSelected = (selectedUsers.length === selectableUserIds.length)" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee">
                            @else
                                <span class="text-gray-300 dark:text-slate-850 font-bold">-</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-between gap-1.5 max-w-[150px]">
                                <div class="truncate">
                                    <p class="font-bold text-gray-900 truncate" title="{{ $user->name }}">{{ $user->name }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        {{-- Tài khoản đăng ký bằng Số điện thoại chưa có email nên hiển thị SĐT làm định danh --}}
                                        <span class="text-[10px] text-gray-400 font-mono truncate" title="{{ $user->email ?: $user->phone }}">{{ $user->email ?: $user->phone }}</span>
                                        @if($user->google_id)
                                            <span class="inline-flex shrink-0" title="{{ __('Đăng ký bằng tài khoản Google') }}">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                                    <path fill="#4285F4" d="M46.5 24c0-1.61-.15-3.16-.42-4.67H24v8.86h12.67C35.15 31.75 30.2 35.8 24 35.8c-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48c12.43 0 22.5-10.07 22.5-22.5z"/>
                                                    <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                                                    <path fill="#34A853" d="M24 38.5c-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48c6.47 0 11.9-2.38 16.14-6.45l-6.85-6.85c-2.42 1.62-5.53 2.8-9.29 2.8z"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="text-blue-500 hover:text-blue-700 p-1 hover:bg-blue-50 rounded-lg transition-all shrink-0" title="{{ __('Chỉnh sửa thành viên') }}">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </td>
                        <td class="p-4">
                            @if($user->referrer)
                                <div class="flex items-center justify-between gap-1.5 max-w-[150px]">
                                    <div class="truncate">
                                        <p class="font-bold text-gray-800 truncate" title="{{ $user->referrer->name }}">{{ $user->referrer->name }}</p>
                                        <p class="text-[9px] text-gray-400 font-mono truncate" title="{{ $user->referrer->email }}">{{ $user->referrer->email }}</p>
                                    </div>
                                    <a href="{{ route('admin.users.edit', $user->referrer->id) }}" class="text-blue-500 hover:text-blue-700 p-1 hover:bg-blue-50 rounded-lg transition-all shrink-0" title="{{ __('Chỉnh sửa người giới thiệu') }}">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-400 font-medium italic text-[10px]">{{ __('Không có') }}</span>
                            @endif
                        </td>
                        <td class="p-4 font-semibold text-gray-600">{{ $user->phone ?? __('Chưa cấu hình') }}</td>
                        <td class="p-4 text-right font-extrabold text-indigo-600">{{ number_format($user->balance) }}đ</td>
                        <td class="p-4 text-right">
                            <p class="font-semibold text-green-600">+{{ number_format($user->total_cashback) }}đ</p>
                            <p class="text-[10px] text-blue-500 font-medium">-{{ number_format($user->total_withdrawn) }}đ</p>
                        </td>
                        <td class="p-4 text-center">
                            {{-- Chỉ hiển thị thông tin số lượng đơn hàng nếu thành viên có ít nhất 1 đơn chờ duyệt hoặc đơn hợp lệ để tránh rối mắt --}}
                            @if(($user->pending_orders_count ?? 0) > 0 || ($user->approved_orders_count ?? 0) > 0)
                                <div class="flex flex-col items-center justify-center gap-0.5">
                                    {{-- Lọc đơn hàng chờ đối soát của thành viên qua tham số user_search --}}
                                    <a href="{{ route('admin.cashback.index', ['user_search' => $user->email, 'status' => 'pending']) }}" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-all font-bold text-[9px]" title="{{ __('Đơn chờ đối soát') }}">
                                        <span>{{ __('Chờ:') }}</span>
                                        <span>{{ $user->pending_orders_count ?? 0 }}</span>
                                    </a>
                                    {{-- Lọc đơn hàng hợp lệ của thành viên qua tham số user_search --}}
                                    <a href="{{ route('admin.cashback.index', ['user_search' => $user->email, 'status' => 'approved']) }}" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-green-50 text-green-700 hover:bg-green-100 transition-all font-bold text-[9px] mt-0.5" title="{{ __('Đơn hợp lệ') }}">
                                        <span>{{ __('Hợp lệ:') }}</span>
                                        <span>{{ $user->approved_orders_count ?? 0 }}</span>
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-300 dark:text-slate-850 font-bold">-</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold {{ $user->role === 'admin' ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-gray-100 text-gray-600' }}">
                                @if($user->role === 'admin')
                                    Admin ({{ $user->roleRelation ? $user->roleRelation->name : __('Super') }})
                                @else
                                    User
                                @endif
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold {{ $user->status === 'active' ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-red-50 text-red-600 border border-red-100' }}">
                                {{ $user->status === 'active' ? __('Hoạt động') : __('Đang khoá') }}
                            </span>
                        </td>
                        <!-- Hiển thị nguồn UTM Source khi thành viên đăng ký -->
                        <td class="p-4 text-center">
                            @if($user->utm_source)
                                <span class="inline-flex px-2 py-0.5 rounded-xl text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 font-mono">
                                    {{ $user->utm_source }}
                                </span>
                            @else
                                <span class="text-gray-400 font-medium text-[10px] italic">-</span>
                            @endif
                        </td>
                        <!-- Hiển thị IP và thiết bị khi đăng ký -->
                        <td class="p-4 text-center">
                            <div class="flex flex-col items-center justify-center gap-0.5">
                                <span class="font-mono text-[10px] text-gray-700 bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded" title="{{ __('IP đăng ký') }}">
                                    {{ $user->ip_address ?? 'N/A' }}
                                </span>
                                <span class="text-[9px] text-gray-400 font-medium max-w-[120px] truncate" title="{{ $user->user_agent }}">
                                    {{ $user->register_device }}
                                </span>
                            </div>
                        </td>
                        {{-- Hiển thị thời gian truy cập gần nhất (Online) của người dùng --}}
                        <td class="p-4 text-center">
                            @if($user->last_seen_at)
                                @if($user->isOnline())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-150/40">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                        {{ __('Đang online') }}
                                    </span>
                                @else
                                    <span class="font-medium text-[10px] text-gray-500 dark:text-slate-400" title="{{ $user->last_seen_at->format('d/m/Y H:i:s') }}">
                                        {{ $user->last_seen_at->diffForHumans() }}
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-slate-650 italic font-medium text-[10px]">{{ __('N/A') }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-center text-gray-400 text-[10px]">{{ $user->created_at?->format('d/m/Y') ?? 'N/A' }}</td>
                        {{-- Cố định cột hành động ở bên phải với hiệu ứng hover và selected đồng bộ với dòng --}}
                        <td class="p-4 text-center sticky right-0 bg-white dark:bg-slate-900 group-hover:bg-gray-50 dark:group-hover:bg-slate-800 group-[.selected]:bg-orange-50 dark:group-[.selected]:bg-slate-800 transition-all z-10 shadow-[-4px_0_8px_-4px_rgba(0,0,0,0.1)] dark:shadow-[-4px_0_8px_-4px_rgba(0,0,0,0.3)]">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($user->id !== auth()->id())
                                    @if(config('app.demo'))
                                        <button type="button" onclick="Swal.fire({
                                            icon: 'error',
                                            title: '{{ __('Thao tác bị chặn') }}',
                                            text: '{{ __('Chức năng đăng nhập nhanh dưới danh nghĩa thành viên bị khóa ở chế độ Demo.') }}',
                                            customClass: {
                                                container: 'admin-modal',
                                                confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                                            },
                                            buttonsStyling: false
                                        })" class="p-1 text-gray-400 hover:bg-gray-100 rounded-lg inline-block" title="{{ __('Đăng nhập nhanh') }}">
                                            <i data-lucide="log-in" class="w-4 h-4 inline"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('admin.users.login_as', $user->id) }}" class="p-1 text-green-600 hover:bg-green-50 rounded-lg inline-block" title="{{ __('Đăng nhập nhanh') }}" target="_blank">
                                            <i data-lucide="log-in" class="w-4 h-4 inline"></i>
                                        </a>
                                    @endif
                                @endif
                                {{-- Nút liên kết xem đơn hàng hoàn tiền của thành viên --}}
                                <a href="{{ route('admin.cashback.index', ['user_search' => $user->email]) }}" class="p-1 text-amber-600 hover:bg-amber-50 rounded-lg inline-block" title="{{ __('Xem đơn hoàn tiền') }}">
                                    <i data-lucide="shopping-bag" class="w-4 h-4 inline"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg inline-block" title="{{ __('Chỉnh sửa') }}">
                                    <i data-lucide="edit" class="w-4 h-4 inline"></i>
                                </a>
                                @if($user->id !== auth()->id() && auth()->user()->hasPermission('delete_users'))
                                <button type="button" @click="openDeleteModal({{ json_encode($user) }})" class="p-1 text-red-600 hover:bg-red-50 rounded-lg inline-block" title="{{ __('Xóa thành viên') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4 inline"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="p-8 text-center text-gray-400">{{ __('Không tìm thấy thành viên nào.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    @if($users->hasPages())
        {{-- Thêm lớp pagination để Javascript ở index.blade.php có thể bắt sự kiện click chuyển trang bằng AJAX --}}
        <div class="p-4 border-t border-gray-100 bg-gray-50/50 pagination">
            {{ $users->links() }}
        </div>
    @endif
</div>

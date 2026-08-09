@extends('layouts.admin')

@section('title', __('Sản Phẩm Đã Lưu Của User') . ' - ' . $siteName)

@php
    $hasFilter = request('product_name') || request('user_search') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    showFilter: @json($hasFilter),
    showClearModal: false
}">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Sản Phẩm Đã Lưu Của User') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Xem danh sách các sản phẩm Shopee được người dùng lưu lại để theo dõi và mua sau') }}</p>
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

            <!-- Nút dọn dẹp tất cả sản phẩm đã lưu -->
            <button type="button" 
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp tất cả') }}</span>
            </button>
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
        <form action="{{ route('admin.logs.saved_products') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <select name="limit" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('Hiển thị 15 dòng') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('Hiển thị 30 dòng') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('Hiển thị 50 dòng') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('Hiển thị 100 dòng') }}</option>
                    <option value="200" {{ request('limit') == 200 ? 'selected' : '' }}>{{ __('Hiển thị 200 dòng') }}</option>
                    <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>{{ __('Hiển thị 500 dòng') }}</option>
                </select>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Thành viên (Tên, email, SĐT)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="product_name" 
                       value="{{ request('product_name') }}"
                       placeholder="{{ __('Tên sản phẩm...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.saved_products') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng danh sách sản phẩm đã lưu -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                        <th class="p-4">ID</th>
                        <th class="p-4">{{ __('Thành viên') }}</th>
                        <th class="p-4 text-center">{{ __('Nền tảng') }}</th>
                        <th class="p-4">{{ __('Sản phẩm') }}</th>
                        <th class="p-4 text-right">{{ __('Giá gốc') }}</th>
                        <th class="p-4 text-right">{{ __('Hoàn tiền') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian lưu') }}</th>
                        <th class="p-4 text-center">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4 font-bold text-gray-400">{{ $product->id }}</td>
                            <td class="p-4">
                                @if($product->user)
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-bold text-gray-950">{{ $product->user->name }}</p>
                                            <p class="text-[10px] text-gray-400 font-mono">{{ $product->user->email }}</p>
                                            @if($product->user->phone)
                                                <p class="text-[9px] text-gray-400">{{ $product->user->phone }}</p>
                                            @endif
                                        </div>
                                        <a href="{{ route('admin.users.edit', $product->user->id) }}" class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg inline-flex items-center justify-center transition-colors shrink-0" title="{{ __('Chỉnh sửa thành viên') }}">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-gray-400 italic">{{ __('Không rõ thành viên') }}</p>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if(($product->platform ?? 'shopee') === 'tiktok')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-black text-white border border-gray-950 dark:border-slate-800 shadow-sm select-none">
                                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5 text-white"></i> {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                    </span>
                                @elseif(($product->platform ?? 'shopee') === 'lazada')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-blue-600 text-white border border-blue-700 shadow-sm select-none">
                                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5 text-white"></i> {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-[#ff5722] text-white border border-[#ff5722] shadow-sm select-none">
                                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5 text-white"></i> {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded-lg border border-gray-100 shrink-0">
                                    @else
                                        <div class="w-10 h-10 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-center text-gray-400 shrink-0">
                                            <i data-lucide="image" class="w-5 h-5"></i>
                                        </div>
                                    @endif
                                    <div class="max-w-[320px]">
                                        <p class="font-bold text-gray-900 truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            @if($product->product_url)
                                                <a href="{{ $product->product_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-0.5 text-[10px] text-blue-600 hover:underline">
                                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                                    {{ __('Link gốc') }}
                                                </a>
                                            @endif
                                            @if($product->affiliate_url)
                                                <span class="text-gray-300">|</span>
                                                <a href="{{ $product->affiliate_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-0.5 text-[10px] text-emerald-600 hover:underline">
                                                    <i data-lucide="link" class="w-3 h-3"></i>
                                                    {{ __('Link Affiliate') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-bold text-gray-700">
                                {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                            </td>
                            <td class="p-4 text-right font-bold text-emerald-600">
                                +{{ \App\Helpers\CurrencyHelper::format($product->cashback_amount) }}
                            </td>
                            <td class="p-4 text-center text-gray-400 text-[10px]" title="{{ $product->created_at }}">
                                {{ $product->created_at->format('d/m/Y H:i:s') }}
                                <span class="block text-[9px] text-gray-300 font-medium">{{ $product->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center">
                                    <form action="{{ route('admin.logs.saved_products.delete', $product->id) }}" method="POST" onsubmit="return confirm('{{ __('Bạn có chắc chắn muốn xoá sản phẩm này khỏi danh sách đã lưu của user?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-xl transition-colors inline-flex items-center justify-center" title="{{ __('Xóa sản phẩm') }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">{{ __('Chưa có sản phẩm nào được lưu.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        @if($products->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- Modal xác nhận dọn dẹp sản phẩm đã lưu -->
    <div x-show="showClearModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showClearModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-gray-100 relative z-10">
                <div class="flex items-center gap-4 text-red-600 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center shrink-0">
                        <i data-lucide="triangle-alert" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Sản Phẩm Đã Lưu') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ danh sách sản phẩm đã lưu của người dùng trong hệ thống?') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.saved_products.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                            {{ __('Xác nhận dọn dẹp') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

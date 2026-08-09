@extends('layouts.admin')

@section('title', __('Quản Lý Đa Tiền Tệ - Admin Panel'))

@section('content')
<div class="space-y-6" x-data="{ 
    isEditMode: false, 
    editCurrency: { id: '', name: '', code: '', symbol: '', exchange_rate: '1.0000', symbol_position: 'after', is_active: true, is_default: false },
    actionUrl: '{{ route('admin.currencies.store') }}',
    setEditMode(curr) {
        this.isEditMode = true;
        this.editCurrency = { ...curr };
        this.actionUrl = '/' + window.adminPrefix + '/currencies/' + curr.id;
    },
    setCreateMode() {
        this.isEditMode = false;
        this.editCurrency = { id: '', name: '', code: '', symbol: '', exchange_rate: '1.0000', symbol_position: 'after', is_active: true, is_default: false };
        this.actionUrl = '{{ route('admin.currencies.store') }}';
    }
}">
    <!-- Tiêu đề -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Quản Lý Tiền Tệ') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Cấu hình các tiền tệ hiển thị và tỷ giá quy đổi trên hệ thống') }}</p>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Danh sách tiền tệ (Cột trái) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm">{{ __('Các tiền tệ trong hệ thống') }}</h3>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-shopee/10 text-shopee">
                    {{ $currencies->count() }} {{ __('Tiền tệ') }}
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 text-[10px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-2">{{ __('Tiền tệ') }}</th>
                            <th class="py-3 px-2">{{ __('Mã Code') }}</th>
                            <th class="py-3 px-2 text-center">{{ __('Ký hiệu') }}</th>
                            <th class="py-3 px-2 text-right">{{ __('Tỷ giá (VND)') }}</th>
                            <th class="py-3 px-2 text-center">{{ __('Vị trí') }}</th>
                            <th class="py-3 px-2 text-center">{{ __('Trạng thái') }}</th>
                            <th class="py-3 px-2 text-right">{{ __('Hành động') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($currencies as $curr)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <!-- Tên tiền tệ -->
                                <td class="py-3.5 px-2 font-semibold text-gray-900">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $curr->name }}</span>
                                        @if($curr->is_default)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ __('Mặc định') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <!-- Mã Code -->
                                <td class="py-3.5 px-2 font-mono text-gray-500 uppercase">{{ $curr->code }}</td>
                                <!-- Ký hiệu -->
                                <td class="py-3.5 px-2 text-center font-bold text-shopee">{{ $curr->symbol }}</td>
                                <!-- Tỷ giá -->
                                <td class="py-3.5 px-2 text-right font-medium text-gray-600">
                                    {{ number_format($curr->exchange_rate, 2) }}
                                </td>
                                <!-- Vị trí -->
                                <td class="py-3.5 px-2 text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] bg-gray-100 text-gray-600">
                                        {{ $curr->symbol_position === 'before' ? __('Trước') : __('Sau') }}
                                    </span>
                                </td>
                                <!-- Trạng thái -->
                                <td class="py-3.5 px-2 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold {{ $curr->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $curr->is_active ? __('Đang bật') : __('Tạm ẩn') }}
                                    </span>
                                </td>
                                <!-- Hành động -->
                                <td class="py-3.5 px-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Nút Sửa -->
                                        <button type="button" 
                                                @click="setEditMode({
                                                    id: '{{ $curr->id }}',
                                                    name: '{{ $curr->name }}',
                                                    code: '{{ $curr->code }}',
                                                    symbol: '{{ $curr->symbol }}',
                                                    exchange_rate: '{{ $curr->exchange_rate }}',
                                                    symbol_position: '{{ $curr->symbol_position }}',
                                                    is_active: {{ $curr->is_active ? 'true' : 'false' }},
                                                    is_default: {{ $curr->is_default ? 'true' : 'false' }}
                                                })"
                                                class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-all" 
                                                title="{{ __('Sửa tiền tệ') }}">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <!-- Nút Xóa -->
                                        @if(!$curr->is_default)
                                            <form action="{{ route('admin.currencies.destroy', $curr->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá tiền tệ này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-all" title="{{ __('Xoá tiền tệ') }}">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button class="p-1.5 bg-gray-50 text-gray-300 rounded-lg cursor-not-allowed" disabled title="{{ __('Không thể xoá tiền tệ mặc định') }}">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">{{ __('Chưa có tiền tệ nào được định nghĩa.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cột Phải: Form Thêm / Sửa Tiền Tệ -->
        <div class="lg:col-span-5 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            
            <!-- Header của Form (Động) -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm" x-text="isEditMode ? '{{ __('Chỉnh sửa tiền tệ') }}' : '{{ __('Thêm tiền tệ mới') }}'"></h3>
                <button type="button" 
                        x-show="isEditMode" 
                        @click="setCreateMode()" 
                        class="text-[10px] text-gray-400 hover:text-shopee flex items-center gap-1"
                        x-cloak>
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    {{ __('Chuyển về Thêm mới') }}
                </button>
            </div>

            <!-- Form xử lý -->
            <form :action="actionUrl" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Tên Tiền Tệ -->
                <div>
                    <label for="name" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Tên Tiền Tệ') }} <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           required 
                           x-model="editCurrency.name"
                           placeholder="Ví dụ: Việt Nam Đồng, Đô la Mỹ..."
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                </div>

                <!-- Mã Code Tiền Tệ -->
                <div>
                    <label for="code" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Mã Code Tiền Tệ (ISO)') }} <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="code" 
                           id="code" 
                           required 
                           x-model="editCurrency.code"
                           placeholder="Ví dụ: VND, USD, EUR..."
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 font-mono text-gray-800 uppercase">
                </div>

                <!-- Ký Hiệu Tiền Tệ -->
                <div>
                    <label for="symbol" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Ký Hiệu Tiền Tệ') }} <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="symbol" 
                           id="symbol" 
                           required 
                           x-model="editCurrency.symbol"
                           placeholder="Ví dụ: ₫, $, €, £..."
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                </div>

                <!-- Tỷ Giá Quy Đổi -->
                <div>
                    <label for="exchange_rate" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Tỷ Giá Quy Đổi') }} <span class="text-red-500">*</span></label>
                    <input type="number" 
                           step="0.0001" 
                           name="exchange_rate" 
                           id="exchange_rate" 
                           required 
                           x-model="editCurrency.exchange_rate"
                           :disabled="editCurrency.is_default"
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                    <p class="text-[10px] text-gray-400 mt-1 leading-relaxed">{{ __('Giá trị quy đổi so với đồng tiền mặc định. Ví dụ: VND làm gốc (= 1), USD có tỷ giá 25000 (tức là 1 USD = 25,000 VND).') }}</p>
                </div>

                <!-- Vị Trí Hiển Thị Ký Hiệu -->
                <div>
                    <label for="symbol_position" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Vị Trí Ký Hiệu') }} <span class="text-red-500">*</span></label>
                    <select name="symbol_position" 
                            id="symbol_position" 
                            required 
                            x-model="editCurrency.symbol_position"
                            class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                        <option value="after">{{ __('Sau số tiền (Ví dụ: 100,000₫)') }}</option>
                        <option value="before">{{ __('Trước số tiền (Ví dụ: $100)') }}</option>
                    </select>
                </div>

                <!-- Checkbox Trạng Thái Hoạt Động -->
                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1" 
                               x-model="editCurrency.is_active"
                               :disabled="editCurrency.is_default"
                               class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg">
                        <span class="text-xs font-semibold text-gray-700" :class="editCurrency.is_default ? 'text-gray-400' : ''">{{ __('Kích hoạt hoạt động trên hệ thống') }}</span>
                    </label>
                    <span x-show="editCurrency.is_default" class="text-[10px] text-gray-400 block mt-1 leading-normal" x-cloak>{{ __('Không thể vô hiệu hóa tiền tệ mặc định.') }}</span>
                </div>

                <!-- Checkbox Tiền Tệ Mặc Định -->
                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="is_default" 
                               value="1" 
                               x-model="editCurrency.is_default"
                               :disabled="editCurrency.is_default"
                               class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg">
                        <span class="text-xs font-semibold text-gray-700" :class="editCurrency.is_default ? 'text-gray-400' : ''">{{ __('Đặt làm tiền tệ mặc định hệ thống') }}</span>
                    </label>
                    <span x-show="editCurrency.is_default" class="text-[10px] text-gray-400 block mt-1 leading-normal" x-cloak>{{ __('Đây đã là tiền tệ mặc định.') }}</span>
                </div>

                <!-- Nút Submit -->
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md mt-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span x-text="isEditMode ? '{{ __('Lưu thay đổi') }}' : '{{ __('Thêm tiền tệ') }}'"></span>
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

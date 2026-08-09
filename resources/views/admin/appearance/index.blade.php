@extends('layouts.admin')
@section('title', 'Trình Dựng Giao Diện Trang Chủ')

@section('content')
@php
    // Chuẩn hoá dữ liệu block cho JS (settings rỗng ép về object để tránh mảng [] trong JSON)
    $blocksData = $blocks->map(fn($b) => [
        'id' => $b->id,
        'type' => $b->type,
        'name' => $b->name,
        'settings' => (object) ($b->settings ?: []),
        'enabled' => (bool) $b->enabled,
    ])->values();
@endphp

<div class="space-y-5"
     x-data="pageBuilder({
        blocks: {{ Illuminate\Support\Js::from($blocksData) }},
        defs: {{ Illuminate\Support\Js::from($definitions) }},
        previewUrl: '{{ route('admin.appearance.preview') }}',
        draftUrl: '{{ route('admin.appearance.preview_draft') }}',
        publishUrl: '{{ route('admin.appearance.publish') }}',
        aiUrl: '{{ route('admin.appearance.generate_ai') }}',
        aiEnabled: {{ $aiEnabled ? 'true' : 'false' }},
        homeUrl: '{{ url('/') }}',
        csrf: '{{ csrf_token() }}',
        isDemo: {{ config('app.demo') ? 'true' : 'false' }}
     })">

    {{-- ============== THANH TIÊU ĐỀ & HÀNH ĐỘNG ============== --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i data-lucide="layout-panel-left" class="w-5 h-5 text-shopee"></i>
                {{ __('Trình Dựng Giao Diện Trang Chủ') }}
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Kéo-thả sắp xếp, bật/tắt và chỉnh sửa nội dung từng khối. Khung bên phải xem trước trực tiếp.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Chỉ báo còn thay đổi chưa xuất bản, giúp Admin không rời trang khi chưa lưu --}}
            <span x-show="dirty" x-cloak class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/40">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">{{ __('Có thay đổi chưa xuất bản') }}</span>
            </span>
            <a :href="homeUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                <span class="hidden sm:inline">{{ __('Mở trang chủ') }}</span>
            </a>
            <button type="button" @click="showPalette = true" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-gray-900 dark:bg-slate-700 hover:bg-gray-800 dark:hover:bg-slate-600 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('Thêm block') }}
            </button>
            <button type="button" @click="publish()" :disabled="saving"
                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-shopee hover:bg-shopee-dark transition-all shadow-lg shadow-shopee/20 disabled:opacity-60">
                <i data-lucide="rocket" class="w-4 h-4" x-show="!saving"></i>
                <svg x-show="saving" x-cloak class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="saving ? '{{ __('Đang lưu...') }}' : '{{ __('Xuất bản') }}'"></span>
            </button>
        </div>
    </div>

    @if(config('app.demo'))
    <div class="p-3 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 text-xs font-semibold text-red-600 dark:text-red-400 flex items-center gap-2">
        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
        {{ __('Chế độ Demo: không thể lưu thay đổi hoặc xem trước bản nháp.') }}
    </div>
    @endif

    {{-- ============== KHUNG LÀM VIỆC: CANVAS + PREVIEW ============== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- Canvas danh sách block --}}
        <div class="lg:col-span-5 xl:col-span-4 space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="layers" class="w-4 h-4 text-shopee"></i>
                    {{ __('Các khối nội dung') }}
                </h3>
                <span class="text-[10px] text-gray-400" x-text="blocks.length + ' block'"></span>
            </div>

            <div x-ref="canvas" class="space-y-2.5">
                <template x-for="(block, index) in blocks" :key="block._k">
                    <div class="group bg-white dark:bg-slate-900 border rounded-2xl shadow-sm hover:shadow-md transition-all"
                         :class="block.enabled ? 'border-gray-200 dark:border-slate-800' : 'border-dashed border-gray-300 dark:border-slate-700 opacity-70'">
                        <div class="flex items-center gap-2 p-3">
                            {{-- Tay kéo --}}
                            <div class="pb-drag cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0">
                                <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                            </div>
                            {{-- Icon loại block --}}
                            <div class="w-8 h-8 rounded-xl bg-shopee/10 flex items-center justify-center text-shopee shrink-0">
                                <i :data-lucide="def(block.type).icon || 'square'" class="w-4 h-4"></i>
                            </div>
                            {{-- Tên + loại --}}
                            <div class="flex-1 min-w-0 cursor-pointer" @click="edit(index)">
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate" x-text="block.name || def(block.type).label"></span>
                                <span class="text-[10px] text-gray-400 block truncate" x-text="def(block.type).label"></span>
                            </div>
                            {{-- Công tắc bật/tắt --}}
                            <button type="button" @click="toggle(index)" class="shrink-0 relative w-9 h-5 rounded-full transition-all"
                                :class="block.enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-700'" :title="block.enabled ? '{{ __('Đang bật') }}' : '{{ __('Đang tắt') }}'">
                                <span class="absolute top-0.5 w-4 h-4 bg-white rounded-full transition-all" :class="block.enabled ? 'left-4' : 'left-0.5'"></span>
                            </button>
                            {{-- Menu hành động --}}
                            <div class="flex items-center gap-0.5 shrink-0">
                                <button type="button" @click="edit(index)" class="p-1.5 rounded-lg text-gray-400 hover:text-shopee hover:bg-orange-50 dark:hover:bg-slate-800 transition-all" title="{{ __('Chỉnh sửa') }}">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button type="button" x-show="!def(block.type).singleton" @click="duplicate(index)" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-slate-800 transition-all" title="{{ __('Nhân bản') }}">
                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                </button>
                                <button type="button" x-show="def(block.type).addable !== false" @click="askRemove(index)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all" title="{{ __('Xoá') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="showPalette = true" class="w-full flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-shopee/40 text-shopee rounded-2xl text-xs font-bold hover:bg-shopee/5 transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                {{ __('Thêm khối nội dung mới') }}
            </button>
        </div>

        {{-- Khung xem trước --}}
        <div class="lg:col-span-7 xl:col-span-8">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden sticky top-4">
                {{-- Thanh trình duyệt giả --}}
                <div class="flex items-center gap-2 px-4 py-2.5 border-b border-gray-100 dark:border-slate-800 bg-gray-50/60 dark:bg-slate-800/40">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                    </div>
                    <div class="flex-1 flex justify-center">
                        <span class="text-[10px] font-mono text-gray-400 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-md px-3 py-0.5 truncate max-w-xs">{{ __('Xem trước trực tiếp') }}</span>
                    </div>
                    <div class="flex items-center gap-1 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-lg p-0.5">
                        <button type="button" @click="device='desktop'" class="p-1.5 rounded-md transition-all" :class="device==='desktop' ? 'bg-shopee/10 text-shopee' : 'text-gray-400'" title="{{ __('Máy tính') }}">
                            <i data-lucide="monitor" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button" @click="device='mobile'" class="p-1.5 rounded-md transition-all" :class="device==='mobile' ? 'bg-shopee/10 text-shopee' : 'text-gray-400'" title="{{ __('Điện thoại') }}">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button" @click="reloadPreview()" class="p-1.5 rounded-md text-gray-400 hover:text-shopee transition-all" title="{{ __('Tải lại') }}">
                            <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
                {{-- Iframe --}}
                <div class="relative bg-gray-100 dark:bg-slate-950 flex justify-center overflow-hidden" style="height: calc(100vh - 190px); min-height: 480px;">
                    <div class="relative h-full transition-all duration-300 bg-white" :class="device==='mobile' ? 'w-[390px] shadow-2xl my-3 rounded-[28px] overflow-hidden border-4 border-gray-900' : 'w-full'">
                        <div x-show="iframeLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-slate-950/70">
                            <div class="w-8 h-8 border-2 border-shopee border-t-transparent rounded-full animate-spin"></div>
                        </div>
                        <iframe x-ref="preview" @load="iframeLoading=false" class="w-full h-full border-0" title="{{ __('Xem trước trang chủ') }}"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============== PANEL CHỈNH SỬA BLOCK (slide-over) ============== --}}
    <template x-teleport="body">
        <div x-show="editing !== null" x-cloak class="fixed inset-0 z-[70]" style="display:none">
        <div x-show="editing !== null" x-transition.opacity @click="closePanel()" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div x-show="editing !== null"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute top-0 right-0 h-full w-full max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col">
            <template x-if="editingBlock()">
                <div class="flex flex-col h-full">
                    {{-- Header panel --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-800 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-shopee/10 flex items-center justify-center text-shopee shrink-0">
                                <i :data-lucide="def(editingBlock().type).icon || 'square'" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate" x-text="def(editingBlock().type).label"></h3>
                                <p class="text-[10px] text-gray-400">{{ __('Chỉnh sửa nội dung khối') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="closePanel()" class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- Body panel --}}
                    <div class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                        {{-- Tên block (chỉ hiển thị trong quản trị) --}}
                        <div class="space-y-1.5" x-show="!def(editingBlock().type).singleton">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">{{ __('Tên khối (nội bộ)') }}</label>
                            <input type="text" x-model="editingBlock().name" @input="markDirty()"
                                class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white">
                        </div>

                        {{-- Ghi chú block không có field --}}
                        <template x-if="def(editingBlock().type).note">
                            <div class="p-3.5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 text-[11px] text-blue-700 dark:text-blue-300 leading-relaxed flex gap-2">
                                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                                <span x-text="def(editingBlock().type).note"></span>
                            </div>
                        </template>

                        {{-- Các field động --}}
                        <template x-for="field in def(editingBlock().type).fields" :key="field.key">
                            <div x-show="fieldVisible(field)" class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300" x-text="field.label"></label>

                                {{-- text --}}
                                <template x-if="field.type==='text'">
                                    <input type="text" x-model="editingBlock().settings[field.key]" @input="markDirty()"
                                        class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white">
                                </template>

                                {{-- textarea --}}
                                <template x-if="field.type==='textarea'">
                                    <textarea rows="3" x-model="editingBlock().settings[field.key]" @input="markDirty()"
                                        class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white"></textarea>
                                </template>

                                {{-- richtext / HTML --}}
                                <template x-if="field.type==='richtext'">
                                    <div class="space-y-2">
                                        <textarea rows="8" x-model="editingBlock().settings[field.key]" @input="markDirty()"
                                            class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[11px] font-mono focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white"
                                            placeholder="{{ __('Nhập mã HTML hoặc văn bản...') }}"></textarea>
                                        <template x-if="field.ai">
                                            <button type="button" @click="openAi(field.key)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-[11px] font-bold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:opacity-90 transition-all">
                                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>{{ __('Tạo nội dung bằng AI') }}
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                {{-- select (dùng :selected thay x-model để hiển thị đúng khi option render động) --}}
                                <template x-if="field.type==='select'">
                                    <select @change="editingBlock().settings[field.key] = $event.target.value; markDirty()"
                                        class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white">
                                        <template x-for="[val,lbl] in Object.entries(field.options||{})" :key="val">
                                            <option :value="val" x-text="lbl" :selected="editingBlock().settings[field.key] === val"></option>
                                        </template>
                                    </select>
                                </template>

                                {{-- toggle --}}
                                <template x-if="field.type==='toggle'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" :checked="editingBlock().settings[field.key]==='1'"
                                            @change="editingBlock().settings[field.key]=$event.target.checked?'1':'0'; markDirty()"
                                            class="w-5 h-5 text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 cursor-pointer">
                                        <span class="text-xs text-gray-500 dark:text-slate-400">{{ __('Bật') }}</span>
                                    </label>
                                </template>

                                {{-- icon --}}
                                <template x-if="field.type==='icon'">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-xl border border-gray-200 dark:border-slate-700 flex items-center justify-center text-gray-600 dark:text-slate-300 bg-gray-50/50 dark:bg-slate-800 shrink-0">
                                            <i :data-lucide="editingBlock().settings[field.key] || 'image'" class="w-5 h-5"></i>
                                        </div>
                                        <input type="text" x-model="editingBlock().settings[field.key]" @input="markDirty()"
                                            class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white">
                                        <button type="button" @click="pickIcon(field.key)" class="shrink-0 px-3 py-2.5 rounded-xl text-xs font-bold text-shopee bg-orange-50 dark:bg-slate-800 hover:bg-orange-100 transition-all">{{ __('Chọn') }}</button>
                                    </div>
                                </template>

                                {{-- image / media --}}
                                <template x-if="field.type==='image' || field.type==='media'">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :id="mediaId(field.key)" x-model="editingBlock().settings[field.key]" @input="markDirty()"
                                                class="block w-full px-3.5 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white"
                                                placeholder="https://...">
                                            <button type="button" @click="pickMedia(mediaId(field.key))" class="shrink-0 inline-flex items-center gap-1 px-3 py-2.5 rounded-xl text-xs font-bold text-white bg-gray-900 dark:bg-slate-700 hover:opacity-90 transition-all">
                                                <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                        <template x-if="field.type==='image' && editingBlock().settings[field.key]">
                                            <img :src="editingBlock().settings[field.key]" class="h-24 rounded-xl border border-gray-200 dark:border-slate-700 object-cover">
                                        </template>
                                    </div>
                                </template>

                                {{-- repeater --}}
                                <template x-if="field.type==='repeater'">
                                    <div class="space-y-2.5">
                                        <template x-for="(item, idx) in (editingBlock().settings[field.key] || [])" :key="item._k || idx">
                                            <div class="p-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-gray-50/40 dark:bg-slate-800/30 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase" x-text="'{{ __('Mục') }} #' + (idx+1)"></span>
                                                    <button type="button" @click="removeItem(field, idx)" class="p-1 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                                <template x-for="sf in field.subfields" :key="sf.key">
                                                    <div class="space-y-1">
                                                        <label class="block text-[10px] font-semibold text-gray-500 dark:text-slate-400" x-text="sf.label"></label>
                                                        <template x-if="sf.type==='image'">
                                                            <div class="flex items-center gap-2">
                                                                <input type="text" :id="itemMediaId(field.key, idx, sf.key)" x-model="item[sf.key]" @input="markDirty()"
                                                                    class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 dark:text-white" placeholder="https://...">
                                                                <button type="button" @click="pickMedia(itemMediaId(field.key, idx, sf.key))" class="shrink-0 px-2.5 py-2 rounded-lg text-white bg-gray-900 dark:bg-slate-700">
                                                                    <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <template x-if="sf.type==='textarea'">
                                                            <textarea rows="2" x-model="item[sf.key]" @input="markDirty()"
                                                                class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 dark:text-white"></textarea>
                                                        </template>
                                                        <template x-if="sf.type!=='image' && sf.type!=='textarea'">
                                                            <input type="text" x-model="item[sf.key]" @input="markDirty()"
                                                                class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 dark:text-white">
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <button type="button" @click="addItem(field)" class="w-full flex items-center justify-center gap-1.5 px-3 py-2.5 border-2 border-dashed border-shopee/40 text-shopee rounded-xl text-[11px] font-bold hover:bg-shopee/5 transition-all">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>{{ __('Thêm mục') }}
                                        </button>
                                    </div>
                                </template>

                                <p x-show="field.help" x-cloak class="text-[10px] text-gray-400 leading-relaxed" x-text="field.help"></p>
                            </div>
                        </template>
                    </div>

                    {{-- Footer panel --}}
                    <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center gap-2 shrink-0">
                        <span class="text-[10px] text-gray-400">{{ __('Thay đổi tự động cập nhật ở khung xem trước.') }}</span>
                        <button type="button" @click="closePanel()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-shopee hover:bg-shopee-dark transition-all">{{ __('Xong') }}</button>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- ============== PALETTE THÊM BLOCK ============== --}}
    <template x-teleport="body">
        <div x-show="showPalette" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display:none">
        <div x-show="showPalette" x-transition.opacity @click="showPalette=false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div x-show="showPalette"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="layout-grid" class="w-5 h-5 text-shopee"></i>{{ __('Chọn loại khối để thêm') }}
                </h3>
                <button type="button" @click="showPalette=false" class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto">
                <template x-for="type in addable()" :key="type">
                    <button type="button" @click="addBlock(type)"
                        class="flex items-center gap-3 p-3.5 rounded-2xl border border-gray-200 dark:border-slate-800 hover:border-shopee hover:bg-orange-50/40 dark:hover:bg-slate-800/50 transition-all text-left">
                        <div class="w-10 h-10 rounded-xl bg-shopee/10 flex items-center justify-center text-shopee shrink-0">
                            <i :data-lucide="def(type).icon" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate" x-text="def(type).label"></span>
                            <span class="text-[10px] text-gray-400 block" x-text="def(type).singleton ? '{{ __('Chỉ thêm 1 lần') }}' : '{{ __('Có thể thêm nhiều') }}'"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- ============== XÁC NHẬN XOÁ ============== --}}
    <template x-teleport="body">
        <div x-show="removeIndex !== null" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center p-4" style="display:none">
        <div x-show="removeIndex !== null" x-transition.opacity @click="removeIndex=null" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div x-show="removeIndex !== null" x-transition class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 text-center space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center text-red-500 mx-auto"><i data-lucide="trash-2" class="w-7 h-7"></i></div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Xoá khối này?') }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">{{ __('Khối sẽ bị gỡ khỏi trang chủ sau khi bạn xuất bản.') }}</p>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="removeIndex=null" class="flex-1 px-4 py-2.5 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all">{{ __('Huỷ') }}</button>
                <button type="button" @click="remove(removeIndex); removeIndex=null" class="flex-1 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-red-500 hover:bg-red-600 transition-all">{{ __('Xoá') }}</button>
            </div>
        </div>
    </template>

    {{-- ============== MODAL TẠO NỘI DUNG BẰNG AI ============== --}}
    <template x-teleport="body">
        <div x-show="ai.show" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display:none">
        <div x-show="ai.show" x-transition.opacity @click="ai.show=false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div x-show="ai.show" x-transition class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-gray-100 dark:border-slate-800 pb-4">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 flex items-center justify-center text-white shrink-0"><i data-lucide="sparkles" class="w-5 h-5"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Tạo nội dung bằng AI') }}</h3>
                    <p class="text-[11px] text-gray-400">{{ __('Mô tả ý tưởng, AI sẽ sinh mã HTML cho khối.') }}</p>
                </div>
            </div>
            <textarea x-model="ai.prompt" rows="4" placeholder="{{ __('Ví dụ: Banner kêu gọi tải ứng dụng nền cam gradient với 2 nút App Store & Google Play...') }}"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-white"></textarea>
            <p x-show="ai.error" x-cloak class="text-[11px] text-red-500 font-semibold" x-text="ai.error"></p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="ai.show=false" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all">{{ __('Hủy bỏ') }}</button>
                <button type="button" @click="generateAi()" :disabled="ai.loading"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:opacity-90 transition-all disabled:opacity-60">
                    <i data-lucide="sparkles" class="w-4 h-4" x-show="!ai.loading"></i>
                    <svg x-show="ai.loading" x-cloak class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="ai.loading ? '{{ __('Đang tạo...') }}' : '{{ __('Tạo nội dung') }}'"></span>
                </button>
            </div>
        </div>
    </template>

    {{-- Toast --}}
    <template x-teleport="body">
        <div x-show="flash.show" x-cloak x-transition class="fixed bottom-6 right-6 z-[90] px-4 py-3 rounded-2xl shadow-2xl text-xs font-bold text-white flex items-center gap-2"
         :class="flash.type==='error' ? 'bg-red-500' : 'bg-green-500'" style="display:none">
        <i :data-lucide="flash.type==='error' ? 'alert-circle' : 'check-circle'" class="w-4 h-4"></i>
        <span x-text="flash.msg"></span>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function pageBuilder(config) {
    return {
        blocks: config.blocks || [],
        defs: config.defs || {},
        previewUrl: config.previewUrl,
        draftUrl: config.draftUrl,
        publishUrl: config.publishUrl,
        aiUrl: config.aiUrl,
        aiEnabled: config.aiEnabled,
        homeUrl: config.homeUrl,
        csrf: config.csrf,
        isDemo: config.isDemo,
        device: 'desktop',
        editing: null,
        removeIndex: null,
        showPalette: false,
        saving: false,
        iframeLoading: true,
        refreshTimer: null,
        dirty: false,
        ai: { show: false, prompt: '', loading: false, error: '', targetKey: null },
        flash: { show: false, msg: '', type: 'success' },

        _seq: 0,
        newKey() { return 'blk_' + (Date.now().toString(36)) + '_' + (this._seq++); },

        init() {
            // Chuẩn hoá settings + gán khoá ổn định cho x-for (giúp SortableJS & Alpine đồng bộ)
            this.blocks.forEach(b => {
                if (Array.isArray(b.settings) || !b.settings) b.settings = {};
                if (!b._k) b._k = this.newKey();
            });
            this.$nextTick(() => {
                this.initSortable();
                if (window.lucide) window.lucide.createIcons();
            });
            // Nạp bản nháp ban đầu vào khung xem trước
            this.pushDraft();

            // Cảnh báo khi rời trang lúc còn thay đổi chưa xuất bản, tránh mất công chỉnh sửa
            var self = this;
            window.addEventListener('beforeunload', function (e) {
                if (!self.dirty || self.saving) return;
                e.preventDefault();
                e.returnValue = '';
            });
        },

        def(type) { return this.defs[type] || { fields: [], label: type, icon: 'square' }; },
        addable() { return Object.keys(this.defs).filter(t => this.defs[t].addable); },
        editingBlock() { return this.editing !== null ? this.blocks[this.editing] : null; },

        initSortable() {
            var el = this.$refs.canvas;
            if (!el || !window.Sortable) return;
            var self = this;
            window.Sortable.create(el, {
                handle: '.pb-drag',
                animation: 180,
                ghostClass: 'opacity-40',
                onEnd: function (e) {
                    if (e.oldIndex === e.newIndex) return;
                    var moved = self.blocks.splice(e.oldIndex, 1)[0];
                    self.blocks.splice(e.newIndex, 0, moved);
                    self.editing = null;
                    self.markDirty();
                }
            });
        },

        edit(i) {
            this.editing = i;
            this.ensureItemKeys(i);
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },

        // Gán khoá ổn định cho từng dòng của trường lặp lại (repeater).
        // Nếu dùng chỉ số mảng làm khoá, khi xoá một dòng ở giữa Alpine sẽ tái sử dụng nhầm
        // ô nhập của dòng khác khiến nội dung hiển thị sai lệch so với dữ liệu thực.
        ensureItemKeys(i) {
            var block = this.blocks[i];
            if (!block) return;
            var self = this;
            (this.def(block.type).fields || []).forEach(function (field) {
                if (field.type !== 'repeater') return;
                var items = block.settings[field.key];
                if (!Array.isArray(items)) return;
                items.forEach(function (item) {
                    if (item && typeof item === 'object' && !item._k) item._k = self.newKey();
                });
            });
        },
        closePanel() { this.editing = null; },

        toggle(i) { this.blocks[i].enabled = !this.blocks[i].enabled; this.markDirty(); },

        addBlock(type) {
            var d = this.defs[type];
            if (!d) return;
            if (d.singleton && this.blocks.some(b => b.type === type)) {
                this.notify('{{ __('Khối này chỉ được thêm một lần.') }}', 'error');
                this.showPalette = false;
                return;
            }
            var settings = JSON.parse(JSON.stringify(d.defaults || {}));
            this.blocks.push({ id: null, _k: this.newKey(), type: type, name: d.label, settings: settings, enabled: true });
            this.showPalette = false;
            this.markDirty();
            this.$nextTick(() => {
                this.edit(this.blocks.length - 1);
                if (window.lucide) window.lucide.createIcons();
            });
        },

        duplicate(i) {
            var copy = JSON.parse(JSON.stringify(this.blocks[i]));
            copy.id = null;
            copy._k = this.newKey();
            copy.name = (copy.name || '') + ' {{ __('(sao chép)') }}';
            this.blocks.splice(i + 1, 0, copy);
            this.markDirty();
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },

        askRemove(i) {
            var block = this.blocks[i];
            if (!block || this.def(block.type).addable === false) return;
            this.removeIndex = i;
        },
        remove(i) {
            var block = this.blocks[i];
            if (!block || this.def(block.type).addable === false) return;
            this.blocks.splice(i, 1);
            if (this.editing === i) this.editing = null;
            this.markDirty();
        },

        // ---------- Field helpers ----------
        mediaId(key) { return 'pbm_' + this.editing + '_' + key; },
        itemMediaId(fieldKey, idx, subKey) { return 'pbi_' + this.editing + '_' + fieldKey + '_' + idx + '_' + subKey; },
        pickMedia(inputId) { if (typeof openElfinderPopup === 'function') openElfinderPopup(inputId); },
        pickIcon(key) {
            var self = this;
            this.$dispatch('open-icon-picker', {
                callback: function (icon) {
                    self.editingBlock().settings[key] = icon;
                    self.markDirty();
                    self.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                }
            });
        },
        fieldVisible(field) {
            if (!field.condition) return true;
            var b = this.editingBlock();
            for (var k in field.condition) {
                if ((b.settings[k] || '') != field.condition[k]) return false;
            }
            return true;
        },
        addItem(field) {
            var b = this.editingBlock();
            if (!Array.isArray(b.settings[field.key])) b.settings[field.key] = [];
            var row = { _k: this.newKey() };
            (field.subfields || []).forEach(sf => row[sf.key] = '');
            b.settings[field.key].push(row);
            this.markDirty();
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        removeItem(field, idx) {
            this.editingBlock().settings[field.key].splice(idx, 1);
            this.markDirty();
        },

        // ---------- Preview / Save ----------
        markDirty() {
            this.dirty = true;
            if (this.isDemo) return;
            clearTimeout(this.refreshTimer);
            var self = this;
            this.refreshTimer = setTimeout(() => self.pushDraft(), 650);
        },
        pushDraft() {
            if (this.isDemo) { this.reloadPreview(true); return; }
            var self = this;
            fetch(this.draftUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ blocks: JSON.stringify(this.serialize()) })
            }).then(() => self.reloadPreview(false)).catch(() => self.reloadPreview(false));
        },
        reloadPreview(plain) {
            var f = this.$refs.preview;
            // Kiểm tra iframe tồn tại trước khi bật cờ loading, tránh kẹt vòng xoay chờ vĩnh viễn
            if (!f) return;
            this.iframeLoading = true;
            var url = plain ? this.previewUrl : (this.previewUrl + '?__builder_preview=1&_t=' + Date.now());
            f.src = url;
        },
        serialize() {
            return this.blocks.map(b => ({ id: b.id, type: b.type, name: b.name, settings: b.settings, enabled: b.enabled }));
        },
        publish() {
            if (this.saving) return;
            if (this.isDemo) { this.notify('{{ __('Không thể lưu ở chế độ Demo.') }}', 'error'); return; }
            this.saving = true;
            var self = this;
            fetch(this.publishUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ blocks: JSON.stringify(this.serialize()) })
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (ok && d.success) {
                    // Gỡ cờ thay đổi trước khi tải lại để không hiện cảnh báo rời trang
                    self.dirty = false;
                    self.notify(d.message || '{{ __('Đã lưu thành công!') }}', 'success');
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    self.notify(d.message || '{{ __('Có lỗi xảy ra. Vui lòng thử lại.') }}', 'error');
                    self.saving = false;
                }
            })
            .catch(() => { self.notify('{{ __('Lỗi kết nối. Vui lòng thử lại.') }}', 'error'); self.saving = false; });
        },

        // ---------- AI ----------
        openAi(key) {
            if (!this.aiEnabled) { this.notify('{{ __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong tab Kết nối > AI.') }}', 'error'); return; }
            this.ai.targetKey = key;
            this.ai.prompt = '';
            this.ai.error = '';
            this.ai.show = true;
        },
        generateAi() {
            if (this.ai.loading) return;
            if (!this.ai.prompt.trim()) { this.ai.error = '{{ __('Vui lòng mô tả nội dung section bạn muốn tạo.') }}'; return; }
            this.ai.loading = true;
            this.ai.error = '';
            var self = this;
            fetch(this.aiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ prompt: this.ai.prompt })
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok || !d.success) { self.ai.error = d.message || '{{ __('Không thể tạo nội dung. Vui lòng thử lại.') }}'; return; }
                if (d.content && self.editingBlock()) {
                    self.editingBlock().settings[self.ai.targetKey] = d.content;
                    self.markDirty();
                }
                self.ai.show = false;
            })
            .catch(() => { self.ai.error = '{{ __('Lỗi kết nối. Vui lòng thử lại.') }}'; })
            .finally(() => { self.ai.loading = false; });
        },

        notify(msg, type) {
            this.flash = { show: true, msg: msg, type: type || 'success' };
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            var self = this;
            setTimeout(() => { self.flash.show = false; }, 3200);
        }
    };
}

// Callback toàn cục cho elFinder: đổ URL ảnh đã chọn vào input theo id rồi kích hoạt sự kiện để Alpine đồng bộ
window.openElfinderPopup = function (inputId) {
    var width = 900, height = 600;
    var left = (screen.width - width) / 2, top = (screen.height - height) / 2;
    var url = '{{ url("elfinder/popup") }}/' + inputId;
    window.open(url, 'elfinderPicker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=no');
};
window.processSelectedFile = function (fileUrl, inputId) {
    var el = document.getElementById(inputId);
    if (el) {
        el.value = fileUrl;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
};
</script>
@endsection

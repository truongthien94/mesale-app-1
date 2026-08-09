@if(isset($latestPosts) && $latestPosts->count() > 0)
            <section aria-labelledby="home-blog-heading" class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
                <div class="bg-gradient-to-br from-white/90 to-blue-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-8 md:p-12 rounded-[32px] border border-blue-100/50 dark:border-slate-800/60 shadow-sm">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-8 md:mb-12">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200 dark:border-blue-900/30">
                                <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                                {{ __('Cẩm nang mua sắm') }}
                            </span>
                            <h2 id="home-blog-heading" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">
                                {{ __('Tin Tức & Bí Quyết Săn Sale Mới Nhất') }}
                            </h2>
                        </div>
                        <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-shopee hover:text-shopee-dark dark:hover:text-shopee-light transition-colors shrink-0 bg-white dark:bg-slate-900 px-4 py-2 rounded-xl border border-gray-150 dark:border-slate-800 shadow-sm">
                            {{ __('Xem tất cả') }}
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                        @foreach($latestPosts as $post)
                        <article class="bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-md dark:shadow-none border border-gray-150 dark:border-slate-800/80 flex flex-col group hover:shadow-xl transition-all duration-300">
                            <!-- Thumbnail -->
                            <div class="relative h-48 overflow-hidden bg-gray-100 dark:bg-slate-800 shrink-0 border-b border-gray-100 dark:border-slate-800/80">
                            {{-- width/height + lazy loading: giữ tỉ lệ khung ảnh chống nhảy bố cục (CLS) và hoãn tải ảnh dưới màn hình đầu --}}
                            <img src="{{ $post->thumbnail ?? asset('assets/images/default-thumbnail.jpg') }}" alt="{{ $post->title }}" width="600" height="384" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @if($post->category)
                            <span class="absolute top-4 left-4 bg-shopee text-white text-[10px] font-bold px-2.5 py-1 rounded-full">
                                {{ $post->category->name }}
                            </span>
                            @endif
                        </div>
                            <!-- Content -->
                            <div class="p-6 flex-grow flex flex-col justify-between space-y-4">
                                <div class="space-y-2.5">
                                    <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-slate-500 font-medium">
                                        {{-- Thẻ <time> kèm datetime chuẩn ISO giúp công cụ tìm kiếm nhận diện chính xác ngày đăng bài --}}
                                        <time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}" class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> {{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at->format('d/m/Y') }}</time>
                                        <span class="flex items-center gap-1.5"><i data-lucide="eye" class="w-3.5 h-3.5"></i> {{ __(':count lượt xem', ['count' => number_format($post->view_count)]) }}</span>
                                    </div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base md:text-lg leading-snug group-hover:text-shopee transition-colors line-clamp-2">
                                        <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ $post->summary }}
                                    </p>
                                </div>
                                <a href="{{ route('blog.show', $post->slug) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-900 dark:text-slate-300 group-hover:text-shopee dark:group-hover:text-shopee transition-colors pt-2 border-t border-gray-100 dark:border-slate-800">
                                    {{ __('Đọc tiếp') }} <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </article>
                        @endforeach
                    </div>
                </div>
            </section>

@endif

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}?v=1.0.1"></script>
<script>
if (typeof window.savingsChartApp === 'undefined') {
    /**
     * Định nghĩa AlpineJS Component quản lý Biểu Đồ Thống Kê Tiết Kiệm.
     * Lưu trữ chartInstance trực tiếp trên DOM element ($refs.canvas._chartInstance)
     * để tránh bị Alpine.js Reactive Proxy can thiệp làm hỏng canvas context khi chuyển tab.
     * 
     * @param {Object} initialData Dữ liệu nạp sẵn từ Controller
     */
    window.savingsChartApp = function(initialData) {
        return {
            period: initialData.period || '30days',
            loading: false,
            totalFormatted: initialData.total_savings_formatted || '0đ',
            lastLabels: initialData.labels || [],
            lastData: initialData.data || [],

            init() {
                // Chờ DOM và AlpineJS hoàn tất render canvas trước khi khởi tạo Chart.js
                this.$nextTick(() => {
                    this.renderChart(this.lastLabels, this.lastData);
                });

                // Lắng nghe sự kiện thay đổi kích thước cửa sổ hoặc chuyển đổi chế độ màn hình để vẽ lại nếu canvas từng bị ẩn
                window.addEventListener('resize', () => {
                    if (this.$refs.canvas && !this.$refs.canvas._chartInstance && this.lastLabels.length > 0) {
                        this.renderChart(this.lastLabels, this.lastData);
                    }
                });
            },

            /**
             * Cập nhật hoặc khởi tạo biểu đồ đường mượt bằng Chart.js
             */
            renderChart(labels, dataValues) {
                if (!this.$refs.canvas) return;
                const canvas = this.$refs.canvas;

                this.lastLabels = labels;
                this.lastData = dataValues;

                // Kiểm tra nếu canvas đang bị ẩn hoàn toàn (display: none trên mobile/desktop) thì hoãn vẽ
                if (canvas.clientWidth === 0 && canvas.clientHeight === 0) {
                    return;
                }

                // Nếu biểu đồ đã tồn tại trên DOM element, cập nhật dữ liệu trực tiếp và gọi update()
                // Giúp quá trình chuyển đổi tab mượt mà, không bị destroy/recreate gây lỗi trắng màn hình
                if (canvas._chartInstance) {
                    canvas._chartInstance.data.labels = labels;
                    canvas._chartInstance.data.datasets[0].data = dataValues;
                    canvas._chartInstance.update();
                    return;
                }

                // Nếu chưa có, tạo mới Chart instance duy nhất và gắn trực tiếp vào canvas element (không qua Alpine proxy)
                const ctx = canvas.getContext('2d');

                // Tạo dải màu gradient mượt phía dưới đường biểu đồ
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(255, 69, 26, 0.35)');
                gradient.addColorStop(1, 'rgba(255, 69, 26, 0.0)');

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';
                const textColor = isDark ? '#94a3b8' : '#64748b';

                canvas._chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: '{{ __("Tiết kiệm") }}',
                            data: dataValues,
                            borderColor: '#FF451A',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#FF451A',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            pointRadius: dataValues.length > 30 ? 2 : 4,
                            pointHoverRadius: 6,
                            fill: true,
                            backgroundColor: gradient,
                            tension: 0.35,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#0f172a' : '#ffffff',
                                titleColor: isDark ? '#f8fafc' : '#0f172a',
                                bodyColor: '#FF451A',
                                borderColor: isDark ? '#1e293b' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 12,
                                boxPadding: 6,
                                usePointStyle: true,
                                callbacks: {
                                    label: function(context) {
                                        const val = context.parsed.y || 0;
                                        return ' ' + new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(val) + ' đ';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: textColor,
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    },
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 12
                                }
                            },
                            y: {
                                grid: {
                                    color: gridColor,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: textColor,
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    },
                                    callback: function(value) {
                                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                        if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                        return value;
                                    }
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            },

            /**
             * Gửi AJAX request lấy dữ liệu biểu đồ tương ứng với mốc thời gian được chọn
             */
            async fetchPeriod(newPeriod) {
                if (this.loading || this.period === newPeriod) return;
                this.period = newPeriod;
                this.loading = true;

                try {
                    const response = await fetch(`{{ route('dashboard.savings_chart') }}?period=${newPeriod}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const res = await response.json();
                    this.totalFormatted = res.total_savings_formatted;
                    this.renderChart(res.labels || [], res.data || []);
                } catch (err) {
                    console.error('Lỗi khi nạp dữ liệu biểu đồ tiết kiệm:', err);
                } finally {
                    this.loading = false;
                }
            }
        };
    };
}
</script>
@endpush

<div class="bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800"
     x-data="savingsChartApp({{ json_encode($savingsChartData ?? ['labels' => [], 'data' => [], 'total_savings_formatted' => '0đ', 'period' => '30days']) }})">
    
    <!-- Header của Card Biểu Đồ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/20 text-[#FF451A] rounded-xl flex items-center justify-center border border-orange-100 dark:border-orange-900/40 shrink-0 shadow-sm">
                <i data-lucide="trending-up" class="w-5 h-5 stroke-[2.2]"></i>
            </div>
            <div>
                <h3 class="text-[14px] sm:text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide flex items-center gap-2">
                    {{ __('THỐNG KÊ TIẾT KIỆM') }}
                </h3>
                <p class="text-[11px] sm:text-[12px] font-medium text-gray-500 dark:text-slate-400">
                    {{ __('Tổng tiết kiệm dự kiến') }}: 
                    <span class="font-extrabold text-[#FF451A] dark:text-orange-400" x-text="totalFormatted">
                        {{ $savingsChartData['total_savings_formatted'] ?? '0đ' }}
                    </span>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 block sm:inline sm:ml-1 font-normal">({{ __('Bao gồm đơn đã duyệt & chờ duyệt') }})</span>
                </p>
            </div>
        </div>

        <!-- Bộ chọn mốc thời gian (Tabs) -->
        <div class="flex items-center bg-gray-100 dark:bg-slate-800/80 p-1 rounded-xl border border-gray-200/60 dark:border-slate-700/60 text-xs font-bold self-start sm:self-auto overflow-x-auto max-w-full">
            <button type="button" 
                    @click="fetchPeriod('7days')" 
                    :class="period === '7days' ? 'bg-white dark:bg-slate-900 text-[#FF451A] shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap">
                {{ __('7 ngày') }}
            </button>
            <button type="button" 
                    @click="fetchPeriod('30days')" 
                    :class="period === '30days' ? 'bg-white dark:bg-slate-900 text-[#FF451A] shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap">
                {{ __('30 ngày') }}
            </button>
            <button type="button" 
                    @click="fetchPeriod('90days')" 
                    :class="period === '90days' ? 'bg-white dark:bg-slate-900 text-[#FF451A] shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap">
                {{ __('90 ngày') }}
            </button>
            <button type="button" 
                    @click="fetchPeriod('this_year')" 
                    :class="period === 'this_year' ? 'bg-white dark:bg-slate-900 text-[#FF451A] shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-3 py-1.5 rounded-lg transition-all whitespace-nowrap">
                {{ __('Năm nay') }}
            </button>
        </div>
    </div>

    <!-- Khung hiển thị canvas biểu đồ -->
    <div class="relative h-64 sm:h-72 w-full">
        <!-- Overlay Loading khi đang tải dữ liệu từ AJAX -->
        <div x-show="loading" 
             class="absolute inset-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-[2px] z-10 flex items-center justify-center rounded-xl">
            <div class="flex items-center gap-2 text-xs font-bold text-[#FF451A] bg-white dark:bg-slate-800 px-4 py-2 rounded-full shadow-md border border-orange-100 dark:border-slate-700">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                <span>{{ __('Đang cập nhật biểu đồ...') }}</span>
            </div>
        </div>

        <canvas x-ref="canvas" class="w-full h-full"></canvas>
    </div>
</div>

{{-- 
    Partial View: Thưởng điểm danh
    Vai trò: Quản lý thiết lập bật tắt tính năng điểm danh nhận thưởng, mức coin thưởng cơ bản hằng ngày, và cấu hình các mốc thưởng chuỗi điểm danh liên tục (sử dụng AlpineJS).
--}}
<div x-show="tab === 'reward'" class="space-y-6" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="gift" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình điểm danh') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cấu hình cơ bản -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                    <i data-lucide="settings" class="w-3.5 h-3.5 text-shopee"></i>
                    {{ __('Cấu hình cơ bản') }}
                </h4>

                <div>
                    <label for="daily_checkin_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái tính năng điểm danh') }}</label>
                    <select name="daily_checkin_enabled"
                        id="daily_checkin_enabled"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['daily_checkin_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                        <option value="0" {{ ($settings['daily_checkin_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Tạm thời khóa hoặc mở tính năng điểm danh nhận thưởng cho người dùng.</span>
                </div>

                <div>
                    <label for="checkin_email_verification_required" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Yêu cầu xác minh Email') }}</label>
                    <select name="checkin_email_verification_required"
                        id="checkin_email_verification_required"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="0" {{ ($settings['checkin_email_verification_required'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Không yêu cầu') }}</option>
                        <option value="1" {{ ($settings['checkin_email_verification_required'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bắt buộc đã xác minh Email') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-405 dark:text-slate-500 mt-1 block">Yêu cầu tài khoản thành viên phải hoàn tất xác minh Email mới được phép điểm danh.</span>
                </div>

                <div>
                    <!-- Tuỳ chọn ON/OFF hiển thị Bảng Vàng Chuyên Cần -->
                    <label for="show_checkin_leaderboard" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Hiển thị Bảng Vàng Chuyên Cần') }}</label>
                    <select name="show_checkin_leaderboard"
                        id="show_checkin_leaderboard"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['show_checkin_leaderboard'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hiển thị') }}</option>
                        <option value="0" {{ ($settings['show_checkin_leaderboard'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hiển thị') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Bật hoặc tắt hiển thị Bảng Vàng Chuyên Cần (Bảng xếp hạng chuỗi điểm danh của các thành viên).</span>
                </div>

                <div>
                    <!-- Cấu hình thiết bị cho phép điểm danh -->
                    <label for="checkin_device_allow" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thiết bị cho phép điểm danh') }}</label>
                    <select name="checkin_device_allow"
                        id="checkin_device_allow"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="both" {{ ($settings['checkin_device_allow'] ?? 'both') === 'both' ? 'selected' : '' }}>{{ __('Tất cả thiết bị (Mobile & Desktop)') }}</option>
                        <option value="mobile" {{ ($settings['checkin_device_allow'] ?? 'both') === 'mobile' ? 'selected' : '' }}>{{ __('Chỉ thiết bị di động (Mobile)') }}</option>
                        <option value="desktop" {{ ($settings['checkin_device_allow'] ?? 'both') === 'desktop' ? 'selected' : '' }}>{{ __('Chỉ máy tính (Desktop)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Giới hạn loại thiết bị của thành viên được phép bấm điểm danh.</span>
                </div>

                <div>
                    <!-- Cấu hình số đơn hàng tối thiểu trong tháng để được điểm danh -->
                    <label for="checkin_min_orders_monthly" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đơn hàng tối thiểu trong tháng') }}</label>
                    <input type="number"
                        name="checkin_min_orders_monthly"
                        id="checkin_min_orders_monthly"
                        value="{{ $settings['checkin_min_orders_monthly'] ?? '0' }}"
                        min="0"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Số lượng đơn hàng hoàn tiền phát sinh tối thiểu trong tháng hiện tại để được phép điểm danh (Nhập 0 để không giới hạn).</span>
                </div>

                <div>
                    <!-- Cấu hình số ngày đăng ký tối thiểu để được điểm danh -->
                    <label for="checkin_min_account_age_days" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số ngày đăng ký tối thiểu') }}</label>
                    <input type="number"
                        name="checkin_min_account_age_days"
                        id="checkin_min_account_age_days"
                        value="{{ $settings['checkin_min_account_age_days'] ?? '0' }}"
                        min="0"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Tài khoản phải đăng ký tối thiểu bao nhiêu ngày mới được phép điểm danh (Nhập 0 để tắt, cho phép tài khoản mới đăng ký điểm danh ngay).</span>
                </div>

                <div>
                    <label for="checkin_reward_coins" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thưởng điểm danh hằng ngày (VNĐ)') }}</label>
                    <input type="number"
                        name="checkin_reward_coins"
                        id="checkin_reward_coins"
                        value="{{ $settings['checkin_reward_coins'] ?? '500' }}"
                        min="0"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Số tiền nhận được khi điểm danh thành công mỗi ngày.</span>
                </div>
            </div>

            <!-- Cấu hình mốc thưởng chuỗi nâng cao -->
            <div class="space-y-4" 
                x-data="{
                    milestones: {},
                    newDay: '',
                    newCoins: '',
                    init() {
                        this.milestones = JSON.parse(this.$el.dataset.milestones || '{}');
                    },
                    addMilestone() {
                        let day = parseInt(this.newDay);
                        let coins = parseFloat(this.newCoins);
                        if (day > 0 && coins >= 0) {
                            this.milestones[day] = coins;
                            this.milestones = {...this.milestones};
                            this.newDay = '';
                            this.newCoins = '';
                        } else {
                            alert('Vui lòng nhập ngày lớn hơn 0 và số tiền hợp lệ!');
                        }
                    },
                    removeMilestone(day) {
                        delete this.milestones[day];
                        this.milestones = {...this.milestones};
                    }
                }"
                data-milestones="{{ json_encode(json_decode($settings['checkin_streak_milestones'] ?? '{"7":2000}', true) ?: ['7' => 2000]) }}">
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="gift" class="w-3.5 h-3.5 text-shopee"></i>
                        {{ __('Mốc thưởng chuỗi liên tục') }}
                    </span>
                    <span class="text-[10px] text-gray-400 font-normal">{{ __('Tự động cộng thêm khi đạt chuỗi') }}</span>
                </h4>

                <!-- Lưu JSON vào input ẩn để gửi lên server -->
                <input type="hidden" name="checkin_streak_milestones" :value="JSON.stringify(milestones)">

                <!-- Danh sách mốc hiện tại -->
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/80 space-y-3">
                    <template x-if="Object.keys(milestones).length === 0">
                        <p class="text-xs text-gray-400 text-center py-2">{{ __('Chưa có mốc thưởng chuỗi nào được cấu hình.') }}</p>
                    </template>

                    <div class="space-y-2">
                        <template x-for="(coins, day) in milestones" :key="day">
                            <div class="flex items-center justify-between bg-white dark:bg-slate-800 px-3 py-2.5 rounded-xl border border-gray-100 dark:border-slate-700/50 text-xs shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 bg-orange-50 dark:bg-orange-950/30 text-shopee flex items-center justify-center font-bold rounded-lg" x-text="day"></span>
                                    <span class="font-medium text-gray-700 dark:text-slate-300">{{ __('ngày liên tiếp') }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="'+' + Number(coins).toLocaleString() + ' VNĐ'"></span>
                                    <button type="button" @click="removeMilestone(day)" class="text-red-500 hover:text-red-700 p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Thêm mốc mới -->
                    <div class="flex gap-2 items-center border-t border-gray-100 dark:border-slate-700 pt-3 mt-2">
                        <div class="w-1/3">
                            <input type="number" x-model="newDay" placeholder="{{ __('Số ngày') }}" min="1" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                        <div class="w-2/3 flex gap-2">
                            <input type="number" x-model="newCoins" placeholder="{{ __('Thưởng thêm (VNĐ)') }}" min="0" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <button type="button" @click="addMilestone()" class="px-3 py-2 bg-shopee hover:bg-shopee-dark text-white rounded-xl text-xs font-semibold shrink-0 flex items-center gap-1 shadow-sm">
                                {{ __('Thêm') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cấu hình tự động chuyển hướng khi điểm danh -->
            <div class="space-y-4 md:col-span-2 pt-6 border-t border-gray-200/60 dark:border-slate-800/85">
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                    <i data-lucide="shuffle" class="w-3.5 h-3.5 text-shopee"></i>
                    {{ __('Cấu hình chuyển hướng Shopee khi điểm danh') }}
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="checkin_redirect_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái chuyển hướng') }}</label>
                        <select name="checkin_redirect_enabled"
                            id="checkin_redirect_enabled"
                            class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="1" {{ ($settings['checkin_redirect_enabled'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật tự động chuyển hướng') }}</option>
                            <option value="0" {{ ($settings['checkin_redirect_enabled'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt chuyển hướng') }}</option>
                        </select>
                        <span class="text-[9px] text-gray-405 dark:text-slate-500 mt-1 block">Khi bật, hệ thống tự động mở liên kết Shopee khi người dùng nhấn nút điểm danh.</span>
                    </div>

                    <div>
                        <label for="checkin_redirect_device" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thiết bị áp dụng') }}</label>
                        <select name="checkin_redirect_device"
                            id="checkin_redirect_device"
                            class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="both" {{ ($settings['checkin_redirect_device'] ?? 'both') === 'both' ? 'selected' : '' }}>{{ __('Tất cả thiết bị (Mobile & Desktop)') }}</option>
                            <option value="mobile" {{ ($settings['checkin_redirect_device'] ?? 'both') === 'mobile' ? 'selected' : '' }}>{{ __('Chỉ trên thiết bị di động (Mobile)') }}</option>
                            <option value="desktop" {{ ($settings['checkin_redirect_device'] ?? 'both') === 'desktop' ? 'selected' : '' }}>{{ __('Chỉ trên máy tính (Desktop)') }}</option>
                        </select>
                        <span class="text-[9px] text-gray-405 dark:text-slate-500 mt-1 block">Lựa chọn loại thiết bị của người dùng sẽ áp dụng tự động mở liên kết.</span>
                    </div>

                    <div>
                        <label for="checkin_redirect_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Liên kết Shopee chuyển hướng') }}</label>
                        <input type="url"
                            name="checkin_redirect_url"
                            id="checkin_redirect_url"
                            placeholder="https://shopee.vn/..."
                            value="{{ $settings['checkin_redirect_url'] ?? '' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <span class="text-[9px] text-gray-405 dark:text-slate-500 mt-1 block">Đường dẫn liên kết Shopee tự động mở ra ở tab mới khi người dùng điểm danh.</span>
                    </div>

                    <div>
                        <label for="checkin_redirect_utm_source" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('UTM Source Điểm Danh') }}</label>
                        <input type="text"
                            name="checkin_redirect_utm_source"
                            id="checkin_redirect_utm_source"
                            placeholder="Ví dụ: diemdanh"
                            value="{{ $settings['checkin_redirect_utm_source'] ?? 'diemdanh' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <span class="text-[9px] text-gray-405 dark:text-slate-500 mt-1 block">Giá trị dùng để nhận diện và phân loại doanh thu đơn hàng thuộc chức năng điểm danh.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

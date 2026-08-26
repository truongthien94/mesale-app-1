{{-- 
    Partial View: Cấu hình rút tiền
    Vai trò: Quản lý số tiền rút tối thiểu, yêu cầu OTP rút tiền qua Email, cách tính và giá trị phí rút, danh sách ngân hàng hỗ trợ rút tiền (quản lý bằng tags qua AlpineJS), nội dung quy định rút tiền.
--}}
<div x-show="tab === 'withdraw'" class="space-y-6" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="banknote" class="w-4 h-4 text-shopee"></i>
            {{ __('Thông số & Quy định rút tiền') }}
        </h3>

        <!-- Trạng thái bật/tắt tính năng rút tiền -->
        <div>
            <label for="withdrawal_enabled" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Trạng thái tính năng rút tiền') }}</label>
            <select name="withdrawal_enabled"
                id="withdrawal_enabled"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <option value="1" {{ ($settings['withdrawal_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                <option value="0" {{ ($settings['withdrawal_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
            </select>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tạm thời khóa hoặc mở tính năng tạo lệnh rút tiền cho tất cả thành viên trên hệ thống.') }}</span>
        </div>

        <div>
            <label for="ios_payout_disabled_version" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Phiên bản iOS tắt tính năng nhận thưởng') }}</label>
            <input type="text"
                name="ios_payout_disabled_version"
                id="ios_payout_disabled_version"
                value="{{ $settings['ios_payout_disabled_version'] ?? '' }}"
                placeholder="Ví dụ: 1.0.1"
                pattern="[0-9]+(\.[0-9]+){1,2}"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Chỉ phiên bản khớp chính xác giá trị này nhận false. Để trống hoặc các phiên bản khác đều nhận true. Android và Web không bị ảnh hưởng.') }}</span>
        </div>

        <!-- Cấu hình số tiền rút tối thiểu và xác minh OTP rút tiền -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="min_withdraw" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số tiền rút tối thiểu') }}</label>
                <input type="number"
                    name="min_withdraw"
                    id="min_withdraw"
                    value="{{ $settings['min_withdraw'] ?? '50000' }}"
                    min="0"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Số tiền tối thiểu thành viên phải có để tạo yêu cầu rút tiền (ví dụ: 50,000).') }}</span>
            </div>

            <div>
                <label for="withdraw_otp_required" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Xác minh OTP khi rút tiền') }}</label>
                <select name="withdraw_otp_required"
                    id="withdraw_otp_required"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['withdraw_otp_required'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                    <option value="0" {{ ($settings['withdraw_otp_required'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Yêu cầu thành viên xác minh mã OTP gửi qua email trước khi tạo lệnh rút tiền.') }}</span>
            </div>
        </div>

        <!-- Cấu hình phí rút tiền và ràng buộc tài khoản ngân hàng độc nhất -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="withdrawal_fee_type" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Loại phí rút tiền') }}</label>
                <select name="withdrawal_fee_type"
                    id="withdrawal_fee_type"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="percentage" {{ ($settings['withdrawal_fee_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>{{ __('Phần trăm (%)') }}</option>
                    <option value="fixed" {{ ($settings['withdrawal_fee_type'] ?? 'percentage') === 'fixed' ? 'selected' : '' }}>{{ __('Cố định (đ)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Lựa chọn cách tính phí theo phần trăm tổng số tiền rút hoặc phí cố định mỗi đơn.') }}</span>
            </div>

            <div>
                <label for="withdrawal_fee_value" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Giá trị phí rút tiền') }}</label>
                <input type="number"
                    step="0.01"
                    name="withdrawal_fee_value"
                    id="withdrawal_fee_value"
                    value="{{ $settings['withdrawal_fee_value'] ?? '0' }}"
                    min="0"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập phần trăm (ví dụ: 2%) hoặc số tiền cố định (ví dụ: 5000) tuỳ theo loại phí đã chọn.') }}</span>
            </div>

            <div>
                <label for="withdraw_unique_account" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Độc nhất số tài khoản') }}</label>
                <select name="withdraw_unique_account"
                    id="withdraw_unique_account"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['withdraw_unique_account'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                    <option value="0" {{ ($settings['withdraw_unique_account'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Ngăn chặn nhiều tài khoản thành viên dùng chung một số tài khoản ngân hàng hoặc ví điện tử.') }}</span>
            </div>
        </div>

        <!-- Cấu hình cho phép thành viên lưu sẵn số tài khoản (STK) để chọn nhanh khi rút -->
        <div>
            <label for="withdraw_saved_accounts_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Cho phép lưu tài khoản nhận tiền (STK)') }}</label>
            <select name="withdraw_saved_accounts_enabled"
                id="withdraw_saved_accounts_enabled"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <option value="1" {{ ($settings['withdraw_saved_accounts_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                <option value="0" {{ ($settings['withdraw_saved_accounts_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
            </select>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để thành viên lưu sẵn số tài khoản (STK) ngân hàng / ví điện tử và chọn nhanh khi rút tiền; tắt thì mỗi lần rút phải nhập lại từ đầu.') }}</span>
        </div>

        <!-- Cấu hình Rút tiền qua Ngân hàng & Ngân hàng được phép rút tiền -->
        <div x-data="{ bankEnabled: '{{ $settings['withdraw_bank_enabled'] ?? '1' }}' }" class="space-y-4">
            <div>
                <label for="withdraw_bank_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Rút tiền qua Ngân hàng') }}</label>
                <select name="withdraw_bank_enabled"
                    id="withdraw_bank_enabled"
                    x-model="bankEnabled"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1">{{ __('Bật hoạt động') }}</option>
                    <option value="0">{{ __('Tắt hoạt động') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Cho phép thành viên tạo yêu cầu rút tiền qua tài khoản ngân hàng.') }}</span>
            </div>

            <!-- Cấu hình các ngân hàng được phép rút tiền sử dụng AlpineJS để quản lý tags -->
            <div x-show="bankEnabled === '1'" x-transition x-cloak x-data='{
                tags: {!! json_encode(array_map("trim", explode(",", $settings["allowed_banks"] ?? "Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank"))) !!},
                newTag: "",
                // Mảng chứa toàn bộ 65 ngân hàng lấy từ API VietQR phục vụ việc gợi ý thêm nhanh cho Admin
                suggestedBanks: ["VietinBank", "Vietcombank", "BIDV", "Agribank", "OCB", "MBBank", "Techcombank", "ACB", "VPBank", "TPBank", "Sacombank", "HDBank", "VietCapitalBank", "SCB", "VIB", "SHB", "Eximbank", "MSB", "CAKE", "Ubank", "ViettelMoney", "Timo", "VNPTMoney", "SaigonBank", "BacABank", "MoMo", "PVcomBank Pay", "PVcomBank", "MBV", "NCB", "ShinhanBank", "ABBANK", "VietABank", "NamABank", "PGBank", "VietBank", "BaoVietBank", "SeABank", "COOPBANK", "LPBank", "KienLongBank", "KBank", "MAFC", "HongLeong", "KEBHANAHN", "KEBHanaHCM", "Citibank", "CBBank", "CIMB", "DBSBank", "Vikki", "VBSP", "GPBank", "KookminHCM", "KookminHN", "Woori", "VRB", "HSBC", "IBKHN", "IBKHCM", "IndovinaBank", "UnitedOverseas", "Nonghyup", "StandardChartered", "PublicBank"],
                addTag() {
                    let val = this.newTag.trim();
                    if (val && !this.tags.includes(val)) {
                        this.tags.push(val);
                    }
                    this.newTag = "";
                },
                removeTag(index) {
                    this.tags.splice(index, 1);
                }
            }' class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Ngân hàng được phép rút tiền') }}</label>

                <!-- Hiển thị các thẻ badge ngân hàng -->
                <div class="flex flex-wrap gap-2 p-3 border border-gray-200 rounded-xl bg-white dark:bg-slate-800 min-h-12 items-center focus-within:ring-2 focus-within:ring-shopee/20 focus-within:border-shopee transition-all">
                    <template x-for="(tag, index) in tags" :key="index">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-50 dark:bg-orange-950/30 text-shopee font-bold rounded-lg text-xs border border-orange-100 dark:border-orange-900/50">
                            <span x-text="tag"></span>
                            <button type="button" @click="removeTag(index)" class="hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </template>
                    <input type="text"
                        x-model="newTag"
                        @keydown.enter.prevent="addTag()"
                        placeholder="Nhập tên ngân hàng rồi nhấn Enter..."
                        class="flex-grow min-w-[200px] outline-none text-xs bg-transparent border-none focus:ring-0 p-1 text-gray-700 dark:text-slate-300">
                </div>

                <input type="hidden" name="allowed_banks" :value="tags.join(',')">
                <span class="text-[9px] text-gray-400 mt-1 block">Nhấn Enter sau khi nhập để thêm. Người dùng chỉ được rút tiền qua các ngân hàng trong danh sách này.</span>

                <!-- Danh sách gợi ý toàn bộ các ngân hàng từ API VietQR, thiết kế cuộn dọc tinh tế max-h-24 tránh tràn giao diện -->
                <div class="mt-2 flex flex-col gap-2" x-data="{ searchQuery: '' }">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-[10px] text-gray-400 font-medium shrink-0">{{ __('Gợi ý thêm nhanh toàn bộ ngân hàng (VietQR):') }}</span>
                        <!-- Ô tìm kiếm nhanh ngân hàng -->
                        <div class="relative max-w-xs w-48 shrink-0">
                            <input type="text"
                                x-model="searchQuery"
                                placeholder="{{ __('Tìm kiếm ngân hàng...') }}"
                                class="w-full pl-2 pr-6 py-1 border border-gray-200 dark:border-slate-800 rounded-lg text-[10px] focus:outline-none focus:ring-1 focus:ring-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-350">
                            <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-450 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="max-h-28 overflow-y-auto pr-1 flex flex-wrap gap-1.5 border border-gray-100/50 dark:border-slate-800/80 p-2 rounded-xl bg-gray-50/30 dark:bg-slate-900/10">
                        <template x-for="bank in suggestedBanks.filter(b => b.toLowerCase().includes(searchQuery.toLowerCase().trim()))" :key="bank">
                            <button type="button"
                                x-show="!tags.includes(bank)"
                                @click="tags.push(bank)"
                                class="px-2.5 py-1 border border-gray-200 dark:border-slate-700 hover:border-shopee hover:text-shopee text-[10px] font-medium rounded-lg transition-all bg-white dark:bg-slate-800 hover:bg-orange-50/20">
                                + <span x-text="bank"></span>
                            </button>
                        </template>
                        <!-- Thông báo nếu không tìm thấy ngân hàng nào khớp -->
                        <div x-show="suggestedBanks.filter(b => b.toLowerCase().includes(searchQuery.toLowerCase().trim())).length === 0" class="w-full text-center py-2 text-[10px] text-gray-400">
                            {{ __('Không tìm thấy ngân hàng nào khớp!') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cấu hình Rút tiền qua Ví điện tử & Ví điện tử được phép rút tiền -->
        <div x-data="{ walletEnabled: '{{ $settings['withdraw_wallet_enabled'] ?? '1' }}' }" class="space-y-4">
            <div>
                <label for="withdraw_wallet_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Rút tiền qua Ví điện tử') }}</label>
                <select name="withdraw_wallet_enabled"
                    id="withdraw_wallet_enabled"
                    x-model="walletEnabled"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1">{{ __('Bật hoạt động') }}</option>
                    <option value="0">{{ __('Tắt hoạt động') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Cho phép thành viên tạo yêu cầu rút tiền qua ví điện tử (Momo, ZaloPay...).') }}</span>
            </div>

            <!-- Cấu hình các ví điện tử được phép rút tiền sử dụng AlpineJS để quản lý tags -->
            <div x-show="walletEnabled === '1'" x-transition x-cloak x-data='{
                tags: {!! json_encode(array_map("trim", explode(",", $settings["allowed_wallets"] ?? "Momo,ZaloPay,ShopeePay"))) !!},
                newTag: "",
                addTag() {
                    let val = this.newTag.trim();
                    if (val && !this.tags.includes(val)) {
                        this.tags.push(val);
                    }
                    this.newTag = "";
                },
                removeTag(index) {
                    this.tags.splice(index, 1);
                }
            }' class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Ví điện tử được phép rút tiền') }}</label>

                <!-- Hiển thị các thẻ badge ví điện tử -->
                <div class="flex flex-wrap gap-2 p-3 border border-gray-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-800 min-h-12 items-center focus-within:ring-2 focus-within:ring-shopee/20 focus-within:border-shopee transition-all">
                    <template x-for="(tag, index) in tags" :key="index">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-xs border border-blue-100 dark:border-blue-900/50">
                            <span x-text="tag"></span>
                            <button type="button" @click="removeTag(index)" class="hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </template>
                    <input type="text"
                        x-model="newTag"
                        @keydown.enter.prevent="addTag()"
                        placeholder="Nhập tên ví điện tử rồi nhấn Enter..."
                        class="flex-grow min-w-[200px] outline-none text-xs bg-transparent border-none focus:ring-0 p-1 text-gray-700 dark:text-slate-300">
                </div>

                <input type="hidden" name="allowed_wallets" :value="tags.join(',')">
                <span class="text-[9px] text-gray-400 mt-1 block">Nhấn Enter sau khi nhập để thêm. Người dùng chỉ được rút tiền qua các ví điện tử trong danh sách này.</span>

                <!-- Danh sách gợi ý các ví điện tử phổ biến để admin click chọn nhanh -->
                <div class="mt-2 flex flex-wrap gap-1.5 items-center">
                    <span class="text-[10px] text-gray-400 font-medium mr-1">{{ __('Gợi ý thêm nhanh:') }}</span>
                    <template x-for="wallet in ['Momo', 'ZaloPay', 'ShopeePay', 'Viettel Money', 'VNPAY']" :key="wallet">
                        <button type="button"
                            x-show="!tags.includes(wallet)"
                            @click="tags.push(wallet)"
                            class="px-2.5 py-1 border border-gray-200 dark:border-slate-700 hover:border-shopee hover:text-shopee text-[10px] font-medium rounded-lg transition-all bg-white dark:bg-slate-800 hover:bg-orange-50/20">
                            + <span x-text="wallet"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Cấu hình quy định rút tiền dưới dạng textarea -->
        <div>
            <label for="withdrawal_rules" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Nội dung quy định rút tiền') }}</label>
            <textarea name="withdrawal_rules"
                id="withdrawal_rules"
                rows="6"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 placeholder-gray-400"
                placeholder="Ví dụ:&#10;- Vui lòng điền đúng thông tin số tài khoản...&#10;- Yêu cầu được duyệt trong 24h làm việc...">{{ $settings['withdrawal_rules'] ?? "Vui lòng điền đúng thông tin số tài khoản và viết hoa tên chủ tài khoản không dấu. Hệ thống không chịu trách nhiệm nếu chuyển khoản sai thông tin do người dùng cung cấp.\nCác yêu cầu rút tiền được duyệt thủ công bởi Admin trong vòng 1-24h làm việc.\nTài khoản vi phạm, cố tình gian lận điểm danh hoặc tạo đơn Shopee ảo sẽ bị khoá vĩnh viễn và huỷ số dư ví." }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">Quy định và hướng dẫn rút tiền hiển thị trực tiếp cho thành viên khi tạo yêu cầu rút. Hỗ trợ xuống dòng.</span>
        </div>

        <!-- Cấu hình tùy chỉnh mã đơn rút tiền -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full mt-6">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="hash" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình tùy chỉnh mã đơn rút tiền') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="withdraw_code_prefix" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Tiền tố mã đơn rút tiền (Prefix)') }}
                    </label>
                    <input type="text"
                        name="withdraw_code_prefix"
                        id="withdraw_code_prefix"
                        value="{{ $settings['withdraw_code_prefix'] ?? 'HTS' }}"
                        placeholder="{{ __('Ví dụ: HTS, WDR, RUTTIEN...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Ký tự viết liền không dấu đi kèm mã đơn rút tiền để nhận diện nguồn traffic hoặc thương hiệu.') }}</span>
                </div>

                <div>
                    <label for="withdraw_code_prefix_position" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Vị trí tiền tố') }}
                    </label>
                    <select name="withdraw_code_prefix_position"
                        id="withdraw_code_prefix_position"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="left" {{ ($settings['withdraw_code_prefix_position'] ?? 'left') === 'left' ? 'selected' : '' }}>{{ __('Bên trái (Ví dụ: HTSxxxxxx)') }}</option>
                        <option value="right" {{ ($settings['withdraw_code_prefix_position'] ?? 'left') === 'right' ? 'selected' : '' }}>{{ __('Bên phải (Ví dụ: xxxxxxHTS)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Chọn vị trí hiển thị của tiền tố nằm trước hay nằm sau chuỗi ký tự ngẫu nhiên.') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label for="withdraw_code_random_length" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Số ký tự ngẫu nhiên') }}
                    </label>
                    <input type="number"
                        name="withdraw_code_random_length"
                        id="withdraw_code_random_length"
                        value="{{ $settings['withdraw_code_random_length'] ?? '6' }}"
                        min="4"
                        max="32"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Độ dài phần chuỗi ngẫu nhiên được sinh ra (giới hạn từ 4 đến 32 ký tự để đảm bảo tính duy nhất và bảo mật).') }}</span>
                </div>

                <div>
                    <label for="withdraw_code_random_type" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Kiểu ký tự ngẫu nhiên') }}
                    </label>
                    <select name="withdraw_code_random_type"
                        id="withdraw_code_random_type"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="alphanumeric_upper" {{ ($settings['withdraw_code_random_type'] ?? 'alphanumeric_upper') === 'alphanumeric_upper' ? 'selected' : '' }}>{{ __('Chữ và số in hoa (Ví dụ: A1B2C3)') }}</option>
                        <option value="alphanumeric" {{ ($settings['withdraw_code_random_type'] ?? 'alphanumeric_upper') === 'alphanumeric' ? 'selected' : '' }}>{{ __('Chữ và số (Ví dụ: a1B2c3)') }}</option>
                        <option value="numeric" {{ ($settings['withdraw_code_random_type'] ?? 'alphanumeric_upper') === 'numeric' ? 'selected' : '' }}>{{ __('Chỉ số (Ví dụ: 123456)') }}</option>
                        <option value="alpha_upper" {{ ($settings['withdraw_code_random_type'] ?? 'alphanumeric_upper') === 'alpha_upper' ? 'selected' : '' }}>{{ __('Chỉ chữ in hoa (Ví dụ: ABCDEF)') }}</option>
                        <option value="alpha" {{ ($settings['withdraw_code_random_type'] ?? 'alphanumeric_upper') === 'alpha' ? 'selected' : '' }}>{{ __('Chỉ chữ (Ví dụ: abcDEF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Lựa chọn định dạng ký tự ngẫu nhiên phù hợp nhất với mong muốn quản trị của bạn.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

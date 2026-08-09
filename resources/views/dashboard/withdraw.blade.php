@extends('layouts.app')

@section('title', __('Yêu Cầu Rút Tiền Về Ngân Hàng') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Thanh điều hướng bên trái (Sidebar) - Ẩn trên mobile để tối ưu diện tích hiển thị --}}
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        {{-- Khu vực nội dung chính của trang Rút Tiền --}}
        <div class="lg:col-span-9 space-y-6" 
             x-data="{ 
                 activeTab: 'withdraw',
                 showBalance: true,
                 isMobile: window.innerWidth < 768,
                 method: '{{ \App\Models\Setting::getVal('withdraw_bank_enabled', '1') === '1' ? 'bank' : 'wallet' }}',
                 amount: '{{ old('amount', '') }}',
                 feeType: '{{ $feeType }}',
                 feeValue: {{ $feeValue }},
                 currency: {
                     code: '{{ $currentCurrency->code }}',
                     symbol: '{{ $currentCurrency->symbol }}',
                     rate: {{ $currentCurrency->exchange_rate }},
                     position: '{{ $currentCurrency->symbol_position }}'
                 },
                 otpRequired: {{ \App\Models\Setting::getVal('withdraw_otp_required', '0') === '1' ? 'true' : 'false' }},
                 otpSent: false,
                 otpCode: '',
                 isSubmitting: false,
                 countdown: 0,
                 otpButtonText: '{{ __('Gửi mã OTP') }}',
                 accountNumber: '{{ old('account_number', '') }}',
                 accountName: '{{ old('account_name', '') }}',
                 bankName: '{{ old('bank_name', '') }}',
                 walletName: '{{ old('wallet_name', '') }}',

                 // Sổ tài khoản nhận tiền đã lưu (chọn nhanh khi rút tiền)
                 savedAccountsEnabled: {{ $savedAccountsEnabled ? 'true' : 'false' }},
                 savedAccounts: {{ Illuminate\Support\Js::from($savedAccounts->map(fn($a) => ['id' => $a->id, 'payment_method' => $a->payment_method, 'bank_name' => $a->bank_name, 'account_number' => $a->account_number, 'account_name' => $a->account_name, 'is_default' => (bool) $a->is_default])) }},
                 savingAccount: false,
                 deleteAccountId: null,
                 deleteAccountLabel: '',

                 // Tính toán phí rút tiền dựa trên cấu hình hệ thống
                 // Làm tròn về số nguyên đồng giống hệt MoneyHelper::round() phía máy chủ để số phí xem trước
                 // luôn bằng đúng số phí hệ thống thực sự thu, kể cả khi quản trị viên cấu hình phí cố định lẻ
                 get fee() {
                     let amtVnd = (parseFloat(this.amount) || 0) * this.currency.rate;
                     if (amtVnd <= 0) return 0;
                     if (this.feeType === 'percentage') {
                         return Math.round((amtVnd * this.feeValue) / 100);
                     }
                     return Math.round(this.feeValue);
                 },
                 
                 // Tính số tiền thực nhận sau khi trừ phí
                 get realAmount() {
                     let amtVnd = (parseFloat(this.amount) || 0) * this.currency.rate;
                     if (amtVnd <= 0) return 0;
                     let diff = amtVnd - this.fee;
                     return diff > 0 ? diff : 0;
                 },
                 
                 // Định dạng tiền tệ theo ngôn ngữ và tỷ giá
                 formatCurrency(value) {
                     const converted = value / this.currency.rate;
                     const decimals = this.currency.code === 'VND' ? 0 : 2;
                     const formatted = new Intl.NumberFormat('en-US', { 
                         minimumFractionDigits: decimals, 
                         maximumFractionDigits: decimals 
                     }).format(converted);
                     
                     if (this.currency.position === 'before') {
                         return this.currency.symbol + formatted;
                     }
                     return formatted + this.currency.symbol;
                 },
                 
                 // Gửi mã OTP xác nhận rút tiền qua Email đăng ký
                 sendOtp() {
                     if (this.countdown > 0) return;
                     this.otpButtonText = '{{ __('Đang gửi...') }}';
                     fetch('{{ route('withdraw.send_otp') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}'
                         }
                     })
                     .then(res => res.json())
                     .then(data => {
                         if (data.success) {
                             this.otpSent = true;
                             window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message, type: 'success' } }));
                             this.countdown = 60;
                             let timer = setInterval(() => {
                                 if (this.countdown <= 0) {
                                     clearInterval(timer);
                                     this.otpButtonText = '{{ __('Gửi lại OTP') }}';
                                 } else {
                                     this.otpButtonText = '{{ __('Gửi lại sau') }} (' + this.countdown + 's)';
                                     this.countdown--;
                                 }
                             }, 1000);
                         } else {
                             window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message || '{{ __('Gửi OTP thất bại') }}', type: 'error' } }));
                             this.otpButtonText = '{{ __('Gửi mã OTP') }}';
                         }
                     })
                     .catch(err => {
                         window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}', type: 'error' } }));
                         this.otpButtonText = '{{ __('Gửi mã OTP') }}';
                     });
                 },
                 
                 // Xử lý gửi yêu cầu rút tiền lên hệ thống
                 submitWithdraw() {
                      let turnstileStatus = {{ \App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_withdraw', '0') === '1' ? 'true' : 'false' }};
                      let turnstileResponse = '';
                      if (turnstileStatus) {
                          const inputs = document.getElementsByName('cf-turnstile-response');
                          for (let i = 0; i < inputs.length; i++) {
                              if (inputs[i].value) {
                                  turnstileResponse = inputs[i].value;
                                  break;
                              }
                          }
                          if (!turnstileResponse) {
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng xác minh Captcha để tiếp tục.') }}', type: 'error' } }));
                              return;
                          }
                      }
                     if (this.isSubmitting) return;
                     if (this.otpRequired && !this.otpCode) {
                         window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng nhập mã OTP để tiếp tục.') }}', type: 'error' } }));
                         return;
                     }
                     
                     this.isSubmitting = true;
                     
                     let formData = {
                         amount: parseFloat(this.amount) * this.currency.rate,
                         payment_method: this.method,
                         account_number: this.accountNumber,
                         account_name: this.accountName,
                     };
                     if (this.method === 'bank') {
                         formData.bank_name = this.bankName;
                     }
                     if (this.method === 'wallet') {
                         formData.wallet_name = this.walletName;
                     }
                     if (this.otpRequired) {
                         formData.otp_code = this.otpCode;
                      }
                      if (turnstileStatus) {
                          formData.cf_turnstile_response = turnstileResponse;
                     }

                     fetch('{{ route('withdraw.post') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json'
                         },
                         body: JSON.stringify(formData)
                      })
                      .then(res => {
                          if (!res.ok) {
                              return res.json().then(err => { throw err; });
                          }
                          return res.json();
                      })
                      .then(data => {
                          this.isSubmitting = false;
                          if (data.success) {
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message, type: 'success' } }));
                              setTimeout(() => {
                                  window.location.reload();
                              }, 1500);
                          } else {
                              if (window.turnstile) window.turnstile.reset();
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message || '{{ __('Rút tiền thất bại.') }}', type: 'error' } }));
                          }
                      })
                      .catch(err => {
                          this.isSubmitting = false;
                          let errMsg = '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}';
                          if (err.errors) {
                              let firstKey = Object.keys(err.errors)[0];
                              errMsg = err.errors[firstKey][0];
                          } else if (err.message) {
                              errMsg = err.message;
                          }
                          if (window.turnstile) window.turnstile.reset();
                          window.dispatchEvent(new CustomEvent('toast', { detail: { text: errMsg, type: 'error' } }));
                      });
                  },

                  // Khởi tạo: tự điền sẵn tài khoản mặc định (nếu có) khi mở trang rút tiền
                  init() {
                      if (this.savedAccountsEnabled && !this.accountNumber && !this.accountName) {
                          let def = this.savedAccounts.find(a => a.is_default);
                          if (def) this.fillFromAccount(def);
                      }
                      this.refreshIcons();
                  },

                  // Vẽ lại các icon Lucide sau khi danh sách tài khoản thay đổi (x-for render động)
                  refreshIcons() {
                      this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                  },

                  // Che bớt số tài khoản khi hiển thị trên card (bảo mật hiển thị)
                  maskAccount(num) {
                      if (!num) return '';
                      let s = String(num);
                      if (s.length <= 5) return s;
                      return s.slice(0, 3) + '••••' + s.slice(-3);
                  },

                  // Điền nhanh thông tin form từ một tài khoản đã lưu
                  fillFromAccount(acc) {
                      this.method = acc.payment_method;
                      if (acc.payment_method === 'bank') {
                          this.bankName = acc.bank_name;
                      } else {
                          this.walletName = acc.bank_name;
                      }
                      this.accountNumber = acc.account_number;
                      this.accountName = acc.account_name;
                  },

                  // Lưu thông tin đang nhập trên form vào sổ tài khoản
                  saveCurrentAccount() {
                      if (this.savingAccount) return;
                      let providerName = this.method === 'bank' ? this.bankName : this.walletName;
                      if (!providerName) {
                          window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng chọn ngân hàng hoặc ví nhận tiền trước khi lưu.') }}', type: 'error' } }));
                          return;
                      }
                      if (!this.accountNumber || !this.accountName) {
                          window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng nhập đầy đủ số tài khoản và tên chủ tài khoản.') }}', type: 'error' } }));
                          return;
                      }
                      this.savingAccount = true;
                      fetch('{{ route('payment_accounts.store') }}', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                          body: JSON.stringify({
                              payment_method: this.method,
                              bank_name: providerName,
                              account_number: this.accountNumber,
                              account_name: this.accountName
                          })
                      })
                      .then(res => res.json())
                      .then(data => {
                          this.savingAccount = false;
                          if (data.success && data.account) {
                              if (data.account.is_default) {
                                  this.savedAccounts.forEach(a => a.is_default = false);
                                  this.savedAccounts.unshift(data.account);
                              } else {
                                  this.savedAccounts.push(data.account);
                              }
                              this.refreshIcons();
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message, type: 'success' } }));
                          } else {
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message || '{{ __('Lưu tài khoản thất bại.') }}', type: 'error' } }));
                          }
                      })
                      .catch(() => {
                          this.savingAccount = false;
                          window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}', type: 'error' } }));
                      });
                  },

                  // Đặt một tài khoản làm mặc định
                  setDefaultAccount(id) {
                      fetch('{{ url('dashboard/payment-accounts') }}/' + id + '/default', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                      })
                      .then(res => res.json())
                      .then(data => {
                          if (data.success) {
                              this.savedAccounts.forEach(a => a.is_default = (a.id === id));
                              this.refreshIcons();
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message, type: 'success' } }));
                          } else {
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message || '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}', type: 'error' } }));
                          }
                      })
                      .catch(() => {
                          window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}', type: 'error' } }));
                      });
                  },

                  // Mở modal xác nhận xoá tài khoản
                  askDeleteAccount(acc) {
                      this.deleteAccountId = acc.id;
                      this.deleteAccountLabel = acc.bank_name + ' • ' + acc.account_number;
                  },

                  // Thực hiện xoá tài khoản đã chọn khỏi sổ
                  deleteAccount() {
                      let id = this.deleteAccountId;
                      if (!id) return;
                      fetch('{{ url('dashboard/payment-accounts') }}/' + id, {
                          method: 'DELETE',
                          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                      })
                      .then(res => res.json())
                      .then(data => {
                          if (data.success) {
                              let wasDefault = (this.savedAccounts.find(a => a.id === id) || {}).is_default;
                              this.savedAccounts = this.savedAccounts.filter(a => a.id !== id);
                              // Đồng bộ cờ mặc định phía client giống server (gán mặc định cho tài khoản mới nhất còn lại)
                              if (wasDefault && this.savedAccounts.length > 0) {
                                  this.savedAccounts.forEach(a => a.is_default = false);
                                  this.savedAccounts[0].is_default = true;
                              }
                              this.refreshIcons();
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message, type: 'success' } }));
                          } else {
                              window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message || '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}', type: 'error' } }));
                          }
                          this.deleteAccountId = null;
                          this.deleteAccountLabel = '';
                      })
                      .catch(() => {
                          this.deleteAccountId = null;
                          window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Có lỗi xảy ra, vui lòng thử lại!') }}', type: 'error' } }));
                      });
                  }
              }"
              @resize.window="isMobile = window.innerWidth < 768">

            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <div class="relative overflow-hidden p-5 sm:p-7 bg-gradient-to-r from-[#FFF4EC] via-[#FFF9F5] to-white dark:from-slate-900/40 dark:to-slate-900/20 rounded-2xl border border-orange-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] transition-all duration-300">
                <div class="flex items-start gap-4 max-w-[70%] sm:max-w-[75%] relative z-10">
                    {{-- Icon thẻ ví nổi bật trên nền cam nhạt --}}
                    <div class="w-12 h-12 flex items-center justify-center bg-[#FFEFEB] dark:bg-orange-950/30 dark:border dark:border-orange-100/10 text-shopee rounded-2xl shrink-0 shadow-sm">
                        <i data-lucide="credit-card" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight leading-none pt-1">
                            {{ __('Rút Tiền Về Tài Khoản') }}
                        </h1>
                        <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-2.5 leading-relaxed whitespace-nowrap">
                            {{ __('Rút tiền dễ dàng, nhanh chóng và an toàn') }}
                        </p>
                    </div>
                </div>
                
                {{-- Hình ảnh ví tiền 3D trang trí góc phải --}}
                <div class="absolute right-0 top-0 bottom-0 w-32 flex items-center justify-end pointer-events-none pr-2 sm:pr-6 z-0">
                    <img src="{{ asset('assets/images/withdraw_banner.webp') }}" 
                         class="h-20 sm:h-24 w-auto object-contain select-none drop-shadow-md" 
                         alt="Withdraw">
                </div>
            </div>

            {{-- 2. Mobile Tab Bar (Chỉ hiển thị trên Mobile) --}}
            <div class="block md:hidden bg-gray-50/70 dark:bg-slate-800/40 p-1.5 rounded-2xl border border-gray-100/50 dark:border-slate-800/60 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] flex items-center gap-1.5 mb-4" x-cloak>
                <button type="button" 
                        @click="activeTab = 'withdraw'" 
                        :class="activeTab === 'withdraw' ? 'bg-white dark:bg-slate-900 text-shopee shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white border-transparent'"
                        class="flex-1 py-2.5 rounded-xl text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5 cursor-pointer focus:outline-none">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                    {{ __('Rút tiền') }}
                </button>
                <button type="button" 
                        @click="activeTab = 'history'" 
                        :class="activeTab === 'history' ? 'bg-white dark:bg-slate-900 text-shopee shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white border-transparent'"
                        class="flex-1 py-2.5 rounded-xl text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5 cursor-pointer focus:outline-none">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    {{ __('Lịch sử') }}
                </button>
            </div>

            {{-- 3. Khối Nội dung tạo lệnh Rút tiền (Hoạt động trên cả Mobile/Desktop) --}}
            <div :class="isMobile ? 'space-y-4' : 'grid grid-cols-1 xl:grid-cols-12 gap-8 items-start'" x-show="!isMobile || activeTab === 'withdraw'" x-cloak>
                
                {{-- Ví số dư (Chỉ hiển thị trên Mobile ở Tab Rút tiền) --}}
                <div class="block md:hidden bg-gradient-to-br from-[#1E293B] to-[#0F172A] text-white p-6 rounded-2xl shadow-lg relative overflow-hidden">
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase flex items-center gap-1.5">
                                {{ __('Số dư hiện tại') }}
                                <button type="button" @click="showBalance = !showBalance" class="text-slate-400 hover:text-white transition-colors focus:outline-none">
                                    <i :data-lucide="showBalance ? 'eye' : 'eye-off'" class="w-3.5 h-3.5"></i>
                                </button>
                            </span>
                        </div>
                        <p class="text-2xl font-extrabold text-white tracking-tight">
                            <span x-show="showBalance">{{ \App\Helpers\CurrencyHelper::format($user->balance) }}</span>
                            <span x-show="!showBalance">••••••</span>
                        </p>
                        
                        <div class="border-t border-slate-700/50 my-3.5"></div>
                        
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="block text-slate-400 text-[10px] uppercase tracking-wider mb-0.5">{{ __('Đã tích luỹ') }}</span>
                                <span class="font-extrabold text-white">
                                    <span x-show="showBalance">{{ \App\Helpers\CurrencyHelper::format($user->total_cashback) }}</span>
                                    <span x-show="!showBalance">••••••</span>
                                </span>
                            </div>
                            <div>
                                <span class="block text-slate-400 text-[10px] uppercase tracking-wider mb-0.5">{{ __('Đã ghi nhận') }}</span>
                                <span class="font-extrabold text-blue-400">
                                    <span x-show="showBalance">{{ \App\Helpers\CurrencyHelper::format($user->total_withdrawn) }}</span>
                                    <span x-show="!showBalance">••••••</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    {{-- Icon ví nét chìm mờ ở góc --}}
                    <i data-lucide="wallet" class="w-28 h-28 text-white/[0.03] absolute -right-4 -bottom-4 z-0 pointer-events-none"></i>
                </div>

                {{-- Box Quy định rút tiền (Hiển thị phía trên trên Mobile) --}}
                @if(!empty(trim($withdrawalRules)))
                <div class="block md:hidden bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2 mb-3">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-orange-500 shrink-0"></i>
                        {{ __('Quy định rút tiền') }}
                    </h3>
                    <div class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed space-y-2 text-justify">
                        {!! nl2br(e($withdrawalRules)) !!}
                    </div>
                </div>
                @endif

                {{-- Form Tạo Lệnh Rút Tiền --}}
                <div :class="isMobile ? 'bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl border border-gray-50 dark:border-slate-800 space-y-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]' : 'xl:col-span-7 bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 space-y-6'">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm md:text-base border-b border-gray-100 dark:border-slate-800/80 pb-3">{{ __('Tạo lệnh rút tiền') }}</h3>
                    
                    @if(config('app.demo'))
                    <div class="p-4 my-2 text-xs text-red-800 rounded-xl bg-red-50 border border-red-150 flex items-start gap-2.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5 animate-pulse"></i>
                        <div>
                            <span class="font-bold">{{ __('Chế độ Demo:') }}</span>
                            {{ __('Tính năng tạo yêu cầu rút tiền đang bị khóa để bảo vệ dữ liệu thử nghiệm. Bạn vẫn có thể trải nghiệm xem lịch sử hoặc giao diện.') }}
                        </div>
                    </div>
                    @endif

                    @php
                        $bankEnabled = \App\Models\Setting::getVal('withdraw_bank_enabled', '1') === '1';
                        $walletEnabled = \App\Models\Setting::getVal('withdraw_wallet_enabled', '1') === '1';
                        $enabledCount = ($bankEnabled ? 1 : 0) + ($walletEnabled ? 1 : 0);
                    @endphp
                    @if($enabledCount == 0)
                    <div class="p-4 my-2 text-xs text-red-800 rounded-xl bg-red-50 border border-red-150 flex items-start gap-2.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5 animate-pulse"></i>
                        <div>
                            <span class="font-bold">{{ __('Không khả dụng:') }}</span>
                            {{ __('Hiện tại không có phương thức nhận tiền nào được kích hoạt. Vui lòng quay lại sau.') }}
                        </div>
                    </div>
                    @else
                    <form @submit.prevent="submitWithdraw()" class="space-y-5">
                        @csrf

                        {{-- Sổ tài khoản nhận tiền đã lưu (chọn nhanh, tránh nhập lại) --}}
                        @if($savedAccountsEnabled)
                        <div class="space-y-2.5 pb-5 border-b border-gray-100 dark:border-slate-800/80">
                            <div class="flex items-center justify-between gap-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="bookmark" class="w-3.5 h-3.5 text-shopee"></i>
                                    {{ __('Sổ tài khoản đã lưu') }}
                                </label>
                                <button type="button"
                                        @click="saveCurrentAccount()"
                                        :disabled="savingAccount || {{ config('app.demo') ? 'true' : 'false' }}"
                                        :class="savingAccount || {{ config('app.demo') ? 'true' : 'false' }} ? 'opacity-50 cursor-not-allowed' : 'hover:border-shopee hover:text-shopee cursor-pointer'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] font-bold text-gray-600 dark:text-slate-300 transition-all whitespace-nowrap">
                                    <i data-lucide="bookmark-plus" class="w-3.5 h-3.5"></i>
                                    <span x-text="savingAccount ? '{{ __('Đang lưu...') }}' : '{{ __('Lưu tài khoản này') }}'"></span>
                                </button>
                            </div>

                            {{-- Trạng thái rỗng --}}
                            <p x-show="savedAccounts.length === 0" x-cloak class="text-[11px] text-gray-400 dark:text-slate-500 bg-gray-50/70 dark:bg-slate-800/40 rounded-xl px-3 py-2.5 leading-relaxed">
                                {{ __('Chưa có tài khoản nào được lưu. Điền thông tin nhận tiền bên dưới rồi bấm "Lưu tài khoản này" để dùng nhanh cho lần sau.') }}
                            </p>

                            {{-- Danh sách card tài khoản đã lưu --}}
                            <div x-show="savedAccounts.length > 0" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="acc in savedAccounts" :key="acc.id">
                                    <div @click="fillFromAccount(acc)"
                                         class="relative cursor-pointer border rounded-2xl p-3 pr-8 transition-all select-none"
                                         :class="(accountNumber === acc.account_number && (acc.payment_method === 'bank' ? bankName : walletName) === acc.bank_name) ? 'border-shopee bg-orange-50/40 dark:bg-orange-950/20 ring-1 ring-shopee/20' : 'border-gray-200 dark:border-slate-800 hover:border-shopee/60 hover:bg-gray-50/60 dark:hover:bg-slate-800/40'">
                                        <div class="flex items-center gap-1.5 mb-1 pr-2">
                                            <i data-lucide="landmark" class="w-3.5 h-3.5 text-shopee shrink-0" x-show="acc.payment_method === 'bank'"></i>
                                            <i data-lucide="wallet" class="w-3.5 h-3.5 text-shopee shrink-0" x-show="acc.payment_method !== 'bank'"></i>
                                            <span class="text-xs font-bold text-gray-800 dark:text-slate-200 truncate" x-text="acc.bank_name"></span>
                                            <span x-show="acc.is_default" x-cloak class="shrink-0 px-1.5 py-0.5 rounded-md text-[8px] font-bold bg-shopee/10 text-shopee uppercase tracking-wide">{{ __('Mặc định') }}</span>
                                        </div>
                                        <p class="text-[11px] font-semibold text-gray-600 dark:text-slate-400 tracking-wide" x-text="maskAccount(acc.account_number)"></p>
                                        <p class="text-[10px] text-gray-400 dark:text-slate-500 truncate" x-text="acc.account_name"></p>

                                        {{-- Thao tác: đặt mặc định + xoá --}}
                                        <div class="absolute top-2 right-1.5 flex flex-col gap-1.5">
                                            <button type="button" @click.stop="setDefaultAccount(acc.id)" x-show="!acc.is_default" title="{{ __('Đặt làm mặc định') }}"
                                                    class="text-gray-300 dark:text-slate-600 hover:text-shopee transition-colors">
                                                <i data-lucide="star" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button type="button" @click.stop="askDeleteAccount(acc)" title="{{ __('Xoá tài khoản') }}"
                                                    class="text-gray-300 dark:text-slate-600 hover:text-red-500 transition-colors">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @endif

                        {{-- Số tiền cần rút --}}
                        <div>
                            <label for="amount" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">{{ __('Số tiền cần rút (VND)') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <i data-lucide="wallet" class="w-4 h-4"></i>
                                </div>
                                <input type="number" 
                                       id="amount" 
                                       required 
                                       x-model="amount"
                                       step="any"
                                       placeholder="{{ __('Ví dụ:') }} {{ round($minWithdraw / $currentCurrency->exchange_rate, $currentCurrency->code === 'VND' ? 0 : 2) }}"
                                       class="block w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900/50 dark:text-slate-100">
                            </div>
                            
                            <span class="text-[10px] text-gray-400 mt-1.5 block">
                                {{ __('Số tiền tối thiểu:') }} <strong>{{ \App\Helpers\CurrencyHelper::format($minWithdraw) }}</strong> | {{ __('Phí rút:') }} <strong class="text-green-600 dark:text-green-400">
                                    @if($feeValue == 0)
                                        {{ __('Miễn phí') }}
                                    @elseif($feeType === 'percentage')
                                        {{ $feeValue }}%
                                    @else
                                        {{ \App\Helpers\CurrencyHelper::format($feeValue) }}
                                    @endif
                                </strong>
                            </span>

                            <div x-show="amount > 0" x-transition x-cloak class="mt-2 text-xs text-shopee font-semibold bg-orange-50/50 dark:bg-orange-950/20 p-2.5 rounded-xl border border-orange-100/50 dark:border-orange-900/20 flex justify-between items-center">
                                <span>{{ __('Phí rút:') }} <span x-text="formatCurrency(fee)"></span></span>
                                <span>{{ __('Thực nhận:') }} <span x-text="formatCurrency(realAmount)"></span></span>
                            </div>
                        </div>

                        {{-- Phương thức rút (Ngân hàng / Ví điện tử) --}}
                        @if($enabledCount > 1)
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">{{ __('Hình thức nhận tiền') }}</label>
                            <div class="grid grid-cols-2 gap-4">
                                @if($bankEnabled)
                                <label class="border p-3.5 rounded-2xl flex items-center justify-between cursor-pointer select-none transition-all dark:border-slate-800"
                                       :class="method === 'bank' ? 'border-shopee bg-orange-50/20 dark:bg-orange-950/20 dark:border-shopee' : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/40'">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="landmark" class="w-4 h-4 text-gray-500 dark:text-slate-400"></i>
                                        <span class="text-xs font-bold text-gray-700 dark:text-slate-300">{{ __('Ngân hàng') }}</span>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center transition-all"
                                         :class="method === 'bank' ? 'border-shopee bg-shopee text-white' : 'border-gray-300 dark:border-slate-700'">
                                        <i data-lucide="check" class="w-2.5 h-2.5" x-show="method === 'bank'"></i>
                                    </div>
                                    <input type="radio" name="payment_method" value="bank" x-model="method" class="hidden">
                                </label>
                                @endif
                                
                                @if($walletEnabled)
                                <label class="border p-3.5 rounded-2xl flex items-center justify-between cursor-pointer select-none transition-all dark:border-slate-800"
                                       :class="method === 'wallet' ? 'border-shopee bg-orange-50/20 dark:bg-orange-950/20 dark:border-shopee' : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/40'">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="wallet" class="w-4 h-4 text-gray-500 dark:text-slate-400"></i>
                                        <span class="text-xs font-bold text-gray-700 dark:text-slate-300">{{ __('Ví điện tử') }}</span>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center transition-all"
                                         :class="method === 'wallet' ? 'border-shopee bg-shopee text-white' : 'border-gray-300 dark:border-slate-700'">
                                        <i data-lucide="check" class="w-2.5 h-2.5" x-show="method === 'wallet'"></i>
                                    </div>
                                    <input type="radio" name="payment_method" value="wallet" x-model="method" class="hidden">
                                </label>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Nhập Tên Ngân Hàng (Searchable Dropdown) --}}
                        <div x-show="method === 'bank'" x-transition x-cloak class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Tên ngân hàng nhận') }}</label>
                            <div x-data="{
                                    open: false,
                                    search: '',
                                    banks: {{ Illuminate\Support\Js::from($allowedBanks) }},
                                    get filtered() {
                                        if (!this.search) return this.banks;
                                        let q = this.search.toLowerCase();
                                        return this.banks.filter(b => b.toLowerCase().includes(q));
                                    },
                                    selectBank(val) {
                                        bankName = val;
                                        this.search = '';
                                        this.open = false;
                                    }
                                 }"
                                 @click.outside="open = false; search = ''"
                                 @keydown.escape.window="open = false; search = ''"
                                 class="relative">

                                {{-- Nút hiển thị giá trị đã chọn hoặc placeholder --}}
                                <button type="button"
                                        @click="open = !open; $nextTick(() => { if(open) $refs.bankSearch.focus() })"
                                        class="flex items-center justify-between w-full px-3.5 py-3 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs font-bold bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all cursor-pointer"
                                        :class="open ? 'ring-2 ring-shopee/20 border-shopee' : ''">
                                    <span :class="bankName ? 'text-gray-900 dark:text-slate-100' : 'text-gray-400 dark:text-slate-500'"
                                          x-text="bankName || '{{ __('-- Chọn ngân hàng nhận --') }}'"></span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                </button>

                                {{-- Panel dropdown có ô tìm kiếm --}}
                                <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                     x-cloak
                                     class="absolute z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-xl overflow-hidden">

                                    {{-- Ô tìm kiếm nhanh --}}
                                    <div class="p-2.5 border-b border-gray-100 dark:border-slate-800">
                                        <div class="relative">
                                            <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                                            <input type="text" x-ref="bankSearch" x-model="search"
                                                   placeholder="{{ __('Tìm ngân hàng...') }}"
                                                   @keydown.enter.prevent="if(filtered.length === 1) selectBank(filtered[0])"
                                                   class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-shopee/30 focus:border-shopee placeholder-gray-400">
                                        </div>
                                    </div>

                                    {{-- Danh sách kết quả --}}
                                    <ul class="max-h-48 overflow-y-auto overscroll-contain py-1">
                                        <template x-for="item in filtered" :key="item">
                                            <li @click="selectBank(item)"
                                                class="px-3.5 py-2.5 text-xs font-semibold cursor-pointer transition-colors flex items-center gap-2"
                                                :class="bankName === item ? 'bg-shopee/5 text-shopee dark:bg-orange-950/30' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/60'">
                                                <i data-lucide="check" class="w-3.5 h-3.5 shrink-0" :class="bankName === item ? 'opacity-100 text-shopee' : 'opacity-0'"></i>
                                                <span x-text="item"></span>
                                            </li>
                                        </template>
                                        {{-- Trạng thái không tìm thấy kết quả --}}
                                        <li x-show="filtered.length === 0" class="px-3.5 py-4 text-xs text-gray-400 dark:text-slate-500 text-center">
                                            {{ __('Không tìm thấy ngân hàng phù hợp') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Nhập Tên Ví Điện Tử (Searchable Dropdown) --}}
                        <div x-show="method === 'wallet'" x-transition x-cloak class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Tên ví điện tử nhận') }}</label>
                            <div x-data="{
                                    open: false,
                                    search: '',
                                    wallets: {{ Illuminate\Support\Js::from($allowedWallets) }},
                                    get filtered() {
                                        if (!this.search) return this.wallets;
                                        let q = this.search.toLowerCase();
                                        return this.wallets.filter(w => w.toLowerCase().includes(q));
                                    },
                                    selectWallet(val) {
                                        walletName = val;
                                        this.search = '';
                                        this.open = false;
                                    }
                                 }"
                                 @click.outside="open = false; search = ''"
                                 @keydown.escape.window="open = false; search = ''"
                                 class="relative">

                                {{-- Nút hiển thị giá trị đã chọn hoặc placeholder --}}
                                <button type="button"
                                        @click="open = !open; $nextTick(() => { if(open) $refs.walletSearch.focus() })"
                                        class="flex items-center justify-between w-full px-3.5 py-3 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs font-bold bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all cursor-pointer"
                                        :class="open ? 'ring-2 ring-shopee/20 border-shopee' : ''">
                                    <span :class="walletName ? 'text-gray-900 dark:text-slate-100' : 'text-gray-400 dark:text-slate-500'"
                                          x-text="walletName || '{{ __('-- Chọn ví điện tử nhận --') }}'"></span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                </button>

                                {{-- Panel dropdown có ô tìm kiếm --}}
                                <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                     x-cloak
                                     class="absolute z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-xl overflow-hidden">

                                    {{-- Ô tìm kiếm nhanh --}}
                                    <div class="p-2.5 border-b border-gray-100 dark:border-slate-800">
                                        <div class="relative">
                                            <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                                            <input type="text" x-ref="walletSearch" x-model="search"
                                                   placeholder="{{ __('Tìm ví điện tử...') }}"
                                                   @keydown.enter.prevent="if(filtered.length === 1) selectWallet(filtered[0])"
                                                   class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-shopee/30 focus:border-shopee placeholder-gray-400">
                                        </div>
                                    </div>

                                    {{-- Danh sách kết quả --}}
                                    <ul class="max-h-48 overflow-y-auto overscroll-contain py-1">
                                        <template x-for="item in filtered" :key="item">
                                            <li @click="selectWallet(item)"
                                                class="px-3.5 py-2.5 text-xs font-semibold cursor-pointer transition-colors flex items-center gap-2"
                                                :class="walletName === item ? 'bg-shopee/5 text-shopee dark:bg-orange-950/30' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/60'">
                                                <i data-lucide="check" class="w-3.5 h-3.5 shrink-0" :class="walletName === item ? 'opacity-100 text-shopee' : 'opacity-0'"></i>
                                                <span x-text="item"></span>
                                            </li>
                                        </template>
                                        {{-- Trạng thái không tìm thấy kết quả --}}
                                        <li x-show="filtered.length === 0" class="px-3.5 py-4 text-xs text-gray-400 dark:text-slate-500 text-center">
                                            {{ __('Không tìm thấy ví điện tử phù hợp') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Số tài khoản nhận tiền --}}
                        <div class="space-y-1.5">
                            <label for="account_number" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1" x-text="method === 'bank' ? '{{ __('Số tài khoản ngân hàng') }}' : '{{ __('Số điện thoại Ví') }}'"></label>
                            <input type="text" 
                                   id="account_number" 
                                   required
                                   x-model="accountNumber"
                                   placeholder="{{ __('Nhập số tài khoản/số điện thoại nhận tiền...') }}"
                                   class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900/50 dark:text-slate-100 font-bold">
                        </div>

                        {{-- Tên chủ tài khoản nhận tiền (Tự động viết hoa không dấu) --}}
                        <div class="space-y-1.5">
                            <label for="account_name" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Họ tên chủ tài khoản') }}</label>
                            <input type="text" 
                                   id="account_name" 
                                   required
                                   x-model="accountName"
                                   @input="accountName = accountName.toUpperCase()"
                                   placeholder="{{ __('VIET HOA KHONG DAU (Ví dụ: NGUYEN VAN A)...') }}"
                                   class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900/50 dark:text-slate-100 uppercase font-bold">
                        </div>

                        {{-- Nhập mã xác minh OTP --}}
                        <div x-show="otpRequired" x-transition x-cloak class="space-y-2">
                            <label for="otp_code" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Mã xác minh OTP') }} <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" 
                                           id="otp_code" 
                                           :required="otpRequired"
                                           x-model="otpCode"
                                           placeholder="{{ __('Nhập mã OTP 6 số...') }}"
                                           class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900/50 dark:text-slate-100 font-bold">
                                </div>
                                <button type="button" 
                                        @click="sendOtp()" 
                                        :disabled="countdown > 0 || {{ config('app.demo') ? 'true' : 'false' }}"
                                        :class="countdown > 0 || {{ config('app.demo') ? 'true' : 'false' }} ? 'bg-gray-150 dark:bg-slate-850 text-gray-400 dark:text-slate-500 border border-gray-200 dark:border-slate-700/50 cursor-not-allowed' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-200 dark:hover:bg-slate-700 hover:text-shopee dark:hover:text-shopee'"
                                        class="px-4 rounded-2xl text-xs font-bold transition-all focus:outline-none cursor-pointer whitespace-nowrap min-w-[120px]"
                                        x-text="otpButtonText">
                                </button>
                            </div>
                            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Mã xác minh OTP được gửi về Email đăng ký tài khoản của bạn.') }}</span>
                        </div>

                        {{-- Captcha Cloudflare Turnstile bảo mật chống spam --}}
                        @if(\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_withdraw', '0') === '1')
                        <div class="my-3">
                            <div class="flex justify-center">
                                <div class="cf-turnstile" data-sitekey="{{ \App\Models\Setting::getVal('turnstile_site_key') }}"></div>
                            </div>
                        </div>
                        @endif

                        <button type="submit" 
                                :disabled="isSubmitting || (otpRequired && !otpCode) || {{ config('app.demo') ? 'true' : 'false' }}"
                                :class="isSubmitting || (otpRequired && !otpCode) || {{ config('app.demo') ? 'true' : 'false' }} ? 'bg-gray-300 text-gray-500 cursor-not-allowed opacity-60' : 'bg-shopee hover:bg-shopee-dark text-white'"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 text-xs font-semibold rounded-2xl transition-all shadow-md shadow-shopee/10 cursor-pointer">
                            <span x-show="isSubmitting" x-cloak class="w-4 h-4 border-2 border-gray-500 border-t-transparent rounded-full animate-spin"></span>
                            <span x-text="isSubmitting ? '{{ __('Đang xử lý...') }}' : '{{ config('app.demo') ? __('Tính năng bị khóa ở chế độ Demo') : __('Gửi yêu cầu rút tiền') }}'"></span>
                        </button>
                    </form>
                    @endif
                </div>

                {{-- Khối thông tin Ví số dư & Quy định trên Desktop (Bên phải) --}}
                <div class="hidden md:block xl:col-span-5 space-y-6">
                    {{-- Ví số dư Desktop --}}
                    <div class="bg-gradient-to-br from-[#1E293B] to-[#0F172A] text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
                        <div class="relative z-10 space-y-4">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">{{ __('Số dư ví hiện tại') }}</span>
                            <p class="text-3xl font-extrabold text-white mt-2">{{ \App\Helpers\CurrencyHelper::format($user->balance) }}</p>
                            
                            <hr class="border-slate-700/50 my-4">
                            
                            <div class="space-y-2 text-xs text-slate-350">
                                <div class="flex justify-between">
                                    <span>{{ __('Đã tích luỹ:') }}</span>
                                    <span class="font-bold text-green-400">{{ \App\Helpers\CurrencyHelper::format($user->total_cashback) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('Đã giải ngân:') }}</span>
                                    <span class="font-bold text-blue-400">{{ \App\Helpers\CurrencyHelper::format($user->total_withdrawn) }}</span>
                                </div>
                            </div>
                        </div>
                        <i data-lucide="wallet" class="w-32 h-32 text-white/[0.03] absolute -right-6 -bottom-6 z-0 pointer-events-none"></i>
                    </div>

                    {{-- Nội dung quy định rút tiền Desktop --}}
                    @if(!empty(trim($withdrawalRules)))
                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800">
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2 mb-3">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-orange-500 shrink-0"></i>
                            {{ __('Quy định rút tiền') }}
                        </h3>
                        <div class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed space-y-2 text-justify">
                            {!! nl2br(e($withdrawalRules)) !!}
                        </div>
                    </div>
                    @endif
                </div>

            </div>

            {{-- 4. Bảng Lịch sử rút tiền trên Desktop --}}
            <div class="hidden md:block bg-white dark:bg-slate-900 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 overflow-hidden mb-6">
                <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-slate-850 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base shrink-0">{{ __('Lịch Sử Rút Tiền') }}</h3>
                    
                    {{-- Form tìm kiếm và lọc trạng thái trên Desktop --}}
                    <form id="withdraw-filter-form" class="flex flex-wrap items-center gap-2 max-w-full lg:max-w-xl w-full lg:w-auto">
                        <div class="relative flex-grow sm:flex-grow-0 sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                            <input type="text" 
                                   id="withdraw-search-input"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('Tìm tên tài khoản, STK, ngân hàng...') }}" 
                                   class="block w-full pl-8 pr-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100">
                        </div>

                        <div class="w-28 sm:w-36 shrink-0">
                            <select id="withdraw-status-select" class="block w-full px-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[11px] sm:text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100 font-medium text-gray-700">
                                <option value="all">{{ __('Tất cả trạng thái') }}</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ duyệt') }}</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Thành công') }}</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Từ chối') }}</option>
                            </select>
                        </div>

                        <button type="submit" class="px-4 py-1.5 shrink-0 inline-flex items-center gap-1.5 text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl text-xs font-bold transition-all shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Tìm kiếm') }}</span>
                        </button>

                        <button type="button" 
                                id="withdraw-reset-filter-btn"
                                class="p-1.5 shrink-0 inline-flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 border border-gray-200 dark:border-slate-800 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 transition-all"
                                style="{{ (request('search') || (request('status') && request('status') !== 'all')) ? 'display: inline-flex;' : 'display: none;' }}"
                                title="{{ __('Xóa bộ lọc') }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                {{-- Vùng nạp dữ liệu lịch sử rút tiền qua AJAX --}}
                <div id="withdrawal-desktop-table-container" class="transition-all duration-300">
                    @include('dashboard.partials.withdrawal_list', ['type' => 'desktop'])
                </div>
            </div>

            {{-- 5. Danh Sách Lịch Sử rút tiền trên Mobile (Chỉ hiển thị ở Tab Lịch sử) --}}
            <div class="block md:hidden space-y-4" x-show="isMobile && activeTab === 'history'" x-cloak>
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] space-y-3">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ __('Lịch Sử Rút Tiền') }}</h3>
                    
                    {{-- Form tìm kiếm và lọc trạng thái trên Mobile --}}
                    <form id="withdraw-filter-form-mobile" class="space-y-2">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                            <input type="text" 
                                   id="withdraw-search-input-mobile"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('Tìm tên tài khoản, STK, ngân hàng...') }}" 
                                   class="block w-full pl-8 pr-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100">
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="flex-grow">
                                <select id="withdraw-status-select-mobile" class="block w-full px-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100 font-medium text-gray-700 font-semibold">
                                    <option value="all">{{ __('Tất cả trạng thái') }}</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ duyệt') }}</option>
                                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Thành công') }}</option>
                                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Từ chối') }}</option>
                                </select>
                            </div>

                            <button type="submit" class="px-4 py-1.5 shrink-0 inline-flex items-center justify-center gap-1.5 text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl text-xs font-bold transition-all shadow-sm">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Tìm') }}</span>
                            </button>

                            <button type="button" 
                                    id="withdraw-reset-filter-btn-mobile"
                                    class="p-1.5 shrink-0 inline-flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 border border-gray-200 dark:border-slate-800 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 transition-all"
                                    style="{{ (request('search') || (request('status') && request('status') !== 'all')) ? 'display: inline-flex;' : 'display: none;' }}"
                                    title="{{ __('Xóa bộ lọc') }}">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Vùng nạp dữ liệu lịch sử rút tiền Mobile qua AJAX --}}
                <div id="withdrawal-mobile-cards-container" class="transition-all duration-300">
                    @include('dashboard.partials.withdrawal_list', ['type' => 'mobile'])
                </div>
            </div>

            {{-- Modal xác nhận xoá tài khoản đã lưu (teleport ra body để backdrop phủ đúng toàn trang) --}}
            @if($savedAccountsEnabled)
            <template x-teleport="body">
                <div x-show="deleteAccountId" x-cloak
                     class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                     x-transition.opacity>
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="deleteAccountId = null"></div>
                    <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-6 border border-gray-100 dark:border-slate-800"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        <div class="w-12 h-12 flex items-center justify-center bg-red-50 dark:bg-red-950/30 text-red-500 rounded-2xl mx-auto mb-4">
                            <i data-lucide="trash-2" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white text-center mb-1.5">{{ __('Xoá tài khoản đã lưu?') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 text-center leading-relaxed mb-1">
                            {{ __('Bạn có chắc muốn xoá tài khoản này khỏi sổ? Thao tác không thể hoàn tác.') }}
                        </p>
                        <p class="text-xs font-bold text-shopee text-center mb-5" x-text="deleteAccountLabel"></p>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="deleteAccountId = null"
                                    class="flex-1 px-4 py-2.5 rounded-2xl text-xs font-bold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition-all cursor-pointer">
                                {{ __('Huỷ bỏ') }}
                            </button>
                            <button type="button" @click="deleteAccount()"
                                    class="flex-1 px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-red-500 hover:bg-red-600 transition-all shadow-md shadow-red-500/20 cursor-pointer">
                                {{ __('Xoá tài khoản') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            @endif

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/withdraw.js') }}"></script>
@endsection

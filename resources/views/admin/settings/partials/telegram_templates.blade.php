{{-- 
    Partial View: Mẫu Telegram
    Vai trò: Quản lý giao diện cấu hình nội dung gửi thông báo Telegram cho các sự kiện của hệ thống (Đăng ký mới, Tạo yêu cầu rút tiền, Duyệt rút tiền, Từ chối rút tiền, Có đơn cashback Shopee, Duyệt đơn cashback).
    Kiến trúc: Sử dụng AlpineJS để chuyển đổi giữa các mẫu tin nhắn và xử lý gửi thử tin nhắn Telegram qua API endpoint bằng Axios.
--}}
<div x-show="tab === 'telegram_templates'" class="space-y-6" x-transition x-cloak x-data="{ 
    activeTelegram: 'new_user',
    showTestModal: false,
    testChatId: '{{ $settings['telegram_chat_id'] ?? '' }}',
    testBotToken: '{{ $settings['telegram_bot_token'] ?? '' }}',
    isSending: false,
    testResult: null,
    
    // Mở modal gửi thử tin nhắn Telegram
    openTest(templateKey) {
        this.testResult = null;
        this.showTestModal = true;
    },
    
    // Thực hiện gửi thử tin nhắn Telegram qua Axios
    async sendTestTelegram() {
        if (!this.testBotToken) {
            alert('Vui lòng cấu hình Telegram Bot Token trước tại tab Cấu hình kết nối.');
            return;
        }

        // Lấy Chat ID nhận thông báo: Ưu tiên Chat ID riêng của template hiện tại, nếu không có mới dùng Chat ID chung
        let currentChatId = this.testChatId;
        const customChatIdInput = document.getElementById('telegram_chat_id_' + this.activeTelegram);
        if (customChatIdInput && customChatIdInput.value.trim() !== '') {
            currentChatId = customChatIdInput.value.trim();
        }
        
        if (!currentChatId) {
            alert('Vui lòng nhập Chat ID nhận thông báo (hoặc cấu hình Chat ID chung).');
            return;
        }
        
        let messageText = '';
        const textarea = document.getElementById('telegram_content_' + this.activeTelegram);
        if (textarea) {
            messageText = textarea.value;
        }
        
        if (!messageText) {
            alert('Nội dung tin nhắn không được trống.');
            return;
        }
        
        // Thay thế các biến động mẫu bằng dữ liệu giả lập để hiển thị trực quan
        const demoData = {
            '{name}': 'Nguyễn Văn A',
            '{email}': 'nguyenvana@gmail.com',
            '{amount}': '150,000',
            '{payment_method}': 'Chuyển khoản ngân hàng',
            '{bank_name}': 'Techcombank',
            '{account_number}': '19036789123456',
            '{account_name}': 'NGUYEN VAN A',
            '{reason}': 'Thông tin tài khoản không trùng khớp',
            '{product_name}': 'Áo Thun Nam Cotton Premium Shopee',
            '{price}': '250,000',
            '{cashback_amount}': '15,000',
            '{commission}': '25,000',
            '{profit}': '10,000',
            '{f1_name}': 'Trần Văn B (F1)',
            '{f1_commission}': '750',
            '{f2_name}': 'Lê Văn C (F2)',
            '{f2_commission}': '300',
            '{gift_title}': 'Thẻ cào Viettel 50k',
            '{gift_price}': '50,000',
            '{site_name}': 'Hoàn Tiền Shopee',
            '{account_name}': 'Shopee Shop A',
            '{username}': 'shopeeshopa',
            '{error_message}': 'Lỗi đồng bộ: Cookie không hợp lệ hoặc hết hạn.',
            '{platform}': 'Shopee',
            '{task_title}': 'Chia sẻ link giới thiệu lên Facebook',
            '{task_type}': 'Một lần',
            '{reward_amount}': '10,000',
            '{note}': 'Link bài đăng: https://facebook.com/post/123',
            '{time}': new Date().toLocaleString('vi-VN')
        };
        
        for (const [key, val] of Object.entries(demoData)) {
            messageText = messageText.replaceAll(key, val);
        }
        
        this.isSending = true;
        this.testResult = null;
        
        try {
            const response = await axios.post('{{ route('admin.settings.test_telegram') }}', {
                bot_token: this.testBotToken,
                chat_id: currentChatId,
                message: messageText
            });
            
            this.testResult = { success: true, message: response.data.message };
        } catch (error) {
            const msg = error.response?.data?.message || 'Không thể kết nối tới Telegram API. Hãy kiểm tra lại Token và Chat ID.';
            this.testResult = { success: false, message: msg };
        } finally {
            this.isSending = false;
        }
    },

    // Bộ nội dung mẫu mặc định cho từng template Telegram (dùng cho nút nhập mẫu mặc định)
    defaultTemplates: {
        new_user: `🔔 <b>[ĐĂNG KÝ TÀI KHOẢN MỚI]</b>
👤 Họ tên: {name}
📧 Email: {email}
📅 Thời gian: {time}`,
        withdrawal_created: `💰 <b>[YÊU CẦU RÚT TIỀN MỚI]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
💳 Phương thức: {payment_method}
🏦 Ngân hàng: {bank_name}
💳 Số tài khoản: {account_number}
👤 Tên tài khoản: {account_name}
📅 Thời gian: {time}`,
        withdrawal_approved: `✅ <b>[DUYỆT YÊU CẦU RÚT TIỀN]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
💳 Phương thức: {payment_method}
🏦 Ngân hàng: {bank_name}
💳 Số tài khoản: {account_number}
👤 Tên tài khoản: {account_name}
🟢 Trạng thái: THÀNH CÔNG
📅 Thời gian: {time}`,
        withdrawal_rejected: `❌ <b>[TỪ CHỐI YÊU CẦU RÚT TIỀN]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
🔴 Trạng thái: TỪ CHỐI
💬 Lý do: {reason}
📅 Thời gian: {time}`,
        cashback_created: `🛍️ <b>[ĐƠN HÀNG CASHBACK CHỜ DUYỆT]</b>
🌐 Nền tảng: {platform}
👤 Thành viên: {name} ({email})
📦 Sản phẩm: {product_name}
💵 Giá trị đơn: {price}đ
💰 Hoàn tiền: {cashback_amount}đ
💸 Hoa hồng sàn: {commission}đ
📊 Lợi nhuận: {profit}đ
📅 Thời gian: {time}`,
        cashback_approved: `🎉 <b>[DUYỆT ĐƠN HÀNG CASHBACK]</b>
🌐 Nền tảng: {platform}
👤 Thành viên: {name} ({email})
📦 Sản phẩm: {product_name}
💵 Giá trị đơn: {price}đ
💰 Hoàn tiền F0: {cashback_amount}đ
💸 Hoa hồng sàn: {commission}đ
📊 Lợi nhuận: {profit}đ
👥 Hoa hồng F1 ({f1_name}): {f1_commission}đ
👥 Hoa hồng F2 ({f2_name}): {f2_commission}đ
📅 Thời gian: {time}`,
        gift_created: `🎁 <b>[YÊU CẦU ĐỔI QUÀ MỚI]</b>
👤 Thành viên: {name} ({email})
🛍️ Quà tặng: {gift_title}
💵 Giá trị: {gift_price}đ
📅 Thời gian: {time}`,
        shopee_cookie_expired: `⚠️ <b>[CẢNH BÁO COOKIE SHOPEE LỖI]</b>
🌐 Hệ thống: {site_name}
👤 Tài khoản: <b>{account_name}</b> ({username})
🔴 Trạng thái: Hết hạn / Lỗi Cookie
💬 Chi tiết: {error_message}
📅 Thời gian: {time}
👉 Vui lòng đăng nhập trang quản trị để cập nhật lại Cookie Shopee.`,
        task_submitted: `📝 <b>[YÊU CẦU XÁC NHẬN NHIỆM VỤ]</b>
👤 Thành viên: {name} ({email})
🎯 Nhiệm vụ: {task_title}
🏷️ Loại: {task_type}
💰 Tiền thưởng: {reward_amount}đ
💬 Ghi chú: {note}
📅 Thời gian: {time}
👉 Vào trang quản trị mục Nhiệm vụ → Tiến độ thành viên để duyệt.`
    },

    // Nhập mẫu mặc định cho template đang chọn, gán nội dung vào textarea
    loadDefault(key) {
        if (!this.defaultTemplates[key]) return;
        if (!confirm('Bạn có chắc muốn nhập mẫu mặc định? Nội dung hiện tại trong ô soạn thảo sẽ bị ghi đè.')) return;
        const textarea = document.getElementById('telegram_content_' + key);
        if (textarea) {
            textarea.value = this.defaultTemplates[key];
        }
    }
}">
    <!-- Hướng dẫn sử dụng Telegram Template -->
    <div class="p-4 bg-orange-50 border border-orange-100 text-xs text-orange-800 rounded-2xl dark:bg-orange-950/30 dark:border-orange-800/50 dark:text-orange-300 space-y-2">
        <h4 class="font-bold flex items-center gap-1.5"><i data-lucide="info" class="w-4.5 h-4.5"></i> Hướng dẫn cấu hình Telegram Template:</h4>
        <p class="leading-relaxed">Telegram hỗ trợ định dạng HTML thô. Bạn có thể sử dụng các thẻ sau để làm nổi bật tin nhắn:
            <code>&lt;b&gt;Chữ in đậm&lt;/b&gt;</code>, 
            <code>&lt;i&gt;Chữ in nghiêng&lt;/i&gt;</code>, 
            <code>&lt;code&gt;Đoạn mã code&lt;/code&gt;</code>, 
            <code>&lt;a href=&quot;url&quot;&gt;Liên kết&lt;/a&gt;</code>.
        </p>
        <p class="leading-relaxed">Sử dụng các thẻ biến động dạng <code>{tên_biến}</code> để tự động điền dữ liệu thực tế khi có sự kiện kích hoạt.</p>
    </div>

    <!-- Chọn Template bằng các nút Tab -->
    <div class="flex flex-wrap gap-2 border-b border-gray-150 pb-3">
        <button type="button" @click="activeTelegram = 'new_user'"
            :class="activeTelegram === 'new_user' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Đăng ký mới
        </button>
        <button type="button" @click="activeTelegram = 'withdrawal_created'"
            :class="activeTelegram === 'withdrawal_created' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Yêu cầu rút tiền
        </button>
        <button type="button" @click="activeTelegram = 'withdrawal_approved'"
            :class="activeTelegram === 'withdrawal_approved' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Duyệt rút tiền
        </button>
        <button type="button" @click="activeTelegram = 'withdrawal_rejected'"
            :class="activeTelegram === 'withdrawal_rejected' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Từ chối rút tiền
        </button>
        <button type="button" @click="activeTelegram = 'cashback_created'"
            :class="activeTelegram === 'cashback_created' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Đơn cashback mới
        </button>
        <button type="button" @click="activeTelegram = 'cashback_approved'"
            :class="activeTelegram === 'cashback_approved' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Duyệt đơn cashback
        </button>
        <button type="button" @click="activeTelegram = 'gift_created'"
            :class="activeTelegram === 'gift_created' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Yêu cầu đổi quà
        </button>
        <button type="button" @click="activeTelegram = 'shopee_cookie_expired'"
            :class="activeTelegram === 'shopee_cookie_expired' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Lỗi Cookie Shopee
        </button>
        <button type="button" @click="activeTelegram = 'task_submitted'"
            :class="activeTelegram === 'task_submitted' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
            Xác nhận nhiệm vụ
        </button>
    </div>

    <!-- 1. Template: Đăng ký tài khoản mới -->
    <div x-show="activeTelegram === 'new_user'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email đăng ký</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian đăng ký</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_new_user" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_new_user" id="telegram_status_new_user" value="1" {{ ($settings['telegram_status_new_user'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_new_user" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_new_user" id="telegram_chat_id_new_user" value="{{ $settings['telegram_chat_id_new_user'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_new_user" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('new_user')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('new_user')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_new_user"
                id="telegram_content_new_user"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_new_user'] ?? "🔔 <b>[ĐĂNG KÝ TÀI KHOẢN MỚI]</b>
👤 Họ tên: {name}
📧 Email: {email}
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>

    <!-- 2. Template: Tạo yêu cầu rút tiền -->
    <div x-show="activeTelegram === 'withdrawal_created'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{amount}</code> - Số tiền rút</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{payment_method}</code> - Phương thức nhận</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{bank_name}</code> - Tên ngân hàng</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{account_number}</code> - Số tài khoản/SĐT ví</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{account_name}</code> - Tên chủ tài khoản</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian yêu cầu</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_withdrawal_created" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_withdrawal_created" id="telegram_status_withdrawal_created" value="1" {{ ($settings['telegram_status_withdrawal_created'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_withdrawal_created" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_withdrawal_created" id="telegram_chat_id_withdrawal_created" value="{{ $settings['telegram_chat_id_withdrawal_created'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_withdrawal_created" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('withdrawal_created')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('withdrawal_created')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_withdrawal_created"
                id="telegram_content_withdrawal_created"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_withdrawal_created'] ?? "💰 <b>[YÊU CẦU RÚT TIỀN MỚI]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
💳 Phương thức: {payment_method}
🏦 Ngân hàng: {bank_name}
💳 Số tài khoản: {account_number}
👤 Tên tài khoản: {account_name}
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>

    <!-- 3. Template: Duyệt rút tiền -->
    <div x-show="activeTelegram === 'withdrawal_approved'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{amount}</code> - Số tiền rút</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{payment_method}</code> - Phương thức nhận</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{bank_name}</code> - Tên ngân hàng</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{account_number}</code> - Số tài khoản/SĐT ví</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{account_name}</code> - Tên chủ tài khoản</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian duyệt</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_withdrawal_approved" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_withdrawal_approved" id="telegram_status_withdrawal_approved" value="1" {{ ($settings['telegram_status_withdrawal_approved'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_withdrawal_approved" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_withdrawal_approved" id="telegram_chat_id_withdrawal_approved" value="{{ $settings['telegram_chat_id_withdrawal_approved'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_withdrawal_approved" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('withdrawal_approved')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('withdrawal_approved')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_withdrawal_approved"
                id="telegram_content_withdrawal_approved"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_withdrawal_approved'] ?? "✅ <b>[DUYỆT YÊU CẦU RÚT TIỀN]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
💳 Phương thức: {payment_method}
🏦 Ngân hàng: {bank_name}
💳 Số tài khoản: {account_number}
👤 Tên tài khoản: {account_name}
🟢 Trạng thái: THÀNH CÔNG
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>

    <!-- 4. Template: Từ chối rút tiền -->
    <div x-show="activeTelegram === 'withdrawal_rejected'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{amount}</code> - Số tiền yêu cầu</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{reason}</code> - Lý do từ chối</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian từ chối</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_withdrawal_rejected" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_withdrawal_rejected" id="telegram_status_withdrawal_rejected" value="1" {{ ($settings['telegram_status_withdrawal_rejected'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_withdrawal_rejected" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_withdrawal_rejected" id="telegram_chat_id_withdrawal_rejected" value="{{ $settings['telegram_chat_id_withdrawal_rejected'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_withdrawal_rejected" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('withdrawal_rejected')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('withdrawal_rejected')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_withdrawal_rejected"
                id="telegram_content_withdrawal_rejected"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_withdrawal_rejected'] ?? "❌ <b>[TỪ CHỐI YÊU CẦU RÚT TIỀN]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
🔴 Trạng thái: TỪ CHỐI
💬 Lý do: {reason}
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>

    <!-- 5. Template: Đơn hàng cashback mới -->
    <div x-show="activeTelegram === 'cashback_created'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{platform}</code> - Nền tảng (Shopee/TikTok Shop)</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{product_name}</code> - Tên sản phẩm</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{price}</code> - Giá trị sản phẩm</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{cashback_amount}</code> - Tiền hoàn lại dự kiến</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{commission}</code> - Hoa hồng sàn trả</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{profit}</code> - Lợi nhuận hệ thống</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian phát sinh đơn</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_cashback_created" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_cashback_created" id="telegram_status_cashback_created" value="1" {{ ($settings['telegram_status_cashback_created'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_cashback_created" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_cashback_created" id="telegram_chat_id_cashback_created" value="{{ $settings['telegram_chat_id_cashback_created'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_cashback_created" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('cashback_created')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('cashback_created')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_cashback_created"
                id="telegram_content_cashback_created"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_cashback_created'] ?? "🛍️ <b>[ĐƠN HÀNG CASHBACK CHỜ DUYỆT]</b>
🌐 Nền tảng: {platform}
👤 Thành viên: {name} ({email})
📦 Sản phẩm: {product_name}
💵 Giá trị đơn: {price}đ
💰 Hoàn tiền: {cashback_amount}đ
💸 Hoa hồng sàn: {commission}đ
📊 Lợi nhuận: {profit}đ
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>

    <!-- 6. Template: Duyệt đơn cashback -->
    <div x-show="activeTelegram === 'cashback_approved'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{platform}</code> - Nền tảng (Shopee/TikTok Shop)</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{product_name}</code> - Tên sản phẩm</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{price}</code> - Giá trị sản phẩm</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{cashback_amount}</code> - Tiền hoàn thực nhận (F0)</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{commission}</code> - Hoa hồng sàn trả</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{profit}</code> - Lợi nhuận hệ thống</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{f1_name}</code> - Tên người giới thiệu F1</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{f1_commission}</code> - Hoa hồng F1 nhận</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{f2_name}</code> - Tên người giới thiệu F2</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{f2_commission}</code> - Hoa hồng F2 nhận</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian duyệt</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_cashback_approved" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_cashback_approved" id="telegram_status_cashback_approved" value="1" {{ ($settings['telegram_status_cashback_approved'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_cashback_approved" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_cashback_approved" id="telegram_chat_id_cashback_approved" value="{{ $settings['telegram_chat_id_cashback_approved'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_cashback_approved" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('cashback_approved')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('cashback_approved')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_cashback_approved"
                id="telegram_content_cashback_approved"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_cashback_approved'] ?? "🎉 <b>[DUYỆT ĐƠN HÀNG CASHBACK]</b>
🌐 Nền tảng: {platform}
👤 Thành viên: {name} ({email})
📦 Sản phẩm: {product_name}
💵 Giá trị đơn: {price}đ
💰 Hoàn tiền F0: {cashback_amount}đ
💸 Hoa hồng sàn: {commission}đ
📊 Lợi nhuận: {profit}đ
👥 Hoa hồng F1 ({f1_name}): {f1_commission}đ
👥 Hoa hồng F2 ({f2_name}): {f2_commission}đ
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>

    <!-- 7. Template: Yêu cầu đổi quà -->
    <div x-show="activeTelegram === 'gift_created'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{gift_title}</code> - Tên quà tặng</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{gift_price}</code> - Giá trị quy đổi</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian yêu cầu</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_gift_created" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_gift_created" id="telegram_status_gift_created" value="1" {{ ($settings['telegram_status_gift_created'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_gift_created" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_gift_created" id="telegram_chat_id_gift_created" value="{{ $settings['telegram_chat_id_gift_created'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_gift_created" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('gift_created')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('gift_created')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_gift_created"
                id="telegram_content_gift_created"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_gift_created'] ?? "🎁 <b>[YÊU CẦU ĐỔI QUÀ MỚI]</b>
👤 Thành viên: {name} ({email})
🛍️ Quà tặng: {gift_title}
💵 Giá trị: {gift_price}đ
📅 Thời gian: {time}" }}</textarea>
        </div>
    </div>
    
    <!-- 8. Template: Lỗi Cookie Shopee -->
    <div x-show="activeTelegram === 'shopee_cookie_expired'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{site_name}</code> - Tên website/hệ thống</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{account_name}</code> - Tên tài khoản Shopee</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{username}</code> - Username Shopee</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{error_message}</code> - Chi tiết lỗi</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian phát sinh</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_shopee_cookie_expired" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_shopee_cookie_expired" id="telegram_status_shopee_cookie_expired" value="1" {{ ($settings['telegram_status_shopee_cookie_expired'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_shopee_cookie_expired" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_shopee_cookie_expired" id="telegram_chat_id_shopee_cookie_expired" value="{{ $settings['telegram_chat_id_shopee_cookie_expired'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-250 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_shopee_cookie_expired" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('shopee_cookie_expired')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('shopee_cookie_expired')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_shopee_cookie_expired"
                id="telegram_content_shopee_cookie_expired"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_shopee_cookie_expired'] ?? "⚠️ <b>[CẢNH BÁO COOKIE SHOPEE LỖI]</b>
🌐 Hệ thống: {site_name}
👤 Tài khoản: <b>{account_name}</b> ({username})
🔴 Trạng thái: Hết hạn / Lỗi Cookie
💬 Chi tiết: {error_message}
📅 Thời gian: {time}
👉 Vui lòng đăng nhập trang quản trị để cập nhật lại Cookie Shopee." }}</textarea>
        </div>
    </div>

    <!-- 9. Template: Thành viên gửi yêu cầu xác nhận hoàn thành nhiệm vụ thủ công -->
    <div x-show="activeTelegram === 'task_submitted'" class="space-y-4" x-transition>
        <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{name}</code> - Tên thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{email}</code> - Email thành viên</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{task_title}</code> - Tên nhiệm vụ</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{task_type}</code> - Loại nhiệm vụ</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{reward_amount}</code> - Tiền thưởng</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{note}</code> - Ghi chú/bằng chứng thành viên gửi</span>
                <span class="px-2 py-1 bg-white border border-gray-250 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-[10px] text-gray-600 dark:text-gray-400 font-mono"><code>{time}</code> - Thời gian gửi yêu cầu</span>
            </div>
        </div>

        <!-- Cấu hình ON/OFF và Chat ID riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center h-full pt-4">
                <input type="hidden" name="telegram_status_task_submitted" value="0">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="telegram_status_task_submitted" id="telegram_status_task_submitted" value="1" {{ ($settings['telegram_status_task_submitted'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                    <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">Bật gửi thông báo</span>
                </label>
            </div>
            <div>
                <label for="telegram_chat_id_task_submitted" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1.5">Chat ID riêng (Bỏ trống để dùng Chat ID chung)</label>
                <input type="text" name="telegram_chat_id_task_submitted" id="telegram_chat_id_task_submitted" value="{{ $settings['telegram_chat_id_task_submitted'] ?? '' }}" placeholder="Dùng Chat ID chung nếu bỏ trống..." class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="telegram_content_task_submitted" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">Nội dung thông báo (HTML)</label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadDefault('task_submitted')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Nhập mẫu mặc định
                    </button>
                    <button type="button" @click="openTest('task_submitted')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Gửi thử
                    </button>
                </div>
            </div>
            <textarea name="telegram_template_task_submitted"
                id="telegram_content_task_submitted"
                rows="8"
                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-850 dark:text-white font-mono leading-relaxed">{{ $settings['telegram_template_task_submitted'] ?? "📝 <b>[YÊU CẦU XÁC NHẬN NHIỆM VỤ]</b>
👤 Thành viên: {name} ({email})
🎯 Nhiệm vụ: {task_title}
🏷️ Loại: {task_type}
💰 Tiền thưởng: {reward_amount}đ
💬 Ghi chú: {note}
📅 Thời gian: {time}
👉 Vào trang quản trị mục Nhiệm vụ → Tiến độ thành viên để duyệt." }}</textarea>
        </div>
    </div>

    <!-- Modal Gửi Thử Telegram Bot -->
    <template x-teleport="body">
        <div x-show="showTestModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
            x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showTestModal = false"></div>

            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-md mx-4 overflow-hidden z-10 transition-all transform scale-100">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="send" class="w-4.5 h-4.5 text-emerald-500"></i>
                        Gửi thử tin nhắn Telegram
                    </h3>
                    <button type="button" @click="showTestModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="w-4.5 h-4.5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <p class="text-xs text-gray-500 leading-relaxed dark:text-gray-400">
                        Bot Telegram sẽ gửi nội dung template đang chọn (với các biến được thay thế bằng dữ liệu mẫu thử nghiệm) về Chat ID dưới đây.
                    </p>
                    
                    <div>
                        <label for="test_telegram_bot_token" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">Telegram Bot Token</label>
                        <input type="text"
                            id="test_telegram_bot_token"
                            x-model="testBotToken"
                            placeholder="Nhập Bot Token..."
                            class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="test_telegram_chat_id" class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">Telegram Chat ID nhận thông báo</label>
                        <input type="text"
                            id="test_telegram_chat_id"
                            x-model="testChatId"
                            placeholder="Nhập Chat ID..."
                            class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
                    </div>

                    <!-- Kết quả kiểm tra -->
                    <div x-show="testResult !== null" x-cloak class="text-xs p-3 rounded-xl border transition-all" :class="testResult && testResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-850 dark:text-emerald-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-850 dark:text-red-400'">
                        <div class="flex items-start gap-2">
                            <template x-if="testResult && testResult.success">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5 animate-bounce"></i>
                            </template>
                            <template x-if="testResult && !testResult.success">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
                            </template>
                            <span class="font-medium" x-text="testResult ? testResult.message : ''"></span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3 bg-gray-50/50 dark:bg-slate-900/50">
                    <button type="button" @click="showTestModal = false" :disabled="isSending" class="px-4 py-2 border border-gray-250 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-xs font-bold rounded-xl transition-all disabled:opacity-50">
                        Hủy
                    </button>
                    <button type="button"
                        @click="sendTestTelegram()"
                        :disabled="isSending || !testChatId || !testBotToken"
                        class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 disabled:opacity-50">
                        <template x-if="isSending">
                            <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        <template x-if="!isSending">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </template>
                        <span x-text="isSending ? 'Đang gửi...' : 'Gửi Thử'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

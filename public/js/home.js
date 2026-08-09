/**
 * Xử lý sự kiện dán link Shopee và lấy thông tin sản phẩm.
 * Sử dụng AlpineJS để quản lý trạng thái tải dữ liệu, thông tin sản phẩm và hiển thị thông báo.
 * @param {Object} config - Cấu hình truyền từ Blade view (endpoint URL và các câu thông báo dịch)
 */
function shopeeCashbackHandler(config) {
    return {
        url: '',
        loading: false,
        product: null,
        logged_in: config.loggedIn || false, // Trạng thái đăng nhập đồng bộ từ backend
        loggedIn: config.loggedIn || false,
        loginUrl: config.loginUrl || '/login',
        copied: false,
        showQr: false,
        endpoint: config.endpoint || '',
        errorMessage: config.errorMessage || 'Không thể tải thông tin sản phẩm. Vui lòng kiểm tra lại link.',
        currency: config.currency || { code: 'VND', symbol: '₫', rate: 1.0, position: 'after' },
        savingLater: false, // Trạng thái đang lưu sản phẩm
        savedLater: false, // Trạng thái sản phẩm đã được lưu

        // Hàm khởi tạo của AlpineJS (chạy tự động khi component được mount)
        // Dùng để kiểm tra các tham số truyền từ Share Target PWA di động (Share Sheet)
        init() {
            // Đọc các giá trị truyền từ Share Target khi người dùng nhấn chia sẻ từ ứng dụng khác (Shopee, TikTok, Safari...)
            const urlParams = new URLSearchParams(window.location.search);
            const sharedTitle = urlParams.get('title');
            const sharedText = urlParams.get('text');
            const sharedUrl = urlParams.get('url');

            let contentToExtract = '';
            if (sharedUrl) contentToExtract += ' ' + sharedUrl;
            if (sharedText) contentToExtract += ' ' + sharedText;
            if (sharedTitle) contentToExtract += ' ' + sharedTitle;

            // Nếu phát hiện có nội dung chia sẻ, tiến hành trích xuất URL sản phẩm
            if (contentToExtract.trim()) {
                const extractedUrl = this.extractUrlFromString(contentToExtract);
                if (extractedUrl) {
                    this.url = extractedUrl;
                    
                    // Làm sạch URL trên thanh địa chỉ của trình duyệt ngay lập tức (Xóa các query parameters của Share Target)
                    // Điều này nhằm tránh việc khi người dùng F5/reload trang thì hệ thống lại tiếp tục gọi API phân tích lại link cũ.
                    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({ path: cleanUrl }, '', cleanUrl);

                    // Quy tắc nghiệp vụ: Tự động chạy phân tích link hoàn tiền nếu tài khoản đã đăng nhập
                    if (this.loggedIn) {
                        // Trì hoãn nhẹ 500ms để đảm bảo các thành phần AlpineJS và DOM được dựng hoàn tất trước khi gọi API
                        setTimeout(() => {
                            this.fetchProductInfo();
                        }, 500);
                    } else {
                        // Nếu chưa đăng nhập, hiển thị thông báo yêu cầu đăng nhập
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { text: 'Vui lòng đăng nhập để tự động phân tích liên kết vừa chia sẻ!', type: 'info' } 
                        }));
                    }
                }
            }
        },

        // Trích xuất link Shopee hoặc TikTok Shop hợp lệ từ chuỗi văn bản nhận được từ thiết bị di động
        extractUrlFromString(text) {
            if (!text) return null;
            
            // Tìm tất cả các chuỗi bắt đầu bằng http:// hoặc https://
            const urlRegex = /(https?:\/\/[^\s]+)/gi;
            const matches = text.match(urlRegex);
            
            if (matches) {
                for (let matchedUrl of matches) {
                    // Loại bỏ các ký tự dấu câu/ký tự đặc biệt thừa ở cuối URL do người dùng ghi kèm khi chia sẻ
                    matchedUrl = matchedUrl.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g, function(match, offset, string) {
                        return offset === string.length - 1 ? '' : match;
                    });
                    
                    const lowerUrl = matchedUrl.toLowerCase();
                    // Chỉ xử lý các link thuộc sàn thương mại điện tử Shopee hoặc TikTok Shop
                    if (lowerUrl.includes('shopee.') || 
                        lowerUrl.includes('shope.ee') || 
                        lowerUrl.includes('shp.ee') || 
                        lowerUrl.includes('tiktok.com') || 
                        lowerUrl.includes('tiktok.shop')) {
                        return matchedUrl;
                    }
                }
            }
            return null;
        },

        // Sao chép liên kết vào bộ nhớ tạm (Clipboard)
        copyToClipboard(text) {
            if (!navigator.clipboard) {
                // Phương pháp fallback dành cho các trình duyệt cũ hoặc môi trường không bảo mật
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    this.triggerCopySuccess();
                } catch (err) {
                    console.error('Lỗi khi sao chép liên kết: ', err);
                }
                document.body.removeChild(textArea);
                return;
            }
            // Sử dụng API Clipboard chính thức của trình duyệt hiện đại
            navigator.clipboard.writeText(text).then(() => {
                this.triggerCopySuccess();
            }).catch(err => {
                console.error('Lỗi khi sao chép liên kết: ', err);
            });
        },

        // Kích hoạt trạng thái sao chép thành công và hiển thị Toast thông báo
        triggerCopySuccess() {
            this.copied = true;
            
            // Kích hoạt Toast thông báo thành công (Toast Global Event)
            window.dispatchEvent(new CustomEvent('toast', { 
                detail: { text: 'Đã sao chép liên kết thành công!', type: 'success' } 
            }));

            // Reset lại trạng thái nút copy sau 2 giây
            setTimeout(() => {
                this.copied = false;
            }, 2000);
        },

        // Gửi yêu cầu lấy dữ liệu sản phẩm Shopee qua API
        fetchProductInfo() {
            // Kiểm tra trạng thái đăng nhập của người dùng.
            // Quy tắc nghiệp vụ: Chỉ cho phép người dùng đã đăng nhập lấy link hoàn tiền.
            // Nếu người dùng chưa đăng nhập, chuyển hướng ngay lập tức đến trang login.
            if (!this.loggedIn) {
                window.location.href = this.loginUrl;
                return;
            }

            if (!this.url) return;
            
            this.loading = true;
            this.product = null;
            this.showQr = false; // Reset lại trạng thái hiển thị mã QR khi tìm sản phẩm mới
            this.savedLater = false; // Reset trạng thái sản phẩm đã lưu
            this.savingLater = false;

            axios.post(this.endpoint, {
                url: this.url
            })
            .then(response => {
                if (response.data.status === 'success') {
                    this.product = response.data.data;
                    this.logged_in = response.data.logged_in;
                    
                    // Phát sự kiện toàn cục để kích hoạt toast thông báo thành công
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: response.data.message, type: 'success' } 
                    }));

                    // Phát sự kiện toàn cục thông báo link mới vừa được tạo để danh sách link tự động nạp lại không cần reload trang
                    window.dispatchEvent(new CustomEvent('link-created', { 
                        detail: response.data.data 
                    }));

                    // Tự động cuộn màn hình mượt mà xuống khu vực hiển thị kết quả sản phẩm.
                    // Việc này giúp tối ưu trải nghiệm người dùng, giúp họ thấy ngay thông tin cashback
                    // và link rút gọn vừa được tạo mà không cần phải tự cuộn trang thủ công.
                    setTimeout(() => {
                        const resultEl = document.getElementById('product-result-card');
                        if (resultEl) {
                            resultEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                }
            })
            .catch(error => {
                // Xử lý lỗi từ server trả về.
                // Nếu phản hồi lỗi từ server là 401 (chưa đăng nhập), chuyển hướng đến trang login.
                // Đây là lớp bảo vệ dự phòng bổ sung độ an toàn bảo mật.
                if (error.response && error.response.status === 401) {
                    window.location.href = this.loginUrl;
                    return;
                }

                const msg = error.response && error.response.data.message 
                    ? error.response.data.message 
                    : this.errorMessage;
                
                // Phát sự kiện toàn cục để kích hoạt toast thông báo lỗi
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: msg, type: 'error' } 
                }));
            })
            .finally(() => {
                this.loading = false;
                
                // Trì hoãn một khoảng thời gian ngắn để AlpineJS vẽ lại DOM rồi mới render lại các Icon Lucide mới xuất hiện
                setTimeout(() => {
                    if (window.lucide) window.lucide.createIcons();
                }, 50);
            });
        },

        // Tải ảnh QR Code về máy bằng cách fetch Blob ở Client-side (Tránh CORS và nghẽn mạng DDEV/Docker)
        downloadQrCode(text) {
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(text);
            
            fetch(qrUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Không thể tải tệp hình ảnh từ QRServer');
                    return response.blob();
                })
                .then(blob => {
                    // Tạo một đường dẫn Blob local của trình duyệt và mô phỏng sự kiện click tải xuống
                    const blobUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = 'qrcode-shopee-cashback.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                })
                .catch(error => {
                    console.error('Lỗi khi tải mã QR ở client-side:', error);
                    // Phương án dự phòng (Fallback): Mở ảnh trực tiếp ở tab mới nếu bị lỗi mạng/CORS
                    window.open(qrUrl, '_blank');
                });
        },

        // Định dạng số tiền sang chuẩn ngoại tệ hiện tại (đã quy đổi từ VND)
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

        // Đọc nội dung từ bộ nhớ tạm (Clipboard) và tự động dán vào trường input url, sau đó tự động phân tích link
        async pasteClipboard() {
            try {
                if (!navigator.clipboard || !navigator.clipboard.readText) {
                    // Hiển thị thông báo nếu trình duyệt không hỗ trợ hoặc chưa cấp quyền truy cập bộ nhớ tạm
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: 'Trình duyệt không hỗ trợ hoặc chưa cấp quyền đọc bộ nhớ tạm!', type: 'error' } 
                    }));
                    return;
                }
                const text = await navigator.clipboard.readText();
                if (text) {
                    this.url = text.trim();
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: 'Đã dán liên kết từ bộ nhớ tạm!', type: 'success' } 
                    }));
                    // Tự động kích hoạt phân tích sản phẩm và lấy link hoàn tiền ngay sau khi dán từ bộ nhớ tạm
                    this.fetchProductInfo();
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: 'Bộ nhớ tạm đang trống!', type: 'warning' } 
                    }));
                }
            } catch (err) {
                console.error('Lỗi khi đọc bộ nhớ tạm: ', err);
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: 'Không thể truy cập bộ nhớ tạm. Hãy cấp quyền cho trang web!', type: 'error' } 
                }));
            }
        },

        // Gửi yêu cầu lưu sản phẩm để mua sau (AJAX POST)
        saveProductForLater() {
            // Nếu chưa đăng nhập, chuyển hướng sang trang đăng nhập
            if (!this.loggedIn) {
                window.location.href = this.loginUrl;
                return;
            }

            if (!this.product) return;

            this.savingLater = true;

            axios.post('/dashboard/saved-products', {
                platform: this.product.platform || 'shopee',
                name: this.product.name,
                image: this.product.image,
                price: this.product.price,
                cashback_amount: this.product.cashback_amount,
                affiliate_url: this.product.affiliate_url,
                product_url: this.product.url || this.url // Link gốc Shopee
            })
            .then(response => {
                if (response.data.success) {
                    this.savedLater = true;
                    // Phát sự kiện toàn cục để kích hoạt toast thông báo thành công
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: response.data.message, type: 'success' } 
                    }));
                } else {
                    // Sản phẩm đã có trong danh sách lưu trữ của bạn
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: response.data.message, type: 'warning' } 
                    }));
                    if (response.data.message.indexOf('đã có') !== -1 || response.data.message.indexOf('đã được lưu') !== -1) {
                        this.savedLater = true;
                    }
                }
            })
            .catch(error => {
                const msg = error.response && error.response.data.message 
                    ? error.response.data.message 
                    : 'Không thể lưu sản phẩm. Vui lòng thử lại sau.';
                // Phát sự kiện toàn cục để kích hoạt toast thông báo lỗi
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: msg, type: 'error' } 
                }));
            })
            .finally(() => {
                this.savingLater = false;
            });
        }
    }
}

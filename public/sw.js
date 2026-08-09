// Đăng ký các sự kiện cơ bản của Service Worker để đáp ứng tiêu chuẩn cài đặt ứng dụng (PWA)
// và cho phép hệ thống gọi Share Target chia sẻ dữ liệu.

self.addEventListener('install', (event) => {
    // Kích hoạt Service Worker mới ngay lập tức sau khi tải xuống mà không bắt người dùng tải lại trang
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Đảm bảo Service Worker mới kiểm soát các trang hiện tại ngay lập tức
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Chỉ xử lý các yêu cầu HTTP/HTTPS.
    // Lý do: Các trình duyệt hoặc tiện ích mở rộng (chrome-extension://, v.v.) có thể gửi yêu cầu nội bộ
    // mà Service Worker không hỗ trợ fetch, gây ra lỗi "Failed to fetch" nghiêm trọng trong console.
    if (!event.request.url.startsWith('http')) {
        return;
    }

    // BẮT BUỘC: Chỉ xử lý request GET. Bỏ qua hoàn toàn POST, PUT, DELETE, PATCH...
    // Lý do: Nếu Service Worker gọi fetch(event.request) trên request POST (đăng ký, đăng nhập, submit form),
    // trình duyệt sẽ gửi request gốc VÀ Service Worker cũng gửi thêm 1 bản sao,
    // dẫn tới server nhận được 2 request POST giống hệt nhau, gây lỗi trùng lặp dữ liệu (Duplicate Entry).
    if (event.request.method !== 'GET') {
        return;
    }

    // Chuyển trực tiếp mọi yêu cầu GET qua phương thức fetch gốc để tránh cache nhầm dữ liệu động,
    // đảm bảo cơ chế bảo mật CSRF và Session của Laravel hoạt động ổn định 100%.
    event.respondWith(
        fetch(event.request).catch((error) => {
            // Bắt lỗi khi không có kết nối mạng hoặc yêu cầu bị hủy bỏ.
            // Lý do: Tránh gây ra lỗi Uncaught (in promise) làm gián đoạn trải nghiệm người dùng hoặc làm đỏ console.
            console.warn('[Service Worker] Fetch failed:', error);
            
            // Trả về một Response rỗng với trạng thái lỗi kết nối hợp lệ
            return new Response('Mất kết nối mạng hoặc yêu cầu bị huỷ.', {
                status: 503,
                statusText: 'Service Unavailable',
                headers: new Headers({ 'Content-Type': 'text/plain; charset=utf-8' })
            });
        })
    );
});

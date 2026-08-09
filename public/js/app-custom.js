/**
 * Trình tự cấu hình và xử lý các sự kiện giao diện hệ thống toàn cục.
 * Quản lý thanh tải trang NProgress, tự động render Lucide icons khi DOM thay đổi,
 * thực hiện prefetch link nội bộ và hiệu ứng chuyển trang mượt mà (Next.js transition style).
 */

// Lắng nghe sự kiện thay đổi DOM để tự động vẽ lại Lucide Icons (Tránh chớp giật giao diện)
if (typeof lucide !== 'undefined') {
    let renderTimeout;
    const observer = new MutationObserver(() => {
        cancelAnimationFrame(renderTimeout);
        renderTimeout = requestAnimationFrame(() => {
            lucide.createIcons();
        });
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
}

document.addEventListener('DOMContentLoaded', () => {
    // Vẽ icon ngay lần đầu khi DOM sẵn sàng tải
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    /**
     * Tải trước (Prefetch) các tệp HTML của đường dẫn nội bộ để tăng tốc chuyển trang tức thời
     * @param {string} url - Đường dẫn cần prefetch
     */
    const prefetchLink = (url) => {
        if (document.querySelector(`link[href="${url}"]`)) return;
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    };

    let hoverTimer;

    // Lắng nghe sự kiện hover chuột để tự động prefetch link sau 65ms hover
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a');
        if (link && link.href) {
            const hrefAttr = link.getAttribute('href');
            if (hrefAttr &&
                link.hostname === window.location.hostname &&
                !hrefAttr.startsWith('#') &&
                !hrefAttr.startsWith('javascript:') &&
                link.target !== '_blank' &&
                !link.hasAttribute('download') &&
                !link.href.includes('/logout')
            ) {
                hoverTimer = setTimeout(() => prefetchLink(link.href), 65);
            }
        }
    });

    // Hủy bỏ prefetch nếu người dùng di chuột ra ngoài nhanh chóng
    document.addEventListener('mouseout', () => {
        if (hoverTimer) clearTimeout(hoverTimer);
    });

    // Lắng nghe sự kiện chạm màn hình (touchstart) để prefetch tức thời trên mobile
    document.addEventListener('touchstart', (e) => {
        const link = e.target.closest('a');
        if (link && link.href) {
            const hrefAttr = link.getAttribute('href');
            if (hrefAttr &&
                link.hostname === window.location.hostname &&
                !hrefAttr.startsWith('#') &&
                !hrefAttr.startsWith('javascript:') &&
                link.target !== '_blank' &&
                !link.hasAttribute('download') &&
                !link.href.includes('/logout')
            ) {
                prefetchLink(link.href);
            }
        }
    }, {
        passive: true
    });


});

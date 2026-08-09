/**
 * Admin Layout - JavaScript chính cho giao diện quản trị
 * 
 * Tách riêng khỏi file Blade để:
 * - Tránh IDE formatter phá cú pháp Blade/PHP (ví dụ: $errors->any() bị biến thành $errors - > any())
 * - Được trình duyệt cache riêng, giảm tải lượng HTML mỗi lần load trang
 * - Code JS sạch sẽ, dễ bảo trì, dễ debug hơn
 */

// ======================================================================
// 1. LUCIDE ICONS - Tự động render icon ngay khi phần tử xuất hiện trong DOM
// ======================================================================
// Nghiệp vụ: Sử dụng MutationObserver để theo dõi thay đổi DOM và tự động
// render Lucide icons, tránh hiện tượng icon chớp giật (FOUC) khi trang load.
if (typeof lucide !== 'undefined') {
    let renderTimeout;
    const observer = new MutationObserver(() => {
        cancelAnimationFrame(renderTimeout);
        renderTimeout = requestAnimationFrame(() => {
            lucide.createIcons();
        });
    });
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
}

// ======================================================================
// ======================================================================
// 3. KHỐI LOGIC CHÍNH - Chạy khi DOM đã sẵn sàng
// ======================================================================
document.addEventListener('DOMContentLoaded', () => {
    // Render toàn bộ Lucide icons có sẵn trong DOM lần đầu
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // ------------------------------------------------------------------
    // 3a. PREFETCH LINKS - Tải trước liên kết khi hover/touch để tăng tốc chuyển trang
    // ------------------------------------------------------------------
    // Nghiệp vụ: Khi người dùng di chuột qua link nội bộ, tự động tạo thẻ <link rel="prefetch">
    // để trình duyệt tải trước tài nguyên, giúp trang tiếp theo load gần như tức thì.
    const prefetchLink = (url) => {
        if (document.querySelector(`link[href="${url}"]`)) return;
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    };

    /**
     * Kiểm tra xem link có phải là liên kết nội bộ hợp lệ để prefetch/transition hay không.
     * Loại trừ: anchor links (#), javascript:, external links, _blank, download, logout
     */
    const isInternalLink = (link, hrefAttr) => {
        // Loại trừ các liên kết nội bộ là link tải file (chứa /export hoặc -export) 
        // hoặc các link được đánh dấu class 'no-loader', thuộc tính 'no-loader', 'data-no-loader'
        // để tránh kích hoạt thanh loading NProgress chạy vô hạn và làm mờ giao diện do trình duyệt không chuyển trang thực tế.
        return hrefAttr &&
            link.hostname === window.location.hostname &&
            !hrefAttr.startsWith('#') &&
            !hrefAttr.startsWith('javascript:') &&
            link.target !== '_blank' &&
            !link.hasAttribute('download') &&
            !link.href.includes('/logout') &&
            !link.href.includes('/export') &&
            !link.href.includes('-export') &&
            !link.classList.contains('no-loader') &&
            !link.hasAttribute('no-loader') &&
            !link.hasAttribute('data-no-loader');
    };

    let hoverTimer;
    // Khi hover link nội bộ quá 65ms, bắt đầu prefetch (tránh prefetch khi di chuột nhanh qua)
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a');
        if (link && link.href) {
            const hrefAttr = link.getAttribute('href');
            if (isInternalLink(link, hrefAttr)) {
                hoverTimer = setTimeout(() => prefetchLink(link.href), 65);
            }
        }
    });

    document.addEventListener('mouseout', () => {
        if (hoverTimer) clearTimeout(hoverTimer);
    });

    // Trên mobile: prefetch ngay khi chạm vào link (không cần delay)
    document.addEventListener('touchstart', (e) => {
        const link = e.target.closest('a');
        if (link && link.href) {
            const hrefAttr = link.getAttribute('href');
            if (isInternalLink(link, hrefAttr)) {
                prefetchLink(link.href);
            }
        }
    }, { passive: true });

    // ------------------------------------------------------------------
    // 3c. NÚT QUAY LẠI ĐẦU TRANG (BACK TO TOP)
    // ------------------------------------------------------------------
    // QUAN TRỌNG: Layout admin dùng body flex h-full nên window KHÔNG BAO GIỜ scroll.
    // Scroll thực tế xảy ra trên #admin-main-container (div.flex-grow có overflow-y-auto).
    // Do đó phải lắng nghe sự kiện scroll trên container này thay vì window.
    const backToTopBtn = document.getElementById('back-to-top');
    const scrollContainer = document.getElementById('admin-main-container');
    if (backToTopBtn && scrollContainer) {
        // Hàm kiểm tra vị trí cuộn của container chính để quyết định ẩn/hiện nút
        const checkScroll = () => {
            if (scrollContainer.scrollTop > 200) {
                backToTopBtn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
                backToTopBtn.classList.add('opacity-100', 'translate-y-0');
            } else {
                backToTopBtn.classList.remove('opacity-100', 'translate-y-0');
                backToTopBtn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
            }
        };

        // Lắng nghe sự kiện scroll trên container flex-grow — nơi cuộn thực sự diễn ra
        scrollContainer.addEventListener('scroll', checkScroll, { passive: true });

        // Khi click nút, cuộn container chính về đầu trang với hiệu ứng mượt mà
        backToTopBtn.addEventListener('click', () => {
            scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Kiểm tra ngay lần đầu khi trang load (trường hợp trang được restore từ cache)
        checkScroll();
    }
});

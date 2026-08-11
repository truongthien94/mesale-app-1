# OAuth Provider Identity Audit — BLK-AUTH-002

Ngày lập: 2026-08-11. Trạng thái: **chờ dữ liệu production**.

## Vấn đề

Hai cột định danh nhà cung cấp hiện không đối xứng:

| Cột | Ràng buộc | Migration |
|---|---|---|
| `users.apple_id` | `nullable()->unique()` | `2026_08_09_130000_add_apple_id_to_users_table.php` |
| `users.google_id` | `nullable()->index()` | `2026_05_21_164725_add_google_id_to_users_table.php` |

`google_id` **không có UNIQUE**, nên một `sub` Google có thể tồn tại trên nhiều user row.

## Vì sao trùng lặp có thể đã xảy ra

Luồng đăng nhập Google trên web tra cứu theo `google_id` **hoặc** email, rồi tự động
gán `google_id` vào account tìm được:

- `app/Http/Controllers/Auth/LoginController.php:635-639` — tìm theo `google_id`, không
  thấy thì tìm tiếp theo `LOWER(email)`.
- `app/Http/Controllers/Auth/LoginController.php:649-651` — nếu account đó chưa có
  `google_id` thì gán vào và lưu.
- `app/Http/Controllers/Auth/LoginController.php:766-772` và `796-805` — hai nhánh
  dự phòng chống race condition, cùng logic `google_id` OR email.

Không có ràng buộc UNIQUE ở tầng database, nên hai request đồng thời hoặc một chuỗi
thao tác đổi email có thể dẫn tới cùng một `sub` Google nằm trên nhiều account.

Điều đáng chú ý: luồng native đã **lường trước** tình trạng này.
`app/Http/Controllers/Api/V1/NativeOAuthController.php:80-91` truy vấn `limit(2)` và
trả lỗi `OAUTH_IDENTITY_AMBIGUOUS` (HTTP 409) khi tìm thấy nhiều hơn một user. Tức là
code đã phòng vệ cho một trạng thái dữ liệu mà schema vẫn cho phép tồn tại.

## Rủi ro nếu thêm UNIQUE ngay

`ALTER TABLE users ADD UNIQUE (google_id)` sẽ **thất bại** nếu dữ liệu đang trùng, và
migration đổ giữa lúc deploy production. Vì vậy phải audit trước, không migrate trước.

Lưu ý MySQL/MariaDB: UNIQUE cho phép nhiều `NULL`, nên account chưa liên kết Google
không bị ảnh hưởng. Nhưng chuỗi rỗng `''` thì **không** được coi là NULL — nếu có row
nào lưu `''` thay vì NULL, chúng sẽ xung đột với nhau. Câu truy vấn bên dưới kiểm tra
cả hai trường hợp.

## Cách chạy audit

### Cách 1 — artisan (local, staging, hoặc host có Terminal)

```bash
php artisan oauth:audit-identities
php artisan oauth:audit-identities --provider=google
```

Lệnh chỉ ĐỌC, không sửa dữ liệu. Exit code `0` = sạch, `1` = có trùng lặp.
Output chỉ in user ID nội bộ và số lượng, không in email/tên/giá trị `sub`.

### Cách 2 — SQL read-only qua phpMyAdmin

Production `mesale.vn` hiện **không có** cPanel Terminal, nên dùng đường này.
Vào cPanel → phpMyAdmin → chọn database → tab SQL → chạy từng câu.

Kiểm tra Google trùng lặp:

```sql
SELECT google_id, COUNT(*) AS total
FROM users
WHERE google_id IS NOT NULL AND google_id != ''
GROUP BY google_id
HAVING COUNT(*) > 1
ORDER BY total DESC;
```

Kiểm tra Apple trùng lặp (nên chạy để đối chứng, kỳ vọng rỗng vì đã có UNIQUE):

```sql
SELECT apple_id, COUNT(*) AS total
FROM users
WHERE apple_id IS NOT NULL AND apple_id != ''
GROUP BY apple_id
HAVING COUNT(*) > 1
ORDER BY total DESC;
```

Đếm tổng số account đã liên kết, để biết quy mô:

```sql
SELECT
  SUM(google_id IS NOT NULL AND google_id != '') AS google_linked,
  SUM(apple_id  IS NOT NULL AND apple_id  != '') AS apple_linked,
  COUNT(*) AS total_users
FROM users;
```

Kiểm tra chuỗi rỗng bị lưu sai thay vì NULL:

```sql
SELECT
  SUM(google_id = '') AS google_empty_string,
  SUM(apple_id  = '') AS apple_empty_string
FROM users;
```

Cả bốn câu đều là `SELECT`, không thay đổi dữ liệu.

**Khi báo cáo kết quả: chỉ ghi lại con số đếm, không copy giá trị `google_id`/`apple_id`
hay email ra ngoài.** Những giá trị đó là định danh thành viên.

## Diễn giải kết quả

**Nếu câu 1 trả về 0 dòng** — không có trùng lặp. An toàn để thêm UNIQUE cho
`google_id`. Bước tiếp: viết migration thêm ràng buộc, test rollback trên SQLite,
rồi deploy. Nên làm cùng lúc với việc siết lại logic auto-link theo email.

**Nếu câu 1 trả về ≥ 1 dòng** — KHÔNG thêm UNIQUE. Mỗi nhóm trùng cần chủ sở hữu
quyết định riêng, vì đây là account thật có ví và số dư:

- Account nào là account chính (thường là account có lịch sử đơn hàng/số dư)?
- Account còn lại xử lý thế nào — gỡ liên kết `google_id`, hay hợp nhất?
- Nếu hợp nhất thì số dư, đơn hàng, referral F1/F2, hoa hồng đi đâu?

Hợp nhất account tài chính là thao tác không thể hoàn tác an toàn. Phải có quyết định
của chủ sở hữu và backup database trước, không tự động hóa.

## Vấn đề thiết kế cần quyết định riêng

Kể cả khi dữ liệu hiện tại sạch, việc **auto-link theo email** ở
`LoginController.php:637-639` vẫn là rủi ro cần xem xét: một người kiểm soát được
email khớp có thể chiếm account đã tồn tại thông qua đăng nhập Google, nếu email đó
chưa từng được xác minh.

CLAUDE.md đã nêu nguyên tắc tương ứng cho Apple: *"không auto-merge chỉ vì email trùng
nếu chưa có proof/linking flow an toàn"*. Nguyên tắc này hiện chưa được áp dụng cho
Google trên luồng web.

Đề xuất đã trình: chỉ auto-link khi Google trả `email_verified = true` **và** account
đích đã có `email_verified_at`; ngoài ra yêu cầu đăng nhập bằng mật khẩu để xác nhận.

**Quyết định của chủ sở hữu ngày 2026-08-11: giữ nguyên hành vi auto-link hiện tại,
không thay đổi.** Hành vi tại `LoginController.php:637-639` được chấp nhận như thiết kế.
Không sửa code cho mục này.

## Trạng thái

- [x] Xác định schema không đối xứng
- [x] Xác định luồng code tạo ra trùng lặp
- [x] Viết công cụ audit read-only (artisan + SQL)
- [ ] Chạy audit trên dữ liệu production
- [ ] Chủ sở hữu quyết định xử lý từng nhóm trùng (nếu có)
- [ ] Chủ sở hữu quyết định chính sách auto-link theo email
- [ ] Thêm UNIQUE cho `google_id` (chỉ sau khi dữ liệu sạch)

Không có secret, thông tin thành viên hay raw production data trong tài liệu này.

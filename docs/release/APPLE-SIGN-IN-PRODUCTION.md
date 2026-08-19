# Sign in with Apple — Production Setup

## Apple Developer

1. Mở Identifier của ứng dụng iOS và xác nhận Bundle ID là `vn.mesale.app`.
2. Bật capability **Sign in with Apple** cho Identifier này.
3. Tạo một **Sign in with Apple key**, tải file `AuthKey_<KEY_ID>.p8` đúng một lần và lưu ngoài repository.
4. Ghi lại Apple Team ID và Key ID. Không commit file `.p8` hoặc nội dung private key.
5. Regenerate provisioning profile rồi rebuild ứng dụng nếu capability vừa được bật.

Native `expo-apple-authentication` dùng Bundle ID làm Apple client ID. Services ID và redirect URI chỉ cần khi có thêm web flow.

## Backend Environment

Production `.env` tối thiểu:

```dotenv
APPLE_BUNDLE_ID=vn.mesale.app
APPLE_SERVICES_ID=
APPLE_OAUTH_AUDIENCES=
APPLE_TEAM_ID=<10-character-team-id>
APPLE_KEY_ID=<10-character-key-id>
APPLE_PRIVATE_KEY=
APPLE_PRIVATE_KEY_PATH=/absolute/secure/path/AuthKey_<KEY_ID>.p8
APPLE_REDIRECT_URI=
```

Yêu cầu vận hành:

- File `.p8` nằm ngoài web root và ngoài Git.
- User chạy PHP-FPM/queue phải đọc được file; quyền khuyến nghị `600` hoặc quyền tối thiểu tương đương.
- `APPLE_REDIRECT_URI` để trống cho native flow hiện tại. Chỉ đặt khi authorization request thực sự sử dụng cùng HTTPS redirect URI.
- Có thể dùng `APPLE_PRIVATE_KEY` thay cho file path; khi đó newline phải được giữ nguyên hoặc biểu diễn bằng `\\n`.

## Deploy Gate

Sau khi cập nhật source và environment:

```bash
php artisan optimize:clear
php artisan oauth:apple:check
php artisan config:cache
php artisan oauth:apple:check
```

Khi preflight pass, vào **Admin → Cài đặt → Open API** và bật **Sign in with Apple API**, sau đó chạy:

```bash
php artisan oauth:apple:check --require-enabled
```

Public config chỉ được xem là bật khi cả feature flag và server credentials đều hợp lệ:

```bash
curl -sS https://mesale.vn/api/v1/openapi/config
```

Giá trị cần thấy:

```text
features.api_auth_oauth_apple = true
```

## Device Verification

1. Cài bản iOS đã ký bằng provisioning profile có entitlement Sign in with Apple.
2. Đăng nhập bằng một Apple ID thử nghiệm được chủ sở hữu phê duyệt.
3. Xác nhận Apple sheet hoàn tất, backend trả Bearer session và app vào trạng thái authenticated.
4. Kiểm tra trường hợp hủy, private relay email, tài khoản email đã tồn tại và retry sau lỗi mạng.
5. Không ghi identity token, authorization code, nonce, private relay email hoặc private key vào log/screenshot.

# Triển khai lên Tenten Shared Hosting (cPanel/DirectAdmin)

Không có SSH — toàn bộ các bước dưới đây thao tác qua giao diện web (cPanel/DirectAdmin + phpMyAdmin) hoặc FTP.

## 1. Kiểm tra phiên bản PHP

Vào **cPanel → Select PHP Version**, chọn PHP 8.0 trở lên. Bấm vào **Extensions** và đảm bảo các extension sau đã bật: `pdo_mysql`, `mbstring`, `json`. Đây là các extension chuẩn, hầu hết hosting PHP đều bật sẵn — chỉ cần xác nhận lại.

## 2. Tạo database MySQL

Vào **cPanel → MySQL® Databases**:
1. Tạo database mới (ví dụ `cpaneluser_simea`).
2. Tạo user MySQL mới + mật khẩu mạnh.
3. Gán user vào database với **All Privileges**.
4. Ghi lại: tên database, username, password, host (thường là `localhost`).

## 3. Import schema + dữ liệu seed

Vào **phpMyAdmin**, chọn database vừa tạo, vào tab **Import**, import lần lượt 3 file theo đúng thứ tự:
1. `database/schema.sql`
2. `database/seed_reference_data.sql`
3. `database/seed_users.sql`

Sau khi import, kiểm tra tất cả bảng là **InnoDB** (không phải MyISAM) — vào tab **Operations** của từng bảng hoặc chạy:

```sql
SHOW TABLE STATUS WHERE Engine <> 'InnoDB';
```

Kết quả phải rỗng. Nếu có bảng nào là MyISAM (một số cấu hình phpMyAdmin mặc định), đổi engine thủ công: `ALTER TABLE ten_bang ENGINE=InnoDB;`

## 4. Upload mã nguồn

- Nén toàn bộ nội dung repo (không nén thư mục `.git`) thành file `.zip`.
- Vào **cPanel → File Manager**, vào thư mục web gốc của domain (thường là `public_html`, hoặc thư mục con nếu domain là add-on domain).
- Upload file zip rồi **Extract** ngay tại đó — sao cho `index.php` nằm ngay trong `public_html`, không nằm trong 1 thư mục con thừa.
- Hoặc dùng FTP (FileZilla) với thông tin đăng nhập FTP từ cPanel.

**Quan trọng**: bật hiển thị file ẩn (dotfiles) trong File Manager (**Settings → Show Hidden Files**) hoặc trong FileZilla, để chắc chắn các file `.htaccess` (ở thư mục gốc, `app/`, `database/`, `storage/`) được upload đầy đủ — đây là các file chặn truy cập trực tiếp vào mã nguồn/dữ liệu.

## 5. Tạo file cấu hình thật trên server

**Không** commit `app/config/config.php` thật lên Git. Trên server:
1. Copy `app/config/config.sample.php` thành `app/config/config.php` (qua File Manager: chọn file → Copy → đổi tên).
2. Sửa nội dung file mới với thông tin DB thật từ bước 2, và:
   - `APP_BASE_URL` = domain thật, có `https://`
   - `DISPLAY_ERRORS` = `false`

## 6. Kiểm tra .htaccess / mod_rewrite hoạt động

Mở trình duyệt, thử các URL sau:
- `https://your-domain/app/config/config.php` → phải trả về **403 Forbidden**
- `https://your-domain/database/schema.sql` → phải trả về **403 Forbidden**
- `https://your-domain/login` → phải hiện đúng trang đăng nhập (chứng tỏ `mod_rewrite` hoạt động)

Nếu `/login` báo lỗi 404, host có thể đã tắt `AllowOverride` cho `.htaccess`. Cách khắc phục tạm: dùng URL dạng `https://your-domain/index.php?route=login`; sau đó liên hệ hỗ trợ Tenten để bật `mod_rewrite`/`AllowOverride All` cho domain.

## 7. Phân quyền thư mục

Giữ nguyên mặc định của hosting (thường 644 cho file, 755 cho thư mục). Chỉ cần đảm bảo `storage/logs/` có quyền ghi được (thường không cần chỉnh gì thêm trên shared hosting vì user sở hữu file = user chạy PHP-FPM). **Không** dùng quyền 777.

## 8. Đăng nhập lần đầu & đổi mật khẩu

Đăng nhập lần lượt bằng 5 tài khoản (`lanhdao`, `dieuphoi`, `xuong`, `kho`, `qc` — mật khẩu mặc định `Simea@2026`) để xác nhận đúng vai trò/giao diện hiển thị.

**Đổi mật khẩu ngay**: phiên bản này chưa có màn hình đổi mật khẩu. Cách đổi:
1. Tạo hash mật khẩu mới — chạy tạm trên máy có PHP: `php -r "echo password_hash('MAT_KHAU_MOI', PASSWORD_DEFAULT);"`
2. Vào phpMyAdmin, bảng `users`, sửa cột `password_hash` của tài khoản tương ứng bằng giá trị hash vừa tạo.

## 9. Sao lưu định kỳ

Vì không có SSH/cron riêng, dùng **cPanel → Backup Wizard** để tải/lên lịch backup định kỳ cho database và file — đặt lịch hàng tuần tối thiểu.

## 10. Theo dõi lỗi

`storage/logs/app.log` ghi lại lỗi PHP (đã tắt hiển thị lỗi ra màn hình ở bước 5). Kiểm tra định kỳ file này qua File Manager nếu người dùng báo lỗi.

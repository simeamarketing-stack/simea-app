# SIMEA — Hệ thống quản lý vận hành sản xuất

Ứng dụng web nội bộ cho SIMEA Coffee, xây bằng PHP thuần (PDO + MySQL/MariaDB) — không cần Node.js, Composer hay bước build nào để chạy, phù hợp với shared hosting (cPanel/DirectAdmin).

## Phạm vi phiên bản hiện tại (MVP)

5 module vận hành cốt lõi, theo đúng bản thiết kế "Thiết Kế Vận Hành SIMEA":

1. **Danh mục** — khách hàng, loại cà phê, vật tư (8 nhóm cố định), SKU sản phẩm
2. **BOM & định mức năng suất** — versioned, draft/duyệt, tự sao chép phiên bản
3. **Kho vật tư** — sổ cái cộng dồn (append-only ledger), giữ chỗ, xuất kho an toàn khi có nhiều người thao tác cùng lúc
4. **Lệnh sản xuất** — trung tâm điều phối: tạo tối giản → bổ sung dần → phát hành (đóng băng BOM/định mức) → xác nhận lịch → theo dõi cảnh báo
5. **Báo cáo ca** — ghi nhận, QC xác nhận, khóa báo cáo, lịch sử chỉnh sửa

Các module **Quy cách sản phẩm, QC & thành phẩm, Đối soát kho, Dashboard lãnh đạo** được để ở phase sau — schema đã chừa chỗ, xem ghi chú trong `database/schema.sql`.

## Vai trò & tài khoản mặc định

5 tài khoản cố định, seed sẵn trong `database/seed_users.sql`, mật khẩu mặc định **`Simea@2026`** (đổi ngay sau khi lên production — xem mục Bảo mật bên dưới):

| Username | Vai trò |
|---|---|
| `lanhdao` | Lãnh đạo (chỉ xem toàn bộ) |
| `dieuphoi` | Điều phối |
| `xuong` | Xưởng |
| `kho` | Kho |
| `qc` | QC |

## Chạy thử ở máy local

Cần PHP 8.x (bật sẵn `pdo_mysql`, `mbstring`) và MySQL/MariaDB.

```bash
php -S localhost:8000 router.php
```

Tạo database, import theo thứ tự:

```sql
-- trong phpMyAdmin hoặc mysql CLI, trỏ vào DB rỗng vừa tạo
source database/schema.sql;
source database/seed_reference_data.sql;
source database/seed_users.sql;
```

Copy `app/config/config.sample.php` thành `app/config/config.php`, điền thông tin kết nối DB, rồi mở `http://localhost:8000`.

## Bảo mật

- `app/`, `database/`, `storage/` bị chặn truy cập web trực tiếp bằng `.htaccess` — không di chuyển các thư mục này ra khỏi vị trí gốc.
- Đổi mật khẩu 5 tài khoản seed ngay sau lần đăng nhập đầu tiên trên production (chưa có màn hình đổi mật khẩu ở phiên bản này — xem `DEPLOY.md`).
- `app/config/config.php` không được commit lên Git (đã có trong `.gitignore`).

Xem `docs/DEPLOY.md` để triển khai lên shared hosting.

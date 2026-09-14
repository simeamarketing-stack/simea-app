# SIMEA — Hệ thống quản lý vận hành sản xuất

Ứng dụng web nội bộ cho SIMEA Coffee, xây bằng PHP thuần (PDO + MySQL/MariaDB) — không cần Node.js, Composer hay bước build nào để chạy, phù hợp với shared hosting (cPanel/DirectAdmin).

## Phạm vi phiên bản hiện tại (MVP)

6 module, theo đúng bản thiết kế "Thiết Kế Vận Hành SIMEA":

1. **Danh mục** — khách hàng, loại cà phê, vật tư (8 nhóm cố định), SKU sản phẩm
2. **BOM & định mức năng suất** — versioned, draft/duyệt, tự sao chép phiên bản
3. **Kho vật tư** — sổ cái cộng dồn (append-only ledger), giữ chỗ, xuất kho an toàn khi có nhiều người thao tác cùng lúc
4. **Lệnh sản xuất** — trung tâm điều phối: tạo tối giản → bổ sung dần → phát hành (đóng băng BOM/định mức) → xác nhận lịch → theo dõi cảnh báo
5. **Báo cáo ca** — ghi nhận, QC xác nhận, khóa báo cáo, lịch sử chỉnh sửa
6. **Dashboard** — lịch sản xuất dạng lịch tháng, cảnh báo (thiếu vật tư/trễ hạn/thiếu báo cáo), vật tư sắp hết, tiến độ vs kế hoạch, tỷ lệ lỗi QC & hao hụt

Các module **Quy cách sản phẩm, QC & thành phẩm (đầy đủ — phiếu sự cố, cách ly/làm lại/loại bỏ), Đối soát kho** được để ở phase sau — schema đã chừa chỗ, xem ghi chú trong `database/schema.sql`. Dashboard hiện dùng 2 chỉ số QC tối giản (`qc_checked_qty`/`qc_defect_qty` nhập ngay lúc QC xác nhận báo cáo ca) làm giải pháp tạm cho tới khi module QC đầy đủ được xây.

## Vai trò & tài khoản mặc định

2 tài khoản cố định, seed sẵn trong `database/seed_users.sql`, mật khẩu mặc định **`Simea@2026`** (đổi ngay sau khi lên production — xem mục Bảo mật bên dưới). Gộp theo đúng thực tế hiện tại của SIMEA (1 người vận hành hiện trường, 1 người điều hành):

| Username | Vai trò | Quyền |
|---|---|---|
| `quanly` | Quản lý | Toàn quyền Danh mục, BOM & định mức, tạo/phát hành lệnh sản xuất; xem toàn bộ hệ thống |
| `vanhanh` | Vận hành | Toàn quyền Kho vật tư, giữ chỗ/xuất vật tư, xác nhận/đổi lịch, gán chuyền, ghi & khóa báo cáo ca, QC xác nhận |

Sau này khi tách người phụ trách riêng cho Kho/Xưởng/QC hoặc cho Lãnh đạo xem-only, chỉ cần thêm role mới trong `app/config/roles.php` và cập nhật các mảng quyền trong `app/routes.php` — không cần sửa lại schema hay logic nghiệp vụ.

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

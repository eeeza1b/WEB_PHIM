# WEB_PHIM

Website phim xây dựng bằng **PHP thuần**, **MySQL**, **Vanilla JavaScript** và **CSS**, theo cấu trúc MVC cơ bản.

Điểm nhấn của dự án là tính năng **gợi ý phim theo tâm trạng (Mood-based Recommendation)**. Người dùng chọn tâm trạng và hệ thống trả về danh sách phim phù hợp qua quan hệ nhiều-nhiều giữa `movies` và `moods`.

---

## 1. Yêu cầu môi trường

Máy cần cài một trong các môi trường PHP/MySQL, khuyến nghị **XAMPP**:

- Apache 2.4+
- PHP 8.0+ (khuyến nghị PHP 8.1+)
- MySQL hoặc MariaDB
- PHP extension `pdo_mysql`
- PHP extension `curl` (bắt buộc nếu dùng TMDB)
- Trình duyệt hiện đại có hỗ trợ ES6 Modules

Trên Windows với XAMPP, PHP CLI thường ở:

```text
C:\xampp\php\php.exe
```

---

## 2. Đặt mã nguồn vào XAMPP

1. Mở thư mục XAMPP:

   ```text
   C:\xampp\htdocs\
   ```

2. Đặt/copy toàn bộ source code vào thư mục:

   ```text
   C:\xampp\htdocs\WEB_PHIM
   ```

3. Sau đó cấu trúc phải có dạng:

   ```text
   C:\xampp\htdocs\WEB_PHIM\
   ├── .htaccess
   ├── config.php
   ├── index.php
   ├── admin\
   ├── api\
   ├── assets\
   ├── database\
   ├── includes\
   ├── tools\
   └── views\
   ```

> Tên thư mục `WEB_PHIM` phải khớp với cả `BASE_URL` trong `config.php` và `RewriteBase` trong `.htaccess`.

---

## 3. Cấu trúc dự án

```text
WEB_PHIM/
├── .htaccess                   # Friendly URL rewrite
├── config.php                  # Cấu hình app, DB và TMDB
├── index.php                   # Router chính
├── actions/                    # Xử lý submit form
├── admin/                      # Khu vực quản trị
├── api/                        # Endpoint JSON (Live Search, trailer...)
├── assets/
│   ├── css/                    # style.css, responsive.css, admin-style.css
│   └── js/
│       ├── main.js             # Khởi tạo UI, Live Search, drawer
│       └── components/         # ES6 modules, bao gồm movie_card.js
├── database/
│   └── schema.sql              # Schema và seed data
├── includes/                   # DB, auth, helper, header/footer, TMDB helper
├── tools/
│   └── sync_tmdb_movies.php    # Đồng bộ TMDB Popular vào MySQL
└── views/                      # Giao diện phía người dùng
```

---

## 4. Phân công nhóm & Quy trình làm việc Git

### 4.1 Danh sách thành viên
Dự án được chia module và thực hiện bởi nhóm gồm 3 thành viên:

| STT | Họ và Tên | MSSV | Vai trò |
|---|---|---|---|
| 1 | [Nguyễn Gia Huy] | [079206023415] | Core Architecture & Tech Lead + Search Engine & RESTful API |
| 2 | [Nguyễn Quốc Huy] | [060206006836] | Admin Management & Analytics |
| 3 | [Nguyễn Tấn Huy] | [079206018491] | User & Social Interaction + UI/UX, Mood Engine, SEO |

### 4.2 Chi tiết nhiệm vụ từng thành viên

**Thành viên 1 (Gia Huy): Core Architecture & Tech Lead + Search Engine & RESTful API**
- Khởi tạo dự án, thiết kế và bàn giao `database/schema.sql` chuẩn cho cả nhóm.
- Quản lý bảo mật cốt lõi: PDO Prepared Statements, Bcrypt, CSRF Protection, phân quyền Session (admin/user).
- Duyệt code (Code Review), giải quyết xung đột (Conflict), merge các nhánh vào `main`.
- Khám phá (`views/browse.php`): bộ lọc kết hợp Thể loại + Năm phát hành + Điểm đánh giá + Sắp xếp.
- Tối ưu AJAX: lọc/chuyển trang không reload toàn bộ trang.
- REST API: mở rộng `api/movies.php`, xây dựng `api/reviews.php` (GET bình luận + rating theo `movie_id`, JSON).

File phụ trách: `config.php`, `includes/`, `.htaccess`, `index.php`, `database/schema.sql`, `views/browse.php`, `api/movies.php`, `api/reviews.php`

---

**Thành viên 2 (Quốc Huy): Admin Management & Analytics**
- Phân trang Admin (`admin/movies.php`, `admin/users.php`): server-side pagination (`LIMIT`/`OFFSET`).
- Quản lý Thể loại (`admin/genres.php` — file mới): CRUD đầy đủ Thêm/Sửa/Xóa thể loại. *(đang thực hiện)*
- Nâng cấp Dashboard (`admin/index.php`): Chart.js đã có sẵn 2 biểu đồ — thêm biểu đồ thứ 3: Top 5 phim được lưu Watchlist nhiều nhất.

File phụ trách: `admin/movies.php` (chỉ thêm phân trang, không sửa logic CRUD gốc), `admin/genres.php` (mới), `admin/index.php`, `admin/users.php`

---

**Thành viên 3 (Tấn Huy): User & Social Interaction + UI/UX, Mood Engine, SEO**
- Hồ sơ cá nhân (`views/profile.php`): form đổi avatar, validate dữ liệu đầu vào.
- Bình luận & Đánh giá: thêm cột `rating` (tinyint, 1-10, nullable) vào bảng `comments` có sẵn — không tạo bảng `reviews` mới.
- Form gửi bình luận kèm rating (`actions/comment_submit.php`).
- Hiển thị bình luận mới nhất + tính điểm đánh giá trung bình, lưu vào cột mới `movies.user_rating_avg` (không ghi đè cột `rating` gốc từ TMDB).
- Gợi ý tâm trạng (`views/mood.php`): hiện đã trim còn 2 mood mẫu (`happy`, `sad`) — mở rộng thêm các mood còn lại (Thư giãn, Lãng mạn, Kịch tính...) liên kết nhóm thể loại tương ứng.
- Responsive mobile/tablet, Toast notification khi thêm Watchlist thành công.
- Modal Trailer YouTube co giãn chuẩn 16:9, xử lý ảnh placeholder khi poster lỗi.
- SEO: tạo `robots.txt`, `sitemap.xml`, thêm thẻ Open Graph (`og:title`, `og:description`, `og:image`) vào `includes/header.php`.

File phụ trách: `views/profile.php`, `views/movie.php` (toàn quyền — cả bình luận lẫn trailer modal), `views/mood.php`, `actions/comment_submit.php`, `assets/css/`, `assets/js/`, `robots.txt` (mới), `sitemap.xml` (mới), `includes/header.php` (phần Open Graph)

---
### 4.3 Cấu trúc nhánh Git

```
main
├── feature/core           + feature/search-api     (Gia Huy)
├── feature/admin-analytics                          (Quốc Huy)
└── feature/user-social     + feature/ui-mood-seo     (Tấn Huy)
```
```

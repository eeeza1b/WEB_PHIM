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

Dự án được chia module và thực hiện bởi nhóm gồm 5 thành viên:

| STT | Họ và Tên | MSSV | Vai trò |
|---|---|---|---|
| 1 | [Nguyễn Gia Huy] | [079206023415] | Core Architecture & Tech Lead |
| 2 | [Họ tên TV2] | [MSSV] | User Module & Social Interaction |
| 3 | [Họ tên TV3] | [MSSV] | Admin Management & Analytics |
| 4 | [Họ tên TV4] | [MSSV] | Search Engine & RESTful API |
| 5 | [Họ tên TV5] | [MSSV] | UI/UX, Mood Engine, Media & SEO |

### 4.2 Chi tiết nhiệm vụ từng thành viên

**Thành viên 1 (Leader): Core Architecture & Tech Lead**
- Khởi tạo dự án, thiết kế và bàn giao `database/schema.sql` chuẩn cho cả nhóm.
- Quản lý bảo mật cốt lõi: PDO Prepared Statements, Bcrypt, CSRF Protection, phân quyền Session (admin/user).
- Duyệt code (Code Review), giải quyết xung đột (Conflict), merge các nhánh vào `main`.
- Hỗ trợ TV4 mở rộng `get_movies()`/`count_movies()` trong `includes/functions.php` khi cần.

File phụ trách: `config.php`, `includes/`, `.htaccess`, `index.php`, `database/schema.sql`

**Thành viên 2: User Module & Social Interaction**
- Hồ sơ cá nhân (`views/profile.php`): form đổi avatar, validate dữ liệu đầu vào.
- Bình luận & Đánh giá: thêm cột `rating` (tinyint, 1-10, nullable) vào bảng `comments` có sẵn — không tạo bảng `reviews` mới.
- Form gửi bình luận kèm rating (`actions/comment_submit.php`).
- Hiển thị bình luận mới nhất + tính điểm đánh giá trung bình, lưu vào cột mới `movies.user_rating_avg` (không ghi đè cột `rating` gốc từ TMDB).

File phụ trách: `views/profile.php`, `actions/comment_submit.php`
File dùng chung (chỉ sửa phần được chỉ định): `views/movie.php` → khối `<section class="comments">`

**Thành viên 3: Admin Management & Analytics**
- Phân trang Admin (`admin/movies.php`, `admin/users.php`): server-side pagination.
- Quản lý Thể loại (`admin/genres.php` - file mới): CRUD Thêm/Sửa/Xóa thể loại.
- Nâng cấp Dashboard (`admin/index.php`): thêm biểu đồ Top 5 phim được Watchlist nhiều nhất (Chart.js đã có sẵn 2 biểu đồ).

File phụ trách: `admin/movies.php` (chỉ thêm phân trang), `admin/genres.php` (mới), `admin/index.php`, `admin/users.php`

**Thành viên 4: Search Engine & RESTful API**
- Khám phá (`views/browse.php`): bộ lọc kết hợp Thể loại + Năm + Điểm đánh giá + Sắp xếp.
- Tối ưu AJAX: lọc/chuyển trang không reload toàn trang.
- REST API: `api/reviews.php` (GET bình luận + rating theo `movie_id`, JSON).

File phụ trách: `views/browse.php`, `api/movies.php`, `api/reviews.php`
Phối hợp bắt buộc: sửa `includes/functions.php` phải báo trước TV1.

**Thành viên 5: UI/UX, Mood Engine, Media & SEO**
- Gợi ý tâm trạng (`views/mood.php`): mở rộng thêm mood liên kết nhóm thể loại.
- Responsive mobile/tablet, Toast notification khi thêm Watchlist.
- Modal Trailer YouTube 16:9, ảnh placeholder khi poster lỗi.
- SEO: `robots.txt`, `sitemap.xml`, thẻ Open Graph trong `includes/header.php`.

File phụ trách: `views/mood.php`, `assets/css/`, `assets/js/`, `robots.txt` (mới), `sitemap.xml` (mới)
File dùng chung (chỉ sửa phần được chỉ định): `views/movie.php` → khối `<div class="trailer-modal">`

### 4.3 Cấu trúc nhánh Git

```
main
├── feature/core              (TV1 - Leader)
├── feature/user-social        (TV2)
├── feature/admin-analytics    (TV3)
├── feature/search-api         (TV4)
└── feature/ui-mood-seo        (TV5)
```

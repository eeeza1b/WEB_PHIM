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

## 3. Khởi động Apache và MySQL

1. Mở **XAMPP Control Panel** bằng quyền bình thường hoặc Administrator.
2. Nhấn **Start** ở hai dịch vụ:
   - Apache
   - MySQL
3. Nếu Apache không khởi động do xung đột cổng 80, hãy kiểm tra Skype, IIS hoặc phần mềm khác đang sử dụng cổng này.

Kiểm tra nhanh Apache:

```text
http://localhost/
```

Nếu trang dashboard XAMPP hiển thị, Apache đang hoạt động.

---

## 4. Tạo và import database

### Cách 1: Import bằng phpMyAdmin

1. Truy cập:

   ```text
   http://localhost/phpmyadmin
   ```

2. Tạo database tên:

   ```text
   web_film_demo
   ```

3. Chọn database `web_film_demo`.
4. Mở tab **Import**.
5. Chọn file:

   ```text
   C:\xampp\htdocs\WEB_PHIM\database\schema.sql
   ```

6. Nhấn **Import**.

### Cách 2: Import bằng Command Prompt/PowerShell

Mở PowerShell tại thư mục dự án và chạy:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS web_film_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Get-Content -Raw database\schema.sql | C:\xampp\mysql\bin\mysql.exe -u root web_film_demo
```

Nếu tài khoản MySQL `root` có mật khẩu, thêm `-p`:

```powershell
Get-Content -Raw database\schema.sql | C:\xampp\mysql\bin\mysql.exe -u root -p web_film_demo
```

> Sau khi chạy lệnh có `-p`, MySQL sẽ yêu cầu nhập mật khẩu.

---

## 5. Cấu hình `config.php`

Mở file `config.php` và kiểm tra/chỉnh các giá trị phù hợp môi trường của bạn:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'web_film_demo');
define('DB_USER', 'root');
define('DB_PASS', '');

/*
 * Nếu project nằm tại C:\xampp\htdocs\WEB_PHIM
 * thì URL phải là /WEB_PHIM
 */
define('BASE_URL', '/WEB_PHIM');
```

### Ví dụ khi project có tên thư mục khác

Nếu source nằm ở:

```text
C:\xampp\htdocs\web_film_demo
```

thì cần đổi thành:

```php
define('BASE_URL', '/web_film_demo');
```

Không thêm dấu `/` ở cuối `BASE_URL`.

Đồng thời mở `.htaccess` và đổi:

```apache
RewriteBase /WEB_PHIM/
```

thành:

```apache
RewriteBase /web_film_demo/
```

---

## 6. Bật Friendly URL bằng `.htaccess`

Dự án dùng file `.htaccess` để biến URL dạng cũ:

```text
/index.php?page=home
```

thành URL thân thiện:

```text
/home
```

### 6.1 Bật `mod_rewrite`

Mở:

```text
C:\xampp\apache\conf\httpd.conf
```

Tìm dòng sau và bỏ ký tự `#` ở đầu nếu đang bị comment:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### 6.2 Cho phép `.htaccess` hoạt động

Trong cùng file `httpd.conf`, tìm cấu hình thư mục `htdocs`, ví dụ:

```apache
<Directory "C:/xampp/htdocs">
    ...
    AllowOverride None
    ...
</Directory>
```

Đổi thành:

```apache
<Directory "C:/xampp/htdocs">
    ...
    AllowOverride All
    ...
</Directory>
```

### 6.3 Restart Apache

Trong XAMPP Control Panel:

1. Nhấn **Stop** Apache.
2. Nhấn **Start** Apache lại.

Sau đó thử mở:

```text
http://localhost/WEB_PHIM/home
```

Nếu vẫn gặp lỗi `404`, xem mục **Xử lý lỗi thường gặp** bên dưới.

---

## 7. Chạy website

Sau khi hoàn tất Apache, MySQL, database và cấu hình, truy cập:

```text
http://localhost/WEB_PHIM/home
```

Các URL hữu ích:

| Chức năng | URL |
| --- | --- |
| Trang chủ | `http://localhost/WEB_PHIM/home` |
| Khám phá phim | `http://localhost/WEB_PHIM/browse` |
| Gợi ý theo tâm trạng | `http://localhost/WEB_PHIM/mood` |
| Đăng nhập | `http://localhost/WEB_PHIM/login` |
| Đăng ký | `http://localhost/WEB_PHIM/register` |
| Danh sách xem | `http://localhost/WEB_PHIM/watchlist` |
| Chi tiết phim | `http://localhost/WEB_PHIM/movie?id=1` |
| API Live Search | `http://localhost/WEB_PHIM/api/movies.php?search=avatar` |
| Admin dashboard | `http://localhost/WEB_PHIM/admin/index.php` |

> Route `movie` hiện vẫn cần tham số `id`, ví dụ `movie?id=1`, để xác định phim cần hiển thị.

---

## 8. Tài khoản Admin

Dữ liệu tài khoản mẫu (nếu được khai báo trong `database/schema.sql`) có thể xem trực tiếp trong file schema hoặc bảng `users` sau khi import.

Nếu cần nâng một user hiện có thành admin, chạy SQL trong phpMyAdmin:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'email-cua-ban@example.com';
```

Sau đó đăng xuất và đăng nhập lại trước khi truy cập:

```text
http://localhost/WEB_PHIM/admin/index.php
```

---

## 9. Cấu hình và sử dụng TMDB API

TMDB được dùng để lấy phim phổ biến và trailer.

### 9.1 Lấy TMDB Read Access Token

1. Tạo/đăng nhập tài khoản tại:

   ```text
   https://www.themoviedb.org/
   ```

2. Mở trang API Settings:

   ```text
   https://www.themoviedb.org/settings/api
   ```

3. Sao chép **API Read Access Token** (v4 auth token).

### 9.2 Thiết lập token an toàn hơn (khuyến nghị)

Trong PowerShell, tại phiên terminal hiện tại:

```powershell
$env:TMDB_API_TOKEN = 'DAN_TOKEN_TMDB_CUA_BAN'
```

Hoặc thiết lập biến môi trường Windows vĩnh viễn:

```powershell
setx TMDB_API_TOKEN "DAN_TOKEN_TMDB_CUA_BAN"
```

Sau khi dùng `setx`, hãy đóng và mở lại XAMPP/terminal để biến môi trường được nhận.

### 9.3 Cách thay thế: dán token vào `config.php`

Chỉ dùng khi không thể thiết lập biến môi trường. Đổi placeholder:

```php
define(
    'TMDB_API_TOKEN',
    getenv('TMDB_API_TOKEN') ?: 'PASTE_YOUR_TMDB_READ_ACCESS_TOKEN_HERE'
);
```

thành:

```php
define(
    'TMDB_API_TOKEN',
    getenv('TMDB_API_TOKEN') ?: 'DAN_TOKEN_TMDB_CUA_BAN'
);
```

> Không commit token thật lên GitHub.

### 9.4 Kiểm tra extension cURL

Mở:

```text
C:\xampp\php\php.ini
```

Tìm dòng:

```ini
;extension=curl
```

và bỏ dấu `;`:

```ini
extension=curl
```

Sau đó restart Apache.

### 9.5 Đồng bộ phim phổ biến vào MySQL

Mở PowerShell trong thư mục source:

```powershell
cd C:\xampp\htdocs\WEB_PHIM
```

Chạy đồng bộ trang phổ biến thứ nhất:

```powershell
C:\xampp\php\php.exe tools\sync_tmdb_movies.php 1
```

Đồng bộ trang thứ hai:

```powershell
C:\xampp\php\php.exe tools\sync_tmdb_movies.php 2
```

Script sẽ:

- Lấy dữ liệu từ endpoint TMDB `/movie/popular`.
- Insert phim chưa tồn tại vào `movies`.
- Insert/liên kết genre vào `genres` và `movie_genre`.
- Không tạo trùng phim nhờ slug có TMDB ID.
- Không chạm vào `moods`, `movie_mood` hoặc `user_mood_history`.

> Chạy script nhiều lần là an toàn. Các phim TMDB đã tồn tại sẽ được bỏ qua.

---

## 10. Kiểm tra các tính năng mới

### 10.1 Mobile Drawer Menu

1. Mở trang chủ bằng Chrome/Edge.
2. Nhấn `F12`.
3. Bật Device Toolbar (biểu tượng điện thoại) hoặc thu chiều rộng dưới 768px.
4. Nhấn nút menu 3 gạch ở header.
5. Menu sẽ trượt từ cạnh trái; click overlay hoặc nhấn `Escape` để đóng.

### 10.2 Movie Grid Scroll Snap

Ở giao diện mobile, các khu vực `.movie-grid` sẽ cuộn ngang từng thẻ phim thay vì kéo dài dọc trang.

### 10.3 Live Search

1. Nhập ít nhất 2 ký tự vào ô **Tìm phim...** trên header.
2. JavaScript gọi API:
   ```text
   /api/movies.php?search=...
   ```
3. Dropdown hiển thị kết quả ngay, không reload trang.
4. Click vào một kết quả để mở trang chi tiết phim.

### 10.4 TMDB Trailer

Khi phim có `tmdb_id`, frontend có thể gọi:

```text
/api/get_trailerMovie.php?id=TMDB_ID
```

API sẽ ưu tiên trailer YouTube chính thức.

### 10.5 Admin Dashboard

Đăng nhập bằng tài khoản có `role = 'admin'`, truy cập:

```text
http://localhost/WEB_PHIM/admin/index.php
```

Dashboard có:

- Tổng số phim.
- Tổng số người dùng.
- Biểu đồ đường: phim được thêm trong 12 tháng gần nhất.
- Biểu đồ doughnut: tỷ trọng thể loại phim.

### 10.6 Thêm phim nhiều thể loại

Truy cập trang thêm phim từ phần quản trị:

1. Nhập tiêu đề, slug và thông tin phim.
2. Chọn một hoặc nhiều checkbox thể loại.
3. Nhấn **Thêm phim**.

Hệ thống lưu quan hệ nhiều thể loại vào bảng `movie_genre`.

---

## 11. Kiểm tra cú pháp PHP

Nếu cần kiểm tra nhanh file PHP sau khi chỉnh sửa:

```powershell
cd C:\xampp\htdocs\WEB_PHIM
C:\xampp\php\php.exe -l index.php
C:\xampp\php\php.exe -l includes\tmdb_helper.php
C:\xampp\php\php.exe -l tools\sync_tmdb_movies.php
C:\xampp\php\php.exe -l admin\index.php
```

Kết quả hợp lệ có dạng:

```text
No syntax errors detected in index.php
```

---

## 12. Xử lý lỗi thường gặp

### Lỗi 404 khi mở `/home`

Nguyên nhân thường là Apache chưa bật rewrite hoặc chưa cho phép `.htaccess`.

Khắc phục:

1. Bật `rewrite_module`.
2. Đổi `AllowOverride None` thành `AllowOverride All`.
3. Restart Apache.
4. Kiểm tra lại đúng `BASE_URL` trong `config.php`.

### CSS/JS không tải hoặc URL bị sai

Kiểm tra:

```php
define('BASE_URL', '/WEB_PHIM');
```

Giá trị này phải đúng với tên thư mục project dưới `C:\xampp\htdocs`.

### Lỗi kết nối database

Kiểm tra trong `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'web_film_demo');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Cũng cần xác nhận MySQL đang chạy trong XAMPP Control Panel và database đã được import.

### Lỗi `TMDB_API_TOKEN chưa được cấu hình`

Thiết lập biến môi trường `TMDB_API_TOKEN` hoặc thay placeholder trong `config.php`, sau đó chạy lại script đồng bộ.

### Lỗi `Call to undefined function curl_init()`

Extension cURL chưa được bật trong `C:\xampp\php\php.ini`. Bật:

```ini
extension=curl
```

rồi restart Apache.

### Chart.js không hiển thị

Dashboard dùng CDN Chart.js. Kiểm tra máy có kết nối Internet và mở DevTools Console để xem lỗi tải script. Phần số liệu thống kê PHP vẫn hoạt động ngay cả khi CDN không tải được.

---

## 13. Cấu trúc dự án

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

## 14. Các tính năng kỹ thuật

1. **Friendly URL** thông qua Apache Rewrite.
2. **Responsive UI** với drawer menu và movie-grid scroll snap ở mobile.
3. **ES6 Modules** cho JavaScript component.
4. **Live Search** không reload trang.
5. **TMDB Integration** dùng PHP cURL.
6. **TMDB Sync** có transaction, chuẩn hóa slug và genre mapping.
7. **Mood-based Recommendation** dựa trên quan hệ Many-to-Many.
8. **Bảo mật cơ bản**: prepared statements, output escaping, CSRF validation.
9. **Admin dashboard** với Chart.js.
10. **Nhiều thể loại trên một phim** qua `movie_genre`.

---

## 15. Phân công nhóm & Quy trình làm việc Git

### 15.1 Danh sách thành viên

Dự án được chia module và thực hiện bởi nhóm gồm 5 thành viên:

| STT | Họ và Tên | MSSV | Vai trò |
|---|---|---|---|
| 1 | [Họ tên bạn] | [MSSV] | Core Architecture & Tech Lead (Leader) |
| 2 | [Họ tên TV2] | [MSSV] | User Module & Social Interaction |
| 3 | [Họ tên TV3] | [MSSV] | Admin Management & Analytics |
| 4 | [Họ tên TV4] | [MSSV] | Search Engine & RESTful API |
| 5 | [Họ tên TV5] | [MSSV] | UI/UX, Mood Engine, Media & SEO |

### 15.2 Chi tiết nhiệm vụ từng thành viên

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

### 15.3 Phối hợp bắt buộc (tránh xung đột & lỗi dữ liệu)

| Tình huống | Quy tắc xử lý |
|---|---|
| TV2 và TV5 cùng sửa `views/movie.php` | TV2 chỉ sửa khối bình luận (cuối trang), TV5 chỉ sửa khối trailer modal (đầu trang). Luôn `git pull` trước khi code. |
| TV4 cần sửa `includes/functions.php` (file của TV1) | Báo trước TV1, tạo Pull Request riêng để TV1 review kỹ |
| TV2 và TV4 cùng thao tác bảng `comments` | Thống nhất tên cột/kiểu dữ liệu trước khi code |
| Bất kỳ ai cần đổi cấu trúc `database/schema.sql` | Phải báo TV1 trước — TV1 là người duy nhất merge thay đổi schema vào `main` |

### 15.4 Cấu trúc nhánh Git

```
main
├── feature/core              (TV1 - Leader)
├── feature/user-social        (TV2)
├── feature/admin-analytics    (TV3)
├── feature/search-api         (TV4)
└── feature/ui-mood-seo        (TV5)
```

### 15.5 Quy trình làm việc

1. Clone đúng nhánh của mình, nhớ chỉ định tên thư mục đích để khớp `BASE_URL`:
   ```bash
   git clone -b feature/<ten-nhanh> https://github.com/eeeza1b/WEB_PHIM.git WEB_PHIM
   ```
2. Code trong phạm vi thư mục/file được giao (mục 15.2)
3. Commit rõ ràng: `git commit -m "Thêm chức năng X"`
4. Push nhánh: `git push origin feature/<ten-nhanh>`
5. Tạo Pull Request trên GitHub → chờ TV1 review & merge vào `main`
6. Sau khi merge, chạy `git pull origin main` để đồng bộ trước khi code tiếp

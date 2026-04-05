# Checklist Blade & SEO (dự án Invest)

Tài liệu này thay cho mẫu dùng `layouts.app` (Laravel mặc định). Ứng dụng dùng **`Layout.Layout`**, **`Layout.LayoutLogin`**, **`Layout.LayoutAdmin`** trong `resources/views/Layout/`.

---

## 1. Chọn layout

| Loại trang | Extend | Vùng nội dung chính |
|------------|--------|----------------------|
| User đã đăng nhập hoặc trang chủ công khai (danh sách mã) | `Layout.Layout` | `@section('user-body-content')` |
| Khách: đăng nhập, đăng ký, quên/đặt lại mật khẩu | `Layout.LayoutLogin` | `@section('body-content')` |
| Admin | `Layout.LayoutAdmin` | `@section('admin-body-content')` |

**Không** dùng `@extends('layouts.app')` trừ khi sau này project thống nhất migrate sang đó.

---

## 2. Các `@section` thường gặp

### `Layout.Layout` (user / home)

- **`title`** — `<title>` trình duyệt (bắt buộc có ý nghĩa rõ).
- **`csrf-token`** — meta CSRF cho AJAX (nếu trang có form/AJAX).
- **`header-css`**, **`header-js`** — `@vite(...)` theo trang.
- **`actions-left`** — menu: `@include('partials.user-nav-primary')` hoặc `@include('partials.guest-nav-actions')`.
- **`actions-right`** — thanh tìm kiếm / nút (tuỳ trang).
- **`user-body-content`** — nội dung chính.
- **`user-script`** — script cuối `</body>` (tuỳ trang).

Tuỳ chọn: **`user-info`** (khối thông tin user trên cùng — nhiều trang đang comment).

### `Layout.LayoutLogin` (guest auth)

- **`title`**, **`csrf-token`**, **`header-css`**, **`header-js`**
- **`seo`** — xem mục 3 (nên có trên mọi trang auth công khai).
- **`body-content`** — form / nội dung chính.
- **`page-modals`**, **`login-script`** — modal + jQuery + `window.__pageData` nếu cần.

### `Layout.LayoutAdmin`

- **`title`**, **`csrf-token`**, **`header-css`**, **`header-js`**
- **`user-info`**, **`actions-left`**, **`actions-right`**
- **`admin-body-content`**

---

## 3. SEO & meta robots

### Partial chung: `partials.seo-public`

Tham số:

- **`pageTitle`** — OG/Twitter title (nên gồm tên site: `'Tiêu đề — ' . config('app.name')`).
- **`description`** — meta description + OG/Twitter (1–2 câu, không nhồi từ khóa).
- Tuỳ chọn: **`canonical`**, **`ogImage`** — mặc định lấy `request()->url()` và `route('site.logo')` (+ cache-bust `?v=filemtime`).

Ví dụ trong trang guest:

```blade
@section('seo')
    @include('partials.seo-public', [
        'pageTitle' => 'Đăng nhập — ' . config('app.name'),
        'description' => 'Mô tả ngắn, đúng nội dung trang.',
    ])
@endsection
```

### `Layout.Layout` — logic hiện tại

1. Nếu view định nghĩa **`@section('seo')`** → chỉ `@yield('seo')` (ghi đè hoàn toàn phần SEO trong layout).
2. Else nếu **`routeIs('home')`** → layout tự gắn `seo-public` + JSON-LD `WebSite` (trang chủ index được).
3. Else nếu **`auth()->check()`** → `<meta name="robots" content="noindex, follow">` (khu vực sau đăng nhập không cần index).

**Gợi ý:** Trang user sau login **không** cần `@section('seo')` — để `noindex` tự áp dụng. Trang công khai mới trên `Layout.Layout` (hiếm) có thể `@section('seo')` với `seo-public` hoặc chỉ `noindex` nếu không muốn index.

### `Layout.LayoutLogin`

Luôn có `@yield('seo')` ngay sau `<title>`. Mỗi trang auth công khai nên có `@section('seo')` (hoặc ít nhất `<meta name="robots" ...>` nếu không muốn index).

### `Layout.LayoutAdmin`

Chưa tích hợp `seo-public`; coi như khu vực riêng tư — không cần SEO tìm kiếm. Có thể thêm `noindex` trong layout admin sau này nếu cần thống nhất.

---

## 4. Thẻ heading (một `h1` rõ ràng)

- Mỗi URL nên có **một** `<h1>` đại diện chủ đề trang.
- Có thể dùng `@include('partials.page-title-invest', ['title' => '...', 'level' => 1])` (`level` 1 = `h1`, 2 = `h2`).
- Trang form auth: đặt tiêu đề chính trong `<h1>` (đã style trong `login.css` cùng nhóm với `h2` cũ).

---

## 5. Liên kết & route

- Trang chủ: **`route('home')`** (URL chuẩn `/trang-chu`; `/home` chỉ redirect 301).
- Đăng nhập / đăng ký / quên mật khẩu: **`route('login')`**, **`route('register')`**, **`route('forgotPassword')`**.
- Tránh hardcode `/login`, `/home` trong Blade/JS mới.

AJAX: truyền URL đầy đủ từ Blade (ví dụ `urlLoginPost: @json(route('login'))`) để đúng subdomain/path khi `APP_URL` có thư mục con.

---

## 6. `robots.txt` & sitemap

- Động tại **`/robots.txt`** (không đặt file tĩnh `public/robots.txt` trùng đường dẫn).
- Sitemap: **`/sitemap.xml`** — hiện chỉ liệt kê URL công khai (home + auth guest).

Sau khi thêm **trang công khai mới** có muốn index: cập nhật route `site.sitemap` trong `routes/web.php`.

---

## 7. Checklist trước khi merge (trang mới)

- [ ] Đã chọn đúng layout (`Layout` / `LayoutLogin` / `LayoutAdmin`).
- [ ] `@section('title')` mô tả đúng trang.
- [ ] Guest trên `LayoutLogin`: có `@section('seo')` với `partials.seo-public` (hoặc `noindex` có lý do).
- [ ] Trên `Layout.Layout`: nếu không phải `home` và user đã login — chấp nhận `noindex` mặc định; nếu là trang công khai đặc biệt thì cân nhắc `@section('seo')`.
- [ ] Một `h1` phù hợp nội dung.
- [ ] Liên kết nội bộ qua `route()` / `url()` đúng chức năng.
- [ ] Ảnh logo quan trọng: có `alt` có ý nghĩa (footer đã chuẩn hoá mẫu).
- [ ] Nếu thêm API gọi từ JS: endpoint lấy từ Blade/`route()` để khớp môi trường.

---

## 8. Tham chiếu file

| Mục đích | File |
|----------|------|
| SEO tags dùng lại | `resources/views/partials/seo-public.blade.php` |
| Layout user / home | `resources/views/Layout/Layout.blade.php` |
| Layout guest auth | `resources/views/Layout/LayoutLogin.blade.php` |
| Layout admin | `resources/views/Layout/LayoutAdmin.blade.php` |
| Tiêu đề banner | `resources/views/partials/page-title-invest.blade.php` |
| Route & sitemap | `routes/web.php` |

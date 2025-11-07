---
layout: default
title: Phát triển host downloader
nav_order: 4
---

# Hướng dẫn phát triển downloader cho host mới

Tài liệu này mô tả các bước tối thiểu để bổ sung khả năng tải cho một host chưa được Goader hỗ trợ.

## 1. Chuẩn bị môi trường

- Bảo đảm bạn đã cài đặt Goader ở chế độ development (clone repository hoặc chỉnh sửa trong dự án hiện tại).
- Kiểm tra rằng bạn có thể chạy `php bin/goader --help` và các lệnh khác thành công.
- Xác định host đích (ví dụ `example.com`), thu thập cấu trúc URL và yêu cầu xác thực (cookie/token).

## 2. Tạo lớp host mới

1. Tìm hoặc tạo cây thư mục phù hợp trong `src/Hosts/`. Với domain `example.com`, vị trí gợi ý là `src/Hosts/com/Example.php`.
2. Tạo lớp kế thừa `Puleeno\Goader\Abstracts\Host` và triển khai tối thiểu các phương thức:
   - `download()` (hoặc phương thức riêng bạn gọi từ đó): tải danh sách ảnh/nguồn và gọi `downloadImages()`.
   - `formatLink()` hoặc hàm chuyển đổi URL cụ thể nếu cần.
   - Ghi đè `defaultExtension`, `downloadClientOptions`, v.v. nếu host yêu cầu.

Ví dụ phác thảo:

```php
<?php
namespace Puleeno\Goader\Hosts\com;

use Puleeno\Goader\Abstracts\Host;

class Example extends Host
{
    const NAME = 'example.com';

    public function download()
    {
        $this->getContent();
        $images = $this->parseImages();
        $this->downloadImages($images);
    }

    protected function parseImages()
    {
        // Phân tích HTML/JSON và trả về mảng URL ảnh.
    }
}
```

## 3. Đăng ký host với hệ thống

Khi lớp nằm trong namespace đúng và đặt trong thư mục `src/Hosts/...`, downloader mặc định sẽ tự ánh xạ domain thành tên lớp (đảo ngược domain và viết hoa từng phần). Nếu cần custom logic (ví dụ host cần xử lý dạng đặc biệt), sử dụng filter `goader_downloader` trong plugin riêng:

```php
Hook::add_filter('goader_downloader', function ($callback, $host, $parts, $url) {
    if ($host === 'example.com') {
        return [new MyCustomDownloader($url, $parts), 'download'];
    }
    return $callback;
}, 10, 4);
```

## 4. Hỗ trợ đăng nhập/cookie (nếu cần)

- Đặt `$supportLogin = true` trong lớp host để bật cơ chế cookie jar.
- Ghi đè `loggin()` và `checkLoggedin()` để xử lý đăng nhập.
- Sử dụng lệnh `goader config host` để nhập cookie/token, đọc bằng `Environment::getUserGoaderDir()`.

## 5. Kiểm thử

- Dùng lệnh `goader download https://example.com/...` để kiểm tra.
- Nếu host trả nhiều chương, đảm bảo tùy chọn `--sequence`, `--prefix` hoạt động như mong đợi.
- Thêm log bằng `Logger::log()` để dễ debug.

## 6. Cập nhật tài liệu & phát hành

- Thêm host mới vào bảng `docs/host-support.md`.
- Ghi chú các yêu cầu đặc biệt (token, cookies, tham số CLI).
- Chạy lại GitHub Pages hoặc hệ thống build để đảm bảo trang docs cập nhật.

> Khuyến nghị đọc thêm tài liệu `docs/DEVELOPERS.md` để hiểu kiến trúc hook và pipeline CLI trước khi triển khai host mới.


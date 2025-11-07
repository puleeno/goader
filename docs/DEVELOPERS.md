---
layout: default
title: Hướng dẫn developer
nav_order: 2
---

Goader Developer Guide
======================

Tài liệu này dành cho developer muốn mở rộng hoặc bảo trì Goader. Nội dung bao gồm kiến trúc tổng quan, cơ chế hook, quy tắc viết plugin/host mới và các điểm cần lưu ý trong quá trình phát triển.

## Kiến trúc tổng quan

- Điểm vào chính: `bin/goader` nạp autoloader Composer, gọi `Goader::getInstance()->run()`.
- `Goader::__construct()` khởi tạo `Environment`, nạp plugin trong thư mục `plugins/` và đăng ký hook.
- Khi chạy, `Goader::run()` kích hoạt chuỗi hook `goader_init` rồi `goader_run`. Plugin sử dụng hook này để đăng ký lệnh, cấu hình, hành vi bổ sung.
- `_run()` trong `Goader` đọc tham số CLI (`Environment::getCommandArgs()`), áp dụng filter `register_goader_command` để tìm callable thực thi lệnh. Nếu không có lệnh hợp lệ, thông báo lỗi.

## CLI flow và core class

- `Command` bọc thư viện [`Commando`](https://github.com/nategood/commando) để quản lý tham số/tùy chọn CLI. Sử dụng `Command::getCommand()` để truy cập instance hiện tại.
- `Environment` cung cấp thông tin về thư mục cài đặt (`getGoaderDir()`), thư mục cấu hình người dùng (`getUserGoaderDir()`), thư mục làm việc (`getWorkDir()`), v.v. Trên Windows thư mục người dùng là `%HOMEDRIVE%%HOMEPATH%/Goader`, trên Unix là `~/.goader`.
- `Hook` là façade cho `voku\helper\Hooks`. Mọi hook/filter đều đi qua lớp này.
- `Logger` hiện chỉ ghi ra `STDOUT`, nhưng có khung để ghi file log (`logs/goader-*.log`).

## Hook & Filter quan trọng

| Hook/Filter | Mục đích |
|-------------|---------|
| `goader_load_plugins` / `goader_loaded_plugins` | Can thiệp vào quá trình nạp plugin.
| `goader_init` | Đăng ký lệnh, options, thiết lập môi trường trước khi CLI chạy.
| `goader_run` | Giai đoạn thực thi chính, `Goader::_run` được gắn tại đây.
| `register_goader_command` | Định tuyến lệnh CLI sang callable thực thi. Trả về mảng `[object, 'method']` hoặc callable.|
| `goader_download_init` | Điểm mở rộng trước khi khởi tạo downloader trong lệnh `download`.
| `goader_downloader` | Cho phép override downloader theo host.
| `custom_none_host` | Hỗ trợ chế độ `goader --help` hoặc các command không dựa trên URL.
| `image_sequence_file_name` | Tùy biến cách đặt tên file khi download sequence.
| `goader_allowed_convert_outputs` | Bổ sung định dạng hợp lệ cho `rename` hoặc `merge`.
| `goader_allowed_extract_outputs` | Bổ sung định dạng cho `extract`.
| `goader_bitmap_image_extension` | Tùy biến danh sách định dạng bitmap đa layer.
| `goader_wget_client_supported_options` | Mở rộng danh sách tham số mà client `Wget` hỗ trợ.
| `goader_extract_cookie_jar_file_name` | Ghi đè quy tắc tạo tên file cookie jar.
| `goaders` | Đăng ký danh sách host hỗ trợ thông qua `Environment::supportedHosters()`.

## Viết plugin/command mới

1. Tạo file PHP trong `plugins/` hoặc thư mục con (ví dụ `plugins/custom/my-command.php`).
2. Trong hook `goader_init`, đăng ký hàm/tùy chọn cần thiết và gắn filter `register_goader_command`.
3. Trả về callable thực thi lệnh khi nhận đúng từ khóa.

Ví dụ tối giản:

```php
<?php
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

Hook::add_action('goader_init', function () {
    Hook::add_filter('register_goader_command', function ($runner, $args) {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command !== 'hello') {
            return $runner;
        }

        $cli = Command::getCommand();
        $cli->option('name')->aka('n')->describedAs('Tên cần chào');

        return function () use ($cli) {
            $name = $cli['name'] ?: 'Goader';
            echo "Xin chào {$name}!" . PHP_EOL;
        };
    }, 10, 2);
});
```

## Thêm host downloader mới

- Tạo lớp kế thừa `Puleeno\Goader\Abstracts\Host` và triển khai các phương thức cần thiết (`download`, `formatLink`, v.v.).
- Đặt lớp vào namespace theo cấu trúc host, ví dụ `Puleeno\Goader\Hosts\com\Example` cho domain `example.com`.
- Mặc định, `Downloader` chuyển URL thành tên lớp bằng cách đảo ngược domain (`example.com` → `Puleeno\Goader\Hosts\com\Example`).
- Nếu muốn đăng ký host dưới tên khác hoặc chia sẻ logic, dùng filter `goader_downloader` để trả về callable/lớp tùy chỉnh.
- Sử dụng `downloadImages()` trong lớp `Host` để tải và ghi tệp thông qua client `Wget`.
- Có thể bật `supportLogin` hoặc `requiredLoggin` trong lớp kế thừa để kích hoạt cơ chế cookie jar và đăng nhập.

## Làm việc với cấu hình & cookie

- `Environment::getUserGoaderDir()` trả về thư mục cấu hình người dùng.
- Lệnh `config` ghi dữ liệu vào các file:
  - `accounts.dat`: danh sách tài khoản (mật khẩu đã mã hóa).
  - `tokens.dat`: token tải cho từng host.
  - `hosts/<domain>.json`: cookie jar ở dạng JSON.
- Khi cần truy cập các file này, ưu tiên gọi phương thức của `Config` hoặc `Environment` để đảm bảo đúng đường dẫn.

## Logging & Debug

- Dùng `Logger::log()` (hoặc `info`, `warning`, `error`) để ghi thông tin ra CLI.
- Trong quá trình phát triển có thể mở rộng `Logger::writeLogFile()` để lưu log vào `logs/goader-YYYY-MM-DD.log`.

## Phụ thuộc ngoài

- **PHP packages**: Commando (CLI), voku/helper (hook), Guzzle + PHPHtmlParser cho HTTP/DOM, v.v. Quản lý qua Composer.
- **Hệ thống**: cần cài ImageMagick (`convert`) cho lệnh `extract`/`merge`, và `wget` cho downloader mặc định.
- Đảm bảo các binary trên xuất hiện trong `PATH` của hệ thống khi chạy Goader.

## Quy ước & Gợi ý phát triển

- Duy trì mã nguồn ASCII trừ khi có lý do bất khả kháng.
- Sử dụng hook thay vì sửa trực tiếp core khi thêm tính năng mới để giữ khả năng nâng cấp.
- Mỗi plugin nên tự đăng ký tùy chọn CLI và giải phóng tài nguyên nếu có thao tác I/O tạm thời.
- Viết test thủ công bằng cách chạy lệnh CLI tương ứng; Goader chưa có test tự động, nên khuyến khích bổ sung khi mở rộng core.


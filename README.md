Goader CLI
==========

Goader là công cụ dòng lệnh giúp tải nội dung (đặc biệt là truyện tranh/webtoon) từ nhiều host khác nhau, đồng thời cung cấp bộ tiện ích xử lý ảnh, đổi tên và xuất file phục vụ quá trình dịch hoặc lưu trữ.

## Key features

- Tải toàn bộ chương/ảnh từ các host được hỗ trợ (xem danh sách chi tiết trong tài liệu).
- Tùy chọn `--sequence`, `--prefix`, `--offset` để kiểm soát tên file và thứ tự tải.
- Hệ thống plugin linh hoạt: có thể mở rộng command, hook và downloader mà không sửa core.
- Công cụ phụ trợ: đổi tên file hàng loạt, trích layer Photoshop, ghép ảnh bằng ImageMagick.
- Hỗ trợ cấu hình tài khoản, cookie và token theo host thông qua `goader config`.

## Requirements

- PHP 7.4+ với Composer.
- `wget` (dùng làm downloader mặc định) và ImageMagick `convert` cho các lệnh `extract`/`merge`.
- Hệ điều hành bất kỳ có thể chạy PHP CLI (Windows, macOS, Linux).

## Quick install

```bash
composer global require puleeno/goadher
# hoặc cài đặt trong dự án
composer require puleeno/goadher
```

Sau khi cài, bảo đảm thư mục `vendor/bin` nằm trong `PATH` rồi dùng lệnh `goader --help` để kiểm tra.

## Basic usage

- Tải chương từ URL: `goader download https://example.com/comic/chapter-1`
- Tải theo danh sách URL (mỗi dòng một link): `goader download file_list.txt`
- Cấu hình tài khoản/host: `goader config account --host=lezhin.com user pass`

Chi tiết từng command nằm trong tài liệu “[Hướng dẫn sử dụng](docs/user-guide.md)”.

## Bundled tools

- `goader rename`: đổi tên và đổi đuôi file hàng loạt, hỗ trợ slugify, sequence, prefix, jump.
- `goader extract`: trích layer từ file PSD/PSB/TIFF hoặc chuyển đổi hàng loạt ảnh.
- `goader merge`: ghép nhiều ảnh theo chiều ngang/dọc, chia nhóm tuỳ chỉnh.

## Development & extension

- Danh sách host hỗ trợ sẵn: xem “[Máy chủ được hỗ trợ](docs/host-support.md)”.
- Quy trình viết downloader cho host mới: “[Phát triển host mới](docs/host-development.md)”.
- Kiến trúc chi tiết, hệ thống hook và plugin: “[Hướng dẫn developer](docs/DEVELOPERS.md)”.

Để đóng góp, fork repository và tạo pull request. Vui lòng mô tả rõ host/command mới, cũng như cập nhật tài liệu liên quan.

## License

Dự án phát hành theo giấy phép MIT.
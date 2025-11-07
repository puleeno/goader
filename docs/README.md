layout: goader
title: Hướng dẫn sử dụng
nav_order: 1
---

Goader command line downloader
==============================

Tài liệu này mô tả toàn bộ các lệnh hiện được Goader hỗ trợ, bao gồm cú pháp, tham số và ví dụ thực tế. Các ví dụ giả định rằng bạn đã cài đặt Goader thông qua Composer và thêm `vendor/bin` vào biến môi trường `PATH`.

## Cách sử dụng chung

- Lệnh tổng quát: `goader [command] [tùy chọn] [đối số]`
- Có thể chạy trực tiếp `goader <URL>` để tải nội dung khi URL hợp lệ; Goader sẽ tự động suy đoán lệnh `download`.
- Tùy chọn toàn cục `--help` hiển thị thông tin trợ giúp.

Các lệnh chính:

- `download` – Tải hình ảnh hoặc nội dung từ host được hỗ trợ.
- `config` – Quản lý cấu hình (tài khoản, cookie, token).
- `rename` – Đổi tên hàng loạt tệp trong thư mục hiện tại.
- `extract` – Tách layer/ảnh từ tệp bitmap (PSD, PSB, TIFF, ...).
- `merge` – Ghép nhiều ảnh thành một ảnh dài.

## download

- Cú pháp: `goader download [tùy chọn] <URL>` hoặc `goader <URL>`
- Nhiệm vụ: Xác định host tương ứng, khởi chạy client và tải toàn bộ tài nguyên.

Tùy chọn:

- `--offset=<số>`: Đặt lại chương/tập bắt đầu tải (một số host có thể bỏ qua).
- `-p, --prefix=<chuỗi>`: Thêm tiền tố vào tên tệp tải về.
- `-s, --sequence`: Đổi tên tệp theo số thứ tự tăng dần; kết hợp được với `--prefix`.

Khi chạy `goader download --help`, hệ thống gọi client trợ giúp và hiển thị gợi ý sử dụng dành riêng cho lệnh tải.

Ví dụ:

```
goader download -s -p chapter01 https://example.com/comic/chap-1
```

## config

- Cú pháp chung: `goader config <nhóm> [tùy chọn] [đối số]`
- Các nhóm cấu hình hợp lệ: `account`, `host`, `core`.

### config account

- Cú pháp: `goader config account --host=<domain> <username> <password>`
- Chức năng: Lưu thông tin đăng nhập đã được mã hóa vào `~/.goader/accounts.dat` (hoặc `~/Goader` trên Windows).
- Ghi chú: Hệ thống yêu cầu cả tài khoản và mật khẩu; Goader sẽ mã hóa mật khẩu trước khi lưu.

### config host

- Cú pháp: `goader config host --name=<domain> [--load-cookies=<file>] [--load-cookiejar=<file>] [--token=<token>]`
- `--load-cookies`: Nhập tệp `cookies.txt` xuất từ trình duyệt, tự động chuyển sang định dạng cookie jar JSON.
- `--load-cookiejar`: Nhập trực tiếp cookie jar JSON hợp lệ.
- `--token`: Lưu token tải cho host được chỉ định vào `tokens.dat`.
- Thư mục cấu hình cá nhân của người dùng sẽ được tạo tự động khi cần.

### config core

- Dự phòng cho việc cấu hình nhân Goader; hiện tại chưa có tham số bổ sung và không thực hiện hành động nào.

## rename

- Cú pháp: `goader rename [tùy chọn]`
- Chức năng: Đổi tên/định dạng lại hàng loạt tệp trong thư mục hiện tại. Các tệp được chọn thông qua mẫu đuôi mở rộng (mặc định là đuôi xuất hiện nhiều nhất).

Tùy chọn chính:

- `-f, --format=<định_dạng>`: Chuyển toàn bộ tệp sang đuôi mới (ví dụ `jpg`).
- `-s, --sequence`: Đổi tên theo số thứ tự; kết hợp với `-b` để đặt số bắt đầu và `-j` để tùy chỉnh bước nhảy.
- `-o, --output=<thư_mục>`: Ghi tệp kết quả sang thư mục khác (mặc định là thư mục hiện tại).
- `-b, --begin=<số>`: Số thứ tự bắt đầu khi dùng `--sequence`.
- `-e, --extension=<ds-đuôi>`: Chỉ xử lý các đuôi được liệt kê (phân tách bằng dấu phẩy).
- `-j, --jump=<số>`: Tăng chỉ số theo bước tùy chỉnh khi dùng `--sequence`.
- `-r, --raw`: Giữ nguyên tên tệp (không slugify).
- `-p, --prefix=<chuỗi>`: Thêm tiền tố vào tên mới.

Quy trình chạy:

1. Tạo thư mục tạm trong thư mục đích.
2. Đổi tên/đổi đuôi từng tệp, ghi log cho từng lần đổi.
3. Chuyển kết quả từ thư mục tạm sang thư mục đích và xóa thư mục tạm.

## extract

- Cú pháp: `goader extract [tùy chọn]`
- Chức năng: Tách layer từ tệp bitmap (PSD/PSB/TIFF) hoặc chuyển đổi hàng loạt ảnh, sử dụng lệnh `convert` của ImageMagick.
- Thư mục đầu ra mặc định: `./Extracted` (tạo mới nếu chưa tồn tại).

Tùy chọn:

- `-f, --format=<định_dạng>`: Định dạng ảnh đầu ra (mặc định `jpg`).
- `-o, --output=<thư_mục>`: Thư mục lưu ảnh.
- `-n, --num=<số_lượng>`: Khi tách layer PSD, giới hạn số layer tối đa mỗi tệp.
- `-b, --begin=<số>`: Số thứ tự bắt đầu cho tên ảnh đầu ra.
- `-a, --all`: Khi làm việc với PSD/PSB/TIFF, trích toàn bộ layer thành một ảnh duy nhất.

Yêu cầu: Cần cài đặt ImageMagick (`convert`) và đảm bảo có mặt trong `PATH`.

## merge

- Cú pháp: `goader merge [tùy chọn] [danh_sách]`
- Chức năng: Ghép nhiều ảnh thành một ảnh duy nhất theo chiều ngang hoặc dọc, sử dụng ImageMagick.
- Thư mục đầu ra mặc định: `./Merged`.

Tùy chọn:

- `-f, --format=<định_dạng>`: Định dạng ảnh sau khi ghép (mặc định `jpg`).
- `-o, --output=<thư_mục>`: Thư mục lưu ảnh ghép.
- `-n, --num=<số>`: Nếu không truyền danh sách cắt nhóm, tự động chia theo số lượng ảnh mỗi nhóm.
- `-m, --mode=vertical|horizontal|v|h`: Chọn chiều ghép (`vertical`/`v` ghép dọc, `horizontal`/`h` ghép ngang).
- `-b, --begin=<số>`: Số thứ tự bắt đầu cho tên ảnh ghép.

Đối số vị trí:

- Nếu truyền danh sách số (ví dụ `10 20 30`), Goader chia danh sách ảnh theo chỉ số và ghép lần lượt.
- Nếu truyền danh sách đường dẫn/định danh tệp, Goader sẽ ghép chính xác các ảnh được chỉ định. Với tệp bitmap đa layer, Goader chỉ lấy layer đầu tiên `[0]` để ghép.

## Trợ giúp

- `goader --help`: Hiển thị trợ giúp tổng quát.
- `goader download --help`: Gọi trình trợ giúp riêng cho lệnh `download`.
- Có thể mở rộng chức năng trợ giúp thông qua plugin (`plugins/core/help.php`).

## Ghi chú mở rộng

- Các lệnh sử dụng hook (`Hook::add_filter` / `Hook::add_action`) nên người dùng có thể thêm lệnh mới bằng cách tạo plugin và đăng ký vào hook `register_goader_command`.
- Thư mục cấu hình cá nhân được tạo tại `~/.goader` (Linux/macOS) hoặc `%HOMEDRIVE%%HOMEPATH%/Goader` (Windows).
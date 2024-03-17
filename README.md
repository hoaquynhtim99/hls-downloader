# hls-downloader
Tải video MP4 từ các luồng stream 

## Yêu cầu

- Windows cần cài [GIT-SCM](https://git-scm.com/download/win) linux khỏi cần
- PHP 8.2+
- ffmpeg tải tại đây https://ffmpeg.org/download.html

## Hướng dẫn

Tải kho code về, tại thư mục chứa file download.sh chạy

```bash
bash download.sh https://url-to-playlist-files.m3u8
```

Bạn cũng có thể chạy đa luồng để tốc độ download cao hơn

```bash
bash multi-thread/download.sh https://url-to-playlist-files.m3u8
```

## Ghi chú

Tại thời điểm hiển tại tool chỉ hỗ trợ tải các luồng phát dạng **MPEG transport stream** và không mã hóa hay giới hạn quyền, nếu bạn có các luồng khác xin vui lòng tạo issue để thảo luận.

Tìm thêm thông tin liên hệ với tác giả tại https://writeblabla.com/ nếu bạn không nhận được phản hồi trên issue.

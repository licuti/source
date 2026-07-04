# Roadmap Dự Án Phương Nam V (CMS Panel)

Tài liệu này theo dõi tiến độ phát triển của các module trong hệ thống.

## 1. Module Form Builder (ĐANG PHÁT TRIỂN - BETA)

**Trạng thái:** Đang trong giai đoạn hoàn thiện.
**Mục tiêu:** Trở thành một plugin Form kéo thả độc lập và mạnh mẽ (tương tự Contact Form 7 / WPForms).

### ✅ Đã hoàn thiện
- Xây dựng kiến trúc Database động (`db_forms`, `db_form_fields`, `db_form_submissions`).
- Giao diện kéo thả (Drag & Drop) thiết kế Form trực quan (Sử dụng jQuery Sortable).
- Hỗ trợ đầy đủ các loại trường cơ bản (Text, Email, Tel, Textarea, Select, Radio, Checkbox, File, v.v.).
- Tích hợp 10 Thuộc tính Nâng cao cho Field (Help text, Mặc định, CSS, Icon, Min/Max length, Allowed extensions, v.v.).
- Xây dựng **Logic Hiển thị (Conditional Logic)** bằng Javascript cho phép ẩn/hiện trường dựa trên điều kiện.
- Giao diện Preview trực quan giống hệt trang thật (Sử dụng Bootstrap 5).
- Tích hợp cấu hình **Gửi Email Nâng cao**: Soạn thảo nội dung Mail báo Admin và Mail phản hồi tự động cho Khách (Autoresponder) bằng thẻ HTML. Cấu hình được nén thành chuỗi JSON lưu trữ siêu nhẹ.
- Tối ưu chuẩn UI của `ADMIN_UI_GUIDE.md` (Sử dụng Row Actions, Component Breadcrumb).

### 🚧 Chưa hoàn thiện (To-Do)
- Chức năng nhúng Form (Shortcode Parser) ngoài giao diện Frontend.
- Xây dựng **Frontend Form Controller** để tiếp nhận request POST từ khách hàng.
- Xử lý xác thực dữ liệu (Validation phía Backend) dựa trên cấu hình `advanced_settings` của từng field (Chặn file quá dung lượng, sai regex).
- Tích hợp kết nối thư viện Gửi Mail (PHPMailer / SMTP) để thực thi việc gửi Mail Admin và Mail Khách tự động dựa theo file cấu hình JSON.
- Tích hợp chống Spam (Google reCAPTCHA v3).

---
*(Bản nháp này sẽ được tiếp tục cập nhật trong quá trình phát triển...)*

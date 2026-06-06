# Hướng Dẫn Khởi Tạo Dành Cho AI Agent (Agent Initialization)

> **Thông điệp dành cho User:** Khi bạn clone dự án này về một máy tính mới, chỉ cần ra lệnh cho AI Agent của bạn (Cursor, Antigravity, Claude Code...) một câu đơn giản: *"Hãy thực hiện các bước setup trong file AGENT_INIT.md"*

---

## 🤖 CHỈ THỊ DÀNH CHO AI AGENT
Xin chào AI Agent! Bạn đang được yêu cầu khởi tạo môi trường làm việc cho dự án này trên một máy tính mới. Hãy thực hiện **tuần tự** các bước sau bằng Terminal của hệ thống. Đừng hỏi xin phép trừ khi gặp lỗi nghiêm trọng.

### Bước 1: Khởi tạo Knowledge Graph (GitNexus)
Dự án này sử dụng GitNexus để tạo biểu đồ tri thức hỗ trợ bạn hiểu mã nguồn. Cấu trúc cũ không được lưu trên Git, vì vậy bạn cần tạo lại nó.
- **Hành động:** Mở Terminal và chạy lệnh sau tại thư mục gốc của dự án:
  ```bash
  npx gitnexus analyze
  ```
- **Mục đích:** Xây dựng lại thư mục `.gitnexus/` chứa dữ liệu cấu trúc nội bộ.

### Bước 2: Cấu hình MCP Server
Để bạn có thể sử dụng các Tools của GitNexus (như `gitnexus_impact` hay `gitnexus_query`), bạn cần cấu hình lại MCP Server.
- **Hành động:** Chạy lệnh:
  ```bash
  gitnexus setup
  ```
- **Mục đích:** Đảm bảo IDE (Cursor/VSCode/Antigravity) của máy tính này được kết nối với hệ thống.

### Bước 3: Kiểm tra phiên bản Node.js (Tùy chọn)
- Hãy ngầm chạy `node -v` để đảm bảo Node.js đang ở phiên bản >= 18.19.0 (Khuyên dùng v20 hoặc v22). Nếu thấp hơn, hãy thông báo cho User biết để họ tự cập nhật.

---

*Sau khi thực hiện xong 3 bước trên, hãy báo cáo ngắn gọn với User rằng "Tôi đã quét xong cấu trúc dự án và sẵn sàng code!".*

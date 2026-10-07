# Tài liệu Student - TRUNG TÂM NGOẠI NGỮ AROMA

## 1. Tổng quan giao diện chung

### Layout chung
- **Header** (cam, fixed top):
  - Logo "MasterStudy - Giáo viên riêng của bạn" (góc trái)
  - Thanh tìm kiếm (giữa)
  - Chuông thông báo + Avatar + Tên user "Duy Kiên 1117" (góc phải)
- **Sidebar** (trái, xám nhạt):
  - Tổng quan
  - Các khoá học
  - Lịch học
  - Nhận xét tháng
  - Nội quy lớp học
- **Main content** (giữa, nền xám nhạt)
- **Footer** (cam, fixed bottom):
  - Logo + "Về chúng tôi" + "Tuyển dụng"
  - "Địa chỉ: 181 Cao Thắng, Block B, Charmington La Pointe Building, Phường Hoà Hưng, TPHCM"
  - "Liên hệ: 0903861248"
  - Nút **Chat** (góc phải, nổi)

## 2. Tổng quan (Dashboard)

**Mục đích:** Trang chủ hiển thị tổng quan cho học sinh.

### Layout
- Sidebar tương tự các trang khác
- Main content hiển thị: có thể là danh sách các lớp đang học, buổi học sắp tới, thông báo

> *Trang chủ Student hiển thị các thông tin tổng quan: lớp đang học, lịch sắp tới, trạng thái BTVN/test*

## 3. Các khóá học

### Layout
- Sidebar
- Main content có 2 phần:

#### 3.1. Lộ trình (mở rộng theo danh mục)
- Hiển thị dạng card grid, mỗi lớp là một section
- VD: "Toán 1-1"
- Bên trong nhóm theo **Danh mục** (VD: Math Problems, Math IQ, Giải đề thi tổng hợp các môn)
- Mỗi danh mục có thể **mở rộng (^)** hoặc **thu gọn (v)**

**Mỗi card chuyên đề:**
- Tên chuyên đề (VD: "How to calculate", "Model Intermediate")
- Đã học: X/Y buổi
- Điểm test: -- %
- Điểm minitest: -- %
- Nút **Nộp test** (màu cam, active) hoặc **Nộp test** (xám, disabled - khi chưa đến số buổi tối đa)

#### 3.2. Nội dung buổi học
- Hiển thị các buổi học của chuyên đề đang chọn
- Mỗi buổi là một card với header: "Buổi học Thứ X dd/M"
- Card buổi hiện tại (mở) hiển thị các ô:
  - **Chuyên đề** (VD: "How to calculate / IQ nhóm 5")
  - **BTVN** (VD: "Học 2 chuyên đề trên BTVN: bài trong sách")
  - **Hạn nộp** (VD: "Hạn nộp: 20:00 24/07/2026")
  - **Nhận xét buổi học** (VD: "Nhận xét buổi học: hôm nay học rất là nhanh")
  - **Tệp đính kèm** (nếu có)
  - Nút **Nộp BTVN** (cam)
  - Trạng thái: "Chưa nộp BTVN" (đỏ, italic)

**Trạng thái buổi học (mặc định khi chưa tới ngày):**
> *\*\*chỉ khi tới ngày học thì các ô buổi học này mới được mở ra*

- Ô hiển thị: "Chưa chọn chuyên đề", "Chưa có BTVN", "Chưa có hạn nộp", "Chưa có nhận xét", "Trống"
- Nút **Nộp BTVN** (cam)
- Trạng thái: "Chưa giao BTVN" (đỏ, italic)

> *\*\*được mở ra thì mặc định đề nút Nộp bài dù GV chưa giao BTVN*

**Buổi tương lai (chưa diễn ra):**
- Hiển thị: "Chưa diễn ra" (đỏ, italic)

## 4. Chi tiết buổi học

Hiển thị chi tiết của một buổi học cụ thể từ lịch:
- Thông tin buổi học: tên chuyên đề, ngày giờ, giáo viên
- BTVN: nội dung, hạn nộp
- Tệp đính kèm từ GV
- Nhận xét của GV
- Nút **Nộp BTVN** nếu có

## 5. Chi tiết khóa học - Hoàn thành chuyên đề

### Layout
- Sidebar
- Header: Tên lớp (VD: "Toán 1-1") + "Lộ trình" (text)
- Danh sách chuyên đề (VD: "Math Problems")

**Mỗi card chuyên đề:**
- Tên: "How to calculate"
- Trạng thái: "Đã dạy: 4/4 buổi" (đỏ)
- Mục tiêu then chốt (textarea): "..... có sẵn ....."
- Section **Bài test chuyên đề** (mở rộng ^):
  - Tên file: `test-how-to-calculate.pdf` + icon download
  - **Bài nộp của học sinh:** 4 ô upload (icon download)
  - Hạn nộp: "Hạn nộp: bạn còn 2 giờ để nộp" (đỏ)
  - Nút **Nộp bài** (cam)
  - **Nhận xét của giáo viên:** 2 ô input (text)
  - File chấm: `test-how-to-calculate-graded.xlsx` + icon download
- Section **Minitest chuyên đề** (mở rộng ^):
  - Tên file: `minitest-how-to-calculate.pdf`
  - (Cấu trúc giống bài test)
  - Note: *\*Admin không duyệt minitest thì học sinh không nộp bài được ở đây*

**Ghi chú:**
> *Hạn nộp: bạn đã nộp trễ 2 phút*

## 6. Chi tiết khóa học - Test (Test.png - góc nhìn học sinh)

**Card chuyên đề** (Math Problems → How to calculate):
- Tên chuyên đề
- Trạng thái: "Đã dạy: 4/4 buổi"
- Mục tiêu then chốt (textarea)

**Bài test chuyên đề** (mở rộng):
- File: `test-how-to-calculate.pdf` + download icon
- Bài nộp của học sinh: 4 ô upload
- Hạn nộp (đỏ): "Hạn nộp: bạn đã nộp trễ 2 phút"
- Nút **Nộp bài** (cam)
- Nhận xét của giáo viên: 2 ô input
- File: `test-how-to-calculate-graded.xlsx` + download icon

**Minitest chuyên đề** (mở rộng):
- File: `minitest-how-to-calculate.pdf`
- Bài nộp: 4 ô upload
- Hạn nộp (đỏ)
- Nút **Nộp bài** (cam)
- Nhận xét: 2 ô input
- File: `minitest-how-to-calculate-graded.xlsx` + download icon

## 7. Chi tiết khóa học - Test (Test (2).png - góc nhìn admin chấm)

**Bài test chuyên đề** (Math Problems → How to calculate):
- Tên chuyên đề
- Trạng thái: "Đã dạy: 4/4 buổi"
- Mục tiêu then chốt: "..... có sẵn ....."

**Bài test chuyên đề** (mở rộng):
- File: `test-how-to-calculate.pdf` + download
- **Bài nộp của học sinh:** 4 ô upload
- Hạn nộp (đỏ): "Hạn nộp: bạn còn 2 giờ để nộp"
- Nút **Nộp bài**
- **Nhận xét của giáo viên:** 2 ô input
- File chấm: `test-how-to-calculate-graded.xlsx` + download
- Nút **Cập nhật** (cam, góc phải)

**Minitest chuyên đề** (mở rộng):
- File: `minitest-how-to-calculate.pdf` + download
- Bài nộp: 4 ô upload
- Hạn nộp (đỏ): "Hạn nộp: bạn còn 2 giờ để nộp"
- Note: *\*Admin không duyệt minitest thì học sinh không nộp bài được ở đây*
- Nút **Nộp bài**
- Nhận xét: 2 ô input
- File: `minitest-how-to-calculate-graded.xlsx` + download
- Nút **Cập nhật**

## 8. Chi tiết Nộp test (Trang Chi tiết Nộp test.png)

**Card chuyên đề** (Toán 1-1 → Math Problems → How to calculate):
- Tên chuyên đề
- Trạng thái: "Đã dạy: 4/4 buổi" (đỏ)
- Mục tiêu then chốt (textarea)

**Bài test chuyên đề** (mở rộng):
- File: `test-how-to-calculate.pdf` + download
- **Bài nộp của học sinh:** 4 ô upload
- Hạn nộp: "Hạn nộp: bạn còn 2 giờ để nộp" (đỏ, bên trái)
- Nút **Nộp bài** (cam, bên phải)
- **Nhận xét của giáo viên:** 2 ô input + File `test-how-to-calculate-graded.xlsx` + download

**Minitest chuyên đề** (mở rộng):
- File: `minitest-how-to-calculate.pdf` + download
- **Bài nộp của học sinh:** 4 ô upload
- Hạn nộp: "Hạn nộp: bạn còn 2 giờ để nộp" (đỏ)
- Nút **Nộp bài** (cam)
- *\*Admin không duyệt minitest thì học sinh không nộp bài được ở đây*
- **Nhận xét của giáo viên:** 2 ô input + File `minitest-how-to-calculate-graded.xlsx` + download

## 9. Lịch học (Calendar view)

### Layout
- Sidebar
- Tiêu đề: "Lịch học"
- Nút prev/next + "Tháng 7 / 2026"

### Calendar grid
- Cột 1: **Khung giờ** (7-8, 8-9, ...)
- Cột 2-8: **Thứ 2 (20/07) → Chủ nhật (26/07)**
- Mỗi ô có thể chứa:
  - Tên lớp (VD: "Toán 1-1", "Tiếng Anh 1-1", "Lớp online chung")
  - Link **Xem chi tiết** (gạch chân)

### Màu sắc ô lịch
- **Đỏ đậm** = Buổi học sắp tới
- **Hồng nhạt** = Buổi học đã hoàn thành
- **Xám** = Buổi học đã nghỉ

### Chú thích (phía dưới calendar)
- 🟥 **Buổi học sắp tới**
- 🌸 **Buổi học đã hoàn thành**
- ⬜ **Buổi học đã nghỉ**

## 10. Nhận xét tháng (collapsed list)

### Layout
- Sidebar
- Tiêu đề: "Nhận xét tháng"
- Danh sách các lớp đang học dạng card:

**Mỗi card:**
- Icon avatar (hình tốt nghiệp)
- Tên lớp (VD: "Lớp online chung - Tiếng Việt", "Toán 1-1", "Tiếng Anh 1-1")
- Tên lớp con: "Lớp 5 - Tiếng Việt", "Lớp 5 - Toán"
- Thời gian: "Thứ 2 19:00 - 20:30" (có thể nhiều dòng: "Thứ 2 19:00 - 20:30", "Thứ 4 19:00 - 20:30")
- Icon mũi tên xuống (collapse/expand)

## 11. Nhận xét tháng (expanded)

Khi mở rộng một card lớp:
- Tên lớp + mũi tên lên
- Dropdown chọn tháng: "Tháng 7 / 2026" + icon mũi tên xuống
- Khung nội dung: "Hiện text do giáo viên nhập ....." (placeholder)

> *GV nhập nội dung nhận xét cho từng tháng, HS chỉ xem*

## 12. Nội quy lớp học

### Layout
- Sidebar
- Tiêu đề: "Nội quy lớp học"
- Khung hiển thị nội dung (read-only):
  - Placeholder: "Hiện text nội quy admin nhập ....."
  - Nội dung từ admin (rich text)

> *Nội quy được admin nhập ở module "Quản lý nội quy" trong admin panel, hiển thị read-only cho học sinh*

## 13. Tương tác & quy tắc chung

### Quy tắc nộp bài
- **Hạn nộp test/minitest:** "Hạn nộp: bạn còn 2 giờ để nộp" (đếm ngược)
- **Nộp trễ:** "Hạn nộp: bạn đã nộp trễ 2 phút" (đỏ)
- **Minitest:** *Admin không duyệt minitest thì học sinh không nộp bài được ở đây*

### Quy tắc buổi học
- *\*\*chỉ khi tới ngày học thì các ô buổi học này mới được mở ra*
- *\*\*được mở ra thì mặc định đề nút Nộp bài dù GV chưa giao BTVN*
- Buổi tương lai: "Chưa diễn ra"
- Buổi chưa mở: hiển thị placeholder ("Chưa chọn chuyên đề", "Chưa có BTVN", "Chưa có hạn nộp", "Chưa có nhận xét", "Trống")

### Nộp test
- Nút **Nộp test** (cam, active) - khi đã đủ buổi học
- Nút **Nộp test** (xám, disabled) - khi chưa đủ buổi
- 4 ô upload cho bài nộp
- File test (PDF) hiển thị để học sinh tải về làm

### Nhận xét từ GV
- 2 ô input hiển thị nhận xét (text)
- File chấm (xlsx) - file kết quả chấm điểm

### Lịch học
- 7 cột: Thứ 2 → Chủ nhật
- Mỗi cột hiển thị ngày/tháng
- Cell có thể có nhiều lớp cùng khung giờ
- Link "Xem chi tiết" để xem buổi học

### Footer
- Logo + Về chúng tôi + Tuyển dụng
- Địa chỉ: "181 Cao Thắng, Block B, Charmington La Pointe Building, Phường Hoà Hưng, TPHCM"
- Liên hệ: "0903861248"
- Nút **Chat** (góc phải, floating button)

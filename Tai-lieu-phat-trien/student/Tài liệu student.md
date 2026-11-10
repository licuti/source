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
- Sidebar: Tổng quan, Các khoá học, Lịch học, Nhận xét tháng, Nội quy lớp học.
- Không hiển thị H1 “Tổng quan”; vào trang là 2 cột nội dung.
- Cột trái (2/3):
  - **Buổi học sắp tới**: card gần nhất còn hiệu lực — tên lớp, ngày, giờ, trạng thái “Sắp diễn ra”; bấm mở chi tiết buổi học.
  - **Các khoá học của tôi**: lưới card 2 cột — avatar mặc định, tên lớp, các dòng lịch cố định (`Thứ 2 19:00 - 20:30`); bấm mở chi tiết lớp.
- Cột phải (1/3): **Thông báo** — mỗi card gồm tiêu đề (tên lớp/ngữ cảnh), nội dung, nút **Xem chi tiết** mở link thông báo.
- Không dùng bảng thống kê/table; dùng card/lưới giống mockup `Trang chủ Student.png`.

### Nguồn dữ liệu
- Lớp: `db_lms_class_students` + `db_lms_classes`; lịch từ `db_lms_cycle_schedules` đang hiệu lực.
- Buổi sắp tới: `db_lms_sessions` có `status = upcoming` và thời gian >= hiện tại, thuộc lớp HV.
- Thông báo: `db_lms_notifications` (`title`, `message`, `link_url`) — tối đa 3 thông báo mới nhất.
- Học viên chỉ xem được buổi của lớp mình; guard chuyên đề chỉ chặn khi buổi gắn chuyên đề không thuộc lớp, không chặn buổi chưa gán chuyên đề.

> *Trạng thái triển khai (03/11/2026): đã dựng lại Trang chủ theo mockup; đã test hv01–hv05.*

## 3. Các khóá học

### Layout
- Mockup tham chiếu: `Tai-lieu-phat-trien/student-ver2/Trang Các khóa học.png`.
- Header gồm tiêu đề **“Các khoá học của tôi”** và link **“Xem tất cả >”** bên phải.
- Lưới **2 cột** (`row g-3` + `col-lg-6`), dùng chung card `.lmnx-card` với trang Nhận xét tháng:
  - Ảnh bìa minh hoạ theo môn: Toán / Tiếng Anh / Tiếng Việt; môn khác fallback icon sách.
  - Tên lớp.
  - Dòng phụ: **khối học viên** (VD: “Lớp 5”).
  - Các dòng lịch có icon lịch: `Thứ 2 19:00 - 20:30`.
  - Trạng thái BTVN in nghiêng, căn phải dưới card:
    - **Giáo viên chưa giao BTVN** (đỏ)
    - **Học sinh chưa nộp BTVN** (đỏ)
    - **Học sinh đã nộp BTVN** (xanh)
    - **Giáo viên đã chấm BTVN** (xanh)
  - Bấm cả thẻ → trang chi tiết lớp (`lms-khoa-hoc/lop?class_id=...`).
- Không dùng avatar icon, không hiện môn riêng dưới tên lớp, không nhóm theo môn, không thanh tiến độ, không hiện mã lớp/giáo viên trên thẻ.

### Nguồn dữ liệu
- Lớp: `db_lms_class_students` + `db_lms_course_cycles` + `db_lms_cycle_students` (cycle active).
- Khối: `db_lms_students.grade`.
- Lịch: `MonthlyReviewService::scheduleRowsMap()` — ưu tiên `db_lms_cycle_schedules` đang hiệu lực, fallback `db_lms_schedules`.
- Trạng thái BTVN: lấy buổi gần nhất có `hw_content`, kết hợp `session_students` của HV.

> *Trạng thái triển khai (06/11/2026 - student-ver2 & teacher-ver2):*
> 1. **Chi tiết khóa học (`lms-khoa-hoc/lop?class_id=...`):**
>    - Nhóm theo Danh mục môn học (Math Problems, Math IQ,...).
>    - Card chuyên đề: Icon bên trái, Tên chuyên đề, Đã dạy: X/Y buổi, Điểm test: XX%, Điểm minitest: XX%.
>    - Nút hành động: Hiển thị song song 2 nút **"Nộp test"** và **"Nộp mini test"** (sáng cam khi bài đã được Admin duyệt, xám disabled khi chưa duyệt) dẫn tới trang nộp test (`lms-khoa-hoc/chuyen-de`); hoặc huy hiệu xanh lá **"Đã hoàn thành"** khi cả 2 bài đều đã có điểm.
>    - Accordion **Lesson Summary & BTVN**: Lưới 2 cột (`col-md-6`) các buổi học với các pill thông tin (Chuyên đề, BTVN, Hạn nộp, Nhận xét, Tệp đính kèm) + nút **Nộp BTVN** dẫn tới trang Chi tiết buổi học (`lms-lich-hoc/buoi?id=...`). Buổi tương lai hiển thị *Chưa diễn ra*.
> 2. **Trang Chi tiết Nộp test (`lms-khoa-hoc/chuyen-de?class_id=...&topic_id=...`, đối chiếu `student-ver2/Trang Chi tiết Nộp test.png`):**
>    - Tách riêng hoàn toàn khỏi trang nộp BTVN (không chứa danh sách buổi học).
>    - Card 1: Tên chuyên đề, Đã dạy: X/Y buổi, Mục tiêu then chốt (nội dung tóm tắt chuyên đề).
>    - Card 2 (Bài test chuyên đề) & Card 3 (Minitest chuyên đề): File đề bài PDF kèm link tải; vùng kéo thả/chọn tối đa 5 tệp bài làm; Hạn nộp đếm ngược / báo nộp trễ hạn; Nút cam **Nộp bài**. Khi Admin chưa duyệt hiển thị ghi chú đỏ: `*Admin không duyệt minitest thì học sinh không nộp bài được ở đây` và nút Nộp bài bị khóa. Phần Nhận xét của GV hiển thị điểm số, tỷ lệ % và file chấm đính kèm.
> 3. **Trang Chi tiết buổi học — Nộp BTVN (`lms-lich-hoc/buoi?id=...`, đối chiếu `student-ver2/Trang Chi tiết buổi học.png`):**
>    - Section 1 (`Lesson Summary & BTVN`): Card buổi học chỉ đọc cho học viên với 5 pill thông tin (`Chọn các chuyên đề *`, `BTVN`, `Hạn nộp`, `Nhận xét buổi học`, `Tệp đính kèm`), **không** hiển thị nút "Cập nhật" (nút "Cập nhật" chỉ dành cho giáo viên/admin).
>    - Section 2 (`Bài tập về nhà`): Học viên tải tệp bài nộp (tối đa 5 tệp) kèm thông báo hạn nộp đếm ngược và nút cam **Nộp bài**. Phần **"Nhận xét của giáo viên"** chỉ hiển thị sau khi được **Admin duyệt Bài tập về nhà** (`db_lms_session_students.status = 'approved'` tại `lms-lop/buoi?id=...`).

#### 3.1. Chi tiết lớp — Lộ trình (mở rộng theo danh mục)
- Mỗi danh mục là một accordion, có thể **mở rộng (^)** hoặc **thu gọn (v)**.

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
- Khung lưới dùng component chung `calendar_week` (như Admin "Lịch học" + Teacher "Quản lý lịch"); thẻ trong ô dùng `calendar_week_event`.
- Cột 1: **Khung giờ** (7-8, 8-9, ...) — mỗi hàng cao 72px (`--lms-cal-hour-h`)
- Cột 2-8: **Thứ 2 (20/07) → Chủ nhật (26/07)**
- Mỗi ô chứa thẻ buổi học:
  - Tên lớp (VD: "Toán 1-1", "Tiếng Anh 1-1", "Lớp online chung")
  - Dòng khung giờ `HH:MM–HH:MM`
  - Link **Xem chi tiết** (gạch chân)
- **Giờ bắt đầu lẻ → thẻ nằm so le trong hàng giờ** (`--lms-cal-top` = phút/60 × 72px: 17:30 lệch 36px) và **chiều cao thẻ = thời lượng** (`--lms-cal-height`: 17:30–19:00 cao 108px, tràn xuống ô 18:00). Hai thẻ chồng giờ nhau trong cùng cột ngày được chia lane (`lms_cal_lanes_for_rows`).

### Màu sắc ô lịch
Thẻ mang màu đậm (`.lms-cal-event--*`), nền ô nhạt cùng tông (`.lms-cal-cell--*`):
- **Đỏ đậm** = Buổi học sắp tới
- **Hồng nhạt** = Buổi học đã hoàn thành
- **Xám** = Buổi học đã nghỉ

### Chú thích (phía dưới calendar)
- 🟥 **Buổi học sắp tới**
- 🌸 **Buổi học đã hoàn thành**
- ⬜ **Buổi học đã nghỉ**

## 10. Nhận xét tháng — danh sách lớp

- **Route:** `GET /lms/student/lms-nhan-xet-thang` (Source: `lms/app/student/sources/lms-nhan-xet-thang.php`, Template: `lms/app/student/templates/lms-nhan-xet-thang/hienthi_tpl.php`)
- **Service:** `Lms\MonthlyReviewService::listForStudent()`
- **Mockup:** `Tai-lieu-phat-trien/student-ver2/Trang Nhận xét tháng.png`, `Trang Nhận xét tháng-2.png`

### Layout
- Sidebar: mục **Nhận xét tháng** đang active; tiêu đề trang **"Nhận xét tháng"** dùng `.lms-page-title`.
- Lưới **2 cột** (`row g-3` + `col-lg-6`), mỗi lớp đang học là một card.
- Mỗi card gồm:
  - **Ảnh bìa môn học** bên trái (Toán / Tiếng Anh / Tiếng Việt); môn không khớp dùng icon sách trên nền cam nhạt.
  - Tên lớp (đậm), khối của học viên (`db_lms_students.grade`, VD "Lớp 11").
  - Các dòng lịch cố định kèm icon lịch: `Thứ 3 17:30 - 19:00`, `Thứ 5 17:30 - 19:00`.
  - Nút mũi tên phải để thu gọn/mở rộng phần nhận xét.
- Không dùng avatar tròn, không hiện số lượng nhận xét hay tên giáo viên trên card.

### Nguồn dữ liệu
- Lớp: `db_lms_class_students` + `db_lms_classes` (enrolment và lớp đang active).
- Khối: `db_lms_students.grade` theo học viên đang đăng nhập.
- Lịch: `db_lms_cycle_schedules` của cycle active (ưu tiên), fallback `db_lms_schedules`.
- Nhận xét: `db_lms_monthly_reviews` chỉ lấy bản ghi `published_at IS NOT NULL` của đúng học viên + lớp.

## 11. Nhận xét tháng — nội dung từng lớp

- Mỗi card có **dropdown tháng riêng** (`Tháng 10 / 2026`, `Tháng 9 / 2026`, …) lấy từ chính các nhận xét đã công bố của lớp đó, mới nhất trước.
- Khung nội dung bên dưới là text giáo viên nhập cho tháng đang chọn; đổi dropdown chỉ đổi nội dung của card đó (JS thuần, không reload).
- Lớp chưa có nhận xét: hiển thị khung viền nét đứt "Giáo viên chưa nhập nhận xét tháng cho lớp này."
- Học viên chưa thuộc lớp nào: card thông báo "Bạn chưa được xếp vào lớp nào…".

> *GV nhập nội dung nhận xét cho từng tháng tại `/lms/teacher/lms-nhan-xet-thang`; HS chỉ xem bản đã công bố.*

> *Trạng thái triển khai (06/11/2026 - student-ver2 & teacher-ver2):*
> 1. **Chi tiết khóa học (`lms-khoa-hoc/lop?class_id=...`):**
>    - Nhóm theo Danh mục môn học (Math Problems, Math IQ,...).
>    - Card chuyên đề: Icon bên trái, Tên chuyên đề, Đã dạy: X/Y buổi, Điểm test: XX%, Điểm minitest: XX%.
>    - Nút hành động: Hiển thị song song 2 nút **"Nộp test"** và **"Nộp mini test"** (sáng cam khi bài đã được Admin duyệt, xám disabled khi chưa duyệt) dẫn tới trang nộp test (`lms-khoa-hoc/chuyen-de`); hoặc huy hiệu xanh lá **"Đã hoàn thành"** khi cả 2 bài đều đã có điểm.
>    - Accordion **Lesson Summary & BTVN**: Lưới 2 cột (`col-md-6`) các buổi học với các pill thông tin (Chuyên đề, BTVN, Hạn nộp, Nhận xét, Tệp đính kèm) + nút **Nộp BTVN** dẫn tới trang Chi tiết buổi học (`lms-lich-hoc/buoi?id=...`). Buổi tương lai hiển thị *Chưa diễn ra*.
> 2. **Trang Chi tiết Nộp test (`lms-khoa-hoc/chuyen-de?class_id=...&topic_id=...`, đối chiếu `student-ver2/Trang Chi tiết Nộp test.png`):**
>    - Tách riêng hoàn toàn khỏi trang nộp BTVN (không chứa danh sách buổi học).
>    - Card 1: Tên chuyên đề, Đã dạy: X/Y buổi, Mục tiêu then chốt (nội dung tóm tắt chuyên đề).
>    - Card 2 (Bài test chuyên đề) & Card 3 (Minitest chuyên đề): File đề bài PDF kèm link tải; vùng kéo thả/chọn tối đa 5 tệp bài làm; Hạn nộp đếm ngược / báo nộp trễ hạn; Nút cam **Nộp bài**. Khi Admin chưa duyệt hiển thị ghi chú đỏ: `*Admin không duyệt minitest thì học sinh không nộp bài được ở đây` và nút Nộp bài bị khóa. Phần Nhận xét của GV hiển thị điểm số, tỷ lệ % và file chấm đính kèm.
> 3. **Trang Chi tiết buổi học — Nộp BTVN (`lms-lich-hoc/buoi?id=...`, đối chiếu `student-ver2/Trang Chi tiết buổi học.png`):**
>    - Section 1 (`Lesson Summary & BTVN`): Card buổi học chỉ đọc cho học viên với 5 pill thông tin (`Chọn các chuyên đề *`, `BTVN`, `Hạn nộp`, `Nhận xét buổi học`, `Tệp đính kèm`), **không** hiển thị nút "Cập nhật" (nút "Cập nhật" chỉ dành cho giáo viên/admin).
>    - Section 2 (`Bài tập về nhà`): Học viên tải tệp bài nộp (tối đa 5 tệp) kèm thông báo hạn nộp đếm ngược và nút cam **Nộp bài**. Phần **"Nhận xét của giáo viên"** chỉ hiển thị sau khi được **Admin duyệt Bài tập về nhà** (`db_lms_session_students.status = 'approved'` tại `lms-lop/buoi?id=...`).

## 12. Nội quy lớp học

- **Route:** `GET /lms/student/lms-noi-quy` (Source: `lms/app/student/sources/lms-noi-quy.php`, Template: `lms/app/student/templates/lms-noi-quy/hienthi_tpl.php`)
- **Service:** `Lms\ClassRulesService::getRules()`, `ClassRulesService::isEmptyContent()`, `ClassRulesService::formatForDisplay()`

### Layout
- Sidebar: mục **Nội quy lớp học** đang active.
- Tiêu đề trang: **"Nội quy lớp học"** dùng `.lms-page-title`.
- Khung nội dung: panel trắng `.lms-rules-panel` (không viền, không toolbar, không textarea) rộng theo vùng nội dung, tối thiểu 520px.
  - Có dữ liệu: render HTML đã sanitize hoặc text thuần đã escape + `nl2br` qua `formatForDisplay()`.
  - Chưa có dữ liệu: hiển thị `"Hiện text nội quy admin nhập ...."` trong `.lms-rules-empty`.

### Nguồn dữ liệu
- Bảng `db_lms_class_rules`, bản ghi `id = 1`; nội dung do Admin biên soạn tại `/lms/admin/lms-noi-quy`.
- Học viên chỉ đọc, không có action POST trên trang này.

> *Trạng thái triển khai (06/11/2026 - student-ver2 & teacher-ver2):*
> 1. **Chi tiết khóa học (`lms-khoa-hoc/lop?class_id=...`):**
>    - Nhóm theo Danh mục môn học (Math Problems, Math IQ,...).
>    - Card chuyên đề: Icon bên trái, Tên chuyên đề, Đã dạy: X/Y buổi, Điểm test: XX%, Điểm minitest: XX%.
>    - Nút hành động: Hiển thị song song 2 nút **"Nộp test"** và **"Nộp mini test"** (sáng cam khi bài đã được Admin duyệt, xám disabled khi chưa duyệt) dẫn tới trang nộp test (`lms-khoa-hoc/chuyen-de`); hoặc huy hiệu xanh lá **"Đã hoàn thành"** khi cả 2 bài đều đã có điểm.
>    - Accordion **Lesson Summary & BTVN**: Lưới 2 cột (`col-md-6`) các buổi học với các pill thông tin (Chuyên đề, BTVN, Hạn nộp, Nhận xét, Tệp đính kèm) + nút **Nộp BTVN** dẫn tới trang Chi tiết buổi học (`lms-lich-hoc/buoi?id=...`). Buổi tương lai hiển thị *Chưa diễn ra*.
> 2. **Trang Chi tiết Nộp test (`lms-khoa-hoc/chuyen-de?class_id=...&topic_id=...`, đối chiếu `student-ver2/Trang Chi tiết Nộp test.png`):**
>    - Tách riêng hoàn toàn khỏi trang nộp BTVN (không chứa danh sách buổi học).
>    - Card 1: Tên chuyên đề, Đã dạy: X/Y buổi, Mục tiêu then chốt (nội dung tóm tắt chuyên đề).
>    - Card 2 (Bài test chuyên đề) & Card 3 (Minitest chuyên đề): File đề bài PDF kèm link tải; vùng kéo thả/chọn tối đa 5 tệp bài làm; Hạn nộp đếm ngược / báo nộp trễ hạn; Nút cam **Nộp bài**. Khi Admin chưa duyệt hiển thị ghi chú đỏ: `*Admin không duyệt minitest thì học sinh không nộp bài được ở đây` và nút Nộp bài bị khóa. Phần Nhận xét của GV hiển thị điểm số, tỷ lệ % và file chấm đính kèm.
> 3. **Trang Chi tiết buổi học — Nộp BTVN (`lms-lich-hoc/buoi?id=...`, đối chiếu `student-ver2/Trang Chi tiết buổi học.png`):**
>    - Section 1 (`Lesson Summary & BTVN`): Card buổi học chỉ đọc cho học viên với 5 pill thông tin (`Chọn các chuyên đề *`, `BTVN`, `Hạn nộp`, `Nhận xét buổi học`, `Tệp đính kèm`), **không** hiển thị nút "Cập nhật" (nút "Cập nhật" chỉ dành cho giáo viên/admin).
>    - Section 2 (`Bài tập về nhà`): Học viên tải tệp bài nộp (tối đa 5 tệp) kèm thông báo hạn nộp đếm ngược và nút cam **Nộp bài**. Phần **"Nhận xét của giáo viên"** chỉ hiển thị sau khi được **Admin duyệt Bài tập về nhà** (`db_lms_session_students.status = 'approved'` tại `lms-lop/buoi?id=...`).

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

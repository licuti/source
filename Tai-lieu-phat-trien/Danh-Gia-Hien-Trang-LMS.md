# BÁO CÁO ĐÁNH GIÁ HIỆN TRẠNG & ĐỐI CHIẾU THIẾT KẾ HỆ THỐNG LMS MASTERSTUDY

> **Ngày lập báo cáo:** 10/10/2026  
> **Cập nhật gần nhất:** 12/10/2026 (sau khi hoàn thành Phase 1 + Phase 2 — xem
> phần tiến độ mới bên dưới; các phần khác giữ nguyên như đánh giá ngày 10/10)  
> **Dự án:** LMS MasterStudy — Trung tâm Ngoại ngữ AROMA  
> **Phạm vi kiểm toán:** Đối chiếu giữa **Bộ tài liệu đặc tả** (`SRS_MasterStudy.md`, `Quy_Trinh_Phat_Trien.md`, `ERD_MasterStudy.md`, `Huong-Dan-Phat-Trien-Page.md`), **Bộ ảnh chụp Mockup PNG** (thư mục `admin/`, `teacher/`, `student/`) và **Mã nguồn thực tế** trong `C:\laragon\www\source\lms`.

---

## 0. TIẾN ĐỘ MỚI NHẤT (cập nhật 12/10/2026)

Tính đến commit `2769188` (12/10/2026), **Phase 0–2 đã hoàn thành**:
- **Phase 1** (commit `3b05aba`): toàn bộ portal Admin/Teacher/Student cơ bản + STU-01..04 + TCH-01..03 + ADM-01..05 — 248 test pass.
- **Phase 2** (commit `61550b5` + `8b00351` + `2769188`): `TickService` (BR-TICK-01/02/03) + `TaxService` (BR-PAY-03) + `PayrollService` (BR-PAY-01/02/04) + cron quét tick + cron chốt lương + logging `db_lms_cron_runs` + module `lms-vi-pham` (ADM-06) + `lms-phi-dv` (ADM-07 admin + TCH-05 teacher) — **309 test / 989 assertion pass**.
- **Phase 3 (đang làm)** (commit sau `2769188`): `MatchingService` + module `lms-sap-xep-lop` (ADM-04 Sắp xếp lớp — FR admin cuối cùng) + thêm `subject_id` / `preferred_teacher_gender` cho hồ sơ học viên (ADM-01) — **328 test / 1088 assertion pass**.

### Bảng module đã build (12/10/2026)

| Portal | Đăng ký | Đã build | Còn thiếu |
|---|:---:|:---:|---|
| **Admin** | 14 | **10** | `lms-bai-tap-ve-nha`, `lms-lo-trinh`, `lms-noi-quy`, `lms-thu-vien`, `lms-thong-ke-buoi-hoc` |
| **Teacher** | 6 | **5** | `lms-thu-vien` (TCH-07) |
| **Student** | 5 | **5** | **100%** (riêng `lms-noi-quy` là placeholder chờ module admin nội quy) |

### Việc còn lại (Priority 12/10/2026)

1. **P2 — TCH-07 Thư viện** (teacher + admin): schema `db_lms_library_files` đã có; reuse `lms_upload.php`.
2. **P3 — 4 module admin view-only** (`bai-tap-ve-nha`, `lo-trinh`, `noi-quy`, `thong-ke-buoi-hoc`) + kiểm tra TCH-06 (MonthlyReviewService đã có, chưa có UI teacher).
3. **P4 — Ops**: lên lịch cron production (tick 15 phút, payroll ngày 1, thiếu `session_generate` hàng ngày), chốt 2 giá trị seed tạm (`ticks_to_warn=5`, `fee_percentage_after_demote=50.00`), viết `Test_Plan_MasterStudy.md`.
4. **Mockup gaps** (xem mục 3 bên dưới — vẫn còn nguyên giá trị): Admin Tổng quan thiếu timeline calendar, Teacher Tổng quan bố cục 3 cột, Teacher "Học viên của tôi" dạng cards grid, widget Chat, tìm kiếm toàn cục.

---

## 1. TỔNG QUAN TIẾN ĐỘ THỰC HIỆN

Hệ thống LMS được quy hoạch nằm gọn trong thư mục `lms/` với **3 Portal đối xứng** (`admin`, `teacher`, `student`), kế thừa cơ sở dữ liệu `phuongnamv_db` nhưng phân vùng bảng riêng (`db_lms_*`), không can thiệp vào `admin/` của web e-commerce cũ.

### 1.1. Bảng tổng hợp tiến độ hoàn thành UI theo Portal

| Phân hệ (Portal) | Tổng module menu | Đã có Controller & Template | Chưa có Code (Trang 404) | Tỷ lệ hoàn thành UI |
|---|:---:|:---:|:---:|:---:|
| **Admin LMS** | 14 module | **10** (`Tổng quan`, `Học viên`, `Giáo viên`, `Lớp học`, `Học sinh - Lớp học`, `Vi phạm`, `Yêu cầu`, `Phí dịch vụ`, `Sắp xếp lớp`, `Thông báo`*) | **5** module | **~64%** |
| **Teacher LMS** | 6 module | **5** (`Tổng quan`, `Học viên của tôi`, `Quản lý lịch`, `Phí dịch vụ`, `Vi phạm`) | **1** module | **~83%** |
| **Student LMS** | 5 module | **5** (`Tổng quan`, `Khoá học`, `Lịch học`, `Nhận xét tháng`, `Nội quy`) | **0** | **100%** |
| **Chung (Core & System)** | Auth, Layout, Router, Notif | **Đã hoàn thành bộ khung lõi** | Background Cronjobs | **~75%** |

(*) `Thông báo` là module ẩn (không hiển thị menu, dùng cho chuông thông báo).

---

### 1.2. Các thành phần ĐÃ HOÀN THÀNH

#### A. Hạ tầng cốt lõi (Core Framework & Libs)
- **Tầng dữ liệu an toàn (`lms/lib/lms_db.php`):** Tách độc lập khỏi God class `func_index` cũ. Dùng PDO chuẩn, 100% prepared statements (bind params), mã hóa `utf8mb4`, xử lý transaction rõ ràng.
- **Xác thực & Bảo mật (`lms/lib/lms_auth.php`, `lms/lib/lms_login.php`):** Hỗ trợ mật khẩu Bcrypt, CSRF token bắt buộc cho mọi POST, Rate-limit (5 lần sai / 15 phút), xoay Cookie Remember-token.
- **Phân vùng Portal nghiêm ngặt (Quy tắc D9):** Đăng nhập sai portal lập tức từ chối và hiển thị link chuyển tới đúng portal tương ứng.
- **Hệ thống Routing & URL sạch (Quy tắc D10):** `lms_url.php` và `lms_router.php` hỗ trợ URL dạng `/lms/<portal>/<alias>/<action>` không đuôi `.php`.
- **Khung giao diện dùng chung (`lms/lib/lms_layout.php`):** Header cam, Footer, Sidebar tự động truy vấn từ bảng `db_lms_modules`.
- **Hệ thống Thông báo thời gian thực (`lms/lib/lms_notif_page.php`, `NotificationService.php`):** Đã có AJAX polling, trang danh sách thông báo và chuông thông báo header.

#### B. Cơ sở dữ liệu & Tầng nghiệp vụ (Service Layer)
- **Database Schema (`db_lms_*`):** Đã migration đầy đủ các bảng dữ liệu lõi (`2026_10_07_lms_auth.sql`, `2026_10_07_lms_core.sql`, `2026_10_09_lms_teacher_profile_slots_notes.sql`).
- **Bộ Services OOP thuần (PHP 7.3):**
  - `UserService.php`: Quản lý tài khoản, phân quyền RBAC, profile học viên & giáo viên.
  - `ClassService.php`: Tạo và cập nhật lớp học 1-1 / lớp nhóm.
  - `EnrolmentService.php`: Xử lý học viên vào lớp, chuyển lớp, rút khỏi lớp.
  - `ScheduleService.php`: Chống trùng lịch giáo viên (`BR-SCHED-01`).
  - `SessionService.php`: Tự động sinh buổi học từ lịch cố định cho 4 tuần kế tiếp.
  - `TeacherProfileService.php`: Quản lý khu vực dạy, trạng thái online, ghi chú tuần của giáo viên.
  - `RequestService.php`: Xử lý tạo và duyệt yêu cầu (xin nghỉ, thêm buổi).
- **Kiểm thử tự động:** Có bộ test PHPUnit trong `tests/Services/` bao phủ các service trên.

#### C. Các màn hình UI đã dựng
- **Admin:**
  - `lms-tong-quan` (Dashboard số liệu và nhóm chương trình).
  - `lms-hoc-vien` (CRUD học viên, khóa/mở tài khoản, lọc, phân trang).
  - `lms-giao-vien` (CRUD giáo viên, hồ sơ thuế, % phí, môn và quận dạy).
  - `lms-lop` (CRUD lớp học, cấu hình lịch tuần, gán giáo viên/học viên).
  - `lms-lop-hoc-sinh` (Ma trận học sinh × chương trình, tab lớp nhóm và lớp 1-1).
- **Teacher:**
  - `lms-tong-quan` (Thống kê lớp dạy, tick phạt, lịch dạy tuần).
  - `lms-hoc-vien-cua-toi` (Danh sách học viên đang phụ trách).
  - `lms-lich-day` (Calendar tuần 5 màu, modal xin nghỉ, thêm buổi, profile giáo viên).
- **Student:**
  - `lms-tong-quan` (Thống kê lớp đang học, buổi sắp tới, thông báo).

---

### 1.3. Các thành phần CHƯA LÀM (Tồn đọng)

#### A. Phân hệ Admin (9 module chưa có code)
1. `lms-bai-tap-ve-nha` (Quản lý BTVN, chấm bài, duyệt bài làm).
2. `lms-vi-pham` (Quản lý vi phạm, cấu hình ngưỡng miễn trừ và hạ phí DV - ADM-06).
3. `lms-yeu-cau` (Quản lý yêu cầu: duyệt xin nghỉ, đổi lịch, thêm buổi, gửi test - ADM-05).
4. `lms-lo-trinh` (Quản lý lộ trình: Chương trình -> Môn học -> Chuyên đề).
5. `lms-noi-quy` (Quản lý nội quy trung tâm).
6. `lms-thu-vien` (Quản lý thư viện tài liệu, đề thi, slide).
7. `lms-phi-dv` (Thống kê phí dịch vụ, tính lương GV, thuế TNCN, xuất Excel - ADM-07).
8. `lms-thong-ke-buoi-hoc` (Thống kê và kiểm số buổi học thực tế).
9. `lms-sap-xep-lop` (Thuật toán gợi ý ghép lớp cho học viên chờ xếp - ADM-04).

#### B. Phân hệ Teacher (3 module chưa có code + thiếu view chi tiết)
1. `lms-phi-dv` (Xem lịch sử nhận tiền, bảng kê chi tiết từng buổi - TCH-05).
2. `lms-vi-pham` (Xem tổng tick phạt thực tế, cảnh báo chạm ngưỡng - TCH-05).
3. `lms-thu-vien` (Kho tài liệu tham khảo - TCH-07).
4. **Trang Chi tiết khóa học / Buổi học:** Hiện tại từ trang "Học viên của tôi" chưa có trang chi tiết để giáo viên nộp Lesson summary, giao BTVN, tải bài nộp và chấm điểm.

#### C. Phân hệ Student (4 module chưa có code)
1. `lms-khoa-hoc` (Cây chuyên đề lộ trình học, nộp BTVN, nộp Test/Minitest - STU-01).
2. `lms-lich-hoc` (Thời khóa biểu cá nhân của học sinh - STU-02).
3. `lms-nhan-xet-thang` (Xem phiếu nhận xét, đánh giá định kỳ của giáo viên - STU-04).
4. `lms-noi-quy` (Xem nội quy lớp học).

#### D. Hệ thống Tác vụ ngầm (Cronjobs)
- Chưa có các script chạy định kỳ qua Cron/Task Scheduler:
  - Quét deadline Lesson summary (12:00 ngày kế tiếp) để tự động phạt tick.
  - Quét deadline chấm BTVN (24h trước buổi học kế tiếp).
  - Tự động sinh buổi học (`sessions`) hàng ngày/hàng tuần.
  - Tự động chốt bảng lương ngày 1 hàng tháng.

---

## 2. ĐỐI CHIẾU VỚI YÊU CẦU TRONG TÀI LIỆU ĐẶC TẢ (SRS & SDLC)

| Mã yêu cầu | Tên chức năng | Yêu cầu theo SRS / SDLC | Tình trạng hiện tại | Đánh giá |
|---|---|---|---|:---:|
| **SYS-01** | Chuông thông báo | Thông báo real-time/polling, đánh dấu đã đọc, phân loại Admin/GV/HV. | Đã có `lms_notif_page.php`, AJAX polling và popup. | **ĐẠT** |
| **SYS-03** | Xác thực & Phiên | PHP session, Bcrypt, CSRF, Rate limit 5 lần / 15 phút, Remember token. | Đã triển khai đầy đủ và chặt chẽ trong `lms_auth.php`. | **ĐẠT** |
| **ADM-01** | Quản lý học viên | CRUD, mã HV tự sinh, SĐT phụ huynh, khung giờ rảnh theo tuần. | Đã có trong `lms-hoc-vien.php` và bảng phụ `free_time_slots`. | **ĐẠT** |
| **ADM-02** | Quản lý giáo viên | CCCD, MST, Cam kết 08, Hạng GV, % Phí DV, môn/quận dạy được. | Đã có trong `lms-giao-vien.php` và `TeacherProfileService`. | **ĐẠT** |
| **ADM-03** | Quản lý lớp học | Tạo lớp 1-1/nhóm, lịch cố định, tự động sinh 4 tuần sessions, hạn BTVN. | Đã có trong `lms-lop.php` & `ClassService`. Sinh sessions tự động. | **ĐẠT** |
| **ADM-04** | Học sinh - Lớp học + Sắp xếp lớp | Ma trận HV theo chương trình, 2 tab lớp nhóm và 1-1, gán lớp trực tiếp; ghép GV cho HV chờ xếp. | Đã có `lms-lop-hoc-sinh.php` (thêm/chuyển lớp) + `lms-sap-xep-lop.php` (`MatchingService`: hard filter môn/quận/giới tính/online/lịch + điểm 0-100, tạo lớp 1-1 tự động). | **ĐẠT** |
| **ADM-05** | Quản lý yêu cầu | Duyệt/Từ chối request: xin nghỉ, thêm buổi, đổi lịch, gửi test. | Đã có `lms-yeu-cau.php` + `RequestService` (duyệt atomic, check trùng lịch, trừ remaining_sessions). | **ĐẠT** |
| **ADM-06** | Quản lý vi phạm | CRUD `violation_thresholds`, cộng/trừ tick, hạ phí dịch vụ. | Đã có `lms-vi-pham.php` + `TickService` (BR-TICK-01/02/03) + cron quét 15 phút. | **ĐẠT** |
| **ADM-07** | Thống kê tài chính | Tính phí DV theo tháng, trừ phạt, tính thuế TNCN, xuất Excel. | Đã có `lms-phi-dv.php` + `PayrollService`/`TaxService` (BR-PAY-01..04) + cron chốt lương + xuất CSV. | **ĐẠT** |
| **TCH-01** | Quản lý lịch dạy | Lịch tuần, 5 màu trạng thái, đăng ký lịch trống, xin nghỉ, thêm buổi. | Đã hoàn thành trong `lms-lich-day.php` và `buoi_tpl.php`. | **ĐẠT** |
| **TCH-02** | Ghi Lesson Summary | Chọn chuyên đề, nhập tóm tắt, đính kèm file, deadline 12:00 hôm sau. | Đã có trong chi tiết buổi học (`buoi_tpl.php`); cron tự đánh `is_summary_late` + tạo tick. | **ĐẠT** |
| **TCH-03** | Chấm BTVN | Tải bài làm học viên, chấm điểm, nhận xét, upload file đã chấm. | Đã có trong `buoi_tpl.php` (chấm BTVN + is_graded_late). | **ĐẠT** |
| **TCH-04** | Chấm Test / Minitest | Chấm điểm thang 20 và %, tải bài nộp, upload file Excel chấm điểm. | Đã có trong `buoi_tpl.php` (chấm test/minitest qua `StudentCourseService`). | **ĐẠT** |
| **TCH-05** | Phí DV & Vi phạm | Xem bảng kê thu nhập, theo dõi tick thực tế và cảnh báo hạ phí. | Đã có `lms-phi-dv.php` + `lms-vi-pham.php` (portal teacher). | **ĐẠT** |
| **TCH-06** | Nhận xét tháng | GV xem nhận xét tháng của mình. | `MonthlyReviewService` đã có; **chưa có UI teacher**. | **THIẾU UI** |
| **TCH-07** | Thư viện | Xem/tải tài liệu lớp học. | Schema `db_lms_library_files` đã có; **chưa có UI**. | **THIẾU UI** |
| **STU-01** | Lộ trình khóa học | Cây chuyên đề, xem tiến độ buổi học, tải đề, nộp BTVN, nộp Test. | Đã có `lms-khoa-hoc.php` (học + nộp BTVN/test). | **ĐẠT** |
| **STU-02** | Lịch học học viên | Xem lịch học tuần, trạng thái buổi học, link học online. | Đã có `lms-lich-hoc.php`. | **ĐẠT** |
| **STU-03** | Nhận xét tháng | Xem nhận xét tháng từ GV (chỉ khi published). | Đã có `lms-nhan-xet-thang.php`. | **ĐẠT** |
| **STU-04** | Nội quy lớp học | Xem nội quy. | Đã có `lms-noi-quy.php` (read-only; nội quy do admin cấu hình — module admin nội quy chưa build). | **ĐẠT (UI view)** |

---

## 3. ĐỐI CHIẾU TRỰC QUAN: MOCKUP ẢNH PNG vs GIAO DIỆN HIỆN TẠI (MATCH vs MISMATCH)

Kiểm tra chi tiết từng file ảnh mockup PNG và so sánh với file template hiện hành trong code:

### 3.1. Phân hệ Admin

#### 1. Màn hình "Tổng quan" (`Tổng quan.png` vs `lms-tong-quan/hienthi_tpl.php`)
- **Trong ảnh Mockup:**
  - 4 Thẻ thống kê đầu trang (Cards).
  - Bảng "Thống kê nhóm chương trình" (cột: STT, Nhóm chương trình, Số lớp đang học, Số lớp đang sắp xếp, Số lớp tạm nghỉ).
  - **Lịch tuần này (Timeline 7 cột Thứ 2 → CN, khung giờ 1:00 đến 24:00):** Mỗi ô thể hiện mã lớp, giờ bắt đầu - kết thúc. Click vào ô lịch sẽ mở Popup chi tiết hiển thị danh sách lớp, link giáo viên, sĩ số.
- **Trong Code thực tế:**
  - Đã có 4 thẻ thống kê và Bảng nhóm chương trình.
  - ❌ **THIẾU HOÀN TOÀN KHỐI LỊCH TUẦN NÀY (Timeline Calendar)**. Trang Dashboard hiện tại chỉ chiếm nửa trên màn hình, thiếu hẳn thành phần tương tác quan trọng nhất.

#### 2. Màn hình "Quản lý lớp học" (`Lớp học.png` vs `lms-lop/hienthi_tpl.php`)
- **Trong ảnh Mockup:**
  - Bộ lọc tìm kiếm có nút cam **"Điều chỉnh số buổi học"** cạnh nút "Thêm".
  - Bảng danh sách có cột **"Khóa học"** (hiển thị 2 khóa gần nhất: Bắt đầu, Kết thúc, Đã học, Còn lại).
  - Bảng danh sách có cột **"Chuyên đề đang học"**.
  - Cột Hành động có nút: Sửa, Lịch học, Thêm khóa học, Xóa.
- **Trong Code thực tế:**
  - ❌ Thiếu nút "Điều chỉnh số buổi học" (`Chỉnh số buổi.png`).
  - ❌ Thiếu cột "Khóa học" và "Chuyên đề đang học" trên bảng dữ liệu.
  - ❌ Cột Hành động chỉ có Sửa, Tạm dừng/Mở, Xóa; thiếu nút xem "Lịch học" và "Thêm khóa học".

#### 3. Form "Thêm lớp mới" (`Lớp mới.png` vs `lms-lop/form_tpl.php`)
- **Trong ảnh Mockup:**
  - Có mục **"Chọn chuyên đề"** (Dropdown cho phép chọn nhiều chuyên đề).
  - Section Cài đặt có toggle: Hạn giao BTVN (12:00), Hạn check BTVN (24 giờ), **Hạn chấm test (giờ, mặc định 72h)**.
- **Trong Code thực tế:**
  - ❌ Thiếu ô chọn chuyên đề thuộc môn học.
  - ❌ Thiếu trường cấu hình "Hạn chấm test".

---

### 3.2. Phân hệ Teacher (Giáo viên)

#### 1. Màn hình "Tổng quan / Trang chủ" (`Trang chủ.png`, `2.png`, `3.png` vs `lms-tong-quan/hienthi_tpl.php`)
- **Trong ảnh Mockup:**
  - Bố cục chia làm **3 cột rõ rệt**:
    - *Cột 1 (Trái):* Sidebar menu.
    - *Cột 2 (Giữa):* Khối "Buổi học sắp tới" (Card lớn có nhãn "Sắp diễn ra" màu đỏ) + Lưới thẻ "Các học viên hiện tại" (Avatar, Tên/Mã HV, Lịch cố định).
    - *Cột 3 (Phải):* Cột "Thông báo & Tác vụ cần xử lý" với các nút cam hành động nhanh: nút "Ghi báo cáo buổi học", nút "Chấm bài", thông báo hạ phí DV.
  - Banner cảnh báo hạ phí DV ở đầu trang: *"⚠ Cảnh báo: Bạn còn 2 tick nữa trước khi bị hạ phí dịch vụ"*.
- **Trong Code thực tế:**
  - Đã có banner cảnh báo (rất tốt).
  - ❌ **Sai lệch 100% bố cục:** Code hiện tại lại dựng thành 3 Thẻ thống kê (Lớp đang dạy, Tick, Buổi dạy) + 1 Bảng danh sách buổi dạy trong tuần. Không hề có Lưới thẻ học viên và Cột tác vụ hành động bên phải.

#### 2. Màn hình "Học viên của tôi" (`Trang Các khóa học.png`, `28.png` vs `lms-hoc-vien-cua-toi/hienthi_tpl.php`)
- **Trong ảnh Mockup:**
  - Giao diện dạng **LƯỚI THẺ (Cards Grid)**.
  - Mỗi card hiển thị: Avatar học viên, Tên HV / Tên lớp nhóm, Môn học, Lịch học cố định, và **Nhãn trạng thái BTVN** (màu đỏ: *Chưa giao BTVN*, *Chưa chấm BTVN*; màu xanh: *Đã chấm BTVN*).
  - Bấm vào Card học viên sẽ chuyển hướng đến trang **"Chi tiết khóa học"**.
- **Trong Code thực tế:**
  - ❌ Đang hiển thị dạng **BẢNG DÒNG (Table)**.
  - ❌ Không có nhãn trạng thái BTVN theo màu sắc.
  - ❌ Không có liên kết bấm vào để mở trang chi tiết khóa học.

---

### 3.3. Các thành phần Toàn cục (Global Elements)

1. **Widget Chat trực tiếp (`3.png` trong teacher & chân trang 3 portal):**
   - *Mockup:* Góc dưới bên phải chân trang có nút "Chat" nổi. Bấm vào sẽ mở popup hội thoại trực tiếp kiểu Shopee/Messenger (danh sách người chat, khung nhắn tin, đính kèm file/ảnh).
   - *Code thực tế:* **HOÀN TOÀN CHƯA CÓ**. `lms_layout.php` chưa tích hợp thành phần này.
2. **Thanh tìm kiếm toàn cục trên Header cam:**
   - *Mockup:* Ô tìm kiếm nằm giữa Header cam ở cả 3 portal.
   - *Code thực tế:* Có input HTML hiển thị nhưng **chưa gắn logic tìm kiếm backend** (chỉ là ô tĩnh).

---

## 4. CÁC ĐIỂM SAI LỆCH, BẤT NHẤT & "SAI SỰ THẬT" CẦN LÀM RÕ (DISCREPANCIES)

1. **Sự thật về Tên gọi & Số lượng file Mockup:**
   - Trong thư mục `Tai-lieu-phat-trien/teacher/`, file ảnh mang tên `Trang Các khóa học.png` thực chất là chụp màn hình **"Học viên của tôi"** (menu bên trái đang active mục này). Nếu lập trình viên nhìn vào tên file rất dễ hiểu nhầm đây là trang của học sinh.
2. **Thông tin trong file `lms/README.md` bị chậm so với code thực tế:**
   - Trong `lms/README.md` ghi rằng mới chỉ hoàn thành Phase 1.1 gồm `lms-hoc-vien`, `lms-giao-vien`. Nhưng thực tế trong code đã có cả `lms-lop.php`, `lms-lop-hoc-sinh.php`, và một phần của `teacher`, `student`. File tài liệu nội bộ này chưa được đồng bộ cập nhật.
3. **Hiện tượng "Bẫy 404" trên Sidebar:**
   - Script seed dữ liệu (`db_lms_modules_seed.sql`) đã chèn đầy đủ 14 menu cho Admin, 6 menu cho Teacher, 5 menu cho Student. Tuy nhiên do code mới chỉ có 5 module Admin, 3 module Teacher, 1 module Student, nên **khi người dùng click vào các mục còn lại trên menu Sidebar sẽ bị văng lỗi 404 (Không tìm thấy trang)**.
4. **Tồn dư tài liệu cũ về Tech Stack:**
   - Trong một số tài liệu cũ còn sót các từ khóa NestJS, PostgreSQL, Redis, Elasticsearch. Cần khẳng định rõ: **Hệ thống đã chuyển đổi 100% sang PHP 7.3 server-rendered + MySQL InnoDB**, không phụ thuộc vào stack cũ.

---

## 5. ĐÁNH GIÁ CHẤT LƯỢNG KỸ THUẬT, KHẢ NĂNG SCALE & CHỊU TẢI

### 5.1. Khả năng Scale (Mở rộng)
- **Scale Mã nguồn (Codebase):** Rất tốt. Việc chia kiến trúc Service Layer (`services/Lms/`) tách biệt với Controller (`sources/`) và View (`templates/`) giúp code sạch, dễ mở rộng tính năng mới mà không bị chồng chéo.
- **Scale Chiều dọc (Vertical Scaling - Nâng RAM/CPU):** Tốt. Do dùng PHP thuần và PDO tối giản, tài nguyên tiêu hao trên mỗi request rất thấp.
- **Scale Chiều ngang (Horizontal Scaling - Thêm Server):** **Sẽ nghẽn ở Session**. Hiện tại hệ thống dùng file session mặc định của PHP. Nếu chạy nhiều server đằng sau Load Balancer, user sẽ bị văng đăng nhập. Cần cấu hình lưu Session tập trung vào MySQL hoặc Redis khi muốn scale ngang.

### 5.2. Khả năng Chịu tải & Các điểm nghẽn tiềm ẩn
1. **Nguy cơ Race Condition (Xung đột dữ liệu):**
   - Khi nhiều học sinh cùng đăng ký một lớp học chỉ còn 1 chỗ trống. Trong `ClassService` cần sử dụng khóa dòng `SELECT ... FOR UPDATE` trong transaction để chống ghi đè số lượng `remaining_sessions`.
2. **Nghẽn tác vụ nặng (Timeout):**
   - Các hành động như gửi thông báo/email cho toàn bộ học viên hoặc xuất file Excel thống kê phí DV lớn chạy đồng bộ trên PHP sẽ dễ bị quá thời gian chạy (`max_execution_time`).
3. **Dung lượng lưu trữ file (File Storage):**
   - File nộp BTVN, bài thi test của học viên và file tài liệu thư viện nếu lưu trực tiếp trên ổ cứng web server sẽ nhanh chóng làm đầy ổ cứng.

---

## 6. KẾ HOẠCH HÀNH ĐỘNG & LỘ TRÌNH KHẮC PHỤC (ROADMAP)

> Cập nhật 12/10/2026. Đánh dấu ✅ = đã xong, ⏳ = còn lại.

### Giai đoạn 1: Sửa các giao diện ĐÃ CÓ cho khớp 100% Mockup (còn nguyên giá trị) ⏳
1. **Admin Tổng quan:** Bổ sung khối Timeline Calendar tuần này (7 ngày × 24 khung giờ) vào phía dưới bảng chương trình trong `lms/app/admin/templates/lms-tong-quan/hienthi_tpl.php`.
2. **Admin Quản lý lớp học:** Thêm cột "Khóa học" và "Chuyên đề" vào bảng danh sách; bổ sung modal "Điều chỉnh số buổi học"; thêm trường chọn chuyên đề và hạn chấm test vào form tạo lớp.
3. **Teacher Tổng quan:** Chuyển đổi giao diện từ dạng 1 bảng danh sách sang **bố cục 3 cột chuẩn mockup**: Cột giữa (Buổi sắp tới + Lưới thẻ học sinh), Cột phải (Hộp tác vụ hành động nộp báo cáo / chấm bài).
4. **Teacher Học viên của tôi:** Viết lại view từ dạng Bảng (Table) sang dạng **Lưới thẻ (Cards Grid)** có avatar, lịch và nhãn trạng thái BTVN theo màu sắc.

### Giai đoạn 2: Trục xương sống BTVN & Bài Test ✅ (hoàn thành Phase 1)
1. ✅ Màn hình **Chi tiết buổi học** cho Teacher (`lms-lich-day/buoi_tpl.php`): lesson summary, giao BTVN, chấm BTVN.
2. ⏳ Module **Quản lý BTVN** cho Admin (`lms-bai-tap-ve-nha`) — view-only, theo P3.
3. ✅ Module **Các khóa học** cho Student (`lms-khoa-hoc`): lộ trình chuyên đề + nộp BTVN/Test (STU-01/02/03).
4. ⏳ Giao diện chấm Test/Minitest cho Teacher (TCH-04) — service chưa có; tick `late_test_grading` đã hỗ trợ sẵn trong TickService.

### Giai đoạn 3: Phân hệ Tài chính & Vi phạm (Phase 2 MVP) ✅ (hoàn thành 12/10)
1. ✅ Module **Quản lý vi phạm** Admin (`lms-vi-pham`, ADM-06) + màn hình **Vi phạm nội quy** Teacher.
2. ✅ `PayrollService` + `TaxService`: phí dịch vụ, hạ phí theo tick, thuế TNCN (cam kết 08).
3. ✅ Màn hình **Thống kê phí dịch vụ** Admin (`lms-phi-dv`, ADM-07, kèm xuất CSV + duyệt draft→pending→paid) + **Phí dịch vụ** Teacher (TCH-05).

### Giai đoạn 4: Cronjobs tự động — một phần ✅
1. ✅ `database/cron/lms_tick_cron.php`: quét lesson summary trễ (BR-TICK-01) + chấm BTVN trễ (BR-TICK-02) + đánh giá tick (BR-TICK-03).
2. ⏳ `database/cron/lms_session_generate.php`: sinh buổi học cho các tuần tiếp theo — **chưa có** (hiện chỉ sinh khi tạo/sửa lớp qua ADM-03).
3. ✅ `database/cron/lms_payroll_cron.php`: chốt lương ngày 1 hàng tháng.
4. ⏳ **Lên lịch production** (Task Scheduler/crontab) cho cả 3 cron + cảnh báo email khi `db_lms_cron_runs` chết >30 phút (R8).

### Giai đoạn 5: Phase 3 còn lại ⏳
1. **ADM-04 Sắp xếp lớp** (module `lms-sap-xep-lop` + service match GV).
2. **TCH-07 Thư viện** (teacher + admin, schema `db_lms_library_files` đã có).
3. Các module admin view-only: `lms-lo-trinh`, `lms-noi-quy`, `lms-thong-ke-buoi-hoc`.
4. Kiểm tra TCH-06 (MonthlyReviewService đã có, chỉ thiếu UI teacher).

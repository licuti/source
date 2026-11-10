# Tài liệu Admin - TRUNG TÂM NGOẠI NGỮ AROMA

## 1. Tổng quan (Dashboard)

- **Route:** `GET /lms/admin/lms-tong-quan` (hoặc `/lms/admin`)
- **Source & Template:** `lms/app/admin/sources/lms-tong-quan.php`, `lms/app/admin/templates/lms-tong-quan/hienthi_tpl.php`
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin/Tổng quan.png`, `Tai-lieu-phat-trien/admin/Tổng quan (2).png`

### 1.1. Layout chung
- **Header:** Logo "MasterStudy - Giáo viên riêng của bạn" (góc trái), thanh tìm kiếm toàn cục (giữa), chuông thông báo (`lms-thong-bao`) + avatar + tên người dùng đăng nhập (góc phải).
- **Sidebar** (trái, các mục chính):
  - Tổng quan (`/lms/admin/lms-tong-quan`)
  - Quản lý tài khoản (`/lms/admin/lms-tai-khoan`)
  - Quản lý lớp học (`/lms/admin/lms-lop`)
  - Học sinh - Lớp học (`/lms/admin/lms-lop-hoc-sinh`)
  - Quản lý BTVN (`/lms/admin/lms-bai-tap-ve-nha`)
  - Quản lý vi phạm (`/lms/admin/lms-vi-pham`)
  - Quản lý yêu cầu (`/lms/admin/lms-yeu-cau`)
  - Quản lý lộ trình (`/lms/admin/lms-lo-trinh`)
  - Quản lý nội quy (`/lms/admin/lms-noi-quy`)
  - Quản lý thư viện (`/lms/admin/lms-thu-vien`)
  - Thống kê phí dịch vụ (`/lms/admin/lms-phi-dv`)
  - Thống kê số buổi học (`/lms/admin/lms-thong-ke-buoi-hoc`)
  - Sắp xếp lớp (`/lms/admin/lms-sap-xep-lop`)

### 1.2. Nội dung chính
**4 thẻ thống kê (`.lms-stat-card`, truy vấn trực tiếp từ DB):**
1. **Học viên đang học** (`fa-solid fa-user-graduate`): `COUNT(db_lms_students WHERE status = 'studying')`
2. **Giáo viên đang hoạt động** (`fa-solid fa-chalkboard-user`): `COUNT(db_lms_teachers WHERE status = 'active')`
3. **Lớp đang hoạt động** (`fa-solid fa-school`): `COUNT(db_lms_classes WHERE status = 'active' AND deleted_at IS NULL)`
4. **Lớp học chờ xếp** (`fa-solid fa-shuffle`): `COUNT(db_lms_classes WHERE status = 'arranging' AND deleted_at IS NULL)`

**Bảng "Thống kê nhóm chương trình" (`.lms-table`, truy vấn từ `db_lms_programs` & `db_lms_classes`):**
- Các cột: **Nhóm chương trình** | **Số lớp đang học** (`status = 'active'`) | **Số lớp đang sắp xếp** (`status = 'arranging'`) | **Số lớp tạm nghỉ** (`status = 'paused'`)
- Dữ liệu mẫu các nhóm chương trình: Toán TDN 1-1, TDN Khác, Tiếng Anh 1-1, Tiếng Việt 1-1, Online đông người miễn phí, Online đông người có phí, Toán 1-1.

**Lịch tuần này (`.lms-week-timeline` — Timeline 7 ngày từ Thứ 2 → Chủ nhật của tuần hiện tại):**
- Trục dọc: Khung giờ từ `1:00` đến `24:00` (`44px / giờ`), cột ngày hôm nay được tô nổi bật (`.is-today`).
- Các buổi học (`db_lms_sessions`) có cùng ngày và cùng khung giờ `start_time - end_time` được gom nhóm qua hàm `lms_group_timeline_sessions()`:
  - Hiển thị thẻ `.lms-wt-card`: `[Mã lớp đầu tiên]` + `(N)` (nếu có từ 2 lớp trở lên trùng khung giờ, kèm tối đa 3 lớp thẻ xếp tầng `.lms-wt-stack-layer` phía sau) + khung giờ `HH:mm - HH:mm`.
  - Phân biệt màu theo loại lớp (`class_type`): Xanh dương (`.lms-wt-card-1on1`) = **Lớp 1-1**, Cam (`.lms-wt-card-group`) = **Lớp đông người** (có thanh chú thích `.lms-wt-legend` bên dưới).
- **Modal chi tiết khung giờ (`#lmsWtModal`):** Khi click vào bất kỳ ô lịch nào trên timeline, popup mở ra hiển thị:
  - Tiêu đề (`#lmsWtModalTitle`): `Thứ X, dd/mm/yyyy HH:mm-HH:mm`
  - Phụ đề (`#lmsWtModalSubtitle`): `N lớp:`
  - Danh sách (`#lmsWtModalList`): Mỗi dòng gồm link `[Mã lớp] - [Tên giáo viên]` (trỏ tới `/lms/admin/lms-lop/sua?id={class_id}`) và `Sĩ số: X`.

## 2. Học sinh - Lớp học

- **Route:**
  - Danh sách ma trận: `GET /lms/admin/lms-lop-hoc-sinh?type=group|one_on_one`
  - Thêm lớp cho học viên: `GET|POST /lms/admin/lms-lop-hoc-sinh/them?student_id={id}&type={type}`
  - Chuyển lớp: `GET|POST /lms/admin/lms-lop-hoc-sinh/chuyen?student_id={id}&from_class_id={class_id}`
  - Xoá học viên khỏi lớp: `GET /lms/admin/lms-lop-hoc-sinh/xoa?student_id={id}&class_id={class_id}&type={type}`
- **Source & Templates:**
  - `lms/app/admin/sources/lms-lop-hoc-sinh.php`
  - `lms/app/admin/templates/lms-lop-hoc-sinh/hienthi_tpl.php`, `them_tpl.php`, `chuyen_tpl.php`
- **Service:** `Lms\EnrolmentService::listMatrix()`, `enrollStudent()`, `transferStudent()`, `removeStudentFromClass()`, `studentClassOptions()`
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin/Học sinh - Lớp học - Lớp đông người.png`, `Tai-lieu-phat-trien/admin/Học sinh - Lớp học - Lớp 1 kèm 1.png`

### 2.1. Layout & Bộ lọc
- **2 Tab chuyển đổi** (dùng component `tabs`):
  - **Lớp đông người** (`type=group`, mặc định)
  - **Lớp 1 kèm 1** (`type=one_on_one`)
- **Thanh tìm kiếm:**
  - Ô `"Tìm theo HV"` (`form/input`, `name="q_student"`, `type="search"`)
  - Ô `"Tìm theo lớp"` (`form/input`, `name="q_class"`, `type="search"`)
  - Nút **Tìm** (`button`, `variant="outline-secondary"`)
- **Dòng tổng kết:** `Tổng: N học viên` + phân trang component `pagination` (`$perPage = 20`).

### 2.2. Bảng ma trận Học viên × Chương trình học
- **Cột cố định:**
  - `Username` (`db_lms_users.username`)
  - `Họ tên` (`db_lms_students.full_name`)
  - `Khối` (`db_lms_students.grade`, tự động lược bỏ chữ `"Lớp "` ở đầu để hiển thị gọn số khối)
- **Nhóm cột động `Mã lớp (chia theo chương trình học)`:**
  - Hệ thống tự động truy vấn tất cả chương trình (`db_lms_programs`) có ít nhất 1 lớp đang hoạt động thuộc tab hiện tại (`group` hoặc `one_on_one`) để sinh cột con động (thêm chương trình mới không cần sửa template).
  - Trong mỗi ô giao giữa Học viên và Chương trình:
    - Hiển thị danh sách các lớp học viên đang theo học thuộc chương trình đó (`.lms-matrix-code`).
    - Mỗi `class_code` là link dẫn tới `/lms/admin/lms-lop/sua?id={class_id}`, kèm icon đồng hồ (`fa-regular fa-clock`) có tooltip hiển thị lịch học cố định (`schedules_text`).
    - Nút **Chuyển lớp** (`button`, `icon="fa-solid fa-arrow-right-arrow-left"`, `variant="outline-secondary"`, `size="sm"`).
    - Nút **Xoá khỏi lớp** (`button`, `icon="fa-solid fa-trash"`, `variant="outline-danger"`, `size="sm"`, có hộp thoại confirm; khi xoá vẫn giữ lại lịch sử các buổi đã học).
- **Cột `Hành động` (cuối dòng):**
  - Nút **Thêm lớp** (`button`, `label="Thêm lớp"`, `icon="fa-solid fa-plus"`, `variant="outline-primary"`, `size="sm"`).

### 2.3. Form "Thêm lớp cho học viên" (`them_tpl.php`) & "Chuyển lớp" (`chuyen_tpl.php`)
- Sử dụng bố cục 1 cột căn giữa (`col-12 col-md-10 col-lg-8 col-xl-7`) với các component chuẩn:
  - `form/input` (read-only): Hiển thị `Họ tên (Mã HV)` và `Lớp hiện tại` (khi chuyển lớp).
  - `form/select` (`class_id` hoặc `to_class_id`): Liệt kê các lớp khả dụng theo định dạng `Mã lớp — Chương trình — Mã GV Tên GV — Lịch học`.
  - `form/actions_bar`: Nút **Thêm** / **Chuyển** (`variant="secondary"`) + nút **Huỷ** quay lại tab hiện tại.
- **Quy tắc nghiệp vụ (`EnrolmentService`):** Lớp `1 kèm 1` (`one_on_one`) chỉ cho phép tối đa 1 học viên đang active — tầng Service tự động chặn nếu thêm hoặc chuyển học viên thứ 2 vào lớp 1-1.

## 3. Quản lý lớp học

- **Route:**
  - Danh sách lớp: `GET /lms/admin/lms-lop`
  - Thêm / Sửa lớp: `GET|POST /lms/admin/lms-lop/them`, `GET|POST /lms/admin/lms-lop/sua?id={id}`
  - Lịch học của lớp: `GET /lms/admin/lms-lop/lich-hoc?id={id}&week=YYYY-MM-DD`
  - Chi tiết buổi học & chấm BTVN: `GET /lms/admin/lms-lop/buoi?id={session_id}`, `POST /lms/admin/lms-lop/cap-nhat-buoi`, `POST /lms/admin/lms-lop/cham-btvn`
  - Điều chỉnh số buổi hàng loạt (AJAX + POST): `GET /lms/admin/lms-lop/ajax-danh-sach-lop`, `POST /lms/admin/lms-lop/dieu-chinh-xoa-buoi`, `POST /lms/admin/lms-lop/dieu-chinh-them-buoi`
  - Xoá lớp (soft-delete): `GET /lms/admin/lms-lop/xoa?id={id}`
- **Source & Templates:**
  - `lms/app/admin/sources/lms-lop.php`
  - `lms/app/admin/templates/lms-lop/hienthi_tpl.php`, `form_tpl.php`, `lich-hoc_tpl.php`, `buoi_tpl.php`
- **Services:** `Lms\ClassService`, `Lms\CourseCycleService`, `Lms\AdminReportService`
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin/Lớp học.png`, `Tai-lieu-phat-trien/admin/Quản lý lớp học - Thêm.png`, `Tai-lieu-phat-trien/admin/Quản lý lớp học - Điều chỉnh số buổi học.png`

### 3.1. Bộ lọc tìm kiếm & Thanh công cụ (`hienthi_tpl.php`)
- **Form bộ lọc** (sử dụng `form/input`, `form/select`, `button`):
  1. `Tìm theo lớp` (`form/input`, `name="q"`): Tìm theo tên lớp hoặc mã lớp.
  2. `Tìm theo GV` (`form/select`, `name="teacher_id"`): Dropdown danh sách giáo viên (`Mã GV — Họ tên`).
  3. `Tìm theo chương trình học` (`form/select`, `name="program_id"`): Dropdown nhóm chương trình (`db_lms_programs`).
  4. `Tìm theo TG` (`form/select`, `name="day_of_week"`): Lọc theo thứ học trong tuần (`Thứ 2` → `Chủ nhật`).
  5. Nút **Tìm** (`button`, `variant="outline-secondary"`).
- **Cụm nút hành động bên phải:**
  - Nút **Điều chỉnh số buổi học** (`button`, `variant="secondary"`, mở Modal `#lmsAdjustSessionsModal`).
  - Nút **Thêm** (`button`, `variant="primary"`, `icon="fa-solid fa-plus"`, dẫn tới `/lms/admin/lms-lop/them`).
- **Dòng tổng kết:** `Tổng: N lớp` + phân trang component `pagination`.

### 3.2. Cột bảng danh sách lớp học
| Cột | Mô tả thực tế |
|---|---|
| **Mã lớp** | Tên lớp (`name` in đậm) + Mã lớp (`class_code`) kèm `badge` loại lớp (`1 kèm 1` hoặc `Đông người`). |
| **Chương trình học** | Tên nhóm chương trình (`program_name`) + tên môn/danh mục (`subject_name` chữ nhỏ bên dưới). |
| **Sĩ số** | Tổng số học viên đang active trong lớp (`student_count`). |
| **Thời gian** | Lịch cố định trong tuần ghép bằng dấu ` / ` (VD: `Thứ 2 17:30 - 19:00 / Thứ 6 17:30 - 19:00`). |
| **Giáo viên** | Mã GV (`teacher_code` in đậm) + `[Tên giáo viên]` ở dòng dưới. |
| **Khoá học** | Hiển thị tối đa **2 khoá học gần nhất** (`array_slice($cycles, 0, 2)` từ `db_lms_course_cycles`) xếp cạnh nhau. Mỗi khoá hiển thị: Tên khoá (`Khóa 1`, `Khóa 2`... kèm badge `Đóng` nếu `status = 'closed'`), `Bắt đầu`, `Kết thúc`, `Đã học`, `Còn lại`. |
| **Chuyên đề đang học** | Tên chuyên đề hiện tại của lớp (`current_topic`). |
| **Hành động** | 2 dòng thao tác: `Sửa | Lịch học` và `Xoá` (có confirm). |

### 3.3. Form "Thêm / Sửa lớp học" (`form_tpl.php`)
Sử dụng các shared form components (`form/select`, `form/input`, `form/field_row`, `form/addon_input`, `form/textarea`, `button`):

**Khối 1 — Thông tin lớp học:**
| Trường | Bắt buộc | Component / ID | Mô tả & Logic JS |
|---|---|---|---|
| Nhóm chương trình | * | `form/select` (`#fld-program`, `name="program_id"`) | Nạp từ `db_lms_programs`. Khi đổi sẽ tự động lọc lại dropdown Chương trình học và Chuyên đề. |
| Chương trình học | * | `form/select` (`#fld-subject`, `name="subject_id"`) | Nạp từ `db_lms_subjects` (gắn `data-program-id`). |
| Loại lớp | * | `form/select` (`#fld-class-type`, `name="class_type"`) | `1 kèm 1` (`one_on_one`) / `Đông người` (`group`). Tự động chuyển đổi chế độ chọn học viên (1 HV vs nhiều HV) và đổi giá trị mặc định Hạn check BTVN (`0` giờ cho 1-1, `24` giờ cho đông người). |
| Mã lớp | * | `form/input` (`#fld-name`, `name="name"`) | Nhập mã/tên hiển thị lớp (VD: `Toán 227`). |
| Chọn giáo viên | * | `form/select` (`#fld-teacher`, `name="teacher_id"`) | Danh sách GV active (`Mã GV - Họ tên`). Khi chọn GV sẽ tự động điền `% GV nhận` theo `service_fee_percentage` mặc định của GV đó. |
| Chọn học viên | * | `form/field_row` (`name="student_ids[]"`) | Nếu `1 kèm 1`: hiển thị dropdown đơn (`#fld-student-single`). Nếu `Đông người`: hiển thị Select2 multi-select (`#fld-student-multi`) cho phép tìm và chọn nhiều học viên. |
| Lịch cố định | * | `form/class_schedule_slots` (`#lms-schedules-container`) | Component dùng chung với trang Lịch học: mỗi dòng gồm Thứ (`sch_day[]`) + Giờ bắt đầu (`sch_start[]`) - Giờ kết thúc (`sch_end[]`), nút `Xoá` / `Thêm`. Bắt buộc tối thiểu 1 dòng. |
| Học phí 1 buổi | * | `form/addon_input` (`name="price_per_session"`) | Nhập số tiền + hậu tố `đồng` (VD: `560000`). |
| Phần trăm gv nhận | * | `form/addon_input` (`name="teacher_percentage"`) | Nhập số `%` (0–100, bước 0.5) + hậu tố `%`. Cho phép chỉnh riêng cho lớp mà không ghi đè hồ sơ gốc của GV. |
| Áp dụng từ ngày | * | `form/input` (`type="date"`, `name="start_date"`) | Ngày bắt đầu áp dụng lịch cố định. |
| Ghi chú | | `form/textarea` (`name="note"`) | Ghi chú nội bộ của lớp học. |
| Chọn chuyên đề | | `form/field_row` (`#fld-topics`, `name="topic_ids[]"`) | Select2 multi-select, cho phép chọn nhiều chuyên đề thuộc nhiều Danh mục; options được nhóm theo Danh mục. |

**Khối 2 — Đăng ký khoá đầu:**
- `Số buổi:` (`form/addon_input`, `name="total_sessions"`, hậu tố `buổi`, mặc định `24`).
- `Ngày bắt đầu` (`form/input`, `type="date"`, `name="cycle_start_date"`, mặc định ngày hiện tại).

**Khối 3 — Cài đặt (quy chế quét thời hạn cấp lớp):**
| Mục | Toggle | Giá trị | Phạt GV | Ghi chú |
|---|---|---|---|---|
| **Hạn giao BTVN** | `#sw-hw-deadline` | `hw_deadline_time` (mặc định `12:00`) — *của ngày hôm sau* | `hw_penalty_ticks` (`1` tick) | |
| **Hạn check BTVN** | `#sw-hw-check` | `hw_check_hours` (mặc định `0` giờ với lớp 1-1, `24` giờ với lớp đông người) — *giờ trước buổi tiếp theo* | `hw_check_penalty_ticks` (`1` tick) | *Chỉ áp dụng nếu học sinh có nộp BTVN trước deadline GV giao.* |
| **Hạn chấm test** | `#sw-test-grace` | `test_grace_hours` (mặc định `72`) — *giờ kể từ khi hs nộp bài* | `test_penalty_ticks` (`1` tick) | |

**Khối 4 — Nút thao tác:** `Lưu thay đổi` / `Huỷ`. Không còn bảng "Khóa học của lớp" ở cuối form (đã bỏ theo thiết kế thực tế).

### 3.4. Trang "Chi tiết buổi học" (`/lms/admin/lms-lop/buoi?id={session_id}` — `buoi_tpl.php`)
- **Card 1 — Cập nhật nội dung buổi học (`POST /lms/admin/lms-lop/cap-nhat-buoi`):**
  - Hiển thị thông tin `Lớp`, `GV`, `Chuyên đề`.
  - Cho phép Admin chỉnh sửa: Chuyên đề (`topic_ids[]` — Select2 multiple), Nội dung buổi học (`lesson_summary`), Nội dung BTVN (`hw_content`), Hạn nộp BTVN (`hw_deadline`), Đính kèm thêm file BTVN (`hw_files[]`) và nhấn **Cập nhật nội dung**.
- **Card 2 — Bài nộp học viên & Chấm bài (`POST /lms/admin/lms-lop/cham-btvn`):**
  - Liệt kê từng học viên của buổi học kèm trạng thái điểm danh và danh sách file bài nộp (`hw_submission_files`).
  - Form chấm/ghi đè điểm của Admin: Điểm `/20` (`grade`), Nhận xét (`teacher_feedback_text`), Lý do ghi đè (`reason`), File nhận xét đính kèm (`teacher_feedback_files[]`) và nút **Lưu chấm bài**.

## 4. Modal "Chi tiết lịch giáo viên" (từ Sắp xếp lớp)

**Tiêu đề:** `Chi tiết lịch giáo viên - [Mã GV] - [Tên GV]`

**Thông tin giáo viên:**
- Địa chỉ hiện tại
- Quận / Phường có thể dạy
- Số lớp đang dạy
- Số lớp tối đa có thể nhận
- Có thể dạy online không

**Lịch tuần (timeline 7 cột × 1:00 - 24:00, cuộn dọc):**
- Ô màu hồng: **Lịch cố định của lớp đang dạy** (hiển thị Mã HV - Tên HV + khung giờ; lớp chưa có HV hiển thị tên lớp)
- Ô màu xanh lá: **Lịch trống có thể nhận thêm lớp**
- *Bấm vào tên học viên sẽ nhảy qua trang chi tiết của học viên*

**Ghi chú tuần** (textarea) + Nút **Cập nhật**
- Lưu theo tuần hiện tại (week_start = thứ 2 của tuần) trong `db_lms_teacher_notes`, tối đa 5000 ký tự.
- Chỉ hiển thị nút Cập nhật khi admin có quyền `edit`; ngược lại textarea ở chế độ chỉ đọc.

## 5. Quản lý sắp xếp lớp

> *Khi nhập username học viên thì các thông tin còn lại sẽ tự động điền theo thông tin tài khoản học viên, nhưng admin vẫn có thể chỉnh sửa các tiêu chí (nhưng ko ảnh hưởng thông tin tài khoản)*

### Block 1: "Tìm kiếm theo tiêu chí" (toggle ON, *có tìm theo tiêu chí này ko)
- Username học viên (text)
  - *Khi nhập username, hệ thống tự điền giới tính / chương trình / quận-phường / Online-Offline theo hồ sơ HV; admin vẫn sửa được và không ghi ngược vào tài khoản.*
- Giới tính (dropdown: Nam / Nữ / Khác)
- Chương trình học (dropdown lấy từ `db_lms_subjects`)
- Quận / Phường (multi-select tag, chọn nhiều)
- Online / Offline (dropdown)
- Nút **Tìm**

### Block 2: "Tìm theo lịch trống" (toggle ON)
- Mỗi dòng: ngày (date picker, hiện kèm thứ) + một hoặc nhiều khung giờ bắt đầu - kết thúc
- Nút X xoá từng khung giờ / xoá cả ngày
- Link **Thêm giờ**, **Thêm ngày**
- *Nếu xoá hết khung giờ, thì mặc định tìm theo tất cả các khung giờ của ngày đó*
- Điều kiện giữa các ngày là OR (khớp lịch trống của bất kỳ ngày nào)
- Nút **Tìm**

### Block 3: "Các giáo viên phù hợp" (Tổng: N)
- Họ tên, username (text)
- Giới tính (dropdown)
- Chương trình dạy (dropdown; GV để trống chương trình = dạy mọi chương trình)
- Quận / Phường có thể dạy (multi-select tag; GV để trống = mọi quận)
- Trạng thái (Đang dạy / Tạm ngưng / Đã nghỉ)
- Nút **Tìm**
  - **Ghi chú**: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác (được thêm)
- **Sắp xếp** (dropdown tự áp dụng khi đổi): Sắp xếp theo hạng giảm dần / theo hạng tăng dần / theo cập nhật gần nhất / theo cập nhật xa nhất

**Bảng kết quả** (cột):
- Username (tooltip hiện mã GV; fallback mã GV nếu chưa có username)
- Họ tên
- Giới tính
- Hạng (Đồng / Vàng / Xanh — badge màu)
- Chương trình dạy (nhiều môn cách nhau dấu phẩy; "Tất cả" nếu để trống)
- Quận / Phường có thể dạy (cách nhau " / "; "Tất cả" nếu để trống)
- Số lớp đang dạy
- Trạng thái (Đang dạy / Tạm ngưng / Đã nghỉ)
- Lần cuối cập nhật (Thứ + dd-mm-Y HH:ii)
- Hành động: **Chi tiết** (mở modal lịch GV) / **Xoá** (ẩn GV khỏi danh sách gợi ý trong phiên làm việc — không xoá tài khoản; nút **Hiện lại N giáo viên đã ẩn** xuất hiện phía trên bảng để hoàn tác)

## 6. Quản lý tài khoản

- **Route:**
  - Danh sách 2 tab: `GET /lms/admin/lms-tai-khoan?tab=hoc-vien|giao-vien`
  - Học viên (CRUD & Trạng thái): `GET|POST /lms/admin/lms-tai-khoan/them-hoc-vien`, `GET|POST /lms/admin/lms-tai-khoan/sua-hoc-vien?id={id}`, `GET /lms/admin/lms-tai-khoan/khoa-hoc-vien?id={id}`, `GET /lms/admin/lms-tai-khoan/mo-hoc-vien?id={id}`, `GET /lms/admin/lms-tai-khoan/xoa-hoc-vien?id={id}`, `POST /lms/admin/lms-tai-khoan/ajax-update-hoc-vien`
  - Giáo viên (CRUD & Trạng thái): `GET|POST /lms/admin/lms-tai-khoan/them-giao-vien`, `GET|POST /lms/admin/lms-tai-khoan/sua-giao-vien?id={id}`, `GET /lms/admin/lms-tai-khoan/khoa-giao-vien?id={id}`, `GET /lms/admin/lms-tai-khoan/mo-giao-vien?id={id}`, `GET /lms/admin/lms-tai-khoan/xoa-giao-vien?id={id}`
- **Source & Templates:**
  - `lms/app/admin/sources/lms-tai-khoan.php` (hỗ trợ cả route rời `lms-hoc-vien.php`, `lms-giao-vien.php`)
  - `lms/app/admin/templates/lms-tai-khoan/hienthi_tpl.php`, `lms/app/admin/templates/lms-hoc-vien/form_tpl.php`, `lms/app/admin/templates/lms-giao-vien/form_tpl.php`
- **Service & Enum:** `Lms\UserService`, `lms/lib/lms_enums.php` (`student_statuses`, `teacher_statuses`, `teacher_ranks`, `learning_types`, `genders`, `yes_no`)
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin/Tài khoản HV.png`, `Tai-lieu-phat-trien/admin/Tài khoản GV.png`, `Tai-lieu-phat-trien/admin/Quản lý tài khoản - Học sinh - Thêm.png`, `Tai-lieu-phat-trien/admin/Quản lý tài khoản - Học sinh - Sửa.png`, `Tai-lieu-phat-trien/admin/Quản lý tài khoản -Giáo Viên - Sửa .png`

### 6.1. Tabs điều hướng
Sử dụng component `tabs`:
- **Tài khoản học viên** (`tab=hoc-vien`, mặc định)
- **Tài khoản giáo viên** (`tab=giao-vien`)

### 6.2. Tab "Tài khoản học viên"
> *Cột `Số ưu tiên` và `Trạng thái` hỗ trợ chỉnh nhanh inline ngay trên bảng qua AJAX (`POST /lms/admin/lms-tai-khoan/ajax-update-hoc-vien`).*

**Bộ lọc & Thanh công cụ:**
- Ô tìm kiếm `Mã / Tên học viên` (`form/input`, `name="q"`, `bare=true`)
- Dropdown `Chương trình học` (`form/select`, `name="subject_id"`, nạp từ `db_lms_subjects`)
- Nút **Tìm** (`button`, `variant="outline-secondary"`)
- Nút **Thêm** (`button`, `variant="primary"`, `icon="fa-solid fa-plus"`, dẫn tới `/lms/admin/lms-tai-khoan/them-hoc-vien`)
- Dòng tổng kết: `Tổng: N học sinh` + phân trang `pagination` (`$perPage = 20`).

**Bảng danh sách học viên (10 cột):**
Ghim cột khi cuộn ngang (`.lms-table-pin` + `data-pin-left/right`, offset do `LMS.initPinnedColumns()` tính): "Số ưu tiên" + "Username" ghim trái (tổng 219px), "Hành động" ghim phải (118px).
| Cột | Mô tả thực tế |
|---|---|
| **Số ưu tiên** | Có icon filter; hiển thị ô `<input type="number" class="lms-inline-priority">` cho phép sửa nhanh inline. |
| **Username** | `db_lms_users.username` (in đậm) + `badge` đỏ `Khoá` nếu `user_status = 'locked'`. |
| **Chương trình học** | Giải mã mảng JSON `db_lms_students.subject_ids` sang danh sách tên môn cách nhau dấu phẩy (fallback `subject_name`). |
| **Lịch học hiện tại** | Hiển thị tối đa 3 buổi học gần nhất từ các lớp active của học sinh (`dd/mm HH:mm–HH:mm (Mã lớp)`), hoặc `class_names` / `Chưa có lịch`. |
| **Giáo viên 1-1** | Tên giáo viên phụ trách lớp 1 kèm 1 của học sinh (`teacher_1on1`). |
| **Ghi chú** | `db_lms_students.note`. |
| **Trạng thái** | Có icon filter; hiển thị dropdown `.lms-inline-status` chỉnh nhanh inline theo enum `student_statuses` (`Đang sắp xếp`, `Chờ học phí`, `Đang học`, `Nghỉ học`, `Tạm nghỉ`). |
| **Chờ sắp xếp từ** | Định dạng `dd/mm/yyyy` (`waiting_from`, fallback `created_at`). |
| **Ngày dự kiến bắt đầu học** | Định dạng `dd/mm/yyyy` (`expected_start_date`, fallback `created_at + 3 days`). |
| **Hành động** | `Chi tiết | Khoá` (hoặc `Mở` nếu đang bị khoá). Link "Sửa" đã bỏ vì trùng URL với "Chi tiết" (cùng mở form `sua-hoc-vien` / `sua-giao-vien`). |

### 6.3. Form "Thêm / Chỉnh sửa học viên" (`lms/app/admin/templates/lms-hoc-vien/form_tpl.php`)
Chuẩn hoá 100% bằng hệ thống LMS Form Components (`form/input`, `form/select`, `form/multi_select_tags`, `form/schedule_slots`, `form/date_picker`, `form/textarea`, `form/avatar_picker`, `button`):

| # | Trường | Bắt buộc | Component | Mô tả / Quy tắc thực tế |
|---|---|---|---|---|
| 1 | Số ưu tiên | | `form/input` (`name="priority_number"`, `type="number"`) | **Only số** (chặn ký tự không phải số qua `oninput`), mặc định `1`. |
| 2 | Họ tên học viên | * | `form/input` (`name="full_name"`) | VD: `Hà Phương` |
| 3 | Username | * | `form/input` (`name="username"`) | VD: `haphuong227` |
| 4 | Password | *(khi thêm)* | `form/input` (`name="password"`, `type="password"`) | Khi sửa: để trống nếu giữ nguyên mật khẩu cũ. |
| 5 | User code | | `form/input` (`name="student_code"`) | VD: `Master227` (chế độ sửa hiển thị `readonly`). |
| 6 | Email | * | `form/input` (`name="email"`, `type="email"`) | VD: `haphuong@gmail.com` |
| 7 | Họ tên phụ huynh | | `form/input` (`name="parent_name"`) | VD: `mai phương` |
| 8 | Số điện thoại | * | `form/input` (`name="parent_phone"`, `type="tel"`) | VD: `0988098097` |
| 9 | Hình thức học | | `form/select` (`name="learning_type"`, `enum="learning_types"`) | `Online` / `Offline` (mặc định `Online`). |
| 10 | Địa chỉ hiện tại | | `form/input` (`name="current_address"`) | VD: `181 Cao Thắng, Phường Hoà Hưng, TPHCM` |
| 11 | Quận / Phường | | `form/multi_select_tags` (`name="teachable_districts[]"`) | Chia 2 nhóm optgroup: **Các quận TP.HCM** (21 quận/huyện) và **Khu vực TP. Thủ Đức (Tất cả 30 phường)**, cho phép chọn nhiều tag. |
| 12 | Lớp | | `form/input` (`name="grade"`) | VD: `5` |
| 13 | Chương trình học | | `form/multi_select_tags` (`name="subject_ids[]"`) | Cho phép chọn nhiều môn từ `db_lms_subjects` (ưu tiên thứ tự: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, ..., Khác). |
| 14 | Trường học | | `form/input` (`name="school_name"`) | VD: `Trường tiểu học Yên Thế` |
| 15 | Giới tính giáo viên | | `form/select` (`name="preferred_teacher_gender"`) | `Nam` (`male`), `Nữ` (`female`), `Tuỳ ý` (`''`). |
| 16 | Thời gian rảnh | | `form/field_row` + `form/schedule_slots` (`#lmsStudentSlots`) | Cụm chọn Thứ + nhiều khung giờ (`slot_day`, `slot_start`, `slot_end`) kèm nút **Thêm giờ** / **Thêm ngày** (mặc định Thứ 2 `17:30 - 19:00`). |
| 17 | Chờ sắp xếp từ | | `form/date_picker` (`name="waiting_from"`) | Mặc định ngày hôm nay, cho phép chọn lại. |
| 18 | Ngày dự kiến bắt đầu học | | `form/date_picker` (`name="expected_start_date"`) | Mặc định ngày hôm nay, cho phép chọn lại. |
| 19 | Ghi chú | | `form/textarea` (`name="note"`) | Ghi chú học viên / phụ huynh. |
| 20 | Trạng thái | | `form/select` (`name="status"`, `enum="student_statuses"`) | `Đang sắp xếp`, `Chờ học phí`, `Đang học`, `Nghỉ học`, `Tạm nghỉ`. |
| 21 | Ảnh đại diện | | `form/avatar_picker` (`name="avatar_file"`) | Chọn hình (`jpg, jpeg, png, webp`) + khung preview ảnh có nút xoá đỏ. |

Nút hành động: `button` (`size="lg"`, `variant="secondary"`) nhãn **Thêm** (khi tạo mới) hoặc **Lưu** (khi chỉnh sửa).

### 6.4. Tab "Tài khoản giáo viên"
**Bộ lọc & Thanh công cụ:**
- Ô tìm kiếm `Mã / Tên giáo viên` (`form/input`, `name="q"`, `bare=true`) + nút **Tìm** (`button`, `variant="outline-secondary"`).
- Nút **Thanh toán phí dịch vụ** (`button`, `variant="secondary"`, mở Modal `#lmsServiceFeePaymentModal` hoặc dẫn tới `/lms/admin/lms-phi-dv`).
- Nút **Thêm** (`button`, `variant="primary"`, `icon="fa-solid fa-plus"`, dẫn tới `/lms/admin/lms-tai-khoan/them-giao-vien`).

**Bảng danh sách giáo viên (9 cột):**
Ghim cột khi cuộn ngang: "Họ và tên" ghim trái (150px, nhờ `min-width` trên `<th>`), "Hành động" ghim phải (118px) — tổng cụm ghim 268px.
| Cột | Mô tả thực tế |
|---|---|
| **Họ và tên** | Tên giáo viên (`full_name` in đậm) + dòng dưới hiển thị `Hạng: <Màu theo enum teacher_ranks>` (`Đồng` / `Vàng` / `Xanh`). |
| **Username** | `db_lms_users.username` (in đậm) + `badge` đỏ `Khoá` nếu `user_status = 'locked'`. |
| **Chương trình dạy** | Giải mã mảng JSON `teachable_subjects` sang tên các môn học. |
| **Số lớp đang dạy** | `active_classes` (số nguyên). |
| **Số điện thoại** | `db_lms_users.phone`. |
| **Cam kết 08** | `Có` (chữ xanh đậm nếu `is_commit_08 = 1`) / `Không` (chữ xám). |
| **Phí dịch vụ** | Số tiền định dạng VNĐ (`đ`). |
| **Số tick tháng này** | `month_ticks` (số nguyên). |
| **Hành động** | `Chi tiết | Khoá` (hoặc `Mở` nếu đang bị khoá). Link "Sửa" đã bỏ vì trùng URL với "Chi tiết" (cùng mở form `sua-hoc-vien` / `sua-giao-vien`). |

### 6.5. Form "Thêm / Chỉnh sửa giáo viên" (`lms/app/admin/templates/lms-giao-vien/form_tpl.php`)
Dẫn chứng mockup: `admin/Quản lý tài khoản -Giáo Viên - Sửa .png` + `admin-ver2/Tài khoản GV mới.png` (H1 trang tạo: **"Tài khoản giáo viên mới"**).
Chuẩn hoá 100% bằng hệ thống LMS Form Components & `LmsEnum`:

| # | Trường | Bắt buộc | Component | Mô tả / Quy tắc thực tế |
|---|---|---|---|---|
| 1 | Họ tên giáo viên | * | `form/input` (`name="full_name"`) | VD: `Thái Phụng` |
| 2 | Username | * | `form/input` (`name="username"`) | VD: `TE125` |
| 3 | Mật khẩu | | `form/input` (`name="password"`, `type="password"`) | Để trống để hệ thống tự sinh (khi tạo mới) hoặc giữ nguyên (khi sửa). |
| 4 | User code | | `form/input` (`name="teacher_code"`) | VD: `TE125` |
| 5 | Số điện thoại | * | `form/input` (`name="phone"`, `type="tel"`) | VD: `0707676299` |
| 6 | Email | * | `form/input` (`name="email"`, `type="email"`) | VD: `ngothaiphung412@gmail.com` |
| 7 | CCCD | | `form/input` (`name="cccd"`) | VD: `0795565656` |
| 8 | Ngày cấp | | `form/date_picker` (`name="issue_date"`) | VD: `04/12/2003` |
| 9 | Nơi cấp | | `form/input` (`name="issue_place"`) | VD: `Bộ Công An` |
| 10 | Mã số thuế | | `form/input` (`name="tax_code"`) | VD: `3215484562` (validate đúng **10 chữ số**). |
| 11 | Địa chỉ hiện tại | | `form/input` (`name="current_address"`) | VD: `181 Cao Thắng, Phường Hoà Hưng, TPHCM` |
| 12 | Quận / Phường có thể dạy | | `form/multi_select_tags` (`name="teachable_districts[]"`) | Chia 2 nhóm optgroup: **Các quận TP.HCM** và **Khu vực TP. Thủ Đức (Tất cả 30 phường)**, chọn nhiều tag. |
| 13 | Ngày sinh | | `form/date_picker` (`name="dob"`) | VD: `04/12/2003` |
| 14 | Giới tính | | `form/select` (`name="gender"`, `enum="genders"`) | `Nam` / `Nữ` / `Khác`. |
| 15 | Chương trình dạy | | `form/multi_select_tags` (`name="teachable_subjects[]"`) | Chọn một hoặc nhiều môn từ `db_lms_subjects` (dùng để khớp dữ liệu ở module Sắp xếp lớp). |
| 16 | Số lớp tối đa có thể nhận | | `form/input` (`name="max_classes"`, `type="number"`) | Số nguyên `0..50` (mặc định `1`). |
| 17 | Có thể dạy online | | `form/select` (`name="can_teach_online"`, `enum="yes_no"`) | `Có` (`1`) / `Không` (`0`). |
| 18 | Hạng giáo viên | | `form/select` (`name="rank"`, `enum="teacher_ranks"`) | `Đồng` (`bronze`), `Vàng` (`gold`), `Xanh` (`green`). |
| 19 | Số tick tháng này | | `form/addon_input` (`name="month_ticks"`, `readonly=true`) | Chỉ đọc + hậu tố `tick`. |
| 20 | Phí dịch vụ | | `form/addon_input` (`name="month_fee"`, `readonly=true`) | Chỉ đọc + hậu tố `đồng`. |
| 21 | Mức phí DV | | `form/addon_input` (`name="service_fee_percentage"`, `type="number"`) | Số `0..100` (bước `0.5`, mặc định `48`) + hậu tố `%`. |
| 22 | Tên ngân hàng | | `form/input` (`name="bank_name"`) | VD: `TMCP Công thương Việt Nam - Vietinbank` |
| 23 | Số tài khoản NH | | `form/input` (`name="bank_account"`) | VD: `565656565` |
| 24 | Tên chủ tài khoản NH | | `form/input` (`name="bank_owner"`, `uppercase=true`) | Tự động viết hoa, VD: `NGO THAI PHUNG` |
| 25 | Cam kết 08 | | `form/select` (`name="is_commit_08"`, `enum="yes_no"`) | `Có` (`1`) / `Không` (`0`). |
| 26 | Trạng thái | | `form/select` (`name="status"`, `enum="teacher_statuses"`) | `Đang dạy` (`active`), `Tạm ngưng` (`paused`), `Đã nghỉ` (`quit`). |
| 27 | Trạng thái tài khoản | | `form/select` (`name="user_status"`) | `Hoạt động` (`active`) / `Bị khoá` (`locked`). |
| 28 | Ảnh đại diện | | `form/avatar_picker` (`name="avatar_file"`) | Upload ảnh (`jpg, jpeg, png, webp`) + preview có nút xoá. |

Nút hành động: `button` (`size="lg"`, `variant="primary"` — cam theo mockup) nhãn **Thêm** (khi tạo mới) hoặc **Lưu** (khi chỉnh sửa).

**Hai điểm mockup admin-ver2 đã chốt KHÔNG làm theo:**
- Field **OSF** (giá trị `Hạng 1`, kèm chip header `OSF: hạng I`): hệ thống không có cột/enum nào cho OSF (`db_lms_teachers` chỉ có `rank`), đã chốt bỏ khỏi form; header tiếp tục hiển thị `Hạng: Xanh/Vàng/Đỏ` từ `rank` (`lms/lib/lms_layout.php`).
- Nhãn **"Số tick hàng ngày"**: dữ liệu thực tế là tổng tick vi phạm **trong tháng hiện tại** (`db_lms_violations`, lọc `violation_date <= last_day(curdate())`), giữ nhãn **"Số tick tháng này"**.

**Mặc định chủ đích khác mockup** (mockup vẽ GV đã hoàn thiện, không phải mặc định tạo mới): `rank = bronze` (GV mới chưa được ưu tiên xếp lớp), `is_commit_08 = 0` (chưa ký cam kết), `service_fee_percentage = 48`.

## 7. Quản lý Bài tập về nhà (BTVN)

- **Route:** `GET /lms/admin/lms-bai-tap-ve-nha`
- **Source & Template:** `lms/app/admin/sources/lms-bai-tap-ve-nha.php`, `lms/app/admin/templates/lms-bai-tap-ve-nha/hienthi_tpl.php`
- **Service:** `Lms\AdminReportService::homeworkList()`
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin/Quản lý BTVN.png`

### 7.1. Bộ lọc tìm kiếm
Sử dụng các component `form/input` (`bare=true`) và `button`:
- **Từ ngày:** `form/input` (`name="date_from"`, `type="date"`)
- **Đến ngày:** `form/input` (`name="date_to"`, `type="date"`)
- **Từ khoá:** `form/input` (`name="q"`, `type="search"`, placeholder `"Tìm theo mã lớp, mã giáo viên"`)
- **Nút Tìm:** `button` (`type="submit"`, `variant="outline-secondary"`)

### 7.2. Bảng danh sách BTVN (8 cột)
Hiển thị dòng tổng kết `Tổng: N buổi học` và phân trang component `pagination` (`$perPage = 25`) ở cả phía trên và phía dưới bảng (bản trên cùng truyền `class => 'mt-0'` vì nằm trong toolbar `d-flex`, bản dưới dùng `mt-4` mặc định):

| Cột | Mô tả thực tế |
|---|---|
| **Thời gian** | Định dạng `Thứ X dd-mm-yyyy HH:ii` từ `session_date` + `start_time` (VD: `Thứ 4 05-08-2026 15:27`). |
| **Mã GV** | `db_lms_teachers.teacher_code` (VD: `TE125`). |
| **Mã Lớp** | `db_lms_classes.class_code` (in đậm). |
| **Chuyên đề** | `topic_name` (nếu buổi học gắn nhiều chuyên đề thì hiển thị cách nhau bởi dấu phẩy; nếu trống hiển thị `—`). |
| **BTVN** | Nội dung `hw_content` (giữ xuống dòng qua `nl2br`); nếu giáo viên chưa nhập sẽ hiển thị chữ đỏ đậm **`Chưa giao`** (`<span class="text-danger fw-semibold">Chưa giao</span>`). |
| **Nhận xét buổi học** | Nội dung `lesson_summary` (nếu trống hiển thị `-`). |
| **Thời gian cập nhật** | Định dạng `Thứ X dd-mm-yyyy HH:ii` (ưu tiên `summary_submitted_at`, nếu chưa có thì lấy `updated_at`). |
| **Hành động** | Link **Chi tiết** dẫn tới `/lms/admin/lms-lop/buoi?id={session_id}` (màn hình Chi tiết buổi học & Chấm bài của Admin). |

**Quy tắc truy vấn (`AdminReportService::homeworkList`):**
- Chỉ liệt kê các buổi học đã diễn ra (`status = 'finished'` hoặc buổi `upcoming` đã qua thời điểm kết thúc); không hiển thị các buổi đã huỷ (`cancelled`) hoặc vắng (`absent_teacher`, `absent_student`).

## 8. Quản lý vi phạm

- **Route:**
  - Danh sách & Modals: `GET /lms/admin/lms-vi-pham`
  - Điều chỉnh tick (+/-): `POST /lms/admin/lms-vi-pham/dieu-chinh`
  - Lưu quy chế miễn giảm & ngưỡng hạ phí: `POST /lms/admin/lms-vi-pham/luu-quy-che-mien-giam`
  - Lưu quy chế quét thời hạn (lớp 1-1 / đông người): `POST /lms/admin/lms-vi-pham/luu-quy-che-quet`
  - Xoá bản ghi vi phạm: `POST /lms/admin/lms-vi-pham/xoa`
- **Source & Templates:**
  - `lms/app/admin/sources/lms-vi-pham.php`
  - `lms/app/admin/templates/lms-vi-pham/hienthi_tpl.php` (đã chuẩn hoá 100% theo LMS UI/Form Components: `form/input`, `form/select`, `form/textarea`, `form/addon_input`, `form/field_row`, `button`, `pagination`)
- **Service:** `Lms\TickService` (`listViolations()`, `addManualTick()`, `saveRegulationThresholds()`, `getScanRules()`, `saveScanRules()`, `formatViolationContent()`)
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin/Quản lý vi phạm.png`, `Tai-lieu-phat-trien/admin/+_- vi phạm.png`, `Tai-lieu-phat-trien/admin/Miễn giảm.png`

### 8.1. Bộ lọc & Nút hành động
- **Thanh tìm kiếm bên trái:**
  - Ô tìm `"Mã lớp, GV"` (`form/input`, `name="q"`, `bare=true`)
  - Nút **Tìm** (`button`, `variant="outline-secondary"`) + nút **Xoá lọc** (hiện khi đang có bộ lọc).
- **Cụm nút chức năng bên phải** (chỉ hiện khi Admin có quyền `edit`):
  - Nút **Chỉnh sửa quy chế** (`button`, `variant="secondary"`, mở Modal `#lmsRegulationModal`)
  - Nút **Điều chỉnh** (`button`, `variant="secondary"`, mở Modal `#lmsAdjustViolationModal`)

### 8.2. Bảng danh sách vi phạm (7 cột)
| Cột | Mô tả thực tế |
|---|---|
| **Mã lớp** | `class_code` (fallback `class_name` hoặc `—`). |
| **Mã GV** | `teacher_code` (có tooltip hiển thị họ tên giáo viên). |
| **Thời gian vi phạm** | Định dạng `dd-mm-yyyy HH:ii` từ `created_at` / `violation_date`. |
| **Số tick mới nhất** | Hiển thị có dấu (`+1`, `+2`, `-1`...); nếu số âm (`< 0`, miễn giảm/trừ tick) hiển thị màu xanh lá `text-success`. |
| **Nội dung vi phạm** | Chuỗi mô tả chuẩn hoá qua `TickService::formatViolationContent()` (VD: *"Bạn đã bị phạt một tick vì quên ghi Lesson summary cho buổi học ngày xx/xx/xxxx"*). |
| **Số tick tháng này** | Tổng số tick lũy kế trong tháng của giáo viên đó (`month_ticks`). |
| **Hành động** | Link **Xoá** (`.js-delete-violation`, xác nhận và submit form POST ẩn có CSRF token tới `/lms/admin/lms-vi-pham/xoa`). |

**Phân trang:** Sử dụng component `pagination` (`$perPage = 20`).

### 8.3. Modal "Điều chỉnh vi phạm" (`#lmsAdjustViolationModal` — Mockup `+_- vi phạm.png`)
Gửi form `POST /lms/admin/lms-vi-pham/dieu-chinh`:
| Trường | Bắt buộc | Component | Mô tả |
|---|---|---|---|
| **Mã lớp** | | `form/select` (`#fld-adjust-class`, `name="class_id"`) | Tích hợp Select2 cho phép gõ tìm và chọn lớp học; khi chọn lớp sẽ tự động gợi ý Giáo viên phụ trách lớp đó. |
| **Giáo viên** | * | `form/select` (`#fld-adjust-teacher`, `name="teacher_id"`) | Tích hợp Select2 cho phép gõ tìm theo mã GV hoặc tên GV. |
| **Số tick** | * | `form/input` (`#fld-adjust-ticks`, `name="tick_amount"`) | Nhập số nguyên có dấu: `+1, -1, +2, -2`... |
| **Nội dung** | | `form/textarea` (`#fld-adjust-note`, `name="note"`) | Placeholder: `"Ghi nội dung điều chỉnh số tick ở đây"`. |

Nút hành động: `button` (Nút **Thêm** căn giữa).

### 8.4. Modal "Chỉnh sửa quy chế miễn giảm và ngưỡng hạ phí dịch vụ" (`#lmsRegulationModal` — Mockup `Miễn giảm.png`)
Gồm 3 biểu mẫu độc lập trong cùng Modal:

1. **Phần 1 — Bảng miễn giảm & Ngưỡng hạ phí dịch vụ (`POST /lms/admin/lms-vi-pham/luu-quy-che-mien-giam`):**
   - Bảng `#lms-exemption-table` (**Số lớp** `class_range[]` | **Số tick miễn giảm** `ticks_exempted[]` | Nút xoá dòng `×`):
     - Dữ liệu mặc định: `Từ 6 trở lên` → `10` | `5` → `9` | `4` → `7` | `3` → `5` | `1-2` → `3`.
     - Cho phép sửa trực tiếp từng ô (`form/input`), xoá dòng (`.js-remove-threshold-row`, giữ tối thiểu 1 dòng) hoặc bấm **Thêm dòng** (`#btn-add-threshold-row`, nhân bản từ `<template id="tpl-threshold-row">`).
   - Trường **Ngưỡng tick hạ phí dịch vụ** (`form/addon_input`, `name="ticks_to_demote_fee"`, mặc định `8` + hậu tố `tick`).
   - Nút **Cập nhật** (`button`).
2. **Phần 2 — Chỉnh sửa quy chế quét thời hạn lớp 1-1 (`POST /lms/admin/lms-vi-pham/luu-quy-che-quet`, `scope="one_on_one"`):**
   - Render qua helper `lvp_render_scan_rule_row()` kết hợp toggle switch + `form/input` + `form/addon_input`:
     - **Hạn giao BTVN:** Toggle `hw_deadline_enabled` + Giờ `hw_deadline_time` (`12:00` *của ngày hôm sau*) + Phạt `hw_deadline_ticks` (`1 tick`).
     - **Hạn check BTVN:** Toggle `hw_check_enabled` + Số giờ `hw_check_hours` (`0` *giờ trước buổi tiếp theo*, chỉ áp dụng nếu HS nộp BTVN trước deadline GV giao) + Phạt `hw_check_ticks` (`1 tick`).
     - **Hạn chấm test:** Toggle `test_grace_enabled` + Số giờ `test_grace_hours` (`72` *giờ kể từ khi hs nộp bài*) + Phạt `test_grace_ticks` (`1 tick`).
   - Nút **Cập nhật** (`button`).
3. **Phần 3 — Chỉnh sửa quy chế quét thời hạn lớp đông người (`POST /lms/admin/lms-vi-pham/luu-quy-che-quet`, `scope="group"`):**
   - **Hạn giao BTVN:** Toggle `hw_deadline_enabled` + Giờ `hw_deadline_time` (`12:00` *của ngày hôm sau*) + Phạt `hw_deadline_ticks` (`1 tick`).
   - **Hạn check BTVN:** Toggle `hw_check_enabled` + Số giờ `hw_check_hours` (`24` *giờ trước buổi tiếp theo*, chỉ áp dụng nếu HS nộp BTVN trước deadline GV giao) + Phạt `hw_check_ticks` (`1 tick`).
   - Nút **Cập nhật** (`button`).

## 9. Quản lý yêu cầu

### Tabs (5 tab)
1. **Request thêm buổi**
2. **Request test**
3. **Duyệt KQ test**
4. **Request đổi lịch**
5. **Kiểm tra số buổi**

### Tab "Request thêm buổi"
- Bộ lọc: Mã / Tên học viên + SĐT + nút Tìm
- Cột: Mã lớp, Mã GV, Chuyên đề, Số buổi đã học, Thời gian request
- VD: Math | TE125 | How to calculate | 3/4 | Thứ 4 05-08-2026 15:27

### Tab "Request test"
- Bộ lọc: Mã lớp, GV + nút Tìm
- Cột: Mã lớp, Mã GV, **Loại test** (Test / Minitest), Chuyên đề, **Số buổi đã học** (VD: 4/4, 3/4), Thời gian request
- Hành động: **Xác nhận** (xanh) / **Xoá** (đỏ)

### Tab "Duyệt KQ test"
- (Dựa trên `duyệt kq test.png`)

### Tab "Request đổi lịch"
- Bộ lọc: Mã lớp, GV + nút Tìm
- Cột: Mã lớp, Mã GV, **Loại** (Nghỉ 1 buổi / Lịch cố định), **Buổi cũ** (VD: "T5, 06/08/2026" hoặc "T3 17h30-19h / T5 17h30-19h"), **Buổi mới / Buổi bù** (VD: "-" hoặc "T2 17h30-19h / T4 17h30-19h"), Thời gian request
- Hành động: **Xác nhận** (xanh) / **Xoá** (đỏ)

### Tab "Kiểm tra số buổi"
- (Dựa trên `kiểm số buổi.png`)

## 10. Quản lý lộ trình

- **Route:** `/lms/admin/lms-lo-trinh` (Source: `lms/app/admin/sources/lms-lo-trinh.php`, Template: `lms/app/admin/templates/lms-lo-trinh/hienthi_tpl.php`)
- **Service:** `Lms\AdminReportService::topicList()`, `duplicateTopic()`, `deleteTopic()`

### Bộ lọc + nút
- Ô tìm kiếm bên trái (`form/input`, `name="q"`, placeholder: `"Tìm nội dung trong bảng..."`) + nút **Tìm** (`button`, `variant="outline-secondary"`): tìm theo tên chuyên đề (`top.name`), nội dung (`top.content`), template báo cáo (`top.report_template`), danh mục (`sub.name`), chương trình (`p.name`).
- Nút **Thêm** bên phải (`button`, `variant="secondary"`, link tới `/lms/admin/lms-lo-trinh/them`).

### Bảng
| Cột | Mô tả |
|---|---|
| Tên chuyên đề | `db_lms_topics.name` — VD: How to calculate correctly, Lộ trình lý thuyết KET (Reading), Lộ trình giải đề TĐN Tiếng Anh, Monthly test |
| Danh mục | `db_lms_subjects.name` — VD: Math Problems, Math IQ, Đại số, Hình học, Reading |
| Loại chuyên đề | `db_lms_topics.has_test`: **Có test** (`1` — áp dụng cả test & minitest) / **Không test** (`0` — nhập báo cáo chung) |
| Nội dung | `db_lms_topics.content` (fallback `report_template` đã lọc thẻ HTML nếu `content` trống) |
| Số buổi học tối đa | `db_lms_topics.max_sessions` — Số nguyên (VD: 3, 16, 100, 7; hiển thị `—` nếu chưa giới hạn) |
| Hành động | 3 link xếp dọc sử dụng component `button` (`variant="link"`): **Sửa** (`GET /lms/admin/lms-lo-trinh/sua?id={id}`) / **Duplicate** (`POST /lms/admin/lms-lo-trinh/duplicate` tạo bản sao `<Tên> (Bản sao)`) / **Xoá** (`POST /lms/admin/lms-lo-trinh/xoa` có kiểm tra ràng buộc khoá ngoại với bài test & chu kỳ khoá học) |

**Phân trang:** Sử dụng component `pagination` (« 1 2 3 ... ») với `$perPage = 20`.

## 11. Quản lý nội quy

- **Route:** `GET|POST /lms/admin/lms-noi-quy` (Source: `lms/app/admin/sources/lms-noi-quy.php`, Template: `lms/app/admin/templates/lms-noi-quy/hienthi_tpl.php`)
- **Service:** `Lms\ClassRulesService::getRules()`, `saveRules()`, `sanitizeHtml()`, `formatForDisplay()` (lưu tại bảng `db_lms_class_rules` với `id = 1`, ghi nhật ký `update_class_rules` vào `db_lms_audit_logs`).

### Layout & Components
- **Trình soạn thảo Rich Text** sử dụng component chuẩn `form/rich_editor` (`lms/app/admin/templates/components/form/rich_editor.php`, `bare => true`, `min_height => 420`):
  - **Hàng 1 Toolbar:** Dropdown chọn Font (`form/select`: Sans Serif, Serif, Monospace), Dropdown chọn Size (`form/select`: Small, Normal, Large, Huge), các nút `button` định dạng: **B** (In đậm), **I** (In nghiêng), **U** (Gạch chân), **S** (Gạch ngang), chọn màu chữ (`foreColor`), màu nền chữ (`hiliteColor`), chỉ số trên (**X²**), chỉ số dưới (**X₂**).
  - **Hàng 2 Toolbar:** Các nút `button`: **H1**, **H2**, trích dẫn (`blockquote`), khối code (`pre`), danh sách đánh số (`ol`), danh sách gạch đầu dòng (`ul`), giảm/tăng thụt lề (`outdent`/`indent`), căn lề xoay vòng (trái/giữa/phải), chèn liên kết (`link`), chèn hình ảnh (`image`), chèn bảng (`table`), và xoá định dạng (`removeFormat`).
  - **Vùng soạn thảo (`contenteditable`):** Placeholder `"Chỉnh sửa nội quy tại đây ..."`, tự động đồng bộ dữ liệu sang `form/textarea` ẩn (`name="content"`) khi gõ hoặc bấm Lưu.
- **Thanh nút hành động:** Sử dụng component `form/actions_bar` (Nút **Lưu** căn phải `align => 'end'`, `variant => 'secondary'`).
- **Liên thông Portal Học viên (`/lms/student/lms-noi-quy`):** Nội dung rich-text sau khi Admin lưu được làm sạch XSS qua `ClassRulesService::sanitizeHtml()` và hiển thị read-only đúng định dạng tại màn hình Nội quy lớp học của Học viên.

## 12. Quản lý thư viện

> Nguồn: `lms/app/admin/sources/lms-thu-vien.php`, template `lms/app/admin/templates/lms-thu-vien/hienthi_tpl.php`,
> component biểu tượng `lms/app/admin/templates/components/library_icon.php`, enum `lms/lib/lms_enums.php` (`library_categories`, `library_downloadable`),
> service `lms/services/Lms/LibraryService.php`.
> Dẫn chứng Mockup: `Tai-lieu-phat-trien/admin/Quản lý thư viện.png`, `Tai-lieu-phat-trien/admin/Quản lý thư viện - Thêm.png`.
> Đã chuẩn hoá 100% theo Kiến trúc LMS Shared Components (`form/input`, `form/select`, `button`, `badge`, `pagination`, `library_icon`) và `LmsEnum`.

### 12.1. Thanh công cụ tìm kiếm & nút hành động
- **Ô tìm kiếm:** Dùng component `form/input` (`bare => true`, `class => 'form-control lms-lib-search-input'`) với placeholder `"Tìm tên tài liệu..."`, chiếm phần lớn chiều ngang bên trái.
- **Nút Tìm:** Dùng component `button` (`variant => 'secondary'` — màu xanh dương `#0b57a4`), submit form tìm kiếm theo tên hoặc mô tả tài liệu.
- **Nút Upload:** Dùng component `button` (`variant => 'primary'` — màu đỏ cam `#e5501b`), kích hoạt Modal popup **"Thêm tài liệu mới"** (`#lmsAddLibraryModal`) trực tiếp trên trang.

### 12.2. Lưới tài liệu (Card Grid 6 cột)
Hiển thị danh sách tài liệu dạng lưới các thẻ đứng (`row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3`, viền `#475569` chuẩn mockup `Quản lý thư viện.png`):
- **Khung biểu tượng (phần trên thẻ — render qua component `library_icon`):**
  - **Liên kết URL (`link` / `[Sách]`):** Icon chuỗi 2 mắt xích đen 🔗 nét đậm đan chéo góc 45 độ.
  - **Tài liệu PDF (`pdf` / `[Review]`):** Icon trang giấy gấp góc nét đen, có 3 vạch ngang phía trên và băng chữ nhật ngang chứa chữ `PDF` in hoa ở giữa trang.
  - **Tài liệu Word (`doc` / `[Test]`):** Icon trang giấy màu xanh dương `#0b66e4`, góc gấp xanh nhạt `#7fb5ff`, chữ `DOC` in hoa màu trắng ở giữa.
  - **Bảng tính Excel (`xls` / `[Test]`):** Icon trang giấy màu xanh lá `#137333`, nhãn chữ nhật `XLS` nền trắng viền xanh lá lệch góc trên trái và 2 cột × 3 hàng vạch ngang trắng bên dưới.
  - **Tài liệu nội bộ (`internal` / `[Nội bộ]`):** Khung thẻ rỗng màu trắng tinh, không có biểu tượng.
  - **Tài liệu khác (`slide`, `other`):** Icon tương ứng với Slide PPT hoặc tài liệu chung.
- **Tên tài liệu (phần dưới thẻ):** Căn giữa, màu chữ đen rõ nét, tự động cắt ngắn dấu `...` (`text-overflow: ellipsis; white-space: nowrap;`).
- **Kiểm soát quyền tải:** Nếu tài liệu bị tắt quyền tải xuống cho giáo viên (`is_downloadable = 0`), hiển thị `badge` (`variant => 'warning'`, icon ổ khoá `Chỉ xem`) ở góc trên phải thẻ.
- **Thao tác nhanh (Hover Overlay — dùng component `button`):**
  - Nút **Mở / Tải:** Đi qua route `/lms/admin/lms-thu-vien/tai?id={id}` (luôn ghi nhận `incrementDownload`), mở liên kết ngoài trong tab mới hoặc tải file cục bộ về máy.
  - Nút **Sửa:** Gọi AJAX (`/lms/admin/lms-thu-vien/chi-tiet?id={id}`) và mở Modal **"Sửa thông tin tài liệu"**.
  - Nút **Xoá:** Xác nhận và thực hiện soft delete file khỏi thư viện (`/lms/admin/lms-thu-vien/xoa`).

### 12.3. Modal "Thêm tài liệu mới" (Mockup: `Quản lý thư viện - Thêm.png`)
| Trường dữ liệu | Thành phần UI / Component | Mô tả nghiệp vụ |
|---|---|---|
| **Liên kết chứa tài liệu** | `form/input` (`name="file_link"`) | Nhập URL ngoài (Google Drive, web link...). Placeholder: `"Dán link tại đây ..."`. |
| **Tải file lên** | Khung Dropzone (`.lms-upload-dropzone`) | Vùng kéo thả file nền xanh xám nhạt viền nét đứt, icon mũi tên tải lên, text `"Choose a file or drag it here."`. Hỗ trợ: PDF, Word, Excel, PPT, Ảnh, Video, Audio, ZIP (tối đa 100MB). |
| **Phân loại danh mục** | `form/select` (`enum="library_categories"`) | Chọn tiền tố danh mục: Không tiền tố, `[Sách]`, `[Review]`, `[Test]`, `[Nội bộ]`. Tự động gợi ý theo loại file/link. |
| **Tên tài liệu** | `form/input` (`name="title"`) | Tự động điền theo tên file tải lên hoặc link URL nếu để trống. |
| **Phân loại tải được hay không** | `form/select` (`enum="library_downloadable"`) | Đặt ở góc dưới trái cạnh nút Thêm (đúng ghi chú `"phân loại tải được hay không"` trong mockup): `Cho phép tải xuống` (`1`) / `Không cho phép tải xuống (Chỉ xem)` (`0`). |
| **Thêm** | `button` (`type="submit"`, `variant="primary"`) | Nút màu đỏ cam đặt góc dưới phải của Modal. |

### 12.4. Modal "Sửa thông tin tài liệu"
- Mở qua AJAX khi Admin bấm nút Sửa trên từng card tài liệu.
- Sử dụng 100% component (`form/select`, `form/input`, `button`) cho phép đổi phân loại danh mục (`library_categories`), tên tài liệu, đường dẫn/liên kết (`file_url`), tải file mới thay thế (tuỳ chọn), và thay đổi quyền tải xuống (`library_downloadable`).
- Tự động tính toán lại `file_type` qua `LibraryService::detectFileType()` khi Admin đổi phân loại hoặc thay file/link.

### 12.5. Phân trang & Liên thông Portal Giáo viên (`/lms/teacher/lms-thu-vien`)
- **Phân trang:** Tích hợp component `pagination` đồng bộ URL và tham số tìm kiếm `q`.
- **Phía Giáo viên (`lms/app/teacher/sources/lms-thu-vien.php`, `lms/app/teacher/templates/lms-thu-vien/hienthi_tpl.php`):**
  - Đồng bộ giao diện theo mockup `Tai-lieu-phat-trien/teacher/Thư viện.png`: Thanh tìm kiếm (`form/input` + nút `Tìm kiếm` màu cam `variant => 'primary'`) và lưới thẻ 5 cột dùng chung component `library_icon`.
  - Nếu `is_downloadable = 1`: Giáo viên bấm vào thẻ hoặc nút tải để mở link / tải file qua `/lms/teacher/lms-thu-vien/tai?id={id}`.
  - Nếu `is_downloadable = 0`: Thẻ hiển thị huy hiệu khoá `Chỉ xem`, vô hiệu hoá thao tác tải trên UI và chặn tại backend qua `LibraryService::canTeacherDownloadFile()`.

## 13. Thống kê phí dịch vụ

> Nguồn: `lms/app/admin/sources/lms-phi-dv.php`, template `lms/app/admin/templates/lms-phi-dv/`,
> service `lms/services/Lms/PayrollService.php` (nhóm `feeSummary`, `transactionsByRange`,
> `allFeeSummaries`, `sessionsByRange`). Mockup: `admin/Thống kê phí dịch vụ*.png`.

### Form tra cứu
| Trường | Mô tả |
|---|---|
| Mã giáo viên | Text, bắt buộc; tra đúng `db_lms_teachers.teacher_code` |
| Khoảng thời gian | 2 date picker (Từ - Đến); mặc định ngày đầu/cuối tháng hiện tại; tự đảo nếu Từ > Đến |

Các ô tổng hợp **chỉ đọc**, tính từ `db_lms_transactions` trong khoảng ngày:
| Trường | Cách tính |
|---|---|
| Ký quỹ | `SUM(amount)` với `type='deposit_deduct'` (mang dấu) |
| Vi phạm | `SUM(ABS(amount))` với `type='penalty'` |
| Phí dịch vụ cộng thêm | `SUM(amount)` với `type IN ('bonus','adjustment')` (mang dấu) |
| Thuế (có điều kiện xét) | 0% nếu `is_commit_08=1` hoặc thu nhập chịu thuế < 5.000.000đ; ngược lại 10% |

> *\*\*nếu có ký cam kết 08 thì thuế là 0%, nếu không ký và tổng phí từ 5tr thì thuế 10%*

Nút **Xuất tất cả phí dịch vụ** (đỏ, CSV toàn bộ GV trong khoảng) + nút **Tìm** (xanh).

### Kết quả (khi có giao dịch)
- **Card thông tin GV**: Ảnh đại diện (`db_lms_teacher_profiles.avatar`) + Tên + Mã GV + SĐT + Email (`db_lms_users`).
- Nút **Xuất file phí dịch vụ** (CSV giao dịch của GV) + **Xuất file số buổi dạy** (CSV buổi dạy, loại `cancelled`). *(Khớp 100% mockup `admin-ver2/Thống kê phí DV-1.png`, không có nút "Duyệt lương tháng...")*.
- **Tổng Tiền** = Tổng thu + Ký quỹ − Vi phạm − Thuế (khớp tổng các dòng bảng).
- Bảng: Thời Gian (`transaction_date` + giờ buổi học) | Số Tiền (dấu +/−) | Nội Dung
  (`reason`; riêng lương buổi hiển thị "Bạn đã nhận được ... khi đã hoàn thành bài học cho <mã lớp>").
- Không tìm thấy GV / không có giao dịch: hiển thị "Chưa có dữ liệu".

> [!NOTE] Lưu ý kiến trúc & phân định nghiệp vụ:
> - Trang **Thống kê phí dịch vụ (`/lms/admin/lms-phi-dv`)** là màn hình **Tra cứu & Xuất báo cáo số liệu thuần túy (Read-only)**. Hệ thống không sử dụng quy trình duyệt lương thủ công 3 bước hay trang chi tiết riêng tại đây.
> - Toàn bộ nghiệp vụ phê duyệt/chi trả thù lao (thanh toán reset ví về 0, hoàn tác cộng bù buổi học, điều chỉnh thưởng/phạt) được tập trung xử lý duy nhất tại **Modal "Thanh toán phí dịch vụ"** (mục 14 bên dưới, đối chiếu mockup `admin-ver2/Thanh toán phí dịch vụ.png`).

### Xuất CSV
| Action | Nội dung |
|---|---|
| `xuat-tat-ca` | Mã GV, Tên, SĐT, Email, Tổng thu, Ký quỹ, Vi phạm, Phí DV cộng thêm, Thuế, Thực nhận |
| `xuat-phi-dv` | Thời gian, Loại, Số tiền, Nội dung, Trạng thái (1 GV) |
| `xuat-buoi-day` | Ngày, Giờ bắt đầu, Giờ kết thúc, Mã lớp, Tên lớp, Trạng thái, Ghi chú (1 GV) |

Tất cả CSV có UTF-8 BOM và chống CSV injection.

## 14. Modal "Thanh toán phí dịch vụ" (từ Tài khoản GV)

### Tìm kiếm + danh sách
- Ô tìm: "tìm nội dung trong bảng"
- Bảng: **Họ và tên** (kèm hạng) | **Mã** | **Phí dịch vụ** | **Chọn** (checkbox)
- VD: Ngô Thái Phụng (Hạng: Vàng) | TE125 | 1,468,800 đ | ☐
- Link **Chọn tất cả**
- Phân trang

### Section "Thanh toán"
- **Thanh toán các buổi:** 2 date picker + nút **Thanh toán**
- **Cộng lại các buổi:** 2 date picker + nút **Thêm tiền** (đỏ)

### Section "Điều chỉnh số tiền cụ thể"
- Input: "+100000, -100000, ..." + nút **Điều chỉnh tiền**
- Textarea: "Ghi nội dung điều chỉnh tiền tại đây..."

## 15. Thống kê số buổi học

- **Route:**
  - Tra cứu & hiển thị: `GET /lms/admin/lms-thong-ke-buoi-hoc`
  - Xuất CSV: `GET /lms/admin/lms-thong-ke-buoi-hoc/xuat?student_code=...&date_from=...&date_to=...`
- **Source & Template:** `lms/app/admin/sources/lms-thong-ke-buoi-hoc.php`, `lms/app/admin/templates/lms-thong-ke-buoi-hoc/hienthi_tpl.php` (chuẩn hoá 100% bằng `lms_component`: `form/input`, `form/date_picker`, `form/field_row`, `button`, `pagination`).
- **Service:** `Lms\AdminReportService::lessonStats()`

### Form filter
| Trường | Component | Mô tả |
|---|---|---|
| Mã học viên | `form/input` (`name="student_code"`) | Tra cứu theo `db_lms_students.student_code` hoặc `db_lms_users.username` (VD: `Master227`, `ST001`, `hv01`) |
| Khoảng thời gian | `form/field_row` + 2 `form/date_picker` (`date_from`, `date_to`) | Tự động đảo chiều nếu `date_from > date_to` |
| Tìm | `button` (`type="submit"`, `variant="secondary"`) | Căn phải dưới ô Khoảng thời gian |

### Kết quả (khi tìm thấy học viên)
- **Card thông tin HV:** Ảnh đại diện (`db_lms_students.avatar`) + **Tên** (`full_name`) + **Mã Học Viên** (`student_code`) + **Số Điện Thoại** (`coalesce(nullif(u.phone, ''), s.parent_phone)`) + **Email** (`u.email`).
- **Thanh công cụ trên bảng:**
  - Bên trái: Nút **Xuất file số buổi học** (`button`, `variant="secondary"`) tải xuống file CSV (có UTF-8 BOM & chống CSV injection).
  - Bên phải: Label lớn **Tổng số buổi học: {total} buổi**.
- **Bảng 5 cột (`table table-bordered table-hover align-middle mb-0 lms-table text-center`):**
  1. `Thời Gian`: `YYYY-MM-DD HH:MM` (từ `session_date` + `start_time`)
  2. `Thứ`: `Thứ 2` .. `Thứ 7`, `Chủ nhật`
  3. `Số buổi học`: `1` (mỗi buổi học không bị huỷ `status <> 'cancelled'` tính là `1` buổi)
  4. `Mã giáo viên`: `db_lms_teachers.teacher_code` (VD: `TE125`)
  5. `Ghi chú`: `db_lms_sessions.lesson_summary`
- **Phân trang:** Sử dụng component `pagination` (`$perPage = 20`).

## 16. Trang "Lịch học" (Calendar tuần của Lớp học)

- **Route:**
  - Xem lịch học: `GET /lms/admin/lms-lop/lich-hoc?id={class_id}&week=YYYY-MM-DD`
  - Cập nhật lịch cố định: `POST /lms/admin/lms-lop/cap-nhat-lich-co-dinh`
  - Thêm buổi học: `POST /lms/admin/lms-lop/them-buoi-hoc`
  - Xoá buổi học: `POST /lms/admin/lms-lop/xoa-buoi-hoc`
  - Đánh dấu buổi nghỉ: `POST /lms/admin/lms-lop/danh-dau-nghi`
- **Template:** `lms/app/admin/templates/lms-lop/lich-hoc_tpl.php`
- **Dẫn chứng Mockup:** `Tai-lieu-phat-trien/admin-ver2/Lịch học.png`

### 16.1. Khối 1 — Thông tin lớp & Cấu hình lịch cố định
- **Cột trái (Read-only):** Hiển thị `Loại lớp` (`1 kèm 1` / `Đông người`), `Giáo viên` (`Mã GV - Họ tên`), `Học viên` (`Mã HV - Họ tên`).
- **Cột phải (`POST /lms/admin/lms-lop/cap-nhat-lich-co-dinh`):**
  - Component dùng chung `form/class_schedule_slots` (`#lms-fixed-schedules-container`): mỗi dòng gồm Thứ (`sch_day[]`) + Giờ bắt đầu (`sch_start[]`) - Giờ kết thúc (`sch_end[]`) + nút `Xoá` / `Thêm`.
  - `Ngày áp dụng lịch` (`effective_from`, date picker) + nút **Lưu**.

### 16.2. Khối 2 — Thanh điều hướng tuần/tháng
- Nút lùi/tiến tuần (`week=YYYY-MM-DD`), tiêu đề `Tháng M / YYYY`, nút **Hôm nay** (nếu đang xem tuần khác).
- Không còn nút **Thêm buổi học**, **Xem đủ 24h** và nhóm chuyển **Lưới lịch / Danh sách**.

### 16.3. Khối 3 — Lưới lịch tuần (`calendar_week` + `calendar_week_event`) & Chú thích màu
- Khung lưới do component chung `calendar_week` render; **thẻ trong ô do component chung `calendar_week_event`** render — dùng cho cả 3 portal (Admin "Lịch học", Teacher "Quản lý lịch", Student "Lịch học"). Header/action/modal vẫn riêng theo portal.
- Chiều cao một hàng giờ = biến CSS `--lms-cal-hour-h` (mặc định `72px`, đồng bộ với hằng số `LMS_CAL_HOUR_H` trong `lms/lib/lms_calendar.php`). Hàng giờ **luôn đúng 72px** vì thẻ định vị `absolute` trong ô, nên lưới là thang thời gian thật (không bị đẩy giãn).
- Header mỗi cột: dòng trên là thứ (`Thứ 2`), dòng dưới là **ngày/tháng** (`2/11`) — không phát lại số thứ (theo `admin-ver2/Lịch học.png`).
- Bảng 7 cột (`Thứ 2` → `Chủ nhật`) × 24 hàng giờ (`01:00` → `24:00`); mỗi ô có vạch đứt nét nửa giờ (`.lms-cal-cell::before`) làm mốc nhìn cho thẻ giờ lẻ.
- **Vị trí & kích thước thẻ theo thời gian** (`lms_cal_offset_px` + `lms_cal_span_px`):
  - `--lms-cal-top` = phút lẻ của giờ bắt đầu: `17:00` → 0px, `17:15` → 18px, `17:30` → 36px, `17:45` → 54px.
  - `--lms-cal-height` = thời lượng × 72px: 17:30–19:00 (1,5 giờ) → 108px, tức thẻ tràn xuống tận cuối ô 18:00 — giống mockup (card `18:30-20:00` cao 1,5 hàng).
  - `min-height: 34px` cho buổi rất ngắn; dữ liệu lỗi (`end <= start`) fallback 1 giờ.
  - `--lms-cal-lane` / `--lms-cal-lanes` (từ `lms_cal_lanes_for_rows`) chia chiều ngang khi hai **buổi học** chồng giờ nhau trong cùng cột ngày; thẻ không chồng luôn chiếm trọn bề ngang ô. Riêng `--free` (lịch trống, chỉ teacher) không chia lane: dải nền full chiều ngang với `z-index: 1` để thẻ buổi học đè lên.
- Variant màu sinh từ helper `lms_cal_variant()` và gắn trên thẻ:
  - **Đỏ (`.lms-cal-event--upcoming`):** Buổi học sắp tới (`upcoming`).
  - **Hồng đậm kèm dấu `✗` góc phải (`.lms-cal-event--missing`):** Buổi đã hoàn thành nhưng thiếu báo cáo / BTVN (`lms_cal_missing_hw($session, true)` = thiếu `summary_submitted_at` **hoặc** thiếu `hw_content`).
  - **Hồng nhạt (`.lms-cal-event--finished`):** Buổi đã hoàn thành đầy đủ (`finished`).
  - **Xám (`.lms-cal-event--cancelled`):** Buổi đã nghỉ / huỷ (`cancelled`, `absent_teacher`, `absent_student`).
- Trang **Tổng quan** (`lms-tong-quan`) dùng lưới `lms-week-timeline` riêng nhưng đã chuyển sang cùng helper `lms_cal_offset_px`/`lms_cal_span_px`: buổi 90 phút giờ cao 60px (trước đây `ceil()` thành 82px).

### 16.4. Tương tác Popup Chi tiết buổi học & Thêm buổi học
- **Click vào thẻ buổi học (`.lms-cal-event`):** Mở Modal chi tiết buổi học hiển thị: Thời lượng (`duration` giờ), Học phí buổi (`price`), Phần trăm GV nhận (`percent`%), Số tiền GV nhận (`teacher-amount`), Chuyên đề, Nội dung báo cáo, BTVN, cùng các nút **Báo cáo buổi học** (`/lms/admin/lms-lop/buoi?id={id}`), **Đánh dấu buổi nghỉ** (`POST /lms/admin/lms-lop/danh-dau-nghi`) và **Xoá buổi** (`POST /lms/admin/lms-lop/xoa-buoi-hoc`). Badge popup lấy từ `data-variant` + `data-status-label`.
- **Click vào ô trống trên lưới lịch:** Mở Modal `#modal-add-session` (`POST /lms/admin/lms-lop/them-buoi-hoc`) tự động điền ngày và khung giờ vừa chọn, cho phép nhập số giờ học, học phí buổi, % giáo viên nhận và chuyên đề.

## 17. Quản lý yêu cầu (chi tiết các tab)

### Tab "Request thêm buổi"
- Bộ lọc: Mã / Tên học viên, SĐT + nút Tìm
- Cột: Mã lớp, Mã GV, Chuyên đề, Số buổi đã học (VD: 3/4), Thời gian request (VD: "Thứ 4 05-08-2026 15:27")
- Không có cột hành động — bấm **mã lớp** để mở chi tiết yêu cầu (`/lms/admin/lms-yeu-cau/xem?id=...`).
- **Trang Chi tiết yêu cầu:** Thiết kế đồng bộ theo kiến trúc Form Component MasterStudy ver2 (`form/input`, `form/textarea` dạng readonly và nút bấm chuẩn):
  - **Thông tin chung:** Tiêu đề yêu cầu, Lớp học, Giáo viên, Học viên, Thời gian gửi.
  - **Chi tiết yêu cầu:** Ngày học xin thêm, Khung giờ học, Ghi chú của giáo viên (hoặc thông tin buổi xin nghỉ / lịch mới đổi tuỳ loại yêu cầu).
  - **Thao tác xử lý (khi chờ duyệt):** Nút **Duyệt yêu cầu** (`primary` - tạo buổi học mới), ô nhập **Lý do từ chối** kèm nút **Từ chối yêu cầu** (`outline-danger`), nút **Quay lại** quay về tab danh sách tương ứng.

### Tab "Request test"
- Bộ lọc: Mã lớp, GV + nút Tìm
- Cột: Mã lớp, Mã GV, **Loại test** (Test / Minitest), Chuyên đề, **Số buổi đã học**, **Thời gian request**
- Hành động: **Xác nhận** (xanh, gửi bài test draft) / **Xoá** (đỏ)

### Tab "Request đổi lịch"
- Bộ lọc: Mã lớp, GV + nút Tìm
- Cột: Mã lớp, Mã GV, **Loại** (Nghỉ 1 buổi / Lịch cố định), **Buổi cũ** (VD: "T5, 06/08/2026" hoặc "T3, 17h30 - 19h"), **Buổi mới / Buổi bù** (VD: "-" hoặc "T2, 17h30 - 19h"), **Thời gian request**
- Hành động: **Xác nhận** (xanh) / **Xoá** (đỏ)

### Tab "Duyệt KQ test"
- Bộ lọc: Mã lớp, GV + nút Tìm
- Cột: Mã lớp, Mã GV, **Loại test** (Test / Minitest), Chuyên đề, **Thời gian nộp bài chấm** (VD: "Thứ 4 05-08-2026 15:27")
- Hành động: **Chi tiết** / **Xác nhận** (xanh) / **Xoá** (đỏ)

### Tab "Kiểm tra số buổi"
> *\*\* ở đây chỉ hiện các lớp còn ít hơn 5 buổi*

- Bộ lọc: Mã lớp, GV + nút Tìm
- Cột: Mã lớp, Mã GV, **Số buổi đã học** (VD: 23/24, 22/24, 21/24, 20/24), **Số buổi còn lại** (VD: 1, 2, 3, 4)
- Hành động: **Thêm khoá học** / **Xoá**

### Trang "Chi tiết kết quả test" (mở từ "Chi tiết" ở Duyệt KQ test)

**Header:** `[Tên chuyên đề] | [Mã HV - Tên HV] | [Mã GV - Tên GV]` | `Đã dạy: 4/4 buổi` (màu đỏ)

**VD:** How to calculate | Master227 - Hà Phương | TE125 - Ngô Thái Phụng | Đã dạy: 4/4 buổi

#### Section 1: Bài test chuyên đề
- File: `test-how-to-calculate.pdf` (có icon download)
- Nút **Xác nhận** (xanh, góc phải)
- **Bài nộp của học sinh:** 4 ô upload (icon download)
- **Chấm điểm:**
  - File template: `test-how-to-calculate-grading-template.pdf` (icon download)
  - Điểm: input số (VD: 15/20) + input phần trăm (VD: 75%)
  - File đã chấm: `test-how-to-calculate-graded.xlsx` (icon download/upload)
- Nút **Cập nhật** (cam)

#### Section 2: Minitest chuyên đề
- File: `mini-test-how-to-calculate.pdf`
- (Cấu trúc giống Section 1 với file `minitest-...`)

### Modal "Điều chỉnh số buổi" (`#lmsAdjustSessionsModal` — mở từ nút "Điều chỉnh số buổi học" ở Quản lý lớp học)
- **Template:** Nằm trong `lms/app/admin/templates/lms-lop/hienthi_tpl.php` (Mockup: `Quản lý lớp học - Điều chỉnh số buổi học.png`).
- **Tải dữ liệu AJAX:** Gọi `GET /lms/admin/lms-lop/ajax-danh-sach-lop?q=...&page=...` để nạp danh sách lớp có phân trang ngay trong modal.
- **Thanh tìm kiếm:** Ô `#lms-adjust-search` (`"Tìm nội dung trong bảng"`, debounce tự động lọc theo mã lớp, học viên, giáo viên).
- **Bảng chọn lớp (`#lms-adjust-tbody`):**
  - Cột: **Mã lớp** | **Mã học viên** (`Mã HV [Tên học sinh]`) | **Mã giáo viên** (`Mã GV [Tên giáo viên]`) | **Chọn** (checkbox `.lms-adjust-chk`).
  - Chân bảng: Phân trang AJAX (`#lms-adjust-pagination`) + bộ đếm `Đã chọn: N lớp` + nút **Chọn tất cả** (`#lms-adjust-btn-toggle-all`).
- **Form 1 — Xoá buổi học hàng loạt (`POST /lms/admin/lms-lop/dieu-chinh-xoa-buoi`):**
  - Chọn khoảng ngày `from_date` → `to_date` + nút **Xoá** (màu đen `#0f172a`): xoá toàn bộ các buổi học `upcoming` trong khoảng ngày của các lớp đã chọn.
- **Form 2 — Thêm buổi học hàng loạt (`POST /lms/admin/lms-lop/dieu-chinh-them-buoi`):**
  - Chọn khoảng ngày `from_date` → `to_date` + nút **Thêm** (màu cam `#ea580c`): tự động sinh các buổi học `upcoming` theo lịch cố định (`db_lms_schedules`) trong khoảng ngày cho các lớp đã chọn.

## 18. Quản lý lộ trình (Form thêm/sửa)

- **Route:**
  - Thêm mới: `GET|POST /lms/admin/lms-lo-trinh/them`
  - Chỉnh sửa: `GET|POST /lms/admin/lms-lo-trinh/sua?id={id}`
- **Template:** `lms/app/admin/templates/lms-lo-trinh/form_tpl.php` (bố cục form 1 cột căn giữa `col-12 col-md-10 col-lg-8 col-xl-7` đồng bộ với `them-giao-vien` / `them-hoc-vien`, chuẩn hoá 100% bằng `lms_component(...)`).
- **Service:** `Lms\AdminReportService::getTopic()`, `saveTopic()`, `listSubjects()`

### Form "Thêm chuyên đề mới" / "Chỉnh sửa chuyên đề"

| Trường | Bắt buộc | Component | Mô tả |
|---|---|---|---|
| Tên chuyên đề | * | `form/input` (`name="name"`) | Text (VD: Unit 1 - Flower, How to calculate, English Assessment Test 1) |
| Danh mục | * | `form/select` (`name="subject_id"`) | Dropdown nạp từ `db_lms_subjects` — **Có option `"+ Thêm loại chuyên đề mới"` (`__new__`)** |
| Loại chuyên đề mới | *(khi chọn `+ Thêm loại chuyên đề mới`)* | `form/input` (`name="new_subject_name"`) | Tự động hiện khi chọn `+ Thêm loại chuyên đề mới` để tạo mới danh mục trong `db_lms_subjects` |
| Loại chuyên đề | * | `form/select` (`name="has_test"`) | Dropdown: **Không test** (`0`) / **Có test** (`1` — tự động bật `has_minitest = 1`) |
| Nội dung | | `form/textarea` (`name="content"`) | Textarea lưu vào `db_lms_topics.content` (VD: "Learn about flowers", "Đề thi thử") |
| Số buổi học tối đa | * | `form/input` (`type="number"`, `name="max_sessions"`) | Số nguyên từ 1 đến 500 (VD: 2, 3, 1) |
| Template báo cáo | | `form/field_row` + `form/textarea` (`name="report_template"`) | Rich text editor có thanh công cụ định dạng (`button` B, I, U, danh sách số/gạch đầu dòng, xoá định dạng). **Tự động hiện khi chọn `Không test` (`chuyên đề mới (3).png`) và ẩn đi khi chọn `Có test` (`chuyên đề mới (2).png`)** |

> *\*\* Quy tắc Loại chuyên đề:*
> - **Có test (`has_test = 1`):** áp dụng cả test và minitest (`has_minitest = 1`), ẩn trường `Template báo cáo`.
> - **Không test (`has_test = 0`):** nhập báo cáo chung qua trường `Template báo cáo`.

Nút hành động: Sử dụng `form/actions_bar` (**Thêm** khi tạo mới / **Lưu** khi chỉnh sửa, kèm nút **Huỷ** quay lại danh sách).

### Dropdown "Danh mục" - mở rộng
Khi bấm vào dropdown (`chuyên đề mới (4).png`), hiển thị các danh mục hiện có trong `db_lms_subjects` kèm tuỳ chọn cuối cùng `+ Thêm loại chuyên đề mới`.

## 19. Tham chiếu nhanh Form "Tài khoản học viên mới / Chỉnh sửa" (`lms-hoc-vien/form_tpl.php`)

*(Chi tiết đầy đủ đã được hợp nhất tại **Mục 6.3** — sử dụng chung template `lms/app/admin/templates/lms-hoc-vien/form_tpl.php` cho cả route `/lms/admin/lms-tai-khoan/them-hoc-vien`, `/lms/admin/lms-tai-khoan/sua-hoc-vien` và `/lms/admin/lms-hoc-vien/them`, `/lms/admin/lms-hoc-vien/sua`).*

## 20. Tham chiếu nhanh Form "Tài khoản giáo viên mới / Chỉnh sửa" (`lms-giao-vien/form_tpl.php`)

*(Chi tiết đầy đủ đã được hợp nhất tại **Mục 6.5** — sử dụng chung template `lms/app/admin/templates/lms-giao-vien/form_tpl.php` cho cả route `/lms/admin/lms-tai-khoan/them-giao-vien`, `/lms/admin/lms-tai-khoan/sua-giao-vien` và `/lms/admin/lms-giao-vien/them`, `/lms/admin/lms-giao-vien/sua`).*

## 21. Nhật ký hệ thống (`lms-audit-log`)

Màn hình **read-only** cho Admin tra cứu lịch sử thao tác nhạy cảm, dữ liệu đọc từ `db_lms_audit_logs` (ghi tự động bởi `LmsService::audit()` trong các service). Không có action ghi/xoá trên màn hình.

- **URL:** `/lms/admin/lms-audit-log` — quyền `view` qua `lms_form_guard()` (super admin bypass theo RBAC).
- **Bộ lọc (GET, giữ nguyên khi phân trang):**
  - `actor`: dropdown người thực hiện, dựng từ các `actor_user_id` có trong log (kèm username); rỗng = tất cả.
  - `action`: dropdown thao tác (distinct `action`), khớp chính xác — VD `create_teacher`, `update_class`.
  - `target`: dropdown đối tượng (distinct `target_type`) — VD `teachers`, `classes`, `transactions`.
  - `date_from` / `date_to`: khoảng ngày (bao gồm cả ngày `date_to`), định dạng `YYYY-MM-DD`.
- **Bảng 6 cột:** Thời điểm (d/m/Y H:i:s) · Người thực hiện (username + badge loại tài khoản `#id`) · Thao tác (`action`) · Đối tượng (`target_type #id`, ẩn `#id` khi bằng 0) · IP (tooltip chứa User-Agent) · Chi tiết.
- **Panel JSON:** nút "Xem JSON" mở vùng collapse hiển thị IP + User-Agent và JSON `before_data`/`after_data` (chỉ hiện vế có dữ liệu; nhãn "Trước" đỏ / "Sau" xanh).
- **Phân trang:** 50 bản ghi/trang, component `lms_pagination()`; sắp xếp mới nhất trước (`created_at desc, id desc`).
- **Loại người thực hiện:** `admin` (Quản trị), `teacher` (Giáo viên), `student` (Học viên), `user` (Người dùng), `system_cron` (Hệ thống/Cron). Bản ghi cron không có `actor_user_id` → hiển thị "Hệ thống/Cron".
- **Component dùng chung:** `form/select`, `form/date_picker`, `button`, `badge`, `pagination`; bảng dùng `lms-table` + Bootstrap `table-bordered table-hover align-middle`.





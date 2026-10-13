# TÀI LIỆU ĐẶC TẢ YÊU CẦU PHẦN MỀM (SRS)
**Dự án:** LMS MasterStudy - Trung tâm Ngoại ngữ AROMA
**Phiên bản:** 2.1 (cập nhật: thêm Glossary, Use Case chi tiết, RBAC matrix, bổ sung phân hệ Phụ huynh, mở rộng Business Rules)

> [!IMPORTANT]
> **Cập nhật 2.1 — Xây dựng trên bộ source PHP/MySQL hiện tại.**
> Toàn bộ **nội dung nghiệp vụ** (FR, Business Rules, RBAC matrix, Glossary, Traceability)
> của file này **vẫn nguyên giá trị** và là kim chỉ nam cho dev/QA.
>
> Chỉ có **yêu cầu kỹ thuật gắn với stack cũ** (React/NestJS/PostgreSQL/Redis/S3/
> Elasticsearch/AWS) là bị thay thế. Các mục bị ảnh hưởng đã được ghi chú
> **"[ĐÃ THAY THẾ — xem D1..D7]"** và điều chỉnh trực tiếp:
> - **SYS-02** (tìm kiếm toàn cục): MySQL FULLTEXT thay Elasticsearch.
> - **SYS-03** (đăng nhập): PHP session + bcrypt + CSRF, không dùng JWT access/refresh token.
> - **Kiến trúc portal**: 3 portal LMS đối xứng (admin/teacher/student) trong `lms/`, dùng chung
>   `db_lms_users` (role ENUM); `admin/` e-commerce và `db_user` **nguyên vẹn** (D3).
> - **§5 NFR**: bỏ Redis/S3/Elasticsearch/partition/10k concurrent/i18n (xem chi tiết mục §5).
>
> Bổ sung kỹ thuật (không thay đổi nghiệp vụ):
> - **D10 — URL portal**: clean URL `/lms/<portal>/<alias>/<action>` (không `.php`),
>   qua `.htaccess` rewrite; chi tiết `Huong-Dan-Phat-Trien-Page.md` §3.3.1.
>
> Quyết định đầy đủ: xem `Quy_Trinh_Phat_Trien.md` (v3.0) và file kế hoạch
> `1791257567462-lms-masterstudy-php-extend.md`.

## 1. GIỚI THIỆU CHUNG

### 1.1. Mục đích
Tài liệu SRS (Software Requirements Specification) này đặc tả chi tiết các yêu cầu chức năng và phi chức năng của hệ thống LMS MasterStudy, làm cơ sở cho đội ngũ lập trình (Dev) và kiểm thử (QA) phát triển phần mềm.

### 1.2. Phạm vi hệ thống
Hệ thống là một nền tảng quản lý trung tâm ngoại ngữ toàn diện, kết nối **4 đối tượng** chính:
- **Admin** (Quản trị vận hành) - Quản lý toàn bộ nghiệp vụ
- **Teacher** (Giáo viên) - Quản lý lớp, lịch dạy, BTVN, nhận phí dịch vụ
- **Student** (Học viên) - Học tập, nộp bài, xem lịch
- **Parent** (Phụ huynh) - Theo dõi tiến độ, thanh toán học phí (mở rộng trong tương lai)

Hỗ trợ: lớp 1-1 và lớp nhóm, lịch học linh hoạt, BTVN/Test, hệ thống tính lương/phạt vi phạm tự động.

### 1.3. Glossary (Thuật ngữ)

| Thuật ngữ | Định nghĩa |
|---|---|
| **Tick** | Đơn vị đếm vi phạm của giáo viên. Mỗi lần vi phạm = +1 tick. |
| **Cam kết 08** | Cam kết của GV với cơ quan thuế: nếu cam kết thì thuế TNCN = 0%; nếu không và tổng thu nhập ≥ 5.000.000đ thì thuế 10%. |
| **Lesson Summary** | Bản nhận xét + nội dung BTVN GV phải ghi sau buổi học, deadline = 12:00 trưa ngày kế tiếp. |
| **Minitest** | Bài kiểm tra nhỏ giữa kỳ (khác với Test cuối chuyên đề). |
| **Lộ trình (Curriculum)** | Tập hợp các chuyên đề (topics) theo thứ tự cho một môn học. |
| **Chuyên đề (Topic)** | Đơn vị kiến thức nhỏ trong môn học, có số buổi tối đa (`max_sessions`). |
| **Phí dịch vụ** | Phần % học phí GV được nhận từ mỗi buổi dạy. |
| **BTVN** | Bài tập về nhà. |
| **Mức GV (Rank)** | Phân hạng GV: bronze (mới) / gold (trung bình) / green (ưu tiên cao). |
| **Hạ phí dịch vụ** | Khi tick thực tế vượt ngưỡng, % phí DV bị giảm theo rule. |
| **Request** | Yêu cầu do GV/HS tạo, Admin phê duyệt (xin nghỉ, thêm buổi, đổi lịch, gửi test). |

### 1.4. Tài liệu liên quan
- `ERD_MasterStudy.md` - Thiết kế cơ sở dữ liệu
- `Quy_Trinh_Phat_Trien.md` - Quy trình phát triển
- `Tài liệu admin.md` / `Tài liệu teacher.md` / `Tài liệu student.md` - Mô tả giao diện chi tiết
- `1791257567462-lms-masterstudy-php-extend.md` - Kế hoạch triển khai trên source PHP/MySQL hiện tại (D1..D7)

---

## 2. YÊU CẦU CHỨC NĂNG (FUNCTIONAL REQUIREMENTS)

> Mỗi FR được mô tả theo format: **ID | Tên | Actor | Mô tả | Pre-condition | Main Flow | Alternative Flow | Business Rule liên quan**

### 2.1. Phân hệ Admin (Quản trị viên)

#### ADM-01: Quản lý Tài khoản Học viên
- **Actor:** Admin
- **Mô tả:** CRUD hồ sơ học viên.
- **Input form:** Username, Password (auto-gen nếu không nhập), SĐT, Họ tên PH, SĐT PH, Lớp, Trường, Hình thức học (online/offline), Quận/Phường/Địa chỉ, Môn học, Thời gian rảnh (chọn khung giờ theo thứ).
- **Validation:** Username unique, SĐT format VN (10-11 số), Trạng thái mặc định = `pending_arrange`.
- **Pre-condition:** Admin đã đăng nhập.
- **Main flow:** (1) Vào menu Quản lý Tài khoản → tab Học viên → nhấn `+ Thêm` → (2) Nhập form → (3) Nhấn `Lưu` → (4) Hệ thống tạo user + student, gửi SMS thông báo tài khoản cho PH.
- **Alternative flow:** Khoá/Mở khoá tài khoản (đổi `users.status`); Sửa thông tin (audit log trước/sau).
- **BR liên quan:** `BR-AUTH-01`

#### ADM-02: Quản lý Tài khoản Giáo viên
- **Actor:** Admin
- **Mô tả:** CRUD hồ sơ giáo viên.
- **Input form:** Tên, CCCD, Ngày cấp/Nơi cấp, MST, Địa chỉ, Số lớp tối đa (`max_classes`), Hạng (bronze/gold/green), Mức % phí DV, Cam kết 08 (checkbox), Ngân hàng/Số TK/Chủ TK, Môn dạy được, Quận dạy được.
- **Validation:** CCCD đúng format 12 số, MST đúng format 10 số, `service_fee_percentage` ∈ [0, 100].
- **Main flow:** Tương tự ADM-01 nhưng thêm bước set quyền dạy các môn.
- **BR liên quan:** `BR-PAY-01`, `BR-PAY-02`

#### ADM-03: Quản lý Lớp học
- **Actor:** Admin
- **Mô tả:** Tạo lớp 1-1 hoặc lớp nhóm, gán GV/HS, cấu hình lịch + deadline.
- **Input form:** Mã lớp (auto), Chương trình → Môn, GV (dropdown search), HS (multi-select cho lớp nhóm), Loại lớp, Học phí/buổi, % GV, Tổng buổi, Lịch cố định (chọn Thứ + khung giờ, có thể nhiều dòng), Hạn giao BTVN (TIME, default 12:00), Hạn chấm BTVN (INT giờ, default 24).
- **Validation:** Khung giờ hợp lệ (start < end), không trùng lịch GV đã dạy lớp khác cùng Thứ-giờ, tổng `current_classes <= max_classes` của GV.
- **Main flow:** (1) Tạo lớp → (2) Cấu hình lịch → (3) Gán HS → (4) Hệ thống auto-generate `sessions` cho 4 tuần tới từ schedules.
- **Alternative flow:** Tạm dừng/Tiếp tục lớp (đổi `classes.status`), Chuyển lớp (chuyển HS sang lớp khác).
- **BR liên quan:** `BR-SCHED-01`

#### ADM-04: Sắp xếp lớp (Class Matching)
- **Actor:** Admin
- **Mô tả:** Gợi ý GV phù hợp cho HV chưa có lớp.
- **Thuật toán match:** Lọc GV có `subject_id` ∈ HV cần, `district` ∈ GV dạy được, lịch rảnh GV khớp ≥ 1 khung giờ rảnh của HV, giới tính khớp yêu cầu (nếu PH yêu cầu), `current_classes < max_classes`.
- **Output:** Danh sách GV xếp hạng theo điểm match, hiển thị % khớp lịch, rank, số lớp hiện tại.
- **Main flow:** (1) Chọn HV đang `pending_arrange` → (2) Hệ thống tính điểm → (3) Admin chọn GV → (4) Tạo lớp tự động với cấu hình mặc định.

#### ADM-05: Quản lý Yêu cầu (Requests)
- **Actor:** Admin (duyệt), Teacher/Student (tạo)
- **Mô tả:** Duyệt/Từ chối các yêu cầu từ bảng `requests`.
- **Loại request:** `add_session` (thêm buổi), `send_test` (gửi bài test), `change_schedule` (đổi lịch cố định), `request_leave` (xin nghỉ), `request_bonus`, `other`.
- **Validation duyệt:**
  - `add_session`: kiểm tra `classes.remaining_sessions > 0` (trừ vào remaining khi duyệt).
  - `change_schedule`: kiểm tra không trùng lịch GV với lớp khác.
  - `request_leave`: kiểm tra buổi đó còn trong tương lai.
- **Main flow:** (1) Vào menu Quản lý Yêu cầu → filter theo trạng thái/loại → (2) Click xem chi tiết → (3) Duyệt hoặc Từ chối (nhập `admin_note`).
- **BR liên quan:** `BR-SCHED-02`

#### ADM-06: Quản lý Vi phạm (Tick)
- **Actor:** Admin, Cronjob
- **Mô tả:** Cấu hình rule + xem log tick phạt.
- **CRUD bảng `violation_thresholds`:** đặt rule theo khoảng số lớp (VD: 1-3 lớp → miễn 2 tick, 4-7 lớp → miễn 3 tick, ≥8 lớp → miễn 4 tick, ngưỡng hạ phí 8 tick).
- **Xem danh sách tick:** Lọc theo GV, lý do, thời gian, có phân biệt `auto_generated` (cronjob) vs thủ công.
- **Thêm tick thủ công:** Chọn GV, lý do, số tick (+/-), ghi chú.
- **BR liên quan:** `BR-TICK-01`, `BR-TICK-02`, `BR-TICK-03`

#### ADM-07: Thống kê Tài chính
- **Actor:** Admin
- **Mô tả:** Tính và xuất phí dịch vụ cho GV theo tháng.
- **Công thức:** Xem `BR-PAY-01`, `BR-PAY-02`, `BR-PAY-03`.
- **Main flow:** (1) Chọn tháng → (2) Hệ thống query `transactions WHERE period_month = ?` → (3) Hiển thị bảng: Tổng thu, Tổng phạt, Tổng thưởng, Thuế, Thực nhận → (4) Xuất Excel.
- **Quy trình duyệt:** Nháp → Chờ duyệt → Đã thanh toán (đổi `transactions.status`).
- **BR liên quan:** `BR-PAY-01` đến `BR-PAY-04`

### 2.2. Phân hệ Teacher (Giáo viên)

#### TCH-01: Quản lý Lịch dạy
- **Actor:** Teacher
- **Mô tả:** Xem lịch tuần, đăng ký lịch trống, gửi request.
- **Calendar view:** Hiển thị 7 ngày × khung giờ, mỗi ô:
  - **Đỏ** = buổi sắp tới
  - **Hồng** = buổi đã hoàn thành
  - **Xám** = buổi nghỉ (cancelled)
  - **Xanh lá** = trống
  - **Xanh dương** = Lớp 1-1, **Cam** = Lớp đông (theo legend)
- **Main flow:** (1) Click ô trống → popup chi tiết → (2) Click `Đăng ký lịch trống` (ghi nhận admin biết) HOẶC `Xin nghỉ` (tạo `request_leave`) HOẶC `Đổi lịch cố định` (tạo `change_schedule`).

#### TCH-02: Quản lý Lớp & Buổi học
- **Actor:** Teacher
- **Mô tả:** Xem danh sách HV/lớp nhóm; ghi lesson summary; yêu cầu thêm buổi/test.
- **Ghi Lesson Summary:** Form gồm (1) Chọn chuyên đề (topic), (2) Nhập nội dung bài học, (3) Upload file đính kèm BTVN, (4) Nhập nội dung BTVN. **Deadline = 12:00 ngày kế tiếp buổi học**. Quá hạn → cronjob tự set `is_summary_late=TRUE` + tạo `violation`.
- **Request thêm buổi:** Tạo `request_type=add_session` với payload `{session_date, start_time, end_time, reason}`. Admin duyệt thì insert session mới, trừ `remaining_sessions`.
- **BR liên quan:** `BR-TICK-01`

#### TCH-03: Giao & Chấm BTVN
- **Actor:** Teacher
- **Mô tả:** Xem danh sách bài nộp, chấm điểm.
- **Main flow:** (1) Vào chi tiết buổi học → tab BTVN → (2) Click vào 1 HV → (3) Tải file bài làm (nếu HS đã nộp) → (4) Nhập điểm + nhận xét + upload file đã chấm (Excel/PDF) → (5) Lưu.
- **Hạn chấm:** Theo `classes.hw_check_hours` (mặc định 24h trước buổi tiếp theo). Quá hạn → cronjob set `is_graded_late=TRUE` + tạo `violation`.
- **BR liên quan:** `BR-TICK-02`

#### TCH-04: Chấm Test / Minitest
- **Actor:** Teacher
- **Mô tả:** Chấm bài làm Test/Minitest của HV.
- **Main flow:** (1) Vào chi tiết lớp → tab Test → (2) Click 1 test → (3) Chọn HV → (4) Tải file bài làm → (5) Nhập `score_x/20` + `score_y_percent` + upload file đã chấm → (6) Lưu.
- **Hạn chấm:** Sau khi HS nộp, deadline = 48h. Quá hạn → cronjob tạo `violation`.
- **BR liên quan:** `BR-TICK-02`

#### TCH-05: Xem Phí dịch vụ & Vi phạm
- **Actor:** Teacher
- **Mô tả:** Xem lịch sử nhận tiền từng buổi + theo dõi tick.
- **Phí dịch vụ:** Bảng giao dịch theo tháng, có filter theo `period_month`, `type`. Tổng cuối = Thực nhận (xem công thức BR-PAY-03).
- **Vi phạm:** Hiển thị tổng tick thực tế (đã trừ tick miễn theo `violation_thresholds.ticks_exempted`); so sánh với ngưỡng `ticks_to_demote_fee`; nếu `tick_thực_tế >= ticks_to_demote_fee` → hiển thị banner cảnh báo cam.
- **BR liên quan:** `BR-TICK-03`, `BR-PAY-01`

#### TCH-06: Nhận xét theo tháng (MỚI)
- **Actor:** Teacher
- **Mô tả:** Viết nhận xét tổng kết hàng tháng cho HV từng lớp.
- **Main flow:** (1) Vào trang Nhận xét theo tháng → (2) Chọn HV + tháng → (3) Chọn rating tổng thể + nhập nội dung + nhập % tiến bộ → (4) Đăng (set `published_at`). HV xem được sau khi publish.

#### TCH-07: Thư viện (MỚI)
- **Actor:** Teacher
- **Mô tả:** Upload tài liệu cho HV/lớp.
- **File types:** slide, exam (đề thi), video, audio, document, other.
- **Main flow:** (1) Vào Thư viện → (2) Upload file (lưu Cloud Storage) → (3) Chọn lớp/môn/chuyên đề (optional) → (4) Lưu.
- **Validation:** File size ≤ 100MB (config), chỉ accept MIME cho phép (chống malware).

### 2.3. Phân hệ Student (Học viên)

#### STU-01: Xem Lộ trình & Chuyên đề
- **Actor:** Student
- **Mô tả:** Xem các môn đang học, chuyên đề (X/Y buổi), điểm Test/Minitest.
- **Main flow:** Vào "Các khoá học" → click lớp → hiển thị: Tên lớp, Môn, GV, % hoàn thành, danh sách chuyên đề (dropdown), điểm test gần nhất.

#### STU-02: Lịch học
- **Actor:** Student
- **Mô tả:** Xem TKB theo tuần.
- **View:** Calendar 7 ngày, mỗi buổi hiển thị mã lớp + giờ.

#### STU-03: Nộp BTVN & Bài Test
- **Actor:** Student
- **Mô tả:** Nộp bài tập/bài test.
- **Main flow:** (1) Vào chi tiết buổi học → (2) Đọc nội dung BTVN + tải file đính kèm từ GV (nếu có) → (3) Upload tối đa 4 file (ảnh/PDF, max 20MB/file) → (4) Nhấn Nộp.
- **Validation:** Trước `hw_deadline` → trạng thái `submitted_on_time`. Sau deadline → vẫn nộp được nhưng set `submitted_late` (text đỏ cảnh báo).
- **Đếm ngược:** Hiển thị countdown tới `hw_deadline`.

#### STU-04: Xem Nhận xét
- **Actor:** Student
- **Mô tả:** Đọc nhận xét từng buổi + nhận xét tháng.
- **Main flow:** Vào tab "Nhận xét" → xem nhận xét buổi (theo `sessions.lesson_summary`) + nhận xét tháng (theo `monthly_reviews` đã `published`).

### 2.4. Phân hệ Phụ huynh (Mở rộng tương lai) — MỚI
- **PAR-01:** Đăng nhập bằng SĐT PH (OTP).
- **PAR-02:** Xem tiến độ con: lịch học, BTVN đã nộp/chưa nộp, điểm test, nhận xét tháng.
- **PAR-03:** Thanh toán học phí (tích hợp VNPay/MoMo).
- **PAR-04:** Nhận thông báo đẩy khi con có tick phạt từ GV hoặc trễ hạn nộp BTVN.

### 2.5. Nghiệp vụ chung

#### SYS-01: Thông báo (MỚI)
- Push notification cho user khi có sự kiện (chuông đỏ UI).
- Cronjob gửi notification cho HV trước buổi học 1h.

#### SYS-02: Tìm kiếm chung (MỚI) — **[ĐÃ THAY THẾ — xem D1]**
- Ô search header: Admin tìm kiếm toàn cục (HS/GV/lớp), Teacher tìm trong HV/lớp của mình, Student tìm lớp/chuyên đề.
- ~~Dùng Elasticsearch~~ → **MySQL FULLTEXT index** (hoặc `LIKE` + index thông thường ở MVP). Search toàn cục Admin gộp `db_lms_users`/`db_lms_classes`/`db_lms_sessions`.

#### SYS-03: Đăng nhập/Đăng xuất — **[ĐÃ THAY THẾ — xem D1, D3]**
- ~~JWT token, refresh token 7 ngày~~ → **PHP session + cookie remember-me (JWT ký cookie, theo pattern admin hiện tại đã hardened)**.
- Mật khẩu mã hóa **bcrypt** (cost ≥ 10).
- **3 portal đối xứng, tách biệt**: `lms/admin.php` (Admin LMS), `lms/teacher.php` (GV),
  `lms/student.php` (HV) — mỗi role vào khu riêng, dùng chung `db_lms_users` (role ENUM).
- **Tách khỏi e-commerce**: tài khoản LMS (`db_lms_users`) độc lập với tài khoản admin
  e-commerce (`db_user`); không dùng `db_thanhvien` (MD5).
- Rate-limit đăng nhập (5 lần/15 phút theo username+IP).
- Quên mật khẩu qua SĐT/email (OTP qua PHPMailer).

---

## 3. BUSINESS RULES (QUY TẮC NGHIỆP VỤ)

### 3.1. Quy tắc Vi phạm (Tick phạt)

**BR-TICK-01: Phạt giao bài (Lesson Summary)**
- Trigger: Cronjob chạy mỗi 15 phút.
- Điều kiện: `sessions.status = 'finished' AND summary_submitted_at IS NULL AND session_date + 1 day + classes.hw_deadline_time < NOW()`.
- Hành động: Set `is_summary_late = TRUE`, insert `violations(teacher_id, class_id, session_id, tick_amount=+1, reason='late_lesson_summary', auto_generated=TRUE)`.
- Mỗi session chỉ phạt 1 lần (check trước khi insert).

**BR-TICK-02: Phạt chấm BTVN/Test**
- Trigger: Cronjob mỗi 15 phút.
- BTVN: `session_students.status = 'submitted' AND hw_graded_at IS NULL AND submitted_at + (classes.hw_check_hours giờ) < NOW()`.
- Test: `test_submissions.submission_status IN ('submitted_on_time', 'submitted_late') AND graded_at IS NULL AND submitted_at + 48h < NOW()`.
- Hành động: Tương tự BR-TICK-01 với `reason='late_hw_grading'` hoặc `'late_test_grading'`.

**BR-TICK-03: Hạ mức phí dịch vụ**
- Công thức: `tick_thực_tế = SUM(violations.tick_amount) - violation_thresholds.ticks_exempted` (lookup rule theo số lớp GV đang dạy).
- Nếu `tick_thực_tế >= violation_thresholds.ticks_to_warn` → notification `tick_warning` + banner cảnh báo trên UI GV.
- Nếu `tick_thực_tế >= violation_thresholds.ticks_to_demote_fee` → tháng tiếp theo áp dụng `fee_percentage_after_demote` thay cho `service_fee_percentage`.

### 3.2. Quy tắc Tính phí dịch vụ (Lương GV)

**BR-PAY-01: Thu nhập buổi dạy**
- `thu_nhap_buoi = sessions.hw_completed = true` thì:
  - `thu_nhap_buoi = classes.price_per_session * COALESCE(classes.teacher_percentage, teachers.service_fee_percentage) / 100`

**BR-PAY-02: Điều chỉnh % theo tick**
- Nếu tháng tính lương GV có `tick_thực_tế >= ticks_to_demote_fee` ở tháng trước → dùng `fee_percentage_after_demote` (tỷ lệ trực tiếp) thay cho % mặc định; nếu giá trị cấu hình cao hơn tỷ lệ hiện hành thì dùng tỷ lệ hiện hành để việc hạ phí không làm tăng thu nhập.

**BR-PAY-03: Tính thuế TNCN**
- Nếu `teachers.is_commit_08 = TRUE` → thuế = 0%.
- Nếu `is_commit_08 = FALSE` AND `tong_thu_nhap >= 5,000,000đ` → thuế = 10% × tổng thu nhập.
- Ngược lại → thuế = 0%.

**BR-PAY-04: Tiền thực nhận**
- `tien_thuc_nhan = tong_thu_nhap_buoi + dieu_chinh_cong - dieu_chinh_tru - phat_vi_pham - thue`
- Trong đó `phat_vi_pham` = `SUM(transactions.amount WHERE type='penalty' AND period_month=?)` (đã được trừ từ `transactions`).

### 3.3. Quy tắc Lịch học

**BR-SCHED-01: Trùng lịch GV**
- Khi tạo/cập nhật `schedules`: kiểm tra không trùng `day_of_week` + `start_time` giữa các lớp cùng GV.
- Cronjob generate `sessions` 4 tuần trước, tránh tạo session trùng session khác đã tồn tại.

**BR-SCHED-02: Đổi lịch cố định**
- Chỉ áp dụng từ tuần kế tiếp.
- Sessions đã tạo trong tuần hiện tại giữ nguyên.
- Cần Admin duyệt `request_type='change_schedule'`.

### 3.4. Quy tắc Tài khoản

**BR-AUTH-01:** Mật khẩu mặc định = 8 ký tự random, HS phải đổi khi đăng nhập lần đầu.

---

## 4. MA TRẬN PHÂN QUYỀN (RBAC MATRIX)

| Resource / Action | Admin | Teacher | Student | Parent (tương lai) |
|---|---|---|---|---|
| **User - Xem danh sách** | All | Self only | Self only | Con của mình |
| **User - Tạo/Sửa/Khoá** | ✔ | ✗ | ✗ | ✗ |
| **Teacher - Cấu hình % lương** | ✔ | ✗ | ✗ | ✗ |
| **Class - Tạo/Xoá** | ✔ | ✗ (chỉ request) | ✗ | ✗ |
| **Class - Xem chi tiết** | ✔ | GV của lớp | HS trong lớp | Con trong lớp |
| **Class - Đổi lịch cố định** | ✔ | Request only | ✗ | ✗ |
| **Session - Xem** | ✔ | GV dạy | HS học | Con học |
| **Session - Ghi summary** | ✗ | ✔ (trong hạn) | ✗ | ✗ |
| **Session - Sửa summary** | ✔ | Chỉ trong 24h | ✗ | ✗ |
| **BTVN - Nộp** | ✗ | ✗ | ✔ | ✗ |
| **BTVN - Chấm** | ✗ | ✔ | ✗ | ✗ |
| **Test - Tạo đề** | ✗ | ✔ | ✗ | ✗ |
| **Test - Nộp bài** | ✗ | ✗ | ✔ | ✗ |
| **Test - Chấm** | ✗ | ✔ | ✗ | ✗ |
| **Violation - Xem** | ✔ | Self only | ✗ | ✗ |
| **Violation - Thêm/Sửa thủ công** | ✔ | ✗ | ✗ | ✗ |
| **Violation - Cronjob** | System | ✗ | ✗ | ✗ |
| **Transaction - Xem** | ✔ | Self only | ✗ | ✗ |
| **Transaction - Tạo/Sửa** | ✔ | ✗ | ✗ | ✗ |
| **Request - Tạo** | ✔ | ✔ | ✔ (giới hạn) | ✗ |
| **Request - Duyệt** | ✔ | ✗ | ✗ | ✗ |
| **Library - Upload** | ✔ | ✔ | ✗ | ✗ |
| **Library - Download** | ✔ | ✔ | ✔ (lớp của mình) | ✔ (con) |
| **Monthly Review - Viết** | ✗ | ✔ | ✗ | ✗ |
| **Monthly Review - Xem** | ✔ | ✔ | ✔ (self, sau publish) | ✔ (con) |
| **Notification - Xem** | ✔ | Self | Self | Self |
| **Audit Log - Xem** | ✔ (super_admin) | ✗ | ✗ | ✗ |
| **Violation Thresholds - Cấu hình** | ✔ | ✗ | ✗ | ✗ |

---

## 5. YÊU CẦU PHI CHỨC NĂNG (NON-FUNCTIONAL REQUIREMENTS)

> [!NOTE]
> **Mục §5 đã được điều chỉnh cho PHP/MySQL (D1).** Các con số hạ xuống cho phù hợp quy mô
> 1 trung tâm ngoại ngữ (không còn mục tiêu 10.000 user / Elasticsearch / Redis / S3 / AWS).
> Phần gạch ngang là yêu cầu cũ đã thay thế.

### 5.1. Hiệu năng
- Tải trang Lịch học (Calendar view) dưới 2 giây. Lazy load theo tháng (query WHERE `session_date` BETWEEN, có composite index).
- ~~Tìm kiếm toàn cục: phản hồi < 500ms (dùng Elasticsearch cho Admin)~~ → **MySQL FULLTEXT/LIKE, < 1s với số lượng bản ghi hiện tại.**
- Generate sessions cho 4 tuần tới: chạy batch job (`cron/session_generate.php`), < 5 phút.

### 5.2. Lưu trữ (Storage)
- ~~File BTVN, Test upload lưu Cloud Storage (S3/GCS), không lưu local.~~ → **Lưu local qua CKFinder (thư mục uploads/), sau này mới cân nhắc S3/GCS.** — **[ĐÃ THAY THẾ — xem D1]**
- Giới hạn: file ≤ 100MB (config), tổng storage/GV ≤ 10GB.
- Auto-delete file sau 2 năm kể từ khi session finished (config) — chuyển thành cron dọn dẹp (Phase 3+).

### 5.3. Responsive
- Giao diện Teacher/Student ưu tiên Mobile/Tablet (HS chụp ảnh nộp BTVN).
- Admin ưu tiên Desktop.
- Breakpoint: < 768px (mobile), 768-1024px (tablet), > 1024px (desktop).

### 5.4. Bảo mật
- Mật khẩu mã hoá Bcrypt (cost ≥ 10).
- ~~API bảo vệ bằng JWT (access 1h, refresh 7 ngày)~~ → **PHP session + CSRF token cho mọi POST + cookie remember-me JWT.** — **[ĐÃ THAY THẾ — xem D1]**
- Chống upload malware: validate MIME + giới hạn extension qua CKFinder.
- Rate limit: 100 req/min/user, 1000 req/min/IP.
- Audit log mọi thao tác tài chính + tick phạt.

### 5.5. Khả dụng
- Uptime ≥ 99.5%.
- Backup database hàng ngày, retention 30 ngày.
- ~~Health check endpoint `/api/health` mỗi 30s~~ → **bảng `db_lms_cron_runs` + cảnh báo email nếu cron chết > 30 phút.**

### 5.6. Khả năng mở rộng
- ~~Hỗ trợ 10.000 user concurrent.~~ → **Hỗ trợ vài trăm user concurrent (quy mô thực tế 1 trung tâm).** — **[ĐÃ THAY THẾ]**
- ~~Database partition theo tháng cho `sessions`, `audit_logs`~~ → **không partition, dùng composite index + `WHERE session_date` (xem ERD v2.1).**
- ~~Cache lịch dạy Redis (TTL 5 phút)~~ → **không cache ở MVP; tối ưu query + index.**

### 5.7. Đa ngôn ngữ (MỚI) — **[TẠM BỎ — xem D1]**
- ~~Hỗ trợ Tiếng Việt (mặc định) + Tiếng Anh.~~ → **Chỉ Tiếng Việt ở MVP** (codebase hiện có sẵn cấu hình vi/ko cho frontend nếu cần bật lại sau).

---

## 6. MA TRẬN TRUY VẾT YÊU CẦU (REQUIREMENTS TRACEABILITY)

| FR ID | Use Case (sẽ bổ sung) | DB Tables chính | Test case ID (tham chiếu) |
|---|---|---|---|
| ADM-01 | UC-ADM-01 | users, students | TC-ADM-01 |
| ADM-02 | UC-ADM-02 | users, teachers | TC-ADM-02 |
| ADM-03 | UC-ADM-03 | classes, schedules, class_students | TC-ADM-03 |
| ADM-04 | UC-ADM-04 | students, teachers, schedules | TC-ADM-04 |
| ADM-05 | UC-ADM-05 | requests | TC-ADM-05 |
| ADM-06 | UC-ADM-06 | violations, violation_thresholds | TC-ADM-06 |
| ADM-07 | UC-ADM-07 | transactions, sessions | TC-ADM-07 |
| TCH-01 | UC-TCH-01 | sessions, schedules, requests | TC-TCH-01 |
| TCH-02 | UC-TCH-02 | sessions, requests | TC-TCH-02 |
| TCH-03 | UC-TCH-03 | session_students, violations | TC-TCH-03 |
| TCH-04 | UC-TCH-04 | tests, test_submissions, violations | TC-TCH-04 |
| TCH-05 | UC-TCH-05 | transactions, violations | TC-TCH-05 |
| TCH-06 | UC-TCH-06 | monthly_reviews | TC-TCH-06 |
| TCH-07 | UC-TCH-07 | library_files | TC-TCH-07 |
| STU-01 | UC-STU-01 | classes, topics, tests | TC-STU-01 |
| STU-02 | UC-STU-02 | sessions, schedules | TC-STU-02 |
| STU-03 | UC-STU-03 | session_students, test_submissions | TC-STU-03 |
| STU-04 | UC-STU-04 | sessions, monthly_reviews | TC-STU-04 |
| PAR-01..04 | UC-PAR-01..04 | users, students, transactions | TC-PAR-01..04 |
| SYS-01 | UC-SYS-01 | notifications | TC-SYS-01 |
| SYS-02 | UC-SYS-02 | (MySQL FULLTEXT index) | TC-SYS-02 |
| SYS-03 | UC-SYS-03 | users | TC-SYS-03 |

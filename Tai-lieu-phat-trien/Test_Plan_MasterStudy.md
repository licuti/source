# TEST PLAN — LMS MasterStudy (Trung tâm Ngoại ngữ AROMA)

> **Phiên bản:** 1.0 — 12/10/2026 (sau khi hoàn thành P4)
> **Phạm vi:** toàn bộ hệ thống LMS (`lms/`), 3 portal Admin/Teacher/Student,
> tầng service (`lms/services/Lms/`), cron jobs và cơ sở dữ liệu `db_lms_*`.
> **Tham chiếu:** `SRS_MasterStudy.md`, `Quy_Trinh_Phat_Trien.md`,
> `ERD_MasterStudy.md`, `Huong-Dan-Phat-Trien-Page.md`.

---

## 1. TỔNG QUAN

### 1.1. Mục tiêu kiểm thử

1. **Xác minh nghiệp vụ (Business Rules):** mọi BR-xxx trong SRS được service
   thực hiện đúng, bao gồm edge case (toàn rỗng, quá giới hạn, cạnh boundary).
2. **Phân quyền + Row-level security:** GV chỉ thấy/sửa dữ liệu của mình; HV
   chỉ thấy lớp mình; sai portal bị từ chối.
3. **Tính idempotent của cron:** chạy lại không tạo trùng, không double-tick,
   không double-pay.
4. **Tích hợp UI thực tế (smoke test):** từng trang render 200, POST lưu DB,
   CSV/luồng file hoạt động.

### 1.2. Mức độ kiểm thử (Test Layers)

| Lớp | Công cụ | Số lượng | Phạm vi |
|---|---|---:|---|
| **Unit/Integration (service)** | PHPUnit + DB test `phuongnamv_db_lms_test` | **355 test / 1261 assertion** | Toàn bộ `lms/services/Lms/` + cron CLI |
| **Smoke test HTTP** | Apache local (`source.test`) + session cookie | 15+ trang đã verify | 3 portal, render + POST + export |
| **Cron dry-run** | CLI `--dry-run` trên cả 2 DB | 3 jobs | Xem trước không ghi DB |
| **Kiểm tra thủ công (mockup)** | So sánh PNG vs template | Xem §6 | Gap giao diện |

### 1.3. Nguyên tắc test

- **KHÔNG đụng DB thật:** PHPUnit dùng `phuongnamv_db_lms_test` (copy schema).
  `LmsTestCase::setUp()` TRUNCATE toàn bộ `db_lms_*` trước mỗi test → cách ly
  tuyệt đối. Test block ngay nếu `LMS_TEST_DB` không chứa `phuongnamv_db_lms_test`.
- **Cron test** chạy script qua `shell_exec(PHP_BINARY ...)` với `--db=test`.
- **PHP 7.3 compatible:** không dùng `str_contains()`, `??=`, named args,
  `fn()`; test cũng phải tương thích.
- **Mọi query bind tham số** (PDO prepared), mọi output HTML escape `e()`.

---

## 2. UNIT TEST THEO TỪNG SERVICE

Bảng dưới ánh xạ service → test class → số test → các BR/module được bao phủ.

### 2.1. Core & Auth

| Service | Test class | Test | Bao phủ |
|---|---|---:|---|
| `UserService` | `UserServiceTest` | 12 | Auth 3 portal (D9 sai portal → từ chối), bcrypt, RBAC, CRUD admin |
| `UserService` | `UserServiceProfileTest` | 30 | Profile GV/HV, `free_time_slots`, `teachable_subjects/districts`, `subject_id`, `preferred_teacher_gender` |
| `ClassService` | `ClassServiceTest` | 26 | ADM-03 tạo lớp 1-1/nhóm, lịch tuần, sinh sessions, `remaining_sessions` (FOR UPDATE) |
| `EnrolmentService` | `EnrolmentServiceTest` | 23 | ADM-04 thêm/chuyển/xoá HV, sync `session_students` upcoming, huỷ khi chưa có dữ liệu |
| `ScheduleService` | `ScheduleServiceTest` | 14 | BR-SCHED-01 chống trùng lịch GV, free slots, weekly note |
| `SessionService` | `SessionServiceTest` | 16 | Sinh session từ lịch (idempotent), hạn BTVN 12:00, lesson summary + chấm BTVN, `listWeek`/`listWeekForStudent` row-level |
| `TeacherProfileService` | `TeacherProfileServiceTest` | 9 | TCH-01 profile, khu vực dạy, online status |
| `RequestService` | `RequestServiceTest` | 9 | TCH-01 request xin nghỉ/thêm buổi/đổi lịch (góc GV) |
| `RequestService` | `RequestServiceAdminTest` | 22 | ADM-05 duyệt atomic + `FOR UPDATE` idempotent, check trùng lịch, trừ `remaining_sessions` |
| `NotificationService` | (qua `TickServiceTest`) | — | SYS-01 chuông thông báo, đánh dấu đã đọc |

### 2.2. Tài chính & Vi phạm (Phase 2)

| Service | Test class | Test | Bao phủ |
|---|---|---:|---|
| `TickService` | `TickServiceTest` | 20 | BR-TICK-01/02/03: `closePastSessions`, quét summary/BTVN trễ, `evaluateTickPenalty` warn/demote idempotent, tick thủ công ±1, miễn trừ, CRUD ngưỡng |
| `TaxService` | `TaxServiceTest` | 15 | BR-PAY-03 thuế TNCN (0% cam kết 08 hoặc <5tr, 10% phần vượt), ghi transaction |
| `PayrollService` | `PayrollServiceTest` | 26 | BR-PAY-01/02/04: `closeMonth` idempotent FOR UPDATE, áp phí đã hạ, `monthSummary`, `setMonthStatus` draft→pending→paid, điều chỉnh thủ công |

### 2.3. Phase 3 (ADM-04 matching, thư viện, P3 view-only)

| Service | Test class | Test | Bao phủ |
|---|---|---:|---|
| `MatchingService` | `MatchingServiceTest` | 20 | ADM-04 hard filter (môn/quận/giới tính/online/lịch/sức chứa), điểm 0-100, `createClassFromMatch` tạo lớp 1-1 tự động |
| `LibraryService` | `LibraryServiceTest` | 12 | TCH-07 upload MIME whitelist 100MB, scope lớp GV, download ACL + counter, soft delete |
| `AdminReportService` | `AdminReportServiceTest` | 3 | `lms-thong-ke-buoi-hoc` (filter học viên/ngày, tổng buổi/finished, CSV), `lms-bai-tap-ve-nha`, `lms-lo-trinh` |
| `ClassRulesService` | `ClassRulesServiceTest` | 2 | `db_lms_class_rules` lưu/đọc nội quy, student read-only |
| `MonthlyReviewService` | `MonthlyReviewServiceTest` | 8 | STU-04 + TCH-06: `listForTeacher`/`saveForTeacher` row-level, draft/publish, chỉ published cho HV |

### 2.4. Cron jobs (P4)

| Job | Test class | Test | Bao phủ |
|---|---|---:|---|
| `lms_tick_cron.php` | `TickServiceTest` (service layer) | 20 | BR-TICK pipeline 4 bước, dry-run không ghi |
| `lms_session_generate.php` | `SessionGenerateCronTest` | 7 | Sinh sessions + `session_students`, idempotent, dry-run, `--weeks`, bỏ qua lớp paused, guard CLI, log `cron_runs` |
| `lms_payroll_cron.php` | `PayrollServiceTest` (service layer) | 26 | Chốt tháng, `--teacher`, `--month` |

**Chạy:**

```bash
# Toàn bộ (DB test, KHÔNG đụng DB thật)
php vendor/phpunit/phpunit/phpunit

# Chỉ 1 class
php vendor/phpunit/phpunit/phpunit --filter SessionGenerateCronTest

# Liệt kê
php vendor/phpunit/phpunit/phpunit --list-tests
```

⚠️ Sau khi chạy xong, PHPUnit truncate DB test → **reseed trước smoke test**:

```bash
php database/seeds/2026_10_07_lms_demo_seed.php phuongnamv_db_lms_test
# + reseed modules/permissions/thresholds (xem lms/README.md mục "Test + seed")
```

---

## 3. SMOKE TEST HTTP (qua Apache local)

### 3.1. Chuẩn bị

```bash
# 1. DB test phải có modules + demo data (PHPUnit truncate sau mỗi run):
#    chạy modules seed + demo seed (xem README)
# 2. Apache chạy (Laragon: http://source.test)
```

### 3.2. Ma trận đăng nhập

| Portal | URL | Tài khoản demo |
|---|---|---|
| Admin | `http://source.test/lms/admin/login` | `admin` / `admin123` |
| Teacher | `http://source.test/lms/teacher/login` | `gv01` / `gv01123456` |
| Student | `http://source.test/lms/student/login` | `hv01` / `hv01123456` |

Lấy CSRF token từ form (field `csrf_token`), POST kèm token.

### 3.3. Checklist trang Admin (15 module)

| # | Alias | Mong đợi | Đã verify |
|---|---|---|:---:|
| 1 | `lms-tong-quan` | 200, 4 thẻ stats + bảng chương trình | ✅ |
| 2 | `lms-hoc-vien` | 200, CRUD + filter + phân trang | ✅ |
| 3 | `lms-giao-vien` | 200, CRUD + hồ sơ thuế | ✅ |
| 4 | `lms-lop` | 200, CRUD lớp | ✅ |
| 5 | `lms-lop-hoc-sinh` | 200, ma trận HV × chương trình | ✅ |
| 6 | `lms-bai-tap-ve-nha` | 200, filter + báo cáo BTVN | ✅ (P3) |
| 7 | `lms-vi-pham` | 200, log tick + CRUD ngưỡng | ✅ |
| 8 | `lms-yeu-cau` | 200, duyệt/từ chối request | ✅ |
| 9 | `lms-lo-trinh` | 200, cây chương trình→môn→chuyên đề | ✅ (P3) |
| 10 | `lms-noi-quy` | 200, form nội quy + POST lưu | ✅ (P3) |
| 11 | `lms-thu-vien` | 200, upload + filter + tải | ✅ |
| 12 | `lms-phi-dv` | 200, thống kê tháng + xuất CSV | ✅ |
| 13 | `lms-thong-ke-buoi-hoc` | 200, filter mã HV + bảng buổi + CSV BOM | ✅ (P3) |
| 14 | `lms-sap-xep-lop` | 200, HV chờ xếp + match GV | ✅ |
| 15 | `lms-thong-bao` (ẩn) | 200, trang chuông | ✅ |

**POST flow đã verify:**
- `lms-noi-quy/luu` (CSRF + content) → `db_lms_class_rules` có row, `updated_by` = admin id.
- `lms-thong-ke-buoi-hoc/xuat?student_code=ST001` → `text/csv`, UTF-8 BOM, 5 dòng (header + 4 buổi).

### 3.4. Checklist trang Teacher (7 module)

| # | Alias | Mong đợi | Đã verify |
|---|---|---|:---:|
| 1 | `lms-tong-quan` | 200, banner cảnh báo tick | ✅ |
| 2 | `lms-hoc-vien-cua-toi` | 200, danh sách HV | ✅ |
| 3 | `lms-lich-day` | 200, calendar tuần 5 màu + modal | ✅ |
| 4 | `lms-phi-dv` | 200, transactions + tổng hợp | ✅ |
| 5 | `lms-vi-pham` | 200, tick + panel ngưỡng | ✅ |
| 6 | `lms-thu-vien` | 200, tài liệu lớp mình | ✅ |
| 7 | `lms-nhan-xet-thang` | 200, list HV + form nhận xét (TCH-06) | ✅ (P3) |

### 3.5. Checklist trang Student (5 module)

| # | Alias | Mong đợi | Đã verify |
|---|---|---|:---:|
| 1 | `lms-tong-quan` | 200, buổi sắp tới | ✅ |
| 2 | `lms-khoa-hoc` | 200, lộ trình chuyên đề + nộp bài | ✅ |
| 3 | `lms-lich-hoc` | 200, calendar tuần 3 màu | ✅ |
| 4 | `lms-nhan-xet-thang` | 200, chỉ hiện published | ✅ |
| 5 | `lms-noi-quy` | 200, read-only nội quy admin | ✅ (P3) |

**Cross-portal verify:** nội quy admin lưu → student `hv01` nhìn thấy ngay.

---

## 4. KIỂM THỬ CRON (PRODUCTION)

### 4.1. Lịch trình đã triển khai

| Job | Tần suất | Windows Task | Linux crontab |
|---|---|---|---|
| `lms_tick_cron.php` | mỗi 15 phút | `LMS_tick_scan` | `*/15 * * * *` |
| `lms_session_generate.php` | hàng ngày 01:00 | `LMS_session_generate` | `0 1 * * *` |
| `lms_payroll_cron.php` | ngày 1 hàng tháng 00:15 | `LMS_payroll_close` | `15 0 1 * *` |

File tham chiếu: `database/cron/lms_crontab.txt` (Linux),
`database/cron/lms_install_scheduled_tasks.php` (Windows).

### 4.2. Dry-run checklist

```bash
# Tick — xem trước số buổi/tick sẽ xử lý (KHÔNG ghi DB, KHÔNG ghi cron_runs)
php database/cron/lms_tick_cron.php --dry-run
php database/cron/lms_tick_cron.php --dry-run --db=phuongnamv_db_lms_test

# Session generate — đếm session/session_students sẽ sinh
php database/cron/lms_session_generate.php --dry-run
php database/cron/lms_session_generate.php --dry-run --weeks=8

# Payroll — xem trước tháng trước
php database/cron/lms_payroll_cron.php --dry-run
php database/cron/lms_payroll_cron.php --dry-run --month=2026-09
```

### 4.3. Idempotency checklist

Mỗi job chạy 2 lần liên tiếp; lần 2 phải không tạo bản ghi mới:

| Job | Lần 1 | Lần 2 mong đợi |
|---|---|---|
| tick | tick tăng theo số buổi trễ | `ticked: 0`, không tạo thông báo trùng |
| session_generate | `sessions_created > 0` | `sessions_created: 0`, `session_students_created: 0` |
| payroll | `closed > 0` | `closed: 0` (transaction tồn tại → skip) |

### 4.4. Monitoring (R1/R8)

Cả 3 job ghi `db_lms_cron_runs` qua `lms/lib/lms_cron.php`:

```sql
select job_name, status, started_at, finished_at, rows_affected, error_message
from db_lms_cron_runs order by id desc limit 9;
```

**Cảnh báo nếu:** job `running` >30 phút (chết treo), hoặc `status='failed'`.
Dry-run KHÔNG ghi row (tránh reset bộ đếm monitoring khi chạy tay).

---

## 5. DỮ LIỆU TEST

### 5.1. Seed

| File | Mục đích | DB |
|---|---|---|
| `2026_10_07_lms_modules_seed.sql` | 15 module admin / 8 teacher / 6 student + phân quyền role | cả 2 |
| `2026_10_10_lms_violation_thresholds_seed.sql` | 3 khoảng lớp → miễn 2/3/4 tick, warn 5, demote 8, phí sau hạ 50% | cả 2 |
| `2026_10_12_lms_teacher_monthly_reviews_module.sql` | Đăng ký module TCH-06 cho teacher + permission | cả 2 |
| `2026_10_12_lms_class_rules.sql` | Bảng nội quy `db_lms_class_rules` | cả 2 |
| `2026_10_07_lms_demo_seed.php` | 1 admin + 3 GV + 5 HV + 2 lớp + sessions 4 tuần + tick/transaction mẫu | cả 2 |

**Giá trị đã chốt (business, 12/10/2026):**
- `ticks_to_warn = 5` — cảnh báo trước ngưỡng hạ phí.
- `fee_percentage_after_demote = 50.00` — % phí sau hạ (mặc định 48%).
- Đổi qua UI ADM-06 (`lms-vi-pham`), không sửa seed.

⚠️ **Áp dụng migration/seed bằng `mysql.exe` phải thêm `--default-character-set=utf8mb4`**,
nếu không tên tiếng Việt bị mojibake (đã xảyra 1 lần với module TCH-06).

### 5.2. Tài khoản demo

```
admin   / admin123     (super_admin)
gv01    / gv01123456   (TE001, dạy CLS1 1-1)
gv02    / gv02123456   (TE002, dạy CLS2 nhóm)
gv03    / gv03123456   (chưa có lớp)
hv01..hv05 / hv0X123456
```

### 5.3. DB test sau PHPUnit

`LmsTestCase::setUp()` TRUNCATE toàn bộ `db_lms_*` → sau khi chạy suite, DB
test trống. **Bắt buộc reseed** trước khi smoke test thủ công.

---

## 6. GAP CÒN LẠI (sau P4)

### 6.1. Mockup vs giao diện (xem `Danh-Gia-Hien-Trang-LMS.md` §3)

| # | Vấn đề | Mức | Ưu tiên |
|---|---|---|---|
| 1 | Admin Tổng quan thiếu **timeline calendar tuần** (7 ngày × 24 khung giờ) | Thiếu hoàn toàn | Cao |
| 2 | Admin `lms-lop` thiếu cột "Khóa học", "Chuyên đề đang học", nút "Điều chỉnh số buổi", "Hạn chấm test" form | Thiếu | Trung bình |
| 3 | Teacher Tổng quan sai bố cục (đang 3 thẻ + bảng, mockup là **3 cột**) | Sai lệch | Cao |
| 4 | Teacher "Học viên của tôi" đang dạng bảng, mockup dạng **cards grid** + nhãn trạng thái BTVN | Sai lệch | Cao |
| 5 | Widget **Chat** (góc phải, 3 portal) | Thiếu hoàn toàn | Thấp |
| 6 | **Tìm kiếm toàn cục** trên header cam (đang là ô tĩnh) | Chưa có backend | Thấp |

(Lưu ý: `lms_layout.php` đã có placeholder chat + nút close, chỉ là HTML tĩnh.)

### 6.2. Kỹ thuật (xem `Danh-Gia-Hien-Trang-LMS.md` §5)

| # | Vấn đề | Giải pháp |
|---|---|---|
| 1 | File session PHP → scale ngang sẽ văng đăng nhập | Lưu session vào MySQL/Redis khi multi-server |
| 2 | Race condition còn lại: đăng ký lớp cuối chỗ | Đã có `FOR UPDATE` ở `EnrolmentService`/`PayrollService`; rà soát các path còn lại |
| 3 | Xuất Excel/CSV lớn chạy đồng đồng bộ → timeout | Chunk + streaming response, hoặc queue |
| 4 | File upload (BTVN, thư viện) đầy ổ cứng | Giới hạn quota + dọn file mồ côi qua cron |
| 5 | Timezone: `date.timezone` php.ini + MySQL cùng `Asia/Ho_Chi_Minh` | Cấu hình server production |

---

## 7. QUY TRÌNH RELEASE (CI thủ công)

Trước mỗi đợt release:

1. **Full test suite:** `php vendor/phpunit/phpunit/phpunit` → phải **OK**.
2. **Lint** tất cả file PHP thay đổi: `php -l <file>` (PHP 7.3 binary).
3. **Dry-run 3 cron** trên DB test (xem §4.2) → output `result: ok`.
4. **Reseed DB test** + smoke test các trang bị ảnh hưởng (xem §3).
5. **Migration forward-only:** chạy trên DB thật **và** DB test;
   `mysql.exe` thêm `--default-character-set=utf8mb4`.
6. **Commit theo phase** (VD `feat(lms): Phase 3 P3 — ...`), loại trừ file
   ngoài phạm vi (e-commerce `sources/`, `templates/`, `admin/` cũ).

---

## 8. LỊCH SỬ KIỂM THỬ

| Ngày | Mốc | Test | Assertion |
|---|---|---:|---:|
| 10/10/2026 | Phase 0–1 | 248 | — |
| 12/10/2026 | Phase 2 (tick/payroll) | 309 | 989 |
| 12/10/2026 | Phase 3 ADM-04 | 341 | 1180 |
| 12/10/2026 | TCH-07 thư viện | 348 | 1214 |
| 12/10/2026 | P3 view-only + TCH-06 | 348 | 1214 |
| 12/10/2026 | P4 cron session_generate | **355** | **1261** |

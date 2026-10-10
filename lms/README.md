# LMS MasterStudy — thư mục `lms/`

Portal LMS (Admin / Giáo viên / Học viên) — **tách biệt hoàn toàn** khỏi e-commerce
trong `admin/` (quyết định D2 + D3, plan `1791257567462-lms-masterstudy-php-extend.md`).

> **Nguyên tắc:** KHÔNG sửa/chỉnh file trong `admin/`, KHÔNG đụng bảng `db_*`
> e-commerce. Mọi bảng LMS dùng prefix `db_lms_`.

## Cấu trúc

```
lms/
  lib/
    lms_bootstrap.php   autoload Lms\ + load .env + connection global (lms_db())
    lms_db.php          data layer tối giản (LmsDb: safeFetch/safeExec/insert/update/txn)
    lms_auth.php        bcrypt + CSRF + rate-limit + remember-me + lms_check_permission
    lms_url.php         helper URL sạch (D10): lms_url() / lms_url_to() / lms_asset_url()
    lms_layout.php      header cam + sidebar từ db_lms_modules + footer (T3)
    lms_router.php      parse route, auth gate, permission, 404 (T4)
    lms_login.php       trang đăng nhập chung 3 portal (T5, D9)
    lms_notif_page.php  logic + AJAX endpoint chuông thông báo (T10)
    lms_notif_tpl.php   view chung trang thông báo (T10)
    lms_admin_forms.php helper chung form CRUD portal admin: guard, pagination, field error
    lms_upload.php       lms_save_files() upload an toàn (MIME whitelist, tên random)
                        + lms_upload_dir() tạo dir + .htaccess cấm execute PHP
  services/Lms/         business layer (PascalCase, namespace Lms\)
    LmsService.php      base: db(), errors(), audit()
    UserService.php     auth 3 portal (D9) + CRUD học viên (ADM-01) + giáo viên (ADM-02)
    ClassService.php    ADM-03 tạo lớp, enroll
    ScheduleService.php BR-SCHED-01 (chống trùng lịch GV) + lịch trống GV (TCH-01)
    SessionService.php  sinh sessions từ lịch (idempotent), hạn BTVN,
                        lesson summary + chấm BTVN (row-level theo teacher)
                        + listWeekForStudent / listUpcomingForStudent (STU-02)
    EnrolmentService.php  ADM-04 thêm/chuyển/xoá HV khỏi lớp
    TeacherProfileService.php  TCH-01 profile GV + ghi chú tuần
    RequestService.php    TCH-01 3 loại request (add_session/leave/change_schedule)
    StudentCourseService.php   STU-01/02/03 khoá học + nộp BTVN/test (row-level)
    MonthlyReviewService.php   STU-04 nhận xét tháng (chỉ published)
    NotificationService.php chuông thông báo (db_lms_notifications)
    TickService.php       BR-TICK-01/02/03 vi phạm (tick) + admin ADM-06
                          (closePastSessions, scanLate*, evaluateTickPenalty,
                          manual tick + CRUD ngưỡng)
  app/                  source + template theo portal (KHÔNG đặt lms/<portal>/ —
    admin/              trùng tên thư mục thật với URL /lms/admin → mod_dir's
    teacher/            DirectorySlash sinh 301 dư; xem note trong .htaccess)
    student/
  admin.php             entry point portal Admin (T5)
  teacher.php           entry point portal Giáo viên (T5)
  student.php           entry point portal Học viên (T5)
  index.php             trang chọn vai trò (T7 + D8, công khai)
  public/               asset tĩnh: vendor (bootstrap/jquery/fontawesome6/toastify)
                         + css/lms.css + js/lms.js + js/lms-notif.js (T2)
```

### Module đã xây dựng (portal Admin)

| Alias | FR | Action | Mô tả |
|---|---|---|---|
| `lms-tong-quan` | — | `hienthi` | Dashboard: 4 thẻ số liệu + thống kê chương trình |
| `lms-hoc-vien` | ADM-01 | `man` `them` `sua` `khoa` `mo` `xoa` | CRUD học viên + khoá/mở tài khoản; filter + phân trang |
| `lms-giao-vien` | ADM-02 | `man` `them` `sua` `khoa` `mo` `xoa` | CRUD giáo viên (CCCD/MST/% phí DV, môn/quận dạy, cam kết 08) |
| `lms-lop` | ADM-03 | `man` `them` `sua` `xoa` | CRUD lớp (tạo lớp + enroll + sinh sessions tự động) |
| `lms-lop-hoc-sinh` | ADM-04 | `man` `them` `chuyen` `xoa` | Ma trận học viên × chương trình; thêm/chuyển/xoá HV khỏi lớp |
| `lms-yeu-cau` | ADM-05 | `man` `xem` `duyet` `tu-choi` | Duyệt/từ chối request của GV (add_session/request_leave/change_schedule): transaction + `FOR UPDATE` idempotent, check trùng lịch GV, insert/cancel session, giảm `remaining_sessions` |
| `lms-vi-pham` | ADM-06 | `man` `them` `xoa` `nguong` `luu-nguong` `xoa-nguong` | Quản lý vi phạm (tick): log tick + filter GV/lý do/tháng/nguồn + phân trang, thêm tick thủ công (±1), xoá tick thủ công, CRUD ngưỡng `violation_thresholds` |
| `lms-thong-bao` | SYS-01 | `hienthi` `unread` `mark_read` `mark_all_read` | Thông báo (module ẩn, AJAX có CSRF) |

Mọi action `them`/`sua`/`khoa`/`xoa` check `lms_check_permission(..., 'edit'|'delete', 'admin')`
trước khi xử lý; POST đều có `lms_csrf_verify()`; ghi `db_lms_audit_logs` (create/update/lock).

### Module đã xây dựng (portal Giáo viên)

| Alias | FR | Action | Mô tả |
|---|---|---|---|
| `lms-tong-quan` | TCH-00 | `hienthi` | Dashboard giáo viên |
| `lms-lich-day` | TCH-01/02 | `man` `luu-thong-tin` `them-slot` `xoa-slot` `buoi` `luu-buoi` `cham-btvn` `gui-yeu-cau` | Quản lý lịch: calendar tuần (7 cột × khung giờ, 5 màu ô), form thông tin cá nhân + ghi chú tuần, đăng ký lịch trống, 3 loại request (thêm buổi / xin nghỉ / đổi lịch), lesson summary + giao BTVN (upload file), chấm BTVN |
| `lms-hoc-vien-cua-toi` | TCH-03 | `man` | Danh sách học viên giáo viên đang dạy |
| `lms-thong-bao` | SYS-01 | `hienthi` `unread` `mark_read` `mark_all_read` | Thông báo (module ẩn, AJAX có CSRF) |

Row-level: service lọc `teacher_id = user id` (guide §3.5); GV chỉ thấy/sửa buổi của chính mình.
Module không insert session trực tiếp — GV tạo `db_lms_requests` (`pending`), admin duyệt
ở ADM-05 (`lms-yeu-cau`) mới insert session (BR-SCHED-02).

### Module đã xây dựng (portal Học viên)

| Alias | FR | Action | Mô tả |
|---|---|---|---|
| `lms-tong-quan` | — | `hienthi` | Dashboard học viên |
| `lms-khoa-hoc` | STU-01/02/03 | `hienthi` `lop` `chuyen-de` `nop-btvn` `nop-test` | Các khoá học: lộ trình chuyên đề + nội dung buổi học + nộp BTVN/test (upload ≤4 file, on_time/late) |
| `lms-lich-hoc` | STU-02 | `hienthi` `buoi` | Lịch học calendar tuần (3 màu: sắp tới/đã hoàn thành/đã nghỉ) + chi tiết buổi + nộp BTVN |
| `lms-nhan-xet-thang` | STU-04 | `hienthi` | Nhận xét tháng theo lớp (collapse card + filter tháng, chỉ xem đã published) |
| `lms-noi-quy` | — | `hienthi` | Nội quy lớp học (read-only, placeholder chờ module admin Phase 3) |
| `lms-thong-bao` | SYS-01 | `hienthi` `unread` `mark_read` `mark_all_read` | Thông báo (module ẩn, AJAX có CSRF) |

Row-level: service lọc `student_id = user id` qua `db_lms_class_students` (status `active`)
(guide §3.5) — HV chỉ thấy lớp/session/test/nhận xét của mình. Student role `can_edit = 0`
nên các POST nộp bài chỉ cần `lms_csrf_verify()` + row-level service, KHÔNG dùng
`lms_form_guard(..., 'edit')`. Nộp trễ vẫn được (label đỏ); session tương lai khóa nộp BTVN;
test/minitest phải `status='published'` (minitest draft → "chưa được admin duyệt").

URL (D10 — clean URL, PHP không bao giờ viết tay link, dùng `lms_url()`):

```
/lms/                               trang chọn vai trò
/lms/<portal>                       dashboard (chưa login → chuyển login)
/lms/<portal>/login | /logout       action thuần
/lms/<portal>/<alias>[/<action>]    module (alias từ db_lms_modules)
/lms/public/...                     asset tĩnh
```

## Data layer — tại sao không dùng `func_index`

`func_index` (`admin/lib/class.php`, 979 dòng) là God class gắn chặt e-commerce
(danh mục, menu, địa chỉ, prefix `#_`, API stateful `setTable/setWhere/delete`).
LMS copy **bản tối giản** (`LmsDb`) để:
- không kéo phụ thuộc e-commerce vào LMS (D2);
- giữ API quen thuộc: `safeFetch` / `safeFetchOne` / `safeExec` / `insert` / `update`
  + thêm `count()` + transaction tường minh;
- charset `utf8mb4`, `PDO::ERRMODE_EXCEPTION`, bind tham số mọi nơi.

```php
$db = lms_db();                                    // hoặc new LmsDb($config)
$rows = $db->safeFetch('select * from db_lms_users where role = ?', array('teacher'));
$db->insert('db_lms_notifications', array('user_id' => 1, 'type' => 'system'));
```

## Phân quyền

Theo `Tai-lieu-phat-trien/Huong-Dan-Phat-Trien-Page.md` (mirror cơ chế
`db_module_admin` + `checkPermission` của e-commerce, nhưng gán theo **role**):

- `db_lms_modules` — module theo portal (admin 14 / teacher 6 / student 5).
- `db_lms_role_permissions` — `can_view` / `can_edit` / `can_delete` theo role.
- `lms_check_permission($db, $user, $alias, $action, $portal)` — sai portal →
  luôn false (D9); `super_admin` bypass (portal admin).

```php
if (!lms_check_permission($db, $user, 'lms-hoc-vien', 'edit', 'admin')) {
    /* 403 */
}
```

Phân quyền **cấp dòng** (GV chỉ thấy vi phạm của mình) làm trong service, không
phải trong `lms_check_permission` (xem guide §3.5).

## Quy ước code

- **PHP 7.3**: không `fn()`, `??=`, `str_contains()`, `?->`, named args.
- Lint: `C:\laragon\bin\php\php-7.3.33-nts-Win32-VC15-x64\php.exe -l <file>`.
- Mọi query bind tham số; mọi output HTML escape `e()`.
- Mỗi file source có guard `!defined('_source')` (Phase 1.4).
- Template hậu tố `_tpl.php`.
- Mọi POST có CSRF (`lms_csrf_verify()`).

## Test + seed

```bash
# PHPUnit (dùng DB test phuongnamv_db_lms_test — KHÔNG đụng DB thật)
php vendor/phpunit/phpunit/phpunit

# Migration (forward-only; chạy trên cả DB thật + DB test khi áp dụng schema mới)
php database/migrations/<file>.sql   # qua runner tạm hoặc client MySQL

# Seed module/permission (chạy 1 lần)
php database/seeds/... (xem file SQL trong database/seeds/)

# Seed dữ liệu demo (idempotent; mặc định phuongnamv_db)
php database/seeds/2026_10_07_lms_demo_seed.php
php database/seeds/2026_10_07_lms_demo_seed.php phuongnamv_db_lms_test
```

### Cron quét vi phạm (BR-TICK-01/02/03)

```bash
# Dry-run (không ghi DB; xem trước số buổi/tick sẽ xử lý)
php database/cron/lms_tick_cron.php --dry-run
php database/cron/lms_tick_cron.php --dry-run --db=phuongnamv_db_lms_test

# Chạy thật (ghi tick + thông báo; idempotent — chạy lại không tạo trùng)
php database/cron/lms_tick_cron.php
```

Lập lịch **mỗi 15 phút** (SRS §3.1): Windows Task Scheduler hoặc crontab:

```
*/15 * * * * php /path/to/source/database/cron/lms_tick_cron.php >> /var/log/lms_tick.log 2>&1
```

Pipeline (theo thứ tự): `closePastSessions` (đóng buổi quá hạn → `finished`) →
`scanLateLessonSummary` (BR-TICK-01) → `scanLateHwGrading` (BR-TICK-02, 1 tick/session) →
`evaluateTickPenalty` (BR-TICK-03: `tick_warning` idempotent 1 lần/tháng/GV;
demote phí chỉ audit + cảnh báo — PayrollService áp dụng ở phase sau).

⚠️ **Timezone**: code dùng `date('Y-m-d H:i:s')` theo PHP default. Cài đặt
`date.timezone = Asia/Ho_Chi_Minh` (php.ini) **và** MySQL server cùng múi giờ
thì hạn phạt mới đúng giờ VN. Chạy `php -i | grep date.timezone` để kiểm tra.

⚠️ Tick do cron tạo (`auto_generated = 1`) không xoá qua UI — admin dùng miễn
giảm thủ công (`manual_exempt`, -1) để bù trừ (giữ lịch sử đối soát lương).

Migration hiện có: `2026_10_07_lms_core.sql` (25 bảng) →
`2026_10_09_lms_teacher_profile_slots_notes.sql` (TCH-01/02: `teachers.current_address`,
`sessions.topic_ids`, `db_lms_teacher_free_slots`, `db_lms_teacher_notes`).

Seed ngưỡng vi phạm: `database/seeds/2026_10_10_lms_violation_thresholds_seed.sql`
(3 khoảng số lớp 1-3/4-7/≥8 → miễn 2/3/4 tick, cảnh báo 5, hạ phí 8, % phí sau
hạ 50 — chạy trên cả DB thật + DB test; `ticks_to_warn` và
`fee_percentage_after_demote` là giá trị tạm, cần chốt với business).

Tài khoản demo sau khi seed: `admin/admin123`, `gv01/gv01123456`, `hv01/hv01123456`…
(chi tiết xem đầu ra script seed).

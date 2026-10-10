# QUY TRÌNH PHÁT TRIỂN HỆ THỐNG VÀ BỘ TÀI LIỆU PHÂN TÍCH

**Dự án áp dụng:** LMS MasterStudy — Trung tâm Ngoại ngữ AROMA
**Phiên bản:** 3.0 (bản sạch, áp dụng stack PHP/MySQL trên source hiện tại)

> [!IMPORTANT]
> **Cấu trúc của tài liệu này**
> - **Phần 1** trình bày quy trình chuẩn (SDLC) áp dụng cho MasterStudy: từ khảo sát yêu cầu
>   đến đào tạo và bảo trì.
> - **Phần 2** liệt kê đầy đủ các thành phần cần có trong một bộ tài liệu phân tích hệ thống
>   chuyên nghiệp.
> - **Phần 3–5** chứa RACI, đánh giá rủi ro và trạng thái bộ tài liệu hiện tại.
>
> **Bối cảnh kỹ thuật:** LMS MasterStudy được xây dựng **mở rộng trên bộ source PHP/MySQL
> e-commerce hiện tại** (không xây greenfield). Toàn bộ LMS nằm trong thư mục `lms/` với
> **3 portal đối xứng** (admin / teacher / student); phần e-commerce hiện tại giữ nguyên,
> không đụng. Toàn bộ nội dung dưới đây đã được điều chỉnh cho phù hợp với thực tế này.
> Nội dung nghiệp vụ (FR, Business Rules, RBAC, ERD, mockup) nằm trong `SRS_MasterStudy.md`
> và `ERD_MasterStudy.md`.

---

## PHẦN 1: QUY TRÌNH PHÁT TRIỂN HỆ THỐNG CHI TIẾT (SDLC)

Mô hình áp dụng cho MasterStudy: **Hybrid (Agile Scrum + Waterfall cho phase đầu)**.

### Bước 1: Khảo sát và Thu thập Yêu cầu (Requirement Gathering) — Tuần 1–2

- **Mục tiêu:** Hiểu rõ hệ thống cần làm gì, giải quyết vấn đề gì cho người dùng cuối.
- **Hoạt động:**
  - Họp với stakeholders: Ban Giám đốc AROMA, Admin trung tâm, 5–10 GV đại diện, 10–20 PH/HV.
  - Khảo sát quy trình As-is: GV đang quản lý lịch + tick bằng Excel/Google Sheet; PH theo dõi con qua Zalo nhóm.
  - Xác định FR (18 yêu cầu chức năng) và NFR (7 nhóm yêu cầu phi chức năng) — xem `SRS_MasterStudy.md`.
- **Đầu ra:** BRD (Business Requirements Document), User Stories, Stakeholder Map.
- **RACI:** R=BA, A=PM, C=Tech Lead, I=Stakeholders.

### Bước 2: Phân tích và Thiết kế Hệ thống (System Analysis & Design) — Tuần 3–4

- **Mục tiêu:** Định hình kiến trúc, luồng dữ liệu và giao diện của hệ thống.
- **Hoạt động:**
  - **Phân tích:** Lập Use Case Diagram, Activity Diagram cho từng FR (18 Use Case).
  - **Thiết kế Database:** ERD 22 bảng + 8 bảng bổ sung — xem `ERD_MasterStudy.md` (đã adapt MySQL, prefix `db_lms_`).
  - **Thiết kế Kiến trúc (đã chốt):**

    Toàn bộ LMS nằm gọn trong thư mục `lms/` với **3 portal đối xứng** (admin / teacher /
    student). Thư mục `admin/` e-commerce **nguyên vẹn, không đụng**.

    | Thành phần | Công nghệ áp dụng |
    |---|---|
    | Ngôn ngữ / Backend | PHP (server-rendered), class `func_index` (PDO, bind tham số) |
    | Portal Admin LMS | `lms/admin.php` + `lms/admin/` (AdminLTE 4 + Bootstrap 5 + jQuery + DataTables + Select2) |
    | Portal GV | `lms/teacher.php` + `lms/teacher/` (layout riêng, header cam + sidebar) |
    | Portal HV | `lms/student.php` + `lms/student/` (layout riêng, header cam + sidebar) |
    | Lib chung 3 portal | `lms/lib/`: `lms_auth.php` (bcrypt + rate-limit + remember-me), `lms_router.php`, `lms_layout.php` |
    | Database | MySQL (`phuongnamv_db`), schema LMS riêng prefix `db_lms_`, 3 portal dùng chung `db_lms_users` (role ENUM admin/teacher/student) |
    | Storage file | Upload local qua CKFinder (chưa cần S3) |
    | Search | MySQL FULLTEXT (chưa cần Elasticsearch) |
    | Cronjob | Lịch máy chủ (crontab / Task Scheduler) gọi PHP CLI |
    | Monitoring | `error_log` PHP + bảng `db_lms_cron_runs` + cảnh báo email |
    | URL portal | Clean URL, không `.php`: `/lms/<portal>/<alias>/<action>` qua `.htaccess` (D10); router parse cả dạng cũ `?p=&a=` |

    > `admin/` và `db_user` chỉ phục vụ e-commerce (D2). Tài khoản LMS và tài khoản
    > e-commerce hoàn toàn tách biệt — giảm rủi ro phá nghiệp vụ đang chạy (R9).

  - **Thiết kế UI/UX:** 3 bộ mockup Admin/Teacher/Student đã có sẵn (PNG + mô tả trong `Tài liệu admin/teacher/student.md`).
  - **API Contract:** danh sách endpoint AJAX nội bộ (admin + portal), tài liệu hóa trong README của từng module.
- **Đầu ra:** SRS, ERD, danh sách endpoint, mockup, sơ đồ kiến trúc.
- **RACI:** R=SA/BA, A=Tech Lead, C=Dev Lead, I=PM.

### Bước 3: Lập trình (Implementation) — Tuần 5–16

**Tech stack đã chốt (PHP/MySQL):**

- PHP + MySQL (PDO qua class `func_index`: `safeFetch` / `safeExec` bind tham số).
- 3 portal LMS đối xứng trong `lms/` (admin / teacher / student), chia sẻ `lms/lib/` (auth, router, layout).
- Admin LMS: AdminLTE 4 + Bootstrap 5 + jQuery + DataTables + Select2.
- Portal GV/HV: PHP server-rendered, layout riêng theo mockup (header cam + sidebar).
- Cron: PHP CLI chạy bằng crontab / Task Scheduler.
- Test: PHPUnit cho logic tiền bạc (tick / lương / thuế); UI test thủ công theo mockup.
- Quản lý phiên bản: git.

**Kế hoạch triển khai theo phase** (thay cho sprint plan greenfield cũ):

| Phase | Scope chính |
|---|---|
| Phase 0 — Nền tảng | Khởi tạo git; kiểm tra version MySQL/PHP; tạo schema `db_lms_*`; service layer skeleton; cài PHPUnit; seed dữ liệu mẫu |
| Phase 1 — Lõi vận hành (MVP 1) | ADM-01/02 (tài khoản HV/GV), ADM-03 (lớp + lịch + sinh sessions), portal GV/HV + auth 3 role, TCH-01/02 (lịch dạy, lesson summary), STU-01/02/03 (lộ trình, lịch học, nộp BTVN) |
| Phase 2 — Tài chính (MVP 2) | ADM-06 (tick + thresholds), cron quét tick, BR-TICK-03 hạ phí dịch vụ, `PayrollService`/`TaxService`, ADM-07 thống kê phí DV + xuất Excel |
| Phase 3 — Phụ | ADM-04 (sắp xếp lớp), ADM-05 (requests), test/minitest, thư viện, nhận xét tháng, nội quy, thông báo |
| Tương lai | Parent portal (PAR-01..04), tìm kiếm toàn cục nâng cao, i18n |

> Chi tiết từng task trong file kế hoạch `1791257567462-lms-masterstudy-php-extend.md` (mục §5.2, §8).

**Definition of Done (DoD) cho mỗi User Story:**

- [ ] Code đã review bởi ≥ 1 dev khác (PR approved).
- [ ] PHPUnit pass cho mọi service tiền bạc (`PayrollService` / `TaxService` / `TickService`).
- [ ] Test service layer + DB thật (bản copy `phuongnamv_db`).
- [ ] UI khớp mockup PNG trong `Tai-lieu-phat-trien/`.
- [ ] Responsive test trên Chrome DevTools (mobile / tablet / desktop).
- [ ] Không có bug Severity 1/2 mở.
- [ ] Endpoint AJAX có comment + mô tả trong README module.
- [ ] Audit log đã ghi (cho thao tác tài chính / tick).
- [ ] `php -l` không lỗi mọi file mới/sửa; mọi POST có CSRF; output escape (`e()`).
- [ ] Query dùng `safeFetch` / `safeExec` (không nối chuỗi dữ liệu user).
- [ ] PM đã demo và accept.

**Đầu ra:** MVP/Beta deploy lên staging, demo cho stakeholders cuối Phase 2.

### Bước 4: Kiểm thử (Testing / QA & QC) — Song song Phase 1–2 + 2 tuần chuyên sâu

- **Mục tiêu:** Đảm bảo hệ thống hoạt động đúng theo SRS, không có bug nghiêm trọng.
- **Hoạt động:**
  - **Unit Testing (PHPUnit):** Test business logic cốt lõi (tính tick, tính lương, tính thuế, validate form). Bắt buộc **pass 100%** cho `PayrollService` / `TaxService` / `TickService` (rủi ro R5 rất cao).
  - **Integration Testing (PHPUnit + DB test):** Test service layer + DB thật. Đặc biệt test luồng: nộp BTVN → cronjob quét → tạo tick → tính lương cuối tháng.
  - **UI Testing (thủ công):** Checklist test 3 role, so từng trang với mockup PNG.
  - **Security Review (thủ công):** Query bind tham số, CSRF mọi POST, escape output, validate MIME khi upload (codebase đã được gia cố bảo mật).
  - **UAT:** 5 Admin + 20 GV + 50 HS thử nghiệm trên staging 1 tuần.
- **Test Plan riêng** (xem `Test_Plan_MasterStudy.md` sẽ tạo ở Phase 2).
- **Đầu ra:** Bug-free ở mức cho phép, biên bản UAT có chữ ký stakeholders.
- **RACI:** R=QA Lead, A=PM, C=Dev, I=Stakeholders.

### Bước 5: Triển khai (Deployment) — Tuần 19

- **Mục tiêu:** Đưa hệ thống lên Production.
- **Hoạt động:**
  - Đẩy source lên hosting PHP/MySQL (shared hosting hoặc VPS).
  - Import các file `database/migrations/*.sql` theo thứ tự thời gian.
  - Bật rule HTTP→HTTPS trong `.htaccess` khi hosting đã có SSL.
  - Cấu hình cron: `cron/tick_scan.php` mỗi 15 phút, `cron/session_generate.php` hàng ngày, `cron/payroll_close.php` ngày 1 hàng tháng.
  - Seed dữ liệu mẫu (chuyển đổi xanh, không ETL dữ liệu cũ).
  - Smoke test trên production trước khi mở cổng (3 role LMS + smoke test e-commerce song song).
  - **Rollback plan:** backup source + DB dump trước mỗi đợt deploy; rollback = giải nén bản cũ + phục hồi DB.
  - **Monitoring tối thiểu:** `error_log` PHP + bảng `db_lms_cron_runs` cảnh báo email nếu cron chết > 30 phút.
- **CI/CD:** chưa áp dụng trong MVP, deploy thủ công. Nếu sau này cần mới thiết lập CI/CD.
- **Đầu ra:** Hệ thống chạy production, runbook xử lý sự cố, cron đã lên lịch và ghi log.
- **RACI:** R=DevOps, A=Tech Lead, C=PM, I=All team.

### Bước 6: Đào tạo và Bảo trì (Training & Maintenance) — Tuần 20+

- **Mục tiêu:** Hướng dẫn sử dụng và duy trì hệ thống ổn định.
- **Hoạt động:**
  - **Đào tạo:**
    - Admin: Workshop 2 ngày tại trung tâm, kèm video hướng dẫn.
    - Teacher: Video tutorial 30 phút + FAQ + hotline hỗ trợ.
    - Student/Parent: Email onboarding + video ngắn 5 phút.
  - **Bảo trì:**
    - SLA: Response < 1h cho bug Severity 1, fix < 4h. Severity 2: response < 4h, fix < 24h.
    - Incident response: On-call rotation 24/7 tháng đầu.
    - Backup verification hàng tuần, DR drill mỗi quý.
    - Cập nhật phụ thuộc: `composer audit` (PHP) hàng tháng + rà `package.json` admin hàng quý.
- **Maintenance window:** Chủ nhật 2h–4h sáng.
- **RACI:** R=Support team, A=PM, C=Dev team, I=All users.

---

## PHẦN 2: BỘ TÀI LIỆU PHÂN TÍCH HỆ THỐNG BAO GỒM NHỮNG GÌ?

Một bộ tài liệu phân tích hệ chuẩn mực và chuyên nghiệp (thường do Business Analyst - BA và System Analyst - SA thực hiện) sẽ bao gồm các thành phần sau:

### 1. BRD (Business Requirements Document) — Tài liệu Yêu cầu Nghiệp vụ

Đây là tài liệu ở góc độ "Business", trả lời cho câu hỏi: *Hệ thống này sinh ra để mang lại giá trị kinh doanh gì?*

- Mục tiêu dự án.
- Mô tả quy trình nghiệp vụ hiện tại (As-Is) và quy trình tương lai khi có hệ thống (To-Be).
- Các đối tượng sử dụng hệ thống (Stakeholders/Actors).
- ROI dự kiến.

### 2. SRS (Software Requirements Specification) — Tài liệu Đặc tả Yêu cầu Phần mềm

Đây là tài liệu chi tiết nhất, là kim chỉ nam cho Dev và Tester làm việc. Bao gồm:

- **Mô tả chức năng (Functional Requirements):** Mô tả chi tiết từng nút bấm, từng form (VD: Form tạo lớp học cần những trường gì, trường nào bắt buộc, logic bắt lỗi ra sao).
- **Yêu cầu phi chức năng (Non-Functional Requirements):**
  - *Hiệu năng (Performance):* Hệ thống phản hồi dưới 2 giây.
  - *Bảo mật (Security):* Mã hóa mật khẩu, phân quyền.
  - *Khả năng mở rộng (Scalability):* Số lượng user dự kiến.
- **Quy tắc nghiệp vụ (Business Rules):** VD: Sau 12h ngày kế tiếp, hệ thống tự khóa nộp BTVN và cộng 1 tick phạt.
- **Ma trận phân quyền (RBAC):** Ai được làm gì trên resource nào.
- **Glossary:** Thuật ngữ chuyên ngành.
- **Ma trận truy vết (Traceability Matrix):** FR ↔ Use Case ↔ Test Case.

### 3. Tài liệu Thiết kế Hệ thống (System Design Documents)

Thường do Tech Lead hoặc System Architect đảm nhận:

- **Sơ đồ Use Case (Use Case Diagram):** Vẽ bằng UML, thể hiện Actor (Ai) làm được hành động gì trên hệ thống.
- **Sơ đồ ERD (Entity Relationship Diagram):** Thiết kế Cơ sở dữ liệu, các bảng (Tables) và mối quan hệ (1-1, 1-n, n-n).
- **Sơ đồ Hoạt động (Activity Diagram) / Sơ đồ Luồng (Flowchart):** Mô tả luồng đi của một nghiệp vụ (VD: Luồng xin nghỉ học → Admin duyệt → Trừ số buổi).
- **Sơ đồ Trạng thái (State Diagram):** Cho các entity có nhiều trạng thái (Session: upcoming → finished → cancelled; Request: pending → approved/rejected).
- **Sơ đồ Kiến trúc (Architecture Diagram):** Frontend, Backend, DB, Storage, Cron.
- **Tài liệu API (API Specification):** Danh sách các endpoint AJAX nội bộ giữa admin/portal và service layer.

### 4. Tài liệu UI/UX (Thiết kế Giao diện)

- **Wireframe:** Bản vẽ nháp bố cục các nút, khung chữ (thường trắng đen).
- **Mockup (UI Design):** Bản thiết kế có màu sắc, logo, hình ảnh thực tế.
- **Prototype:** Bản Mockup có thể click qua lại để xem luồng chuyển trang (Navigation).
- **Design System:** Component library, color palette, typography, icon set (cho dự án lớn).

### 5. Test Plan & Test Cases (Tài liệu Kiểm thử)

Do bộ phận QA/QC (Kiểm thử viên) lập ra dựa trên SRS:

- **Test Plan:** Kế hoạch kiểm thử (sẽ test trên môi trường nào, dùng công cụ gì, ai test, timeline).
- **Test Cases:** Danh sách các kịch bản kiểm thử chi tiết.
  - *VD:* Kịch bản 1: Nhập sai mật khẩu → Kết quả mong đợi: Báo lỗi đỏ "Mật khẩu không đúng".
- **Test Data:** Dữ liệu mẫu để test (script seed SQL).

### 6. User Manual (Tài liệu Hướng dẫn sử dụng)

Cung cấp cho người dùng cuối (Admin, Teacher, Student) để họ biết cách thao tác trên phần mềm. Có thể kèm video hướng dẫn hoặc hình ảnh chụp màn hình từng bước.

### 7. Tài liệu Vận hành (Operations Documentation) — BỔ SUNG

- **Runbook:** Hướng dẫn xử lý sự cố thường gặp (DB chết, cronjob lỗi, storage đầy).
- **Deployment Guide:** Cách deploy lên môi trường mới.
- **Backup & Recovery:** Quy trình backup, restore, DR.
- **Monitoring:** Cách xem `db_lms_cron_runs`, `error_log`, rule cảnh báo email.

---

## PHẦN 3: MA TRẬN RACI DỰ ÁN MASTERSTUDY

| Hoạt động | PM | BA | SA/Tech Lead | Dev | QA | DevOps | Stakeholder |
|---|---|---|---|---|---|---|---|
| Thu thập yêu cầu | A | R | C | I | I | I | C |
| Phân tích & thiết kế | A | R | R | C | C | I | I |
| Lập trình Backend | A | I | C | R | I | I | - |
| Lập trình Frontend | A | I | C | R | I | I | C (UI/UX) |
| Code Review | A | - | R | R | C | - | - |
| Unit Test | A | - | C | R | C | - | - |
| Integration Test | A | - | C | C | R | - | - |
| UI Test (manual) | A | - | I | C | R | I | C |
| UAT | A | C | I | I | C | I | R |
| Deploy Production | A | - | C | I | I | R | I |
| Vận hành & bảo trì | A | I | C | R | R | R | I |

R=Responsible (Thực hiện), A=Accountable (Chịu trách nhiệm chính), C=Consulted (Tham vấn), I=Informed (Thông báo).

---

## PHẦN 4: ĐÁNH GIÁ RỦI RO & GIẢM THIỂU

| # | Rủi ro | Xác suất | Tác động | Giảm thiểu |
|---|---|---|---|---|
| R1 | Cronjob quét tick bị miss hoặc chạy trùng → duplicate tick | Trung bình | Cao | DB UNIQUE constraint `(teacher_id, session_id, reason, DATE(violation_date))` + check-before-insert + ghi `db_lms_cron_runs` |
| R2 | File BTVN nhiễm malware | Thấp | Cao | Validate MIME + giới hạn extension qua CKFinder |
| R3 | Calendar load chậm khi số session lớn | Cao | Trung bình | Composite index `(teacher_id, session_date)` + lazy load theo tháng |
| R4 | GV hiểu nhầm luật tick → khiếu nại | Trung bình | Trung bình | UI hiển thị rõ rule + tooltip giải thích + email xác nhận khi bị tick |
| R5 | Tính lương sai → mất uy tín với GV | Thấp | Rất cao | PHPUnit 100% cho `PayrollService`/`TaxService`/`TickService` + review thủ công hàng tháng |
| R6 | Thay đổi luật thuế TNCN | Thấp | Cao | Config `is_commit_08` ở DB, không hardcode |
| R7 | Số lượng PH đăng ký đột biến khi khai giảng | Trung bình | Trung bình | Rate-limit login/đăng ký đã có sẵn |
| R8 | Cron chết không ai biết | Trung bình | Cao | Bảng `db_lms_cron_runs` ghi log mỗi lần chạy + cảnh báo email nếu quá 30 phút không có run mới |
| R9 | Phá nghiệp vụ e-commerce đang chạy khi thêm LMS | Trung bình | Cao | Schema LMS dùng prefix `db_lms_` tách biệt; smoke test giỏ hàng/đơn hàng sau mỗi phase |

---

## PHẦN 5: TRẠNG THÁI BỘ TÀI LIỆU

> [!TIP]
> **Bộ tài liệu hiện tại đã có:**
> - `SRS_MasterStudy.md` — 18 FR + NFR + RBAC + Traceability
> - `ERD_MasterStudy.md` — 22 bảng + 8 bảng bổ sung + index (đã adapt MySQL)
> - `Quy_Trinh_Phat_Trien.md` — SDLC + RACI + Risk + phase plan
> - `Tài liệu admin.md` / `Tài liệu teacher.md` / `Tài liệu student.md` — Mô tả giao diện chi tiết (BRD + Mockup)
> - `1791257567462-lms-masterstudy-php-extend.md` — Kế hoạch triển khai chi tiết trên source PHP/MySQL hiện tại
>
> **Cần bổ sung tiếp để hoàn thiện:**
> - Use Case Diagram (18 UC) + Activity Diagram cho các luồng phức tạp (tính lương, xin nghỉ, sắp xếp lớp).
> - State Diagram cho Session, Request, Violation, Class.
> - Sơ đồ kiến trúc thực tế (front-controller admin + portal entry points + cron CLI).
> - Test Plan + Test Cases (cho PHPUnit + checklist manual UI).
> - User Manual cho 3 roles + 1 role Parent (tương lai).
> - Runbook (xử lý khi cron chết, DB đầy, migration lỗi) + cảnh báo email `cron_runs`.

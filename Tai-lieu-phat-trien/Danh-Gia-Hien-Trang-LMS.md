# BÁO CÁO ĐÁNH GIÁ HIỆN TRẠNG & ĐỐI CHIẾU THIẾT KẾ HỆ THỐNG LMS MASTERSTUDY

> **Ngày lập báo cáo ban đầu:** 10/10/2026
> **Cập nhật & Kiểm toán toàn diện lần 4:** 17/10/2026
> **Dự án:** LMS MasterStudy — Trung tâm Ngoại ngữ AROMA  
> **Phạm vi kiểm toán:** Đối chiếu chéo giữa **Bộ tài liệu đặc tả** (`SRS_MasterStudy.md`, `Quy_Trinh_Phat_Trien.md`, `ERD_MasterStudy.md`, `Huong-Dan-Phat-Trien-Page.md`), **Bộ ảnh chụp Mockup PNG** (thư mục `admin/`, `teacher/`, `student/`) và **Mã nguồn thực tế** trong `C:\laragon\www\source\lms`.

---

## 1. TỔNG QUAN TIẾN ĐỘ THỰC HIỆN (CẬP NHẬT 14/10/2026)

Hệ thống LMS được quy hoạch nằm gọn trong thư mục `lms/` với **3 Portal đối xứng** (`admin`, `teacher`, `student`), dùng chung cơ sở dữ liệu `phuongnamv_db` nhưng phân vùng bảng riêng (`db_lms_*`), không can thiệp vào mã nguồn hay database của web e-commerce cũ.

Sau đợt rà soát từ ngày 10/10 đến 17/10/2026, full PHPUnit pass **392 tests / 1.423 assertions**. Đã sửa fixture `EnrolmentServiceTest` bị trùng fixed session slot. Code cho cycle/unlock, Teacher course detail, Admin session detail/chấm BTVN, Dashboard arranging và matching filters đã có. DB local có hai môn nhưng không có topic rows nên cycle topic/unlock chưa được xác nhận bằng dữ liệu nghiệp vụ. SMTP sandbox SYS-03 chưa được smoke test.

### 1.1. Bảng tiến độ triển khai theo Portal

| Phân hệ (Portal) | Tổng module menu đăng ký | Đã có Controller & Template | Chưa có Code (Trang 404) | Tỷ lệ hoàn thành chức năng |
|---|:---:|:---:|:---:|:---:|
| **Admin LMS** | 14 module menu | **16** (14 module menu + `lms-audit-log` + `lms-thong-bao`) | **0** | **100%** |
| **Teacher LMS** | 6 module menu (+1 phụ) | **8** (7 menu + `lms-thong-bao`) | **0** | **100%** |
| **Student LMS** | 5 module menu | **6** (5 menu + `lms-thong-bao`) | **0** | **100%** |
| **Hạ tầng chung (Core & Ops)** | Auth, Layout, Router, Search, Crons | **Đã hoàn thành toàn bộ** | **0** | **100%** |

Ghi chú: tỷ lệ 100% ở bảng này chỉ tính module menu đã có source/template; các gap UI/mockup ngoài menu được liệt kê riêng tại §3.

---

### 1.2. Danh sách các chức năng ĐÃ HOÀN THÀNH TOÀN DIỆN

#### A. Hạ tầng cốt lõi (Core Framework & Security)
- **Tầng dữ liệu an toàn (`lms/lib/lms_db.php`):** Tách độc lập khỏi `func_index` cũ. Dùng PDO chuẩn, 100% prepared statements (bind params), mã hóa `utf8mb4`, hỗ trợ transaction tường minh.
- **Xác thực & Bảo mật (`lms/lib/lms_auth.php`, `lms/lib/lms_login.php`, `lms/lib/lms_change_password.php`):**
  - Mật khẩu mã hóa Bcrypt.
  - CSRF token bắt buộc cho mọi request POST.
  - Rate-limit chống brute force (5 lần sai / 15 phút).
  - Cơ chế xoay Cookie Remember-me token an toàn.
  - **BR-AUTH-01:** Mật khẩu để trống khi tạo mới sẽ tự sinh 10 ký tự ngẫu nhiên; cờ `must_change_password = 1` ép người dùng phải đổi mật khẩu ở lần đăng nhập đầu tiên qua trang `doi-mat-khau`.
- **Phân vùng Portal nghiêm ngặt (Quy tắc D9):** Đăng nhập sai portal lập tức từ chối và hiển thị link chuyển tới đúng portal tương ứng, không tự động redirect.
- **Routing & URL sạch (Quy tắc D10):** Hỗ trợ URL dạng `/lms/<portal>/<alias>/<action>` không đuôi `.php` qua `.htaccess`.
- **Tìm kiếm toàn cục Header (`lms/lib/lms_search.php` - SYS-02):** Đã kết nối thanh tìm kiếm trên Header cam cho cả 3 Portal, tự động phân quyền tìm kiếm theo vai trò (Admin tìm được cả Học viên, Giáo viên, Lớp học; Giáo viên tìm được học viên lớp mình dạy).
- **Nhật ký kiểm toán (`lms-audit-log`):** Màn hình riêng cho Admin tra cứu lịch sử thao tác hệ thống, phân loại theo người thực hiện, hành động và ngày tháng.
- **Hệ thống Thông báo thời gian thực (`lms/lib/lms_notif_page.php`, `NotificationService.php`):** AJAX polling, popup chuông thông báo và trang lịch sử thông báo.

#### B. Cơ sở dữ liệu & Tầng nghiệp vụ (Service Layer)
- Đầy đủ các migration bảng nghiệp vụ prefix `db_lms_*` (30+ bảng).
- 19 service/adapter cụ thể tương thích chuẩn PHP 7.3 (20 class nếu tính cả `LmsService` base; trong đó 18 concrete services kế thừa base và `MailService` là adapter độc lập):
  - `UserService`: Auth 3 portal, CRUD học viên (ADM-01), CRUD giáo viên (ADM-02), reset mật khẩu.
  - `ClassService`: Tạo & cấu hình lớp học 1-1 / nhóm (ADM-03), gán chuyên đề (`db_lms_class_topics`), hạn chấm test (`test_grace_hours`), điều chỉnh số buổi.
  - `EnrolmentService`: Thêm/chuyển/rút học viên khỏi lớp (màn Học sinh - Lớp học, thuộc ADM-03).
  - `ScheduleService`: Chống trùng lịch giáo viên (`BR-SCHED-01`), quản lý lịch trống giáo viên (TCH-01).
  - `SessionService`: Tự động sinh buổi học từ lịch cố định, quản lý Lesson summary, chấm BTVN.
  - `StudentCourseService`: Xem lộ trình chuyên đề, nộp BTVN, nộp Test/Minitest (STU-01/02/03).
  - `MonthlyReviewService`: Nhận xét tháng (TCH-06, STU-04).
  - `TickService`: Vi phạm nội quy (ADM-06), tự động phạt tick theo rule `BR-TICK-01/02/03`, cảnh báo hạ phí.
  - `TaxService`: Tính thuế TNCN (BR-PAY-03), tự động miễn thuế với GV có Cam kết 08 hoặc thu nhập < 5 triệu.
  - `PayrollService`: Chốt lương GV (ADM-07, TCH-05, BR-PAY-01/02/04), áp dụng mức phí đã hạ do phạt tick, duyệt chi trả, xuất file CSV.
  - `MatchingService`: Thuật toán ghép lớp tự động cho học viên chờ xếp (ADM-04).
  - `LibraryService`: Quản lý kho tài liệu, phân quyền tải (TCH-07).
  - `AdminReportService`: Thống kê buổi học, xuất báo cáo CSV.
  - `ClassRulesService`: Quản trị nội quy trung tâm.
  - `NotificationService`: Chuông thông báo và lịch sử notification.
  - `RequestService`: Tạo và duyệt yêu cầu nghỉ/thêm buổi/đổi lịch.
  - `TeacherProfileService`: Hồ sơ, khu vực dạy và lịch trống giáo viên.
  - `PasswordResetService` và `MailService`: OTP đặt lại mật khẩu và adapter PHPMailer (SYS-03).

#### C. Hệ thống Tác vụ ngầm (Cronjobs Production)
Đã triển khai đầy đủ 3 kịch bản cron định kỳ trong `database/cron/` kèm script cài đặt tự động cho cả Windows Task Scheduler (`lms_install_scheduled_tasks.php`) và Linux Crontab (`lms_crontab.txt`):
1. `lms_tick_cron.php`: Chạy mỗi 15 phút. Tự động đóng buổi học quá khứ, quét phạt trễ Lesson summary (12:00 hôm sau), phạt trễ chấm BTVN (24h trước buổi kế tiếp), nhắc học viên trước buổi khoảng 1 giờ (cửa sổ 45–75 phút; trạng thái theo từng enrollment), ghi log vào `db_lms_cron_runs`.
2. `lms_session_generate.php`: Chạy hàng ngày lúc 01:00. Tự động sinh buổi học cho 4-8 tuần tiếp theo từ thời khóa biểu cố định.
3. `lms_payroll_cron.php`: Chạy ngày 1 hàng tháng lúc 00:15. Tự động tính tổng tiền dạy, tiền phạt tick, tiền thưởng, trừ thuế và chốt bảng lương cho giáo viên.

---

## 2. KẾT QUẢ KIỂM TRA CÁC MỤC ĐÃ ĐƯỢC FIX (SO VỚI BÁO CÁO NGÀY 10/10)

Bảng dưới đây ghi nhận chi tiết các lỗi lệch chuẩn thiết kế đã được đội ngũ phát triển **sửa chữa triệt để**:

| # | Vấn đề ghi nhận ở báo cáo 10/10 | Tình trạng hiện tại (14/10/2026) | Trạng thái |
|---|---|---|:---:|
| 1 | **Hiện tượng "Bẫy 404" trên Sidebar:** 9 module Admin, 3 module Teacher, 4 module Student bị lỗi 404 do chưa có code. | Cả 14 module Admin, 7 module Teacher, 5 module Student đều đã có file source và template hoàn chỉnh. 100% menu click hoạt động bình thường. | **[ĐÃ FIX]** |
| 2 | **Admin Tổng quan thiếu Timeline Calendar:** Trang chỉ có 4 thẻ thống kê, thiếu hoàn toàn lịch tuần này dạng timeline 7 ngày. | Đã bổ sung khối `lms-week-timeline` (Timeline 7 cột Thứ 2 → CN, khung giờ 1:00–24:00) kèm Modal popup chi tiết buổi học (hiển thị lớp, môn, GV, thời gian, loại lớp, sĩ số, nút "Mở lớp học"). | **[ĐÃ FIX]** |
| 3 | **Admin Lớp học thiếu cột Khóa học & Chuyên đề:** Bảng danh sách thiếu cột theo dõi khóa học (đã học/còn lại) và chuyên đề đang học. | Bảng danh sách đã có cột "Khóa học" (hiển thị ngày bắt đầu/kết thúc, thanh tiến độ mini, số buổi đã học/còn lại) và cột "Chuyên đề đang học". | **[ĐÃ FIX]** |
| 4 | **Admin Lớp học thiếu nút Điều chỉnh số buổi & Lịch học:** Không có nút điều chỉnh buổi học theo mockup `Chỉnh số buổi.png`. | Đã thêm nút "Lịch học" (`lich-hoc_tpl.php`) và nút "Điều chỉnh số buổi học" mở modal `lmsAdjustSessionsModal` để cập nhật tổng số buổi của lớp. | **[ĐÃ FIX]** |
| 5 | **Form Thêm lớp mới thiếu Chuyên đề & Hạn chấm test:** Form tạo lớp thiếu chọn chuyên đề và thiếu hạn chấm test. | Đã thêm trường Multi-select "Chuyên đề" (tự động lọc theo môn học đã chọn) và trường "Hạn chấm test (giờ)" (`test_grace_hours`, mặc định 72h). | **[ĐÃ FIX]** |
| 6 | **Teacher Tổng quan sai lệch 100% bố cục:** Thay vì bố cục 3 cột theo mockup, code cũ lại dựng thành 3 thẻ stat và 1 bảng danh sách tuần. | Đã viết lại template theo đúng **bố cục 3 cột** kiểu mockup: Banner cảnh báo tick; cột giữa "Buổi học sắp tới" (card ngày/giờ/trạng thái) + lưới thẻ "Các học viên hiện tại" (avatar banner, tên, khung lịch); cột phải "Thông báo" gồm card tác vụ Ghi báo cáo/Chấm bài, card hạ phí dịch vụ và thông báo gần đây; header hiển thị hạng GV. Chat vẫn là placeholder (TODO). | **[ĐÃ FIX]** |
| 7 | **Teacher Học viên của tôi dạng Table:** Hiển thị dạng bảng thay vì danh sách thẻ theo mockup, thiếu nhãn trạng thái BTVN màu sắc. | Đã chuyển thành **danh sách thẻ ngang full-width**: avatar xám (hoặc ảnh HV), tên lớp (nhóm) / tên HV (1-1), khối + môn, lịch cố định mỗi buổi một dòng, nhãn BTVN góc phải dưới (đỏ: Chưa giao/Chưa chấm; xanh: Đã chấm). Bấm vào thẻ mở trang chi tiết khóa học. | **[ĐÃ FIX]** |
| 8 | **Thanh tìm kiếm Header cam bị vô hiệu hóa:** Input HTML bị disable, chưa gắn logic backend. | Đã kết nối form tìm kiếm với `lms_search.php`, hỗ trợ tìm kiếm phân quyền tự động theo vai trò người dùng (SYS-02). | **[ĐÃ FIX]** |
| 9 | **Module Nhận xét theo tháng của Giáo viên (TCH-06):** Source và template `lms-nhan-xet-thang` đã được viết nhưng chưa chạy migration module vào `db_lms_modules` trên database thật `phuongnamv_db`, gây ra lỗi 404 khi truy cập URL. | Đã thực thi migration `2026_10_12_lms_teacher_monthly_reviews_module.sql` vào `phuongnamv_db`. Phân quyền view/edit chuẩn. Đã kiểm thử tự động và trực quan bằng trình duyệt thực tế đạt 100%. | **[ĐÃ FIX]** |


---

## 3. ĐỐI CHIẾU TRỰC QUAN MOCKUP ẢNH PNG vs GIAO DIỆN HIỆN TẠI (NHỮNG ĐIỂM CÒN THIẾU HOẶC LỆCH MỚI)

Sau khi kiểm tra đối chiếu lại toàn bộ 89 ảnh mockup PNG với bộ source đã update, dưới đây là các **điểm thiếu sót thực tế còn tồn đọng** cần tiếp tục hoàn thiện:

### 3.1. Phân hệ Toàn cục (Global Elements)

1. **Widget Chat trực tiếp (`3.png` trong teacher & chân trang 3 portal):**
   - *Mockup:* Góc dưới bên phải mọi trang luôn có nút "Chat" nổi. Khi bấm sẽ mở Popup hội thoại Shopee/Messenger-style (cột danh sách cuộc trò chuyện, khung chat, ô nhập tin nhắn, đính kèm file/ảnh).
   - *Code thực tế:* Đã có **placeholder FAB/popover tĩnh**, chưa có logic chat thật. Đây là gap backlog, không phải blocker của SYS-01/SYS-03.

---

### 3.2. Phân hệ Admin

1. **Nút "Thêm khóa học" trên từng dòng bảng Lớp học (`Lớp học.png`):**
   - *Mockup cũ:* Cột Hành động của mỗi lớp có các nút: *Sửa*, *Lịch học*, ***Thêm khóa học***, *Xóa*.
   - *Thực tế hiện tại:* Đã bỏ bảng "Khóa học của lớp" và các action tạo/đóng cycle thủ công khỏi form sửa lớp theo thiết kế `admin-ver2`; cycle vẫn được tạo tự động khi tạo lớp và dữ liệu cycle cũ được giữ nguyên.

2. **Giao diện Chi tiết buổi học cho Admin (`chi tiết buổi học.png`):**
   - *Mockup:* Khi Admin truy cập từ màn hình Lịch học hoặc Quản lý BTVN, click vào 1 buổi học sẽ mở ra trang **Chi tiết buổi học** gồm 2 Section:
     - *Section 1 - Thông tin buổi học:* Tên chuyên đề, Ghi chú nội dung, BTVN, Nhận xét buổi học, Tệp đính kèm bài giảng + nút Cập nhật.
     - *Section 2 - Bài tập về nhà:* Danh sách 4 thumbnails/file bài nộp của học sinh, khung Chấm bài (textarea + 4 ô đính kèm bài đã chấm) + nút Cập nhật.
   - *Code thực tế:* Đã có route Admin `lms-lop/buoi`, link từ lịch học và báo cáo BTVN, cập nhật nội dung/chấm BTVN có audit. Cần browser smoke kiểm tra upload file, feedback và responsive so với mockup.

3. **Cột hiển thị trong bảng "Thống kê nhóm chương trình" (`Tổng quan.png`):**
   - *Mockup:* Các cột của bảng gồm: `STT` | `Nhóm chương trình` | `Số lớp đang học` | `Số lớp đang sắp xếp` | `Số lớp tạm nghỉ`.
   - *Code thực tế:* Đã chuyển các cột thống kê sang active/arranging/paused theo chương trình. Cần browser smoke sau migration để xác nhận dữ liệu hiển thị.

---

### 3.3. Phân hệ Teacher (Giáo viên)

1. **Trang Chi tiết khóa học tổng thể (`Trang Chi tiết khóa học.png`, `26.png`, `27.png`, `29.png`):**
   - *Mockup:* Khi bấm vào thẻ một học sinh ở trang "Học viên của tôi", mockup thể hiện một trang **Tổng quan khóa học** của lớp đó: hiển thị toàn bộ cây chuyên đề lộ trình, danh sách các bài test/minitest học sinh đã nộp và trạng thái chấm điểm của từng bài.
   - *Code thực tế:* Đã có route course detail theo học viên/cycle với tiến độ chuyên đề và trạng thái test/submission. Cần browser smoke xác nhận row-level, cycle override và dữ liệu điểm.

---

## 4. CÁC ĐIỂM SAI LỆCH VỀ TÀI LIỆU CẦN ĐÍNH CHÍNH (DISCREPANCIES)

1. **Nhầm lẫn tên file trong bộ Mockup Teacher:**
   - Trong thư mục `Tai-lieu-phat-trien/teacher/`, file ảnh mang tên `Trang Các khóa học.png` thực chất là chụp màn hình **"Học viên của tôi"** (Menu bên trái đang active mục Học viên của tôi). Lập trình viên khi nhìn vào tên file dễ bị nhầm đây là trang của học sinh.
2. **Khác biệt về định dạng xuất báo cáo (Excel vs CSV):**
   - Tài liệu SRS và SDLC đề cập đến "Xuất Excel" cho báo cáo phí dịch vụ và số buổi học.
   - Code thực tế hiện tại đang xuất định dạng **CSV chuẩn UTF-8 có BOM** (mở trực tiếp bằng Microsoft Excel không bị lỗi font tiếng Việt). Cần ghi nhận giải pháp này là hợp lý và đủ đáp ứng yêu cầu nghiệp vụ, không nhất thiết phải cài thêm thư viện nặng như `PhpSpreadsheet`.

---

## 5. ĐÁNH GIÁ CHẤT LƯỢNG KỸ THUẬT, KHẢ NĂNG SCALE & BẢO MẬT

### 5.1. Khả năng Scale & Điểm nghẽn hạ tầng
- **Codebase & Tầng nghiệp vụ:** Cấu trúc Service Layer tách biệt logic khỏi giao diện; hiện có 19 service/adapter cụ thể, gồm 18 concrete services kế thừa `LmsService` và `MailService` độc lập.
- **Điểm nghẽn Session khi chạy Multi-Server:** Hệ thống vẫn đang sử dụng file session mặc định của PHP (`session.save_handler = files`). Nếu sau này triển khai Load Balancer chạy nhiều web server song song, học viên/giáo viên sẽ bị văng đăng nhập. **Khuyến nghị:** Cần chuyển cấu hình lưu session vào Redis hoặc Database khi scale ngang.
- **Dung lượng lưu trữ File Upload:** Hiện tại các file bài nộp BTVN, đề thi test và thư viện tài liệu được lưu trực tiếp vào thư mục `uploads/lms/` trên ổ cứng server (đã có file `.htaccess` chặn thực thi script PHP bảo mật). Tuy nhiên, khi số lượng học sinh nộp file PDF/ảnh tăng cao, ổ cứng server sẽ nhanh đầy. **Khuyến nghị:** Nên có cron định kỳ nén/dọn dẹp hoặc cấu hình đẩy file lên Cloud Storage (S3 / R2).

### 5.2. Đánh giá Kiểm thử tự động (Test Coverage)
- Full PHPUnit ngày 17/10 pass **392 tests / 1.423 assertions** sau khi sửa fixture duplicate fixed slot trong `EnrolmentServiceTest`.
- SYS-01 reminder được kiểm thử trong `TickServiceTest`; SYS-03 reset/OTP trong `PasswordResetServiceTest`.
- Các test trọng yếu hiện có kiểm tra những quy tắc kinh doanh sau (không đồng nghĩa 100% code coverage):
  - Tính phạt tick và hạ % phí dịch vụ (`TickServiceTest`).
  - Tính thuế TNCN và miễn thuế theo Cam kết 08 (`TaxServiceTest`).
  - Chốt lương idempotent, có transaction chống trùng lặp (`PayrollServiceTest`).
  - Chống trùng lịch giáo viên và sinh lịch học tự động (`ScheduleServiceTest`, `SessionServiceTest`).
   - Ghép lớp theo điểm match (`MatchingServiceTest`).

### 5.3. Đối chiếu nghiệp vụ với mockup lần 2 (15–17/10/2026)
- Đã sửa Admin Timeline: chuẩn hóa thứ trong tuần theo cùng quy ước `WEEKDAYS` (Thứ 2 = 2 … Chủ nhật = 8), tránh lệch session sang cột khác hoặc mất session Chủ nhật.
- Đã giới hạn lộ trình Student theo `db_lms_class_topics` khi lớp có cấu hình chuyên đề; các lớp cũ chưa có bản ghi assignment vẫn giữ hành vi tương thích subject-wide.
- Đã enforce tại `StudentCourseService::submitTest()` điều kiện đủ số buổi (`max_sessions`) trước khi nhận bài, không phụ thuộc nút disabled trên giao diện; UI chi tiết chuyên đề cũng phản ánh trạng thái chưa đủ buổi.
- Đã triển khai code nền tảng course cycle Khóa 1/2/3, unlock theo từng học viên, Teacher course detail, Admin session detail/chấm BTVN có audit, trạng thái lớp `arranging` và bộ lọc matching.
- **Dữ liệu local cần chuẩn hóa:** `phuongnamv_db` có hai môn nhưng không có row `db_lms_topics`; backfill tạo cycle nhưng không thể tạo cycle topics/unlocks. Regression test xác nhận khi chưa có curriculum thì không tạo unlock giả; với topic fixture thì Student chỉ thấy topic đã mở. Không seed topic giả; cần import curriculum chính thức rồi chạy backfill cycle topics/unlocks.
- Full PHPUnit hiện pass 391/1.416; fixture trùng session slot đã được sửa.
- DB `phuongnamv_db` đã có schema cycle/backfill nhưng 2 môn hiện hữu không có topic rows, vì vậy cycle topic/unlock row count bằng 0. Cần curriculum chính thức trước khi khẳng định smoke unlock nghiệp vụ.
- Release gates: import curriculum chính thức và backfill topic/unlock; browser smoke cycle/unlock/Admin grading/matching; SMTP sandbox SYS-03. Chat thật vẫn ngoài scope.

---

## 6. KẾ HOẠCH HÀNH ĐỘNG TIẾP THEO (NEXT ACTION PLAN)

Để đưa hệ thống đạt điểm hoàn hảo tuyệt đối (10/10) so với thiết kế:

1. **P0 - Dữ liệu curriculum:** Import topic/chuyên đề chính thức cho hai môn hiện có trong `phuongnamv_db`; kiểm tra assignment lớp, sau đó backfill cycle topics và unlock đầu tiên, đối chiếu count trước/sau. Không tự tạo topic giả.
2. **P0 - Regression suite:** Fixture duplicate fixed slot trong `EnrolmentServiceTest::testRemoveKeepsSessionStudentsWithData` đã sửa; full PHPUnit pass 391/1.416.
3. **P1 - Browser smoke:** Tạo cycle 2 và override; xác nhận cycle 1 bất biến; kiểm tra unlock theo đủ buổi/max NULL; Admin grading/audit; arranging Dashboard; matching filter/pagination.
4. **P1 - SYS-03:** Khởi chạy Mailpit/MailHog và test reset password thành công/thất bại, OTP hết hạn/dùng lại, anti-enumeration trên cả ba portal. Hiện không có SMTP listener ở `localhost:1025`/`2525`.
5. **P2 - UI polish:** Hoàn thiện UI điểm/feedback test-minitest phía Teacher; bảng "Khóa học của lớp" trong form sửa lớp đã được bỏ theo thiết kế admin-ver2.
6. **Ngoài scope:** Chat thật; giữ placeholder cho tới khi có đặc tả và hạ tầng được duyệt.

### Ghi chú dependency bảo mật

`firebase/php-jwt` được khóa ở `^6.4` vì các bản `6.10+` không hỗ trợ PHP 7.3; PHPMailer được cài trong `library/vendor` và thư mục vendor không được track. `composer audit` báo CVE-2025-45769 ảnh hưởng các bản JWT `<7.0.0`. Nâng cấp lên bản đã vá cần nâng runtime PHP lên phiên bản được package hỗ trợ; theo dõi và xử lý trong kế hoạch nâng cấp PHP, không bỏ qua cảnh báo.

---

## 7. BÁO CÁO RÀ SOÁT & KẾT QUẢ KIỂM THỬ TOÀN DIỆN 3 PHÂN HỆ VỚI BỘ TÀI KHOẢN THỰC TẾ

> **Thời điểm ghi nhận:** 17/10/2026
> **Phạm vi bằng chứng:** PHPUnit chạy trong workspace; các kết quả browser/E2E trước đây chưa được tái chạy ở lần cập nhật này.
> **Kết quả tổng quan:**
> - **PHPUnit:** **392 / 392 tests PASS (1.423 assertions)** sau khi sửa fixture duplicate session slot và thêm empty-curriculum/cycle-topic regression; còn deprecation warning dynamic properties trên PHP 8.2.
> - **E2E HTTP Smoke:** kết quả 89/89 trước đây là lịch sử; script/evidence không nằm trong repo và chưa được tái lập ở lần cập nhật này.
> - **Apache local:** PHP 7.4.9; PHPUnit CLI PHP 8.2 có deprecation warning dynamic properties trong `EnrolmentServiceTest`.

### 7.1. Bảng thông tin tài khoản và phạm vi kiểm thử

| Phân hệ (Portal) | URL Cổng đăng nhập | Tài khoản kiểm thử | Mật khẩu | Kết quả đăng nhập |
|---|---|---|---|:---:|
| **Quản trị (Admin)** | `/lms/admin/login` | Tài khoản demo đã seed trong DB local | Không lưu mật khẩu trong tài liệu | Đã smoke 05/11/2026 |
| **Giáo viên (Teacher)** | `/lms/teacher/login` | Tài khoản demo đã seed trong DB local | Không lưu mật khẩu trong tài liệu | Đã smoke 05/11/2026 |
| **Học viên (Student)** | `/lms/student/login` | Tài khoản demo đã seed trong DB local | Không lưu mật khẩu trong tài liệu | Đã smoke 05/11/2026 |

> Trang chọn vai trò `/lms/` chỉ hiển thị 2 thẻ **Học viên** và **Giáo viên** theo mockup `login-role-selection.png`; Admin đăng nhập trực tiếp tại `/lms/admin/login` (không hiện trên trang chọn vai trò). Ba trang login dùng chung layout theo mockup `Đăng nhập admin.png`.

---

### 7.2. Ma trận kết quả kiểm thử chức năng chi tiết từng Phân hệ

#### A. Phân hệ Quản trị (Admin LMS — 18 Endpoints đã kiểm tra)

| STT | Module / Endpoint | Tên chức năng | Thao tác kiểm thử | HTTP | Kết quả hiển thị & Nghiệp vụ |
|:---:|---|---|---|:---:|---|
| 1 | `/lms/admin/lms-tong-quan` | Tổng quan Dashboard | GET, View, Timeline | 200 | Hiển thị 4 stat cards, Timeline tuần 7 ngày, modal chi tiết ca học khi click vào lịch |
| 2 | `/lms/admin/lms-lop` | Quản lý Lớp học | GET, Table, Filter | 200 | Bảng danh sách lớp, tiến độ mini, chuyên đề, nút Sửa/Lịch/Chỉnh buổi/Tạm dừng/Xóa |
| 3 | `/lms/admin/lms-lop/them` | Thêm lớp mới | GET, Form, CSRF | 200 | Form đa trường: chọn chuyên đề theo môn, hạn chấm test (`test_grace_hours`), GV, phòng |
| 4 | `/lms/admin/lms-lop/lich-hoc?id=1` | Lịch học chi tiết của lớp | GET, Calendar | 200 | Lịch tuần 24 giờ dùng component `calendar_week` + `calendar_week_event` (thẻ giờ lẻ thụt theo phút); lịch cố định dùng chung `form/class_schedule_slots`; không còn nút Thêm buổi/Xem đủ 24h/chuyển Lưới-Danh sách |
| 5 | `/lms/admin/lms-hoc-vien` | Quản lý Học viên | GET, Table, Filter | 200 | Bảng danh sách học viên, tìm kiếm, xem hồ sơ, trạng thái đang học/chờ xếp/nghỉ |
| 6 | `/lms/admin/lms-giao-vien` | Quản lý Giáo viên | GET, Table, Filter | 200 | Bảng danh sách giáo viên, môn phụ trách, % phí dịch vụ, trạng thái hợp đồng |
| 7 | `/lms/admin/lms-lop-hoc-sinh` | Ma trận Học sinh - Lớp | GET, Enrolment | 200 | Quản lý xếp học viên vào lớp, chuyển lớp, rút học viên khỏi lớp an toàn |
| 8 | `/lms/admin/lms-sap-xep-lop` | Sắp xếp lớp & Ghép GV | GET, Matching | 200 | 3 khối lọc (tiêu chí HV tự điền theo username, lịch trống nhiều ngày/khung giờ, tiêu chí GV), bảng 10 cột sắp theo hạng/cập nhật, modal chi tiết lịch tuần GV + ghi chú tuần, ẩn/hiện GV trong phiên |
| 9 | `/lms/admin/lms-vi-pham` | Quản lý Vi phạm & Phạt tick | GET, Rules, Filter | 200 | Danh sách vi phạm, áp dụng ngưỡng phạt tick (3 tick = hạ 5%, 5 tick = hạ 10%) |
| 10 | `/lms/admin/lms-phi-dv` | Phí dịch vụ & Quyết toán | GET, Payroll, Calc | 200 | Tính đúng thuế TNCN 10% (miễn thuế nếu có Cam kết 08), duyệt lương, chốt bảng lương |
| 11 | `/lms/admin/lms-thong-ke-buoi-hoc`| Thống kê số buổi học | GET, Stats, Export | 200 | Thống kê số buổi đã dạy, xuất file báo cáo CSV chuẩn UTF-8 có BOM mở Excel tốt |
| 12 | `/lms/admin/lms-bai-tap-ve-nha` | Quản lý Bài tập về nhà | GET, Monitor HW | 200 | Theo dõi BTVN và nhận xét buổi học của giáo viên theo từng buổi đã kết thúc; buổi chưa báo cáo hiển thị "Chưa giao" |
| 13 | `/lms/admin/lms-lo-trinh` | Quản lý Lộ trình & Chuyên đề | GET, Syllabus | 200 | Danh sách chuyên đề phân cấp theo môn học, cờ bài test / minitest |
| 14 | `/lms/admin/lms-noi-quy` | Quản lý Nội quy trung tâm | GET, Rules edit | 200 | Chỉnh sửa và ban hành nội quy, quy chuẩn giảng dạy và học tập |
| 15 | `/lms/admin/lms-yeu-cau` | Quản lý Yêu cầu (Request) | GET, Workflow | 200 | Duyệt yêu cầu xin nghỉ dạy, đổi lịch hoặc thêm buổi học của giáo viên/học viên |
| 16 | `/lms/admin/lms-thu-vien` | Kho Thư viện tài liệu | GET, File Manager | 200 | Phân loại danh mục tài liệu, phân quyền tải, tải lên tệp giáo trình |
| 17 | `/lms/admin/lms-audit-log` | Nhật ký kiểm toán hệ thống | GET, Audit Trail | 200 | Tra cứu thao tác dữ liệu nhạy cảm kèm before/after JSON, IP & User-Agent |
| 18 | `/lms/admin/lms-thong-bao` | Trung tâm Thông báo | GET, Notif center | 200 | Danh sách thông báo hệ thống, phân loại đọc/chưa đọc |

---

#### B. Phân hệ Giáo viên (Teacher LMS — 10 Endpoints đã kiểm tra với `gv01`, `gv02`, `gv03`)

| STT | Module / Endpoint | Tên chức năng | Thao tác kiểm thử | HTTP | Kết quả hiển thị & Nghiệp vụ |
|:---:|---|---|---|:---:|---|
| 1 | `/lms/teacher/lms-tong-quan` | Tổng quan Giáo viên | GET, 3-column UI | 200 | Bố cục 3 cột theo mockup: banner cảnh báo tick, Buổi học sắp tới, lưới thẻ học viên hiện tại, cột Thông báo (card tác vụ + thông báo), header kèm hạng GV |
| 2 | `/lms/teacher/lms-tai-khoan` | Tài khoản giáo viên | GET/POST, Profile | 200 | Card hồ sơ + 3 nhóm Thông tin cá nhân / Giảng dạy / Thanh toán; upload Đổi Ảnh; validate email/phone/CCCD; nút Hủy & Lưu thay đổi |
| 3 | `/lms/teacher/lms-hoc-vien-cua-toi` | Học viên của tôi | GET, danh sách thẻ | 200 | Card full-width dùng chung `.lmnx-card--avatar`: avatar xám/ảnh HV, tên lớp (nhóm)/tên HV (1-1), khối + môn, lịch từng buổi, trạng thái BTVN đỏ/xanh in nghiêng; click thẻ mở chi tiết |
| 4 | `/lms/teacher/lms-lich-day` | Lịch dạy tuần | GET, 5-color Calendar | 200 | Lịch tuần dùng component chung `calendar_week` + `calendar_week_event` (5 màu: sắp tới / thiếu BTVN / hoàn thành / đã nghỉ / lịch trống; thẻ giờ lẻ thụt theo phút); hồ sơ GV + ghi chú tuần; 3 modal đăng ký thêm buổi, xin nghỉ, đổi lịch cố định |
| 5 | `/lms/teacher/lms-lich-day/buoi?id=X` | Chi tiết buổi dạy | GET, Report & Grade | 200 | **Kiểm tra Row-Level Security:** GV01 chỉ mở được buổi #1 của mình (200), GV02 mở buổi #5 của mình (200); GV02 cố mở buổi #1 của GV01 bị chặn và chuyển hướng an toàn (302) |
| 6 | `/lms/teacher/lms-phi-dv` | Thù lao & Phí dịch vụ | GET, Fee breakdown | 200 | Lọc theo học viên + khoảng ngày, ô Tổng tiền VND, bảng Ngày/Học viên/Số tiền/Nội dung; banner cảnh báo tick; số liệu khớp admin `lms-phi-dv` |
| 7 | `/lms/teacher/lms-vi-pham` | Vi phạm chất lượng dịch vụ | GET, Violations log | 200 | Panel tick raw/miễn/thực tế/ngưỡng hạ phí, bảng Ngày/Học viên/Số tick/Nội dung, ghi chú chính sách miễn giảm, phân trang 20 dòng |
| 8 | `/lms/teacher/lms-nhan-xet-thang` | Nhận xét học viên hàng tháng | GET/POST, TCH-06 | 200 | Card dùng chung với Student: avatar/tên lớp, khối-môn, lịch; dropdown tháng; lớp 1-1 một field, lớp nhóm nhiều field theo từng HV; nút Cập nhật lưu & đăng ngay |
| 9 | `/lms/teacher/lms-thu-vien` | Thư viện giáo trình | GET, Library view | 200 | Xem và tải tài liệu/giáo trình phục vụ giảng dạy |
| 10 | `/lms/teacher/lms-thong-bao` | Thông báo giáo viên | GET, Notifications | 200 | Danh sách thông báo phân công lớp, nhắc nhở nộp báo cáo |

---

#### C. Phân hệ Học viên (Student LMS — 6 Endpoints đã kiểm tra với `hv01` đến `hv05`)

| STT | Module / Endpoint | Tên chức năng | Thao tác kiểm thử | HTTP | Kết quả hiển thị & Nghiệp vụ |
|:---:|---|---|---|:---:|---|
| 1 | `/lms/student/lms-tong-quan` | Tổng quan Học viên | GET, Dashboard | 200 | Buổi học sắp tới (card ngày/giờ/trạng thái), lưới khoá học kèm lịch cố định, cột thông báo có nút Xem chi tiết |
| 2 | `/lms/student/lms-khoa-hoc` | Các khóa học & Lộ trình | GET, Roadmap, Test | 200 | Header “Xem tất cả” + lưới 2 cột card `.lmnx-card`: ảnh bìa môn, tên lớp, khối, lịch, trạng thái BTVN in nghiêng; bấm card mở chi tiết lớp/lộ trình/test |
| 3 | `/lms/student/lms-lich-hoc` | Lịch học tuần | GET, Schedule | 200 | Thời khóa biểu tuần của học viên, thời gian ca học, phòng học hoặc đường link học trực tuyến |
| 4 | `/lms/student/lms-nhan-xet-thang` | Nhận xét tháng | GET, Reviews list | 200 | Lưới 2 cột: ảnh bìa môn, tên lớp, khối, lịch cố định, dropdown tháng riêng từng lớp; chỉ hiện nhận xét đã công bố (published), đổi tháng không reload |
| 5 | `/lms/student/lms-noi-quy` | Nội quy lớp học | GET, Rules read-only | 200 | Panel trắng chỉ đọc đúng mockup, không toolbar/textarea; render nội quy admin nhập (sanitize HTML hoặc escape text), placeholder khi chưa có dữ liệu |
| 6 | `/lms/student/lms-thong-bao` | Thông báo học viên | GET, Notifications | 200 | Thông báo lịch học mới, nhắc học trước 1 giờ (SYS-01), thông báo bài tập |

---

### 7.3. Kết quả kiểm tra bảo mật phân quyền chéo (Cross-Portal Security)

Hệ thống tuân thủ nghiêm ngặt **Quy tắc D9 (Phân vùng Portal an toàn)**:

1. **Học viên (`hv01`) truy cập Admin Dashboard (`/lms/admin/lms-tong-quan`):** Bị chặn lập tức, trả về mã HTTP `302`, chuyển hướng về đúng portal học viên (`/lms/student/login`).
2. **Giáo viên (`gv01`) truy cập Admin Quyết toán Phí DV (`/lms/admin/lms-phi-dv`):** Bị chặn lập tức, trả về mã HTTP `302`, chuyển hướng về portal giáo viên (`/lms/teacher/login`).
3. **Học viên (`hv01`) truy cập Lịch dạy Giáo viên (`/lms/teacher/lms-lich-day`):** Bị chặn lập tức, trả về mã HTTP `302`, chuyển hướng về portal học viên.
4. **Giáo viên (`gv01`) truy cập Khóa học của Học viên (`/lms/student/lms-khoa-hoc`):** Bị chặn lập tức, trả về mã HTTP `302`, chuyển hướng về portal giáo viên.
5. **Người dùng chưa đăng nhập truy cập bất kỳ trang nội bộ nào:** Bị chặn lập tức, trả về mã HTTP `302`, yêu cầu đăng nhập tại portal tương ứng.
6. **Bảo mật phân quyền mức dòng (Row-Level Security):** Giáo viên `gv02` không thể xem hoặc sửa buổi dạy của giáo viên `gv01` (bị redirect về lịch tuần của mình kèm thông báo cảnh báo).

---

### 7.4. Bằng chứng kiểm thử & Dấu vết Artifacts

- **Video ghi hình phiên kiểm thử Admin Portal:** [lms_admin_test.webp](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/lms_admin_test_1791967697178.webp)
- **Video ghi hình phiên kiểm thử Teacher & Student Portal:** [lms_teacher_student_test.webp](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/lms_teacher_student_test_1791967966598.webp)
- **Video kiểm thử tương tác Teacher Monthly Review & Student Roadmap:** [verify_modules_fix.webp](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/verify_modules_fix_1791969461216.webp)
- **Ảnh chụp màn hình phân hệ:**
  - Admin: [Lịch Timeline & Modal buổi học](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_overview_calendar_1791967843270.png), [Quản lý Lớp học](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_classes_1791967856223.png), [Form Thêm lớp chuyên đề](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_add_class_1791967865534.png), [Sắp xếp ghép lớp](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_scheduling_1791967879637.png), [Vi phạm & Phạt tick](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_violations_1791967900477.png), [Phí dịch vụ & Lương](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_service_fees_1791967920632.png), [Nhật ký Audit](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/admin_audit_log_1791967951070.png).
  - Teacher: [Tổng quan 3 cột](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/teacher_overview_1791968011032.png), [Học viên của tôi (danh sách thẻ)](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/teacher_students_1791968014869.png), [Lịch dạy 5 màu](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/teacher_schedule_1791968018475.png), [Nhận xét tháng (TCH-06 Form)](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/teacher_monthly_review_1791969491249.png).
  - Student: [Tổng quan học viên](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/student_overview_1791968059514.png), [Khóa học & Tiến độ](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/student_courses_1791968063766.png), [Cây chuyên đề lộ trình & Test](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/student_class_roadmap_1791969525454.png), [Lịch học tuần](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/student_schedule_1791968067557.png), [Nhận xét tháng đã công bố](file:///C:/Users/admin/.gemini/antigravity-ide/brain/4b7bb455-72b2-45c3-8b07-60061c1206d9/student_monthly_review_1791968071978.png).

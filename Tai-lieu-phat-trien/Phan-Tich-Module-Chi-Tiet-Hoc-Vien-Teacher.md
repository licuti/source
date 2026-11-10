# Phân tích nghiệp vụ — Teacher “Chi tiết học viên”

> Tài liệu phân tích & trạng thái triển khai module Teacher “Chi tiết học viên”.
> Trạng thái: **đã triển khai Phase 1, Phase 2 (Gửi test) và luồng lớp nhóm; phần còn lại có thể tạm hoãn.**
> Ngày lập: 02/11/2026 — cập nhật trạng thái: 05/11/2026.

---

## 1. Phạm vi

Module Teacher: `/lms/teacher/lms-hoc-vien-cua-toi` → trang chi tiết học viên.

Bộ mockup tham chiếu (15 ảnh trong `Tai-lieu-phat-trien/teacher/` và ver2 trong `Tai-lieu-phat-trien/teacher-ver2/`):

1. `Chi tiết học viên - Lộ trình và Buổi học.png`
2. `Chi tiết học viên - Lộ trình KET.png`
3. `Chi tiết học viên - Lộ trình Tiếng Anh.png`
4. `Chi tiết học viên - Lộ trình Khoa học.png`
5. `Chi tiết học viên - Math IQ.png`
6. `Chi tiết học viên - Math IQ (2).png`
7. `Chi tiết học viên - Modal Báo cáo chung đề thi.png`
8. `Chi tiết học viên - Modal Request test.png`
9. `Chi tiết học viên - Modal Request thêm buổi.png`
10. `Chi tiết học viên - Chấm Test và Minitest.png`
11. `Chi tiết học viên - Dropdown chuyên đề.png`
12. `Chi tiết học viên - Trạng thái đã giao BTVN.png`
13. `Chi tiết học viên - Trạng thái đã chấm BTVN/hoàn thành.png` (Trạng thái hoàn thành)
14. `Chi tiết học viên - Trạng thái đã nộp test.png`
15. `Chi tiết học viên - Trạng thái đã nộp minitest.png`
16. `Chi tiết Chấm Test.png` (teacher-ver2)

Module admin liên quan: `/lms/admin/lms-yeu-cau` (5 tab).

---

## 2. Kết luận cốt lõi

1. **Đã chốt Phương án B:** một lớp được chọn nhiều chuyên đề, không ràng buộc Danh mục/Chương trình.
2. `classes.subject_id` chỉ còn là **Danh mục chính** (cho danh sách/học phí), không giới hạn chuyên đề.
3. Một học viên có thể học nhiều lớp → nhiều Danh mục.
4. Trang chi tiết đúng mockup phải là **trang theo học viên**, gộp tất cả lớp/cycle đang active của học viên với giáo viên hiện tại.
5. Lộ trình group theo **`topic.subject_id`** của từng chuyên đề đã gán, không theo `class.subject_id`.

---

## 2.1. Trạng thái triển khai & khả năng tạm hoãn (05/11/2026)

### Đã triển khai

- **Cấp 1:** 1 thẻ/lớp nhóm, 1 thẻ/học viên với lớp 1-1; nhãn BTVN theo dữ liệu.
- **Cấp 2A (1-1):** 2 accordion cha; Lộ trình group theo Danh mục; card hiển thị X/Y, điểm test/minitest, trạng thái khóa; Lesson summary 3 trạng thái + link trang buổi học.
- **Trạng thái trước khi gửi test (Pre-request state):** Chuyên đề hoàn thành đủ buổi học (ví dụ: Đã dạy: 2/2 buổi) nhưng chưa gửi duyệt test sẽ hiển thị song song 2 nút cam **"Gửi test"** và **"Thêm buổi"**.
- **Gửi test (Phase 2):** nút Gửi test → modal Request test (tự động nhận tên chuyên đề, điểm Review, Deadline và link doc Lesson summary chuyên đề sau) → tạo draft test/minitest → admin publish → mở khóa chuyên đề kế tiếp; card hiển thị Chờ duyệt test / Chờ học sinh nộp.
- **Thêm buổi cho 1-1:** nút Thêm buổi → modal Request thêm buổi học (nhận tên chuyên đề, nhập lý do) → tạo request `add_session` sang admin.
- **Giáo viên chấm test:** màn hình Chấm Test (`cham-test`) khớp layout `teacher-ver2/Chi tiết Chấm Test.png` với preview đề test, bài làm của học sinh, form nhập điểm X, quy đổi %, nhận xét và đính kèm file feedback giáo viên.
- **Cấp 2B (lớp nhóm):** 3 accordion (Danh sách HV / Lộ trình / Nội dung buổi học), Thêm buổi tạo request cho lớp, chấm BTVN inline từng học viên.
- **Dữ liệu mẫu đầy đủ các trạng thái:**
  - *Đã hoàn thành:* How to calculate (Điểm test 80%, minitest 65%).
  - *Chờ duyệt test:* Model Intermediate (đang ở trạng thái draft chờ admin duyệt).
  - *Chờ giáo viên chấm test:* Model Intermediate 2 (học sinh đã nộp bài, hiện nút cam "Chấm test").
  - *Trước khi gửi admin duyệt:* Model Intermediate 3 (đã dạy 2/2 buổi, hiện 2 nút cam "Gửi test" & "Thêm buổi").
  - *Chưa mở khóa:* IQ Type x, Đề 1, Đề 2 (các chuyên đề tiếp theo).

### Có thể tạm hoãn phần còn lại

Các phần dưới đây **không chặn admin, student portal hay các module Teacher khác**:

- Gộp trang chi tiết theo học viên (nhiều lớp) — hiện trang vẫn theo lớp.
- **Math IQ**: bảng 4 điểm + báo cáo chung.
- KET/English **3 tầng roadmap**.
- Sửa nội dung buổi học **inline** cho lớp nhóm.
- Chat thật theo học viên.

### Điều kiện khi quay lại làm tiếp

1. Chốt trang chi tiết 1-1 có gộp nhiều lớp theo học viên hay giữ theo lớp.
2. Chốt cấu trúc 3 tầng KET/English (Danh mục vs nhóm kỹ năng).
3. Chốt mô hình Math IQ (Danh mục chuyên đề hay bộ đề riêng 4 điểm).
4. Chuẩn bị curriculum thật và dữ liệu test/bài nộp tương ứng.

---

## 3. Có bao nhiêu tầng dữ liệu?

### 3.1. Tầng dữ liệu trong DB

```
db_lms_programs   (Chương trình, VD: Toán TĐN, Tiếng Anh giao tiếp)
   └── db_lms_subjects  (Danh mục/Môn, VD: Math Problems, Math IQ, Reading)
          └── db_lms_topics  (Chuyên đề/Lộ trình, VD: How to calculate)
```

- `db_lms_classes.subject_id` → **Danh mục chính**; từ Phương án B không còn giới hạn chuyên đề của lớp.
- `db_lms_cycle_topics` → gắn topic vào cycle của lớp.
- `db_lms_topics.has_test` = “Loại chuyên đề: Có test / Không test”.
- `db_lms_subjects.program_id` có sẵn, nhưng giao diện admin **Quản lý lộ trình** chỉ hiển thị 2 tầng: **Danh mục (subject) → Chuyên đề (topic)**; không quản lý nhóm kỹ năng/program như một tầng UI.

### 3.2. Mockup hiển thị mấy tầng?

| Ảnh | Tầng hiển thị |
|---|---|
| Toán (`Lộ trình và Buổi học.png`) | **2 tầng**: Danh mục (Math Problems / Math IQ) → Chuyên đề (card) |
| KET (`Lộ trình KET.png`) | **3 tầng**: “Lộ trình KET” → Reading/Writing/Listening → Part 1/Part 2 |
| Tiếng Anh (`Lộ trình Tiếng Anh.png`) | **3 tầng**: “Lộ trình” → Listening/Reading/Writing/Speaking/Grammar/Vocabulary → chuyên đề |

→ Ảnh Toán làm được khi dữ liệu lớp có chuyên đề thuộc nhiều Danh mục (sau Phương án B). Ảnh KET/English vẫn cần quy tắc nhóm ngoài `subject` hoặc render phẳng theo Danh mục.

---

## 4. Mô hình UI mong muốn (theo mockup + trao đổi)

Trang chi tiết học viên:

```
Tên học viên

▼ Lộ trình (luôn mở)
   ▼ Math Problems             ← accordion con = Danh mục (subject)
        [How to calculate]     ← card = Chuyên đề (topic)
        [Model Intermediate]
   ▼ Math IQ
        [Đề 1] [Đề 2]

▼ Lesson summary & BTVN (luôn mở)
   [Buổi học Thứ 2 21/7]
   [Buổi học Thứ 4 23/7]
```

Mỗi card chuyên đề gồm:

- Tên chuyên đề.
- Đã dạy: `done/max` buổi.
- Điểm test %, điểm minitest % (chỉ khi chuyên đề có `has_test = 1`).
- Nút theo trạng thái:
  - Chưa gửi test: **Gửi test** + **Thêm buổi**.
  - Đã nộp test chưa chấm: **Chấm Test**.
  - Đã nộp minitest chưa chấm: **Chấm Minitest**.
  - Đã chấm xong: **Done - Request test**.
  - Chưa mở khóa: **Chưa mở khóa** (disabled).
- Chuyên đề `has_test = 0`: dùng **báo cáo chung** (bút chì) thay vì điểm test/minitest.

---

## 5. Mapping UI ↔ dữ liệu

| UI | Dữ liệu |
|---|---|
| Accordion con “Math Problems” | `db_lms_subjects.name` (Danh mục) |
| Card “How to calculate” | `db_lms_topics.name` |
| Đã dạy X/Y | `db_lms_cycle_topics.max_sessions` + đếm session `finished` có topic trong `topic_id/topic_ids` |
| Điểm test / minitest | `db_lms_tests` + `db_lms_test_submissions.score_y_percent` |
| Có test / Không test | `db_lms_topics.has_test` (admin ghi rõ: Có test = áp dụng test + minitest) |
| Chưa mở khóa | `db_lms_cycle_student_topics.status` |
| Lesson summary | `db_lms_sessions` + `db_lms_session_students` |
| Lớp/cycle của card | `class_id`, `cycle_id` gắn ngầm để action đúng lớp |

---

## 6. Luồng nghiệp vụ chi tiết

### 6.1. Gửi test

```
Teacher bấm "Gửi test"
   → mở modal Request test
   → tạo bài test trạng thái draft (db_lms_tests)
   → Admin vào tab "Request test"
   → Admin Xác nhận → published
   → Học viên thấy bài test để làm
```

**Hiện trạng code:**

- Admin tab “Request test” chỉ đọc bài test `draft` và có nút Xác nhận/Xoá — **không có màn tạo test**.
- Chưa có code nào tạo `db_lms_tests` từ Teacher.
- `RequestService` không nhận type `send_test`; nếu có request `send_test` thì trang chi tiết admin báo “chưa hỗ trợ duyệt tự động”.

**Modal Request test (mockup):**

- Điểm Review*
- Deadline* (ngày + giờ)
- Link Lesson summary chuyên đề sau*
- Gửi

**Cần bổ sung:**

- Cột `db_lms_tests.lesson_summary_url`.
- Quy ước “Điểm Review” (dùng `max_score` hay thêm `review_score`).
- Loại test (test/minitest) — một modal tạo cả hai hay chọn loại?
- Quyền Teacher được tạo draft test cho lớp mình dạy.

### 6.2. Mở khóa chuyên đề

**Đã chốt theo trao đổi:** chuyên đề trước **đã gửi test** → chuyên đề sau mở.

Hiện tại code mở khóa theo **số buổi finished >= max_sessions** và GV bấm nút riêng (`CourseCycleService::teacherUnlockNextTopic`). Cần đổi logic:

- Đề xuất hiểu “đã gửi test” = đã tồn tại ít nhất 1 bản ghi `db_lms_tests` (draft hoặc published) cho topic trước.
- Cần chốt: mở ngay khi tạo draft (GV vừa bấm Gửi) hay khi Admin publish.

### 6.3. Thêm buổi

```
Teacher gửi request add_session
   → Admin tab "Request thêm buổi"
   → Admin duyệt → tạo session mới + trừ remaining_sessions
```

**Vấn đề:** modal trong mockup chỉ có Chuyên đề + Lý do; nhưng `RequestService` bắt buộc `session_date + start_time + end_time`.

- “Thời gian request” trong bảng admin là `created_at`, **không phải** ngày/giờ học.
- Ngày/giờ học chỉ hiện ở trang chi tiết request (bấm mã lớp).
- Nếu modal không có ngày/giờ → không thể tạo request hợp lệ.

**Đề xuất:** thêm ngày + giờ vào modal Teacher (khác mockup một chút nhưng chạy đúng).

### 6.4. Lesson summary & BTVN

Trạng thái theo ảnh:

1. **Chưa diễn ra** → “Chưa diễn ra”.
2. **Đã dạy, chưa giao BTVN** → form: chọn chuyên đề*, nội dung BTVN*, deadline (ngày + giờ), nhận xét, tệp đính kèm, Cập nhật; trạng thái “Chưa giao BTVN”.
3. **Đã giao BTVN** → read-only + nút **Chấm BTVN**; trạng thái “Chưa chấm BTVN”.
4. **Đã chấm** → nút **Xem chi tiết** + “Đã hoàn thành”.

Lưu ý: lesson summary là **cấp buổi học/lớp**, không phải cấp học viên. Với lớp nhóm, sửa từ trang 1 HV sẽ đổi nội dung của cả lớp.

### 6.5. Chấm Test / Minitest

- Dùng `db_lms_tests` + `db_lms_test_submissions`.
- Hiển thị PDF đề, 4 ô bài nộp, template chấm, `score_x` (X/20), `score_y_percent` (Y%), file Excel, cảnh báo hạn chấm, Gửi.
- Hiện chỉ Admin có hàm chấm (`AdminRequestService::updateSubmission`); Teacher chưa có service/trang chấm.
- Cần chốt: Teacher nhập điểm hay Admin duyệt kết quả? Admin tab “Duyệt KQ test” đang là nơi xác nhận kết quả.

### 6.6. Math IQ

- Card “Đề 1 / Đề 2” có 4 điểm: trắc nghiệm, tự luận toán, tự luận tiếng Anh, Overall.
- Nút bút chì mở “Báo cáo chung”.
- Chưa có bảng/cột lưu 4 điểm.
- Đề xuất bảng Teacher-side `db_lms_exam_results` (student_id, cycle_id, topic_id, 4 điểm, report, updated_by), không sửa code admin.

---

## 7. Điểm cần hỏi lại quản lý

1. **Mốc mở khóa:** tạo draft test, admin publish, hay học viên nộp test?
2. **Gửi test tạo gì:** có đồng ý Teacher tạo `db_lms_tests` draft để admin tab “Request test” xử lý không?
3. **Điểm Review:** là thang điểm (`max_score`) hay một điểm đánh giá riêng?
4. **Loại test:** một lần gửi tạo Test + Minitest hay chọn từng loại?
5. **Link Lesson summary chuyên đề sau:** bắt buộc hay không bắt buộc? Mở tab mới?
6. **Deadline test:** giáo viên nhập ngày + giờ, có cần cả giờ không?
7. **Thêm buổi:** đồng ý thêm ngày + giờ vào modal? Ai chọn thời gian (GV hay Admin)?
8. **Lesson summary lớp nhóm:** GV sửa từ trang 1 HV ảnh hưởng cả lớp — có chấp nhận?
9. **Deadline lesson summary:** khóa sau 12h ngày kế tiếp như hiện tại?
10. **Chấm test/minitest:** Teacher chấm hay Admin chấm? Có cần Admin xác nhận lại?
11. **Nhiều test cùng topic/loại:** hiển thị điểm nào (mới nhất / cao nhất / bài chưa chấm)?
12. **Has_test = 1:** bắt buộc có cả test và minitest? Thứ tự làm test trước hay minitest trước?
13. **Done - Request test:** nghĩa là gì? Có phải xin gửi test cho chuyên đề kế tiếp?
14. **KET/English:** dữ liệu có Chương trình → Danh mục con không? Nếu không, chấp nhận render phẳng?
15. **Math IQ:** 4 điểm lưu bảng mới có được không? Có thay thế test/minitest không?
16. **Dữ liệu cycle thiếu:** cycle không có `cycle_topics` (hiện có ở lớp Toán Duy Kiên) → fallback `class_topics`; nếu vẫn rỗng thì hiển thị empty state.
17. **Trùng chuyên đề giữa nhiều cycle:** gộp theo tên (ưu tiên cycle mới) hay hiển thị riêng từng lớp?
18. **Quyền Teacher tạo draft test:** có giới hạn theo lớp mình dạy không?

---

## 8. Hiện trạng kỹ thuật & dữ liệu đáng chú ý

- `ClassService` đã bỏ validate chuyên đề theo `subject_id` của lớp → một lớp có thể chọn chuyên đề từ nhiều Danh mục.
- `CourseCycleService::teacherCourseDetail()` yêu cầu `class_id + student_id + cycle_id` → chỉ 1 lớp.
- `CourseCycleService::teacherUnlockNextTopic()` mở khóa theo số buổi, không theo test.
- `RequestService` chỉ nhận `add_session`, `request_leave`, `change_schedule`; `send_test` chỉ có trong danh mục hiển thị.
- Admin “Request test” đọc `db_lms_tests` status `draft`; không có màn tạo.
- Admin “Request thêm buổi” duyệt `add_session`; yêu cầu payload có ngày/giờ.
- `db_lms_tests` chưa có cột link lesson summary / review score.
- Chưa có bảng lưu 4 điểm Math IQ.
- Dev DB: cycle 1 (Toán Duy Kiên) không có `cycle_topics`; hv03/hv04 có 2 subject; topic 9 có 2 test, 1 bài nộp chưa chấm + 1 đã chấm.

---

## 9. Các điểm đã chốt

- Trang chi tiết là **theo học viên**, gộp nhiều lớp.
- 2 accordion chính luôn mở: **Lộ trình** và **Lesson summary & BTVN**.
- Accordion con Lộ trình = **Danh mục**, card = **Chuyên đề**.
- **Phương án B:** một lớp được chọn nhiều chuyên đề thuộc nhiều Danh mục; bỏ validate theo `subject_id`; bỏ fallback lấy toàn bộ chuyên đề của subject.
- Mở khóa chuyên đề sau khi chuyên đề trước **đã gửi test**.
- Thêm cột **Link Lesson summary chuyên đề sau** cho bài test.
- Modal “Thêm buổi” cần bổ sung ngày/giờ để request hợp lệ (chờ xác nhận cuối).

### 9.1. Dữ liệu mẫu đã seed (03/11/2026)

- Danh mục mới: `Math Problems`, `Math IQ`, `Reading`, `Writing`, `Listening`.
- Chuyên đề: How to calculate, Model Intermediate, IQ Type x, Đề 1, Đề 2, Reading Part 1/2, Writing Task 1, Listening Part 1; có `max_sessions`, `has_test/minitest`.
- Lớp 1 (Math 1-1): gán 5 chuyên đề thuộc 2 Danh mục; Lớp 2 (nhóm Toán): 2 chuyên đề; Lớp 3 (English): 4 chuyên đề thuộc 3 Danh mục.
- Test/minitest: How to calculate có test đã chấm 75%, test chưa chấm, minitest đã chấm 65%; Reading Part 1 có test draft cho Request test; lớp 2 có minitest chưa chấm.
- Buổi học lớp 1: 03/11 đã hoàn thành, 05/11 đã giao BTVN chưa chấm, 10/11 chưa diễn ra.
- Request: 1 `add_session` pending cho lớp 1/HV5.
- Cấp 1: có đủ nhãn Chưa giao / Chưa chấm / Đã chấm BTVN.

### 9.2. Lớp nhóm (triển khai 03/11/2026)

- Cấp 1 gom **1 thẻ/lớp nhóm** (không lặp theo từng học viên); thẻ link sang `chi-tiet-lop`.
- Trang `chi-tiet-lop` có 3 accordion:
  1. **Danh sách học viên**: Họ tên, Email, icon chat placeholder.
  2. **Lộ trình cấp lớp**: Danh mục → card chuyên đề X/Y + nút Thêm buổi (request add_session cho lớp).
  3. **Nội dung buổi học**: nội dung/BTVN dùng chung; danh sách nộp BTVN theo học viên, expand để xem bài nộp và chấm inline.
- Dùng lại `SessionService::gradeHomework`; form chấm POST về `cham-btvn-lop`.
- `RequestService` cho phép lớp nhóm tạo `add_session` với `student_id = null`; admin duyệt sẽ tạo session cho cả lớp.
- Chưa làm: sửa nội dung buổi học inline (đang link sang trang buổi học), chat thật, Math IQ cho lớp nhóm.

---

## 10. Phân kỳ đề xuất

### Phase 1 — Trang chi tiết (đã triển khai một phần, 03/11/2026)

- **[DONE]** 2 accordion cha luôn mở: Lộ trình và Lesson summary & BTVN.
- **[DONE]** Lộ trình group theo Danh mục; card hiển thị X/Y, điểm test/minitest, trạng thái khóa.
- **[DONE]** Lesson summary hiển thị buổi tuần hiện tại (hoặc 2 buổi gần nhất), trạng thái và link sang trang buổi học để nhập tóm tắt/chấm BTVN.
- **[DONE]** Bỏ bảng Test/Minitest riêng; điểm hiển thị trực tiếp trên card chuyên đề.
- **[CHƯA]** Modal Thêm buổi có ngày/giờ.
- **[CHƯA]** `teacherStudentOverview` gộp nhiều lớp theo học viên; hiện trang chi tiết vẫn theo lớp (một lớp có thể nhiều Danh mục).
- **[CHƯA]** Fallback `class_topics` ở trang chi tiết khi cycle chưa có topic.

### Phase 2 — Gửi test + mở khóa (đã triển khai 03/11/2026)

- **[DONE]** Nút `Gửi test` hiển thị khi chuyên đề 1-1 đã mở khóa, `has_test=1`, chưa có test.
- **[DONE]** Modal Request test: Điểm Review, Deadline, Lesson summary URL.
- **[DONE]** Tạo `db_lms_tests` draft (test + minitest nếu chuyên đề có minitest).
- **[DONE]** Trạng thái card: Gửi test → Đã gửi yêu cầu → Done - Request test; HS đã nộp hiển thị “chờ chấm”.
- **[DONE]** Admin tab Request test publish draft (UI có sẵn).
- **[DONE]** Mở khóa chuyên đề kế tiếp ngay sau khi gửi test (không cần đủ số buổi).
- **[DONE]** Migration `2026_11_03_lms_test_request_fields.sql` thêm `review_score`, `lesson_summary_url`.
- **[CHƯA]** Nút Chấm Test/Chấm Minitest + trang chấm giáo viên (Phase 3).
- **[CHƯA]** Nút Thêm buổi cho 1-1.

### Phase 3 — Chấm Test/Minitest (Admin) + Math IQ

- **[ĐÃ CHỐT]** Admin là người chấm Test/Minitest; Teacher không có màn Chấm Test riêng.
- Admin dùng tab **Duyệt KQ test** + trang chi tiết kết quả test (đã có) để nhập `X/20`, `Y%`, nhận xét, xác nhận.
- Teacher card: khi HS nộp → “Chờ Admin chấm”; sau khi Admin xác nhận → hiển thị điểm và trạng thái Done.
- **[CHƯA]** Math IQ: bảng `db_lms_exam_results` + form 4 điểm/báo cáo.

### Phase 4 — KET/English 3 tầng

- Chốt quy tắc program/nhóm kỹ năng; render 3 tầng hoặc chấp nhận phẳng.

---

## 11. Có thể làm module này sau không?

**Có.** Sau khi hoàn tất Phase 1 + Phase 2 (Gửi test) + luồng lớp nhóm, phần còn lại **có thể tạm hoãn** mà không ảnh hưởng:

- Admin: luồng Request test / Duyệt KQ test đã có sẵn.
- Student portal: không phụ thuộc trang chi tiết Teacher.
- Các module Teacher khác: Tổng quan, Quản lý lịch, Phí dịch vụ, Vi phạm, Thư viện, Nhận xét tháng, Chi tiết buổi học/BTVN.
- Cấp 1 và Cấp 2A/Cấp 2B hiện tại vẫn chạy được với dữ liệu đã gán.

### Ưu tiên khi quay lại

1. Gộp chi tiết theo học viên (nếu khách yêu cầu nhiều lớp trên một trang).
2. Thêm buổi cho 1-1.
3. Math IQ 4 điểm/báo cáo.
4. KET/English 3 tầng.
5. Sửa nội dung buổi học inline + chat thật.

**Khuyến nghị:** tạm chốt module ở trạng thái hiện tại, ưu tiên các module ít phụ thuộc nghiệp vụ hơn; khi khách trả lời 4 điều kiện ở §2.1 thì tiếp tục theo thứ tự trên.

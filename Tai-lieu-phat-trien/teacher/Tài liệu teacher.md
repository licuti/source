# Tài liệu chức năng Teacher — LMS "TRUNG TÂM NGOẠI NGỮ AROMA"

Tài liệu này mô tả chi tiết các màn hình, form, nút bấm và luồng nghiệp vụ dành cho vai trò **Giáo viên (Teacher)** trong hệ thống LMS Aroma, dựa trên các ảnh màn hình trong thư mục `Tai-lieu-phat-trien/teacher/`.

---

## 1. Tổng quan hệ thống Teacher

- **Tên dự án:** TRUNG TÂM NGOẠI NGỮ AROMA / "MasterStudy — Giáo viên riêng của bạn"
- **Số module sidebar (GV):** 8 — Tổng quan, Tài khoản giáo viên, Học viên của tôi, Quản lý lịch, Phí dịch vụ, Vi phạm chất lượng dịch vụ, Thư viện, Nhận xét theo tháng
- **Loại lớp GV dạy:** Lớp 1-1 (cá nhân) và Lớp đông/nhóm (Lớp online chung)
- **Chương trình có thể dạy:** Toán (Math Problems / Math IQ), Khoa học, Tiếng Anh (Listening/Reading/Writing/Speaking/Grammar/Vocabulary), KET, Tiếng Việt (lớp nhóm), v.v.

### 1.1. Bố cục chung mọi trang

| Khu vực | Mô tả |
|---|---|
| **Header (cam)** | Logo MasterStudy + khẩu hiệu "Giáo viên riêng của bạn" (góc trái); ô tìm kiếm (giữa); chuông thông báo có chấm đỏ + avatar tròn + tên "Nguyễn Văn Mười" (góc phải) |
| **Sidebar trái** | 8 mục chức năng. Mục đang active có nền cam nhạt (peach) |
| **Main content** | Nội dung chính của từng trang |
| **Footer (cam)** | Logo MasterStudy; liên kết "Về chúng tôi", "Tuyển dụng"; Địa chỉ: "181 Cao Thắng, Block B, Charmington La Pointe Building, Phường Hoà Hưng, TPHCM"; Liên hệ: 0903861248; nút **Chat** nổi góc phải |
| **Chat widget** | Nổi ở góc dưới phải, mở popup liệt kê cuộc hội thoại Shopee/Messenger-style |

### 1.2. Quy tắc chung

- Tất cả nút hành động chính dùng màu cam đậm (`#F26B2A`-style), bo nhẹ.
- Nút bị disable dùng nền xám nhạt.
- Trạng thái hiển thị dạng chữ màu:
  - **Đỏ** = cảnh báo / vi phạm / chưa hoàn thành / quá hạn
  - **Xanh lá** = đã hoàn thành / đúng hạn
  - **Vàng** = tổng tick thực tế
- Icon caret `˄` / `˅` để mở rộng/thu gọn section.
- Modal overlay làm mờ nền khi mở.

---

## 2. Tổng quan (Trang chủ)

Ảnh tham chiếu: `Tổng quan.png`, `Tổng quan - Thông báo.png`, `Tổng quan - Nút chat.png`

### 2.1. Cảnh báo phí dịch vụ (`Tổng quan - Thông báo.png`)
- Banner cam nhạt phía trên header (hoặc ngay dưới header): **"⚠ Cảnh báo: Bạn còn 2 tick nữa trước khi bị hạ phí dịch vụ"** + nút `×` để đóng.
- Hiển thị khi tổng tick vi phạm thực tế + số tick sắp tới chạm ngưỡng "Ngưỡng hạ mức phí dịch vụ" (xem mục Vi phạm).

### 2.2. Bố cục 3 cột (`Tổng quan.png`)
- **Cột trái — Sidebar:** Tổng quan (active, nền peach).
- **Cột giữa:**
  - **Buổi học sắp tới** — card hiển thị HV (vd: Duy Kiên 1117), ngày (vd: 23/07/2026), khung giờ (vd: 19:00-20:30), nhãn **"Sắp diễn ra"** (màu đỏ).
  - **Các học viên hiện tại** — lưới thẻ HV: avatar, tên + mã HV, lịch cố định (vd: "Thứ 2 19:00-20:30 / Thứ 4 19:00-20:30").
- **Cột phải — Thông báo:**
  - Thông báo **Admin** về tick phạt vì quên ghi Lesson summary → nút **"Ghi báo cáo buổi học"** (cam).
  - Thông báo từ HV đã nộp BTVN → nút **"Chấm bài"** (cam).
  - Thông báo Admin: "Bạn đã bị hạ phí dịch vụ" (text-only).

### 2.3. Chat widget (`Tổng quan - Nút chat.png`)
- Click nút **Chat** ở footer góc phải → popup chat hiện lên.
- Cột trái: danh sách cuộc hội thoại — mỗi mục có avatar, tên, tin nhắn cuối, ngày. Một số có nhãn trạng thái (vd: "Đã bị khóa", "Đóng lạnh").
- Cột phải: hội thoại đang chọn + ô nhập + các icon đính kèm (emoji, ảnh, file, contact, poll) + nút gửi.
- Tiêu đề popup: "Chat (N)" với số cuộc hội thoại.

---

## 3. Học viên của tôi

Ảnh tham chiếu: `Học viên của tôi - Lớp 1-1.png`, `Học viên của tôi - Danh sách lớp.png`

### 3.1. Danh sách HV/lớp 1-1 (`Học viên của tôi - Lớp 1-1.png`)
- Tiêu đề trang: **"Học viên của tôi"**.
- Mỗi học viên × lớp là một **card full-width** dùng chung cấu trúc HTML `.lmnx-card` với trang Student — Các khoá học / Nhận xét tháng (biến thể `.lmnx-card--avatar`), xếp danh sách dọc:
  - Ảnh đại diện HV bên trái; chưa có ảnh thì khung xám + icon tốt nghiệp.
  - Tên thẻ: lớp 1-1 hiển thị **tên học viên**; lớp nhóm hiển thị **tên lớp** (xem §3.2).
  - Dòng phụ: **khối + môn** (vd: "Lớp 5 - Toán").
  - Lịch cố định: mỗi khung giờ một dòng đầy đủ thứ + giờ bắt đầu - kết thúc (vd: "Thứ 2 19:00 - 20:30", "Thứ 4 19:00 - 20:30"); lớp chưa có lịch cố định hiển thị "—".
  - **Nhãn trạng thái BTVN** (góc phải dưới, chữ đỏ/xanh, in nghiêng):
    - **Chưa giao BTVN** (đỏ) — GV chưa giao bài (hoặc lớp chưa có BTVN nào).
    - **Chưa chấm BTVN** (đỏ) — HS đã nộp, GV chưa chấm.
    - **Đã chấm BTVN** (xanh) — đã hoàn tất chấm.
- Bấm vào bất kỳ vị trí nào trên thẻ → trang chi tiết khóa học của HV trong lớp đó.

### 3.2. Thẻ lớp nhóm (`Học viên của tôi - Danh sách lớp.png`)
- Cùng cấu trúc card `.lmnx-card--avatar` §3.1; tên thẻ là tên lớp (vd: "Toán Nhóm - Lớp 9-12", "Lớp online chung - G5 - 22").
- Dòng phụ: các khối của HV trong lớp + môn (vd: "Lớp 9, Lớp 10 - Toán Hình học").
- Avatar mặc định là icon nhóm trên nền xám.
- Lịch: có thể nhiều buổi/tuần, mỗi buổi một dòng (vd: "Thứ 2 19:30 - 21:00", "Thứ 4 19:30 - 21:00", "Thứ 6 08:00 - 09:30").
- Trạng thái BTVN tính theo từng cặp (học viên × lớp), không gộp cả lớp.

### 3.3. Click vào thẻ → Trang chi tiết (mục 6 & 8 bên dưới)
- Nguồn dữ liệu: `db_lms_cycle_students` + `db_lms_course_cycles` (cycle đang active).
- Lịch card dùng `MonthlyReviewService::scheduleRowsMap()`: ưu tiên `db_lms_cycle_schedules` đang hiệu lực, fallback `db_lms_schedules`.
- CSS `.lms-student-card*` cũ đã được thay bằng `.lmnx-card*` dùng chung; không còn class duplicate cho trang này.

> *Trạng thái triển khai (04/11/2026): đã chuyển danh sách “Học viên của tôi” sang HTML card dùng chung; test gv01 với lớp 1-1 và lớp nhóm, bấm thẻ mở đúng trang chi tiết.*

---

## 4. Quản lý lịch

Ảnh tham chiếu: `Quản lý lịch - Lịch tuần.png`, `Quản lý lịch - Modal Xin nghỉ.png`, `Quản lý lịch - Modal Đổi lịch cố định.png`, `Quản lý lịch - Modal Đăng ký thêm buổi học.png`

### 4.1. Form thông tin cá nhân (phía trên lịch)
| Trường | Loại | Mô tả |
|---|---|---|
| Địa chỉ hiện tại | Text | Địa chỉ nơi GV đang sinh sống |
| Các khu vực có thể dạy | Text | Hướng dẫn: "quận cũ, nếu thử đức thì hiện phường" (placeholder) |
| Có thể dạy online | Dropdown | "Không" / "Có" |
| Số lớp tối đa có thể nhận | Number | Giới hạn số lớp GV có thể nhận thêm |

### 4.2. Calendar tuần (`Quản lý lịch - Lịch tuần.png`)
- Tiêu đề tháng: "Tháng 7 / 2026" với nút `‹` `›` để chuyển tuần.
- Hai nút hành động phía trên calendar:
  - **Đăng ký thêm buổi học** (cam) — mở modal đăng ký lịch cho HV cụ thể.
  - **Đăng ký lịch trống** (cam) — mở form đăng ký các ô xanh.
- Cột: Thứ 2, Thứ 3, Thứ 4, Thứ 5, Thứ 6, Thứ 7, Chủ nhật (kèm ngày).
- Hàng: Khung giờ (7-8, 8-9, ...) cao `--lms-cal-hour-h` = 72px.
- Khung lưới dùng component chung `calendar_week`; mỗi thẻ trong ô (buổi học **hoặc** lịch trống) do component chung `calendar_week_event` render — giống Admin "Lịch học" và Student "Lịch học".
- **Thẻ định vị theo thời gian thật** trong cột (`--lms-cal-top` = phút lẻ của giờ bắt đầu, `--lms-cal-height` = thời lượng × 72px): buổi 17:30–19:00 bắt đầu nửa hàng 17:00 và kéo dài 108px (tận cuối ô 18:00); lịch trống 08:30–11:00 cao 180px.
- **Lịch trống là dải nền** (`.lms-cal-event--free`): full chiều ngang cột, `z-index: 1` nên thẻ buổi học (`z-index: 2`) nằm đè lên, viền đứt nét `#a3cfbb` không đổ bóng. Không còn tô cả ô bằng `.lms-cal-cell--free` vì dải đã phủ đúng thời lượng (08:30–11:00 = 180px qua 2,5 hàng).
- Khi hai **buổi học** chồng giờ nhau trong cùng cột ngày, `lms_cal_lanes_for_rows()` chia lane để chúng đứng cạnh nhau; lịch trống không tham gia chia lane.
- Header mỗi cột: trên là thứ, dưới là ngày/tháng — không lặp lại số thứ.
- Lịch trống đăng ký giờ cũng hiển thị thành thẻ xanh lá `Lịch trống` + khung giờ (trước đây chỉ tô nền ô, không có chữ).

### 4.3. Màu ô lịch
Thẻ mang màu đậm (`.lms-cal-event--*`), ô chỉ nền nhạt cùng tông (`.lms-cal-cell--*`).

| Màu | Ý nghĩa | Ghi chú |
|---|---|---|
| Đỏ đậm | Buổi học sắp tới | Có nút "Chỉnh lịch" dưới thẻ |
| Hồng đậm + ✗ | Buổi học thiếu BTVN | |
| Hồng nhạt | Buổi học đã hoàn thành | |
| Xám | Buổi học đã nghỉ | |
| Xanh lá | Lịch trống có thể nhận thêm lớp | Click để đăng ký |

### 4.4. Chú thích & Ghi chú
- Bảng chú thích (Chú thích) liệt kê 5 màu ô lịch như trên.
- **Ghi chú:** textarea lớn — GV ghi chú cho tuần/đợt dạy.
- **Cập nhật** (cam) — lưu tất cả thay đổi thông tin cá nhân + ghi chú.

### 4.5. Modal "Chỉnh lịch - [Tên HV]" (`Quản lý lịch - Modal Xin nghỉ.png`, `Quản lý lịch - Modal Đổi lịch cố định.png`)
- Mở khi click link "Chỉnh lịch" trên ô lịch đỏ.
- **Dropdown hành động** gồm các tuỳ chọn:
  - **"Xin nghỉ"** (`Quản lý lịch - Modal Xin nghỉ.png`):
    - Ngày xin nghỉ (date)
    - Lý do (text)
    - Ngày bù (không bắt buộc) (date)
    - Nút **Gửi**
  - **"Đổi lịch cố định"** (`Quản lý lịch - Modal Đổi lịch cố định.png`):
    - Buổi thứ nhất: chọn ngày + khung giờ
    - Buổi thứ hai: chọn ngày + khung giờ
    - Nút **Gửi**

### 4.6. Modal "Đăng ký thêm buổi học" (`Quản lý lịch - Modal Đăng ký thêm buổi học.png`)
- **Chọn học viên** (dropdown) — vd: Duy Kiên 1117
- **Ngày** (date) + **Khung giờ** (time range) — vd: 20/07/2026, 20:00-21:30
- **Ghi chú thêm** (text)
- Nút **Gửi**

---

## 5. Phí dịch vụ

Ảnh tham chiếu: `Phí dịch vụ.png`

### 5.1. Bộ lọc & tổng tiền
| Trường | Loại | Mô tả |
|---|---|---|
| Tổng tiền | Read-only | Hiển thị tổng phí (vd: 4,502,333 VND) |
| Chọn học viên | Dropdown | Lọc theo HV (vd: Duy Kiên 1117) |
| Chọn khoảng thời gian | Date range | Từ - Đến (vd: 2026-07-01 → 2026-07-31) |
| Tìm kiếm | Button cam | Thực hiện truy vấn |

### 5.2. Bảng giao dịch
| Cột | Mô tả |
|---|---|
| Ngày | Ngày + giờ giao dịch |
| Học viên | Tên HV (hoặc "-" cho giao dịch điều chỉnh) |
| Số tiền | Số dương `+X,XXX` (nhận) hoặc số âm `-X,XXX` (trừ) |
| Nội dung | Mô tả giao dịch, vd: "Bạn đã nhận được 243,000 đ khi đã hoàn thành bài học cho TDN1117.Duy Kiên", "Điều Chỉnh Tiền" |

---

## 6. Chi tiết học viên (lớp 1-1)

Ảnh tham chiếu:
- `Chi tiết học viên - Lộ trình và Buổi học.png`
- `Chi tiết học viên - Dropdown chuyên đề.png`
- `Chi tiết học viên - Trạng thái đã giao BTVN.png`
- `Chi tiết học viên - Trạng thái hoàn thành.png`
- `Chi tiết học viên - Math IQ.png`
- `Chi tiết học viên - Math IQ (2).png`
- `Chi tiết học viên - Modal Báo cáo chung đề thi.png`
- `Chi tiết học viên - Lộ trình Khoa học.png`
- `Chi tiết học viên - Lộ trình Tiếng Anh.png`
- `Chi tiết học viên - Lộ trình KET.png`
- `Chi tiết học viên - Modal Request thêm buổi.png`
- `Chi tiết học viên - Modal Request test.png`
- `Chi tiết học viên - Trạng thái đã nộp test.png`
- `Chi tiết học viên - Trạng thái đã nộp minitest.png`
- `Chi tiết học viên - Chấm Test và Minitest.png`

### 6.1. Header trang
- Tên HV (vd: "Duy Kiên 1117")
- Tên chương trình (vd: "Lộ trình KET")

### 6.2. Lộ trình (Section 1)
- Hiển thị các chương trình học mà HV đang theo (vd: "Math Problems", "Math IQ", "Khoa học", KET sub-skills: Reading/Writing/Listening, v.v.).
- Mỗi chương trình có thể mở rộng để xem các chuyên đề con.

### 6.3. Card chuyên đề (trong chương trình mở rộng)
Mỗi card hiển thị:
- Tên chuyên đề (vd: "How to calculate", "Model Intermediate")
- **Đã dạy:** X/Y buổi
- **Điểm test:** --% (đỏ, nếu chưa có)
- **Điểm minitest:** --% (đỏ, nếu chưa có)
- Nút hành động (tuỳ trạng thái):
  - **Gửi test** (cam) — Request gửi đề test cho HS (`Chi tiết học viên - Modal Request test.png`).
  - **Thêm buổi** (cam) — Yêu cầu thêm buổi học (`Chi tiết học viên - Modal Request thêm buổi.png`).
  - **Chấm Test** (cam, kích thước lớn, full-width) — khi HS đã nộp test (`Chi tiết học viên - Trạng thái đã nộp test.png`).
  - **Chấm Minitest** (cam) — khi HS đã nộp minitest (`Chi tiết học viên - Trạng thái đã nộp minitest.png`).
  - **Chưa mở khoá** (xám, disabled) — khi chưa đủ điều kiện.
  - **Done - Request test** (xám) — sau khi test đã giao xong.

- Ghi chú màu đỏ bên cạnh nút:
  - "*Nút này luôn hiện sẵn để GV bấm" (cho Gửi test, Thêm buổi).
  - "*Khi bấm nút này có thể mở khoá chuyên đề sau" (cho Gửi test).
  - "*Số buổi học giới hạn bởi admin" (cho thông số buổi).

### 6.4. Math IQ section (`Chi tiết học viên - Math IQ.png`, `Chi tiết học viên - Math IQ (2).png`, `Chi tiết học viên - Modal Báo cáo chung đề thi.png`)
- Tiêu đề phụ: "Giải đề thi tổng hợp các môn"
- Grid thẻ đề thi (vd: "Đề 1", "Đề 2", "Part 1", "Part 2"):
  - Mỗi đề có 4 trường điểm (màu đỏ nếu trống):
    - **Điểm trắc nghiệm**
    - **Điểm tự luận toán**
    - **Điểm tự luận tiếng anh**
    - **Overall**
  - Icon bút chì ✏ để mở modal "Báo cáo chung - Đề KET 1" (`Chi tiết học viên - Modal Báo cáo chung đề thi.png`): textarea + nút **Lưu**.

### 6.5. Lesson summary & BTVN (Section 2)
Mỗi buổi học là một card có thể mở rộng. Ví dụ: "Buổi học Thứ 2 21/7", "Buổi học Thứ 4 23/7" (hiển thị "Chưa diễn ra" nếu chưa tới).

**Trạng thái 1 — Form nhập BTVN mới (trước buổi học):** (`Chi tiết học viên - Dropdown chuyên đề.png`)
- Dropdown **"Chọn các chuyên đề*"** (multiple): liệt kê các chuyên đề đã mở khoá (vd: How to calculate, Model Intermediate, IQ group 5, Đề thi thử số 1).
- **Nội dung BTVN*** (textarea)
- **Deadline (chọn lịch, giờ)** (date+time picker)
- **Nhận xét buổi học** (textarea)
- **Tệp đính kèm** (upload button)
- Nút **Cập nhật** (cam) — lưu lesson summary + BTVN.
- Trạng thái: **"Chưa giao BTVN"** (đỏ).

**Trạng thái 2 — Sau khi giao, chưa chấm (`Chi tiết học viên - Trạng thái đã giao BTVN.png`):**
- Các trường chuyển sang read-only block (nền xám):
  - "How to calculate / IQ nhóm 5"
  - "Học 2 chuyên đề trên / BTVN: bài trong sách"
  - "Nhận xét buổi học: hôm nay học rất là nhanh"
  - "Tệp đính kèm"
- Nút lớn **Chấm BTVN** (cam) → mở giao diện chấm.
- Trạng thái: **"Chưa chấm BTVN"** (đỏ).

**Trạng thái 3 — Hoàn thành (`Chi tiết học viên - Trạng thái hoàn thành.png`):**
- Tất cả block read-only.
- Nút **Xem chi tiết** (cam) → xem lại toàn bộ.
- Trạng thái: **"Đã hoàn thành"** (xanh).

### 6.6. Modal "Request thêm buổi học" (`Chi tiết học viên - Modal Request thêm buổi.png`)
- Mở khi click nút **Thêm buổi** trên card chuyên đề.
- **Chuyên đề:** (readonly, vd: "How to calculate")
- **Lý do *:** (textarea bắt buộc)
- Nút **Gửi** (cam).

### 6.7. Modal "Request test - [Tên chuyên đề]" (`Chi tiết học viên - Modal Request test.png`)
- Mở khi click nút **Gửi test**.
- **Điểm Review*** (text)
- **Deadline*** (date + time, vd: 23/07/2026 19:00-20:30)
- **Lesson summary chuyên đề sau*** (input với placeholder "Gắn link dọc vào đây")
- Nút **Gửi** (cam).

### 6.8. Trang chấm Test/Minitest (`Chi tiết học viên - Chấm Test và Minitest.png`)
- Mở khi click **Chấm Test** hoặc **Chấm Minitest** trên card.
- Mỗi phần (Bài test chuyên đề / Minitest chuyên đề) gồm:
  - File PDF đề (`test-how-to-calculate.pdf`) với icon tải xuống.
  - **Bài nộp của học sinh:** 4 ô upload ảnh/file.
  - **Chấm điểm:** file grading template (vd: `test-how-to-calculate-grading-template.pdf`) + 2 ô nhập: `X/20` và `Y%`.
  - Ô upload file Excel kết quả (vd: `test-how-to-calculate-graded.xlsx`).
  - Ghi chú deadline đỏ: **"Hạn chấm: bạn còn 2 giờ để chấm"**.
  - Nút **Gửi** (cam).
- Card "How to calculate" ở đầu trang hiển thị "Mục tiêu then chốt" (textarea).
- Ghi chú deadline bên trái: **"Hạn chấm: bạn đã chấm trễ 2 phút"** (nếu quá hạn).

### 6.9. Chương trình khác
- **Khoa học** (`Chi tiết học viên - Lộ trình Khoa học.png`): Cấu trúc tương tự Math Problems, danh sách chuyên đề thuộc chương trình Khoa học.
- **Tiếng Anh** (`Chi tiết học viên - Lộ trình Tiếng Anh.png`): Lộ trình gồm 6 kỹ năng — Listening, Reading, Writing, Speaking, Grammar, Vocabulary.
- **KET** (`Chi tiết học viên - Lộ trình KET.png`): Lộ trình KET gồm Reading (Part 1, Part 2), Writing, Listening. Card chuyên đề KET có các trạng thái: "Done - Request test" (xám), "Chưa mở khoá" (xám).

> **Trạng thái triển khai (04/11/2026):**
> 1. **Trang chi tiết khóa học:** Đã có 2 accordion cha (Lộ trình / Lesson summary & BTVN). Lộ trình group theo Danh mục (Math Problems, Math IQ,...). Card chuyên đề hiển thị số buổi dạy X/Y + điểm test/minitest.
> 2. **Trạng thái hành động chuyên đề:**
>    - Khi chưa có test: nút cam **Gửi test** + nút **+ Buổi**.
>    - Khi học sinh đã nộp bài test hoặc minitest: nút cam **Chấm test** hoặc **Chấm Minitest** dẫn sang trang chấm test riêng (`/lms/teacher/lms-hoc-vien-cua-toi/cham-test`).
>    - Khi cả 2 bài test & minitest đã hoàn thành chấm điểm: hiển thị huy hiệu xanh lá **Đã hoàn thành**.
>    - Khi chưa đủ điều kiện: nút xám **Chưa mở khóa**.
> 3. **Màn hình Chấm Test & Minitest (`cham-test_tpl.php` - Chi tiết Chấm Test.png):** Tái sử dụng đồng bộ cấu trúc HTML/CSS với portal Student:
>    - Card 1: Tên chuyên đề, số buổi đã dạy, Mục tiêu then chốt (nội dung tóm tắt chuyên đề).
>    - Card 2 (Bài test chuyên đề) & Card 3 (Minitest chuyên đề): File đề bài PDF kèm icon tải xuống; lưới 5 ô hiển thị bài nộp của học sinh (xem file PDF/ảnh); form chấm điểm gồm ô nhập Điểm số (`X/20`), ô nhập Tỷ lệ (`Y%`), ô tải file nhận xét của GV; thông báo hạn chấm đếm ngược / trễ hạn; nút cam **GỬI** lưu trực tiếp vào `db_lms_test_submissions`.

---

## 7. Chi tiết buổi học (lớp 1-1)

Ảnh tham chiếu: `Chi tiết buổi học - Báo cáo và Chấm BTVN.png`

### 7.1. Section "Lesson summary & BTVN"
- Tiêu đề buổi: "Buổi học Thứ 2 21/7"
- Block read-only (khi đã giao):
  - "How to calculate / IQ nhóm 5"
  - "Học 2 chuyên đề trên / BTVN: bài trong sách"
  - "Nhận xét buổi học: hôm nay học rất là nhanh"
  - "Các tệp đính kèm"
- Ghi chú deadline: **"** Chỉ được sửa trước 12h trưa ngày kế tiếp"** (đỏ).
- Nút **Cập nhật** (cam) — lưu chỉnh sửa.

### 7.2. Section "Bài tập về nhà"
- **Bài nộp của học sinh:** 4 ô upload ảnh bài làm.
- Ghi chú deadline bên trái: **"Hạn chấm: bạn đã chấm trễ 2 phút"** (đỏ, nếu quá hạn).
- **Chấm bài:**
  - Textarea "Nhập nội dung...."
  - 4 ô upload file (mỗi ô có icon tải xuống)
  - "Đính kèm thêm file" (link)
- Ghi chú deadline đỏ: **"Hạn chấm: bạn còn 2 giờ để chấm"**.
- Nút **Gửi** (cam).

> **Trạng thái triển khai (06/11/2026 - `lms-lich-day/buoi?id=...`, đối chiếu `teacher-ver2/Trang Chi tiết buổi học.png`):**
> - Header hiển thị tên học viên (lớp 1-1) hoặc tên lớp (lớp nhóm) màu cam kèm link `Xem tất cả >` quay về chi tiết học viên/lớp.
> - Section 1 (`Lesson Summary & BTVN`): Card `Buổi học Thứ ...` với 5 pill nền xám (`Chọn các chuyên đề *`, `BTVN`, `Hạn nộp`, `Nhận xét buổi học`, `Tệp đính kèm`), dòng lưu ý đỏ in nghiêng `** Chỉ được sửa trc 12h trưa ngày kế tiếp` bên trái và nút cam **Cập nhật** bên phải (chỉ hiển thị cho giáo viên).
> - Section 2 (`Bài tập về nhà`): Hiển thị `Bài nộp của học sinh` (`file_grid`) và khung `Chấm bài` (`.lms-hw-compose-box` gồm textarea `Nhập nội dung....` + `form/file_upload`), dòng hạn chấm đỏ bên trái và nút cam **GỬI** bên phải. Khi GV gửi chấm bài, trạng thái chuyển sang `graded` (chờ Admin duyệt tại `lms-lop/buoi?id=...` để chuyển sang `approved` và hiển thị nhận xét cho học viên).

---

## 8. Chi tiết lớp nhóm (Lớp đông)

Ảnh tham chiếu: `Chi tiết lớp nhóm - Lộ trình và Danh sách học viên.png`, `Chi tiết lớp nhóm - Trạng thái đã giao BTVN.png`, `Chi tiết lớp nhóm - Danh sách nộp BTVN.png`, `Chi tiết lớp nhóm - Chấm BTVN từng học viên.png`

### 8.1. Header trang
- Tên lớp: "Lớp online chung - G5 - 22"

### 8.2. Section "Danh sách học viên" (`Chi tiết lớp nhóm - Lộ trình và Danh sách học viên.png`)
- Bảng cột: **Họ tên** | **Email** | **Hành động**.
- Ghi chú:
  - "** List hết học viên dưới đây"
  - "** icon chat với học viên" — mỗi hàng có icon chat mở messenger nội bộ.

### 8.3. Section "Lộ trình" (`Chi tiết lớp nhóm - Lộ trình và Danh sách học viên.png`)
- Tương tự lớp 1-1: cây chương trình → môn → chuyên đề.
- Card chuyên đề lớp nhóm có **1 nút "Thêm buổi"** (không có nút Gửi test).
- Vd: "Tiếng Việt — Đã dạy: 10/100 buổi".

### 8.4. Section "Nội dung buổi học" (`Chi tiết lớp nhóm - Lộ trình và Danh sách học viên.png`, `Chi tiết lớp nhóm - Trạng thái đã giao BTVN.png`)
- Mỗi buổi học là 1 card (vd: "Buổi học Thứ 2 21/7" + "Buổi học Thứ 4 23/7").
- **Form BTVN mới (trước buổi):** Chọn các chuyên đề* | Nội dung* | Nhận xét buổi học | Tệp đính kèm | **Cập nhật** | "Chưa giao BTVN".
- **Trạng thái BTVN đã giao, chưa chấm (`Chi tiết lớp nhóm - Trạng thái đã giao BTVN.png`):** Nút lớn **Chấm BTVN** | "Chưa chấm BTVN".

### 8.5. BTVN lớp nhóm (`Chi tiết lớp nhóm - Danh sách nộp BTVN.png`, `Chi tiết lớp nhóm - Chấm BTVN từng học viên.png`)
- Phần lesson summary chung ở trên (read-only sau khi giao, có "** Chỉ được sửa trước 12h trưa ngày kế tiếp" + Cập nhật).
- Danh sách thẻ BTVN từng HV (`Chi tiết lớp nhóm - Danh sách nộp BTVN.png`), mỗi thẻ:
  - "BTVN - [Tên HV]" (dropdown chevron)
  - Trạng thái: **Đã nộp bài** (xanh), **Chưa nộp bài** (đỏ), **Đã chấm** (xanh).
- Click expand (`Chi tiết lớp nhóm - Chấm BTVN từng học viên.png`):
  - **Bài nộp của học sinh:** 4 ô upload.
  - **Chấm bài:** textarea "Nhập nội dung...." + 4 ô upload file + "Đính kèm thêm file".
  - **"Hạn chấm: bạn còn 2 giờ để chấm"** (đỏ) + **Gửi**.
- Ghi chú deadline bên trái: **"Hạn chấm: bạn đã chấm trễ 2 phút"**.

> **Trạng thái triển khai (04/11/2026):**
> 1. **Trang chi tiết khóa học:** Đã có 2 accordion cha (Lộ trình / Lesson summary & BTVN). Lộ trình group theo Danh mục (Math Problems, Math IQ,...). Card chuyên đề hiển thị số buổi dạy X/Y + điểm test/minitest.
> 2. **Trạng thái hành động chuyên đề:**
>    - Khi chưa có test: nút cam **Gửi test** + nút **+ Buổi**.
>    - Khi học sinh đã nộp bài test hoặc minitest: nút cam **Chấm test** hoặc **Chấm Minitest** dẫn sang trang chấm test riêng (`/lms/teacher/lms-hoc-vien-cua-toi/cham-test`).
>    - Khi cả 2 bài test & minitest đã hoàn thành chấm điểm: hiển thị huy hiệu xanh lá **Đã hoàn thành**.
>    - Khi chưa đủ điều kiện: nút xám **Chưa mở khóa**.
> 3. **Màn hình Chấm Test & Minitest (`cham-test_tpl.php` - Chi tiết Chấm Test.png):** Tái sử dụng đồng bộ cấu trúc HTML/CSS với portal Student:
>    - Card 1: Tên chuyên đề, số buổi đã dạy, Mục tiêu then chốt (nội dung tóm tắt chuyên đề).
>    - Card 2 (Bài test chuyên đề) & Card 3 (Minitest chuyên đề): File đề bài PDF kèm icon tải xuống; lưới 5 ô hiển thị bài nộp của học sinh (xem file PDF/ảnh); form chấm điểm gồm ô nhập Điểm số (`X/20`), ô nhập Tỷ lệ (`Y%`), ô tải file nhận xét của GV; thông báo hạn chấm đếm ngược / trễ hạn; nút cam **GỬI** lưu trực tiếp vào `db_lms_test_submissions`.

---

## 9. Vi phạm chất lượng dịch vụ

Ảnh tham chiếu: `Vi phạm nội quy.png`

### 9.1. Thống kê tick (4 ô ngang)
| Trường | Mô tả |
|---|---|
| Số tick đã vi phạm | Tổng tick tích lũy (vd: 8) |
| Số tick được miễn | Tick được admin miễn (vd: 3) + icon `?` help |
| Tổng tick thực tế | Ô vàng = đã vi phạm - được miễn (vd: 5) |
| Ngưỡng hạ mức phí dịch vụ | Ngưỡng cảnh báo (vd: 8) — nếu tổng tick ≥ ngưỡng thì hạ phí |

### 9.2. Bảng vi phạm
| Cột | Mô tả |
|---|---|
| Ngày | Ngày + giờ vi phạm |
| Học viên | Tên HV liên quan |
| Số lượng tick | +X (cộng) hoặc -X (trừ, khi miễn) |
| Nội dung vi phạm | Mô tả, vd: "Không ghi báo cáo buổi học" |

### 9.3. Ghi chú
- "** Có chỉnh sách miễn giảm tick" — GV có thể yêu cầu miễn/giảm tick.

---

## 10. Thư viện

Ảnh tham chiếu: `Thư viện.png`
- **Route:** `/lms/teacher/lms-thu-vien` (Source: `lms/app/teacher/sources/lms-thu-vien.php`, Template: `lms/app/teacher/templates/lms-thu-vien/hienthi_tpl.php`)
- **Service:** `Lms\LibraryService::listFilesForTeacher()`, `canTeacherDownloadFile()`, `incrementDownload()`
- **Components sử dụng:** `form/input`, `button`, `badge`, `pagination`, `library_icon`

### 10.1. Tìm kiếm
- Ô tìm kiếm bên trái (`form/input`, `name="q"`, placeholder: `"Tìm tên tài liệu..."`) + nút **Tìm kiếm** bên phải (`button`, `variant="primary"` — màu cam).

### 10.2. Lưới file
Hiển thị lưới thẻ 5 cột trên màn hình lớn (`row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3`), mỗi file là 1 thẻ đứng gồm khung biểu tượng (`library_icon`) và tên tài liệu rút gọn (`text-overflow: ellipsis`):
| Tiền tố / Loại | Icon | Mô tả |
|---|---|---|
| `[Sách]` (`link`) | Link (icon dây xích đen 🔗) | Tài liệu sách/bài giảng liên kết |
| `[Review]` (`pdf`) | PDF (trang giấy nét đen có băng chữ `PDF` ở giữa) | Bài review / Tài liệu PDF |
| `[Test]` (`doc` / `xls`) | DOC (xanh dương `#0b66e4`) / XLS (xanh lá `#137333`) | Đề test / Bảng điểm (Word hoặc Excel) |
| `[Nội bộ]` (`internal`) | Trắng trống | Tài liệu nội bộ (không icon) |

### 10.3. Ghi chú phân quyền tải xuống (`is_downloadable`)
- `"** Admin có quyền chỉnh cho file đó có tải xuống được hay không"` — Giáo viên chỉ tải/mở được tài liệu khi `is_downloadable = 1` (thông qua `/lms/teacher/lms-thu-vien/tai?id={id}`).
- Khi `is_downloadable = 0`, thẻ hiển thị huy hiệu `badge` (`Chỉ xem` kèm icon ổ khoá), tắt thao tác bấm tải trên giao diện và backend chặn tải qua `LibraryService::canTeacherDownloadFile()`.

---

## 11. Nhận xét theo tháng

- **Route:** `GET /lms/teacher/lms-nhan-xet-thang` (Source: `lms/app/teacher/sources/lms-nhan-xet-thang.php`, Template: `lms/app/teacher/templates/lms-nhan-xet-thang/hienthi_tpl.php`)
- **Service:** `Lms\MonthlyReviewService::listClassesForTeacher()`, `teacherMonthOptions()`, `saveForTeacher()`
- **Mockup:** `Nhận xét theo tháng - Danh sách học viên.png`, `Nhận xét theo tháng - Form nhận xét lớp nhóm.png`, `teacher-ver2/Nhận xét theo tháng.png`

### 11.1. Danh sách card lớp/học viên
- Tiêu đề trang **"Nhận xét theo tháng"** dùng `.lms-page-title`.
- Dùng chung cấu trúc card với trang Student — Nhận xét tháng: `.lmnx-card`, `.lmnx-card-head`, `.lmnx-cover`, `.lmnx-summary`, `.lmnx-schedules`, `.lmnx-toggle`.
  - **Lớp 1-1:** tiêu đề là tên học viên; dòng phụ `{khối} - {môn}`; avatar học viên nếu có, fallback icon tốt nghiệp.
  - **Lớp nhóm:** tiêu đề là tên lớp; dòng phụ `{khối chung | Lớp nhóm} - {môn}`; avatar fallback icon nhóm.
  - Mỗi card hiển thị các dòng lịch cố định (`Thứ 2 19:00 - 20:30`), lấy từ `db_lms_cycle_schedules` active, fallback `db_lms_schedules`.
- Nút mũi tên bên phải mở/đóng form nhận xét (Bootstrap collapse).

### 11.2. Form nhận xét & nút Cập nhật
- Trong mỗi card có dropdown tháng (`.lmnx-month-select`): 12 tháng gần nhất + các tháng đã có nhận xét; đổi tháng reload trang với `month` + `open=class_id`.
- **Lớp 1-1:** một field nội dung nhận xét cho học viên.
- **Lớp nhóm:** nhiều field, mỗi học viên một field `reviews[student_id]` (đúng mockup Form nhận xét lớp nhóm).
- Nút **Cập nhật** (`.btn-lms`) gửi POST tới action `luu`; server lưu từng field và **đăng ngay** (`published=true`) để học viên thấy trên trang Nhận xét tháng.
- Field để trống sẽ bỏ qua; nếu tất cả trống, hiển thị lỗi yêu cầu nhập ít nhất một nhận xét.
- Form mới chỉ gửi `content`; nếu bản ghi đã có `overall_rating` / `progress_percent` thì service giữ nguyên, không xoá trắng.

> *Trạng thái triển khai (04/11/2026): đã dựng lại theo card dùng chung + nút Cập nhật; test gv01 với lớp 1-1 và 2 lớp nhóm; seed demo `database/seeds/2026_11_04_lms_teacher_review_demo.php`.*

---

## 12. Tài khoản giáo viên

- **Route:** `GET/POST /lms/teacher/lms-tai-khoan` (Source: `lms/app/teacher/sources/lms-tai-khoan.php`, Template: `lms/app/teacher/templates/lms-tai-khoan/hienthi_tpl.php`)
- **Service:** `Lms\UserService::getTeacher()`, `UserService::updateTeacherSelf()`
- **Mockup:** `Tai-lieu-phat-trien/teacher-ver2/Chi tiết GV.png`

### Layout
- Tiêu đề trang **“Tài khoản giáo viên”** dùng `.lms-page-title`.
- **Card hồ sơ tóm tắt:** avatar tròn (ảnh hoặc chữ cái đầu), họ tên, vai trò “Giáo viên”, email, số điện thoại và nút **Đổi Ảnh** (upload jpg/jpeg/png/webp, tối đa 1 file).
- **Thông tin cá nhân:** Họ tên*, Số điện thoại*, Email*, Ngày sinh, Giới tính, CCCD, Ngày cấp, Nơi cấp, Địa chỉ hiện tại, Quận/Phường (multi-select).
- **Thông tin giảng dạy:** Chương trình dạy (multi-select, note chọn nhiều môn), Số lớp tối đa, Có thể dạy online (Có/Không).
- **Thông tin thanh toán:** Tên ngân hàng, Số tài khoản, Chủ tài khoản + note “Thông tin tài khoản ngân hàng để nhận thanh toán từ Master Study.”
- Cuối trang: nút **Hủy** (quay lại trang) và **Lưu thay đổi** (cam).
- Dùng component chuẩn `form/input`, `form/date_picker`, `form/select`, `form/multi_select_tags`; card/section dùng `.lms-card` + `.lms-ta-*`.

### Nghiệp vụ
- Giáo viên chỉ sửa hồ sơ của chính mình (`teacher_id` lấy từ session, không nhận ID từ URL).
- Server whitelist đúng các trường trên; **không cho sửa** username, password, trạng thái, hạng, phí dịch vụ, cam kết 08, mã số thuế.
- Validate: họ tên bắt buộc; email/số điện thoại bắt buộc và đúng định dạng; email/CCCD không trùng giáo viên khác; ngày đúng `YYYY-MM-DD`; số lớp 0–50; môn dạy phải tồn tại; quận/phường thuộc TP.HCM.
- Avatar lưu ở `db_lms_teacher_profiles.avatar`; hồ sơ giảng dạy lưu `db_lms_teachers`; email/phone lưu `db_lms_users`.

> *Trạng thái triển khai (04/11/2026): đã thêm module/route/quyền; test gv01 cập nhật hồ sơ, validate email sai, upload avatar; đổi menu “Vi phạm chất lượng dịch vụ”.*

## 13. Phụ lục: Quy tắc deadline (rút ra từ các màn hình)

| Quy tắc | Chi tiết |
|---|---|
| **Deadline giao BTVN** | "** Chỉ được sửa trước 12h trưa ngày kế tiếp" — sau thời điểm này, lesson summary bị khoá. |
| **Hạn chấm BTVN** | "Hạn chấm: bạn còn 2 giờ để chấm" / "Hạn chấm: bạn đã chấm trễ 2 phút" — có thời gian đếm ngược / cảnh báo trễ. |
| **Tick phạt quên Lesson summary** | Hệ thống tự động cộng tick nếu GV không ghi lesson summary trong hạn → cảnh báo "Bạn đã bị phạt một tick". |
| **Hạ mức phí dịch vụ** | Khi tổng tick thực tế chạm "Ngưỡng hạ mức phí dịch vụ", GV bị hạ phí. Trước ngưỡng, banner cảnh báo "còn N tick nữa trước khi bị hạ phí". |
| **Đăng ký buổi học** | Click "Đăng ký thêm buổi học" → modal chọn HV + ngày + giờ. |

---

## 14. Phụ lục: So sánh 1-1 vs lớp nhóm

| Tính năng | Lớp 1-1 | Lớp nhóm |
|---|---|---|
| Lộ trình | Cây chương trình → chuyên đề | Cấu trúc tương tự |
| Card chuyên đề | Gửi test + Thêm buổi (riêng) | Chỉ có Thêm buổi |
| Test/Minitest | Có (Gửi test, Chấm Test, Chấm Minitest) | Không có |
| Lesson summary | 1 block riêng cho mỗi buổi | 1 block chung cho cả lớp |
| BTVN | 1 danh sách file cho 1 HV | Danh sách BTVN từng HV (dropdown expand) |
| Chấm bài | Trên trang chi tiết buổi học của HV | Mở rộng từng dòng BTVN HV trong trang lớp |
| Trang chi tiết | 1-1: xem chi tiết 1 HV | Nhóm: có thêm "Danh sách học viên" + chat từng HV |

---

## 15. Tổng kết các file ảnh và ánh xạ chức năng

| # | File ảnh mới | Tên file cũ | Màn hình / Chức năng | Mục |
|---|---|---|---|---|
| 1 | `Tổng quan.png` | `Tổng quan.png` / `Trang chủ.png` | Trang chủ Dashboard 3 cột | §2 |
| 2 | `Tổng quan - Thông báo.png` | `Tổng quan - Thông báo.png` / `2.png` | Cột thông báo & Banner cảnh báo hạ phí | §2.1 |
| 3 | `Tổng quan - Nút chat.png` | `Tổng quan - Nút chat.png` / `3.png` | Widget hội thoại Chat nổi | §2.3 |
| 4 | `Học viên của tôi - Lớp 1-1.png` | `Trang Các khóa học.png` | Danh sách học viên lớp 1-1 | §3.1 |
| 5 | `Học viên của tôi - Danh sách lớp.png` | `28.png` | Danh sách gồm lớp nhóm & lớp 1-1 | §3.2 |
| 6 | `Quản lý lịch - Lịch tuần.png` | `Quản lý lịch.png` | Lịch tuần, thông tin cá nhân & 5 trạng thái ô lịch | §4.2 |
| 7 | `Quản lý lịch - Modal Xin nghỉ.png` | `20.png` | Modal Chỉnh lịch → Tab "Xin nghỉ" | §4.5 |
| 8 | `Quản lý lịch - Modal Đổi lịch cố định.png` | `21.png` | Modal Chỉnh lịch → Tab "Đổi lịch cố định" | §4.5 |
| 9 | `Quản lý lịch - Modal Đăng ký thêm buổi học.png` | `22.png` | Modal đăng ký thêm buổi học cho học viên | §4.6 |
| 10 | `Phí dịch vụ.png` | `Phí dịch vụ.png` | Bảng kê giao dịch phí & Bộ lọc thời gian | §5 |
| 11 | `Chi tiết học viên - Lộ trình và Buổi học.png` | `Trang Chi tiết khóa học.png` | Chi tiết HV 1-1: Lộ trình & Lesson summary | §6.2 |
| 12 | `Chi tiết học viên - Dropdown chuyên đề.png` | `Trang Chi tiết khóa học (2).png` | Chi tiết HV 1-1: Dropdown chọn chuyên đề giao bài | §6.5 |
| 13 | `Chi tiết học viên - Trạng thái đã giao BTVN.png` | `Trang Chi tiết khóa học (3).png` | Chi tiết HV 1-1: Trạng thái BTVN đã giao, chưa chấm | §6.5 |
| 14 | `Chi tiết học viên - Trạng thái hoàn thành.png` | `Trang Chi tiết khóa học (4).png` | Chi tiết HV 1-1: Trạng thái buổi học hoàn thành | §6.5 |
| 15 | `Chi tiết học viên - Math IQ.png` | `14.png` | Chi tiết HV 1-1: Lộ trình Math IQ mở rộng | §6.4 |
| 16 | `Chi tiết học viên - Math IQ (2).png` | `17.png` | Chi tiết HV 1-1: Math IQ (biến thể / bản chụp 2) | §6.4 |
| 17 | `Chi tiết học viên - Modal Báo cáo chung đề thi.png` | `15.png` | Modal ghi báo cáo chung cho đề thi | §6.4 |
| 18 | `Chi tiết học viên - Lộ trình Khoa học.png` | `16.png` | Chi tiết HV 1-1: Lộ trình môn Khoa học | §6.9 |
| 19 | `Chi tiết học viên - Lộ trình Tiếng Anh.png` | `26.png` | Chi tiết HV 1-1: Lộ trình Tiếng Anh 6 kỹ năng | §6.9 |
| 20 | `Chi tiết học viên - Lộ trình KET.png` | `27.png` | Chi tiết HV 1-1: Lộ trình chứng chỉ KET | §6.9 |
| 21 | `Chi tiết học viên - Modal Request thêm buổi.png` | `Chi tiết Thêm buổi.png` | Modal gửi yêu cầu cấp thêm buổi học | §6.6 |
| 22 | `Chi tiết học viên - Modal Request test.png` | `Chi tiết Gửi test.png` | Modal gửi yêu cầu bài test cho học viên | §6.7 |
| 23 | `Chi tiết học viên - Trạng thái đã nộp test.png` | `Chi tiết khóa học - Đã nộp test.png` | Trạng thái hiển thị nút lớn "Chấm Test" | §6.3 |
| 24 | `Chi tiết học viên - Trạng thái đã nộp minitest.png` | `Chi tiết khóa học - Đã nộp minitest.png` | Trạng thái hiển thị nút "Chấm Minitest" | §6.3 |
| 25 | `Chi tiết học viên - Chấm Test và Minitest.png` | `Chi tiết Chấm Test.png` | Màn hình chấm điểm Test & Minitest chuyên đề | §6.8 |
| 26 | `Chi tiết buổi học - Báo cáo và Chấm BTVN.png` | `Trang Chi tiết buổi học.png` | Màn hình chi tiết buổi học, báo cáo & chấm BTVN 1-1 | §7 |
| 27 | `Chi tiết lớp nhóm - Lộ trình và Danh sách học viên.png` | `29.png` | Chi tiết lớp nhóm: Danh sách HV, Lộ trình, Buổi học | §8.2 |
| 28 | `Chi tiết lớp nhóm - Trạng thái đã giao BTVN.png` | `30.png` | Chi tiết lớp nhóm: Trạng thái BTVN đã giao | §8.4 |
| 29 | `Chi tiết lớp nhóm - Danh sách nộp BTVN.png` | `31.png` | Chi tiết lớp nhóm: Danh sách nộp bài từng HV | §8.5 |
| 30 | `Chi tiết lớp nhóm - Chấm BTVN từng học viên.png` | `32.png` | Chi tiết lớp nhóm: Form chấm BTVN cho từng HV | §8.5 |
| 31 | `Vi phạm nội quy.png` | `Vi phạm.png` | Vi phạm chất lượng dịch vụ: 4 ô thống kê tick & bảng phạt | §9 |
| 32 | `Thư viện.png` | `Thư viện.png` | Thư viện tài liệu số, tìm kiếm & phân quyền tải | §10 |
| 33 | `Nhận xét theo tháng - Danh sách học viên.png` | `33.png` | Danh sách HV/lớp cần nhận xét tháng | §11.1 |
| 34 | `Nhận xét theo tháng - Form nhận xét lớp nhóm.png` | `34.png` | Form nhập nhận xét tháng cho từng HV trong lớp nhóm | §11.2 |
| 35 | `teacher-ver2/Chi tiết GV.png` | — | Tài khoản giáo viên: hồ sơ, giảng dạy, thanh toán | §12 |

---

## Ghi chú về bộ ảnh thiết kế:

- Tên file ảnh gốc từng chứa nhiều mã số tự do (`14.png`, `15.png`... `34.png`) và một số tên nhầm lẫn (`Trang Các khóa học.png` thực chất là trang Học viên của tôi).
- Toàn bộ 34 file ảnh đã được chuẩn hóa lại theo tiền tố logic module: `Tổng quan - *`, `Học viên của tôi - *`, `Quản lý lịch - *`, `Chi tiết học viên - *`, `Chi tiết lớp nhóm - *`, `Nhận xét theo tháng - *`, `Phí dịch vụ.png`, `Vi phạm nội quy.png`, `Thư viện.png`.
- Tên mới giúp việc tra cứu và đối chiếu giữa mã nguồn và thiết kế Figma trở nên trực quan, chính xác và đồng bộ 100%.

# Tài liệu chức năng Teacher — LMS "TRUNG TÂM NGOẠI NGỮ AROMA"

Tài liệu này mô tả chi tiết các màn hình, form, nút bấm và luồng nghiệp vụ dành cho vai trò **Giáo viên (Teacher)** trong hệ thống LMS Aroma, dựa trên 33 ảnh PNG trong thư mục `teacher/`.

---

## 1. Tổng quan hệ thống Teacher

- **Tên dự án:** TRUNG TÂM NGOẠI NGỮ AROMA / "MasterStudy — Giáo viên riêng của bạn"
- **Số module sidebar (GV):** 6 — Tổng quan, Học viên của tôi, Quản lý lịch, Phí dịch vụ, Vi phạm nội quy, Thư viện
- **Loại lớp GV dạy:** Lớp 1-1 (cá nhân) và Lớp đông/nhóm (Lớp online chung)
- **Chương trình có thể dạy:** Toán (Math Problems / Math IQ), Khoa học, Tiếng Anh (Listening/Reading/Writing/Speaking/Grammar/Vocabulary), KET, Tiếng Việt (lớp nhóm), v.v.

### 1.1. Bố cục chung mọi trang

| Khu vực | Mô tả |
|---|---|
| **Header (cam)** | Logo MasterStudy + khẩu hiệu "Giáo viên riêng của bạn" (góc trái); ô tìm kiếm (giữa); chuông thông báo có chấm đỏ + avatar tròn + tên "Nguyễn Văn Mười" (góc phải) |
| **Sidebar trái** | 6 mục chức năng. Mục đang active có nền cam nhạt (peach) |
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

Ảnh: `Trang chủ.png`, `2.png`, `3.png`

### 2.1. Cảnh báo phí dịch vụ (`2.png`)
- Banner cam nhạt phía trên header (hoặc ngay dưới header): **"⚠ Cảnh báo: Bạn còn 2 tick nữa trước khi bị hạ phí dịch vụ"** + nút `×` để đóng.
- Hiển thị khi tổng tick vi phạm thực tế + số tick sắp tới chạm ngưỡng "Ngưỡng hạ mức phí dịch vụ" (xem mục Vi phạm).

### 2.2. Bố cục 3 cột
- **Cột trái — Sidebar:** Tổng quan (active, nền peach).
- **Cột giữa:**
  - **Buổi học sắp tới** — card hiển thị HV (vd: Duy Kiên 1117), ngày (vd: 23/07/2026), khung giờ (vd: 19:00-20:30), nhãn **"Sắp diễn ra"** (màu đỏ).
  - **Các học viên hiện tại** — lưới thẻ HV: avatar, tên + mã HV, lịch cố định (vd: "Thứ 2 19:00-20:30 / Thứ 4 19:00-20:30").
- **Cột phải — Thông báo:**
  - Thông báo **Admin** về tick phạt vì quên ghi Lesson summary → nút **"Ghi báo cáo buổi học"** (cam).
  - Thông báo từ HV đã nộp BTVN → nút **"Chấm bài"** (cam).
  - Thông báo Admin: "Bạn đã bị hạ phí dịch vụ" (text-only).

### 2.3. Chat widget (`3.png`)
- Click nút **Chat** ở footer góc phải → popup chat hiện lên.
- Cột trái: danh sách cuộc hội thoại (DANYCASE Viet..., ANENG, top1k.sale, cfycvn, turnbright1.vn, Coputa, VOVOVA-MALL, Guofpet) — mỗi mục có avatar, tên, tin nhắn cuối, ngày. Một số có nhãn trạng thái (vd: "Đã bị khóa", "Đóng lạnh").
- Cột phải: hội thoại đang chọn + ô nhập + các icon đính kèm (emoji, ảnh, file, contact, poll, GPKD) + nút gửi.
- Tiêu đề popup: "Chat (N)" với số cuộc hội thoại.

---

## 3. Học viên của tôi

Ảnh: `Trang Các khóa học.png` (file bị đặt tên sai, thực chất là trang này), `28.png`

### 3.1. Danh sách HV/lớp
- Tiêu đề trang: **"Học viên của tôi"**.
- Mỗi thẻ HV gồm:
  - Avatar sinh viên (hình mặc định xám).
  - Tên HV hoặc tên lớp nhóm (vd: "Duy Kiên 1117", "Lớp online chung - G5 - 22").
  - Lớp + môn (vd: "Lớp 5 - Toán", "Lớp 5 - Tiếng Việt").
  - Lịch cố định (vd: "Thứ 2 19:00-20:30" + "Thứ 4 19:00-20:30").
  - **Nhãn trạng thái BTVN** (góc phải):
    - **Chưa giao BTVN** (đỏ) — GV chưa giao bài.
    - **Chưa chấm BTVN** (đỏ) — GV đã giao, HS đã nộp, GV chưa chấm.
    - **Đã chấm BTVN** (xanh) — đã hoàn tất chấm.

### 3.2. Thẻ lớp nhóm (`28.png`)
- Tên lớp: "Lớp online chung - G5 - 22"
- Môn: "Lớp 5 - Tiếng Việt"
- Lịch: "Thứ 2 19:00-20:30" (chỉ 1 buổi/tuần)
- Trạng thái BTVN: "Chưa giao BTVN"

### 3.3. Click vào thẻ → Trang chi tiết (mục 6 & 7 bên dưới)

---

## 4. Quản lý lịch

Ảnh: `Quản lý lịch.png`, `20.png`, `21.png`, `22.png`

### 4.1. Form thông tin cá nhân (phía trên lịch)
| Trường | Loại | Mô tả |
|---|---|---|
| Địa chỉ hiện tại | Text | Địa chỉ nơi GV đang sinh sống |
| Các khu vực có thể dạy | Text | Hướng dẫn: "quận cũ, nếu thử đức thì hiện phường" (placeholder) |
| Có thể dạy online | Dropdown | "Không" / "Có" |
| Số lớp tối đa có thể nhận | Number | Giới hạn số lớp GV có thể nhận thêm |

### 4.2. Calendar tuần
- Tiêu đề tháng: "Tháng 7 / 2026" với nút `‹` `›` để chuyển tuần.
- Hai nút hành động phía trên calendar:
  - **Đăng ký thêm buổi học** (cam) — mở modal đăng ký lịch cho HV cụ thể.
  - **Đăng ký lịch trống** (cam) — mở form đăng ký các ô xanh.
- Cột: Thứ 2, Thứ 3, Thứ 4, Thứ 5, Thứ 6, Thứ 7, Chủ nhật (kèm ngày).
- Hàng: Khung giờ (7-8, 8-9, ...).

### 4.3. Màu ô lịch
| Màu | Ý nghĩa | Ghi chú |
|---|---|---|
| Đỏ đậm | Buổi học sắp tới | Có link "Chỉnh lịch" |
| Hồng đậm + ✗ | Buổi học thiếu BTVN | |
| Hồng nhạt | Buổi học đã hoàn thành | |
| Xám | Buổi học đã nghỉ | |
| Xanh lá | Lịch trống có thể nhận thêm lớp | Click để đăng ký |

### 4.4. Chú thích & Ghi chú
- Bảng chú thích (Chú thích) liệt kê 5 màu ô lịch như trên.
- **Ghi chú:** textarea lớn — GV ghi chú cho tuần/đợt dạy.
- **Cập nhật** (cam) — lưu tất cả thay đổi thông tin cá nhân + ghi chú.

### 4.5. Modal "Chỉnh lịch - [Tên HV]" (`20.png`, `21.png`)
- Mở khi click link "Chỉnh lịch" trên ô lịch đỏ.
- **Dropdown hành động** gồm các tuỳ chọn:
  - **"Xin nghỉ"** (`20.png`):
    - Ngày xin nghỉ (date)
    - Lý do (text)
    - Ngày bù (không bắt buộc) (date)
    - Nút **Gửi**
  - **"Đổi lịch cố định"** (`21.png`):
    - Buổi thứ nhất: chọn ngày + khung giờ
    - Buổi thứ hai: chọn ngày + khung giờ
    - Nút **Gửi**

### 4.6. Modal "Đăng ký thêm buổi học" (`22.png`)
- **Chọn học viên** (dropdown) — vd: Duy Kiên 1117
- **Ngày** (date) + **Khung giờ** (time range) — vd: 20/07/2026, 20:00-21:30
- **Ghi chú thêm** (text)
- Nút **Gửi**

---

## 5. Phí dịch vụ

Ảnh: `Phí dịch vụ.png`

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

Ảnh: `Trang Chi tiết khóa học.png`, `(2).png`, `(3).png`, `(4).png`, `14.png`, `15.png`, `16.png`, `17.png`, `26.png`, `27.png`, `Chi tiết Thêm buổi.png`, `Chi tiết Gửi test.png`, `Chi tiết khóa học - Đã nộp test.png`, `Chi tiết khóa học - Đã nộp minitest.png`, `Chi tiết Chấm Test.png`

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
  - **Gửi test** (cam) — Request gửi đề test cho HS.
  - **Thêm buổi** (cam) — Yêu cầu thêm buổi học.
  - **Chấm Test** (cam, kích thước lớn, full-width) — khi HS đã nộp test.
  - **Chấm Minitest** (cam) — khi HS đã nộp minitest.
  - **Chưa mở khoá** (xám, disabled) — khi chưa đủ điều kiện.
  - **Done - Request test** (xám) — sau khi test đã giao xong.

- Ghi chú màu đỏ bên cạnh nút:
  - "*Nút này luôn hiện sẵn để GV bấm" (cho Gửi test, Thêm buổi).
  - "*Khi bấm nút này có thể mở khoá chuyên đề sau" (cho Gửi test).
  - "*Số buổi học giới hạn bởi admin" (cho thông số buổi).

### 6.4. Math IQ section (`14.png`, `17.png`, `15.png`, `16.png`, `26.png`, `27.png`)
- Tiêu đề phụ: "Giải đề thi tổng hợp các môn"
- Grid thẻ đề thi (vd: "Đề 1", "Đề 2", "Part 1", "Part 2"):
  - Mỗi đề có 4 trường điểm (màu đỏ nếu trống):
    - **Điểm trắc nghiệm**
    - **Điểm tự luận toán**
    - **Điểm tự luận tiếng anh**
    - **Overall**
  - Icon bút chì ✏ để mở modal "Báo cáo chung - Đề KET 1" (`15.png`): textarea + nút **Lưu**.

### 6.5. Lesson summary & BTVN (Section 2)
Mỗi buổi học là một card có thể mở rộng. Ví dụ: "Buổi học Thứ 2 21/7", "Buổi học Thứ 4 23/7" (hiển thị "Chưa diễn ra" nếu chưa tới).

**Trạng thái 1 — Form nhập BTVN mới (trước buổi học):**
- Dropdown **"Chọn các chuyên đề*"** (multiple): liệt kê các chuyên đề đã mở khoá (vd: How to calculate, Model Intermediate, IQ group 5, Đề thi thử số 1).
- **Nội dung BTVN*** (textarea)
- **Deadline (chọn lịch, giờ)** (date+time picker)
- **Nhận xét buổi học** (textarea)
- **Tệp đính kèm** (upload button)
- Nút **Cập nhật** (cam) — lưu lesson summary + BTVN.
- Trạng thái: **"Chưa giao BTVN"** (đỏ).

**Trạng thái 2 — Sau khi giao, chưa chấm (`(3).png`):**
- Các trường chuyển sang read-only block (nền xám):
  - "How to calculate / IQ nhóm 5"
  - "Học 2 chuyên đề trên / BTVN: bài trong sách"
  - "Nhận xét buổi học: hôm nay học rất là nhanh"
  - "Tệp đính kèm"
- Nút lớn **Chấm BTVN** (cam) → mở giao diện chấm.
- Trạng thái: **"Chưa chấm BTVN"** (đỏ).

**Trạng thái 3 — Hoàn thành (`(4).png`):**
- Tất cả block read-only.
- Nút **Xem chi tiết** (cam) → xem lại toàn bộ.
- Trạng thái: **"Đã hoàn thành"** (xanh).

### 6.6. Modal "Request thêm buổi học" (`Chi tiết Thêm buổi.png`)
- Mở khi click nút **Thêm buổi** trên card chuyên đề.
- **Chuyên đề:** (readonly, vd: "How to calculate")
- **Lý do *:** (textarea bắt buộc)
- Nút **Gửi** (cam).

### 6.7. Modal "Request test - [Tên chuyên đề]" (`Chi tiết Gửi test.png`)
- Mở khi click nút **Gửi test**.
- **Điểm Review*** (text)
- **Deadline*** (date + time, vd: 23/07/2026 19:00-20:30)
- **Lesson summary chuyên đề sau*** (input với placeholder "Gắn link dọc vào đây")
- Nút **Gửi** (cam).

### 6.8. Trang chấm Test/Minitest (`Chi tiết Chấm Test.png`)
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

### 6.9. Chương trình khác (`16.png`, `26.png`, `27.png`)
- **Khoa học** (`16.png`): Cấu trúc tương tự Math Problems, danh sách chuyên đề thuộc chương trình Khoa học.
- **Tiếng Anh** (`26.png`): Lộ trình gồm 6 kỹ năng — Listening, Reading, Writing, Speaking, Grammar, Vocabulary.
- **KET** (`27.png`): Lộ trình KET gồm Reading (Part 1, Part 2), Writing, Listening. Card chuyên đề KET có các trạng thái: "Done - Request test" (xám), "Chưa mở khoá" (xám).

---

## 7. Chi tiết buổi học (lớp 1-1)

Ảnh: `Trang Chi tiết buổi học.png`

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

---

## 8. Chi tiết lớp nhóm (Lớp đông)

Ảnh: `29.png`, `30.png`, `31.png`, `32.png`

### 8.1. Header trang
- Tên lớp: "Lớp online chung - G5 - 22"

### 8.2. Section "Danh sách học viên" (`29.png`)
- Bảng cột: **Họ tên** | **Email** | **Hành động**.
- Ghi chú:
  - "** List hết học viên dưới đây"
  - "** icon chat với học viên" — mỗi hàng có icon chat mở messenger nội bộ.

### 8.3. Section "Lộ trình" (`29.png`)
- Tương tự lớp 1-1: cây chương trình → môn → chuyên đề.
- Card chuyên đề lớp nhóm có **1 nút "Thêm buổi"** (không có nút Gửi test).
- Vd: "Tiếng Việt — Đã dạy: 10/100 buổi".

### 8.4. Section "Nội dung buổi học" (`29.png`, `30.png`)
- Mỗi buổi học là 1 card (vd: "Buổi học Thứ 2 21/7" + "Buổi học Thứ 4 23/7").
- **Form BTVN mới (trước buổi):** Chọn các chuyên đề* | Nội dung* | Nhận xét buổi học | Tệp đính kèm | **Cập nhật** | "Chưa giao BTVN".
- **Trạng thái BTVN đã giao, chưa chấm (`30.png`):** Nút lớn **Chấm BTVN** | "Chưa chấm BTVN".

### 8.5. BTVN lớp nhóm (`31.png`, `32.png`)
- Phần lesson summary chung ở trên (read-only sau khi giao, có "** Chỉ được sửa trước 12h trưa ngày kế tiếp" + Cập nhật).
- Danh sách thẻ BTVN từng HV, mỗi thẻ:
  - "BTVN - [Tên HV]" (dropdown chevron)
  - Trạng thái: **Đã nộp bài** (xanh), **Chưa nộp bài** (đỏ), **Đã chấm** (xanh).
- Click expand (`32.png`):
  - **Bài nộp của học sinh:** 4 ô upload.
  - **Chấm bài:** textarea "Nhập nội dung...." + 4 ô upload file + "Đính kèm thêm file".
  - **"Hạn chấm: bạn còn 2 giờ để chấm"** (đỏ) + **Gửi**.
- Ghi chú deadline bên trái: **"Hạn chấm: bạn đã chấm trễ 2 phút"**.

---

## 9. Vi phạm nội quy

Ảnh: `Vi phạm.png`

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

Ảnh: `Thư viện.png`

### 10.1. Tìm kiếm
- Ô tìm kiếm + nút **Tìm kiếm** (cam).

### 10.2. Lưới file
Mỗi file là 1 thẻ với icon loại + tên rút gọn. Các loại:
| Tiền tố | Icon | Mô tả |
|---|---|---|
| [Sách] | Link (icon dây xích) | Tài liệu sách/bài giảng |
| [Review] | PDF | Bài review |
| [Test] | DOC/XLS (xanh) | Đề test (Word hoặc Excel) |
| [Nội bộ] | Trắng trống | File nội bộ (không icon) |

### 10.3. Ghi chú
- "** Admin có quyền chỉnh cho file đó có tải xuống được hay không" — GV chỉ tải được nếu admin cho phép.

---

## 11. Nhận xét theo tháng (mở rộng)

Ảnh: `33.png`

### 11.1. Danh sách HV/lớp
- Tiêu đề: **"Nhận xét theo tháng"**.
- Mỗi thẻ HV/lớp: avatar + tên + lớp/môn + lịch học.
- Icon mũi tên xuống `˅` bên phải → click để mở form nhận xét tháng (cấu trúc tương tự nhận xét tháng của học sinh: điểm mạnh, điểm yếu, nhận xét, khuyến nghị).

---

## 12. Phụ lục: Quy tắc deadline (rút ra từ các màn hình)

| Quy tắc | Chi tiết |
|---|---|
| **Deadline giao BTVN** | "** Chỉ được sửa trước 12h trưa ngày kế tiếp" — sau thời điểm này, lesson summary bị khoá. |
| **Hạn chấm BTVN** | "Hạn chấm: bạn còn 2 giờ để chấm" / "Hạn chấm: bạn đã chấm trễ 2 phút" — có thời gian đếm ngược / cảnh báo trễ. |
| **Tick phạt quên Lesson summary** | Hệ thống tự động cộng tick nếu GV không ghi lesson summary trong hạn → cảnh báo "Bạn đã bị phạt một tick". |
| **Hạ mức phí dịch vụ** | Khi tổng tick thực tế chạm "Ngưỡng hạ mức phí dịch vụ", GV bị hạ phí. Trước ngưỡng, banner cảnh báo "còn N tick nữa trước khi bị hạ phí". |
| **Đăng ký buổi học** | Click "Đăng ký thêm buổi học" → modal chọn HV + ngày + giờ. |

---

## 13. Phụ lục: So sánh 1-1 vs lớp nhóm

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

## 14. Tổng kết các file ảnh đã tài liệu hoá

| # | File ảnh | Màn hình/Form | Mục |
|---|---|---|---|
| 1 | Trang chủ.png | Tổng quan (3 cột) | §2 |
| 2 | 2.png | Tổng quan + banner cảnh báo | §2.1 |
| 3 | 3.png | Tổng quan + chat widget | §2.3 |
| 4 | Trang Các khóa học.png | Học viên của tôi (tên file sai) | §3 |
| 5 | 28.png | Học viên của tôi (có lớp nhóm) | §3.2 |
| 6 | Quản lý lịch.png | Lịch tuần + 5 trạng thái | §4 |
| 7 | 20.png | Modal Chỉnh lịch → Xin nghỉ | §4.5 |
| 8 | 21.png | Modal Chỉnh lịch → Đổi lịch cố định | §4.5 |
| 9 | 22.png | Modal Đăng ký thêm buổi học | §4.6 |
| 10 | Phí dịch vụ.png | Phí DV + bảng giao dịch | §5 |
| 11 | Trang Chi tiết khóa học.png | Lộ trình + Lesson summary | §6.2, §6.5 |
| 12 | Trang Chi tiết khóa học (2).png | Biến thể dropdown chuyên đề | §6.5 |
| 13 | Trang Chi tiết khóa học (3).png | Trạng thái BTVN đã giao | §6.5 |
| 14 | Trang Chi tiết khóa học (4).png | Trạng thái hoàn thành | §6.5 |
| 15 | 14.png | Math IQ mở rộng | §6.4 |
| 16 | 15.png | Modal Báo cáo chung | §6.4 |
| 17 | 16.png | Chương trình Khoa học | §6.9 |
| 18 | 17.png | Trùng 14.png (Math IQ) | §6.4 |
| 19 | 26.png | Chương trình Tiếng Anh | §6.9 |
| 20 | 27.png | Lộ trình KET | §6.9 |
| 21 | Chi tiết Thêm buổi.png | Modal Request thêm buổi | §6.6 |
| 22 | Chi tiết Gửi test.png | Modal Request test | §6.7 |
| 23 | Chi tiết khóa học - Đã nộp test.png | Trạng thái sau nộp test | §6.3 |
| 24 | Chi tiết khóa học - Đã nộp minitest.png | Trạng thái sau nộp minitest | §6.3 |
| 25 | Chi tiết Chấm Test.png | Trang chấm Test + Minitest | §6.8 |
| 26 | Trang Chi tiết buổi học.png | Chi tiết buổi học 1-1 | §7 |
| 27 | 29.png | Chi tiết lớp nhóm (mở rộng) | §8 |
| 28 | 30.png | Lớp nhóm trạng thái BTVN đã giao | §8.4 |
| 29 | 31.png | BTVN lớp nhóm (danh sách HV) | §8.5 |
| 30 | 32.png | Chấm BTVN 1 HV trong lớp nhóm | §8.5 |
| 31 | Vi phạm.png | Vi phạm + 4 ô tick | §9 |
| 32 | Thư viện.png | Thư viện (4 loại file) | §10 |
| 33 | 33.png | Nhận xét theo tháng | §11 |

**Tổng: 33/33 ảnh đã được mô tả đầy đủ.**

---

## Ghi chú đặc biệt

- File `Trang Các khóa học.png` thực chất chứa nội dung trang **Học viên của tôi** (không phải trang Các khóa học). Trang danh sách khoá học của teacher có lẽ được truy cập từ nút trên thẻ HV/lớp (chưa có ảnh riêng).
- File `Học viên của tôi.png` (tên mong đợi) không tồn tại trong thư mục gốc; thay vào đó nội dung nằm trong 2 ảnh `Trang Các khóa học.png` (lớp 1-1) và `28.png` (có thêm lớp nhóm).
- Ảnh `14.png` và `17.png` trùng nội dung.
- Ảnh `29.png` và `30.png` cùng là trang lớp nhóm, khác trạng thái BTVN.

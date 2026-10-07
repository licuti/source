# Tài liệu Admin - TRUNG TÂM NGOẠI NGỮ AROMA

## 1. Tổng quan (Dashboard)

### Layout
- **Header**: Logo "MasterStudy - Giáo viên riêng của bạn" (góc trái), thanh tìm kiếm (giữa), chuông thông báo + avatar + tên người dùng "Nguyễn Văn Mười" (góc phải)
- **Sidebar** (trái, các mục chính):
  - Tổng quan
  - Quản lý tài khoản
  - Quản lý lớp học
  - Học sinh - Lớp học
  - Quản lý BTVN
  - Quản lý vi phạm
  - Quản lý yêu cầu
  - Quản lý lộ trình
  - Quản lý nội quy
  - Quản lý thư viện
  - Thống kê phí dịch vụ
  - Thống kê số buổi học
  - Sắp xếp lớp

### Nội dung chính
**4 thẻ thống kê (Cards):**
- Học sinh đang active: 100
- Giáo viên đang active: 46
- Lớp học đang active: 100
- Học sinh chờ xếp: 40

**Bảng "Thống kê nhóm chương trình"** (cột: STT | Nhóm chương trình | Số lớp đang học | Số lớp đang sắp xếp | Số lớp tạm nghỉ):
- 1. Toán TDN 1-1 — 40 | 3 | 0
- 2. TDN Khác — 15 | 5 | 0
- 3. Tiếng Anh 1-1 — 10 | 2 | 0
- 4. Tiếng Việt 1-1 — 9 | 1 | 0
- 5. Online đông người miễn phí — 7 | 2 | 0
- 6. Online đông người có phí — 4 | 2 | 0
- 7. Toán 1-1 — 15 | 1 | 0

**Lịch tuần này:**
- Lịch tuần dạng timeline 7 cột (Thứ 2 → Chủ nhật) với khung giờ từ 1:00 đến 24:00
- Mỗi ô hiển thị `[Mã lớp] + giờ bắt đầu - giờ kết thúc` (nếu nhiều lớp cùng khung giờ sẽ hiển thị con số trên góc phải)
- Click vào ô lịch sẽ mở popup chi tiết:
  - Tiêu đề: `Thứ X, dd/mm/yyyy HH:mm-HH:mm`
  - Dòng "N lớp:"
  - Danh sách các dòng: `[Mã lớp thứ i] - [Tên giáo viên]` (hyperlink) + `Sĩ số: X`
- Chú thích màu: xanh dương = Lớp 1-1, cam = Lớp đông người
- Cột "Số lớp tạm nghỉ" trong một số phiên bản hiển thị là "Số lớp đã nghỉ"

## 2. Học sinh - Lớp học

### Layout
- 2 tab: **Lớp đông người** (mặc định) | **Lớp 1 kèm 1**
- 2 ô tìm kiếm: "Tìm theo HV" + "Tìm theo lớp" + nút **Tìm**
- Bảng danh sách học sinh

### Cột bảng
- Username
- Họ tên
- Khối
- **Mã lớp (chia theo chương trình học)** — nhóm các cột con, hiện các lớp theo từng chương trình:
  - Tiếng Anh
  - Khoa học
  - Văn-Sử-Địa
  - (Có thể mở rộng thêm cột chương trình mới như Toán, ...)

### Ghi chú yêu cầu
- Hiện tất cả lớp đông người, nhưng **chia theo chương trình học**, mỗi chương trình là một cột. VD hiện tại có 3 chương trình phổ biến (Tiếng Anh, Khoa học, Văn-Sử-Địa) → 3 cột; nếu sau thêm chương trình Toán cho lớp đông người thì phải hiện thêm 1 cột Toán nữa (thành 4 cột).
- Trong tài khoản học sinh: list ra các lớp đang học, thêm thao tác **xoá** và **thêm lớp** ngay tại đó
- Phân ra chỗ xem danh sách lớp online chung, lịch, giáo viên, số lượng học sinh, hay lớp 1-1
- Làm lịch thời khoá biểu cho 2 loại (lớp đông người & lớp 1-1) cho dễ nhìn

### Tab "Lớp 1 kèm 1"
- Cấu trúc giống tab Lớp đông người, chỉ khác dữ liệu là lớp 1-1
- Cột chương trình học có thể khác (VD: Tiếng Anh TĐN, Toán TĐN, Tiếng Anh chứng chỉ,...)

## 3. Quản lý lớp học

### Bộ lọc tìm kiếm
- Tìm theo lớp
- Tìm theo GV
- Tìm theo chương trình học (cho chọn nhiều)
- Tìm theo TG (tìm theo thời gian học)
- Nút **Tìm**
- Nút **Điều chỉnh số buổi học**
- Nút **Thêm**

### Cột bảng
| Cột | Mô tả |
|---|---|
| Mã lớp | Mã lớp |
| Chương trình học | VD: Toán TDN, Tiếng Anh TDN, Tiếng Anh miễn phí, Tiếng Anh chứng chỉ, Văn sử địa |
| Sĩ số | Số học sinh |
| Thời gian | Khung giờ cố định trong tuần (VD: T3 19:00 - 20:30 / T5 19:00 - 20:30) |
| Giáo viên | Mã GV + tên (VD: TE125 [tên giáo viên]) |
| Khóa học | Liệt kê các khoá (tối đa 2 khoá gần nhất). Mỗi khoá có: Bắt đầu, Kết thúc, Đã học, Còn lại |
| Chuyên đề đang học | Tên chuyên đề (How to calculate, Ratio, Fraction, Lộ trình KET, Lộ trình lớp online chung,...) |
| Hành động | Sửa / Lịch học / Thêm khoá học / Xoá |

### Ghi chú logic Khóa học
- Các ngày bắt đầu và kết thúc cũ chạy theo số buổi học của từng khoá, **không cần admin nhập tay**, chỉ cần ngày bắt đầu khoá 1 mới cần nhập
- Ô khoá học chỉ hiện **2 khoá gần nhất**

### Phân trang
- Hiển thị: « 1 2 3 4 5 6 7 8 9 ... 104 105 » (có prev/next)

### Form "Lớp học mới"

**Danh sách Nhóm chương trình** (dropdown): Toán TDN 1-1, TDN Khác, Tiếng Anh 1-1, Tiếng Việt 1-1, Online đông người miễn phí, Online đông người tính phí, Toán THCS 1-1

**Các trường form:**
| Trường | Bắt buộc | Mô tả |
|---|---|---|
| Nhóm chương trình | * | Dropdown |
| Chương trình học | * | Dropdown |
| Loại lớp | * | Dropdown (1 kèm 1 / đông người) |
| Mã lớp | * | Text (VD: Toán 227) |
| Chọn giáo viên | * | Dropdown (Mã GV - Tên GV) |
| Chọn học viên | * | Dropdown (cho phép chọn nhiều nếu là lớp đông người) |
| Lịch cố định | * | Danh sách động gồm (Thứ + giờ bắt đầu - giờ kết thúc), có nút Xoá/Thêm từng dòng |
| Học phí 1 buổi | * | Số + đơn vị "đồng" (VD: 560000) |
| Phần trăm GV nhận | * | Số + đơn vị "%" (VD: 48). **Hiện theo số mặc định trong tài khoản GV, nhưng vẫn cho phép chỉnh ở đây (không ảnh hưởng tới tài khoản)** |
| Áp dụng từ ngày | * | Date picker |
| Ghi chú | | Textarea |
| Chọn chuyên đề | | Dropdown (cho phép chọn nhiều chuyên đề) |

**Section "Đăng ký khoá đầu":**
- Số buổi: số + "buổi"
- Ngày bắt đầu: date picker

**Section "Cài đặt" (mặc định cho lớp 1-1):**
| Mục | Toggle | Giá trị | Ghi chú |
|---|---|---|---|
| Hạn giao BTVN | Có | Giờ (VD: 12:00) — của ngày hôm sau | + "Phạt GV" + "1 tick" |
| Hạn check BTVN | Có | Số giờ trước buổi tiếp theo (VD: 0) | **chỉ áp dụng nếu hs có nộp BTVN trước deadline GV giao** + "Phạt GV" + "1 tick" |
| Hạn chấm test | Có | Số giờ kể từ khi hs nộp bài (VD: 72) | + "Phạt GV" + "1 tick" |

**Mặc định cho lớp đông người (ghi chú):**
- hạn giao BTVN: 12:00
- hạn check BTVN: 24 giờ

**Nút hành động:** Thêm

### Chi tiết buổi học (truy cập từ "Lịch học" hoặc QL BTVN)

**Header:** `Buổi học [Thứ X dd/M]` | `[Mã HV - Tên HV]` | `[Mã GV - Tên GV]`

**Section 1 — Thông tin buổi học:**
- Tên chuyên đề / nhóm (VD: How to calculate, IQ nhóm 5)
- Ghi chú nội dung buổi học (VD: "Học 2 chuyên đề trên")
- BTVN: (VD: "bài trong sách")
- Nhận xét buổi học: textarea (VD: "hôm nay học rất là nhanh")
- Các tệp đính kèm: danh sách file
- Nút **Cập nhật**

**Section 2 — Bài tập về nhà:**
- **Bài nộp của học sinh**: danh sách 4 thumbnails/file đính kèm
- **Chấm bài**: textarea "Nhập nội dung..." + danh sách file đính kèm (4 ô) + nút **Đính kèm thêm file**
- Nút **Cập nhật**

## 4. Modal "Chi tiết lịch giáo viên" (từ Sắp xếp lớp)

**Tiêu đề:** `Chi tiết lịch giáo viên - [Mã GV] - [Tên GV]`

**Thông tin giáo viên:**
- Địa chỉ hiện tại
- Quận / Phường có thể dạy
- Số lớp đang dạy
- Số lớp tối đa có thể nhận
- Có thể dạy online không

**Lịch tuần (timeline 7 cột × 1:00 - 24:00):**
- Ô màu hồng: **Lịch cố định của lớp đang dạy** (hiển thị Mã HV - Tên HV + khung giờ)
- Ô màu xanh lá: **Lịch trống có thể nhận thêm lớp**
- *Bấm vào tên học viên sẽ nhảy qua trang chi tiết của học viên*

**Ghi chú** (textarea) + Nút **Cập nhật**

## 5. Quản lý sắp xếp lớp

> *Khi nhập username học viên thì các thông tin còn lại sẽ tự động điền theo thông tin tài khoản học viên, nhưng admin vẫn có thể chỉnh sửa các tiêu chí (nhưng ko ảnh hưởng thông tin tài khoản)*

### Block 1: "Tìm kiếm theo tiêu chí" (toggle ON, *có tìm theo tiêu chí này ko)
- Username học viên (text)
- Giới tính (dropdown: Nữ,...)
- Chương trình học (VD: Toán TĐN, cho chọn nhiều tag: Quận 1, Quận 3, Quận 5,...)
- Online / Offline (dropdown)
- Nút **Tìm**

### Block 2: "Tìm theo lịch trống" (toggle ON)
- Lịch cố định: danh sách (Thứ + giờ bắt đầu - giờ kết thúc), mỗi dòng có nút X để xoá
- Thêm ngày (link)
- Thêm giờ (link)
- *Nếu xoá hết khung giờ, thì mặc định tìm theo tất cả các khung giờ của ngày đó*
- Nút **Tìm**

### Block 3: "Các giáo viên phù hợp" (Tổng: N)
- **Tìm kiếm theo tiêu chí**: Họ tên/username (text) | Giới tính (Nữ) | Chương trình (VD: Toán TĐN — Quận 1, Quận 3) | Trạng thái (Đang dạy) | Nút Tìm
  - **Ghi chú**: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác (được thêm)
- **Sắp xếp** (dropdown): Sắp xếp theo hạng giảm dần / theo hạng tăng dần / theo cập nhật gần nhất / theo cập nhật xa nhất

**Bảng kết quả** (cột):
- Username
- Họ tên
- Giới tính
- Hạng (Đồ / Xanh / Vàng — có badge màu)
- Chương trình học
- Quận / Phường có thể dạy
- Số lớp đang dạy
- Trạng thái (Đang dạy / Tạm ngưng)
- Lần cuối cập nhật
- Hành động: **Chi tiết** (mở modal lịch GV) / **Xoá**

## 6. Quản lý tài khoản

### Tabs
- **Tài khoản học viên** (mặc định)
- **Tài khoản giáo viên**

### Tab "Tài khoản học viên"

> *Cột số ưu tiên và trạng thái được chỉnh nhanh tại đây luôn*

**Bộ lọc:**
- Mã / Tên học viên (text)
- Chương trình học (dropdown)
- Nút **Tìm**
- Nút **Thêm**

Hiển thị: `Tổng: 100 học sinh`

**Bảng** (cột):
| Cột | Ghi chú |
|---|---|
| Số ưu tiên | filter icon — chỉnh nhanh inline |
| Username | |
| Chương trình học | VD: Toán TĐN, Toán THCS, Tiếng Anh, KET, PET |
| Lịch học hiện tại | **Hiển thị lịch học của tất cả các lớp học sinh đang theo học** |
| Giáo viên 1-1 | **Hiển thị giáo viên đảm nhiệm lớp 1-1 học sinh đang theo** |
| Ghi chú | VD: phụ huynh dễ, phụ huynh khó tính |
| Trạng thái | filter icon — chỉnh nhanh inline (Đang sắp xếp / Đang học / Nghỉ học) |
| Chờ sắp xếp từ | filter icon |
| Ngày dự kiến bắt đầu học | filter icon |
| Hành động | Chi tiết / Sửa / Khoá |

**Phân trang:** « 1 2 3 4 5 6 7 8 9 ... 104 105 »

### Trang "Chi tiết học viên"

> Các trường `Số ưu tiên` và `User code` hiển thị ở chế độ **Only số** (read-only).

**Form chi tiết** (các trường có `*` là bắt buộc):

| Trường | Bắt buộc | Mô tả / placeholder |
|---|---|---|
| Số ưu tiên | | **Only số** (VD: 1) — read-only |
| Họ tên học viên | * | VD: Hà Phương |
| Username | * | VD: haphuong227 |
| Password | * | VD: 12345678 |
| User code | | **Only số** (VD: Master227) — read-only |
| Email | * | VD: haphuong@gmail.com |
| Họ tên phụ huynh | | VD: mai phương |
| Số điện thoại | * | VD: 0988098097 |
| Hình thức học (Online / Offline) | | Dropdown (Online, Offline) |
| Địa chỉ hiện tại | | **Thả danh sách các quận trong TPHCM, riêng khu vực Thủ Đức thì list tất cả các phường, admin được chọn nhiều lựa chọn** |
| Quận / Phường | | VD: Quận 1, Quận 2, Quận 3, ... |
| Lớp | | Số (VD: 5) |
| Chương trình học | | Dropdown (cho chọn nhiều môn: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác — được thêm) |
| Trường học | | VD: Trường tiểu học Yên Thế |
| Giới tính giáo viên | | Dropdown (Nam, Nữ, Tuỳ ý) |
| Thời gian rảnh | | Danh sách (Thứ + khung giờ). **Mặc định hôm nay, nhưng có thể chỉnh sửa** |
| Chờ sắp xếp từ | | Date picker |
| Ngày dự kiến bắt đầu học | | Date picker |
| Ghi chú | | Textarea |
| Trạng thái | | Dropdown: **Đang sắp xếp, Chờ học phí, Đang học, Nghỉ học, Tạm nghỉ** |
| Ảnh đại diện | | Upload + preview |

### Tab "Tài khoản giáo viên"

**Bộ lọc:**
- Mã / Tên giáo viên (text)
- Nút **Tìm**
- Nút **Thanh toán phí dịch vụ**
- Nút **Thêm**

**Bảng** (cột):
| Cột | Mô tả |
|---|---|
| Họ và tên + Hạng | Tên + badge màu hạng (Xanh / Đồ / Vàng) |
| Username | |
| Chương trình dạy | VD: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt |
| Số lớp đang dạy | |
| Số điện thoại | |
| Cam kết 08 | Có / Không |
| Phí dịch vụ | Số tiền (VD: 1,468,800 đ) |
| Số tick tháng này | Số nguyên |
| Hành động | Chi tiết / Sửa / Khoá |

### Trang "Chi tiết giáo viên"

**Form** (các trường `*` là bắt buộc):

| Trường | Bắt buộc | Mô tả / placeholder |
|---|---|---|
| Họ tên giáo viên | * | VD: Thái Phụng |
| Username | * | VD: TE125 |
| User code | | VD: TE125 |
| Số điện thoại | * | VD: 0707676299 |
| Email | * | VD: ngothaiphung412@gmail.com |
| CCCD | | VD: 0795565656 |
| Ngày cấp | | Date picker (VD: 04/12/2003) |
| Nơi cấp | | VD: Bộ Công An |
| Mã số thuế | | VD: 0795565656 |
| Địa chỉ hiện tại | | **Thả danh sách các quận trong TPHCM, riêng khu vực Thủ Đức thì list tất cả các phường, admin được chọn nhiều lựa chọn** |
| Quận / Phường có thể dạy | | VD: Quận 1, Quận 2, Quận 3,... |
| Ngày sinh | | Date picker |
| Giới tính | | Dropdown: **Nam, Nữ** |
| Chương trình dạy | | Dropdown: **Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác (được thêm)** |
| Số lớp tối đa có thể nhận | | Số (VD: 2) |
| Có thể dạy online | | Dropdown: **Có / Không** |
| Hạng giáo viên | | Dropdown: **đồ, vàng, xanh** |
| Số tick tháng này | | Read-only + đơn vị "tick" |
| Phí dịch vụ | | Read-only + đơn vị "đồng" |
| Mức phí DV | | Số + đơn vị "%" (VD: 45) |
| Tên ngân hàng | | VD: TMCP Công thương Việt Nam - Vietinbank |
| Số tài khoản NH | | VD: 565656565 |
| Tên chủ tài khoản NH | | VD: NGO THAI PHUNG |
| Cam kết 08 | | Dropdown (Có / Không) |
| Trạng thái | | Dropdown: **Đang dạy, Tạm ngưng** |
| Ảnh đại diện | | Upload + preview |

**Phân trang:** « 1 2 3 4 5 6 7 8 9 ... 104 105 »

## 7. Quản lý Bài tập về nhà (BTVN)

### Bộ lọc
- Từ ngày (date picker) - VD: 05/08/2026
- Đến ngày (date picker) - VD: 05/08/2026
- Tìm theo mã lớp, mã giáo viên
- Nút **Tìm**

### Bảng danh sách
| Cột | Mô tả |
|---|---|
| Thời gian | VD: "Thứ 4 05-08-2026 15:27" |
| Mã GV | VD: TE125 |
| Mã Lớp | VD: Eng, Math, Online, Văn |
| Chuyên đề | VD: How to calculate IQ Type 5 |
| BTVN | "Học 2 chuyên đề trên BTVN: bài trong sách" hoặc "Chưa giao" (màu đỏ) |
| Nhận xét buổi học | VD: "Bé lo ra nhiều, làm bài chậm" |
| Thời gian cập nhật | VD: "Thứ 4 05-08-2026 15:27" |
| Hành động | **Chi tiết** |

**Phân trang:** « 1 2 3 4 5 6 7 8 9 ... 104 105 »

## 8. Quản lý vi phạm

### Bộ lọc + nút hành động
- Ô tìm: "Mã lớp, GV" + nút **Tìm**
- Nút **Chỉnh sửa quy chế** (mở modal cấu hình)
- Nút **Điều chỉnh** (mở modal điều chỉnh tick)

### Bảng danh sách
| Cột | Mô tả |
|---|---|
| Mã lớp | VD: Math |
| Mã GV | VD: TE125 |
| Thời gian vi phạm | VD: 05-08-2026 15:27 |
| Số tick mới nhất | VD: +1 |
| Nội dung vi phạm | VD: "Bạn đã bị phạt một tick vì quên ghi Lesson summary cho buổi học ngày xx/xx/xxxx" |
| Số tick tháng này | Số nguyên (VD: 6, 8, 3, 1, 2) |
| Hành động | **Xoá** |

### Modal "Điều chỉnh vi phạm"
| Trường | Mô tả |
|---|---|
| Mã lớp | "** Cho search và chọn mã lớp" |
| Giáo viên * | "** Cho search và chọn giáo viên" |
| Số tick * | Nhập: +1, -1, +2, -2 |
| Nội dung | Textarea: "Ghi nội dung điều chỉnh số tick ở đây" |

Nút **Thêm**

### Modal "Chỉnh sửa quy chế miễn giảm và ngưỡng hạ phí dịch vụ"
> *\*\*các dòng này đều cho phép thay đổi số, cho thêm dòng, xoá dòng*

**Bảng "Số lớp | Số tick miễn giảm":**
| Số lớp | Số tick miễn giảm |
|---|---|
| Từ 6 trở lên | 10 |
| 5 | 9 |
| 4 | 7 |
| 3 | 5 |
| 1-2 | 3 |

**Ngưỡng tick hạ phí dịch vụ:** `8 tick` + nút **Cập nhật**

**Chỉnh sửa quy chế quét thời hạn lớp 1-1** (toggle, "\*\* cho chọn giờ"):
| Mục | Toggle | Giá trị | Phạt |
|---|---|---|---|
| Hạn giao BTVN | Có | 12:00 *của ngày hôm sau* | 1 tick |
| Hạn check BTVN | Có | 0 *giờ trước buổi tiếp theo* (\*\* chỉ áp dụng nếu hs có nộp BTVN trước deadline gv giao) | 1 tick |
| Hạn chấm test | Có | 72 *giờ kể từ khi hs nộp bài* | 1 tick |

Nút **Cập nhật**

**Chỉnh sửa quy chế quét thời hạn lớp đông người**:
| Mục | Toggle | Giá trị | Phạt |
|---|---|---|---|
| Hạn giao BTVN | Có | 12:00 *của ngày hôm sau* | 1 tick |
| Hạn check BTVN | Có | 24 *giờ trước buổi tiếp theo* (\*\* chỉ áp dụng nếu hs có nộp BTVN trước deadline gv giao) | 1 tick |

Nút **Cập nhật**

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

### Bộ lọc + nút
- Ô tìm: "Tìm nội dung trong bảng..." + nút **Tìm**
- Nút **Thêm**

### Bảng
| Cột | Mô tả |
|---|---|
| Tên chuyên đề | VD: How to calculate correctly, Lộ trình lý thuyết KET (Reading), Lộ trình giải đề TĐN Tiếng Anh, Monthly test |
| Danh mục | VD: Math Problems, Math IQ, Đại số, Hình học, Reading |
| Loại chuyên đề | **Có test** / **Không test** |
| Nội dung | Text (OKOKOKOK...) |
| Số buổi học tối đa | Số (VD: 3, 16, 100, 7) |
| Hành động | **Sửa** / **Duplicate** / **Xoá** |

**Phân trang:** « 1 2 3 4 5 6 7 8 9 ... 104 105 »

## 11. Quản lý nội quy

### Layout
- Trình soạn thảo rich text (toolbar gồm: Font Sans Serif, Size Normal, B, I, U, S, màu chữ, màu nền, X², X₂, H1, H2, trích dẫn, code, list dọc, list ngang, căn trái, căn giữa, căn phải, link, ảnh, bảng, xoá định dạng)
- Vùng soạn thảo với placeholder: "Chỉnh sửa nội quy tại đây ..."

## 12. Quản lý thư viện

### Bộ lọc + nút
- Ô tìm: "Tìm tên tài liệu..." + nút **Tìm**
- Nút **Upload** (mở modal thêm tài liệu mới)

### Lưới tài liệu
- Hiển thị dạng card grid với icon theo loại file (PDF, DOC, XLS, link,...)
- Mỗi card hiển thị: icon + tên file bên dưới
- Phân loại: "[Sách] ...", "[Review] ...", "[Test] ...", "[Nội bộ] ..."

### Modal "Thêm tài liệu mới"
| Trường | Mô tả |
|---|---|
| Liên kết chứa tài liệu | Input "Dán link tại đây ..." |
| Tải file lên | Drop zone: "Choose a file or drag it here" |

> *phân loại tài được hay không*

Nút **Thêm**

## 13. Thống kê phí dịch vụ

### Form filter
| Trường | Mô tả |
|---|---|
| Mã giáo viên | Text |
| Khoảng thời gian | 2 date picker (Từ - Đến) |
| Ký quỹ | Text: "+100000, -100000, ..." |
| Vi phạm | Text: "+100000, -100000, ..." |
| Phí dịch vụ cộng thêm | Text: "+100000, -100000, ..." |
| Thuế (có điều kiện xét) | Number: 0% |

> *\*\*nếu có ký cam kết 08 thì thuế là 0%, nếu không ký và tổng phí từ 5tr thì thuế 10%*

Nút **Xuất tất cả phí dịch vụ** (đỏ) + nút **Tìm** (xanh)

### Kết quả (khi có dữ liệu)
- **Card thông tin GV**: Ảnh + Tên + Mã GV + SĐT + Email
- VD: Ngô Thái Phụng | TE125 | 0707676299 | ngothaiphung412@gmail.com
- Nút **Xuất file phí dịch vụ** + **Xuất file số buổi dạy**
- **Tổng Tiền: 4,860,000 đ** (label lớn bên phải)
- Bảng: Thời Gian | Số Tiền | Nội Dung
- VD: 2026-06-27 19:30 | + 243,000 | "Bạn đã nhận được 243,000 đ khi đã hoàn thành bài học cho TĐN1275.Huy Tuấn"
- Nếu chưa có: hiển thị "Chưa có dữ liệu"

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

### Form filter
- Mã học viên (text, VD: Master227)
- Khoảng thời gian (2 date picker)
- Nút **Tìm**

### Kết quả
- **Card thông tin HV:** Ảnh + Tên + Mã HV + SĐT + Email
- VD: Hà Phương | Master227 | 0707676299 | ngothaiphung412@gmail.com
- Nút **Xuất file số buổi học**
- **Tổng số buổi học: 20 buổi** (label lớn bên phải)
- Bảng: Thời Gian | Thứ | Số buổi học | Mã giáo viên | Ghi chú
- VD: 2026-06-27 19:30 | Thứ 2 | 1 | TE125

## 16. Trang "Lịch học" (calendar view)

### Form
| Trường | Bắt buộc | Mô tả |
|---|---|---|
| Loại lớp | * | Dropdown (1 kèm 1) |
| Giáo viên | * | Dropdown (VD: TE125 - Ngô Thái Phụng) |
| Học viên | * | Dropdown (VD: Master227 - Hà Phương) |
| Lịch cố định | * | Danh sách: Thứ + giờ bắt đầu - giờ kết thúc, có nút **Xoá** / **Thêm** (VD: "Thứ 2 17:30-19:00", "Thứ 6 17:30-19:00") |
| Ngày áp dụng lịch | * | Date picker (VD: 05/08/2026) |

Nút **Lưu**

### Calendar view
- Hiển thị: Tháng 7/2026 (có nút prev/next)
- 7 cột: Thứ 2 → Chủ nhật
- Rows: 1:00 - 24:00
- Ô lịch hiển thị: `Mã HV - Tên HV` + khung giờ (VD: "Master227 - Hà Phương / 17:30 - 19:00")

### Chú thích màu
- **Đỏ** = Buổi học sắp tới
- **X (đỏ đậm)** = Buổi học thiếu BTVN
- **Hồng** = Buổi học đã hoàn thành
- **Xám** = Buổi học đã nghỉ

### Tương tác
> *\*\* Click chọn một buổi, pop up, hiện thông tin:*
> - số giờ học, học phí buổi đó, phần trăm giáo viên nhận, số tiền gv nhận
> - có nút xoá
> - có nút đánh dấu buổi nghỉ

> *\*\* Thao tác xoá và thêm buổi:*
> - **xoá:** bấm vào buổi đấy, pop up, lựa chọn xoá
> - **thêm:** kéo thả khung giờ bất kì (giống google calendar), pop up, điền số giờ học (đề mặc định là khung giờ mới kéo thả), số tiền hp buổi đó, số phần trăm giáo viên nhận
>   - **QUÁ KHỨ:** đánh dấu buổi đó là hỏng hoặc hỏng x.
>   - **TƯƠNG LAI:** auto màu đỏ

## 17. Quản lý yêu cầu (chi tiết các tab)

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

### Modal "Điều chỉnh số buổi" (mở từ nút "Điều chỉnh số buổi học" ở QL lớp học)

- Ô tìm: "tìm nội dung trong bảng"
- Bảng: **Mã lớp** | **Mã học viên** ([tên học sinh]) | **Mã giáo viên** ([tên giáo viên]) | **Chọn** (checkbox)
- VD: Toán 1-1 | Master235 | TE125 | ☐
- Link **Chọn tất cả** + phân trang

**Xoá buổi học:** 2 date picker + nút **Xoá** (đen)

**Thêm buổi học:** 2 date picker + nút **Thêm** (đỏ cam)

## 18. Quản lý lộ trình (Form thêm/sửa)

### Form "Thêm chuyên đề mới"

| Trường | Bắt buộc | Mô tả |
|---|---|---|
| Tên chuyên đề | * | Text (VD: Unit 1 - Flower, How to calculate, English Assessment Test 1) |
| Danh mục | * | Dropdown (VD: Khoa học, Math Problems, Tiếng Anh) - **Có option "+ Thêm loại chuyên đề mới"** |
| Loại chuyên đề | * | Dropdown: **Có test** / **Không test** |
| Nội dung | | Textarea (VD: "Learn about flowers", "Đề thi thử") |
| Số buổi học tối đa | * | Number (VD: 2, 3, 1) |
| Template báo cáo | | Textarea (hoặc rich text editor với toolbar Sans Serif, Normal, B, I, U, S, màu chữ, X², X₂, H1, H2, ", <>, list, link, ảnh, bảng, xoá định dạng) |

> *\*\* Có test, không có test*
> - **Có test:** test và minitest
> - **Không test:** nhập báo cáo chung

Nút **Thêm**

### Dropdown "Danh mục" - mở rộng
Khi bấm vào dropdown, hiển thị các option hiện có:
- Math Problems
- Math IQ
- Đại số
- Hình học
- (+ Thêm loại chuyên đề mới)

## 19. Form "Tài khoản học viên mới" (HV mới)

> *Số ưu tiên: chỉ cho phép nhập số*

| Trường | Bắt buộc | Mô tả / placeholder | Ghi chú |
|---|---|---|---|
| Số ưu tiên | | VD: 1 | **Only số** |
| Họ tên học viên | * | VD: Hà Phương | |
| Username | * | VD: haphuong227 | |
| Password | * | VD: 12345678 | |
| User code | | VD: Master227 | |
| Email | * | VD: haphuong@gmail.com | |
| Họ tên phụ huynh | | VD: mai phương | |
| Số điện thoại | * | VD: 0988098097 | |
| Hình thức học | | Dropdown: **Online / Offline** | |
| Địa chỉ hiện tại | | VD: 181 Cao Thắng, Phường Hòa Hưng, TPHCM | |
| Quận / Phường | | VD: Quận 1, Quận 2, Quận 3, ... | **\*\* thả danh sách các quận trong tphcm, riêng khu vực thủ đức thì list tất cả các phường, admin được chọn nhiều lựa chọn** |
| Lớp | | VD: 5 (số) | |
| Chương trình học | | Dropdown: **Toán TĐN 1-1** (cho chọn nhiều: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác - được thêm) | **\*\* cho chọn nhiều môn** |
| Trường học | | VD: Trường tiểu học Yên Thế | |
| Giới tính giáo viên | | Dropdown: **Nam, Nữ, Tuỳ ý** | **\*\* Nam, Nữ, Tuỳ ý** |
| Thời gian rảnh | | Danh sách: Thứ + khung giờ. Mặc định hôm nay, nhưng có thể chỉnh sửa. VD: "Thứ 2 17:30-19:00", "Thứ 6 8:30-10:00 / 17:30-19:00". Có link **Thêm giờ** / **Thêm ngày** | **\*\* Mặc định hôm nay, nhưng có thể chỉnh sửa** |
| Chờ sắp xếp từ | | Date picker (VD: 21/08/2015) | |
| Ngày dự kiến bắt đầu học | | Date picker (VD: 21/08/2015) | |
| Ghi chú | | Textarea | |
| Trạng thái | | Dropdown: **Đang sắp xếp, Chờ học phí, Đang học, Nghỉ học, Tạm nghỉ** | **\*\*Đang sắp xếp, Chờ học phí, Đang học, Nghỉ học, Tạm nghỉ** |
| Ảnh đại diện | | Upload (Chọn hình) + preview | (png, jpg, jpeg) |

Nút **Thêm**

## 20. Form "Tài khoản giáo viên mới" (GV mới)

| Trường | Bắt buộc | Mô tả / placeholder | Ghi chú |
|---|---|---|---|
| Họ tên giáo viên | * | VD: Thái Phụng | |
| Username | * | VD: TE125 | |
| User code | | VD: TE125 | |
| Số điện thoại | * | VD: 0707676299 | |
| Email | * | VD: ngothaiphung412@gmail.com | |
| CCCD | | VD: 0795565656 | |
| Ngày cấp | | Date picker (VD: 04/12/2003) | |
| Nơi cấp | | VD: Bộ Công An | |
| Mã số thuế | | VD: 0795565656 | |
| Địa chỉ hiện tại | | VD: 181 Cao Thắng, Phường Hòa Hưng, TPHCM | |
| Quận / Phường có thể dạy | | VD: Quận 1, Quận 2, Quận 3,... | **\*\* thả danh sách các quận trong tphcm, riêng khu vực thủ đức thì list tất cả các phường, admin được chọn nhiều lựa chọn** |
| Ngày sinh | | Date picker (VD: 04/12/2003) | |
| Giới tính | | Dropdown: **Nam, Nữ** | **\*\* Nam, Nữ** |
| Chương trình dạy | | Dropdown: **Toán TĐN** | **\*\* Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác (được thêm)** |
| Số lớp tối đa có thể nhận | | VD: 2 (số) | |
| Có thể dạy online | | Dropdown: **Có / Không** | **Có / Không** |
| Hạng giáo viên | | Dropdown: **Xanh** (Đồ, Vàng, Xanh) | **\*\* đồ, vàng, xanh** |
| Số tick tháng này | | Read-only: `0` + đơn vị `tick` | |
| Phí dịch vụ | | Read-only: `0` + đơn vị `đồng` | |
| Mức phí DV | | VD: 45 + đơn vị `%` | |
| OSF | | VD: Hạng 1 | |
| Tên ngân hàng | | VD: TMCP Công thương Việt Nam - Vietinbank | |
| Số tài khoản NH | | VD: 565656565 | |
| Tên chủ tài khoản NH | | NGO THAI PHUNG | |
| Cam kết 08 | | Dropdown: **Có** | |
| Trạng thái | | Dropdown: **Đang dạy** | **\*\* Đang dạy, Tạm ngưng** |
| Ảnh đại diện | | Upload (Chọn hình) + preview | (png, jpg, jpeg) |

Nút **Thêm**




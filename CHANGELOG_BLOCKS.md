# Nhật ký phát triển Module "Khối Giao Diện" (Blocks)

Dưới đây là các nâng cấp và sửa lỗi đã thực hiện cho module Blocks (`db_blocks` và `db_block_items`) để đảm bảo hệ thống hoạt động ổn định và hỗ trợ đa ngôn ngữ hoàn chỉnh.

## 1. Sửa lỗi tính năng Sắp xếp (Sort Order)
- **Vấn đề:** 
  - Tại bảng Khối (Blocks): Gõ số thứ tự bị báo lỗi "Trường không hợp lệ".
  - Tại bảng Mục con (Block Items): Kéo thả không lưu và không hiển thị thông báo.
- **Cách xử lý:** 
  - Mở khóa trường `sort_order` trong `BlockController::updateStatusAjax`.
  - Fix lỗi Javascript bắt sai điều kiện (`res.status` thay vì `res.success`).
  - Thêm hiển thị Toast Notification (`AppNotify.success`) khi kéo thả SortableJS thành công.

## 2. Sửa lỗi bộ lọc và phân trang (Query Builder State Loss)
- **Vấn đề:** Khi truy cập `/admin/blocks/1/items`, mặc định không sắp xếp đúng theo `sort_order`, đồng thời các tính năng tìm kiếm (keyword) và lọc trạng thái bị mất tác dụng trên kết quả hiển thị.
- **Cách xử lý:** 
  - Do cơ chế của `app/Core/Model.php` sẽ gọi `$this->resetQB()` khi thực thi hàm `count()`, làm xóa trắng mọi cấu hình `where` và `orderBy` trước đó.
  - Tách riêng câu query tính tổng số lượng (`$countQuery`) và câu query lấy dữ liệu thực tế (`$query`) ở cả `BlockController@index` và `BlockItemController@index`.

## 3. Sửa lỗi sinh rác dữ liệu khi thao tác Đa ngôn ngữ
- **Vấn đề:** Khi sửa và lưu một khối hoặc item bất kỳ, hệ thống luôn chèn thêm 1 dòng dữ liệu tiếng Anh (`en`) mới thay vì cập nhật dòng đã có.
- **Cách xử lý:** 
  - Lỗi sinh ra do Global Scope mặc định ép `WHERE lang = 'vi'` trong thư viện Model. 
  - Thêm cờ `$q->use_lang = false;` vào tất cả các đoạn mã query/update/first đa ngôn ngữ bên trong `BlockController` và `BlockItemController`.

## 4. Fix lỗi trùng lặp hộp thoại Xóa (Delete Dialog)
- **Vấn đề:** Khi xóa 1 Item hoặc xóa hàng loạt, trình duyệt hiện bảng `confirm()` gốc, bấm OK xong lại hiện thêm bảng `SweetAlert` của hệ thống.
- **Cách xử lý:** 
  - Gỡ bỏ sự kiện bắt `confirm()` thủ công bằng Javascript gốc.
  - Sử dụng thống nhất `AppNotify.confirm()` cho mọi thao tác Xóa đơn lẻ và Xóa hàng loạt.
  - Đồng bộ chuẩn UI Toast Alert (Xanh/Đỏ) cho các phản hồi API.

## 5. Bổ sung cấu hình Đa ngôn ngữ cho Khối (Blocks)
- **Vấn đề:** Bảng `db_blocks` đã thiết kế các cột `description` và `image` nhưng form giao diện không có chỗ nhập, và tất cả ngôn ngữ đều bị lưu đè bằng chung 1 cái tên tiếng Việt.
- **Cách xử lý:** 
  - Nâng cấp `resources/views/admin/block/form.php`: Bố cục lại form, đưa `name`, `description` (dùng CKEditor), `image` (dùng Image Upload CKFinder) vào trong **Tabs Ngôn Ngữ**.
  - Cập nhật backend `BlockController@store` và `BlockController@update` để tiếp nhận mảng dữ liệu đa ngôn ngữ `name[vi]`, `name[en]`, v.v... và xử lý lưu riêng biệt thành từng dòng.

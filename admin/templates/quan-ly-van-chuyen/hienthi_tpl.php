<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Quản lý vận chuyển
      <small>Danh sách mức phí</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
      <li class="active">Vận chuyển</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box box-info">
          <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-cog"></i> Cấu hình chung</h3>
          </div>
          <form action="index.php?p=quan-ly-van-chuyen&a=save_settings" method="post" class="form-horizontal">
            <div class="box-body">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group" style="margin: 0 5px;">
                    <label>Miễn phí từ (đ):</label>
                    <input type="text" name="free_ship_threshold" class="form-control" value="<?= number_format($shipping_settings['free_ship_threshold'] ?: 0) ?>" onkeyup="this.value=formatNumber(this.value)">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group" style="margin: 0 5px;">
                    <label>Phí ship mặc định (đ):</label>
                    <input type="text" name="default_ship_phi" class="form-control" value="<?= number_format($shipping_settings['default_ship_phi'] ?: 0) ?>" onkeyup="this.value=formatNumber(this.value)">
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group" style="margin: 0 5px;">
                    <label>Khối lượng cơ bản (kg):</label>
                    <input type="number" step="0.1" name="ship_base_weight" class="form-control" value="<?= $shipping_settings['ship_base_weight'] ?: 1.0 ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group" style="margin: 0 5px;">
                    <label>Quy tắc cân nặng:</label>
                    <div class="input-group">
                      <select name="ship_rounding" class="form-control">
                        <option value="0" <?= $shipping_settings['ship_rounding'] == 0 ? 'selected' : '' ?>>Không làm tròn</option>
                        <option value="1" <?= $shipping_settings['ship_rounding'] == 1 ? 'selected' : '' ?>>Làm tròn lên 1kg</option>
                      </select>
                      <span class="input-group-btn">
                        <button type="submit" class="btn btn-info btn-flat"><i class="fa fa-save"></i> Cập nhật</button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">Danh sách phí vận chuyển</h3>
        <div class="box-tools">
          <a href="index.php?p=quan-ly-van-chuyen&a=add" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Thêm mới</a>
        </div>
      </div>
      <!-- /.box-header -->
      <div class="box-body">
        <table class="table table-bordered table-striped table-hover" id="dataTableShip">
          <thead>
            <tr>
              <th style="width: 50px;">STT</th>
              <th>Tỉnh / Thành phố</th>
              <th>Quận / Huyện</th>
              <th>Phường / Xã</th>
              <th>Phí vận chuyển</th>
              <th>Ghi chú</th>
              <th style="width: 100px;">Hiển thị</th>
              <th style="width: 150px;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!empty($items)): ?>
              <?php foreach($items as $k => $v): ?>
                <tr>
                  <td><?= $v['so_thu_tu'] ?></td>
                  <td><?= $v['ten_tinh'] ?></td>
                  <td><?= $v['ten_huyen'] ?: '<span class="label label-info">Tất cả huyện</span>' ?></td>
                  <td><?= $v['ten_xa'] ?: '<span class="label label-info">Tất cả xã</span>' ?></td>
                  <td><b class="text-red"><?= number_format($v['phi_ship']) ?>đ</b></td>
                  <td style="font-size: 13px;"><?= $v['ghi_chu'] ?></td>
                  <td class="text-center">
                    <?php if($v['hien_thi']==1){ ?>
                      <label class="label label-success">Hiện</label>
                    <?php }else{ ?>
                      <label class="label label-danger">Ẩn</label>
                    <?php } ?>
                  </td>
                  <td class="text-center">
                    <a href="index.php?p=quan-ly-van-chuyen&a=edit&id=<?= $v['id'] ?>" class="btn btn-warning btn-xs"><i class="fa fa-edit"></i> Sửa</a>
                    <a href="index.php?p=quan-ly-van-chuyen&a=delete&id=<?= $v['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Xóa mức phí này?')"><i class="fa fa-trash"></i> Xóa</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- /.box-body -->
    </div>
    <!-- /.box -->
  </section>
</div>

<link rel="stylesheet" href="templates/plugin/datatables.net-bs/css/dataTables.bootstrap.min.css">
<script src="public/plugin/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="public/plugin/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script>
    $('#dataTableShip').DataTable({
        'autoWidth'   : false,
        'searching'   : true,
        'lengthChange': true,
        "iDisplayLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
        }
    });
</script>

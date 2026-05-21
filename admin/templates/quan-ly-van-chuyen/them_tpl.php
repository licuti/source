<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Cấu hình vận chuyển
      <small><?= isset($item) ? 'Chỉnh sửa' : 'Thêm mới' ?> mức phí</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
      <li><a href="index.php?p=quan-ly-van-chuyen&a=man">Vận chuyển</a></li>
      <li class="active"><?= isset($item) ? 'Chỉnh sửa' : 'Thêm mới' ?></li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="box box-primary">
      <form method="post" action="index.php?p=quan-ly-van-chuyen&a=save&id=<?= @$item['id'] ?>" class="form-horizontal">
        <div class="box-body">
          
          <div class="form-group">
            <label class="col-sm-2 control-label">Chọn Tỉnh / Thành phố</label>
            <div class="col-sm-8">
              <select name="id_tinh" id="id_tinh" class="form-control select2" required onchange="load_huyen_admin(this.value)">
                <option value="">-- Chọn Tỉnh / Thành phố --</option>
                <?php foreach($tinhs as $t): ?>
                  <option value="<?= $t['code'] ?>" <?= (@$item['id_tinh'] == $t['code']) ? 'selected' : '' ?>><?= $t['ten'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Chọn Quận / Huyện</label>
            <div class="col-sm-8">
              <select name="id_huyen" id="id_huyen" class="form-control select2" onchange="load_xa_admin(this.value)">
                <option value="">-- Tất cả Quận / Huyện --</option>
                <?php if(!empty($huyens)): ?>
                  <?php foreach($huyens as $h): ?>
                    <option value="<?= $h['code'] ?>" <?= (@$item['id_huyen'] == $h['code']) ? 'selected' : '' ?>><?= $h['ten'] ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <p class="help-block">Lưu ý: Nếu chọn "Tất cả Quận / Huyện", mức phí này sẽ áp dụng chung cho toàn tỉnh.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Chọn Phường / Xã</label>
            <div class="col-sm-8">
              <select name="id_xa" id="id_xa" class="form-control select2">
                <option value="">-- Tất cả Phường / Xã --</option>
                <?php if(!empty($xas)): ?>
                  <?php foreach($xas as $x): ?>
                    <option value="<?= $x['code'] ?>" <?= (@$item['id_xa'] == $x['code']) ? 'selected' : '' ?>><?= $x['ten'] ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <p class="help-block">Lưu ý: Nếu chọn "Tất cả Phường / Xã", mức phí này sẽ áp dụng chung cho toàn quận/huyện.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Phí vận chuyển (đ)</label>
            <div class="col-sm-8">
              <input type="text" name="phi_ship" class="form-control" value="<?= number_format(@$item['phi_ship'] ?: 0) ?>" onkeyup="this.value=formatNumber(this.value)" required>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Phụ phí mỗi kg tiếp theo (đ)</label>
            <div class="col-sm-8">
              <input type="text" name="phi_extra_kg" class="form-control" value="<?= number_format(@$item['phi_extra_kg'] ?: 0) ?>" onkeyup="this.value=formatNumber(this.value)">
              <p class="help-block">Áp dụng khi khối lượng vượt quá mức cơ bản cấu hình trong Setting.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Freeship từ (kg)</label>
            <div class="col-sm-8">
              <input type="number" step="0.1" name="free_weight" class="form-control" value="<?= @$item['free_weight'] ?: 0 ?>">
              <p class="help-block">Nếu khối lượng đơn hàng đạt mốc này, hệ thống sẽ tự động MIỄN PHÍ VẬN CHUYỂN (Nhập 0 nếu không áp dụng). <br/>Lưu ý đơn vị: 1kg = <?= $config['weight']['conversion'] . $config['weight']['unit'] ?></p>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Ghi chú / Thời gian</label>
            <div class="col-sm-8">
              <input type="text" name="ghi_chu" class="form-control" value="<?= @$item['ghi_chu'] ?>" placeholder="VD: Giao hàng 2-3 ngày, chỉ áp dụng nội thành...">
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Số thứ tự</label>
            <div class="col-sm-2">
              <input type="number" name="so_thu_tu" class="form-control" value="<?= @$item['so_thu_tu'] ?: 0 ?>">
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-8">
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="hien_thi" <?= (!isset($item) || @$item['hien_thi']) ? 'checked' : '' ?>> Hiển thị (Kích hoạt mức phí này)
                </label>
              </div>
            </div>
          </div>

        </div>
        <!-- /.box-body -->
        <div class="box-footer text-center">
          <button type="submit" class="btn btn-primary">Lưu dữ liệu</button>
          <a href="index.php?p=quan-ly-van-chuyen&a=man" class="btn btn-default">Quay lại</a>
        </div>
        <!-- /.box-footer -->
      </form>
    </div>
  </section>
</div>

<script>
function load_huyen_admin(id_tinh) {
    if(!id_tinh) {
        $('#id_huyen').html('<option value="">-- Tất cả Quận / Huyện --</option>');
        $('#id_xa').html('<option value="">-- Tất cả Phường / Xã --</option>');
        return;
    }
    $.ajax({
        url: '../sources/ajax/ajax.php',
        type: 'POST',
        data: { do: 'get_huyen', code_tinh: id_tinh },
        success: function(res) {
            $('#id_huyen').html('<option value="">-- Tất cả Quận / Huyện --</option>' + res);
            $('#id_xa').html('<option value="">-- Tất cả Phường / Xã --</option>');
        }
    });
}

function load_xa_admin(id_huyen) {
    if(!id_huyen) {
        $('#id_xa').html('<option value="">-- Tất cả Phường / Xã --</option>');
        return;
    }
    $.ajax({
        url: '../sources/ajax/ajax.php',
        type: 'POST',
        data: { do: 'get_xa', code_huyen: id_huyen },
        success: function(res) {
            $('#id_xa').html('<option value="">-- Tất cả Phường / Xã --</option>' + res);
        }
    });
}

function formatNumber(nStr) {
    nStr += '';
    x = nStr.split('.');
    x1 = x[0].replace(/\D/g, "");
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}
</script>

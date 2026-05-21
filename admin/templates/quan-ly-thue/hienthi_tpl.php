<?php
    if(!defined('_template')) die("Error");
?>
<div class="content-wrapper">
    <section class="content-header">
      <h1>
        Quản lý thuế (VAT)
      </h1>
      <ol class="breadcrumb">
        <li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
        <li class="active">Quản lý thuế</li>
      </ol>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Thiết lập cấu hình thuế</h3>
            </div>
            <form name="frm" method="post" class="form-horizontal" action="index.php?p=<?=$_GET['p']?>&a=save" enctype="multipart/form-data">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Thuế suất VAT (%):</label>
                        <div class="col-sm-3">
                            <div class="input-group">
                                <input type="number" step="0.1" min="0" max="100" name="vat_rate" class="form-control" value="<?= $item['vat_rate']?>" placeholder="Ví dụ: 8 hoặc 10">
                                <span class="input-group-addon">%</span>
                            </div>
                            <p class="help-block">Mức thuế suất áp dụng chung cho toàn bộ đơn hàng.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Cách tính thuế:</label>
                        <div class="col-sm-10">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="vat_type" value="0" <?= ($item['vat_type'] == 0) ? 'checked' : '' ?>>
                                    Không áp dụng thuế
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="vat_type" value="1" <?= ($item['vat_type'] == 1) ? 'checked' : '' ?>>
                                    <strong>Kiểu 1: Giá đã bao gồm thuế (Inclusive)</strong><br>
                                    <small class="text-muted">Giá bán hiển thị trên web đã có thuế. Hệ thống sẽ bóc tách tiền thuế để hiển thị: <code>Tiền thuế = Tổng tiền - (Tổng tiền / (1 + Thuế suất))</code></small>
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="vat_type" value="2" <?= ($item['vat_type'] == 2) ? 'checked' : '' ?>>
                                    <strong>Kiểu 2: Cộng thêm thuế khi thanh toán (Exclusive)</strong><br>
                                    <small class="text-muted">Giá bán hiển thị trên web chưa có thuế. Thuế sẽ được cộng thêm vào tổng hóa đơn: <code>Tiền thuế = Tổng tiền * Thuế suất</code></small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-save"></span> Lưu cấu hình</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

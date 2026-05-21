<?php
$col = "db_dathang.ma_dh, db_dathang.ngay_dathang, db_dathang.trangthai_xuly, db_dathang.trangthai_thanhtoan, db_dathang.ngay_capnhat, SUM(db_dathang_chitiet.so_luong*db_dathang_chitiet.gia_goc) as tong_goc, SUM(db_dathang_chitiet.so_luong*db_dathang_chitiet.gia_ban) as tong_ban";
$table = " db_dathang JOIN db_dathang_chitiet ON db_dathang.id = db_dathang_chitiet.id_dh";
$where = "id_ctv = ".$_SESSION['id_login']." ";
$donhang =  $d -> o_fet("select ".$col." from ".$table." where ".$where." GROUP BY id_dh ORDER BY db_dathang.id DESC ");

$row_thanhtoan = $d->simple_fetch("SELECT SUM(thanh_toan) as thanhtoan FROM `#_thanhtoan_ctv` WHERE id_ctv = ".(int)$_SESSION['id_login']." GROUP BY id_ctv ");
$dathanhtoan = $row_thanhtoan['thanhtoan'];

?>

<div class="table-responsive">
    <table class="table datatables2 table-bordered">
        <thead>
            <tr>
                <th style="width: 30px;">STT</th>
                <th>Mã ĐH</th>
                <th>Đặt hàng</th>
                <th>Trạng thái</th>
                <th>Thanh toán</th>
                <th>Tổng tiền</th>
                <th>Chiết khấu</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tong =  0;
            foreach ($donhang as $key => $value) {
                //$row_tong = $d -> simple_fetch("SELECT SUM(so_luong) as so_luong, SUM(so_luong*gia_ban) as tongtien FROM `db_dathang_chitiet` where id_dh =".$value['id']."  GROUP BY ma_dh "); 
                $tong = $tong+ ($value['tong_ban']-$value['tong_goc']);
            ?>
            <tr>
                <td class="text-center"><?=$key+1?></td>
                <td><?=$value['ma_dh']?></td>
                <td><?=substr($value['ngay_dathang'],0,10)?></td>
                <td  style=" text-align: center">
                    <?php if($value['trangthai_xuly']==0){ ?>
                    <label style="font-size: 13px;padding: 5px 15px 7px;" class="badge bg-danger">Đang xủ lý</label> 
                    <?php }elseif ($value['trangthai_xuly']==1) {?>
                     <label style="font-size: 13px;padding: 5px 15px 7px;" class="badge bg-warning">Đang xủ lý</label> 
                    <?php }elseif ($value['trangthai_xuly']==2) {?>
                    <label style="font-size: 13px;padding: 5px 15px 7px;" class="badge bg-info">Đang giao</label> 
                    <?php }elseif ($value['trangthai_xuly']==3) {?>
                     <label style="font-size: 13px;padding: 5px 15px 7px;" class="badge bg-success">Đã giao</label> 
                    <?php }elseif ($value['trangthai_xuly']==4) {?>
                      <label style="font-size: 13px;padding: 5px 15px 7px;" class="badge bg-info">Trả hàng</label> 
                    <?php }?>
                </td>
                <td>
                    <?php if ($value['trangthai_xuly']==3) { ?>
                    <?=substr($value['ngay_capnhat'],0,10)?>
                    <?php } ?>
                </td>
                <td>
                    Giá vốn: <?=  numberformat($value['tong_goc'])?><sup>đ</sup><br>
                    Giá bán: <b><?=  numberformat($value['tong_ban'])?><sup>đ</sup></b>
                </td>
                <td>
                    <?=  numberformat($value['tong_ban']-$value['tong_goc'])?><sup>đ</sup>
                </td>
                <td  style=" text-align: center"><a href="javascript:void(0)" style="font-size: 13px;padding: 5px 15px 7px;" data-fancybox data-src="#view_donhang2" onclick="view_donhang2('<?=$value['ma_dh']?>')" class="badge bg-info text-dark">Xem chi tiết</a></td>
            </tr>  
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" style="text-align: right">Tổng chiết khấu</th>
                <th><?=  numberformat($tong)?></th>
                <th style="text-align: right">Đã thanh toán</th>
                <th><?=  numberformat($dathanhtoan)?><sup>đ</sup></th>
                <th style=" text-align: right">Chờ thanh toán</th>
                <th><?=  numberformat($tong-$dathanhtoan)?><sup>đ</sup></th>
                <th style=" text-align: center">
                    <a href="javascript:void(0)" data-fancybox data-src="#view_thanhtoan" style="font-size: 13px;padding: 5px 15px 7px;" class="badge bg-info text-dark">Chi tiết thanh toán</a>
                </th>
            </tr>
        </tfoot>
    </table>
</div>
<div style="display: none;width: 700px; max-width: 90%" id="view_donhang2" >
    
</div>
<div style="display: none;width: 700px; max-width: 90%" id="view_thanhtoan" >
    <h4 style="text-align: center;margin-bottom: 35px;text-transform: uppercase;font-size: 16px;">Lịch sử thanh toán chiết khấu</h4>
     <?php $thanhtoan = $d->o_fet("select * from #_thanhtoan_ctv where id_ctv = ".(int)$_SESSION['id_login']." order by id desc");  ?>
        <table class="table table-striped table-bordered datatables2" >
            <thead>
                <tr>
                    <th class=" text-center">STT</th>
                    <th class=" text-center">Ngày thanh toán</th>
                    <th class=" text-center">Số tiền</th>
                    <th class=" text-center">Mã chuyển tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($thanhtoan as $key => $value) {?>
                <tr>
                    <td class=" text-center"><?=$key+1?></td>
                    <td style="text-align: center;"><?=$value['ngay_thanhtoan']?></td>
                    <td style="text-align: right;"><?=  numberformat($value['thanh_toan'])?><sup>đ</sup></td>
                    <td class="text-center"><?=$value['ma_hoadon']?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
</div>
<script>
    
    function view_donhang2(ma_dh){
        $.ajax({
            url : "<?= URLPATH ?>ajax/location/district",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'view_donhang_ctv',
                 ma_dh : ma_dh
            },
            success : function (result){
                $('#view_donhang2').html(result);
                
            }
        });
    }
</script>
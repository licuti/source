<?php
$donhang =  $d -> o_fet("select * from #_dathang where id_thanhvien = ".$_SESSION['id_login']." order by id DESC "); 
?>
<div class="table-responsive">
    <table class="table datatables3">
        <thead>
            <tr>
                <th>Mã đơn hàng</th>
                <th>Ngày</th>
                <th>Trạng thái</th>
                <th>Tổng tiền</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($donhang as $key => $value) {
                $row_tong = $d -> simple_fetch("SELECT SUM(so_luong) as so_luong, SUM(so_luong*gia_ban) as tongtien FROM `db_dathang_chitiet` where id_dh =".$value['id']."  GROUP BY ma_dh "); 
            ?>
            <tr>
                <td><?=$value['ma_dh']?></td>
                <td><?=$value['ngay_dathang']?></td>
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
                <td><b><?=  numberformat($row_tong['tongtien']+$value['phi_vanchuyen']-$value['so_tien_giam'])?><sup>đ</sup></b> cho <b><?=$row_tong['so_luong']?></b> Sản phẩm</td>
                <td  style=" text-align: center"><a href="javascript:void(0)" data-fancybox data-src="#view_donhang" style="font-size: 13px;padding: 5px 15px 7px;"  onclick="view_donhang('<?=$value['ma_dh']?>')" class="badge bg-info text-dark">Xem chi tiết</a></td>
            </tr>  
            <?php } ?>
        </tbody>
    </table>
</div>
<div style="display: none;width: 700px; max-width: 80%" id="view_donhang" >
    
</div>
<script>
    function view_donhang(ma_dh){
        $.ajax({
            url : "<?= URLPATH ?>ajax/location/district",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'view_donhang',
                 ma_dh : ma_dh
            },
            success : function (result){
                $('#view_donhang').html(result);
                
            }
        });
    }
</script>
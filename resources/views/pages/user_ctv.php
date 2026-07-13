<?php
$row_sp_ctv = $d -> o_fet("select * from #_sanpham_ctv where id_thanhvien = '".(int)$_SESSION['id_login']."' and hien_thi = 1 order by id DESC ");

?>
<div class="table-responsive">
    <table class="table datatables">
        <thead>
            <tr>
                <th>STT</th>
                <th>Hình ảnh</th>
                <th>Sản phẩm</th>
                <th>Giá gốc</th>
                <th>Giá bán</th>
                <th>Đã bán</th>
                <th>Link chia sẻ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($row_sp_ctv as $key => $value) {
            $row_sanpham = $d->simple_fetch("SELECT * FROM `#_sanpham` WHERE id_code = ".$value['id_sp'].""); 
            $thuoctinh_chitiet = $d->o_fet("SELECT * FROM `db_sanpham_chitiet` WHERE id_sp = ".$row_sanpham['id_code']." ");
            $row_tong_ban = $d->simple_fetch("SELECT SUM(so_luong) as soluong FROM `db_dathang_chitiet` WHERE id_sp=".$value['id_sp']." AND id_ctv = ".$_SESSION['id_login']." GROUP BY id_sp"); 
            ?>
            <tr>
                <td class="text-center"><?=$key+1?></td>
                <td>
                    <getImageUrl style="height: 100px;border: 1px solid #eee;border-radius: 10px;" src="getImageUrl_data/images/<?=$row_sanpham['hinh_anh']?>" alt="<?=$row_sanpham['ten']?>">
                </td>
                <td>
                    <a style="text-decoration: revert;color: #eb9701;" target="_blank" href="<?=URLPATH.$row_sanpham['slug']?>.html"><?=$row_sanpham['ten']?></a>
                    <div style="font-size: 13px;color: #3e3d3d;">Số lượng:
                    <?php $sl=0; foreach ($thuoctinh_chitiet as $key => $value2) {
                    $sl = $sl+$value2['so_luong'];
                    ?>
                    <span style="display: inline-block;margin-left: 10px;"><?=$value2['ten']?>: <b><?=$value2['so_luong']?></b></span>        
                    <?php } ?>
                    </div>
                </td>
                <td>
                    <p><?=  numberformat($row_sanpham['gia0'])?><sup>đ</sup></p>
                </td>
                <td>
                    <input type="number" style="width: 100px;" data="<?=$value['id']?>" value="0" class="form-control update_gia_ctv" />
                    <span class="num_gia_<?=$value['id']?>" style="margin-top: 10px;display: block;font-size: 14px;font-weight: 400;" >Giá: <?=  numberformat($value['gia'])?><sup>đ</sup></span>
                </td>
                <td><?=  numberformat($row_tong_ban['soluong'])?> Sản phẩm</td>
                <td style=" text-align: center">
                    <a class="badge bg-success" href="javascript:void(0)" onclick="share_link(<?=$value['id']?>)" data-fancybox data-src="#hidde-share"><i class="far fa-link"></i> <?=$d->getTxt(112)?></a>
                </td>
            </tr>    
           <?php  } ?>
        </tbody>
    </table>
</div>
<div style="display: none;" id="hidde-share">
    
</div>
<script>
    function share_link(id){
       $.ajax({
            url : "<?= URLPATH ?>ajax/location/district",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'get_linkshare',
                 id         : id,
            },
            success : function (result){
                $('#hidde-share').html(result);
            }
        }); 
    }
    function formatNumber(nStr, decSeperate, groupSeperate) {
        nStr += '';
        x = nStr.split(decSeperate);
        x1 = x[0];
        x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + groupSeperate + '$2');
        }
        return x1 + x2;
    };
    $('.update_gia_ctv').keyup(function(){
        var data =$(this).attr('data');
        var num = $(this).val();
        $('.num_gia_'+data).html('Giá: '+formatNumber(num,',','.')+'<sup>đ</sup>');
    });
    $('.update_gia_ctv').change(function(){
        var data =$(this).attr('data');
        $.ajax({
            url : "<?= URLPATH ?>ajax/location/district",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'update_giasp',
                 id         : data,
                 gia        : $(this).val()
            },
            success : function (result){
                $('.num_gia_'+data).html('Giá: '+result);
                $(this).val(0)
            }
        });
    })
    
</script>
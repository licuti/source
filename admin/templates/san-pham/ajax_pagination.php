<?php
if(!isset($_SESSION))
{
    session_start();
}
define('_lib','../../lib/');
@include _lib."config.php";
@include_once _lib."class.php";
@include_once _lib."function.php";
$d = new func_index($config['database']);
$id_module = $_POST['id_module']; // lấy từ bảng db_module
$current_page =  $_POST['page'];
$where_search = addslashes($_POST['where']);
$total_page = (int)$_POST['totalPages'];
$limit=(int)$_POST['limit'];
if ($current_page > $total_page){
    $current_page = $total_page;
}
else if ($current_page < 1){
    $current_page = 1;
}
$start = ($current_page - 1) * $limit;

//Thông số tìm kiếm 
$link_search = addslashes($_POST['search']);

$items = $d->o_fet("select * from #_sanpham where lang ='".LANG."' $where_search order by so_thu_tu asc, cap_nhat desc, id desc limit $start, $limit");
foreach ($items as $key => $value) {?>
    <tr>
        <td class="text-center">
            <?php if($d->checkPermission_dele($id_module)==1){ ?>
            <input class="chk_box" style="margin-top: 0;" type="checkbox" name="chk_child[]" value="<?=$value['id_code']?>">
            <?php }?>
        </td>
        <td class="text-center">
            <?php if($d->checkPermission_edit($id_module)==1){ ?>
            <input type="number" value="<?=$value['so_thu_tu']?>" class="a_stt" data-table="#_sanpham" data-col="so_thu_tu" data-id="<?=$value['id_code']?>" />
            <?php }else{?>
            <span class="label label-primary"><?=$value['so_thu_tu']?></span>
            <?php }?>

        </td>
        <td>
           <span>
           <?php 
                $query = $d->simple_fetch("select ten from #_category where id_code='".$value['id_loai']."' and lang = 'vi'");

                if(!empty($query)){ echo $query['ten'];}

                ?>
           </span>
        </td>
        <td style="text-align:left">
            <a href="index.php?p=san-pham&a=edit&id=<?=$value['id_code']?><?=$link_search?>&page=<?=$current_page?>"><?=$value['ten']?></a>
        </td>

        <td class="text-center">
            <?php if ($value['khuyen_mai'] > 0 ): ?>
                <span class="text-danger"><?= number_format($value['khuyen_mai'] , 0,',','.')."đ" ?></span><br>
                <span style="text-decoration: line-through;"><?= number_format($value['gia'] , 0,',','.')."đ" ?></span>
            <?php else: ?>
                <span class="text-danger"><?= number_format($value['gia'] , 0,',','.')."đ" ?></span>
            <?php endif ?>
            
        </td>
        <td  class="text-center">
            <?=($value['hinh_anh'] <> '')?"<img src='../img_data/images/".$value['hinh_anh']."' class='img_object50'>":""; ?>
        </td>



        <td  class="text-left">
               

                <label style="font-size: 13px; margin-bottom: 2px;">
                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_sanpham','tieu_bieu','<?=$value['id_code']?>')" <?php if($value['tieu_bieu'] == 1) echo 'checked="checked"'; ?>> Nổi bật
                </label><br>
                <label style="font-size: 13px; margin-bottom: 2px;">
                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_sanpham','sp_moi','<?=$value['id_code']?>')" <?php if($value['sp_moi'] == 1) echo 'checked="checked"'; ?>> Mới
                </label><br>
                <label style="font-size: 13px; margin-bottom: 2px;">
                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_sanpham','sp_hot','<?=$value['id_code']?>')" <?php if($value['sp_hot'] == 1) echo 'checked="checked"'; ?>> Hot
                </label><br>
                <label style="font-size: 13px; margin-bottom: 2px;">
                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_sanpham','sp_sale','<?=$value['id_code']?>')" <?php if($value['sp_sale'] == 1) echo 'checked="checked"'; ?>> Sale
                </label><br>
                <label style="font-size: 13px; margin-bottom: 2px;">
                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_sanpham','sp_top','<?=$value['id_code']?>')" <?php if($value['sp_top'] == 1) echo 'checked="checked"'; ?>> Top
                </label>
                <?php  ?>
               
        </td>
        <td class="text-center">
        <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_sanpham','hien_thi','<?=$value['id_code']?>')" <?php if($value['hien_thi'] == 1) echo 'checked="checked"'; ?>>
        </td>
        <td  class="text-left">
            <?php if($value['nofollow']==1){ ?>
                <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> No-follow</span>
                <?php }else{ ?>
                <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Do-follow</span>
                <?php } ?><br>
                <?php if($value['noindex']==1){ ?>
                <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Noindex</span>
                <?php }else{ ?>
                <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Index</span>
           <?php }?>
        </td>
        <td class="text-center">
            <a style="padding: 3px 5px 5px;font-size: 11px;" href="index.php?p=san-pham&a=edit&id=<?=$value['id_code']?><?=$link_search?>&page=<?=$current_page?>" class="btn btn-sm btn-warning" title="Sửa"><i class="glyphicon glyphicon-edit"></i></a>
            <?php if($d->checkPermission_dele($id_module)==1){ ?>
            <a style="padding: 3px 5px 5px;font-size: 11px;" href="index.php?p=san-pham&a=delete&id=<?=$value['id_code']?><?=$link_search?>&page=<?=$current_page?>" onClick="if(!confirm('Xác nhận xóa?')) return false;" class="bnt btn-sm btn-danger" title="Xóa"><i class="glyphicon glyphicon-remove"></i></a>
            <?php }?>
        </td>
    </tr>
<?php }?>
<script>
    $(document).ready(function(){
        if($('.a_stt').length > 0){
            $('.a_stt').on('blur',function() {
                var table=$(this).attr('data-table');
                var col=$(this).attr('data-col');
                var id=$(this).attr('data-id');
                var val=$(this).val();
                $.ajax({
                        url: './sources/ajax.php',
                        type: "POST",
                        data: {'table': table, 'col': col, 'val':val, 'id':id, 'do':'update_stt'},	
                        dataType: "json",
                        success : function (result){
                            if(result===1){
                                $.notify("Cập nhật thành công", "success");
                            }else{
                                $.notify("Đã sảy ra lỗi! ", "error");
                            }
                            return false;
                        }
                })
                return false;
            })
        }
    });
</script>

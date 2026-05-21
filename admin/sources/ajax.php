<?php
if(!isset($_SESSION))
{
    session_start();
}
define('_lib','../lib/');
@include _lib."config.php";
@include_once _lib."class.php";
$d = new func_index($config['database']);

$do = addslashes($_POST['do']);

if ($do == 'remove_button_contact') {
    $id = $_POST['id'];
    $d->reset();
    $d->setTable('#_button_contact');
    $d->setWhere('id', $id);
    $d->delete();
}


if ($do == 'create_menu') {
    $name = trim($_POST['name'] ?? '');
    if ($name == '') {
        echo json_encode(['status' => 'error', 'message' => 'Tên menu không hợp lệ']);
        exit;
    }

    $d->reset();
    $d->setTable('#_menus');
    $id = $d->insert(['name' => $name]);

    echo json_encode(['status' => 'success', 'menu_id' => $id]);
    exit;
}

if ($do == 'delete_menu') {
    $menu_id = intval($_POST['menu_id'] ?? 0);
    if ($menu_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID menu không hợp lệ']);
        exit;
    }

    $d->reset();
    $d->rawQuery("delete from #_menu_items where menu_id = {$menu_id}");

    $d->reset();
    $d->rawQuery("delete from #_menus where id = {$menu_id}");

    echo json_encode(['status' => 'success']);
    exit;
}


if ($do == 'save_menu') {
    // 1. Đọc và giải mã dữ liệu JSON
    $json_string = isset($_POST['json_data']) ? $_POST['json_data'] : '';
    $data = json_decode($json_string, true); // true để chuyển thành mảng

    // 2. Kiểm tra dữ liệu đầu vào
    if (empty($data) || !isset($data['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }

    global $d;

    try {
        $menu_id = (int)$data['id'];
        $menu_name = $d->clear($data['name']);
        
        // ---- 3. CẬP NHẬT TÊN MENU (Bảng `menus`) ----
        $d->reset();
        $d->setTable('#_menus');
        $d->setWhere('id', $menu_id);
        $d->update(['name' => $menu_name]);
        
        // ---- 4. CẬP NHẬT VỊ TRÍ (Bảng `menu_locations`) ----
        // Mảng ['primary_vi', 'footer_en']
        $locations_from_js = isset($data['locations']) ? (array)$data['locations'] : [];

        $d->reset();
        $d->setTable('#_menu_locations');
        $d->setWhere('menu_id', $menu_id);
        $d->update(['menu_id' => 0]);

        if (!empty($locations_from_js)) {
            foreach ($locations_from_js as $loc_string) {
                // Tách "primary_vi" thành "primary" và "vi"
                $parts = explode('_', $loc_string);
                if (count($parts) == 2) {
                    $location_name = $parts[0];
                    $lang_code = $parts[1];

                    $d->reset();
                    $d->setTable('#_menu_locations');
                    $d->setWhere('location_name', $location_name);
                    $d->setWhere('lang', $lang_code);
                    $d->update(['menu_id' => $menu_id]);
                }
            }
        }

        // ---- 5. CẬP NHẬT CÁC MỤC MENU (Bảng `menu_items`) ----
        $d->reset();
        $d->setTable('#_menu_items');
        $d->setWhere('menu_id', $menu_id);
        $d->delete();
        
        save_menu_items_recursive($d, $data['items'], $menu_id, 0); 

        // ---- 6. Trả về thành công ----
        echo json_encode(['status' => 'success', 'message' => 'Menu đã được lưu thành công!']);
        exit;

    } catch (Exception $e) {
        // Bắt bất kỳ lỗi nào xảy ra
        echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
        exit;
    }
}

function save_menu_items_recursive($d, $items, $menu_id, $parent_id) {
    if (!is_array($items) || empty($items)) {
        return; // Dừng nếu không có item con
    }

    $sort_order = 0;
    foreach ($items as $item) {
        $item_data = [
            'menu_id'    => $menu_id,
            'parent_id'  => $parent_id,
            'sort_order' => $sort_order,
            'label'      => $d->clear($item['label']),
            'url'        => $d->clear($item['url']),
            'class'      => $d->clear($item['class']),
            'style'      => $d->clear($item['style']),
            'block'      => $d->clear($item['block']),
            'target'     => $d->clear($item['target']),
            'image'      => $d->clear($item['image']),
            'type'      => $d->clear($item['type']),
            'object_type'=> $d->clear($item['object_type']),
            'object_id'  => (int)$item['object_id'],
        ];
        
        $d->reset();
        $d->setTable('#_menu_items');
        $new_item_id = $d->insert($item_data);

        // 3. Xử lý các mục con (nếu có)
        if ($new_item_id && isset($item['children']) && !empty($item['children'])) {
            // Gọi lại chính hàm này, truyền ID mới làm parent_id
            save_menu_items_recursive($d, $item['children'], $menu_id, $new_item_id);
        }
        
        $sort_order++;
    }
}



if($do=='update_stt') {
    $table = $_POST['table'];
    $col = $_POST['col'];
    $val = $_POST['val'];
    $id = $_POST['id']; 
    if($d->o_que("update $table set $col= '$val' where id_code=$id")){
        echo 1;
    }else{
        echo 0;
    }
}
if($do=='update_data') {
    $table = $_POST['table'];
    $col = $_POST['col'];
    $val = $_POST['val'];
    $id = $_POST['id']; 
    if($d->o_que("update $table set $col= '$val' where id=$id")){
        echo 1;
    }else{
        echo 0;
    }
}
if($do=='xoa_anh_sp'){
    $id = addslashes($_POST['id']);
    $hinh_anh = $d->o_fet("select * from #_sanpham_hinhanh where id = '".$id."'");
    if($d->o_que("delete from #_sanpham_hinhanh where id = '".$id."'")){
        
    }
}elseif($do=='xoa_anh_album'){
    $id = addslashes($_POST['id']);
    $hinh_anh = $d->o_fet("select * from #_album_hinhanh where id = '".$id."'");
    if($d->o_que("delete from #_album_hinhanh where id = '".$id."'")){
    }
}elseif($do=='xoa_thuoctinh'){
    $id = (int)$_POST['id'];
    $d->o_que("delete from #_sanpham_hinhanh where id_chitiet = '".$id."'");
    $d->o_que("delete from #_sanpham_chitiet where id = '".$id."'");
}elseif($do=='add_thuoctinh'){
    $ten        = addslashes($_POST['ten']);
    $cauhinh    = addslashes($_POST['cauhinh']);
    $data['ten'] = $ten;
    $arr_cauhinh = explode(',', $cauhinh);
    for($i=0;$i<count($arr_cauhinh);$i++){
        if($arr_cauhinh[$i]==1){ $data['mo_ta'] = 1;}
        if($arr_cauhinh[$i]==2){ $data['hinh_anh'] = 1;}
        if($arr_cauhinh[$i]==3){ $data['gia'] = 1;}
        if($arr_cauhinh[$i]==4){ $data['ma'] = 1;}
    }
    $d->setTable('#_sanpham_thuoctinh');
    if($id = $d->insert($data)){
        $row_thuoctinh = $d->o_fet("select * from #_sanpham_thuoctinh order by id ASC");                                  
        echo '<option value="">Chọn thuộc tính sản phẩm</option>';
        foreach ($row_thuoctinh as $key => $value) {
            echo '<option value="'.$value['id'].'">'.$value['ten'].'</option>  ';
        }
        echo '<option value="0">Thêm thuộc tính mới</option>';
    }else{
        echo 0;
    }
}elseif($do=='add_text'){
    $text = addslashes($_POST['text']);
    $row_text = $d->simple_fetch("select * from #_text where text = '".$text."' ");
    if(count($row_text)>0){
        echo '<?=$d->gettext('.$row_text['id'].')?>';
    }else{
        $data['text'] = $text;
        $d->setTable('#_text');
        if($id = $d->insert($data)){
            echo '<?=$d->gettext('.$id.')?>';
        }else{
            echo 0;
        }
    }
    die();
}elseif($do=='get_thuoctinh'){
    $id_thuoctinh = (int)$_POST['id'];
    $row_thuoctinh = $d->simple_fetch("select * from #_sanpham_thuoctinh where id = '".$id_thuoctinh."' ");
    echo '<div class="box-thuoctinh" id="thuoctinh_'.$id_thuoctinh.'">
            <span class="name-thuoctinh">'.$row_thuoctinh['ten'].'</span>
            <button style="right: 50px;" class="btn btn-primary btn-add-tt btn-sm" type="button" onclick="add_thuoctinh('.$id_thuoctinh.')">Thêm '.$row_thuoctinh['ten'].'</button>
            <button class="btn btn-danger btn-add-tt btn-sm" type="button" onclick=" $(this).parent().remove();">Xóa</button>
            <input type="hidden" name="id_thuoctinh[]" value="'.$id_thuoctinh.'" />
            <div id="thuoctinh_'.$id_thuoctinh.'"></div>
        </div>';
}elseif($do=='add_thuoctinh_item_sub'){
    $id_thuoctinh   = (int)$_POST['id'];
    $id_sub         = (int)$_POST['id_sub'];
    $stt            = $_POST['stt'];
    $row_thuoctinh = $d->simple_fetch("select * from #_sanpham_thuoctinh where id = '".$id_sub."' ");
    ?>
    <div class="item-thuoctinh">
        <input type="hidden" name="id_thuoctinh_ct_sub_<?=$id_thuoctinh?>_<?=$stt?>[]" value="" />
        <div class="row m-10">
            <div class="form-group col-sm-4 m0 p10">
                <label>Tên:</label>
                <input type="text" class="form-control" name="ten_<?=$id_thuoctinh?>_<?=$stt?>[]" value="">
            </div>
            <?php if($row_thuoctinh['mo_ta']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>Màu:</label>
                <input type="color" class="form-control" name="mo_ta_<?=$id_thuoctinh?>_<?=$stt?>[]" value="">
            </div>
            <?php } ?>
            <?php if($row_thuoctinh['ma']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>Mã:</label>
                <input type="text" class="form-control" name="ma_<?=$id_thuoctinh?>_<?=$stt?>[]" value="">
            </div>
            <?php } ?>
            <?php if($row_thuoctinh['gia']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>Giá:</label>
                <input type="text" class="form-control" name="gia_<?=$id_thuoctinh?>_<?=$stt?>[]" value="">
            </div>
            <div class="form-group col-sm-4 m0 p10">
                <label>Khuyến mãi:</label>
                <input type="text" class="form-control" placeholder="Khuyến mãi" name="khuyen_mai_<?=$id_thuoctinh?>_<?=$stt?>[]" value="">
            </div>
            <?php } ?>
            <?php if($row_thuoctinh['so_luong']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>SL:</label>
                <input type="number" class="form-control" name="so_luong_<?=$id_thuoctinh?>_<?=$stt?>[]" value="">
            </div>
            <?php } ?>
        </div>
    </div>
<?php }elseif($do=='add_thuoctinh_item'){
    $id_thuoctinh = (int)$_POST['id'];
    $id_sub = (int)$_POST['sub'];
    $row_thuoctinh = $d->simple_fetch("select * from #_sanpham_thuoctinh where id = '".$id_thuoctinh."' ");
    $stt=time();
    ?>
    <div class="item-thuoctinh">
        <input type="hidden" name="id_thuoctinh_ct_<?=$id_thuoctinh?>[]" value="" />
        <input type="hidden" name="stt_<?=$id_thuoctinh?>[]" value="<?=$stt?>" />
        <div class="row m-10">
            <div class="form-group col-sm-4 m0 p10">
                <label>Tên:</label>
                <input type="text" class="form-control" name="ten_<?=$id_thuoctinh?>[]" value="">
            </div>
            <?php if($row_thuoctinh['mo_ta']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>Màu:</label>
                <input type="color" class="form-control" name="mo_ta_<?=$id_thuoctinh?>[]" value="">
            </div>
            <?php } ?>
            <?php if($row_thuoctinh['ma']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>Mã:</label>
                <input type="text" class="form-control" name="ma_<?=$id_thuoctinh?>[]" value="">
            </div>
            <?php } ?>
            <?php if($row_thuoctinh['gia']==1){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>Giá:</label>
                <input type="text" class="form-control" placeholder="Giá" name="gia_<?=$id_thuoctinh?>[]" value="">
            </div>
            <div class="form-group col-sm-4 m0 p10">
                <label>Khuyến mãi:</label>
                <input type="text" class="form-control" placeholder="Khuyến mãi" name="khuyen_mai_<?=$id_thuoctinh?>[]" value="">
            </div>
            <?php } ?>
            <?php if($row_thuoctinh['so_luong']==1 and $id_sub==0){ ?>
            <div class="form-group col-sm-4 m0 p10">
                <label>SL:</label>
                <input type="number" class="form-control" name="so_luong_<?=$id_thuoctinh?>[]" value="">
            </div>
            <?php } ?>
        </div>
        <?php if($row_thuoctinh['hinh_anh']==1){ ?>
        <div class="form-group m0 hinh_anh" >
            <label>Hình ảnh:</label>
            <span class="box-img2">
                <img src="img/no-image.png"  style="max-width: 100%;max-height: 100%;object-fit: contain;" id="review_hinh_anh_<?=$stt?>" alt="NO PHOTO" />
                <input type="hidden" value="" name="hinh_anh_<?=$id_thuoctinh?>[]" id="hinh_anh_<?=$stt?>" class=" form-control">
                <a href="filemanager/dialog.php?type=1&field_id=hinh_anh_<?=$stt?>&relative_url=1&multiple=0" class="btn btn-upload2 iframe-btn" > <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh</a>
            </span>
        </div>
        <div class="form-group m0">
            <label>Hình chi tiết:</label>
            <div class="row m-10" id="list_album_<?=$stt?>">
                
            </div>
            <input type="hidden" data_view="<?=$stt?>" id="album_tt_<?=$stt?>" data_thuoctinh ="<?=$id_thuoctinh?>" class="form-control" />
            <a href="filemanager/dialog.php?type=1&field_id=album_tt_<?=$stt?>&relative_url=1&multiple=1" class="btn btn-upload2 iframe-btn" > <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh</a>
        </div>
<script>
    $('.iframe-btn').fancybox({
        'type'		: 'iframe',
        'autoScale'    	: false
    });
function responsive_filemanager_callback(field_id){
    //console.log(field_id);
    var url=jQuery('#'+field_id).val();
    if(field_id=='album'){
        var  text = '';
        for (var i = 0; i < url.length; i++){
            if(url[i]!=='[' && url[i]!==']' && url[i]!=='"'){
                text +=url[i];
            }
        }
        let arr = [];
        arr = text.split(',');
        for (var i = 0; i < arr.length; i++){
            $('#list_'+field_id).append('<div class="col-sm-3 p10" ><div class="item-album"><input type="hidden" name="album[]" value="'+arr[i]+'" class="form-control" /><img src="../img_data/images/'+arr[i]+'" /></div><button onclick="$(this).parent().remove()" class="btn btn-delete-album" type="button" style="top: 0;right: 10px;"><i class="fa fa-trash"></i></button></div>');
        }

    }else{
        $('#review_'+field_id).attr('src','../img_data/images/'+url);
        var data = $('#'+field_id).attr('data_view');
        var id_thuoctinh = $('#'+field_id).attr('data_thuoctinh');
        if(data!==''){
            var  text = '';
            for (var i = 0; i < url.length; i++){
                if(url[i]!=='[' && url[i]!==']' && url[i]!=='"'){
                    text +=url[i];
                }
            }
            let arr = [];
            arr = text.split(',');
            for (var i = 0; i < arr.length; i++){
                $('#list_album_'+data).append('<div class="col-sm-3 p10" ><div class="item-album"><input type="hidden" name="album_'+id_thuoctinh+'_'+data+'[]" value="'+arr[i]+'" class="form-control" /><img src="../img_data/images/'+arr[i]+'" /></div><button onclick="$(this).parent().remove()" class="btn btn-delete-album" type="button" style="top: 0;right: 10px;"><i class="fa fa-trash"></i></button></div>');
            }
        }
    }
}
</script>
        <?php } ?>
        <?php if($id_sub>0){ 
        $row_thuoctinh_sub = $d->simple_fetch("select * from #_sanpham_thuoctinh where id = '".$id_sub."' ");    
        ?>
        <div class="box-thuoctinh">
            <span class="name-thuoctinh"><?= $row_thuoctinh_sub['ten'] ?> của <?=$row_thuoctinh['ten']?></span>
            <button style="right: 50px;" class="btn btn-primary btn-add-tt btn-sm" type="button" onclick="add_thuoctinh_sub(<?=$id_thuoctinh?>,<?=$row_thuoctinh_sub['id']?>,<?=$stt?>)">Thêm <?=$row_thuoctinh_sub['ten'] ?></button>
            <button class="btn btn-danger btn-add-tt btn-sm" type="button" onclick=" $(this).parent().remove();">Xóa</button>
            <div id="thuoctinh_sub_<?=$stt?>">
            </div>
        </div>
<script>
    function add_thuoctinh_sub(id_thuoctinh,id_sub,stt){
        $.ajax({
            url: "./sources/ajax.php",
            type:'POST',
            data:"id="+id_thuoctinh+"&stt="+stt+"&id_sub="+id_sub+"&do=add_thuoctinh_item_sub",
            success: function(data){
                $('#thuoctinh_sub_'+stt).append(data);
            }
        });
        i++;
    }
</script>
        <?php }?>
    </div>
<?php }elseif($do=='get_lienhe'){
    $id = (int)$_POST['id'];
    $row = $d->simple_fetch("select * from #_lienhe where id = '".$id."' ");
    $d->o_que("update #_lienhe set trang_thai = 1 where id = $id");
    ?>
    <?php if($row['tieu_de']!=''){ ?>
    <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;"><?=$row['tieu_de']?></h4>
    <?php }else{?>
        <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;">Chi tiết liên hệ</h4>
    <?php }?>
    <table class="table">
        <tr>
            <td style="width: 90px;font-weight: bold;">Họ tên:</td>
            <td><?=$row['ho_ten']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Điện thoại:</td>
            <td><?=$row['sdt']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Email:</td>
            <td><?=$row['email']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Địa chỉ:</td>
            <td><?=$row['dia_chi']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Nội dung:</td>
            <td><?=$row['noi_dung']?></td>
        </tr>
    </table>
<?php 
}elseif($do=='add_sl'){
    $id = (int)$_POST['id'];
    $so_luong = (int)$_POST['so_luong'];
    
    $row_sp = $d->simple_fetch("select * from #_sanpham where id_code = ".$id." ");
    $soluong_con = $row_sp['so_luong'];
    $soluong_moi = $soluong_con+$so_luong;
    $data['id_sp']          =   $id;
    $data['so_luong_con']   =   $soluong_con;
    $data['so_luong_nhap']  =   $soluong_moi;
    $data['ngay_nhap']      =   date('Y-m-d',  time());
    $d->setTable('#_nhaphang');
    if($d->insert($data)){
        if($d->o_que("update #_sanpham set so_luong= '".$soluong_moi."' where id_code=$id")){
            echo number_format($soluong_moi);
        }else{
            echo 0;
        }
    }else{
        echo 0;
    }
}elseif($do=='view_donhang_ctv'){
    $ma_dh = addslashes($_POST['ma_dh']);
    $id_ctv = addslashes($_POST['id_ctv']);
    $donhang =  $d -> simple_fetch("select * from #_dathang where ma_dh = '".$ma_dh."' "); 
    $donhang_ct = $d -> o_fet("select * from #_dathang_chitiet where id_dh = ".$donhang['id']." and id_ctv = ".$id_ctv." ");?>
        <div class="calculate-shiping p-40 border-radius-15 border">
            <h3 class="mb-10 text-center">CHI TIẾT ĐƠN HÀNG</h3>
            <p class="mb-30 text-center"><span class="font-lg text-muted">Mã đơn hàng: </span><strong class="text-brand"><?=$ma_dh?></strong></p>
            <p class=" mb-10">
                <b>Khách hàng: </b> <?=$donhang['ho_ten']?>
            </p>
            <p class=" mb-10">
                <b>Điện thoại: </b> <?=$donhang['dien_thoai']?>
            </p>
            <p class=" mb-10">
                <b>Email: </b> <?=$donhang['email']?>
            </p>
            <p class=" mb-10">
                <b>Địa chỉ: </b> <?=$donhang['dia_chi']?>
            </p>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Tên SP</th>
                        <th style="text-align: right;">Giá gốc</th>
                        <th style="text-align: right;">Giá bán</th>
                        <th style="text-align: right;">SL</th>
                        <th style="text-align: right;">Thành tiền</th>
                        <th style="text-align: right;">Lợi nhuận</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $tong_sl = $tong_gia = $tong_loinhuan  = 0;
                    foreach ($donhang_ct as $key => $value) {
                    $tong_sl = $tong_sl+$value['so_luong'];
                    $tong_gia = $tong_gia+($value['gia_ban']*$value['so_luong']);
                    $tong_loinhuan = $tong_loinhuan +(($value['gia_ban'] - $value['gia_goc'])*$value['so_luong']);
                    ?>
                    <tr>
                        <td>
                            <?=$value['ten_sp']?><br>
                            <?=$value['thuoc_tinh']?>
                        </td>
                        <td style="text-align: right;"><?=  number_format($value['gia_goc'])?></td>
                        <td style="text-align: right;"><?=  number_format($value['gia_ban'])?></td>
                        <td style="text-align: right;"><?=  number_format($value['so_luong'])?></td>
                        <td style="text-align: right;"><?=  number_format($value['gia_ban']*$value['so_luong'])?></td>
                        <td style="text-align: right;"><?=  number_format(($value['gia_ban'] - $value['gia_goc'])*$value['so_luong'])?></td>
                    </tr>          
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Tổng:</th>
                        <th style="text-align: right;"></th>
                        <th style="text-align: right;"><?=  number_format($tong_sl)?></th>
                        <th style="text-align: right;"><?=  number_format($tong_gia)?></th>
                        <th style="text-align: right;"><?=  number_format($tong_loinhuan)?></th>
                    </tr>
                </tfoot>
            </table>
            <div class="mb-10"><b>Ghi chú đơn hàng: </b> <?=$donhang['loi_nhan']?></div>
            <div class="mb-10"><b>Thanh toán: </b> <?=$donhang['thanh_toan']?></div>
        </div>
        <button type="button" data-fancybox-close="" class="fancybox-button fancybox-close-small" title="Close"><svg xmlns="http://www.w3.org/2000/svg" version="1" viewBox="0 0 24 24"><path d="M13 12l5-5-1-1-5 5-5-5-1 1 5 5-5 5 1 1 5-5 5 5 1-1z"></path></svg></button>
<?php }elseif($do == 'get_binhluan'){

    $id = (int)$_POST['id'];
    $row = $d->simple_fetch("select * from #_binhluan where id = '".$id."' ");
    $d->o_que("update #_binhluan set trang_thai = 1 where id = $id");
    $row_sp = $d->simple_fetch("select ten, alias, id_loai from #_sanpham where id_code=".$row['id_sanpham']." and lang = 'vi' ");
?>

    <?php if($row['tieu_de']!=''){ ?>
    <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;"><?=$row['tieu_de']?></h4>
    <?php }else{ ?>
        <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;">Chi tiết bình luận</h4>
    <?php } ?>
    <table class="table">
        <tr>
            <td style="width: 90px;font-weight: bold;">Sản phẩm:</td>
            <td><?=$row_sp['ten']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Họ tên:</td>
            <td><?=$row['ho_ten']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Email:</td>
            <td><?=$row['email']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Nội dung:</td>
            <td><?=$row['noi_dung']?></td>
        </tr>
    </table>
<?php }else if($do == 'get_coupon'){
    $id = (int)$_POST['id'];
    $row = $d->simple_fetch("select * from #_coupon where id = '".$id."' ");
    $d->o_que("update #_coupon set trang_thai = 1 where id = $id"); 
?>
<?php if($row['tieu_de']!=''){ ?>
    <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;"><?=$row['tieu_de']?></h4>
    <?php }else{?>
        <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;">Thông tin chi tiết</h4>
    <?php }?>
    <table class="table">
        <tr>
            <td style="width: 90px;font-weight: bold;">Họ tên:</td>
            <td><?=$row['ho_ten']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Điện thoại:</td>
            <td><?=$row['sdt']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Ngày sinh:</td>
            <td><?=$row['birthday']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Giới tính:</td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Địa chỉ:</td>
            <td><?=$row['address']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Ngày hẹn:</td>
            <td><?=$row['datebooking']?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Thời gian hẹn:</td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Chi nhánh/ hình thức:</td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Lời nhắn:</td>
            <td><?=strip_tags($row['content'])?></td>
        </tr>
    </table>

<?php }else if($do == 'get_newsletter'){ 
    $id = (int)$_POST['id'];
    $row = $d->simple_fetch("select * from #_newsletter where id = '".$id."' ");
    $d->o_que("update #_newsletter set trang_thai = 1 where id = $id"); 
?>
<?php if($row['tieu_de']!=''){ ?>
<h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;"><?=$row['tieu_de']?></h4>
    <?php }else{ ?>
        <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;">Thông tin chi tiết</h4>
    <?php } ?>
    <table class="table">
        <tr>
            <td style="width:120px;font-weight: bold;">Họ tên:</td>
            <td><?=$row['ho_ten']?></td>
        </tr>
        <tr>
            <td style="width: 120px;font-weight: bold;">Số điện thoại:</td>
            <td><?=$row['sdt']?></td>
        </tr>
        <tr>
            <td style="width: 120px;font-weight: bold;">Email:</td>
            <td><?=$row['email']?></td>
        </tr>
        <tr>
            <td style="width: 120px;font-weight: bold;">Nội dung:</td>
            <td><?=$row['noi_dung']?></td>
        </tr>
    </table>
<?php } ?>
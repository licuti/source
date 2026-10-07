<?php
/**
 * AJAX endpoint cho admin (menu, update_stt, update_data, xem chi tiết...).
 *
 * BẢO MẬT:
 *  - BẮT BUỘC đăng nhập — trước đây file này KHÔNG kiểm tra session.
 *  - `table` / `col` do client gửi phải qua whitelist regex (chống SQL injection).
 *  - `$val` trong update_data được bind bằng safeExec() thay vì nối chuỗi.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../lib/bootstrap.php';
$d = new func_index($config['database']);

/* --- Auth guard: chỉ tài khoản admin đã đăng nhập mới được gọi --- */
if (empty($_SESSION['id_user']) || empty($_SESSION['user_hash'])) {
    http_response_code(401);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
    exit;
}

/* --- CSRF guard: mọi POST (mutating) phải có token hợp lệ --- */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
        http_response_code(419);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(array(
            'status'  => 'error',
            'message' => 'CSRF token không hợp lệ. Vui lòng tải lại trang và thử lại.'
        ));
        exit;
    }
}

if (!function_exists('admin_ajax_safe_table')) {
    /** Chỉ chấp nhận tên bảng dạng `#_ten_bang` (class sẽ thay `#_` bằng prefix thật). */
    function admin_ajax_safe_table($table)
    {
        $table = (string) $table;
        return preg_match('/^#_[a-z0-9_]+$/', $table) ? $table : '';
    }
}

if (!function_exists('admin_ajax_safe_col')) {
    /** Chỉ chấp nhận tên cột chữ thường / số / gạch dưới. */
    function admin_ajax_safe_col($col)
    {
        $col = (string) $col;
        return preg_match('/^[a-z0-9_]+$/', $col) ? $col : '';
    }
}

if (!function_exists('admin_ajax_e')) {
    /**
     * Escape dữ liệu trước khi echo ra HTML (chống XSS lưu trữ).
     * ajax.php không nạp templates/components/_helpers.php nên định nghĩa
     * helper cục bộ — cùng hành vi với hàm e() ở đó.
     *
     * @param  mixed $value
     * @return string
     */
    function admin_ajax_e($value)
    {
        if ($value === null || $value === false) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$do = isset($_POST['do']) ? addslashes((string) $_POST['do']) : '';

if ($do == 'remove_button_contact') {
    $id = $_POST['id'];
    $d->reset();
    $d->setTable('#_button_contact');
    $d->setWhere('id', $id);
    $d->delete();
}

require_once __DIR__ . '/ajax/menu.php';

if ($do == 'update_stt') {
    $table = admin_ajax_safe_table(isset($_POST['table']) ? $_POST['table'] : '');
    $col   = admin_ajax_safe_col(isset($_POST['col']) ? $_POST['col'] : '');
    $val   = (int) (isset($_POST['val']) ? $_POST['val'] : 0);
    $id    = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

    // Whitelist table/col đã chặn SQL injection; val/id đã ép về int.
    if ($table === '' || $col === '' || $id <= 0) {
        echo 0;
        exit;
    }

    // Một số bảng dùng khoá chính `id` thay vì `id_code`
    $idKeyTables = ['#_module_admin'];
    $keyCol = in_array($table, $idKeyTables, true) ? 'id' : 'id_code';

    if ($d->o_que("update $table set $col = $val where $keyCol = $id")) {
        echo 1;
    } else {
        echo 0;
    }
    exit;
}
if($do=='update_data') {
    $table = admin_ajax_safe_table(isset($_POST['table']) ? $_POST['table'] : '');
    $col   = admin_ajax_safe_col(isset($_POST['col']) ? $_POST['col'] : '');
    $val   = isset($_POST['val']) ? (string) $_POST['val'] : '';
    $id    = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

    if ($table === '' || $col === '' || $id <= 0) {
        echo 0;
        exit;
    }

    // Bind $val — trước đây nối chuỗi trực tiếp vào SQL.
    if($d->safeExec("update " . $table . " set " . $col . " = ? where id = ?", array($val, $id))){
        echo 1;
    }else{
        echo 0;
    }
    exit;
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
            echo '<option value="' . (int) $value['id'] . '">' . admin_ajax_e($value['ten']) . '</option>  ';
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
    $__tt_ten = isset($row_thuoctinh['ten']) ? $row_thuoctinh['ten'] : '';
    echo '<div class="box-thuoctinh" id="thuoctinh_' . (int) $id_thuoctinh . '">
            <span class="name-thuoctinh">' . admin_ajax_e($__tt_ten) . '</span>
            <button style="right: 50px;" class="btn btn-primary btn-add-tt btn-sm" type="button" onclick="add_thuoctinh(' . (int) $id_thuoctinh . ')">Thêm ' . admin_ajax_e($__tt_ten) . '</button>
            <button class="btn btn-danger btn-add-tt btn-sm" type="button" onclick=" $(this).parent().remove();">Xóa</button>
            <input type="hidden" name="id_thuoctinh[]" value="' . (int) $id_thuoctinh . '" />
            <div id="thuoctinh_' . (int) $id_thuoctinh . '"></div>
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
                <a <?= function_exists('fm_button_attrs') ? fm_button_attrs([
                    'field_id' => "hinh_anh_$stt",
                    'type'     => 'image',
                    'multiple' => false,
                    'class'    => 'btn btn-upload2 iframe-btn js-fm-open'
                ]) : 'href="ckfinder/ckfinder.html?type=Images" class="btn btn-upload2 iframe-btn js-fm-open" data-fm-driver="ckfinder" data-fm-field="hinh_anh_'.$stt.'" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="0"' ?> > <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh</a>
            </span>
        </div>
        <div class="form-group m0">
            <label>Hình chi tiết:</label>
            <div class="row m-10" id="list_album_<?=$stt?>">
                
            </div>
            <input type="hidden" data_view="<?=$stt?>" id="album_tt_<?=$stt?>" data_thuoctinh ="<?=$id_thuoctinh?>" class="form-control" />
            <a <?= function_exists('fm_button_attrs') ? fm_button_attrs([
                'field_id' => "album_tt_$stt",
                'type'     => 'image',
                'multiple' => true,
                'class'    => 'btn btn-upload2 iframe-btn js-fm-open'
            ]) : 'href="ckfinder/ckfinder.html?type=Images" class="btn btn-upload2 iframe-btn js-fm-open" data-fm-driver="ckfinder" data-fm-field="album_tt_'.$stt.'" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="1"' ?> > <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh</a>
        </div>
<script>
    $('.iframe-btn').fancybox({
        'type'		: 'iframe',
        'autoScale'    	: false
    });

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
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo '<div class="alert alert-warning mb-0">ID không hợp lệ.</div>';
        exit;
    }

    $row = $d->simple_fetch("SELECT * FROM #_lienhe WHERE id = " . $id);
    if (empty($row)) {
        echo '<div class="alert alert-warning mb-0">Không tìm thấy liên hệ.</div>';
        exit;
    }

    // Đánh dấu đã xem
    $d->o_que("UPDATE #_lienhe SET da_xem = 1 WHERE id = " . $id);

    $fields = [
        'Họ tên'      => $row['ho_ten'] ?? '',
        'Điện thoại'  => $row['so_dien_thoai'] ?? '',
        'Email'       => $row['email'] ?? '',
        'Địa chỉ'     => $row['dia_chi'] ?? '',
        'Ngày gửi'    => $row['ngay_gui'] ?? '',
    ];

    $title = trim((string) ($row['tieu_de'] ?? ''));
    if ($title === '') {
        $title = 'Chi tiết liên hệ';
    }
    ?>
    <div class="contact-detail">
        <h5 class="text-center text-uppercase fw-semibold mb-3"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h5>
        <table class="table table-sm table-bordered align-middle mb-0">
            <tbody>
            <?php foreach ($fields as $label => $val): ?>
                <?php if (trim((string) $val) === '') { continue; } ?>
                <tr>
                    <th scope="row" class="bg-body-tertiary" style="width:140px"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                    <td>
                        <?php if ($label === 'Email'): ?>
                            <a href="mailto:<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php elseif ($label === 'Điện thoại'): ?>
                            <a href="tel:<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <th scope="row" class="bg-body-tertiary">Nội dung</th>
                    <td class="text-break" style="white-space:pre-wrap"><?= nl2br(htmlspecialchars((string) ($row['noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
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
            <p class="mb-30 text-center"><span class="font-lg text-muted">Mã đơn hàng: </span><strong class="text-brand"><?=admin_ajax_e($ma_dh)?></strong></p>
            <p class=" mb-10">
                <b>Khách hàng: </b> <?=admin_ajax_e(isset($donhang['ho_ten']) ? $donhang['ho_ten'] : '')?>
            </p>
            <p class=" mb-10">
                <b>Điện thoại: </b> <?=admin_ajax_e(isset($donhang['dien_thoai']) ? $donhang['dien_thoai'] : '')?>
            </p>
            <p class=" mb-10">
                <b>Email: </b> <?=admin_ajax_e(isset($donhang['email']) ? $donhang['email'] : '')?>
            </p>
            <p class=" mb-10">
                <b>Địa chỉ: </b> <?=admin_ajax_e(isset($donhang['dia_chi']) ? $donhang['dia_chi'] : '')?>
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
                            <?=admin_ajax_e(isset($value['ten_sp']) ? $value['ten_sp'] : '')?><br>
                            <?=admin_ajax_e(isset($value['thuoc_tinh']) ? $value['thuoc_tinh'] : '')?>
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
            <div class="mb-10"><b>Ghi chú đơn hàng: </b> <?=admin_ajax_e(isset($donhang['loi_nhan']) ? $donhang['loi_nhan'] : '')?></div>
            <div class="mb-10"><b>Thanh toán: </b> <?=admin_ajax_e(isset($donhang['thanh_toan']) ? $donhang['thanh_toan'] : '')?></div>
        </div>
        <button type="button" data-fancybox-close="" class="fancybox-button fancybox-close-small" title="Close"><svg xmlns="http://www.w3.org/2000/svg" version="1" viewBox="0 0 24 24"><path d="M13 12l5-5-1-1-5 5-5-5-1 1 5 5-5 5 1 1 5-5 5 5 1-1z"></path></svg></button>
<?php }

require_once __DIR__ . '/ajax/detail.php';

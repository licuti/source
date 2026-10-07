<?php
if(isset($_POST['add_diachi']) and $_SESSION['token']   == $_POST['_token']){
    token();
    $data['mo_ta']          =   addslashes(replaceHTMLCharacter($_POST['mo_ta']));
    $data['ho_ten']         =   addslashes(replaceHTMLCharacter($_POST['ho_ten']));
    $data['dien_thoai']     =   addslashes(replaceHTMLCharacter($_POST['dien_thoai']));
    $data['email']          =   addslashes(replaceHTMLCharacter($_POST['email']));
    $data['dia_chi']        =   addslashes(replaceHTMLCharacter($_POST['dia_chi']));
    $data['id_thanhvien']   =   addslashes(replaceHTMLCharacter($_SESSION['id_login']));
    $data['code_xa']        =   addslashes(replaceHTMLCharacter($_POST['code_xa']));
    $data['code_huyen']     =   addslashes(replaceHTMLCharacter($_POST['code_huyen']));
    $data['code_tinh']      =   addslashes(replaceHTMLCharacter($_POST['code_tinh']));
    $d->reset();
    $d->setTable('#_diachi');
    if($d->insert($data)) {
        $thongbao_tt        =   'Thêm thành công';
        $thongbao_icon      =   'success';
        $thongbao_content   =   '';
        $thongbao_url       =  URLPATH.$com.'.html?act=address-tab';
    }
}
if(isset($_POST['update_diachi']) and $_SESSION['token']   == $_POST['_token']){
    token();
    $data['mo_ta']          =   addslashes(replaceHTMLCharacter($_POST['mo_ta']));
    $data['ho_ten']         =   addslashes(replaceHTMLCharacter($_POST['ho_ten']));
    $data['dien_thoai']     =   addslashes(replaceHTMLCharacter($_POST['dien_thoai']));
    $data['email']          =   addslashes(replaceHTMLCharacter($_POST['email']));
    $data['dia_chi']        =   addslashes(replaceHTMLCharacter($_POST['dia_chi']));
    $data['code_xa']        =   addslashes(replaceHTMLCharacter($_POST['code_xa']));
    $data['code_huyen']     =   addslashes(replaceHTMLCharacter($_POST['code_huyen']));
    $data['code_tinh']      =   addslashes(replaceHTMLCharacter($_POST['code_tinh']));
    $d->reset();
    $d->setTable('#_diachi');
    $d->setWhere('id',$_POST['id']);
    if($d->update($data)){
        $thongbao_tt        =   'Cập nhật thành công';
        $thongbao_icon      =   'success';
        $thongbao_content   =   '';
        $thongbao_url       =  URLPATH.$com.'.html?act=address-tab';
    }
}
if(isset($search['id_delete']) and $_SESSION['token']   == $search['token']){
    token();
    $d->reset();
    $d->setTable('#_diachi');
    $d->setWhere('id',(int)$search['id_delete']);
    $d->delete();
    $d->location(URLPATH.$com.".html?act=address-tab");
}
if(isset($search['id_macdinh']) and $_SESSION['token']   == $search['token']){
    token();
    $d->reset();
    $d->setTable('#_diachi');
    $d->setWhere('id',(int)$search['id_delete']);
    $d->delete();
    $d->location(URLPATH.$com.".html?act=address-tab");
    $data['trang_thai']      =   0;
    $d->reset();
    $d->setTable('#_diachi');
    $d->setWhere('id_thanhvien',$_SESSION['id_login']);
    if($d->update($data)){
        $data1['trang_thai']      =   1;
        $d->reset();
        $d->setTable('#_diachi');
        $d->setWhere('id',$search['id_macdinh']);
        if($d->update($data1)){
            $thongbao_tt        =   'Cập nhật thành công';
            $thongbao_icon      =   'success';
            $thongbao_content   =   '';
            $thongbao_url       =  URLPATH.$com.'.html?act=address-tab';
        }
        
    }
}
$row_diachi = $d -> o_fet("select * from #_diachi where id_thanhvien = '".(int)$_SESSION['id_login']."' order by id DESC ");
?>
<div class="row">
    <?php foreach ($row_diachi as $key => $value) {?>
    <div class="col-lg-6">
        <div class="card card-diachi mb-3" style="border: 1px solid #eee;">
            <div class="card-header">
                <h5><?=$value['mo_ta']?></h5>
            </div>
            <div class="card-body">
                <address>
                    <?=$value['dia_chi']?><br>
                    <?=$d->getXa($value['code_huyen'],'*', $value['code_xa'])['ten']?>, <?=$d->getHuyen($value['code_tinh'],'ten', $value['code_huyen'])['ten']?><br>
                    <?=$d->getTinh('ten', $value['code_tinh'])['ten']?><br>
                    <hr>
                    <?=$value['ho_ten']?>. <br>
                    Điện thoại: <?=$value['dien_thoai']?> <br>
                    Email: <?=$value['email']?>
                </address>
                <a style="color: #f8b133;margin-right: 10px;" href="javascript:void(0)" data-fancybox data-src="#edit_diachi" onclick="edit_diachi(<?=$value['id']?>)" class="btn-small"><i class="fal fa-map-marker-edit"></i> chỉnh sửa</a>
                <a style="color: red;" onclick="if(!confirm('Xác nhận xóa địa chỉ?')) return false;" href="<?=URLPATH.$com?>.html?act=<?=$search['act']?>&id_delete=<?=$value['id']?>&token=<?=$_SESSION['token']?>" class="btn-small"><i class="fad fa-map-marker-times"></i> Xóa</a>
                <?php if(count($row_diachi)==1 or $value['trang_thai']==1){ ?>
                <a class="link_macdinh active" href="javascript:void(0)" class="btn-small">Mặc định</a>
                <?php }else{?>
                <a class="link_macdinh" href="<?=URLPATH.$com?>.html?act=<?=$search['act']?>&id_macdinh=<?=$value['id']?>&token=<?=$_SESSION['token']?>" class="btn-small">Đặt làm mặc định</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<div style="display: none;width: 700px; max-width: 80%" id="edit_diachi" >
    
</div>
<div style="display: none;width: 700px; max-width: 80%" id="add_diachi" >
    <form method="POST" action="" id="form-diachi">
        <input type="hidden" value="<?=$_SESSION['token']?>" name="_token" />
        <div class="row">
            <div class="input-style mb-20 col-sm-6">
                <label>Loại địa chỉ</label>
                <select class="form-control" name="mo_ta">
                    <option value="Nhà riêng">Nhà riêng</option>
                    <option value="Văn phòng">Văn phòng</option>
                </select>
            </div>
            <div class="input-style mb-20 col-sm-6">
                <label>Họ tên người nhận</label>
                <input type="text" required placeholder="Nhập họ tên" value="<?=$user_login['ho_ten']?>" name="ho_ten" class="form-control" />
            </div>
        </div>
        <div class="row">
            <div class="input-style mb-20  col-sm-6">
                <label>Điện thoại</label>
                <input type="text" required placeholder="Nhập số điện thoại" value="<?=$user_login['dien_thoai']?>" name="dien_thoai" class="form-control" />
            </div>
            <div class="input-style mb-20  col-sm-6">
                <label>Email</label>
                <input type="text" placeholder="Nhập email" value="<?=$user_login['email']?>" name="email" class="form-control" />
            </div>
        </div>
        <div class="row">
            <div class="input-style mb-20 col-sm-6">
                <label>Tỉnh / Thành phố</label>
                <select class="form-control" required name="code_tinh" id="code_tinh" onchange="get_huyen('code_tinh', 'code_huyen')">
                    <option value="">Chọn Tỉnh / Thành phố</option>
                    <?php foreach ($d->getTinh('code,ten') as $key => $value) {?>
                    <option value="<?=$value['code']?>"><?=$value['ten']?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="input-style mb-20 col-sm-6">
                <label>Quân / Huyện</label>
                <select class="form-control" required id="code_huyen" name="code_huyen" onchange="get_xa('code_huyen', 'code_xa')"></select>
            </div>
        </div>
        <div class="row">
            <div class="input-style mb-20 col-sm-6">
                <label>Phường / xã</label>
                <select class="form-control" required id="code_xa" name="code_xa"></select>
            </div>
            <div class="input-style mb-20 col-sm-6">
                <label>Địa chỉ</label>
                <input type="text" required placeholder="Nhập tên đường, phường/xã" name="dia_chi" class="form-control" />
            </div>
        </div>
        <div class="text-center">
            <button class="btn" name="add_diachi">Thêm địa chỉ</button>
        </div>
    </form>
</div>
<script>
    function edit_diachi(id){
        $.ajax({
            url : "sources/ajax/ajax.php",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'get_diachi_edit',
                 id : id
            },
            success : function (result){
                $('#edit_diachi').html(result);
                
            }
        });
        $('select').niceSelect('update');
    }
    function get_huyen(code_tinh,code_huyen){
        var id_quocgia = $('#'+code_tinh).val();
         $.ajax({
            url : "sources/ajax/ajax.php",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'get_huyen',
                 code_tinh : id_quocgia
            },
            success : function (result){
                $('#'+code_huyen).html(result);
                $('select').niceSelect('update');
            }
        });
        
    }
    function get_xa(code_huyen,code_xa){
        var id_huyen = $('#'+code_huyen).val();
         $.ajax({
            url : "sources/ajax/ajax.php",
            type : "post",
            dataType:"text",
            data : {
                 do         : 'get_xa',
                 code_huyen : id_huyen
            },
            success : function (result){
                $('#'+code_xa).html(result);
                $('select').niceSelect('update');
            }
        });
        
    }
</script>
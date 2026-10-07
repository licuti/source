<?php
if(!isset($_SESSION['id_login'])){
    $d->location(URLPATH.$d->getCate(21,'alias').".html");
    exit();
}
$link=explode("?",$_SERVER['REQUEST_URI']);
if($link[1]!=''){
    $vari=explode("&",$link[1]);
    $search=array();
    foreach($vari as $item) {
        $str=explode("=",$item);
        $search["$str[0]"]=$str[1];
    }
}
if(isset($_POST['capnhat_thongtin']) and $_SESSION['token']   == $_POST['_token']){
    token();
    $data['ho_ten']             =   addslashes(replaceHTMLCharacter($_POST['ho_ten']));
    $data['email']              =   addslashes(replaceHTMLCharacter($_POST['email']));
    $data['dien_thoai']         =   addslashes(replaceHTMLCharacter($_POST['dien_thoai']));
    $data['dia_chi']            =   addslashes(replaceHTMLCharacter($_POST['dia_chi']));
    $data['md5_email']          =   addslashes(MD5($_POST['email']));
    if(isset($_POST['mat_khau']) and $_POST['mat_khau']!=''){
        $data['mat_khau']           =   MD5($_POST['mat_khau']);
    }
    $d->reset();
    $d->setTable('#_thanhvien');
    $d->setWhere('id',$user_login['id']);
    if($d->update($data)){
        $thongbao_tt        =   'Cập nhật thành công';
        $thongbao_icon      =   'success';
        $thongbao_content   =   '';
        $thongbao_url       =   URLPATH.$com.".html";
    }
}
?>
<main>
    <!-- breadcrumb__area-start -->
    <section class="breadcrumb__area box-plr-75">
        <div class="container">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="breadcrumb__wrapper">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?=URLPATH?>">Trang chủ</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?=$row['ten']?></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb__area-end -->
    <!-- product-details-start -->
    <div class="product-details pb-100">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 m-auto">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="dashboard-menu">
                                <ul class="nav flex-column" role="tablist">
                                    <?php if($user_login['loai']==1){ ?>
                                    <li class="nav-item">
                                        <a class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" href="#dashboard" role="tab" aria-controls="dashboard" aria-selected="false"><i class="far fa-sliders-h"></i> <?=$d->getTxt(92)?></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#orders_ctv" role="tab" aria-controls="orders" aria-selected="false"><i class="far fa-cart-arrow-down"></i> <?=$d->getTxt(91)?></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#orders" role="tab" aria-controls="orders" aria-selected="false"><i class="far fa-shopping-bag"></i> <?=$d->getTxt(113)?></a>
                                    </li>
                                    <?php }else{?>
                                    <li class="nav-item">
                                        <a class="nav-link active" id="orders-tab" data-bs-toggle="tab" href="#orders" role="tab" aria-controls="orders" aria-selected="false"><i class="far fa-shopping-bag"></i>  <?=$d->getTxt(113)?></a>
                                    </li>
                                    <?php } ?>
                                    <!--li class="nav-item">
                                        <a class="nav-link" id="track-orders-tab" data-bs-toggle="tab" href="#track-orders" role="tab" aria-controls="track-orders" aria-selected="false"><i class="far fa-cart-arrow-down"></i> <?=$d->getTxt(93)?></a>
                                    </li--->
                                    <li class="nav-item">
                                        <a class="nav-link" id="address-tab" data-bs-toggle="tab" href="#address" role="tab" aria-controls="address" aria-selected="true"><i class="fas fa-map-marker-alt"></i>  <?=$d->getTxt(94)?></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="account-detail-tab" data-bs-toggle="tab" href="#account-detail" role="tab" aria-controls="account-detail" aria-selected="true"><i class="far fa-user"></i> <?=$d->getTxt(88)?></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href=<?=URLPATH?>?logout=1><i class="far fa-sign-out-alt"></i> <?=$d->getTxt(95)?></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="tab-content account dashboard-content pl-20">
                                <?php if($user_login['loai']==1){ ?>
                                <div class="tab-pane fade active show" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                                    <div class="card">
                                        <div class="title-diachi">
                                            <?=$d->getTxt(111)?>
                                            <a class="label label-warning float-end" href="<?=URLPATH.$d->getCate(2,'alias')?>.html?ctv=<?=$user_login['token_gioithieu']?>"> Xem gian hàng</a>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="card-body">
                                            <?php include 'user_ctv.php'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="orders_ctv" role="tabpanel" aria-labelledby="orders-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0"><?=$d->getTxt(91)?></h3>
                                        </div>
                                        <div class="card-body">
                                            <?php include 'user_donhang_ctv.php'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade " id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0"><?=$d->getTxt(113)?></h3>
                                        </div>
                                        <div class="card-body">
                                            <?php include 'user_donhang.php'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php }else{?>
                                <div class="tab-pane fade  active show" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0"><?=$d->getTxt(113)?></h3>
                                        </div>
                                        <div class="card-body">
                                            <?php include 'user_donhang.php'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                
                                <div class="tab-pane fade" id="track-orders" role="tabpanel" aria-labelledby="track-orders-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0">Orders tracking</h3>
                                        </div>
                                        <div class="card-body contact-from-area">
                                            <p>To track your order please enter your OrderID in the box below and press "Track" button. This was given to you on your receipt and in the confirmation email you should have received.</p>
                                            <div class="row">
                                                <div class="col-lg-8">
                                                    <form class="contact-form-style mt-30 mb-50" action="#" method="post">
                                                        <div class="input-style mb-20">
                                                            <label>Order ID</label>
                                                            <input name="order-id" class="form-control" placeholder="Found in your order confirmation email" type="text">
                                                        </div>
                                                        <div class="input-style mb-20">
                                                            <label>Billing email</label>
                                                            <input name="billing-email"  class="form-control" placeholder="Email you used during checkout" type="email">
                                                        </div>
                                                        <button class="submit submit-auto-width" type="submit">Track</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="address" role="tabpanel" aria-labelledby="address-tab">
                                    <div class="title-diachi"><?=$d->getTxt(94)?>
                                        <a class="label label-warning float-end" href="javascript:void(0)" data-fancybox data-src="#add_diachi"><i class="far fa-plus"></i> Thêm địa chỉ</a>
                                        <div class="clearfix"></div>
                                    </div>
                                    <?php include 'user_diachi.php'; ?>
                                </div>
                                <div class="tab-pane fade" id="account-detail" role="tabpanel" aria-labelledby="account-detail-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="title"><?=$d->getTxt(88)?></h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="" id="form-thongtin">
                                                <input type="hidden" value="<?=$_SESSION['token']?>" name="_token" />
                                                <div class="form-group">
                                                    <label><?=$d->getTxt(65)?> <span class="required">*</span></label>
                                                    <input required="" class="form-control" name="ho_ten" value="<?=$user_login['ho_ten']?>" type="text">
                                                </div>
                                                <div class="form-group">
                                                    <label>Email <span class="required">*</span></label>
                                                    <input required="" class="form-control" name="email" value="<?=$user_login['email']?>" type="text">
                                                </div>
                                                <div class="form-group">
                                                    <label><?=$d->getTxt(6)?></label>
                                                    <input class="form-control" name="dien_thoai" value="<?=$user_login['dien_thoai']?>" type="text">
                                                </div>
                                                <div class="form-group">
                                                    <label><?=$d->getTxt(7)?></label>
                                                    <input class="form-control" name="dia_chi" value="<?=$user_login['dia_chi']?>" type="text">
                                                </div>
                                                <div class="login_footer form-group mb-50">
                                                    <div class="chek-form">
                                                        <div class="custome-checkbox" >
                                                            <input class="form-check-input" type="checkbox" name="save" id="exampleCheckbox1" value="1">
                                                            <label class="form-check-label" for="exampleCheckbox1"><span onclick="doimatkhau()"><?=$d->getTxt(90)?></span></label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label><?=$d->getTxt(66)?> <span class="required">*</span></label>
                                                    <input required="" disabled class="form-control re_makhau" id="matkhau" name="mat_khau" type="password">
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <label><?=$d->getTxt(67)?> <span class="required">*</span></label>
                                                    <input required="" disabled="" class="form-control re_makhau" name="mat_khau2" type="password">
                                                </div>
                                                <button type="submit" class="btn btn-heading btn-fill-out submit font-weight-bold" name="capnhat_thongtin" value="Submit"><?=$d->getTxt(89)?></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<link rel="stylesheet" type="text/css" href="<?=URLPATH?>templates/css/jquery.fancybox.min.css"/>
<script src="<?=URLPATH?>templates/js/jquery.fancybox.min.js"></script>
<script type="text/javascript" src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/jquery.validate.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css"/>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('.datatables').DataTable();
        $('.datatables2').DataTable();
        $('.datatables3').DataTable();
    });
    function doimatkhau(){
        var disabled = $(".re_makhau").prop('disabled');
        if (disabled) {
          $(".re_makhau").prop('disabled', false);		// if disabled, enable
        } else {
          $(".re_makhau").prop('disabled', true);		// if enabled, disable
        }
    }
</script>
<script>
    <?php if(isset($thongbao_tt) and $thongbao_tt!=""){ ?>
    swal({
        title: '<?=$thongbao_tt ?>',
        text: '<?= $thongbao_content ?>',
        icon: '<?= $thongbao_icon ?>',
        button: false,
        timer: 2000
    }).then((value) => {
        window.location="<?=$thongbao_url?>";
    }); 
    <?php }?> 
$(document).ready(function() {
    //Khi bàn phím được nhấn và thả ra thì sẽ chạy phương thức này
    $("#form-thongtin").validate({
        rules: {
            ho_ten: "required",
            xacnhan: "required", 
            email: {
                required: true,
                email: true
            },
            mat_khau: {
                required: true,
                minlength: 6,
            },
            mat_khau2: {
                required: true,
                minlength: 6,
                equalTo: "#matkhau"
            }
        },
        messages: {
            ho_ten: "Vui lòng nhập họ tên",
            xacnhan: "Vui lòng xác nhận đồng ý với các điều khoản",
            email: {
                required: "Vui lòng nhập email",
                email: "Vui lòng nhập đúng định dạng mail"
            },
            mat_khau: {
                required: "Vui lòng nhập mật khẩu",
                minlength: 'Mật khẩu tối thiểu 6 ký tự'
            },
            mat_khau2: {
                required: 'Vui lòng xác nhận lại mật khẩu',
                minlength: 'Mật khẩu tối thiểu 6 ký tự',
                equalTo: "Nhập lại mật khẩu không đúng"
            }
        }
    });
    $("#form-diachi").validate({
        rules: {
            ho_ten          :   "required",
            dien_thoai      :   "required", 
            email: {
                email: true
            },
            id_countries    :   "required",
            id_states       :   "required", 
            id_cities       :   "required", 
            dia_chi         :   "required"
        },
        messages: {
            ho_ten          :   "Vui lòng nhập họ tên",
            dien_thoai      :   "Vui lòng nhập số diện thoại",
            email: {
                email       :   "Vui lòng nhập đúng định dạng mail"
            },
            id_countries    :   "Vui lòng chọn quốc gia",
            id_states       :   "Vui lòng chọn tỉnh / thành phố",
            id_cities       :   "Vui lòng chọn quận / huyện",
            dia_chi         :   "Vui lòng nhập dịa chỉ"
        }
    });
});
</script>
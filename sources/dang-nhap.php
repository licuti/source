<?php
require_once './library/vendor/autoload.php';
use \Firebase\JWT\JWT;
$key = !empty($config['jwt']['secret']) ? $config['jwt']['secret'] : '';

$link=explode("?",$_SERVER['REQUEST_URI']);
if($link[1]!=''){
    $vari=explode("&",$link[1]);
    $search=array();
    foreach($vari as $item) {
        $str=explode("=",$item);
        $search["$str[0]"]=$str[1];
    }
}
if(isset($search) and $search['url']!=''){
    $url_back = URLPATH.$search['url'].'.html';
}else{
    $url_back = URLPATH;
}
if(isset($_POST['login']) ){
    if($_SESSION['token']  == $_POST['_token']){
        $md5_email  = MD5($_POST['email']);
        $matkhau    = MD5($_POST['password']);

        // Chỉ 1 truy vấn: lấy luôn bản ghi thay vì đếm rồi lấy lại
        $thanhvien = $d->safeFetchOne(
            "select * from #_thanhvien where md5_email = ? and mat_khau = ?",
            array($md5_email, $matkhau)
        );

        if(!empty($thanhvien)){

            if($thanhvien['trang_thai']==1){

                $_SESSION['id_login'] = $thanhvien['id'];
                $_SESSION['thanhvien_login'] = $thanhvien['ho_ten'];
                $_SESSION['type'] = $thanhvien['loai'];
                if(isset($_POST['save'])){

                    if($thanhvien['token']==""){
                        $token  =   randomString(6);
                        $data_tv['token'] =  $token;
                        $d->reset();
                        $d->setTable('#_thanhvien');
                        $d->setWhere('id', $thanhvien['id']);
                        $d->update($data_tv);
                    }else{
                        $token  =   $thanhvien['token'];
                    }
                    $payload['id_login']        =   $thanhvien['id'];
                    $payload['user_login']      =   $thanhvien['ho_ten'];
                    $payload['type']            =   $thanhvien['loai'];
                    $payload['token']           =   $token;
                    try {
                        $jwt = JWT::encode($payload, $key, 'HS256');
                        setrawcookie("keyId", urlencode($jwt), time()+(60*60*24*365), "/",NULL,FALSE,TRUE);
                    } catch (\Throwable $e) {
                        error_log('[JWT] Tao keyId cookie that bai: ' . $e->getMessage());
                    }
                }

                $thongbao_tt    =   'Đăng nhập thành công';
                $thongbao_icon  =   'success';
                $thongbao_content=  'Chào mừng thành viên '.$thanhvien['ho_ten'];
                $thongbao_url       =  $url_back;
            }else{
                $thongbao_tt    =   'Đã sảy ra lỗi!';
                $thongbao_icon  =   'error';
                $thongbao_content=  'Tài khoản chưa được xác nhận';
                $thongbao_url       =  URLPATH;
            }

        }else{
            $thongbao_tt    =   'Đã sảy ra lỗi!';
            $thongbao_icon  =   'error';
            $thongbao_content=  'Email hoặc mật khẩu không đúng';
            $thongbao_url       =  $url_page;
        }
    }else{
        $thongbao_tt        =   'Đã sảy ra lỗi!';
        $thongbao_icon      =   'error';
        $thongbao_content   =   'Erore token';
        $thongbao_url       =   $url_page;
    }
    //token();
    
}
?>
<main>
    <!-- account-area-start -->
    <div class="account-area mt-70 mb-70">
        <div class="container">
            <div class="row">
                <div class="col-xl-8 col-lg-10 col-md-12 m-auto">
                    <div class="row">
                        <div class="col-lg-6 pr-30 d-none d-lg-block">
                            <img class="border-radius-15" src="media/images/<?=$row['hinh_anh']?>" alt="">
                        </div>
                        <div class="col-lg-6 col-md-8">
                            <div class="login_wrap widget-taber-content background-white">
                                <div class="padding_eight_all bg-white">
                                    <div class="heading_s1">
                                        <h1 class="mb-5"><?=$row['ten']?></h1>
                                        <p class="mb-30"><?=$d->getTxt(61)?> <a href="<?=$d->getCate(22,'alias')?>.html"><?=$d->getCate(22,'ten')?></a></p>
                                    </div>
                                    <form method="POST" action="" id="form-dangnhap">
                                        <input type="hidden" value="<?=$_SESSION['token']?>" name="_token" />
                                        <div class="form-group">
                                            <input type="text" required="" name="email" placeholder="<?=$d->getTxt(31)?> *">
                                        </div>
                                        <div class="form-group">
                                            <input required="" type="password" name="password" placeholder="<?=$d->getTxt(66)?> *">
                                        </div>
                                        <div class="login_footer form-group mb-50">
                                            <div class="chek-form">
                                                <div class="custome-checkbox">
                                                    <input class="form-check-input" type="checkbox" name="save" id="exampleCheckbox1" value="1">
                                                    <label class="form-check-label" for="exampleCheckbox1"><span><?=$d->getTxt(87)?></span></label>
                                                </div>
                                            </div>
                                            <a href="<?=URLPATH.$d->getCate(23,'alias')?>.html"><?=$d->getCate(23,'ten')?>?</a>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-heading btn-block hover-up" name="login">Log in</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script type="text/javascript" src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/jquery.validate.min.js"></script>
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
    $("#form-dangnhap").validate({
        rules: { 
            email: {
                required: true,
                email: true
            },
            password: {
                required: true
            }
        },
        messages: {
            email: {
                required: "Vui lòng nhập email",
                email: "Vui lòng nhập đúng định dạng mail"
            },
            password: {
                required: "Vui lòng nhập mật khẩu"
            }
        }
    });
});
</script>
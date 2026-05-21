<?php 
    if(isset($_POST['lienhe']) and $_SESSION['token']   == $_POST['_token'] ){
        if (isset($_POST['ct_hiddenRecaptcha']) && !empty($_POST['ct_hiddenRecaptcha'])) {
            $gsitekey       = $_POST['ct_hiddenRecaptcha'];
            $gsecret        = _secretkey;
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$gsecret.'&response='.$gsitekey);
            $responseData   = json_decode($verifyResponse);

            if ($responseData->success) {
                $ho_ten     =   validate_content($_POST['ho_ten']);
                $dien_thoai =   validate_content($_POST['dien_thoai']);
                $email      =   validate_content($_POST['email']);
                $noidung    =   validate_content($_POST['noi_dung']);
                if(!empty($dien_thoai)){
                    $data['tieu_de']    =   "Đăng ký báo giá!";
                    $data['ho_ten']     =   $ho_ten;
                    $data['sdt']        =   $dien_thoai;
                    $data['email']      =   $email;
                    $data['noi_dung']   =   $noidung;

                    // var_dump($data);
                    // die();
                    if (strlen(strstr($data['noi_dung'], 'http')) > 0) {
                        $d->alert("Nội dung gửi không hợp lệ!");
                    }elseif(strlen($data['sdt']) < 9 or strlen($data['sdt']) > 11 or substr ($data['sdt'],0,1)!='0'){
                        $d->alert("Số điện thoại không hợp lệ!");
                    }else{
                        $d->reset();
                        $d->setTable('#_lienhe');

                        if($d->insert($data)) {
                            $thongbao_tt    =   $d->getTxt(15);
                            $thongbao_icon  =   'success';
                            $thongbao_content=  $d->getTxt(16);
                            $thongbao_url       = _URLLANG;
                        }else{
                            
                        }
                    }
                }
            }else{
                $thongbao_tt    =   'Error recaptcha';
                $thongbao_icon  =   'error';
                $thongbao_content=  'Đả xảy ra lỗi';
                $thongbao_url       = _URLLANG;
            }
        }else{
            $thongbao_tt    =   'Error Recaptcha';
            $thongbao_icon  =   'error';
            $thongbao_content=  'Đả xảy ra lỗi';
            $thongbao_url       = _URLLANG;
        }
    }
?>
<?php
    $block_contact = $d->getContent(576);
?>
<div class="block bg-gray">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <h2 class="main-title main-title-underline text-x mb-4 mb-lg-5"><?= $block_contact['ten'] ?></h2>
                <?= $block_contact['noi_dung'] ?>
            </div>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-lg-8">
                <form class="form-booking" method="post" action="" autocomplete="">
                    <input type="hidden" value="<?=$_SESSION['token']?>" name="_token" />
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <input id="form_name" type="text" name="ho_ten" placeholder="Họ tên" required="required" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <input id="form_phone" type="text" name="dien_thoai" placeholder="Số điện thoại" required="required" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <input id="form_phone" type="text" name="email" placeholder="Email" required="required" class="form-control">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <textarea id="form_message" name="noi_dung" class="form-control" placeholder="<?= $d->getTxt(133) ?>" rows="3" required="required"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" name="lienhe" class="btn-custom btn-y px-5"><?= $d->getTxt(13) ?> <i class="fa-light fa-paper-plane ms-1"></i></button>
                        </div>
                        <div class="col-md-6 d-flex justify-content-lg-end">
                            <div class="g-recaptcha-contact" data-sitekey="<?=_sitekey?>" id="RecaptchaField1"></div>
                            <input type="hidden" class="hiddenRecaptcha" name="ct_hiddenRecaptcha" id="ct_hiddenRecaptcha">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        $(".form-home-contact").validate({
            rules: {
                ho_ten: {
                    required: true,
                    minlength: 2
                },
                dien_thoai: {
                    required: true,
                    digits: true,
                    minlength: 9,
                    maxlength: 11
                },
                email: {
                    required: true,
                    email: true
                },
                noi_dung: {
                    required: true,
                    minlength: 10
                },
                ct_hiddenRecaptcha: {
                    required: function () {
                        return grecaptcha.getResponse().length === 0;
                    }
                }
            },
            messages: {
                ho_ten: {
                    required: "Vui lòng nhập họ tên",
                    minlength: "Họ tên phải có ít nhất 2 ký tự"
                },
                dien_thoai: {
                    required: "Vui lòng nhập số điện thoại",
                    digits: "Số điện thoại chỉ chứa chữ số",
                    minlength: "Số điện thoại tối thiểu 9 số",
                    maxlength: "Số điện thoại tối đa 11 số"
                },
                email: {
                    required: "Vui lòng nhập email",
                    email: "Email không hợp lệ"
                },
                noi_dung: {
                    required: "Vui lòng nhập nội dung",
                    minlength: "Nội dung phải có ít nhất 10 ký tự"
                },
                ct_hiddenRecaptcha: {
                    required: "Vui lòng xác nhận reCAPTCHA"
                }
            },
            errorElement: "div",
            errorClass: "invalid-feedback",
            highlight: function (element) {
                $(element).addClass("is-invalid").removeClass("is-valid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid").addClass("is-valid");
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") == "ct_hiddenRecaptcha") {
                    error.insertAfter("#RecaptchaField1");
                } else {
                    // Nếu input nằm trong .form-group của Bootstrap
                    if (element.parent(".form-group").length) {
                        error.appendTo(element.parent());
                    } else {
                        error.insertAfter(element);
                    }
                }
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>
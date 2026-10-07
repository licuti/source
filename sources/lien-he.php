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
                $dia_chi    =   validate_content($_POST['dia_chi']);
                if(!empty($dien_thoai)){
                    $data['ho_ten']     =   $ho_ten;
                    $data['so_dien_thoai']        =   $dien_thoai;
                    $data['email']      =   $email;
                    $data['dia_chi']    =   $dia_chi;
                    $data['noi_dung']   =   $noidung;
                    $data['da_xem']     =   0;

                    // var_dump($data);
                    // die();
                    if (strlen(strstr($data['noi_dung'], 'http')) > 0) {
                        $d->alert("Nội dung gửi không hợp lệ!");
                    }elseif(strlen($data['so_dien_thoai']) < 9 or strlen($data['so_dien_thoai']) > 11 or substr ($data['so_dien_thoai'],0,1)!='0'){
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

<div class="block">
    <div class="container-fluid">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6 pt-3 d-flex flex-column">
                <div class="heading-content mb-lg-3 mt-lg-3"><?= $row['mo_ta'] ?></div>
                <div class="box-iframe box-iframe-contact flex-fill">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.004819828108!2d106.70278927573628!3d10.810942158554722!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752895f900334d%3A0x93aa55bb7ac179f!2zMjEzIENodSBWxINuIEFuLCBCw6xuaCBUaOG6oW5oLCBI4buTIENow60gTWluaCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1787806009286!5m2!1svi!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    <?= $info['map'] ?>
                </div>
            </div>
            <div class="col-lg-6">
                <form id="contact-form" method="post" action="" autocomplete="off">
                    <div class="heading-content">
                        <h2>GỬI TIN NHẮN</h2>
                    </div>
                    <input type="hidden" value="<?=$_SESSION['token']?>" name="_token" />
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Họ & tên*</label>
                                <input type="text" name="ho_ten" placeholder="<?= $d->getTxt(5) ?>" required="required" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Điện thoại</label>
                                <input type="text" name="dien_thoai" placeholder="<?= $d->getTxt(6) ?>" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" placeholder="Email" required="required" class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label>Lĩnh vực cần quan tâm *</label>
                                <select class="form-select form-control">
                                    <option value="">Chọn lĩnh vực</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label>Loại hình doanh nghiệp *</label>
                                <select class="form-select form-control">
                                    <option value="">Công ty TNHH ABC</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label>Nội Dung</label>
                                <textarea  name="noi_dung" class="form-control" placeholder="<?= $d->getTxt(8) ?>" rows="3" required="required"></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="g-recaptcha-contact" data-sitekey="<?=_sitekey?>" id="RecaptchaField1"></div>
                            <input type="hidden" class="hiddenRecaptcha" name="ct_hiddenRecaptcha" id="ct_hiddenRecaptcha">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="lienhe" class="btn-custom btn-x btn-solid w-100 rounded-2"><?= $d->getTxt(13) ?> <i class="fa-sharp fa-light fa-paper-plane ms-1"></i></button>
                            <p class="small text-center mt-2">Chúng tôi sẽ phản hồi trong vòng 24 giờ làm việc</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
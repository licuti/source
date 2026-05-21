<?php
if(!isset($_SESSION['id_login'])){
    $d->location(URLPATH.$d->getCate(21,'alias').".html");
    exit();
}

$params = getQueryParams();

if(isset($_POST['capnhat_thongtin']) && $_SESSION['token'] == $_POST['_token']){
    token();
    $data = [
        'ho_ten'     => addslashes(replaceHTMLCharacter($_POST['ho_ten'])),
        'email'      => addslashes(replaceHTMLCharacter($_POST['email'])),
        'dien_thoai' => addslashes(replaceHTMLCharacter($_POST['dien_thoai'])),
        'dia_chi'    => addslashes(replaceHTMLCharacter($_POST['dia_chi'])),
        'md5_email'  => addslashes(MD5($_POST['email']))
    ];
    
    if(!empty($_POST['mat_khau'])){
        $data['mat_khau'] = MD5($_POST['mat_khau']);
    }
    
    $d->reset();
    $d->setTable('#_thanhvien');
    $d->setWhere('id', (int)($_SESSION['id_login']));
    
    if($d->update($data)){
        $thongbao_tt      = 'Cập nhật thành công';
        $thongbao_icon    = 'success';
        $thongbao_content = '';
        $thongbao_url     = URLPATH . $com . ".html";
    }
}

// Giả lập $user_login từ session nếu chưa có ở global
$user_login_raw = $d->simple_fetch("SELECT * FROM #_thanhvien WHERE id = " . (int)$_SESSION['id_login']);
$user_login = (object)$user_login_raw;
?>
<main>
    <section class="breadcrumb__area box-plr-75 py-4">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?=URLPATH?>">Trang chủ</a></li>
                    <li class="breadcrumb-item active"><?= $row->ten ?? 'Thành viên' ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <div class="product-details pb-100">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="dashboard-menu p-3 border rounded bg-light">
                        <ul class="nav flex-column gap-2" role="tablist">
                            <?php if(($user_login->loai ?? 0) == 1): ?>
                            <li class="nav-item">
                                <a class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" href="#dashboard" role="tab"><i class="fas fa-chart-line me-2"></i> <?=$d->getTxt(92)?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="ctv-orders-tab" data-bs-toggle="tab" href="#orders_ctv" role="tab"><i class="fas fa-users me-2"></i> <?=$d->getTxt(91)?></a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (($user_login->loai ?? 0) != 1) ? 'active' : '' ?>" id="orders-tab" data-bs-toggle="tab" href="#orders" role="tab"><i class="fas fa-box me-2"></i> <?=$d->getTxt(113)?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="address-tab" data-bs-toggle="tab" href="#address" role="tab"><i class="fas fa-map-marker-alt me-2"></i> <?=$d->getTxt(94)?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="account-detail-tab" data-bs-toggle="tab" href="#account-detail" role="tab"><i class="fas fa-user-cog me-2"></i> <?=$d->getTxt(88)?></a>
                            </li>
                            <hr>
                            <li class="nav-item">
                                <a class="nav-link text-danger" href="<?=URLPATH?>?logout=1"><i class="fas fa-sign-out-alt me-2"></i> <?=$d->getTxt(95)?></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-9 mt-4 mt-md-0">
                    <div class="tab-content account dashboard-content">
                        <?php if(($user_login->loai ?? 0) == 1): ?>
                            <div class="tab-pane fade active show" id="dashboard" role="tabpanel">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?=$d->getTxt(111)?></h5>
                                        <a class="btn btn-sm btn-outline-primary" href="<?=URLPATH.$d->getCate(2,'alias')?>.html?ctv=<?=$user_login->token_gioithieu?>">Xem gian hàng</a>
                                    </div>
                                    <div class="card-body">
                                        <?php include 'user_ctv.php'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="orders_ctv" role="tabpanel">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white py-3"><h5 class="mb-0"><?=$d->getTxt(91)?></h5></div>
                                    <div class="card-body"><?php include 'user_donhang_ctv.php'; ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="tab-pane fade <?= (($user_login->loai ?? 0) != 1) ? 'active show' : '' ?>" id="orders" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3"><h5 class="mb-0"><?=$d->getTxt(113)?></h5></div>
                                <div class="card-body"><?php include 'user_donhang.php'; ?></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="address" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?=$d->getTxt(94)?></h5>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal"><i class="fas fa-plus me-1"></i> Thêm địa chỉ</button>
                                </div>
                                <div class="card-body"><?php include 'user_diachi.php'; ?></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="account-detail" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3"><h5 class="mb-0"><?=$d->getTxt(88)?></h5></div>
                                <div class="card-body font-size-sm">
                                    <form method="post" action="" id="form-thongtin">
                                        <input type="hidden" value="<?=$_SESSION['token']?>" name="_token" />
                                        <div class="mb-3">
                                            <label class="form-label"><?=$d->getTxt(65)?> <span class="text-danger">*</span></label>
                                            <input required class="form-control" name="ho_ten" value="<?=$user_login->ho_ten?>" type="text">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input required class="form-control" name="email" value="<?=$user_login->email?>" type="email">
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?=$d->getTxt(6)?></label>
                                                <input class="form-control" name="dien_thoai" value="<?=$user_login->dien_thoai?>" type="text">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?=$d->getTxt(7)?></label>
                                                <input class="form-control" name="dia_chi" value="<?=$user_login->dia_chi?>" type="text">
                                            </div>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="save" id="changePassBtn" value="1">
                                            <label class="form-check-label pointer" for="changePassBtn" onclick="togglePasswordFields()"><?=$d->getTxt(90)?></label>
                                        </div>
                                        <div class="password-fields d-none">
                                            <div class="mb-3">
                                                <label class="form-label"><?=$d->getTxt(66)?> <span class="text-danger">*</span></label>
                                                <input class="form-control re_makhau" id="matkhau" name="mat_khau" type="password" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?=$d->getTxt(67)?> <span class="text-danger">*</span></label>
                                                <input class="form-control re_makhau" name="mat_khau2" type="password" disabled>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary" name="capnhat_thongtin" value="Submit"><?=$d->getTxt(89)?></button>
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

<script>
    function togglePasswordFields(){
        var isChecked = $("#changePassBtn").is(":checked");
        if(isChecked){
            $(".password-fields").removeClass("d-none");
            $(".re_makhau").prop('disabled', false);
        } else {
            $(".password-fields").addClass("d-none");
            $(".re_makhau").prop('disabled', true);
        }
    }
    
    $(document).ready(function() {
        $("#form-thongtin").validate({
            rules: {
                ho_ten: "required",
                email: { required: true, email: true },
                mat_khau: { required: "#changePassBtn:checked", minlength: 6 },
                mat_khau2: { required: "#changePassBtn:checked", equalTo: "#matkhau" }
            }
        });
    });
</script>
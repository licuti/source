<?php
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Thông tin website - Liên hệ
      </h1>
      <ol class="breadcrumb">
        <li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
        <li><a href="#">Quản trị giao diện</a></li>
        <li class="active">Thông tin website - Liên hệ</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="box box-primary">
            <form name="frm" method="post" class=" form-horizontal" action="index.php?p=<?=$_GET['p']?>&a=save&id=<?=@$_REQUEST['id']?>" enctype="multipart/form-data">
                <div class="box-body">
                    <?php if(count(get_json('lang'))>1){ ?>
                    <ul id="myTabs" class="nav nav-tabs" role="tablist">
                        <?php foreach (get_json('lang') as $key => $value) {?>
                        <li role="presentation" <?php if($key==0){?>class="active" <?php } ?>>
                            <a href="#<?=$value['code']?>" id="home-tab" role="tab" data-toggle="tab" aria-controls="<?=$value['code']?>" aria-expanded="true"><?=$value['name']?></a>
                        </li>
                        <?php } ?>
                    </ul>
                    <?php }?>
                    <div id="myTabContent" class="tab-content">
                        <?php foreach (get_json('lang') as $key => $value) {
                        $row = $d->simple_fetch("select * from #_thongtin where lang = '".$value['code']."' ");   
                        ?>
                        <div role="tabpanel" class="tab-pane fade <?php if($key==0){?> active in<?php }?>" id="<?=$value['code']?>" aria-labelledby="<?=$value['code']?>">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h3 class="box-title">Thông tin website</h3>
                                    <div class="form-group text-center">
                                        <div class="col-sm-5 col-sm-offset-2">
                                            <img src="../img_data/images/<?=$row['favicon']?>" id="review_favicon_<?=$value['code']?>" style="height: 100px" />
                                            <input type="hidden" name="favicon[]" value="<?=$row['favicon']?>" id="favicon_<?=$value['code']?>">
                                            <a class="box-uploadimg iframe-btn" style="display: block;" href="filemanager/dialog.php?type=1&field_id=favicon_<?=$value['code']?>&relative_url=1&multiple=0">
                                                <span><i class="fa fa-upload" aria-hidden="true"></i> Favicon</span>
                                            </a>
                                        </div>
                                        <div class="col-sm-5">
                                            <img src="../img_data/images/<?=$row['icon_share']?>" id="review_icon_share_<?=$value['code']?>" style="height: 100px;max-width: 100%;object-fit: contain;"/>
                                            <input type="hidden" name="icon_share[]"  value="<?=$row['icon_share']?>" id="icon_share_<?=$value['code']?>">
                                            <a class="box-uploadimg iframe-btn" href="filemanager/dialog.php?type=1&field_id=icon_share_<?=$value['code']?>&relative_url=1&multiple=0" style="display: block;">
                                                <span><i class="fa fa-upload" aria-hidden="true"></i> Logo</span>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Địa chỉ website:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập địa chỉ website" name="website[]" class="form-control" value="<?= $row['website']?>" >
                                        </div>
                                    </div>
                                    <div class=" form-group">
                                        <label class="col-sm-2 control-label">Copyright:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập copyright" name="coppy_right[]" class="form-control" value="<?= $row['coppy_right']?>" >
                                        </div>
                                    </div>
                                    <h3 class="box-title">Thông tin doanh nghiệp</h3>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Tên công ty:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập tên công ty" name="company[]" class="form-control" value="<?= $row['company']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Địa chỉ:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập địa chỉ công ty" name="address[]" class="form-control" value="<?= $row['address']?>" >
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Văn phòng:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập địa chỉ công ty" name="office[]" class="form-control" value="<?= $row['office']?>" >
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Hotline:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập hotline" name="hotline[]" class="form-control" value="<?= $row['hotline']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" style="padding-top: 0px;">Điện thoại:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập số điện thoại" name="dien_thoai[]" class="form-control" value="<?= $row['dien_thoai']?>" >
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Email:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập email" name="email[]" class="form-control" value="<?= $row['email']?>" >
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Thời gian hoạt động:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập thời gian hoạt động" name="thoi_gian[]" class="form-control" value="<?= $row['thoi_gian']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Bản đồ:</label>
                                        <div class="col-sm-10">
                                            <textarea name="map[]" class="form-control" rows="5" ><?=$row['map']?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Link google map:</label>
                                        <div class="col-sm-10">
                                            <textarea name="link_map[]" class="form-control" rows="3" ><?=$row['link_map']?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Iframe:</label>
                                        <div class="col-sm-10">
                                            <textarea name="iframe[]" class="form-control" rows="5" ><?=$row['iframe']?></textarea>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-sm-6">
                                    <h3 class="box-title">Mạng xã hội</h3>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">zalo:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập số zalo" name="zalo[]" class="form-control" value="<?= $row['zalo']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Messenger:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập id messenger" name="messenger[]" class="form-control" value="<?= $row['messenger']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Skype:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập id skype" name="skype[]" class="form-control" value="<?= $row['skype']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Facebook:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn trang cá nhân facebook" name="facebook[]" class="form-control" value="<?= $row['facebook']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Twitter:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn trang cá nhân Twitter" name="twitter[]" class="form-control" value="<?= $row['twitter']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">LinkedIn:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn trang cá nhân LinkedIn" name="linkedin[]" class="form-control" value="<?= $row['linkedin']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Youtube:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn kênh Youtube" name="youtube[]" class="form-control" value="<?= $row['youtube']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Pinterest:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn trang cá nhân Pinterest" name="pinterest[]" class="form-control" value="<?= $row['pinterest']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Instagram:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn trang cá nhân Instagram" name="instagram[]" class="form-control" value="<?= $row['instagram']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Telegram:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập đường dẫn kết nối Telegram" name="telegram[]" class="form-control" value="<?= $row['telegram']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Whatsapp:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập Whatsapp" name="whatsapp[]" class="form-control" value="<?= $row['whatsapp']?>" >
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Tiktok:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập Tiktok" name="tiktok[]" class="form-control" value="<?= $row['tiktok']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Shoppe:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập Shoppe" name="shoppe[]" class="form-control" value="<?= $row['shoppe']?>" >
                                        </div>
                                    </div>


                                    <h3 class="box-title">Recaptcha</h3>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Site key:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập site key" name="site_key[]" class="form-control" value="<?= $row['site_key']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Secret key:</label>
                                        <div class="col-sm-6">
                                            <input type="text" placeholder="Nhập secret key" name="secret_key[]" class="form-control" value="<?= $row['secret_key']?>" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php }?>
                    </div>
                    <?php if($d->checkPermission_edit($id_module)==1){ ?>
                    <div class="form-group">
                        <div class=" text-center">
                             <button type="submit" name="capnhat" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-save"></span> Cập nhật</button>
                        </div>
                    </div>
                    <?php }?>
                </div>
            </form>
        </div>
    </section>
</div>

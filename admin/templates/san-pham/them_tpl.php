<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            SẢN PHẨM <small>[<?php if(isset($_GET['id'])) echo "Sửa "; else echo "Thêm mới" ?>]</small>
        </h1>
        <ol class="breadcrumb">
          <li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
          <li><a href="#">Quản trị danh mục</a></li>
          <li class="active">Sản phẩm</li>
        </ol>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="box box-primary">
            <form name="frm" method="post" class=" form-horizontal" action="index.php?p=<?=$_GET['p']?>&a=save&id=<?=@$_REQUEST['id']?><?=$link_option?>" enctype="multipart/form-data">
                <div class="box-body">
                     <div class="row">
                        <div class="col-md-9">

                        <?php @include 'accordion-variants.php' ?>

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
                                if(isset($_GET['id'])){
                                    $row = $d->simple_fetch("select * from #_sanpham where id_code = ".(int)$_GET['id']." and lang = '".$value['code']."' ");
                                }      
                                ?>
                                <?php if(isset($_GET['id'])){ ?>
                                <input type="hidden" name="id_row[]" value="<?=$row['id']?>" />
                                <?php }?>
                                <div role="tabpanel" class="tab-pane fade in <?php if($key==0){?> active <?php }?>" id="<?=$value['code']?>" aria-labelledby="<?=$value['code']?>">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Tên sản phẩm <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" placeholder="Nhập tên sản phẩm" <?php if(!isset($_GET['id'])){ ?>OnkeyUp="addText(this,'#alias_<?=$value['code']?>','#title_<?=$value['code']?>')"<?php }else{?>OnkeyUp="addText(this,'','#title_<?=$value['code']?>')"<?php }?> name="ten[]" value="<?= $row['ten']?>" >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Đường dẫn <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-3" style="position: relative;">
                                           <input type="text" placeholder="" class="form-control" name="slug[]" id="slug_<?=$value['code']?>" value="<?php echo $row['slug']?>"  OnkeyUp="addText(this,'#slug_<?=$value['code']?>')" >
                                           <span style="position: absolute;right: -5px;top: 7px;color: #8b8989;font-size: 20px;">/</span>
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" placeholder="Nhập đường dẫn" class="form-control" name="alias[]" id="alias_<?=$value['code']?>" value="<?= $row['alias']?>"   OnkeyUp="addText(this,'#alias')" >
                                        </div>
                                    </div>
                                    <?php if(get_json('product', 'don_vi_tinh')==1){ ?>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Đơn vị tính <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-5">
                                            <input type="text" placeholder="Nhập đơn vị tính" class="form-control" name="dvt[]" id="dvt_<?=$value['code']?>" value="<?= $row['dvt']?>"  >
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Mô tả <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                           <textarea class="form-control ck_editor1" name="mo_ta[]"  id="mo_ta_<?=$value['code']?>" rows="3"><?= $row['mo_ta']?></textarea>
                                        </div>
                                        <script>
                                            CKEDITOR.replace( 'mo_ta_<?=$value['code']?>' ,{
                                                filebrowserBrowseUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserUploadUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserImageBrowseUrl : 'filemanager/dialog.php?type=1&editor=ckeditor&fldr=',
                                                // toolbar: 'Basic',
                                                height: '250px',
                                            });
                                        </script>
                                    </div>
                                    <?php  if(get_json('product', 'thong_so')==1){ ?>
                                     <div class="form-group">
                                        <label class="col-sm-2 control-label">Thông số kỹ thuật <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                            <?php
                                                $strjson = $row['thong_so_kt'];
                                                if ($strjson != '[]' and $strjson != '') {
                                                    $strjson = json_decode($strjson, true);
                                                    $addinfor="";
                                                    foreach ($strjson as $items2) {
                                                        $item = explode('%%%', $items2);
                                                        $addinfor .= "<div class=\"info-product\">Tên: <input type=\"text\" name=\"nameinfo_".$value['code']."[]\" value=\"" . $item[0] . "\" size=\"50\" /> : Chi tiết: <input type=\"text\" name=\"detailinfo_".$value['code']."[]\" value=\"" . $item[1] . "\" size=\"80\" /> | <strong class=\"delinfo\"> Xoá </strong></div>";
                                                    }
                                                } else {
                                                    $addinfor = '';
                                                }
                                            ?>
                                            <fieldset style="border: 1px solid #ccc;box-shadow: 1px 1px 10px 1px #7b787869;border-radius: 0px;padding: 15px 15px 0px 15px;">
                                                <p><strong  style="font-size: 14px;">VD:</strong> => Tên: 
                                                <input style="padding-left: 5px;padding-right: 5px;border: 1px solid  #d2d6de;height: 30px;" type="text" readonly="true" value="Hãng sản xuất"/> : Chi tiết: 
                                                <input style="padding-left: 5px;padding-right: 5px;border: 1px solid  #d2d6de;height: 30px;" readonly="true" type="text" value="Samsung"/></p>
                                                <p><strong  style="font-size: 14px;">Thêm thông số sản phẩm click => <span id="add_<?=$value['code']?>" style="color:red;cursor: pointer;">Thêm</span></strong></p>
                                                
                                                <div id="addinfo_<?=$value['code']?>">
                                                    <?= $addinfor ?>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                    <script>
                                         var i = 1;
                                            $('span#add_<?=$value['code']?>').click(function() {
                                                i++;
                                                $('div#addinfo_<?=$value['code']?>').append('<div class=\"info-product\">Tên: <input style=\"padding-left: 5px;padding-right: 5px;border: 1px solid  #d2d6de;\" type=\"text\" name=\"nameinfo_<?=$value['code']?>[]\" size=\"45\" /> : Chi tiết: <input style=\"padding-left: 5px;padding-right: 5px;border: 1px solid  #d2d6de;\" type=\"text\" name=\"detailinfo_<?=$value['code']?>[]\" size=\"45\" /> | <strong class=\"delinfo\"> Xoá </strong></div>');
                                            });
                                            $(document).on('click', '#addinfo_<?=$value['code']?> .delinfo', function() {
                                                $(this).parent().remove();
                                            });
                                    </script>
                                    <?php }   ?>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Chi tiết sản phẩm <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                           <textarea class="form-control" name="noi_dung[]"  id="noi_dung_<?=$value['code']?>" rows="7"><?= $row['noi_dung']?></textarea>
                                           
                                        </div>
                                        <script>
                                            CKEDITOR.replace( 'noi_dung_<?=$value['code']?>' ,{
                                                filebrowserBrowseUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserUploadUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserImageBrowseUrl : 'filemanager/dialog.php?type=1&editor=ckeditor&fldr=',
                                                height: '500px',
                                            });
                                        </script>
                                    </div>
                                    <?php /* ?>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Hướng dẫn mua hàng <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                           <textarea class="form-control" name="noi_dung_1[]"  id="noi_dung_1<?=$value['code']?>" rows="3"><?= $row['noi_dung_1']?></textarea>
                                           
                                        </div>
                                        <script>
                                            CKEDITOR.replace( 'noi_dung_1<?=$value['code']?>' ,{
                                                filebrowserBrowseUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserUploadUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserImageBrowseUrl : 'filemanager/dialog.php?type=1&editor=ckeditor&fldr='
                                            });
                                        </script>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Vận chuyển - bảo hành <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                           <textarea class="form-control" name="noi_dung_2[]"  id="noi_dung_2<?=$value['code']?>" rows="3"><?= $row['noi_dung_2']?></textarea>
                                           
                                        </div>
                                        <script>
                                            CKEDITOR.replace( 'noi_dung_2<?=$value['code']?>' ,{
                                                filebrowserBrowseUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserUploadUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                                filebrowserImageBrowseUrl : 'filemanager/dialog.php?type=1&editor=ckeditor&fldr='
                                            });
                                        </script>
                                    </div>
                                    <?php */ ?>

                                    <h3 class="box-title" style="margin-top: 20px;">Cấu hình SEO</h3>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Title <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                            <input type="text" placeholder="Nhập title" id="title_<?=$value['code']?>" class="form-control" name="title[]" id="title_<?=$value['code']?>" value="<?php echo $row['title']?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Keyword <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                            <textarea placeholder="Nhập từ khóa" class="form-control" rows="3" name="keyword[]"><?=$row['keyword']?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Description <?php if(count(get_json('lang'))>1){ ?>(<?=$value['code']?>)<?php }?>:</label>
                                        <div class="col-sm-10">
                                            <textarea placeholder="Nhập Description" class="form-control" rows="3" name="des[]"><?=$row['des']?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">HTML header<br><span style="color: #ff4b49;font-weight: 400;"><?=htmlentities('<head>...</head>')?></span> </label>
                                        <div class="col-sm-10">
                                            <textarea id="code2" placeholder="HTML - javascript chèn trước thẻ </head>" class="form-control" rows="3" name="seo_head[]"><?=$row['seo_head']?></textarea>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">HTML footer <br><span style="color: #ff4b49;font-weight: 400;"><?=htmlentities('<body>...</body>')?></span> </label>
                                        <div class="col-sm-10">
                                            <textarea placeholder="HTML - javascript chèn trước thẻ </body>" id="code" class="form-control" rows="3" name="seo_body[]"><?=$row['seo_body']?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php } ?>
                                
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!--h3 class="box-title">Thông tin chung</h3-->
                            <ul id="myTabs" class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active">
                                    <a href="#thongtinsp" role="tab" data-toggle="tab" aria-controls="thongtinsp" aria-expanded="true">Thông tin</a>
                                </li>
                                <li role="presentation">
                                    <a href="#album_anh" role="tab" data-toggle="tab" aria-controls="album_anh" aria-expanded="true">Album hình</a>
                                </li>
                            </ul>
                            <div id="myTabContent" class="tab-content">
                                <div role="tabpanel" class="tab-pane fade active in" id="thongtinsp" aria-labelledby="thongtinsp">
                                    <div class="form-group m0 hinh_anh" >
                                        <label>Hình ảnh:</label>
                                        <span class="box-img2">
                                            <?php if(isset($_GET['id']) and $row['hinh_anh'] != ''){ ?>
                                            <img src="../img_data/images/<?php echo $row['hinh_anh']?>" id="review_hinh_anh" alt="NO PHOTO" />
                                            <button class="btn btn-xs btn-danger" type="button" onclick="xoa_img('_sanpham','hinh_anh', '<?=$_GET['id']?>','')"><i class="fa fa-trash"></i></button>
                                            <?php }else{ ?>
                                            <img src="img/no-image.png"  style="max-width: 100%;max-height: 100%;object-fit: contain;" id="review_hinh_anh" alt="NO PHOTO" />
                                            <?php }?>
                                            <input type="hidden" value="<?=$row['hinh_anh']?>" name="hinh_anh" id="hinh_anh" class=" form-control">
                                            <a href="filemanager/dialog.php?type=1&field_id=hinh_anh&relative_url=1&multiple=0" class="btn btn-upload2 iframe-btn" > <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh</a>
                                        </span>
                                    </div>
                                     <div class="form-group m0">
                                        <label>Danh mục:</label>
                                        <select name="id_loai" class="form-control select2">
                                            <option value="0">Chọn danh mục</option>
                                            <?=$loai?>
                                        </select>
                                    </div>

                                    <?php if(get_json('product', 'video') == 1){ ?>
                                        <iframe style="width: 100%;margin-bottom: 10px;" id="if_video" height="200" src="https://www.youtube.com/embed/<?php if(isset($_GET['id'])){echo $row['video'];}?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                        <div class="form-group m0">
                                            <label>Mã Video: <span style="font-weight: 400;color: red;font-style: italic;font-size: 14px;">https://www.youtube.com/watch?v={Mã video}</span></label>
                                            <input type="text" placeholder="Nhập mã video" class="form-control" onchange="$('#if_video').attr('src', 'https://www.youtube.com/embed/'+$(this).val())" name="ma_video" value="<?= $row['video']?>">
                                        </div>
                                    <?php } ?>

                                    <?php if(get_json('product', 'file') == 1){ ?>
                                        <?php if(isset($_GET['id'])){ 
                                            if($row['file'] != ''){
                                                $link = URLPATH.'uploads/files/'.$row['file'];
                                            }elseif($row['link_khac'] !=''){
                                                $link =  $row['link_khac'];
                                            }else{
                                                $link = '';
                                            }
                                        ?>
                                        <?php if($link!=''){ ?>
                                        <div class="form-group m0 hinh_anh" >
                                            <iframe class="iframe" src="http://docs.google.com/gview?url=<?=$link?>&embedded=true" style="height: 200px;width: 100%;" frameborder="0"></iframe>
                                            <iframe style="width: 100%;"> src="<?=$link?>" frameborder="0"></iframe>
                                        </div>
                                        <?php }
                                    } ?>
                                    <div class="form-group m0">
                                        <label>Upload file:</label>
                                        <input type="file" name="file_download" class="form-control" >
                                    </div>
                                    <div class="form-group m0">
                                        <label>Đường dẫn file khác:</label>
                                        <div class="row m-5">
                                            <div class="col-sm-9 p5">
                                                <input type="text" placeholder="Nhập đường dẫn file" class="form-control" name="link_khac" value="<?= $row['link_khac']?>">
                                            </div> 
                                            <div class="col-sm-3 p5">
                                                <select class=" form-control" name="loai_file">
                                                    <option <?=$row['loai_file']=='pdf'?'selected':''?> value="pdf">PDF</option>
                                                    <option <?=$row['loai_file']=='docx'?'selected':''?> value="docx">WORD</option>
                                                    <option <?=$row['loai_file']=='xlsx'?'selected':''?> value="xlsx">EXCEL</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <?php }?>

                                    <?php if(get_json('product', 'ma_sp')==1){ ?>
                                    <div class="form-group m0">
                                        <label>Mã sản phẩm:</label>
                                        <input type="text" placeholder="Nhập mã sản phẩm" class="form-control" name="ma_sp" id="ma_sp" value="<?= $row['ma_sp']?>">
                                    </div>
                                    <?php }?>
                                

                                    <?php if(get_json('product', 'gia') == 1){ ?>
                                    <div class="form-group m0">
                                        <label>Giá thường: <span class="text-gia" style="color: red;"><?php if(isset($_GET['id'])){ echo numberformat($row['gia']);} ?></span></label>
                                        <input type="number" placeholder="Nhập giá " class="form-control" name="gia" id="gia" value="<?= $row['gia']?>">
                                    </div>
                                    <?php } ?>

                                    
                                    <?php  if(get_json('product', 'khuyen_mai')==1){ ?>
                                        <div class="form-group m0">
                                            <label>Giá khuyến mãi: <span class="text-km" style="color: red;"><?php if(isset($_GET['id'])){ echo numberformat($row['khuyen_mai']);} ?></span></label>
                                            <input type="number" placeholder="Nhập giá khuyến mãi" class="form-control" name="khuyen_mai" id="khuyen_mai" value="<?= $row['khuyen_mai']?>">
                                        </div>
                                    <?php }  ?>

                                    <div class="form-group m0">
                                        <label>Khối lượng (<?= $config['weight']['unit'] ?? 'g' ?>):</label>
                                        <input type="number" placeholder="Nhập khối lượng (<?= $config['weight']['unit'] ?? 'g' ?>)" class="form-control" name="weight" id="weight" value="<?= $row['weight']?>">
                                        <p class="help-block">Dùng để tính phí vận chuyển theo cân nặng.</p>
                                    </div>


                                    <!-- <div class="form-group m0">
                                        <label>Đã bán:</label>
                                        <input type="text" placeholder="Nhập số lượng đã bán" class="form-control" name="da_ban" id="da_ban" value="<?= $row['da_ban']?>">
                                    </div> -->

                                    <div class="form-group m0">
                                        <label>Số thứ tự:</label>
                                        <input type="number" placeholder="Nhập số thứ tự" class="form-control" name="so_thu_tu" id="so_thu_tu" value="<?= $row['so_thu_tu']?>">
                                    </div>

                                    <div class="form-group m0">
                                        <div class="">
                                            <label class="checkbox-inline">
                                                <input name="hien_thi" <?php if(isset($items[0]['hien_thi'])) { if(@$items[0]['hien_thi']==1) echo 'checked="checked"';} else echo'checked="checked"'; ?> type="checkbox"> Hiển thị
                                            </label>
                                            <label class="checkbox-inline">
                                                <input name="tieu_bieu" <?php if(@$items[0]['tieu_bieu']==1) echo 'checked="checked"'; ?> type="checkbox"> Nổi bật
                                            </label>
                                            <label class="checkbox-inline">
                                                <input name="sp_moi" <?php if(@$items[0]['sp_moi']==1) echo 'checked="checked"'; ?> type="checkbox"> Mới
                                            </label>
                                            <label class="checkbox-inline">
                                                <input name="sp_hot" <?php if(@$items[0]['sp_hot']==1) echo 'checked="checked"'; ?> type="checkbox"> Hot
                                            </label>
                                            <label class="checkbox-inline">
                                                <input name="sp_sale" <?php if(@$items[0]['sp_sale']==1) echo 'checked="checked"'; ?> type="checkbox"> Sale
                                            </label>
                                            <label class="checkbox-inline">
                                                <input name="sp_top" <?php if(@$items[0]['sp_top']==1) echo 'checked="checked"'; ?> type="checkbox"> Top
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group m0">
                                        <div class="">
                                            <label class="checkbox-inline">
                                                <input name="nofollow" <?php if(@$items[0]['nofollow']==1) echo 'checked="checked"'; ?> type="checkbox"> Nofollow
                                            </label>
                                            <label class="checkbox-inline">
                                                <input name="noindex" <?php if(@$items[0]['noindex']==1) echo 'checked="checked"'; ?> type="checkbox"> Noindex
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div role="tabpanel" class="tab-pane fade" id="album_anh" aria-labelledby="album_anh">
                                    <div class="form-group m0">
                                        <label>Hình chi tiết:</label>
                                        <div class="row m-10" id="list_album">
                                            <?php if(isset($_GET['id']) ){ 
                                                $row_album = $d->o_fet("select * from #_sanpham_hinhanh where id_sp = ".(int)$_GET['id']."  order by stt ASC");
                                            ?>
                                            <?php foreach ($row_album as $key => $value) {?>
                                            <div class="col-sm-4 p10" id="album_<?=$value['id']?>">
                                                <div class="item-album">
                                                    <img src="../img_data/images/<?=$value['hinh_anh']?>" />
                                                    <button onclick="xoa_hinh_sp(<?=$value['id']?>)" class="btn btn-delete-album" type="button" ><i class="fa fa-trash"></i></button>
                                                </div>
                                            </div>
                                            <?php } ?>
                                            <?php } ?>
                                            
                                        </div>
                                        
                                    </div>
                                    <input type="hidden" id="album" class="form-control" />
                                    <a href="filemanager/dialog.php?type=1&field_id=album&relative_url=1&multiple=1" class="btn btn-upload2 iframe-btn" > <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh</a>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <a href="/admin/index.php?p=san-pham&a=man" type="button" class="btn btn-default">
                                    <i class="fas fa-long-arrow-alt-left"></i> Quay lại
                                </a>
                                <?php if($d->checkPermission_edit($id_module)==1){ ?>
                                    <button type="submit" value="capnhat" name="capnhat" class="btn btn-primary ml-3"><span class="glyphicon glyphicon-floppy-save"></span>Lưu</button>
                                    <button type="submit" value="capnhat_sua" name="capnhat" class="btn btn-primary ml-3"><span class="glyphicon glyphicon-floppy-save"></span> Lưu và Sửa</button>
                                <?php } ?>
                            </div>
                        </div>
                     </div>
                </div>
            </form>
        </div>
    </section>
</div>




<script>
    function formatNumber(nStr, decSeperate, groupSeperate) {
        nStr += '';
        x = nStr.split(decSeperate);
        x1 = x[0];
        x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + groupSeperate + '$2');
        }
        return x1 + x2;
    };
    function xoa_hinh_sp(id){
        if(!confirm('Xác nhận xóa?')) return false;
        $.ajax({
          url: "./sources/ajax.php",
          type:'POST',
          data:"id="+id+"&do=xoa_anh_sp",
          success: function(){
            $("#album_"+id).remove();
          }
        })
    }
    function xoa_thuoctinh(id){
        if(!confirm('Xác nhận xóa?')) return false;
        $.ajax({
          url: "./sources/ajax.php",
          type:'POST',
          data:"id="+id+"&do=xoa_thuoctinh",
          success: function(){
            $("#item-thuoctinh_"+id).remove();
          }
        })
    }
    function xoa_hinh_sp2(id){
        if(!confirm('Xác nhận xóa?')) return false;
        $.ajax({
          url: "./sources/ajax.php",
          type:'POST',
          data:"id="+id+"&do=xoa_anh_sp",
          success: function(){
            $("#album2_"+id).remove();
          }
        })
    }
   $('#gia').keyup(function(){
        var num = $(this).val();
        $('.text-gia').html(formatNumber(num,',','.'));
    });
    $('#khuyen_mai').keyup(function(){
        var num = $(this).val();
        $('.text-km').html(formatNumber(num,',','.'));
    })
    $('#gia_flash_sale').keyup(function(){
        var num = $(this).val();
        $('.text-sale').html(formatNumber(num,',','.'));
    })
</script>
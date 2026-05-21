<style>
    .box-header .form-control{
        font-size:1.3rem;
        height:30px;
    }
    .box-body{
        padding:0.5rem 0.2rem 1rem 0.5rem;
    }
    .table td,.table th{
        font-size:1.3rem!important;
       
    }
    .table th{
        padding:0.5rem 0.5rem!important;
    }
    .table td{
        padding:0.2rem 0.5rem!important;
    }
</style>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Danh mục
      </h1>
      <ol class="breadcrumb">
        <li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
        <li><a href="#">Cấu hình website</a></li>
        <li class="active">Danh mục</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="btn-group">
                    <select id="action" name="action" onclick="form_submit(this)" class="form-control">
                        <option selected="">Tác vụ</option>
                        <option value="delete">Xóa</option>
                    </select>
                </div>
                <div class="btn-group">
                    <input type="text" value="<?=$_GET['key']?>" id="key-search" class=" form-control" placeholder="Nhập nội dung cần tìm" />
                </div>
                <div class="btn-group">
                    <select id="search-input" class="form-control" data-p="<?=$_GET['p']?>">
                        <option>Tìm theo...</option>
                        <option <?=$_GET['seach']=="ten"?'selected':''?> value="ten">Tên danh mục</option>
                        <option <?=$_GET['seach']=="id_code"?'selected':''?> value="id_code">ID danh mục</option>
                    </select>
                </div>
                <div class="btn-group">
                    <a href="index.php?p=<?=$_GET['p']?>&a=<?=$_GET['a']?>" class="btn btn-default"><i class="fa fa-refresh" aria-hidden="true"></i></a>
                </div>
                <?php if($d->checkPermission_edit($id_module)==1){ ?>
                <div class="pull-right">
                    <a href="index.php?p=<?=$_GET['p']?>&a=add" class="btn btn-primary pull-right"><i class="glyphicon glyphicon-plus"></i> Thêm mới</a>
                </div>
                <?php }?>
                <div class="clearfix"></div>
            </div>
            <div class="box-body">
                <form id="form" method="post" action="index.php?p=category&a=delete_all" role="form">
                    <table class="table table-bordered table-striped table-hover  table-primary" id="dataTable1">
                        <thead>
                            <tr>
                                <th style="width:50px"  class="text-center">
                                    <input class="chk_box checkall" type="checkbox" name="chk" value="0"  id="check_all">
                                </th>
                                <th style="width:70px" class="text-center">STT</th>
                                <th>Danh mục</th>
                                <th style="width:100px; text-align: center;">Hình ảnh</th>
                                <th style="width:120px; text-align: center;">Module</th>
                                <th style="width:80px; text-align: center;">Trang chủ</th>
                                <th style="width:60px; text-align: center;">Nổi bật</th>
                                <th style="width:60px; text-align: center;">Menu</th>
                                
                                <th style="width:60px; text-align: center;">Hiển thị</th>
                                <th style="width:80px; text-align: center;">Trạng thái</th>
                                <th style="width:120px; text-align: center;">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count=count($items); for($i=0; $i<$count; $i++){ ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <input class="chk_box" type="checkbox" name="chk_child[]" value="<?=$items[$i]['id_code']?>">
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($d->checkPermission_edit($id_module)==1){ ?>
                                    <input type="number" value="<?=$items[$i]['so_thu_tu']?>" class="a_stt" data-table="#_category" data-col="so_thu_tu" data-id="<?=$items[$i]['id_code']?>" />
                                    <?php }else{?>
                                    <label class="label label-primary"><?=$items[$i]['so_thu_tu']?></label>
                                    <?php }?>
                                </td>
                                <td style="text-align:left">
                                    <a href="index.php?p=category&a=edit&id=<?=$items[$i]['id_code']?>&page=<?=@$_GET['page']?>"><?=$items[$i]['ten']?></a> 
                                </td>
                                <td  class="text-center">
                                    <?php if($items[$i]['hinh_anh'] <> ''){ ?>
                                    <a href="index.php?p=category&a=edit&id=<?=$items[$i]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-sm" title="Xóa">
                                       <img src="../img_data/images/<?=$items[$i]['hinh_anh'] ?>" class='img_object50'>
                                    </a>
                                    <?php } ?>
                                </td>
                                <td class="text-center"><?php $module=$d->simple_fetch("select * from #_module where id={$items[$i]['module']}"); echo $module['title']?></td>
                                <td class="text-center">
                                    <input class="chk_box"  <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','home','<?=$items[$i]['id_code']?>')" <?php if($items[$i]['home'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box"  <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','tieu_bieu','<?=$items[$i]['id_code']?>')" <?php if($items[$i]['tieu_bieu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','menu','<?=$items[$i]['id_code']?>')" <?php if($items[$i]['menu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','hien_thi','<?=$items[$i]['id_code']?>')" <?php if($items[$i]['hien_thi'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-left">
                                    <?php if($items[$i]['nofollow']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Nofollow</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Dofollow</span>
                                    <?php }?><br>
                                    <?php if($items[$i]['noindex']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Noindex</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Index</span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <a  href="index.php?p=category&a=edit&id=<?=$items[$i]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-xs btn-warning" title="Sửa"><i class="glyphicon glyphicon-edit"></i> Sửa</a>
                                    <?php /*if($d->checkPermission_dele($id_module)==1){ ?>
                                    <a  href="index.php?p=category&a=delete&id=<?=$items[$i]['id_code']?>&page=<?=@$_GET['page']?>" onClick="if(!confirm('Xác nhận xóa danh mục và các danh mục con trực thuộc?')) return false;" class="btn btn-xs btn-danger" title="Xóa"><i class="glyphicon glyphicon-remove"></i> Xóa</a>
                                    <?php }*/ ?>
                                </td>
                            </tr>
                            <!-- // cap 1 -->
                            <?php
                                $child_items = $d->o_fet("select * from #_category where id_loai ='".$items[$i]['id_code']."' and lang='".LANG."' order by so_thu_tu asc");

                                      $count_child=count($child_items);
                                      for($j=0; $j<$count_child; $j++){
                                ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <input type="checkbox" class="chk_box" name="chk_child[]" value="<?=$child_items[$j]['id_code']?>">
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($d->checkPermission_edit($id_module)==1){ ?>
                                    <input type="number" value="<?=$child_items[$j]['so_thu_tu']?>" class="a_stt" data-table="#_category" data-col="so_thu_tu" data-id="<?=$child_items[$j]['id_code']?>" />
                                    <?php }else{?>
                                    <label class="label label-default"><?=$child_items[$j]['so_thu_tu']?></label>
                                    <?php }?>
                                </td>
                                <td style="text-align:left">
                                    <a style="padding-left:5px" href="index.php?p=category&a=edit&id=<?=$child_items[$j]['id_code']?>&page=<?=@$_GET['page']?>">|____ <?=$child_items[$j]['ten']?></a>
                                </td>
                                <td  class="text-center">
                                    <?php if($child_items[$j]['hinh_anh'] <> ''){ ?>
                                    <a href="index.php?p=category&a=edit&id=<?=$child_items[$j]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-sm" title="Xóa">
                                    <img src="../img_data/images/<?=$child_items[$j]['hinh_anh'] ?>" class='img_object50'>
                                    </a>
                                    <?php } ?>
                                </td>
                                <td class="text-center"><?php $module=$d->simple_fetch("select * from #_module where id={$child_items[$j]['module']}"); echo $module['title']?></td>
                                <td class="text-center">
                                    <input class="chk_box" type="checkbox" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> onclick="on_check(this,'#_category','home','<?=$child_items[$j]['id_code']?>')" <?php if($child_items[$j]['home'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" type="checkbox" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> onclick="on_check(this,'#_category','tieu_bieu','<?=$child_items[$j]['id_code']?>')" <?php if($child_items[$j]['tieu_bieu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" type="checkbox" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> onclick="on_check(this,'#_category','menu','<?=$child_items[$j]['id_code']?>')" <?php if($child_items[$j]['menu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                
                                <td class="text-center">
                                    <input class="chk_box" type="checkbox" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> onclick="on_check(this,'#_category','hien_thi','<?=$child_items[$j]['id_code']?>')" <?php if($child_items[$j]['hien_thi'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-left">
                                    <?php if($child_items[$j]['nofollow']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Nofollow</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Dofollow</span>
                                    <?php }?><br>
                                    <?php if($child_items[$j]['noindex']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Noindex</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Index</span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?p=category&a=edit&id=<?=$child_items[$j]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-xs btn-warning" title="Sửa"><i class="glyphicon glyphicon-edit"></i> Sửa</a>
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <a href="index.php?p=category&a=delete&id=<?=$child_items[$j]['id_code']?>&page=<?=@$_GET['page']?>" onClick="if(!confirm('Xác nhận xóa danh mục và các danh mục con trực thuộc?')) return false;" class="btn btn-xs btn-danger" title="Xóa"><i class="glyphicon glyphicon-remove"></i> Xóa</a>
                                    <?php }?>
                                </td>
                            </tr>
                            <!-- cap 2 -->
                            <?php
                                $child_items_2 = $d->o_fet("select * from #_category where id_loai ='".$child_items[$j]['id_code']."' and lang='".LANG."'  order by so_thu_tu asc");
                                $count_child_2=count($child_items_2);
                                for($k=0; $k<$count_child_2; $k++){
                                ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="chk_box" name="chk_child[]" value="<?=$child_items_2[$k]['id_code']?>">
                                </td>
                                <td class="text-center">
                                    <?php if($d->checkPermission_edit($id_module)==1){ ?>
                                    <input type="number" value="<?=$child_items_2[$k]['so_thu_tu']?>" class="a_stt" data-table="#_category" data-col="so_thu_tu" data-id="<?=$child_items_2[$k]['id_code']?>" />
                                    <?php }else{?>
                                    <?=$child_items_2[$k]['so_thu_tu']?>
                                    <?php }?>
                                </td>
                                <td style="text-align:left">
                                    <a style="padding-left:20px" href="index.php?p=category&a=edit&id=<?=$child_items_2[$k]['id_code']?>&page=<?=@$_GET['page']?>">|____ <?=$child_items_2[$k]['ten']?></a>
                                </td>
                                <td  class="text-center">
                                    <?php if($child_items_2[$k]['hinh_anh'] <> ''){ ?>
                                    <a href="index.php?p=category&a=edit&id=<?=$child_items_2[$k]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-sm" title="Xóa">
                                    <img src="../img_data/images/<?=$child_items_2[$k]['hinh_anh'] ?>" class='img_object50'>
                                    </a>
                                    <?php } ?>
                                </td>
                                <td class="text-center"><?php $module=$d->simple_fetch("select * from #_module where id={$child_items_2[$k]['module']}"); echo $module['title']?></td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','home','<?=$child_items_2[$k]['id_code']?>')" <?php if($child_items_2[$k]['home'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','tieu_bieu','<?=$child_items_2[$k]['id_code']?>')" <?php if($child_items_2[$k]['tieu_bieu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','menu','<?=$child_items_2[$k]['id_code']?>')" <?php if($child_items_2[$k]['menu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                
                                <!-- <td>
                                    <input class="chk_box" type="checkbox" onclick="on_check(this,'#_category','is_top','<?=$child_items_2[$k]['id']?>')" <?php if($child_items_2[$k]['is_top'] == 1) echo 'checked="checked"'; ?>>
                                    </td> -->
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','hien_thi','<?=$child_items_2[$k]['id_code']?>')" <?php if($child_items_2[$k]['hien_thi'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-left">
                                    <?php if($child_items_2[$k]['nofollow']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Nofollow</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Dofollow</span>
                                    <?php }?><br>
                                    <?php if($child_items_2[$k]['noindex']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Noindex</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Index</span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?p=category&a=edit&id=<?=$child_items_2[$k]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-xs btn-warning" title="Sửa"><i class="glyphicon glyphicon-edit"></i> Sửa</a>
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <a href="index.php?p=category&a=delete&id=<?=$child_items_2[$k]['id_code']?>&page=<?=@$_GET['page']?>" onClick="if(!confirm('Xác nhận xóa danh mục và các danh mục con trực thuộc?')) return false;" class="btn btn-xs btn-danger" title="Xóa"><i class="glyphicon glyphicon-remove"></i> Xóa</a>
                                    <?php }?>
                                </td>
                            </tr>
                            <!-- cap 3 -->
                            <?php
                                $child_items_3 = $d->o_fet("select * from #_category where id_loai ='".$child_items_2[$k]['id_code']."' and lang='".LANG."'  order by so_thu_tu asc");
                                  $count_child_3=count($child_items_3);
                                  for($m=0; $m<$count_child_3; $m++){
                                ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <input type="checkbox" class="chk_box" name="chk_child[]" value="<?=$child_items_3[$m]['id_code']?>">
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($d->checkPermission_edit($id_module)==1){ ?>
                                    <input type="number" value="<?=$child_items_3[$m]['so_thu_tu']?>" class="a_stt" data-table="#_category" data-col="so_thu_tu" data-id="<?=$child_items_3[$m]['id_code']?>" />
                                    <?php }else{?>
                                    <?=$child_items_3[$m]['so_thu_tu']?>
                                    <?php }?>
                                </td>
                                <td style="text-align:left">
                                    <a style="padding-left:40px" href="index.php?p=category&a=edit&id=<?=$child_items_3[$m]['id_code']?>&page=<?=@$_GET['page']?>">|____<?=$child_items_3[$m]['ten']?></a>
                               </td>
                                <td class="text-center">
                                    <?php if($child_items_3[$m]['hinh_anh'] <> ''){ ?>
                                    <img src="../img_data/images/<?=$child_items_3[$m]['hinh_anh'] ?>" class='img_object50'>
                                    <?php } ?>
                                </td>
                                <td class="text-center"><?php $module=$d->simple_fetch("select * from #_module where id={$child_items_3[$m]['module']}"); echo $module['title']?></td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','home','<?=$child_items_3[$m]['id_code']?>')" <?php if($child_items_3[$m]['home'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','tieu_bieu','<?=$child_items_3[$m]['id_code']?>')" <?php if($child_items_3[$m]['tieu_bieu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','menu','<?=$child_items_3[$m]['id_code']?>')" <?php if($child_items_3[$m]['menu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','hien_thi','<?=$child_items_3[$m]['id_code']?>')" <?php if($child_items_3[$m]['hien_thi'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-left">
                                    <?php if($child_items_3[$m]['nofollow']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Nofollow</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Dofollow</span>
                                    <?php }?><br>
                                    <?php if($child_items_3[$m]['noindex']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Noindex</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Index</span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?p=category&a=edit&id=<?=$child_items_3[$m]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-xs btn-warning" title="Sửa"><i class="glyphicon glyphicon-edit"></i> Sửa</a>
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <a href="index.php?p=category&a=delete&id=<?=$child_items_3[$m]['id_code']?>&page=<?=@$_GET['page']?>" onClick="if(!confirm('Xác nhận xóa danh mục và các danh mục con trực thuộc?')) return false;" class="btn btn-xs btn-danger" title="Xóa"><i class="glyphicon glyphicon-remove"></i> Xóa</a>
                                    <?php }?>
                                </td>
                            </tr>
                            <!-- cap 4 -->
                            <?php
                                $child_items_4 = $d->o_fet("select * from #_category where id_loai ='".$child_items_3[$m]['id_code']."' and lang='".LANG."'  order by so_thu_tu asc");
                                  $count_child_4=count($child_items_4);
                                  for($l=0; $l<$count_child_4; $l++){
                                ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <input type="checkbox" class="chk_box" name="chk_child[]" value="<?=$child_items_4[$l]['id_code']?>">
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($d->checkPermission_edit($id_module)==1){ ?>
                                    <input type="number" value="<?=$child_items_4[$l]['so_thu_tu']?>" class="a_stt" data-table="#_category" data-col="so_thu_tu" data-id="<?=$child_items_4[$l]['id_code']?>" />
                                    <?php }else{?>
                                    <?=$child_items_4[$l]['so_thu_tu']?>    
                                    <?php }?>
                                </td>
                                <td style="text-align:left">
                                    <a style="padding-left: 60px" href="index.php?p=category&a=edit&id=<?=$child_items_4[$l]['id_code']?>&page=<?=@$_GET['page']?>">|____<?=$child_items_4[$l]['ten']?></a>
                                </td>
                                <td class="text-center">
                                    <?php if($child_items_4[$l]['hinh_anh'] <> ''){ ?>
                                    <img src="../img_data/images/<?=$child_items_3[$l]['hinh_anh'] ?>" class='img_object50'>
                                    <?php } ?>
                                </td>
                                <td><?php $module=$d->simple_fetch("select * from #_module where id={$child_items_3[$m]['module']}"); echo $module['title']?></td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','home','<?=$child_items_4[$l]['id_code']?>')" <?php if($child_items_4[$l]['home'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','tieu_bieu','<?=$child_items_4[$l]['id_code']?>')" <?php if($child_items_4[$l]['tieu_bieu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','menu','<?=$child_items_4[$l]['id_code']?>')" <?php if($child_items_4[$l]['menu'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                <td class="text-center">
                                    <input class="chk_box" <?php if($d->checkPermission_edit($id_module)==0){ ?>disabled<?php }?> type="checkbox" onclick="on_check(this,'#_category','hien_thi','<?=$child_items_4[$l]['id_code']?>')" <?php if($child_items_4[$l]['hien_thi'] == 1) echo 'checked="checked"'; ?>>
                                </td>
                                 <td class="text-left">
                                    <?php if($child_items_3[$l]['nofollow']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Nofollow</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Dofollow</span>
                                    <?php }?><br>
                                    <?php if($child_items_3[$l]['noindex']==1){ ?>
                                    <span class="text-danger"><i style="font-size: 12px;" class="fa fa-circle-thin" aria-hidden="true"></i> Noindex</span>
                                    <?php }else{?>
                                    <span class="text-success"><i style="font-size: 12px;" class="fa fa-circle" aria-hidden="true"></i> Index</span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?p=category&a=edit&id=<?=$child_items_4[$l]['id_code']?>&page=<?=@$_GET['page']?>" class="btn btn-xs btn-warning" title="Sửa"><i class="glyphicon glyphicon-edit"></i> Sửa</a>
                                    <?php if($d->checkPermission_dele($id_module)==1){ ?>
                                    <a href="index.php?p=category&a=delete&id=<?=$child_items_4[$l]['id_code']?>&page=<?=@$_GET['page']?>" onClick="if(!confirm('Xác nhận xóa danh mục và các danh mục con trực thuộc?')) return false;" class="btn btn-xs btn-danger" title="Xóa"><i class="glyphicon glyphicon-remove"></i> Xóa</a>
                                    <?php }?>
                                </td>
                            </tr>
                            <?php } ?>
                            <!-- end cap 4 -->
                            <?php } ?>
                            <!-- end cap 3 -->
                            <?php } ?>
                            <!-- end cap 2 -->
                            <?php } ?>
                            <!-- end cap 1 -->
                            <?php } ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </section>
</div>

<link rel="stylesheet" href="templates/plugin/datatables.net-bs/css/dataTables.bootstrap.min.css">
<!-- DataTables -->
<script src="public/plugin/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="public/plugin/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script>
     $('#dataTable1').on("click",function(){
        setTimeout(function(){reloadUpStt();return false;},500);
    }).DataTable({
        'autoWidth'     : false,
        'searching'     : true,
        'lengthChange'  : true,
        'iDisplayLength': '25', 
        'searching'     : false,
        'lengthChange'  : false
    });
</script>

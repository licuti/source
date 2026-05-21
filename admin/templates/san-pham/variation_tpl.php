<?php
    if(isset($_POST['add_variation'])){
        if(!isset($_GET['id_loai'])){
            $data0['ten']  = addslashes($_POST['ten'][0]);
            $data0['type'] = 4;
            $d->reset();
            $d->setTable('cf_parent');

            if($id_code = $d->insert($data0)) {
                foreach (get_json('lang') as $key => $value) {

                    $variation['ten']       =   $d->clear(addslashes($_POST['ten'][$key]));
                    $variation['alias']     =   addslashes($_POST['alias'][$key]);
                    $variation['mo_ta']     =   $d->clear(addslashes($_POST['mo_ta'][$key]));
                    $variation['loai']      =   $d->clear($_POST['loai']);
                    $variation['sap_xep']   =   $d->clear($_POST['sap_xep']);

                    $variation['id_code']   =   $id_code;
                    $variation['lang']      =   $value['code'];

                    $d->reset();
                    $d->setTable('#_thuoctinh');
                    $d->insert($variation);
                }
                $d->redirect("index.php?p=".$_GET['p']."&a=variation");
            }
        }else{
            $id      = (int)$_GET['id_loai'];
            $data0['ten'] = addslashes($_POST['ten'][0]);

            $d->reset();
            $d->setTable('cf_parent');
            $d->setWhere('id', $id);

            if($d->update($data0)) {
                foreach (get_json('lang') as $key => $value) {

                    $variation['ten']      =  $d->clear(addslashes($_POST['ten'][$key]));
                    $variation['alias']    =  addslashes($_POST['alias'][$key]);
                    $variation['mo_ta']    =  $d->clear(addslashes($_POST['mo_ta'][$key]));
                    $variation['loai']     =  $d->clear($_POST['loai']);
                    $variation['sap_xep']  =  $d->clear($_POST['sap_xep']);

                    $d->reset();
                    $d->setTable('#_thuoctinh');
                    $d->setWhere('id', $_POST['id_row'][$key]);
                    $d->update($variation);
                }
                $d->redirect("index.php?p=".$_GET['p']."&a=variation");
            }
        }
    }
?>


<?php
    if(isset($_GET['delete']) and $_GET['delete']!=''){
        if($d->checkPermission_dele($id_module)==1){
            $id = (int)$_GET['delete'];

            $sub_content = $d->o_fet("select * from #_thuoctinh_giatri where id_thuoctinh=".$id."");
            if(count($sub_content)>0){
                foreach ($sub_content as $key => $value) {
                    $d->o_que("delete from cf_parent where id=".$value['id_code']."");
                }
                $d->o_que("delete from #_thuoctinh_giatri where id_thuoctinh=".$id."");
            }

            $d->reset();
            $d->setTable('#_thuoctinh');
            $d->setWhere('id_code',$id);
            if($d->delete()){
                $d->o_que("delete from cf_parent where id = $id ");
                $d->redirect("index.php?p=".$_GET['p']."&a=variation");
            }else{
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("index.php?p=".$_GET['p']."&a=variation");
            }
        }else{
            $d->redirect("index.php?p=".$_GET['p']."&a=variation");
        }
    }
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Biến thể</h1>
        <ol class="breadcrumb">
            <li><a href="<?= urladmin ?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
            <li><a href="#">Quản trị giao diện</a></li>
            <li class="active">Biến thể</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-sm-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title pull-left"><?= $_GET['id_loai'] ? 'Chỉnh sửa' : 'Thêm mới'  ?></h3>
                    </div>
                    <div class="box-body">
                        <form method="POST" action=""  enctype="multipart/form-data">
                            <?php if (count(get_json('lang')) > 1): ?>
                                <ul id="myTabs" class="nav nav-tabs" role="tablist">
                                    <?php foreach (get_json('lang') as $key => $value) { ?>
                                    <li role="presentation" class="<?= $key == 0 ? 'active' : '' ?>">
                                        <a href="#tab_content_<?= $value['code'] ?>" id="tab_<?= $value['code'] ?>" role="tab" data-toggle="tab" aria-controls="tab_content_<?= $value['code'] ?>" aria-expanded="true">
                                            <?= $value['name'] ?>        
                                        </a>
                                    </li>
                                    <?php } ?>
                                </ul>
                            <?php endif ?>

                            <div id="myTabContent" class="tab-content">
                                <?php foreach (get_json('lang') as $key => $value) {
                                    if(isset($_GET['id_loai'])){
                                        $row = $d->simple_fetch("select * from #_thuoctinh where id_code=".(int)$_GET['id_loai']." and lang='".$value['code']."' ");
                                    }    
                                ?>
                                <div role="tabpanel" class="tab-pane fade in <?= $key == 0 ? 'active' : '' ?>" id="tab_content_<?= $value['code'] ?>" aria-labelledby="tab_content_<?= $value['code'] ?>">
                                    <div class="form-group">
                                        <label>Tên(<?= $value['code'] ?>)</label>
                                        <input type="text" name="ten[]" value="<?= $row['ten'] ? $row['ten'] : '' ?>" class="form-control">

                                        <?php if($row){ ?>
                                            <input type="hidden" name="id_row[]" value="<?= $row['id'] ?>" />
                                        <?php } ?>
                                    </div>
                                    <div class="form-group">
                                        <label>Đường dẫn(<?= $value['code'] ?>)</label>
                                        <input type="text" name="alias[]" value="<?= $row['alias'] ? $row['alias'] : '' ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Mô tả(<?= $value['code'] ?>)</label>
                                        <textarea class="form-control" rows="5" name="mo_ta[]"><?= $row['mo_ta'] ? $row['mo_ta'] : '' ?></textarea>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Loại</label>
                                <select name="loai" class="form-control">
                                    <?php foreach ($data_type_variation as $key => $type): ?>
                                        <option value="<?= $key ?>" <?= $key == $row['loai'] ? 'selected' : '' ?>><?= $type ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Sắp xếp theo</label>
                                <select name="sap_xep" class="form-control">
                                    <?php foreach ($data_type_sort as $key => $sort): ?>
                                        <option value="<?= $key ?>" <?= $key == $row['sap_xep'] ? 'selected' : '' ?>><?= $sort ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>        


                            <div class="text-right">
                                <?php if ($_GET['id_loai']): ?>
                                    <a class="btn btn-warning" href="index.php?p=san-pham&a=variation">Hủy</a>
                                <?php endif ?>
                                <button class="btn btn-primary pull-right" type="submit" name="add_variation">
                                    <?php echo (!empty($_GET['id_loai'])) ? 'Cập nhật':'Thêm mới';?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title pull-left">Trang chủ</h3>
                    </div>  
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-primary">
                            <thead>
                                <tr>
                                    <th class="text-center">Tiêu đề</th>
                                    <th class="text-center">Đường dẫn</th>
                                    <th class="text-center">Loại</th>
                                    <th class="text-center">Sắp sếp theo</th>
                                    <th class="text-center">Tên chủng loại</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $key => $value) { ?>
                                <tr>
                                    <td class="text-center">

                                        <p><?= $value['ten'] ?></p>

                                        <a href="index.php?p=<?=$_GET['p']?>&a=variation&id_loai=<?= $value['id_code'] ?>" style="margin-right: 5px;">
                                            <i class="glyphicon glyphicon-edit"></i> Sửa
                                        </a>
                                        <a href="index.php?p=<?= $_GET['p'] ?>&a=variation&delete=<?= $value['id_code'] ?>" onClick="if(!confirm('Xác nhận xóa?')) return false;"><i class="glyphicon glyphicon-remove"></i> Xóa
                                        </a>
                                    </td>
                                    <td class="text-center"><?= $value['alias'] ? $value['alias'] : 'duong-dan' ?></td>
                                    <td class="text-center"><?= $value['loai'] ? $value['loai'] : 'Select' ?></td>
                                    <td class="text-center"><?= $value['sap_xep'] ? $value['sap_xep'] : 'Tùy chỉnh sắp xếp' ?></td>
                                    <td class="text-center">
                                        <p>
                                            <?php
                                                $get_variants = $d->o_fet("select ten from #_thuoctinh_giatri where id_thuoctinh=".$value['id_code']." and lang='".LANG."' order by id ASC");
                                                echo implode(',', array_column($get_variants, 'ten'));
                                            ?>
                                        </p>
                                        <a href="index.php?p=san-pham&a=variants&variation=<?= $value['id_code'] ?>">
                                            Thêm chủng loại của thuộc tính
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
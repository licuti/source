<?php
    if(isset($_POST['add_variation'])){
        if(!isset($_GET['id_loai'])){

            $id_thuoctinh = addslashes($_POST['id_thuoctinh']);

            $data0['ten']  = addslashes($_POST['ten'][0]);
            $data0['type'] = 5;
            $d->reset();
            $d->setTable('cf_parent');

            if($id_code = $d->insert($data0)) {
                foreach (get_json('lang') as $key => $value) {

                    $variants['id_thuoctinh']   =   $id_thuoctinh;

                    $variants['gia_tri']        =   $d->clear(addslashes($_POST['gia_tri']));
                    $variants['ten']            =   $d->clear(addslashes($_POST['ten'][$key]));
                    $variants['alias']          =   addslashes($_POST['alias'][$key]);
                    $variants['mo_ta']          =   $d->clear(addslashes($_POST['mo_ta'][$key]));

                    $variants['id_code']        =   $id_code;
                    $variants['lang']           =   $value['code'];

                    $d->reset();
                    $d->setTable('#_thuoctinh_giatri');
                    $d->insert($variants);
                }
                $d->redirect("index.php?p=".$_GET['p']."&a=variants&variation=".$_GET['variation']."");
            }
        }else{
            $id      = (int)$_GET['id_loai'];
            $data0['ten'] = addslashes($_POST['ten'][0]);

            $d->reset();
            $d->setTable('cf_parent');
            $d->setWhere('id', $id);

            if($d->update($data0)) {
                foreach (get_json('lang') as $key => $value) {

                    $variants['gia_tri']  =  $d->clear(addslashes($_POST['gia_tri']));
                    $variants['ten']      =  $d->clear(addslashes($_POST['ten'][$key]));
                    $variants['alias']    =  addslashes($_POST['alias'][$key]);
                    $variants['mo_ta']    =  $d->clear(addslashes($_POST['mo_ta'][$key]));

                    $d->reset();
                    $d->setTable('#_thuoctinh_giatri');
                    $d->setWhere('id', $_POST['id_row'][$key]);
                    $d->update($variants);
                }
                $d->redirect("index.php?p=".$_GET['p']."&a=variants&variation=".$_GET['variation']."");
            }
        }
    }
?>


<?php

if(isset($_GET['delete']) and $_GET['delete'] !='' ){
    if($d->checkPermission_dele($id_module) == 1){
        $id = (int)$_GET['delete'];
        $d->reset();
        $d->setTable('#_thuoctinh_giatri');
        $d->setWhere('id_code',$id);
        if($d->delete()){
            $d->o_que("delete from cf_parent where id=$id");
            $d->redirect("index.php?p=".$_GET['p']."&a=variants&variation=".$_GET['variation']."");
        }else{
            $d->alert("Xóa dữ liệu bị lỗi!");
            $d->redirect("index.php?p=".$_GET['p']."&a=variants&variation=".$_GET['variation']."");
        }
    }else{
        $d->redirect("index.php?p=".$_GET['p']."&a=variation");
    }
}

?>  

<style>
    .show-value{
        width: 3rem;
        height: 3rem;
        border: 1px solid #ccc;
        border-radius: 50%;
        overflow: hidden;
    }

    .show-value.color{
        background-color: var(--value);
    }

    .show-value.image{
        background-image: var(--value);
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
    }

</style>


<div class="content-wrapper">
    <section class="content-header">
        <h1>Thuộc tính biến thể</h1>
        <ol class="breadcrumb">
            <li><a href="<?= urladmin ?>"><i class="fa fa-dashboard"></i> Trang chủ</a></li>
            <li><a href="#">Biến thể</a></li>
            <li class="active">Thuộc tính biến thể</li>
        </ol>
    </section>
    <section class="content-header">
        <a href="index.php?p=san-pham&a=variation">
            <i class="fa fa-angle-double-left" aria-hidden="true"></i> Quay lại trang Biến Thể
        </a>
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
                                        $row = $d->simple_fetch("select * from #_thuoctinh_giatri where id_code=".(int)$_GET['id_loai']." and lang='".$value['code']."' ");
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
                                <label>Giá trị</label>
                                <?php
                                    // echo "<pre>";
                                    // var_dump($variation);
                                    // echo "</pre>";
                                ?>
                                <?php if ($variation['loai'] == 'color'): ?>
                                    <input type="color" name="gia_tri" value="<?= $row['gia_tri'] ?>" class="form-control">
                                <?php elseif ($variation['loai'] == 'image'): ?>
                                    <input type="text" name="gia_tri" value="<?= $row['gia_tri'] ?>" id="gia_tri" class=" form-control">
                                    <a href="filemanager/dialog.php?type=1&field_id=gia_tri&relative_url=1&multiple=0" class="btn btn-upload2 iframe-btn" >
                                        <i class="fa fa-upload" aria-hidden="true"></i>Chọn hình ảnh
                                    </a>
                                <?php else: ?>
                                    <input type="text" name="gia_tri" value="<?= $row['gia_tri'] ?>" class="form-control">
                                <?php endif ?>

                            </div>

                            <input type="hidden" name="id_thuoctinh" value="<?= $variation['id_code'] ?>">

                            <?php if($row){ ?>
                                <input type="hidden" name="id_row[]" value="<?= $row['id'] ?>" />
                            <?php } ?>

                            <div class="text-right">
                                <?php if ($_GET['id_loai']): ?>   
                                    <a class="btn btn-danger" href="index.php?p=san-pham&a=variants&variation=<?= $_GET['variation'] ?>">Hủy</a>
                                <?php endif ?>
                                <button class="btn btn-primary" type="submit" name="add_variation">
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
                        <h3 class="box-title pull-left">Danh sách các thuộc tính</h3>
                    </div>  
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-primary">
                            <thead>
                                <tr>
                                    <th class="text-center">Giá trị</th>
                                    <th class="text-center">Tên</th>
                                    <th class="text-center">Đường dẫn</th>
                                    <th class="text-center">Mô tả</th>
                                    <th class="text-center" style="width: 180px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $key => $value) { ?>
                                <tr>
                                    <td>
                                        <?php if ($variation['loai'] == 'color'): ?>
                                            <div class="show-value color" style="--value: <?= $value['gia_tri'] ?>"></div>
                                        <?php elseif($variation['loai'] == 'image'): ?>
                                            <div class="show-value image" style="--value: url(<?= '/img_data/images/'.$value['gia_tri'] ?>)"></div>
                                        <?php endif ?>
                                    </td>
                                    <td><?= $value['ten'] ?></td>
                                    <td><?= $value['alias'] ?></td>
                                    <td><?= $value['mo_ta'] ?></td>
                                    <td class="text-center">
                                        <a href="index.php?p=<?= $_GET['p'] ?>&a=variants&variation=<?= $_GET['variation'] ?>&id_loai=<?= $value['id_code'] ?>" style="margin-right: 5px;" class="btn btn-primary">
                                            <i class="glyphicon glyphicon-edit"></i>Sửa
                                        </a>
                                        <a href="index.php?p=<?= $_GET['p'] ?>&a=variants&variation=<?= $_GET['variation'] ?>&delete=<?=$value['id_code']?>" onClick="if(!confirm('Xác nhận xóa?')) return false;" class="btn btn-danger">
                                            <i class="glyphicon glyphicon-remove"></i> Xóa
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
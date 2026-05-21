<div class="content-wrapper">
	<section class="content-header">
		<h1>Nút liên hệ</h1>
		<ol class="breadcrumb">
			<li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i>Quản trị giao diện</a></li>
			<li><a href="#">Cấu hình website</a></li>
			<li class="active">Nút liên hệ</li>
		</ol>
	</section>
	<!-- Main content -->
    <section class="content">
        <div class="box box-primary">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.css"/>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>
            <style type="text/css">
            	.box-img2{
            		height: 100px;
            	}
            	.btn-upload2{
            		font-size: 10px;
            	}
            	.clr-field{
            		display: block;
            	}
            </style>

            <div class="box-body">
            	<form id="" class="" method="post" action="index.php?p=<?= $_REQUEST['p'] ?>&a=save&id=<?= @$_REQUEST['id'] ?>">
            		<table class="table table-bordered table-striped table-hover table-primary" id="group-button-contact">
            			<thead>
	            			<tr>
	            				<th>Thumbnail</th>
	            				<th>Name</th>
		            			<th>Link</th>
		            			<th>Target</th>
		            			<th>Background</th>
		            			<th>Background Outline</th>
		            			<th>Text Corlor</th>
		            			<th>Action</th>
	            			</tr>
            			</thead>
	            		<tbody>
	            			<?php foreach ($items as $key => $value): ?>
	            				<tr class="item-button-contact">
		            				<td>
		            					<span class="box-img2">
			                                <?php if($value['image']){ ?>
			                                	<img src="../img_data/images/<?= $value['image'] ?>" id="review_image_<?= $value['id'] ?>" alt="NO PHOTO" />
			                                	<button class="btn btn-xs btn-danger btn-remove-image" type="button" data-image-view="#review_image_<?= $value['id'] ?>"><i class="fa fa-trash"></i></button>
			                                <?php } else{ ?>
			                                    <img src="img/no-image.png" id="review_image_<?= $value['id'] ?>" alt="NO PHOTO" />
			                                <?php } ?>
			                                <input type="hidden" value="<?= $value['image'] ?>" name="image[]" id="image_<?= $value['id'] ?>" class=" form-control">
			                                <a href="filemanager/dialog.php?type=1&field_id=image_<?= $value['id'] ?>&relative_url=1&multiple=0" class="btn btn-upload2 iframe-btn" ></i>Chọn hình ảnh</a>
			                            </span>
			                            <input type="hidden" name="id[]" placeholder="URL" value="<?= $value['id'] ?>" class="form-control">
		            				</td>
		            				<td><input type="text" name="name[]" placeholder="Name Social" value="<?= $value['name'] ?>" class="form-control"></td>
		            				<td><input type="text" name="link[]" placeholder="URL" value="<?= $value['link'] ?>" class="form-control"></td>
		            				<td>
		            					<select name="target[]" class="form-control">
			            					<option value="_self" <?= $value['target'] == '_self' ? 'selected' : '' ?>>_self</option>
			            					<option value="_blank" <?= $value['target'] == '_blank' ? 'selected' : '' ?>>_blank</option>
			            				</select>
		            				</td>
		            				<td><input type="text" name="color_background[]" placeholder="#000000" value="<?= $value['color_background'] ?>" class="form-control" data-coloris></td>
		            				<td><input type="text" name="color_background_alpha[]" placeholder="#000000" value="<?= $value['color_background_alpha'] ?>" class="form-control" data-coloris></td>
		            				<td><input type="text" name="color_text[]" placeholder="#000000" value="<?= $value['color_text'] ?>" class="form-control" data-coloris></td>
		            				<td class="text-center"><button type="button" class="btn btn-danger inputRemove" data-id="<?= $value['id'] ?>"><i class="fa fa-trash" aria-hidden="true"></i></button></td>
		            			</tr>
	            			<?php endforeach ?>
	            			<?php /* ?>
	            			<tr class="item-button-contact">
	            				<td>
	            					<span class="box-img2">
		                                <?php if(isset($_GET['id']) and $row['hinh_anh'] != ''){ ?>
		                                	<img src="../img_data/images/<?php echo $row['hinh_anh']?>"  id="review_hinh_anh" alt="NO PHOTO" />
		                                	<button class="btn btn-xs btn-danger" type="button" onclick="xoa_img('_category','hinh_anh', '<?=$_GET['id']?>','')"><i class="fa fa-trash"></i></button>
		                                <?php }else{ ?>
		                                    <img src="img/no-image.png" id="review_hinh_anh" alt="NO PHOTO" />
		                                <?php }?>
		                                <input type="hidden" value="<?=$row['hinh_anh']?>" name="image[]" id="hinh_anh" class=" form-control">
		                                <a href="filemanager/dialog.php?type=1&field_id=hinh_anh&relative_url=1&multiple=0" class="btn btn-upload2 iframe-btn" ></i>Chọn hình ảnh</a>
		                            </span>
	            				</td>
	            				<td><input type="text" name="name[]" placeholder="Name Social" class="form-control"></td>
	            				<td><input type="text" name="link[]" placeholder="URL" class="form-control"></td>
	            				<td>
	            					<select name="target[]" class="form-control">
		            					<option value="_self">_self</option>
		            					<option value="_blank">_blank</option>
		            				</select>
	            				</td>
	            				<td><input type="text" name="color_background[]" placeholder="#000000" class="form-control" data-coloris></td>
	            				<td><input type="text" name="color_background_alpha[]" placeholder="#000000" class="form-control" data-coloris></td>
	            				<td><input type="text" name="color_text[]" placeholder="#000000" class="form-control" data-coloris></td>
	            				<td><button type="button" class="btn btn-danger inputRemove"><i class="fa fa-trash" aria-hidden="true"></i></button></td>
	            			</tr>
	            			<?php */ ?>
            			</tbody>
            		</table>
            		<button id="addmore" type="button" class="btn btn-primary">Thêm</button>
            		<button type="submit" class="btn btn-primary">Lưu</button>
            	</form>
            	<div class="row">
            		<div class="col-md-2">
            			<div class="form-group">
            				<label>Link Zalo ví dụ:</label>
            				<input type="text" name="" value="https://zalo.me/0915101017" class="form-control" readonly>
            			</div>
            		</div>
            		<div class="col-md-2">
            			<div class="form-group">
            				<label>Link SĐT ví dụ:</label>
            				<input type="text" name="" value="tel:0915101017" class="form-control" readonly>
            			</div>
            		</div>
            		<div class="col-md-2">
            			<div class="form-group">
            				<label>Link Email ví dụ:</label>
            				<input type="text" name="" value="mailto:kythuat@phuongnamvina.vn" class="form-control" readonly>
            			</div>
            		</div>
            	</div>
            </div>
        </div>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-sortablejs@latest/jquery-sortable.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
    	Coloris({
    		formatToggle: true,
    		clearButton: true,
    		clearLabel: 'Clear',
  			closeButton: true,
  			closeLabel: 'Close',
  			swatches: [
			    '#264653',
			    '#2a9d8f',
			    '#e9c46a',
			    'rgb(244,162,97)',
			    '#e76f51',
			    '#d62828',
			    'navy',
			    '#07b',
			    '#0096c7',
			    '#00b4d880',
			    'rgba(0,119,182,0.8)'
			],
		});
    	$('#group-button-contact tbody').sortable({
			// handle: '.handle',
			invertSwap: true,
			animation: 200,
			onUpdate: function (evt) {
				// thêm ajax lưu khi sort -> update sau
			},
		});
        var index = 0;
       	$("#addmore").click(function() {
            $("#group-button-contact tbody").append('<tr class="item-button-contact"><td><span class="box-img2"><img src="img/no-image.png" id="review_hinh_anh_'+ index +'" alt="NO PHOTO" /><input type="hidden" value="" name="image[]" id="hinh_anh_'+ index +'" class=" form-control"><a href="filemanager/dialog.php?type=1&field_id=hinh_anh_'+ index +'&relative_url=1&multiple=0" class="btn btn-upload2 iframe-btn" ></i>Chọn hình ảnh</a></span></td><td><input type="text" name="name[]" placeholder="Name Social" class="form-control"></td><td><input type="text" name="link[]" placeholder="URL" class="form-control"></td><td><select name="target[]" class="form-control"><option value="_self">_self</option><option value="_blank">_blank</option></select></td><td><input type="text" name="color_background[]" placeholder="#000000" class="form-control" data-coloris></td><td><input type="text" name="color_background_alpha[]" placeholder="#000000" class="form-control" data-coloris></td><td><input type="text" name="color_text[]" placeholder="#000000" class="form-control" data-coloris></td><td class="text-center"><button type="button" class="btn btn-danger inputRemove"><i class="fa fa-trash" aria-hidden="true"></i></button></td></tr>');
            index ++;
            $('.iframe-btn').fancybox({
		        'type'		: 'iframe',
		        'autoScale'    	: false
		    });
        });
        

       	$('body').on('click','.inputRemove',function() {
                const id =  $(this).data('id'); // thêm data-id vào những item đã lưu
               	const item_button_contact = $(this).parent().parent('.item-button-contact');
                if(id){
               		if (confirm("Xác nhận xóa ?") == true) {
               			$.ajax({
				            url : "sources/ajax.php",
				            type : "post",
				            dataType:"text",
				            data : {
				                do : 'remove_button_contact',
				                id : id
				            },
				            success : function (response){
				            	item_button_contact.remove();
				            	toastr.success('Xóa thành công!', 'Thành công');
				            }
				        });
               		}
                }else {
                	item_button_contact.remove();
                	toastr.success('Xóa thành công!', 'Thành công');
                }
            });
        });


    	$('.btn-remove-image').on('click', function(){
    		image_view =  $(this).data('image-view');
    		$(image_view).attr('src', 'img/no-image.png');
    	});

</script>


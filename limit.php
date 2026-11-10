<?php
    if(!empty($_POST['xac_nhan'])){
        $ma = addslashes($_POST['xac_nhan']);
        if($ma == '606060'){
            setcookie('code', '606060', time() + (86400 * 30 *365), "/"); // 86400 = 1 day
            $d->redirect(URLPATH);
        }
    }
?>

<?php if(!isset($_COOKIE['code']) || $_COOKIE['code'] != 606060){ ?>
	<div class="wp-fix">
		<ul class="bg-bubbles">
            <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> <li></li> </ul>
		<div class="input-fix">
			<h1>Vui lòng nhập mật khẩu</h1>
			<form method="POST" action="">
				<input type="text" name="xac_nhan" placeholder="Mật khẩu">
				<button class="btn-enter"><i class="fa fa-arrow-right" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
	<?php exit(); ?>
<?php } ?>

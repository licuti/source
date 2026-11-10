<?php $block_register = $d->getContent(772); ?>

<?php if ($block_register): ?>	
	<div class="wrapper-register-contact">
	    <h2 class="heading-title text-white text-center mb-4 w-75 mx-auto"><?= $block_register['ten'] ?></h2>
	    <div class="wrapper-inner">
	        <div class="text-center">
	            <?= $block_register['noi_dung'] ?>
	        </div>
	        <div class="icon-contact">
	            <div class="icon">
	                <i class="fa-solid fa-phone"></i>
	            </div>
	            <div class="content">
	                <p class="mb-0 fw-medium">Hoặc liên hệ</p>
	                <p class="fw-bold fs-24 lh-1"><?= $info['hotline'] ?></p>
	            </div>
	        </div>
	    </div>
	</div>
<?php endif ?>
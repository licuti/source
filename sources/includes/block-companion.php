<?php
    $block_consultation = $d->getContent(1443);
?>
<?php if ($block_consultation): ?>
	<div class="box-consultation position-relative">
		<div class="content fw-semibold w-50"><?= $block_consultation['noi_dung'] ?></div>
		<div class="bg-cover" style="background-image: url('<?= getImageUrl($block_consultation['background']) ?>');"></div>
	</div>
<?php endif ?>
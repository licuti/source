<?php
    $block_commit = $d->getContent(455);
    $content_commit = $d->getContents(455);
?>

<?php if ($block_commit): ?>
	<div class="block">
		<div class="container-fluid">
			<div class="row g-4">
				<?php foreach ($content_commit as $key => $value): ?>
					<div class="col-6 col-lg-3">
						<div class="box-commit">
							<h3 class="title"><?= $value['ten'] ?></h3>
							<?= $value['noi_dung'] ?>
						</div>
					</div>
				<?php endforeach ?>
			</div>
		</div>
		<div class="bg-cover" style="background: linear-gradient(to right, #FFC31F, #FA5B02 100%);"></div>
	</div>
<?php endif ?>
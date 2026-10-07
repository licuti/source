<?php $get_lang = get_json('lang'); ?>

<div class="translate">
	<?php foreach ($get_lang as $key => $item): ?>
	    <a href="<?= URLPATH . $item['code']. '/' .$row_multi_lang[$item['code']]['alias'] ?>.html" class="lang-item <?= $item['code'] == $_SESSION['lang'] ? 'active' : '' ?>" data-lang="<?= $item['code'] ?>" data-id_code="<?= $row['id_code'] ?>">
	       <?= $item['label'] ?>
	    </a>
    <?php endforeach ?>
</div>
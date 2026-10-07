<style>
	.translate{
	    display: flex;
	    align-items: center;
	    box-shadow: 0 0 2px 0 rgba(0, 0, 0, 0.25) inset;
	    border-radius: 50rem;
	}

	.translate .img-lang-current{
	    width: 2rem;
	    height: 2rem;
	    border-radius: 50%;
	    overflow: hidden;
	}

	.translate button{
	    box-shadow: none;
	    border: none;
	    padding-top: 0.25rem;
	    padding-bottom: 0.25rem;
	    padding-right: 0.25rem;
	    font-weight: 700;
	    font-size: inherit;
	}


	.dropdown-toggle::after{
	    margin-left: 0;
	}

	.translate .dropdown-menu.show{
	    padding-top: 0;
	    padding-bottom: 0;
	    min-width: -webkit-fill-available !important;
	    overflow: hidden;
	}

	.translate .dropdown-menu .dropdown-item{
	    display: flex;
	    justify-content: space-between;
	    padding: 0.25rem 0.5rem;
	    font-weight: 500;
	}

	.translate .dropdown-menu .dropdown-item .icon{
	    width: 1.75rem;
	    height: 1.75rem;
	    border-radius: 50%;
	    overflow: hidden;
	}

	.translate .dropdown-menu .dropdown-item.active,
	.translate .dropdown-menu .dropdown-item:hover{
	    background-color: var(--cl-x);
	    color: #fff;
	}
</style>

<?php $get_lang = get_json('lang'); ?>
<div class="dropdown translate">
	<button class="btn dropdown-toggle" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
		<?php
			// Lấy label ngôn ngữ hiện tại
			foreach ($get_lang as $item) {
				if ($item['code'] == $_SESSION['lang']) {
					$current_label =  $item['label'];
					$current_flag  = $item['image'];
					echo $current_label;
					break;
				}
			}
		?>
	</button>
	<ul class="dropdown-menu" aria-labelledby="langDropdown">
		<?php foreach ($get_lang as $item): ?>
		<li>
			<a class="dropdown-item <?= $item['code'] == $_SESSION['lang'] ? 'active' : '' ?>"
				href="<?= URLPATH . $item['code']. '/' .$row_multi_lang[$item['code']]['alias'] ?>.html"
				data-lang="<?= $item['code'] ?>"
				data-id_code="<?= $row['id_code'] ?>">
				<?= $item['label'] ?>
				<div class="icon">
					<img src="<?= URLPATH.$item['image'] ?>" alt="<?= $item['label'] ?>" class="image-cover">
				</div>
			</a>
		</li>
		<?php endforeach ?>
	</ul>
	<div class="img-lang-current">
		<img src="<?= URLPATH.$current_flag ?>" alt="<?= $current_label ?>" class="image-cover">
	</div>
</div>
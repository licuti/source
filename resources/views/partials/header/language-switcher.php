<style>
	.translate{
	    display: flex;
	    align-items: center;
	    background-color: #D9D9D9;
	    box-shadow: 0 0 2px 0 rgba(0, 0, 0, 0.25) inset;
	    border-radius: 50rem;
	}

	.translate .getImageUrl-lang-current{
	    width: 1.75rem;
	    height: 1.75rem;
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
	    width: 1.5rem;
	    height: 1.5rem;
	    border-radius: 50%;
	    overflow: hidden;
	}

	.translate .dropdown-menu .dropdown-item.active,
	.translate .dropdown-menu .dropdown-item:hover{
	    background-color: var(--cl-x);
	    color: #fff;
	}
</style>

<?php $get_lang = config('lang', []); ?>
<div class="dropdown translate">
	<button class="btn dropdown-toggle" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
		<?php
			$current_lang = $_SESSION['app_locale'] ?? 'vi';
			$current_flag = '';
			foreach ($get_lang as $item) {
				if ($item['code'] == $current_lang) {
					echo $item['label'];
					$current_flag = $item['image'];
					break;
				}
			}
		?>
	</button>
	<ul class="dropdown-menu" aria-labelledby="langDropdown">
		<?php foreach ($get_lang as $item): ?>
		<li>
			<a class="dropdown-item <?= $item['code'] == $current_lang ? 'active' : '' ?>"
				href="<?= url_lang($item['code']) ?>">
				<?= $item['label'] ?>
				<div class="icon">
					<img src="<?= url(str_replace('/templates/', 'assets/', $item['image'])) ?>" alt="<?= $item['label'] ?>" class="image-cover" style="width: 100%; height: 100%; object-fit: cover;">
				</div>
			</a>
		</li>
		<?php endforeach ?>
	</ul>
	<div class="getImageUrl-lang-current">
		<?php if ($current_flag): ?>
		<img src="<?= url(str_replace('/templates/', 'assets/', $current_flag)) ?>" class="image-cover" style="width: 100%; height: 100%; object-fit: cover;">
		<?php endif; ?>
	</div>
</div>

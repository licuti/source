
<h1 class="visually-hidden"><?= $info['company'] ?></h1>

<?php include _source."/includes/home-commit.php"; ?>

<?php include _source."/includes/home-curriculum.php"; ?>

<?php include _source."/includes/home-teaching-staff.php"; ?>

<?php include _source."/includes/home-advantages.php"; ?>

<?php include _source."/includes/home-review.php"; ?>

<?php //include _source."/includes/home-featured-product.php"; ?>
<?php //include _source."/includes/home-banner.php"; ?>
<?php //include _source."/includes/home-category-product.php"; ?>
<?php //include _source."/includes/home-about.php"; ?>
<?php //include _source."/includes/home-post-col.php"; ?>



<?php $home_popup = $d->getContent(1467) ?>
<?php if ($home_popup): ?>
    <div class="modal fade" id="home-popup" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="--bs-modal-width: 1000px">
            <div class="modal-content border-0 overflow-hidden rounded-4">
                <div class="modal-body p-0">
                    <div class="row align-items-center g-0">
                        <div class="col-lg-6">
                            <img src="<?= getImageUrl($home_popup['hinh_anh']) ?>" alt="<?= $home_popup['ten'] ?>" class="image-cover">
                        </div>
                        <div class="col-lg-6">
                            <div class="heading-content heading-gradient p-4">
                                <?= $home_popup['noi_dung'] ?>
                                <div class="icon-contact d-flex align-items-center gap-2 mt-4 mt-lg-5">
                                    <div class="icon" style="width: 3rem; height: 3rem; display: flex; justify-content: center; align-items: center; background-color: var(--cl-y); border-radius: 50%; font-size: 1.5rem; color: #fff; flex-shrink: 0;">
                                        <i class="fa-solid fa-phone"></i>
                                    </div>
                                    <div class="content">
                                        <p class="mb-0 fw-medium">Hoặc liên hệ</p>
                                        <p class="fw-bold fs-24 lh-1"><?= $info['hotline'] ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 z-1 d-flex justify-content-center align-items-center rounded-4 bg-white shadow-none" data-bs-dismiss="modal" aria-label="Close" style="width: 2.5rem; height: 2.5rem;"></button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                const popup = document.getElementById('home-popup');

                if (popup && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(popup);
                    modal.show();
                }
            }, 3000);
        });
    </script>
<?php endif ?>
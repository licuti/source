<?php if ($row['id_code'] == 99): ?>

    <?php include _source."/page-about.php"; ?>

<?php elseif ($row['id_code'] == 276): ?>

    <?php include _source."/page-about-kimchi-production.php"; ?>

<?php elseif ($row['id_code'] == 277): ?>

    <?php include _source."/page-about-certificate.php"; ?>

<?php else: ?>
    <div class="block">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <?php if ($row['noi_dung']): ?>
                        <?= $row['noi_dung'] ?>
                    <?php else: ?>
                        <p class="text-center"><?= $d->getTxt(141) ?></p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
<?php if ($row['id_code'] == 277): ?>

    <?php include _source."/page-course.php"; ?>

<?php elseif ($row['id_code'] == 298): ?>

    <?php include _source."/page-course-program-secondary-school.php"; ?>

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
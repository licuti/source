<!-- use: include _source."/module/vertical-menu.php" -->

<?php
    $vertical_menu = $d->o_fet("select * from #_category where id_loai=100 and hien_thi=1 "._where_lang." order by so_thu_tu asc");
?>

<div class="dropdown vertical-menu">
    <div class="btn-dropdown" data-bs-auto-close="outside">
        <i class="fa-solid fa-bars-staggered fa-flip-horizontal me-3"></i> DANH MỤC SẢN PHẨM
    </div>
    <ul class="dropdown-menu shadow">
        <?php foreach ($vertical_menu as $parent): ?>
            <?php 
                $vertical_menu_child = $d->o_fet("select * from #_category where id_loai=".$parent['id_code']." and hien_thi=1 "._where_lang);
            ?>
            <?php if (count($vertical_menu_child) > 0): ?>
                 <li class="dropend">
                    <a <?= createAlias($parent) ?> class="dropdown-item dropdown-toggle" data-bs-auto-close="outside">
                        <i class="fa-regular fa-angle-right"></i> <?= $parent['ten'] ?>
                    </a>
                    <ul class="dropdown-menu shadow">
                        <?php foreach ($vertical_menu_child as $child): ?>
                            <li>
                                <a class="dropdown-item" <?= createAlias($child) ?> >
                                    <i class="fa-solid fa-caret-right"></i> <?= $child['ten'] ?>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </li>
            <?php else: ?>
                <li>
                    <a class="dropdown-item" <?= createAlias($parent) ?> >
                        <i class="fa-regular fa-angle-right"></i> <?= $parent['ten'] ?>
                    </a>
                </li>
            <?php endif ?>
        <?php endforeach ?>
    </ul>
</div>
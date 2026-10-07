<?php
    // 'primary' là tên vị trí, bạn có thể đổi thành tên khác tùy cấu hình trong Admin
    $nav_tree = $d->getMenuByLocation('primary', $_SESSION['lang']);
    if (!function_exists('renderMenuItem')) {
        function renderMenuItem($item, $com, $level = 1) {
            
            $label = $item['label'] ?? $item['ten'] ?? ''; // Ưu tiên label mới, fallback về tên cũ
            $url   = $item['url'] ?? '';
            $sub_label = $item['sub_label'] ?? '';
            
            $href = $url.'.html'; 
            
            $has_children = !empty($item['children']);
            
            $activeClass = ($com != '' && strpos($url, $com) !== false) ? 'active' : '';

            // Các thuộc tính bổ sung
            $target = !empty($item['target']) ? 'target="'.$item['target'].'"' : '';
            $customClass = !empty($item['class']) ? $item['class'] : '';

            // Icon toggle (Cấp 1 và cấp con khác nhau)
            $icon = ($level == 1) 
                ? '<i class="fa-solid fa-chevron-down fa-xs"></i>' 
                : '<i class="fa-regular fa-chevron-right fa-xs"></i>';
?>
            <?php /* --- TRƯỜNG HỢP CÓ MENU CON --- */ ?>
            <?php if ($has_children): ?>
                
                <li class="<?= ($level == 1) ? 'nav-item dropdown' : 'dropdown' ?> <?= $customClass ?>">
                    
                    <div class="<?= ($level == 1) ? 'nav-link' : 'dropdown-item' ?> <?= $activeClass ?>" role="button" aria-expanded="false">
                        <a href="<?= $href ?>" <?= $target ?>>
                            <span class="menu-label"><?= $label ?></span>
                            <?php if ($sub_label): ?>
                                <span class="menu-sub-label"><?= $sub_label ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="toggle-menu"><?= $icon ?></div>
                    </div>

                    <ul class="dropdown-menu">
                        <?php foreach ($item['children'] as $child): ?>
                            <?php renderMenuItem($child, $com, $level + 1); ?>
                        <?php endforeach; ?>
                    </ul>

                </li>

            <?php /* --- TRƯỜNG HỢP KHÔNG CÓ MENU CON --- */ ?>
            <?php else: ?>

                <?php if ($level == 1): ?>
                    <li class="nav-item <?= $customClass ?>">
                        <a class="nav-link <?= $activeClass ?>" href="<?= $href ?>" <?= $target ?>>
                            <span class="menu-label"><?= $label ?></span>
                            <?php if ($sub_label): ?>
                                <span class="menu-sub-label"><?= $sub_label ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <div class="dropdown-item">
                             <a href="<?= $href ?>" <?= $target ?>>
                                <?= $label ?>
                                <?php if ($sub_label): ?>
                                    <span class="menu-sub-label"><?= $sub_label ?></span>
                                <?php endif; ?>
                             </a>
                        </div>
                    </li>
                <?php endif; ?>

            <?php endif; ?>
<?php
        }
    }
?>
<ul class="navbar-nav">
    <?php if (!empty($nav_tree)): ?>
        <?php foreach ($nav_tree as $item): ?>
            <?php renderMenuItem($item, $com, 1); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>
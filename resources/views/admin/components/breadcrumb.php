<?php
/**
 * Component Breadcrumb Admin
 * @param string $title Tiêu đề trang (hiển thị góc trái)
 * @param array $bitems Mảng các item ['name' => '...', 'url' => '...']. Item cuối cùng tự động được hiểu là active.
 */
$title = $title ?? '';
$bitems = $bitems ?? [];
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0"><?= htmlspecialchars($title) ?></h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <?php 
                    $count = count($bitems);
                    foreach ($bitems as $index => $item): 
                        $isLast = ($index === $count - 1);
                        $name = htmlspecialchars($item['name'] ?? '');
                        $url = $item['url'] ?? '';
                    ?>
                        <?php if ($isLast || empty($url)): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= $name ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url) ?>"><?= $name ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Component: pagination
 * Phân trang bằng link tĩnh (server-side, reload trang), tái sử dụng
 * cho mọi module admin: bai-viet, category, san-pham, video...
 *
 * Render ra <nav><ul class="pagination">...</ul></nav> chuẩn Bootstrap 5
 * với ARIA, link dạng ?page=N, có ellipsis khi nhiều trang.
 *
 * Props:
 * @var int    $total       Tổng số dòng
 * @var int    $per_page    Số dòng / trang (mặc định 14)
 * @var int    $current     Trang hiện tại (1-based)
 * @var string $base_url    URL cơ sở, vd admin_url(['p' => 'bai-viet', 'a' => 'man'])
 * @var array  $keep_params Mảng key GET cần giữ khi đổi trang, vd ['search', 'key']
 * @var int    $window      Số nút trang hiện xung quanh current (mặc định 2)
 * @var string $size        'sm' | '' (mặc định '')
 *
 * Hành vi:
 *  - Ẩn hoàn toàn nếu total <= per_page.
 *  - Trang 1: nút ‹ bị vô hiệu.
 *  - Trang cuối: nút › bị vô hiệu.
 *  - Ellipsis '…' khi khoảng cách giữa 2 cụm nút >= 2.
 *  - Trang hiện tại được đánh dấu .active + aria-current="page".
 */
$total       = (int) ($total ?? 0);
$per_page    = max(1, (int) ($per_page ?? 14));
$current     = max(1, (int) ($current ?? 1));
$base_url    = (string) ($base_url ?? '');
$keep_params = (array)  ($keep_params ?? []);
$window      = max(0, (int) ($window ?? 2));
$size        = (string) ($size ?? '');

$total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;

// Không có gì để phân trang -> ẩn.
if ($total_pages <= 1) {
    return;
}

// Đảm bảo current không vượt quá tổng trang.
$current = min($current, $total_pages);

/** Sinh URL phân trang; tránh redeclaration khi gọi nhiều lần. */
if (!function_exists('pagination_url')) {
    function pagination_url(string $base, array $keep, int $page): string {
        $sep = strpos($base, '?') === false ? '?' : '&';
        $params = $keep;
        $params['page'] = $page;
        return $base . $sep . http_build_query($params);
    }
}

/** Sinh dãy trang hiển thị với 'gap' ở giữa 2 cụm. */
if (!function_exists('pagination_window')) {
    function pagination_window(int $current, int $total_pages, int $window): array {
        $out = [];
        $start = max(1, $current - $window);
        $end   = min($total_pages, $current + $window);

        if ($start > 1) {
            $out[] = 1;
            if ($start > 2) { $out[] = 'gap'; }
        }
        for ($i = $start; $i <= $end; $i++) { $out[] = $i; }
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) { $out[] = 'gap'; }
            $out[] = $total_pages;
        }
        return $out;
    }
}

$pages = pagination_window($current, $total_pages, $window);

$sizeClass  = $size !== '' ? ' pagination-' . $size : '';
$navAttr    = 'aria-label="Phân trang"';
?>

<nav class="" <?= $navAttr ?>>
    <ul class="pagination pagination-sm justify-content-center mb-0<?= $sizeClass ?>">

        <!-- ‹ Trước -->
        <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
            <?php if ($current <= 1): ?>
                <span class="page-link" aria-hidden="true">&lsaquo;</span>
            <?php else: ?>
                <a class="page-link" href="<?= e(pagination_url($base_url, $keep_params, $current - 1)) ?>"
                   rel="prev" aria-label="Trang trước">&lsaquo;</a>
            <?php endif; ?>
        </li>

        <?php foreach ($pages as $p): ?>
            <?php if ($p === 'gap'): ?>
                <li class="page-item disabled" aria-hidden="true">
                    <span class="page-link">&hellip;</span>
                </li>
            <?php elseif ($p === $current): ?>
                <li class="page-item active" aria-current="page">
                    <span class="page-link"><?= (int) $p ?></span>
                </li>
            <?php else: ?>
                <li class="page-item">
                    <a class="page-link"
                       href="<?= e(pagination_url($base_url, $keep_params, (int) $p)) ?>"
                       aria-label="Trang <?= (int) $p ?>"><?= (int) $p ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- › Sau -->
        <li class="page-item <?= $current >= $total_pages ? 'disabled' : '' ?>">
            <?php if ($current >= $total_pages): ?>
                <span class="page-link" aria-hidden="true">&rsaquo;</span>
            <?php else: ?>
                <a class="page-link" href="<?= e(pagination_url($base_url, $keep_params, $current + 1)) ?>"
                   rel="next" aria-label="Trang sau">&rsaquo;</a>
            <?php endif; ?>
        </li>
    </ul>
</nav>

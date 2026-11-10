<?php
/**
 * LMS Shared Component: pagination
 * Phân trang chuẩn giao diện LMS MasterStudy (Bootstrap 5).
 * Tự động đồng bộ URL, giữ lại các tham số lọc GET, có dấu ellipsis '…' khi nhiều trang.
 *
 * Props:
 * @var int    $page        Trang hiện tại (1-based, mặc định: kế thừa từ $page global)
 * @var int    $total_pages Tổng số trang (mặc định: kế thừa từ $totalPages global)
 * @var string $base_url    URL cơ sở (mặc định: lms_url($alias, 'man') hoặc URL hiện tại)
 * @var array  $params      Mảng tham số giữ lại khi chuyển trang (VD: ['tab' => $tab, 'q' => $q])
 * @var string $size        'sm' | '' | 'lg' (mặc định: 'sm')
 * @var string $align       'center' | 'start' | 'end' (mặc định: 'center')
 * @var int    $window      Số nút trang hiện quanh trang hiện tại (mặc định: 2)
 * @var string $class       Class CSS bổ sung cho <nav>. Mặc định (và khi chưa truyền
 *                          class `mt-*`) component tự thêm `mt-4` để tách phân trang khỏi bảng.
 *
 * PHP 7.3 compatible.
 */

global $page, $totalPages, $alias, $tab;

$curPage    = isset($page) ? max(1, (int) $page) : 1;
$totPages   = isset($total_pages) ? (int) $total_pages : (isset($totalPages) ? (int) $totalPages : 1);
$size       = isset($size) && $size !== '' ? (string) $size : 'sm';
$align      = isset($align) && $align !== '' ? (string) $align : 'center';
$window     = isset($window) ? max(1, (int) $window) : 2;
$params     = isset($params) && is_array($params)
    ? $params
    : (isset($query) && is_array($query) ? $query : array());
$params     = array_filter($params, static function ($v) {
    return $v !== '' && $v !== null;
});
$extraClass = isset($class) ? trim((string) $class) : '';
if ($extraClass === '' || strpos($extraClass, 'mt-') === false) {
    $extraClass = trim('mt-4 ' . $extraClass);
}

// Nếu không có base_url thì tự nhận theo alias hiện tại
if (!isset($base_url) || $base_url === '') {
    $base_url = function_exists('lms_url') ? lms_url($alias ?? 'lms-tai-khoan', 'man') : '';
}

// Nếu tổng trang <= 1 thì không cần hiển thị phân trang
if ($totPages <= 1) {
    return;
}

$curPage = min($curPage, $totPages);

// Hàm tạo URL cho từng trang
$pageUrl = function ($p) use ($base_url, $params) {
    $q = array_merge($params, array('page' => (int) $p));
    $sep = strpos($base_url, '?') === false ? '?' : '&';
    return $base_url . $sep . http_build_query($q);
};

// Căn lề flexbox
$alignClass = 'justify-content-center';
if ($align === 'start') {
    $alignClass = 'justify-content-start';
} elseif ($align === 'end') {
    $alignClass = 'justify-content-end';
}

$ulClass = 'pagination mb-0';
if ($size === 'sm') {
    $ulClass .= ' pagination-sm';
} elseif ($size === 'lg') {
    $ulClass .= ' pagination-lg';
}

$navClass = 'lms-pagination d-flex ' . $alignClass . ' mb-0';
if ($extraClass !== '') {
    $navClass .= ' ' . $extraClass;
}

// Tính toán các cụm trang hiển thị (kèm dấu ...)
$start = max(1, $curPage - $window);
$end   = min($totPages, $curPage + $window);
?>

<nav aria-label="Phân trang LMS" class="<?= htmlspecialchars($navClass, ENT_QUOTES, 'UTF-8') ?>">
    <ul class="<?= $ulClass ?>">
        <!-- Nút Trang trước « -->
        <?php if ($curPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= htmlspecialchars($pageUrl($curPage - 1), ENT_QUOTES, 'UTF-8') ?>" rel="prev" aria-label="Trang trước">«</a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link" aria-hidden="true">«</span>
            </li>
        <?php endif; ?>

        <!-- Trang đầu tiên nếu khoảng cách xa -->
        <?php if ($start > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= htmlspecialchars($pageUrl(1), ENT_QUOTES, 'UTF-8') ?>">1</a>
            </li>
            <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Dãy trang xung quanh current -->
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $curPage ? 'active' : '' ?>" <?= $p === $curPage ? 'aria-current="page"' : '' ?>>
                <?php if ($p === $curPage): ?>
                    <span class="page-link"><?= $p ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>

        <!-- Trang cuối cùng nếu khoảng cách xa -->
        <?php if ($end < $totPages): ?>
            <?php if ($end < $totPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= htmlspecialchars($pageUrl($totPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totPages ?></a>
            </li>
        <?php endif; ?>

        <!-- Nút Trang sau » -->
        <?php if ($curPage < $totPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= htmlspecialchars($pageUrl($curPage + 1), ENT_QUOTES, 'UTF-8') ?>" rel="next" aria-label="Trang sau">»</a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link" aria-hidden="true">»</span>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<?php
/**
 * Lịch sử sử dụng mã khuyến mãi.
 */
$MODULE     = 'ma-khuyen-mai';
$item       = is_array($item ?? null) ? $item : [];
$usage_logs = is_array($usage_logs ?? null) ? $usage_logs : [];
$used_count = (int) ($used_count ?? 0);
$page       = (int) ($_GET['page'] ?? 1);
$id         = (int) ($item['id'] ?? 0);
$ma         = (string) ($item['ma'] ?? '');
$lim        = (int) ($item['gioi_han'] ?? 0);
?>

<?php component('page_header', [
    'title'      => 'Lịch sử dùng mã',
    'subtitle'   => $ma !== '' ? $ma : '',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Mã khuyến mãi', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => 'Lịch sử'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php if (empty($item)): ?>
            <div class="alert alert-warning">Không tìm thấy mã khuyến mãi.</div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>">Quay lại</a>
        <?php else: ?>

            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-semibold"><?= e((string) ($item['ten'] ?? '')) ?></div>
                    <div>
                        <code class="fw-bold"><?= e($ma) ?></code>
                        <span class="ms-2"><?= km_status_badge($item, $used_count) ?></span>
                        <span class="text-secondary small ms-2">
                            Đã dùng <strong><?= $used_count ?></strong> / <?= $lim > 0 ? $lim : '∞' ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary"
                       href="<?= e(admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id])) ?>">
                        <i class="fa-solid fa-pen-to-square me-1"></i>Sửa mã
                    </a>
                    <a class="btn btn-sm btn-outline-secondary"
                       href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>">
                        <i class="fa-solid fa-arrow-left me-1"></i>Danh sách
                    </a>
                </div>
            </div>

            <?php component('card_open', ['body' => 'none']); ?>
                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">STT</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th class="text-center">Thành viên</th>
                                <th class="text-center">Đơn hàng</th>
                                <th class="text-center">Ngày dùng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usage_logs)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 6,
                                    'message' => 'Chưa có lượt sử dụng',
                                    'icon'    => 'fa-regular fa-clock',
                                ]); ?>
                            <?php else: ?>
                                <?php
                                $sttBase = ((int) ($current_page ?? $page) - 1) * (int) ($per_page ?? 20);
                                foreach ($usage_logs as $i => $ls):
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $sttBase + $i + 1 ?></td>
                                        <td><?= e((string) ($ls['email'] ?? '')) ?></td>
                                        <td><?= e((string) ($ls['dien_thoai'] ?? '')) ?></td>
                                        <td class="text-center">
                                            <?= (int) ($ls['id_thanhvien'] ?? 0) > 0 ? '#' . (int) $ls['id_thanhvien'] : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int) ($ls['id_donhang'] ?? 0) > 0): ?>
                                                <a href="<?= e(admin_url(['p' => 'quan-ly-don-hang', 'a' => 'edit', 'id' => (int) $ls['id_donhang']])) ?>">
                                                    #<?= (int) $ls['id_donhang'] ?>
                                                </a>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap"><?= e((string) ($ls['ngay_dung'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-3 pb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> lượt
                    </div>
                    <?php component('pagination', [
                        'total'       => (int) ($total_records ?? 0),
                        'per_page'    => (int) ($per_page ?? 20),
                        'current'     => (int) ($current_page ?? $page),
                        'base_url'    => admin_url(['p' => $MODULE, 'a' => 'history', 'id' => $id]),
                        'keep_params' => [],
                    ]); ?>
                </div>
            <?php component('card_close', ['body' => 'none']); ?>

        <?php endif; ?>
    </div>
</div>

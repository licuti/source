<?php
$title = "Cấu hình Sitemap";
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình hệ thống', 'url' => '#'],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            <!-- Cấu hình -->
            <div class="col-md-9">
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">Chi tiết cấu hình Sơ đồ trang web</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= route('admin.sitemap.save') ?>" method="POST">
                            
                            <?php 
                            $groups = [
                                'post' => 'Bài viết',
                                'product' => 'Sản phẩm',
                                'category' => 'Danh mục'
                            ];
                            foreach ($groups as $type => $label): 
                            ?>
                            <div class="border rounded p-3 mb-3">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-file-lines text-primary"></i> <?= $label ?></h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Đưa vào Sitemap</label>
                                        <select name="sitemap_<?= $type ?>_enable" class="form-select">
                                            <option value="1" <?= $settings[$type]['enable'] == 1 ? 'selected' : '' ?>>Có</option>
                                            <option value="0" <?= $settings[$type]['enable'] == 0 ? 'selected' : '' ?>>Không</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tần suất cập nhật (ChangeFreq)</label>
                                        <select name="sitemap_<?= $type ?>_freq" class="form-select">
                                            <option value="always" <?= $settings[$type]['freq'] == 'always' ? 'selected' : '' ?>>Always</option>
                                            <option value="hourly" <?= $settings[$type]['freq'] == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                                            <option value="daily" <?= $settings[$type]['freq'] == 'daily' ? 'selected' : '' ?>>Daily</option>
                                            <option value="weekly" <?= $settings[$type]['freq'] == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                            <option value="monthly" <?= $settings[$type]['freq'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                            <option value="yearly" <?= $settings[$type]['freq'] == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                            <option value="never" <?= $settings[$type]['freq'] == 'never' ? 'selected' : '' ?>>Never</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Mức độ ưu tiên (Priority)</label>
                                        <input type="number" step="0.1" min="0" max="1" name="sitemap_<?= $type ?>_priority" class="form-control" value="<?= htmlspecialchars((string)$settings[$type]['priority']) ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu cấu hình</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Công cụ -->
            <div class="col-md-3">
                <div class="card card-outline card-success mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">Công cụ sinh XML</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($sitemapInfo): ?>
                            <div class="alert alert-info py-2">
                                <small>
                                    <strong>File tồn tại:</strong> Có<br>
                                    <strong>Cập nhật lần cuối:</strong> <?= $sitemapInfo['time'] ?><br>
                                    <strong>Dung lượng:</strong> <?= $sitemapInfo['size'] ?>
                                </small>
                            </div>
                            <div class="mb-3">
                                <a href="<?= $sitemapInfo['url'] ?>" target="_blank" class="btn btn-outline-info btn-sm w-100"><i class="fa-solid fa-external-link"></i> Xem file hiện tại</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2">
                                <small>Chưa có file Sitemap nào được tạo!</small>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <p class="small text-muted">Bấm nút bên dưới để hệ thống quét dữ liệu và cập nhật lại file Sitemap.xml</p>
                        <button type="button" id="btnGenerate" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-bolt"></i> TẠO SITEMAP NGAY</button>
                    </div>
                </div>
                
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-body bg-light">
                        <h6 class="fw-bold"><i class="fa-solid fa-circle-info text-primary"></i> Mẹo SEO</h6>
                        <p class="small text-muted mb-0">Việc tạo Sitemap giúp Google Bot dễ dàng tìm thấy các URL ẩn sâu trên web. Sau khi tạo, hãy khai báo đường dẫn này trên Google Search Console.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnGenerate');
    if (btn) {
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang tạo...';
            
            fetch('<?= route('admin.sitemap.generate') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Thành công!', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Lỗi!', data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-bolt"></i> TẠO SITEMAP NGAY';
                }
            })
            .catch(err => {
                Swal.fire('Lỗi!', 'Lỗi kết nối máy chủ.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-bolt"></i> TẠO SITEMAP NGAY';
            });
        });
    }
});
</script>

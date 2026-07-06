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
                                        <?= view('admin.components.select', [
                                            'name' => 'sitemap_' . $type . '_enable',
                                            'label' => 'Đưa vào Sitemap',
                                            'value' => $settings[$type]['enable'],
                                            'options' => [
                                                '1' => 'Có',
                                                '0' => 'Không'
                                            ]
                                        ]) ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= view('admin.components.select', [
                                            'name' => 'sitemap_' . $type . '_freq',
                                            'label' => 'Tần suất cập nhật (ChangeFreq)',
                                            'value' => $settings[$type]['freq'],
                                            'options' => [
                                                'always' => 'Always',
                                                'hourly' => 'Hourly',
                                                'daily' => 'Daily',
                                                'weekly' => 'Weekly',
                                                'monthly' => 'Monthly',
                                                'yearly' => 'Yearly',
                                                'never' => 'Never'
                                            ]
                                        ]) ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= view('admin.components.input', [
                                            'type' => 'number',
                                            'name' => 'sitemap_' . $type . '_priority',
                                            'label' => 'Mức độ ưu tiên (Priority)',
                                            'value' => $settings[$type]['priority'],
                                            'attrs' => ['step' => '0.1', 'min' => '0', 'max' => '1']
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Tập tin robots.txt -->
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="fw-bold mb-3"><i class="fa-brands fa-android text-success"></i> Tập tin robots.txt</h6>
                                <p class="small text-muted mb-2">Tập tin này dùng để chỉ đường hoặc ngăn chặn các công cụ tìm kiếm (Google, Bing) quét các thư mục trên website của bạn.
                                <br><strong class="text-danger">Lưu ý:</strong> Phải xóa ngay dòng <code>Disallow: /</code> nếu có, nếu không website của bạn sẽ bị chặn hoàn toàn khỏi Google.</p>
                                <textarea name="robots_txt" class="form-control font-monospace" rows="8" style="background: #282c34; color: #abb2bf;"><?= htmlspecialchars($robotsContent ?? '') ?></textarea>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Lưu cấu hình</button>
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
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Sitemap Index (Tổng hợp)</label>
                            <a href="<?= $sitemapUrl ?>" target="_blank" class="btn btn-outline-info btn-sm w-100 mb-2"><i class="fa-solid fa-sitemap"></i> Xem Sitemap Index</a>
                            
                            <label class="form-label fw-bold small text-muted mt-2">Sitemap Thành phần (Chi tiết)</label>
                            <?php if ($settings['post']['enable'] == 1): ?>
                                <a href="<?= url('/sitemap-posts.xml') ?>" target="_blank" class="btn btn-light btn-sm w-100 mb-1 text-start"><i class="fa-solid fa-file-lines text-primary"></i> Bài viết (Posts)</a>
                            <?php endif; ?>
                            <?php if ($settings['product']['enable'] == 1): ?>
                                <a href="<?= url('/sitemap-products.xml') ?>" target="_blank" class="btn btn-light btn-sm w-100 mb-1 text-start"><i class="fa-solid fa-box text-success"></i> Sản phẩm (Products)</a>
                            <?php endif; ?>
                            <?php if ($settings['category']['enable'] == 1): ?>
                                <a href="<?= url('/sitemap-categories.xml') ?>" target="_blank" class="btn btn-light btn-sm w-100 text-start"><i class="fa-solid fa-folder-open text-warning"></i> Danh mục (Categories)</a>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        <p class="small text-muted">Bấm nút bên dưới để hệ thống gửi thông báo (Ping) cho Google và Bing biết Sitemap đã thay đổi.</p>
                        <button type="button" id="btnPing" class="btn btn-success btn-sm w-100 fw-bold"><i class="fa-solid fa-paper-plane"></i> PING MÁY CHỦ TÌM KIẾM</button>
                    </div>
                </div>
                
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-body bg-light">
                        <h6 class="fw-bold"><i class="fa-solid fa-circle-info text-primary"></i> Mẹo SEO</h6>
                        <p class="small text-muted mb-0">Việc tạo Sitemap giúp Google Bot dễ dàng tìm thấy các URL ẩn sâu trên web. Sau khi cập nhật, hãy bấm Ping để báo cho Search Engines.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnPing');
    if (btn) {
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang Ping...';
            
            fetch('<?= route('admin.sitemap.ping') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Thành công!', data.message, 'success');
                } else {
                    Swal.fire('Lỗi!', data.message, 'error');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> PING MÁY CHỦ TÌM KIẾM';
            })
            .catch(err => {
                Swal.fire('Lỗi!', 'Lỗi kết nối máy chủ.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> PING MÁY CHỦ TÌM KIẾM';
            });
        });
    }
});
</script>

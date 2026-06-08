<?= view('admin.components.breadcrumb', [
    'title' => 'Cấu hình Ngôn ngữ',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Ngôn ngữ', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa-solid fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-outline card-primary shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Danh sách <span class="badge bg-secondary ms-1"><?= count($languages ?? []) ?></span></h3>
                <div class="card-tools d-flex gap-2">
                    <form action="<?= route('admin.language.index') ?>" method="GET" class="d-inline-block m-0">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="keyword" class="form-control float-right" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                    </form>
                    <?php if (hasPermission('admin.language', 'add')): ?>
                    <a href="<?= route('admin.language.create') ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-plus"></i> Thêm mới</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                </th>

                                <th width="80" class="text-center">Icon</th>
                                <th>Tên ngôn ngữ</th>
                                <th>Mã code / Bản địa hóa</th>
                                <th>Nhãn (Label)</th>
                                <th>Ký hiệu</th>
                                <th width="80" class="text-center">RTL</th>
                                <th width="100" class="text-center">Mặc định</th>
                                <th width="100" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($languages)): ?>
                                <?php foreach ($languages as $lang): ?>
                                <tr class="wp-row">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-check" value="<?= $lang->id ?>">
                                    </td>

                                    <td class="text-center">
                                        <?php if ($lang->image): ?>
                                            <img src="<?= getImageUrl($lang->image) ?>" alt="<?= htmlspecialchars($lang->name) ?>" style="width: 32px; height: auto;">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($lang->name) ?></strong>
                                        
                                        <?php 
                                        $actions = [];
                                        if (hasPermission('admin.language', 'edit')) {
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.language.edit', ['id' => $lang->id]), 
                                                'class' => 'text-primary'
                                            ];
                                        }
                                        if (!$lang->is_default && hasPermission('admin.language', 'delete')) {
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => route('admin.language.destroy', ['id' => $lang->id]), 
                                                'class' => 'text-danger btn-delete',
                                                'attributes' => 'onclick="return confirm(\'Bạn có chắc chắn muốn xóa ngôn ngữ này?\');"'
                                            ];
                                        }
                                        if (!empty($actions)) {
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($lang->code) ?></strong> <br>
                                        <small class="text-muted"><?= htmlspecialchars($lang->locale) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($lang->label) ?></td>
                                    <td><?= htmlspecialchars($lang->currency_symbol) ?> (<?= htmlspecialchars($lang->price_unit) ?>)</td>
                                    <td class="text-center">
                                        <?php if ($lang->is_rtl): ?>
                                            <span class="badge text-bg-warning">RTL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($lang->is_default): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($lang->is_active): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Hiển thị</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fa-solid fa-eye-slash"></i> Đã ẩn</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fs-1 d-block mb-2"></i>
                                        Chưa có dữ liệu ngôn ngữ nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer clearfix">
                <div class="row align-items-center">
                    <div class="col-md-6 text-muted small">
                        Hiển thị <?= count($languages ?? []) ?> bản ghi
                    </div>
                    <div class="col-md-6">
                        <!-- Pagination here when backend supports it -->
                    </div>
                </div>
            </div>

        </div>

        <div class="alert alert-warning mt-3">
            <i class="fa-solid fa-circle-exclamation"></i> <strong>Lưu ý:</strong> Bất kỳ thay đổi nào tại đây sẽ tự động đồng bộ và ghi đè nội dung file <code>config/languages.php</code> để tối ưu tốc độ website.
        </div>

    </div>
</div>

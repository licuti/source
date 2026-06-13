<?= view('admin.components.breadcrumb', [
    'title' => 'Cấu hình Ngôn ngữ',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Ngôn ngữ', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <!-- Left: Bulk actions -->
                    <?php if (hasPermission('admin.language', 'delete')): ?>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.language.destroy_multiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các ngôn ngữ đã chọn? (Không áp dụng với ngôn ngữ mặc định)">
                                Xóa
                            </option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>
                            Áp dụng
                        </button>
                    </div>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <!-- Right: Search & Add New -->
                    <form action="<?= route('admin.language.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm ngôn ngữ..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        <?php if (!empty($_GET['keyword'])): ?>
                            <a href="<?= route('admin.language.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>
                        <?php if (hasPermission('admin.language', 'add')): ?>
                            <a href="<?= route('admin.language.create') ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Thêm mới
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
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
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <?php if (hasPermission('admin.language', 'edit') && !$lang->is_default): ?>
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $lang->id ?>" data-field="is_active" data-url="<?= route('admin.language.updateStatusAjax') ?>" <?= $lang->is_active ? 'checked' : '' ?>>
                                            <?php else: ?>
                                                <input class="form-check-input" type="checkbox" <?= $lang->is_active ? 'checked' : '' ?> disabled title="<?= $lang->is_default ? 'Ngôn ngữ mặc định không thể ẩn' : '' ?>">
                                            <?php endif; ?>
                                        </div>
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

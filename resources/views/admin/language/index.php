

<?= view('admin.components.breadcrumb', [
    'title' => 'Cấu hình Ngôn ngữ',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Ngôn ngữ', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Danh sách Ngôn ngữ</h3>
                        <a href="<?= route('admin.language.create') ?>" class="btn btn-sm btn-primary ms-auto">
                            <i class="fa-solid fa-plus"></i> Thêm mới
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success m-3">
                                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger m-3">
                                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="50" class="text-center">STT</th>
                                    <th width="80" class="text-center">Icon</th>
                                    <th>Mã code</th>
                                    <th>Bản địa hóa</th>
                                    <th>Tên ngôn ngữ</th>
                                    <th>Nhãn (Label)</th>
                                    <th>Ký hiệu</th>
                                    <th width="80" class="text-center">RTL</th>
                                    <th width="100" class="text-center">Mặc định</th>
                                    <th width="100" class="text-center">Hiển thị</th>
                                    <th width="150" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($languages)): ?>
                                    <?php foreach ($languages as $lang): ?>
                                    <tr>
                                        <td class="text-center"><?= $lang->sort_order ?></td>
                                        <td class="text-center">
                                            <?php if ($lang->image): ?>
                                                <img src="<?= getImageUrl($lang->image) ?>" alt="<?= $lang->name ?>" style="width: 32px; height: auto;">
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= $lang->code ?></strong></td>
                                        <td><?= $lang->locale ?></td>
                                        <td><?= $lang->name ?></td>
                                        <td><?= $lang->label ?></td>
                                        <td><?= $lang->currency_symbol ?> (<?= $lang->price_unit ?>)</td>
                                        <td class="text-center">
                                            <?php if ($lang->is_rtl): ?>
                                                <span class="badge text-bg-warning">RTL</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($lang->is_default): ?>
                                                <span class="badge text-bg-success"><i class="fa-solid fa-check"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($lang->is_active): ?>
                                                <span class="badge text-bg-primary">Hiển thị</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">Đã ẩn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= route('admin.language.edit', ['id' => $lang->id]) ?>" class="btn btn-sm btn-info text-white" title="Chỉnh sửa">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <?php if (!$lang->is_default): ?>
                                            <a href="<?= route('admin.language.destroy', ['id' => $lang->id]) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa ngôn ngữ này?');" title="Xóa">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4">Chưa có dữ liệu ngôn ngữ nào!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fa-solid fa-circle-exclamation"></i> <strong>Lưu ý:</strong> Bất kỳ thay đổi nào tại đây sẽ tự động đồng bộ và ghi đè nội dung file <code>config/languages.php</code> để tối ưu tốc độ website.
                </div>
            </div>
        </div>
    </div>
</div>

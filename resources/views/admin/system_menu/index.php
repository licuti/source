<?= view('admin.components.breadcrumb', [
    'title' => 'Cấu trúc Menu (Sidebar)',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Menu Hệ thống', 'url' => '']
    ]
]) ?>

<style>
    .dd { max-width: 100%; }
    .dd-handle { height: auto; padding: 10px 15px; border-radius: 4px; background: #f8f9fa; border: 1px solid #dee2e6; cursor: move; }
    .dd-item { position: relative; }
    .dd-item > button { margin-top: 5px; }
    .menu-item-actions { position: absolute; right: 10px; top: 8px; z-index: 10; }
    .menu-item-actions a { margin-left: 10px; cursor: pointer; }
    .menu-badge { font-size: 11px; padding: 2px 5px; border-radius: 4px; margin-left: 10px; }
    .menu-icon { width: 25px; display: inline-block; text-align: center; }
</style>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            <!-- Cột trái: Cây Menu Kéo Thả -->
            <div class="col-md-7">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Sắp xếp Menu</h3>
                        <div class="card-tools">
                            <button class="btn btn-sm btn-success" id="btnSaveSort"><i class="fa-solid fa-save"></i> Lưu thứ tự</button>
                            <button class="btn btn-sm btn-default" id="btnExpandAll">Mở rộng</button>
                            <button class="btn btn-sm btn-default" id="btnCollapseAll">Thu gọn</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="dd" id="nestable-menu">
                            <ol class="dd-list">
                                <?php
                                if (!empty($tree)) {
                                    echo view('admin.system_menu.nestable_tree', ['items' => $tree]);
                                } else {
                                    echo '<div class="alert alert-info">Chưa có menu nào. Hãy tạo mới bên cột phải.</div>';
                                }
                                ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Form Thêm / Sửa -->
            <div class="col-md-5">
                <div class="card card-outline card-success" id="formCard">
                    <div class="card-header">
                        <h3 class="card-title" id="formTitle">Thêm Menu Mới</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" id="btnCancelEdit" style="display:none;"><i class="fa-solid fa-xmark"></i> Hủy sửa</button>
                        </div>
                    </div>
                    <form action="<?= route('admin.system_menu.store') ?>" method="POST" id="menuForm">
                        <div class="card-body">
                            <div class="mb-3">
                                <label>Tên Menu <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="menuName" class="form-control form-control-sm" required placeholder="Ví dụ: Sản phẩm">
                            </div>
                            
                            <div class="mb-3">
                                <label>Menu Cha</label>
                                <select name="parent" id="menuParent" class="form-select form-select-sm">
                                    <option value="0">--- Gốc (Cấp 1) ---</option>
                                    <?php foreach($parentOptions as $opt): ?>
                                        <option value="<?= $opt->id ?>"><?= htmlspecialchars($opt->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Icon (FontAwesome Class)</label>
                                <input type="text" name="icon" id="menuIcon" class="form-control form-control-sm" placeholder="fa-box, fa-users...">
                                <small class="text-muted">Xem thêm tại <a href="https://fontawesome.com/icons" target="_blank">FontAwesome</a>. Mặc định: fa-circle</small>
                            </div>

                            <div class="mb-3">
                                <label>Route Name (Ưu tiên)</label>
                                <input type="text" name="route_name" id="menuRoute" class="form-control form-control-sm" placeholder="admin.product.index">
                                <small class="text-muted">Tên route được khai báo trong routes/admin.php</small>
                            </div>

                            <div class="mb-3">
                                <label>Alias (Legacy Fallback)</label>
                                <input type="text" name="alias" id="menuAlias" class="form-control form-control-sm" placeholder="product">
                                <small class="text-muted">Dùng cho code cũ: ?com=alias</small>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label>Huy hiệu (Badge Query)</label>
                                    <input type="text" name="badge_query" id="menuBadgeQuery" class="form-control form-control-sm" placeholder="Đếm bảng nào...">
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Màu Huy hiệu</label>
                                    <select name="badge_color" id="menuBadgeColor" class="form-select form-select-sm">
                                        <option value="danger">Đỏ (Danger)</option>
                                        <option value="warning">Vàng (Warning)</option>
                                        <option value="success">Xanh lá (Success)</option>
                                        <option value="primary">Xanh dương (Primary)</option>
                                        <option value="info">Xanh nhạt (Info)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Quyền hạn (Role Level)</label>
                                <input type="number" name="permission_level" id="menuRole" class="form-control form-control-sm" value="1" min="1">
                                <small class="text-muted">Mức độ quyền tối thiểu để xem menu này.</small>
                            </div>

                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="menuActive" checked value="1">
                                <label class="form-check-label" for="menuActive">Hiển thị Menu</label>
                            </div>

                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-save"></i> Lưu thông tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Init Nestable
        $('#nestable-menu').nestable({
            maxDepth: 3, // Giới hạn lồng 3 cấp
            group: 1
        });

        // Expand / Collapse
        $('#btnExpandAll').on('click', function(e) { $('#nestable-menu').nestable('expandAll'); });
        $('#btnCollapseAll').on('click', function(e) { $('#nestable-menu').nestable('collapseAll'); });

        // Lưu thứ tự qua AJAX
        $('#btnSaveSort').on('click', function() {
            var dataJSON = window.JSON.stringify($('#nestable-menu').nestable('serialize'));
            var btn = $(this);
            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...');
            btn.prop('disabled', true);

            $.post('<?= route('admin.system_menu.updateSortAjax') ?>', { data: dataJSON }, function(response) {
                btn.html('<i class="fa-solid fa-save"></i> Lưu thứ tự');
                btn.prop('disabled', false);
                if (response.success) {
                    AppNotify.success('Lưu thứ tự thành công!');
                } else {
                    AppNotify.error(response.message);
                }
            }, 'json').fail(function() {
                AppNotify.error('Có lỗi xảy ra kết nối server.');
                btn.html('<i class="fa-solid fa-save"></i> Lưu thứ tự');
                btn.prop('disabled', false);
            });
        });

        // Chỉnh sửa Menu
        $(document).on('click', '.btn-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).data('id');
            var url = '<?= route('admin.system_menu.edit', ['id' => 'XXX']) ?>'.replace('XXX', id);
            
            $.get(url, function(res) {
                if(res.success) {
                    var data = res.data;
                    $('#formTitle').text('Chỉnh sửa Menu');
                    $('#formCard').removeClass('card-success').addClass('card-warning');
                    $('#btnCancelEdit').show();
                    
                    // Đổi action form sang update
                    $('#menuForm').attr('action', '<?= route('admin.system_menu.update', ['id' => 'XXX']) ?>'.replace('XXX', id));
                    
                    // Đổ dữ liệu
                    $('#menuName').val(data.name);
                    $('#menuParent').val(data.parent);
                    $('#menuIcon').val(data.icon);
                    $('#menuRoute').val(data.route_name);
                    $('#menuAlias').val(data.alias);
                    $('#menuBadgeQuery').val(data.badge_query);
                    $('#menuBadgeColor').val(data.badge_color);
                    $('#menuRole').val(data.permission_level);
                    $('#menuActive').prop('checked', data.is_active == 1);
                }
            });
        });

        // Xóa Menu
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var href = $(this).attr('href');
            AppNotify.confirm('Xóa menu này sẽ xóa luôn các menu con của nó. Xác nhận?', function() {
                window.location.href = href;
            });
        });

        // Hủy sửa
        $('#btnCancelEdit').on('click', function() {
            $('#formTitle').text('Thêm Menu Mới');
            $('#formCard').removeClass('card-warning').addClass('card-success');
            $(this).hide();
            $('#menuForm').attr('action', '<?= route('admin.system_menu.store') ?>');
            $('#menuForm')[0].reset();
            $('#menuParent').val(0);
            $('#menuActive').prop('checked', true);
        });
    });
</script>

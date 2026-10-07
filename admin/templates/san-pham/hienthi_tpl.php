<?php
/**
 * Sản phẩm — Danh sách.
 * Tìm theo tên/id/mã/loại, xoá mảng, phân trang server-side.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'san-pham';
$TABLE     = '#_sanpham';

$currentSearch = [
    'col'  => $_GET['search'] ?? '',
    'key'  => $_GET['key']    ?? '',
    'loai' => (int) ($_GET['loai'] ?? 0),
];

$catNameMap = [];
foreach ($d->o_fet("select id_code, ten from #_category where lang = '" . LANG . "'") as $c) {
    $catNameMap[(string) $c['id_code']] = $c['ten'];
}
?>

<?php component('page_header', [
    'title'      => 'Sản phẩm',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Sản phẩm'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ten'     => 'Tên sản phẩm',
                'id_code' => 'ID sản phẩm',
                'ma_sp'   => 'Mã sản phẩm (SKU)',
            ],
            'category'   => $loai,
            'show_loai'  => true,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
            'extra_actions' => $canEdit ? [[
                'url'   => '#',
                'label' => 'Đồng bộ từ Pancake',
                'icon'  => 'fa-solid fa-cloud-arrow-down',
                'class' => 'btn-outline-success btn-sync-pancake',
            ], [
                'url'   => '#',
                'label' => 'Hủy đồng bộ Pancake',
                'icon'  => 'fa-solid fa-trash-can',
                'class' => 'btn-outline-danger btn-unsync-pancake',
            ], [
                'url'   => admin_url(['p' => $MODULE, 'a' => 'variation']),
                'label' => 'Biến thể sản phẩm',
                'icon'  => 'fa-solid fa-shapes',
                'class' => 'btn-outline-primary',
            ]] : [],
        ]); ?>

        <?php component('card_open', ['body' => 'none']); ?>

            <form id="form" method="post"
                  action="<?= e(admin_url(['p' => $MODULE, 'a' => 'delete_all'])) ?>">

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">
                                    <input type="checkbox" id="check_all" class="form-check-input"
                                           aria-label="Chọn tất cả">
                                </th>
                                <th class="text-center col-shrink">STT</th>
                                <th class="col-shrink">SKU</th>
                                <th class="col-shrink">Danh mục</th>
                                <th>Tên sản phẩm</th>
                                <th class="text-center col-shrink">Giá</th>
                                <th class="text-center col-shrink">Hình ảnh</th>
                                <th class="text-center col-shrink">Trạng thái</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">SEO</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 10,
                                    'message' => 'Chưa có sản phẩm',
                                    'hint'    => 'Nhấn "Thêm mới" để tạo sản phẩm đầu tiên.',
                                ]); ?>
                            <?php else: ?>
                                <?php foreach ($items as $it): ?> 
                                <?php
                                    $id      = $it['id_code'];
                                    $editUrl = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id]);
                                    $delUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]);
                                ?>
                                <tr>
                                    <td class="text-center col-shrink">
                                        <?php component('row_check', [
                                            'value' => $id,
                                            'show'  => $canDelete,
                                            'label' => $it['ten'],
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('stt_input', [
                                            'table'    => $TABLE,
                                            'col'      => 'so_thu_tu',
                                            'id'       => $id,
                                            'value'    => $it['so_thu_tu'],
                                            'can_edit' => $canEdit,
                                        ]); ?>
                                    </td>
                                    <td class="col-shrink">
                                        <?= e($it['ma_sp'] ?? '—') ?>
                                    </td>
                                    <td class="col-shrink">
                                        <?= e($catNameMap[(string) $it['id_loai']] ?? '—') ?>
                                    </td>
                                    <td>
                                        <a href="<?= e($editUrl) ?>"><?= e($it['ten']) ?></a>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php if (($it['khuyen_mai'] ?? 0) > 0): ?>
                                            <span class="text-danger"><?= number_format((float) $it['khuyen_mai'], 0, ',', '.') ?>đ</span><br>
                                            <span class="text-decoration-line-through text-muted small"><?= number_format((float) $it['gia'], 0, ',', '.') ?>đ</span>
                                        <?php else: ?>
                                            <span class="text-danger"><?= number_format((float) $it['gia'], 0, ',', '.') ?>đ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('thumb', [
                                            'file' => $it['hinh_anh'] ?? '',
                                            'url'  => $editUrl,
                                            'alt'  => $it['ten'],
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('multi_switches', [
                                            'table'    => $TABLE,
                                            'switches' => [
                                                'sp_moi'  => 'Mới',
                                                'sp_hot'  => 'Hot',
                                                'sp_sale' => 'Sale',
                                                'sp_top'  => 'Top',
                                            ],
                                            'row'      => $it,
                                            'disabled' => !$canEdit,
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('switch_toggle', [
                                            'table'    => $TABLE,
                                            'col'      => 'hien_thi',
                                            'id'       => $id,
                                            'checked'  => ($it['hien_thi'] ?? 0) == 1,
                                            'disabled' => !$canEdit,
                                            'title'    => 'Hiển thị',
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('seo_status', [
                                            'noindex'  => ($it['noindex']  ?? 0) == 1,
                                            'nofollow' => ($it['nofollow'] ?? 0) == 1,
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('row_actions', [
                                            'edit_url'    => $editUrl,
                                            'delete_url'  => $delUrl,
                                            'name'        => $it['ten'],
                                            'child_count' => 0,
                                            'can_edit'    => $canEdit,
                                            'can_delete'  => $canDelete,
                                        ]); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>                    </table>
                </div>

                <div class="px-3 pb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> sản phẩm
                        &middot; Trang <strong><?= (int) ($current_page ?? 1) ?></strong>
                        / <strong><?= (int) ($total_page ?? 1) ?></strong>
                    </div>

                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
                        'loai'   => $currentSearch['loai'] >   0 ? (string) $currentSearch['loai'] : null,
                    ]);
                    component('pagination', [
                        'total'       => (int) ($total_records ?? 0),
                        'per_page'    => (int) ($limit ?? 12),
                        'current'     => (int) ($current_page ?? 1),
                        'base_url'    => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'keep_params' => $keepQuery,
                    ]);
                    ?>
                </div>
            </form>

        <?php component('card_close', ['body' => 'none']); ?>
    </div>
</div>

<!-- Modal Preview Pancake Data -->
<div class="modal fade" id="pancakePreviewModal" tabindex="-1" aria-labelledby="pancakePreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pancakePreviewModalLabel">Xem trước Dữ liệu từ Pancake</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="pancake-preview-content">
            <div class="text-center py-5">
                <i class="fa-solid fa-spinner fa-spin fa-3x text-secondary mb-3"></i>
                <p>Đang tải dữ liệu từ Pancake POS...</p>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-success" id="btn-confirm-pancake-sync" style="display: none;">
            <i class="fa-solid fa-cloud-arrow-down"></i> Tiến hành Lưu vào Hệ thống
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Sync Pancake Data -->
<div class="modal fade" id="pancakeSyncModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Đang đồng bộ dữ liệu...</h5>
      </div>
      <div class="modal-body text-center">
        <i class="fa-solid fa-cloud-arrow-down fa-3x text-primary mb-3"></i>
        <p id="pancake-sync-status" class="fw-bold">Đang chuẩn bị...</p>
        <div class="progress mb-3" style="height: 20px;">
            <div id="pancake-sync-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
        <div id="pancake-sync-log" class="small text-start text-muted bg-light p-2 rounded" style="max-height: 150px; overflow-y: auto;">
        </div>
      </div>
      <div class="modal-footer" id="pancake-sync-footer" style="display: none;">
        <button type="button" class="btn btn-primary" onclick="location.reload();">Đóng & Tải lại trang</button>
      </div>
    </div>
  </div>
</div>

<script>
    $(document).ready(function() {
        var pancakePreviewModal = new bootstrap.Modal(document.getElementById('pancakePreviewModal'), {
            backdrop: 'static'
        });

        $('.btn-sync-pancake').click(function(e) {
            e.preventDefault();
            
            // Hiện Modal với trạng thái loading
            $('#pancake-preview-content').html(`
                <div class="text-center py-5">
                    <i class="fa-solid fa-spinner fa-spin fa-3x text-secondary mb-3"></i>
                    <p>Đang tải dữ liệu từ Pancake POS...</p>
                </div>
            `);
            $('#btn-confirm-pancake-sync').hide();
            pancakePreviewModal.show();

            // Gọi AJAX lấy preview đệ quy
            var allParents = [];
            
            function renderPancakeTable(parents, raw_response) {
                if (parents.length === 0) {
                    let debugStr = JSON.stringify(raw_response, null, 2);
                    $('#pancake-preview-content').html(`
                        <div class="alert alert-warning">
                            <p class="fw-bold mb-1">Không tìm thấy sản phẩm nào trên Pancake.</p>
                            <p class="small text-muted mb-2">Điều này có thể do Cửa hàng chưa có sản phẩm, hoặc cần nhập <strong>Shop ID</strong> trong file <code>config.php</code>.</p>
                            <details class="mt-2">
                                <summary class="small cursor-pointer text-primary">Xem phản hồi thô (Raw Data) từ Pancake API</summary>
                                <pre class="bg-dark text-light p-2 mt-2 rounded small" style="max-height: 250px; overflow: auto;">` + debugStr + `</pre>
                            </details>
                        </div>
                    `);
                    return;
                }

                let html = '<p class="text-secondary"><i class="fa-solid fa-circle-info me-1"></i> Bản xem trước đang hiển thị <strong>' + parents.length + '</strong> sản phẩm mới nhất đang có trên Pancake POS.</p>';
                html += '<div class="input-group mb-3"><span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span><input type="text" id="pancake-search" class="form-control" placeholder="Tìm kiếm sản phẩm theo tên hoặc mã SKU..." autofocus></div>';
                html += '<div class="table-responsive"><table class="table table-bordered table-sm">';
                html += '<thead class="table-light"><tr><th class="text-center" style="width:40px;"><input type="checkbox" id="chk-all-pancake" checked class="form-check-input"></th><th>Hình ảnh</th><th>Tên sản phẩm</th><th>Mã (SKU)</th><th>Giá bán</th><th>Tồn kho</th></tr></thead>';
                
                // Lặp qua dữ liệu trả về để tạo bảng
                parents.forEach(function(p) {
                            let name = p.name || '---';
                            let sku = p.display_id || p.custom_id || p.sku || '---';
                            let pImage = p.image || (p.images && p.images.length > 0 ? p.images[0] : '');
                            let pImgTag = pImage ? `<img src="${pImage}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">` : `<div style="width: 40px; height: 40px; background: #eee; border-radius: 4px;"></div>`;
                            
                            // Pancake CRM thường lưu giá gốc ở retail_price, không có khuyen_mai
                            let price = p.retail_price || p.price || 0;
                            let stock = p.remain_quantity || p.available_stock || p.quantity || 0;
                            
                            let variantCount = (p.variations && Array.isArray(p.variations)) ? p.variations.length : 0;
                            let pId = p.id || '';
                            
                            // Gom tất cả text tìm kiếm (tên sp, sku cha, sku con) vào data-search
                            let searchText = name + " " + sku;
                            if (variantCount > 0) {
                                p.variations.forEach(v => {
                                    searchText += " " + (v.display_id || v.custom_id || '');
                                });
                            }
                            
                            html += `<tbody class="pancake-product-group" data-search="${searchText.replace(/"/g, '')}">`;
                            html += '<tr class="table-secondary fw-bold">';
                            html += '<td class="text-center align-middle"><input type="checkbox" class="form-check-input chk-pancake-item" value="' + pId + '" checked></td>';
                            html += '<td class="text-center">' + pImgTag + '</td>';
                            html += '<td><i class="fa-solid fa-box-open text-primary me-2"></i>' + name + (variantCount > 0 ? ` <span class="badge bg-info ms-2">${variantCount} biến thể</span>` : '') + '</td>';
                            html += '<td><code>' + sku + '</code></td>';
                            html += '<td>' + new Intl.NumberFormat('vi-VN').format(price) + 'đ</td>';
                            html += '<td>' + stock + '</td>';
                            html += '</tr>';

                            // Hiển thị biến thể thụt lề
                            if (variantCount > 0) {
                                p.variations.forEach(function(v) {
                                    let vSku = v.display_id || v.custom_id || '---';
                                    let vImage = (v.images && v.images.length > 0) ? v.images[0] : '';
                                    let vImgTag = vImage ? `<img src="${vImage}" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">` : `<div style="width: 30px; height: 30px; background: #eee; border-radius: 4px;"></div>`;
                                    
                                    let vPrice = v.retail_price || 0;
                                    let vStock = v.remain_quantity || 0;
                                    let vAttrs = '';
                                    
                                    // Hiển thị attributes của biến thể
                                    if (v.fields && Array.isArray(v.fields)) {
                                        let attrArr = v.fields.map(a => `${a.value}`);
                                        vAttrs = attrArr.join(' - ');
                                    } else if (v.attributes && Array.isArray(v.attributes)) {
                                        let attrArr = v.attributes.map(a => `${a.value}`);
                                        vAttrs = attrArr.join(' - ');
                                    } else if (v.sort_variation_attributes) {
                                        vAttrs = v.sort_variation_attributes.replace(/\|/g, ' - ');
                                    }

                                    html += '<tr>';
                                    html += '<td></td>'; // Cột trống để thụt lề dưới ô checkbox
                                    html += '<td class="text-center">' + vImgTag + '</td>';
                                    html += '<td class="ps-5 text-muted"><i class="fa-solid fa-arrow-turn-up fa-rotate-90 me-2"></i>' + (vAttrs || 'Biến thể') + '</td>';
                                    html += '<td><code>' + vSku + '</code></td>';
                                    html += '<td class="text-danger">' + new Intl.NumberFormat('vi-VN').format(vPrice) + 'đ</td>';
                                    html += '<td>' + vStock + '</td>';
                                    html += '</tr>';
                                });
                            }
                            html += '</tbody>';
                        });

                        html += '</table></div>';
                        
                        $('#pancake-preview-content').html(html);
                        $('#btn-confirm-pancake-sync').show();

                        // Thêm sự kiện tìm kiếm
                        $('#pancake-search').on('keyup', function() {
                            let val = $(this).val().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                            $('.pancake-product-group').each(function() {
                                let text = $(this).attr('data-search').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                                if (text.indexOf(val) > -1) {
                                    $(this).show();
                                } else {
                                    $(this).hide();
                                }
                            });
                        });
                        
                        // Xử lý sự kiện nút "Chọn tất cả"
                        $('#chk-all-pancake').change(function() {
                            let isChecked = $(this).is(':checked');
                            $('.chk-pancake-item:visible').prop('checked', isChecked);
                        });
            }

            function loadPancakePage(page) {
                $('#pancake-preview-content').html(`
                    <div class="text-center py-5">
                        <i class="fa-solid fa-spinner fa-spin fa-3x text-secondary mb-3"></i>
                        <p>Đang tải dữ liệu từ Pancake POS (Trang ${page})...</p>
                    </div>
                `);

                $.ajax({
                    url: 'ajax_pancake_preview.php',
                    type: 'POST',
                    data: { page: page, csrf_token: '<?=admin_csrf_token()?>' },
                    dataType: 'text',
                    success: function(rawText) {
                        var res;
                        try {
                            res = JSON.parse(rawText);
                        } catch(e) {
                            // Hiển thị raw response để debug
                            $('#pancake-preview-content').html(
                                '<div class="alert alert-danger">' +
                                '<strong>Lỗi: Dữ liệu trả về không phải JSON hợp lệ.</strong><br>' +
                                '<small>Parse error: ' + e.message + '</small>' +
                                '<hr><strong>Nội dung thô từ server:</strong>' +
                                '<pre class="bg-dark text-white p-2 mt-2 rounded small" style="max-height:300px;overflow:auto;">' +
                                $('<div>').text(rawText).html() +
                                '</pre></div>'
                            );
                            return;
                        }

                        if (res.success) {
                            let parents = res.data.filter(p => !p.product_id);
                            if (parents.length === 0 && res.data.length > 0) parents = res.data;
                            allParents = allParents.concat(parents);

                            if (res.has_more && page < 20) {
                                loadPancakePage(page + 1);
                            } else {
                                renderPancakeTable(allParents, res.raw_response);
                            }
                        } else {
                            if (page === 1) {
                                $('#pancake-preview-content').html('<div class="alert alert-danger"><strong>Lỗi:</strong> ' + res.message + '</div>');
                            } else {
                                renderPancakeTable(allParents, {});
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        if (page === 1) {
                            $('#pancake-preview-content').html(
                                '<div class="alert alert-danger">Lỗi kết nối máy chủ.<br>' +
                                '<small>HTTP Status: ' + xhr.status + ' | ' + error + '</small>' +
                                '<hr><strong>Nội dung thô:</strong>' +
                                '<pre class="bg-dark text-white p-2 mt-2 rounded small" style="max-height:200px;overflow:auto;">' +
                                $('<div>').text(xhr.responseText).html() +
                                '</pre></div>'
                            );
                        } else {
                            renderPancakeTable(allParents, {});
                        }
                    }
                });
            }

            loadPancakePage(1);
        });

        // Nút Xác nhận lưu dữ liệu
        $('#btn-confirm-pancake-sync').click(function() {
            var selectedIds = [];
            $('.chk-pancake-item:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm để đồng bộ!');
                return;
            }

            pancakePreviewModal.hide();
            
            var pancakeSyncModal = new bootstrap.Modal(document.getElementById('pancakeSyncModal'), {
                backdrop: 'static',
                keyboard: false
            });
            pancakeSyncModal.show();
            
            $('#pancake-sync-progress').css('width', '0%').attr('aria-valuenow', 0).text('0%');
            $('#pancake-sync-log').empty();
            $('#pancake-sync-footer').hide();

            var totalSyncedAll = 0;
            
            function syncPage(page) {
                $('#pancake-sync-status').text('Đang xử lý dữ liệu (Trang ' + page + ')...');
                $.ajax({
                    url: 'ajax_pancake_sync.php',
                    type: 'POST',
                    data: { page: page, selected_ids: selectedIds, csrf_token: '<?=admin_csrf_token()?>' },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            totalSyncedAll += res.total_synced;
                            $('#pancake-sync-log').prepend('<div><i class="fa-solid fa-check text-success me-2"></i>' + res.message + '</div>');
                            
                            var pct = Math.min(100, Math.round((page / 20) * 100)); // Giả định tối đa 20 trang
                            $('#pancake-sync-progress').css('width', pct + '%').attr('aria-valuenow', pct).text(pct + '%');

                            if (res.has_more && page < 20) {
                                syncPage(page + 1);
                            } else {
                                $('#pancake-sync-status').text('Hoàn tất đồng bộ!').removeClass('text-danger').addClass('text-success');
                                $('#pancake-sync-progress').css('width', '100%').attr('aria-valuenow', 100).text('100%').removeClass('progress-bar-animated');
                                $('#pancake-sync-log').prepend('<div><i class="fa-solid fa-flag-checkered text-primary me-2"></i><strong>Đã lưu tổng cộng ' + totalSyncedAll + ' sản phẩm (bao gồm biến thể).</strong></div>');
                                $('#pancake-sync-footer').show();
                            }
                        } else {
                            $('#pancake-sync-status').text('Lỗi đồng bộ').addClass('text-danger');
                            $('#pancake-sync-log').prepend('<div class="text-danger"><i class="fa-solid fa-xmark me-2"></i>' + res.message + '</div>');
                            $('#pancake-sync-footer').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#pancake-sync-status').text('Lỗi kết nối máy chủ').addClass('text-danger');
                        $('#pancake-sync-log').prepend('<div class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Lỗi kết nối (HTTP ' + xhr.status + ')</div>');
                        $('#pancake-sync-footer').show();
                    }
                });
            }
            
            syncPage(1);
        });

        // Xóa hàng loạt SP Pancake
        $(document).on('click', '.btn-unsync-pancake', function(e) {
            e.preventDefault();
            
            var selectedIds = [];
            $('.js-row-check:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm để hủy đồng bộ.');
                return false;
            }
            
            if (confirm("Hành động này sẽ XÓA VĨNH VIỄN " + selectedIds.length + " sản phẩm đã chọn khỏi máy chủ Website này.\n\nLưu ý: Sản phẩm trên phần mềm Pancake sẽ không bị ảnh hưởng.\n\nBạn có chắc chắn muốn Xóa không?")) {
                var $btn = $(this);
                $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang xóa...').addClass('disabled');
                
                $.ajax({
                    url: 'ajax_pancake_unsync.php',
                    type: 'POST',
                    data: { selected_ids: selectedIds, csrf_token: '<?=admin_csrf_token()?>' },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            alert('Đã xóa thành công ' + res.deleted + ' sản phẩm từ Pancake khỏi hệ thống.');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + res.message);
                            $btn.html('<i class="fa-solid fa-trash-can me-1"></i> Hủy đồng bộ Pancake').removeClass('disabled');
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra khi thực thi.');
                        $btn.html('<i class="fa-solid fa-trash-can me-1"></i> Hủy đồng bộ Pancake').removeClass('disabled');
                    }
                });
            }
        });
    });
</script>
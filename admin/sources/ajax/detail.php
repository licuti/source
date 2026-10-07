<?php
/**
 * Partial DETAIL - handler xem chi tiet (binh luan, coupon).
 * KHONG goi truc tiep; phai include tu sources/ajax.php.
 * Yeu cau: $d (func_index), $do (string), admin_ajax_e() da co.
 */
if (!isset($d) || !is_object($d) || !isset($do)) { http_response_code(500); exit; }

if ($do == 'get_binhluan') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo '<div class="alert alert-warning mb-0">ID không hợp lệ.</div>';
        exit;
    }

    $row = $d->simple_fetch('SELECT * FROM #_binhluan WHERE id = ' . $id);
    if (empty($row)) {
        echo '<div class="alert alert-warning mb-0">Không tìm thấy bình luận.</div>';
        exit;
    }

    $lang = defined('LANG') ? LANG : 'vi';
    $langSql = $d->clear($lang);
    $baseUrl = defined('URLPATH') ? URLPATH : '/';

    $spId  = (int) ($row['id_sanpham'] ?? 0);
    $tinId = (int) ($row['id_tin'] ?? 0);
    $objLabel = '';
    $objName  = '';
    $objLink  = '';
    $loaiText = 'Khác';

    if ($spId > 0) {
        $loaiText = 'Sản phẩm';
        $objLabel = 'Sản phẩm';
        $obj = $d->simple_fetch(
            "SELECT ten, alias FROM #_sanpham WHERE id_code = {$spId} AND lang = '{$langSql}' LIMIT 1"
        );
        if (!empty($obj['ten'])) {
            $objName = $obj['ten'];
            if (!empty($obj['alias'])) {
                $objLink = $baseUrl . $obj['alias'] . '.html';
            }
        } else {
            $objName = 'SP #' . $spId;
        }
    } elseif ($tinId > 0) {
        $loaiText = 'Tin tức';
        $objLabel = 'Tin tức';
        $obj = $d->simple_fetch(
            "SELECT ten, alias FROM #_tintuc WHERE id_code = {$tinId} AND lang = '{$langSql}' LIMIT 1"
        );
        if (!empty($obj['ten'])) {
            $objName = $obj['ten'];
            if (!empty($obj['alias'])) {
                $objLink = $baseUrl . $obj['alias'] . '.html';
            }
        } else {
            $objName = 'Tin #' . $tinId;
        }
    }

    $stars = max(0, min(5, (int) ($row['danh_gia'] ?? 0)));
    $title = trim((string) ($row['tieu_de'] ?? ''));
    if ($title === '') {
        $title = 'Chi tiết bình luận';
    }

    $fields = [
        'Loại'       => $loaiText,
        'Họ tên'     => $row['ho_ten'] ?? '',
        'Email'      => $row['email'] ?? '',
        'Điện thoại' => $row['dien_thoai'] ?? '',
        'Ngày gửi'   => $row['ngay'] ?? '',
    ];
    if ($objLabel !== '' && $objName !== '') {
        // chèn sau Loại
        $fields = [
            'Loại'       => $loaiText,
            $objLabel    => $objName,
            'Họ tên'     => $row['ho_ten'] ?? '',
            'Email'      => $row['email'] ?? '',
            'Điện thoại' => $row['dien_thoai'] ?? '',
            'Ngày gửi'   => $row['ngay'] ?? '',
        ];
    }
    ?>
    <div class="review-detail">
        <h5 class="text-center text-uppercase fw-semibold mb-2"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h5>
        <?php if ($spId > 0): ?>
        <div class="text-center text-warning mb-3" aria-label="<?= (int) $stars ?> sao">
            <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fa-<?= $s <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
            <?php endfor; ?>
            <span class="text-secondary small ms-1">(<?= (int) $stars ?>/5)</span>
        </div>
        <?php endif; ?>
        <table class="table table-sm table-bordered align-middle mb-0">
            <tbody>
            <?php foreach ($fields as $label => $val): ?>
                <?php if (trim((string) $val) === '') { continue; } ?>
                <tr>
                    <th scope="row" class="bg-body-tertiary" style="width:140px"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                    <td>
                        <?php if ($label === 'Email'): ?>
                            <a href="mailto:<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php elseif ($label === 'Điện thoại'): ?>
                            <a href="tel:<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php elseif (($label === 'Sản phẩm' || $label === 'Tin tức') && $objLink !== ''): ?>
                            <a href="<?= htmlspecialchars($objLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <th scope="row" class="bg-body-tertiary">Nội dung</th>
                    <td class="text-break" style="white-space:pre-wrap"><?= nl2br(htmlspecialchars((string) ($row['noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php }else if($do == 'get_coupon'){
    $id = (int)$_POST['id'];
    $row = $d->simple_fetch("select * from #_coupon where id = '".$id."' ");
    $d->o_que("update #_coupon set trang_thai = 1 where id = $id"); 
?>
<?php if(!empty($row['tieu_de'])){ ?>
    <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;"><?=admin_ajax_e($row['tieu_de'])?></h4>
    <?php }else{?>
        <h4 class="text-center" style="text-transform: uppercase;font-size: 16px;font-weight: 600;">Thông tin chi tiết</h4>
    <?php }?>
    <table class="table">
        <tr>
            <td style="width: 90px;font-weight: bold;">Họ tên:</td>
            <td><?=admin_ajax_e(isset($row['ho_ten']) ? $row['ho_ten'] : '')?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Điện thoại:</td>
            <td><?=admin_ajax_e(isset($row['sdt']) ? $row['sdt'] : '')?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Ngày sinh:</td>
            <td><?=admin_ajax_e(isset($row['birthday']) ? $row['birthday'] : '')?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Giới tính:</td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Địa chỉ:</td>
            <td><?=admin_ajax_e(isset($row['address']) ? $row['address'] : '')?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Ngày hẹn:</td>
            <td><?=admin_ajax_e(isset($row['datebooking']) ? $row['datebooking'] : '')?></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Thời gian hẹn:</td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Chi nhánh/ hình thức:</td>
            <td></td>
        </tr>
        <tr>
            <td style="width: 90px;font-weight: bold;">Lời nhắn:</td>
            <td><?=admin_ajax_e(strip_tags(isset($row['content']) ? $row['content'] : ''))?></td>
        </tr>
    </table>

<?php } ?>
<?php
    /**
     * Chống lỗi Undefined variable: value
     * Hỗ trợ các tên biến phổ biến khác như $item, $post
     */
    if (!isset($value)) {
        if (isset($item)) $value = $item;
        elseif (isset($post)) $value = $post;
        else return; // Thoát nếu không có dữ liệu
    }

    // Chuyển đổi Array sang Object nếu cần
    if (is_array($value)) {
        $value = (object)$value;
    }
?>
<a href="<?= route('news.show', $value->slug) ?>" class="text-decoration-none">
    <div class="box-blog box-hover-zoom">
        <div class="box-thumbnail">
            <div class="inner-thumbnail ratio ratio-4x3">
                <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>" class="image-cover">
            </div>
        </div>
        <div class="box-content">
            <div class="box-title">
                <h3 class="title"><?= $value->ten ?></h3>
            </div>
            <div class="box-excerpt"><?= $value->mo_ta ?></div>
            <div class="box-view-more"><?= __('xem_them') ?></div>
        </div>
    </div>
</a>
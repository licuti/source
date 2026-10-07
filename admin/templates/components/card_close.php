<?php
/**
 * Component: card_close
 * Đóng thẻ .card mở bởi `card_open`.
 *
 * Props:
 * @var string $footer (tuỳ chọn) HTML cho .card-footer
 * @var string $body   (tuỳ chọn) 'none' nếu card_open cũng dùng body='none'
 */
$footer = $footer ?? '';
$body   = $body   ?? '';
?>
    <?php if ($body !== 'none'): ?>
    </div><!-- /.card-body -->
    <?php endif; ?>
    <?php if ($footer !== ''): ?>
        <div class="card-footer"><?= $footer ?></div>
    <?php endif; ?>
</div><!-- /.card -->
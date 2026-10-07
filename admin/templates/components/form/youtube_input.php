<?php
/**
 * Component: form/youtube_input
 * Nhập mã / URL YouTube + xem trước iframe.
 *
 * Props:
 * @var string $label
 * @var string $name         mặc định ma_video
 * @var string $id
 * @var string $value        mã video hoặc URL đầy đủ
 * @var string $hint
 * @var string $placeholder
 */
$label       = $label       ?? 'Mã YouTube';
$name        = $name        ?? 'ma_video';
$id          = $id          ?? $name;
$value       = trim((string) ($value ?? ''));
$hint        = $hint        ?? 'Dán mã video hoặc URL youtube.com/watch?v=...';
$placeholder = $placeholder ?? 'vd: dQw4w9WgXcQ';

// Chuẩn hóa: nếu dán full URL thì lấy v=
$code = $value;
if ($value !== '' && (strpos($value, 'youtube.com') !== false || strpos($value, 'youtu.be') !== false)) {
    if (preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $value, $m)) {
        $code = $m[1];
    }
}
$previewId = 'yt_preview_' . preg_replace('/[^a-z0-9_]/i', '_', $id);
?>
<div class="mb-3 form-youtube-input" data-youtube-input>
    <label class="form-label fw-medium" for="<?= e($id) ?>"><?= e($label) ?></label>

    <iframe id="<?= e($previewId) ?>"
            class="w-100 rounded border mb-2"
            style="aspect-ratio:16/9;<?= $code === '' ? 'display:none' : '' ?>"
            src="<?= $code !== '' ? 'https://www.youtube.com/embed/' . e($code) : '' ?>"
            title="YouTube preview"
            frameborder="0"
            allowfullscreen
            loading="lazy"></iframe>

    <input type="text"
           class="form-control form-control-sm"
           name="<?= e($name) ?>"
           id="<?= e($id) ?>"
           value="<?= e($code) ?>"
           placeholder="<?= e($placeholder) ?>"
           data-yt-preview="<?= e($previewId) ?>"
           autocomplete="off">

    <?php if ($hint !== ''): ?>
        <div class="form-text"><?= e($hint) ?></div>
    <?php endif; ?>
</div>

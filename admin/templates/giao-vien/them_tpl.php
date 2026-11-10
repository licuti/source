<?php
/**
 * Quản lý Đội ngũ giáo viên — Form chỉnh sửa hồ sơ Website
 *
 * Chuẩn AdminLTE 4 + Bootstrap 5 (tham chiếu bài viết / bai-viet/them_tpl.php).
 * Sử dụng hệ thống component/ và grid 9/3 chuẩn admin.
 *
 * PHP 7.3 compatible.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$teacherId = (int) ($teacher['user_id'] ?? 0);
$MODULE    = 'giao-vien';
$actionUrl = admin_url([
    'p'  => $MODULE,
    'a'  => 'save',
    'id' => $teacherId,
]);

$selectedCourseIds = array();
if (!empty($profile['course_ids'])) {
    $decoded = json_decode($profile['course_ids'], true);
    if (is_array($decoded)) {
        $selectedCourseIds = $decoded;
    }
}

// Giải mã bằng cấp chuyên môn (hỗ trợ cả dạng cũ URL string[] và dạng mới repeater {name, image}[])
$diplomaItems = array();
if (!empty($profile['diploma_images'])) {
    $decodedImgs = json_decode($profile['diploma_images'], true);
    if (is_array($decodedImgs)) {
        foreach ($decodedImgs as $idx => $it) {
            if (is_array($it)) {
                $diplomaItems[] = array(
                    'name'  => (string) ($it['name'] ?? $it['ten'] ?? $it['title'] ?? ''),
                    'image' => (string) ($it['image'] ?? $it['hinh_anh'] ?? ''),
                );
            } elseif (is_string($it) && trim($it) !== '') {
                $diplomaItems[] = array(
                    'name'  => '',
                    'image' => trim($it),
                );
            }
        }
    } elseif (is_string($profile['diploma_images'])) {
        $parts = array_filter(array_map('trim', explode(',', $profile['diploma_images'])));
        foreach ($parts as $p) {
            $diplomaItems[] = array('name' => '', 'image' => $p);
        }
    }
}

component('page_header', [
    'title'      => 'Đội ngũ giáo viên',
    'subtitle'   => '[Hồ sơ: ' . e($teacher['full_name'] ?? '') . ' - ' . e($teacher['teacher_code'] ?? '') . ']',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Đội ngũ giáo viên', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => 'Chỉnh sửa hồ sơ'],
    ],
]);
?>

<div class="app-content">
    <div class="container-fluid">

        <form name="frm" method="post" action="<?= e($actionUrl) ?>"
              enctype="multipart/form-data" data-warn-unsaved>
            <?= admin_csrf_field() ?>
            <input type="hidden" name="teacher_id" value="<?= $teacherId ?>">

            <div class="row g-3">

                <!-- ============ Cột chính (9/12) ============ -->
                <div class="col-lg-9">

                    <!-- 1. Thông tin cố định từ LMS (Read-only) -->
                    <?php component('card_open', [
                        'title' => 'Thông tin cố định từ LMS',
                        'icon'  => 'fa-solid fa-graduation-cap text-secondary',
                    ]); ?>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label text-muted small mb-1">Mã giáo viên</label>
                                <input type="text" class="form-control form-control-sm bg-light"
                                       value="<?= e($teacher['teacher_code'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Họ tên giáo viên</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-bold"
                                       id="teacher_full_name_ro"
                                       value="<?= e($teacher['full_name'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small mb-1">Trạng thái LMS</label>
                                <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-block px-2 py-1 mt-1">
                                    <i class="fa-solid fa-circle-check me-1"></i>Đang công tác
                                </span>
                            </div>
                        </div>
                        <div class="form-text small mt-2">
                            <i class="fa-solid fa-circle-info text-info me-1"></i>
                            Tên và Mã giáo viên được đồng bộ trực tiếp từ hệ thống LMS nhằm đảm bảo không sai lệch dữ liệu.
                        </div>
                    <?php component('card_close'); ?>

                    <!-- 2. Tiêu đề & Thông điệp truyền thông -->
                    <?php component('card_open', [
                        'title' => 'Tiêu đề & Thông điệp truyền thông',
                        'icon'  => 'fa-solid fa-id-card-clip text-primary',
                    ]); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <?php component('form/input', [
                                    'label'       => 'Chức danh / Danh hiệu nổi bật',
                                    'name'        => 'title_badge',
                                    'id'          => 'title_badge',
                                    'value'       => $profile['title_badge'] ?? '',
                                    'placeholder' => 'Ví dụ: IELTS 8.0 OVERALL, Thạc sĩ TESOL...',
                                    'required'    => true,
                                    'hint'        => 'Hiển thị nổi bật ngay dưới tên giáo viên ở đầu trang.',
                                ]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php component('form/input', [
                                    'label'       => 'Câu châm ngôn / Slogan tâm đắc',
                                    'name'        => 'quote',
                                    'id'          => 'quote',
                                    'value'       => $profile['quote'] ?? '',
                                    'placeholder' => 'Ví dụ: Teaching is the greatest act of optimism.',
                                    'hint'        => 'Câu trích dẫn ngắn truyền cảm hứng giảng dạy.',
                                ]); ?>
                            </div>
                            <div class="col-12">
                                <?php component('form/textarea', [
                                    'label'       => 'Đoạn văn giới thiệu chung',
                                    'name'        => 'intro_text',
                                    'id'          => 'intro_text',
                                    'value'       => $profile['intro_text'] ?? '',
                                    'rows'        => 3,
                                    'placeholder' => 'Nhập đoạn văn ngắn giới thiệu về phong cách, nhiệt huyết giảng dạy...',
                                ]); ?>
                            </div>
                        </div>
                    <?php component('card_close'); ?>

                    <!-- 3. Hồ sơ chuyên môn (4 Khối: Chứng chỉ, Kinh nghiệm, Giải thưởng, Học vấn - Dạng Tabs) -->
                    <?php component('card_open', [
                        'title' => 'Hồ sơ chuyên môn',
                        'icon'  => 'fa-solid fa-graduation-cap text-success',
                    ]); ?>
                        <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold" id="tab-cert-btn" data-bs-toggle="tab" data-bs-target="#tab-cert" type="button" role="tab" aria-controls="tab-cert" aria-selected="true">
                                    <i class="fa-solid fa-award text-warning me-1"></i> Chứng chỉ
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="tab-exp-btn" data-bs-toggle="tab" data-bs-target="#tab-exp" type="button" role="tab" aria-controls="tab-exp" aria-selected="false">
                                    <i class="fa-solid fa-briefcase text-info me-1"></i> Kinh nghiệm
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="tab-award-btn" data-bs-toggle="tab" data-bs-target="#tab-award" type="button" role="tab" aria-controls="tab-award" aria-selected="false">
                                    <i class="fa-solid fa-trophy text-warning me-1"></i> Giải thưởng
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="tab-edu-btn" data-bs-toggle="tab" data-bs-target="#tab-edu" type="button" role="tab" aria-controls="tab-edu" aria-selected="false">
                                    <i class="fa-solid fa-user-graduate text-primary me-1"></i> Học vấn
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="profileTabsContent">
                            <!-- Khối 1: Chứng chỉ -->
                            <div class="tab-pane fade show active" id="tab-cert" role="tabpanel" aria-labelledby="tab-cert-btn">
                                <?php component('form/editor', [
                                    'label'   => 'Chứng chỉ chuyên môn',
                                    'name'    => 'certificates',
                                    'id'      => 'certificates',
                                    'value'   => $profile['certificates'] ?? '',
                                    'height'  => '200px',
                                    'toolbar' => 'Basic',
                                    'hint'    => 'Nhập các chứng chỉ quốc tế / chuyên môn (vd: IELTS 8.0, TESOL 120h, CELTA...). Hỗ trợ bullet list, in đậm, link.',
                                ]); ?>
                            </div>

                            <!-- Khối 2: Kinh nghiệm -->
                            <div class="tab-pane fade" id="tab-exp" role="tabpanel" aria-labelledby="tab-exp-btn">
                                <?php component('form/editor', [
                                    'label'   => 'Kinh nghiệm giảng dạy & làm việc',
                                    'name'    => 'experience',
                                    'id'      => 'experience',
                                    'value'   => $profile['experience'] ?? '',
                                    'height'  => '200px',
                                    'toolbar' => 'Basic',
                                    'hint'    => 'Nhập số năm kinh nghiệm, các vị trí đã phụ trách hoặc dự án giảng dạy tiêu biểu.',
                                ]); ?>
                            </div>

                            <!-- Khối 3: Giải thưởng -->
                            <div class="tab-pane fade" id="tab-award" role="tabpanel" aria-labelledby="tab-award-btn">
                                <?php component('form/editor', [
                                    'label'   => 'Giải thưởng & Thành tích',
                                    'name'    => 'awards',
                                    'id'      => 'awards',
                                    'value'   => $profile['awards'] ?? '',
                                    'height'  => '200px',
                                    'toolbar' => 'Basic',
                                    'hint'    => 'Nhập các giải thưởng, danh hiệu giáo viên tiêu biểu hoặc thành tích đạt được.',
                                ]); ?>
                            </div>

                            <!-- Khối 4: Học vấn -->
                            <div class="tab-pane fade" id="tab-edu" role="tabpanel" aria-labelledby="tab-edu-btn">
                                <?php component('form/editor', [
                                    'label'   => 'Trình độ học vấn',
                                    'name'    => 'education',
                                    'id'      => 'education',
                                    'value'   => $profile['education'] ?? '',
                                    'height'  => '200px',
                                    'toolbar' => 'Basic',
                                    'hint'    => 'Nhập trình độ học vấn, trường đại học đào tạo, bằng cử nhân, thạc sĩ, tiến sĩ...',
                                ]); ?>
                            </div>
                        </div>
                    <?php component('card_close'); ?>

                    <!-- 4. Bằng cấp chuyên môn & Video giới thiệu -->
                    <?php component('card_open', [
                        'title' => 'Bằng cấp chuyên môn & Video giới thiệu',
                        'icon'  => 'fa-solid fa-photo-film text-danger',
                    ]); ?>
                        <div class="mb-4">
                            <?php component('form/image_repeater', [
                                'name'             => 'diplomas',
                                'items'            => $diplomaItems,
                                'label'            => 'BẰNG CẤP CHUYÊN MÔN',
                                'image_field'      => 'diploma_images[]',
                                'name_field'       => 'diploma_names[]',
                                'name_label'       => 'Tên bằng cấp / chứng chỉ:',
                                'name_placeholder' => 'Ví dụ: Chứng chỉ TESOL 120 giờ quốc tế, IELTS 8.5, Thạc sĩ Ngôn ngữ...',
                                'add_text'         => 'Thêm bằng cấp / chứng chỉ',
                                'empty_text'       => 'Chưa có bằng cấp nào trong hồ sơ. Bấm "Thêm bằng cấp / chứng chỉ" bên dưới để tạo mới.',
                                'hint'             => 'Nhập tên bằng cấp và upload ảnh scan chứng chỉ để hiển thị trong slide bằng cấp trên website.',
                            ]); ?>
                        </div>

                        <hr>

                        <!-- Video giới thiệu & Ảnh thumbnail video -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <?php component('form/file_picker', [
                                    'label'       => 'VIDEO GIỚI THIỆU (Tải video từ Media)',
                                    'name'        => 'intro_video',
                                    'value'       => $profile['intro_video'] ?? '',
                                    'type'        => 3,
                                    'preview'     => 'video',
                                    'height'      => 180,
                                    'choose_text' => 'Chọn video từ Media (CKFinder)',
                                    'hint'        => 'Định dạng video: .mp4, .webm... Video lưu trong thư mục media/videos.',
                                ]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php component('form/image_picker', [
                                    'label'       => 'ẢNH THUMBNAIL CHO VIDEO',
                                    'name'        => 'video_thumbnail',
                                    'id'          => 'video_thumbnail',
                                    'value'       => $profile['video_thumbnail'] ?? '',
                                    'height'      => 180,
                                    'hint'        => 'Ảnh đại diện thumbnail hiển thị trước khi người dùng bấm phát video.',
                                ]); ?>
                            </div>
                        </div>
                    <?php component('card_close'); ?>

                    <!-- 5. Các khóa học đang dạy (Website) -->
                    <?php component('card_open', [
                        'title' => 'Các khóa học đang dạy (Website)',
                        'icon'  => 'fa-solid fa-book-bookmark text-primary',
                    ]); ?>
                        <p class="text-muted small mb-2">Chọn các chương trình đào tạo mà giáo viên này đứng lớp để hiển thị ra khối <em>"CÁC KHÓA HỌC ĐANG DẠY"</em> trên website:</p>
                        <div class="row g-2">
                            <?php if (!empty($courseOptions)): ?>
                                <?php foreach ($courseOptions as $course): 
                                    $cid = (int) $course['id_code'];
                                    $isChecked = in_array($cid, $selectedCourseIds, true);
                                    ?>
                                    <div class="col-md-6">
                                        <div class="form-check p-2 border rounded bg-light-subtle">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="course_ids[]"
                                                   value="<?= $cid ?>" id="course_<?= $cid ?>" <?= $isChecked ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-medium text-dark" for="course_<?= $cid ?>">
                                                <?= e($course['ten']) ?>
                                                <small class="text-muted d-block">/<?= e($course['alias']) ?>.html</small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted small">Chưa có danh mục khóa học nào trong hệ thống.</div>
                            <?php endif; ?>
                        </div>
                    <?php component('card_close'); ?>

                    <!-- 6. Cấu hình SEO (Chuẩn component form/seo_panel) -->
                    <?php component('card_open', [
                        'title' => 'Tối ưu hóa tìm kiếm (SEO)',
                        'icon'  => 'fa-solid fa-magnifying-glass-chart text-info',
                    ]); ?>
                        <?php component('form/seo_panel', [
                            'title'    => $profile['seo_title'] ?? '',
                            'des'      => $profile['seo_des'] ?? '',
                            'keyword'  => $profile['seo_keyword'] ?? '',
                            'base_url' => defined('URLPATH') ? URLPATH : '',
                            'name_id'  => 'teacher_full_name_ro',
                            'alias_id' => 'fld_slug',
                        ]); ?>
                    <?php component('card_close'); ?>

                </div>

                <!-- ============ Cột phụ (3/12) ============ -->
                <div class="col-lg-3">

                    <!-- Thông tin xuất bản -->
                    <?php component('card_open', ['title' => 'Xuất bản']); ?>

                        <?php component('form/checkbox', [
                            'label'   => 'Hiển thị ra ngoài Website',
                            'name'    => 'show_on_website',
                            'id'      => 'fld_show_on_website',
                            'value'   => 1,
                            'checked' => !empty($profile['show_on_website']) && (int) $profile['show_on_website'] === 1,
                            'switch'  => true,
                            'hint'    => 'Nếu tắt, giáo viên này sẽ bị ẩn khỏi trang Đội ngũ giáo viên.',
                        ]); ?>

                        <hr>

                        <?php component('form/input', [
                            'label'       => 'Đường dẫn tĩnh (Slug)',
                            'name'        => 'slug',
                            'id'          => 'fld_slug',
                            'value'       => $profile['slug'] ?? '',
                            'prefix'      => '/giao-vien/',
                            'placeholder' => 'cao-minh-dat',
                            'hint'        => 'Chỉ dùng chữ thường, số và dấu gạch ngang. Để trống sẽ tự sinh.',
                            'attr'        => ['onkeyup' => "addText(this,'#fld_slug')"],
                        ]); ?>

                        <?php component('form/input', [
                            'label' => 'Số thứ tự',
                            'name'  => 'so_thu_tu',
                            'id'    => 'fld_so_thu_tu',
                            'type'  => 'number',
                            'value' => (int) ($profile['so_thu_tu'] ?? 0),
                            'hint'  => 'Số nhỏ hơn sẽ đứng trước trên danh sách.',
                        ]); ?>

                    <?php component('card_close'); ?>

                    <!-- Ảnh đại diện truyền thông -->
                    <?php component('card_open', ['title' => 'Ảnh đại diện']); ?>
                        <?php component('form/image_picker', [
                            'label'   => 'Ảnh chân dung',
                            'name'    => 'avatar',
                            'id'      => 'avatar',
                            'value'   => $profile['avatar'] ?? '',
                            'height'  => 200,
                            'hint'    => 'Nên sử dụng ảnh chân dung lịch sự, độ phân giải sắc nét.',
                        ]); ?>
                    <?php component('card_close'); ?>

                    <!-- Actions bar (Quay lại / Lưu / Lưu và Sửa) dính đáy chuẩn admin -->
                    <?php component('form/actions_bar', [
                        'back_url' => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'can_edit' => $canEdit,
                    ]); ?>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Tự động resize CKEditor khi chuyển tab trong Hồ sơ chuyên môn (tránh lỗi 0-height hoặc width của iframe ẩn)
document.querySelectorAll('#profileTabs button[data-bs-toggle="tab"]').forEach(function (tabBtn) {
    tabBtn.addEventListener('shown.bs.tab', function (e) {
        var targetSel = e.target.getAttribute('data-bs-target');
        if (!targetSel || typeof CKEDITOR === 'undefined') return;
        var pane = document.querySelector(targetSel);
        if (pane) {
            pane.querySelectorAll('textarea').forEach(function (ta) {
                if (ta.id && CKEDITOR.instances[ta.id]) {
                    CKEDITOR.instances[ta.id].resize('100%', 200);
                }
            });
        }
    });
});

/*
 * Trước khi submit form, đồng bộ toàn bộ CKEditor sang textarea
 * và format lại slug để đảm bảo chỉ chứa chữ thường, số và dấu gạch ngang.
 */
var teacherForm = document.querySelector('form[name="frm"]');
if (teacherForm) {
    teacherForm.addEventListener('submit', function () {
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances) {
            for (var instance in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(instance)) {
                    CKEDITOR.instances[instance].updateElement();
                }
            }
        }
        document.querySelectorAll('input[name="slug"]').forEach(function (input) {
            if (typeof locdau === 'function') {
                input.value = locdau(input.value);
            }
        });
    });
}
</script>

<?php
if (!defined('_source')) {
    die('Error');
}

// Lấy danh sách giáo viên hiển thị trên website
$teachers = $d->safeFetch(
    "SELECT p.*, t.full_name, t.teacher_code, t.gender 
     FROM #_lms_teacher_profiles p 
     JOIN #_lms_teachers t ON t.user_id = p.teacher_id 
     WHERE p.show_on_website = 1 
     ORDER BY COALESCE(p.so_thu_tu, 9999) ASC, t.full_name ASC"
);

// Giáo viên tiêu biểu (đầu tiên trong danh sách)
$featured = !empty($teachers) ? $teachers[0] : null;
$featuredLink = !empty($featured) ? URLPATH . 'giao-vien/' . $featured['slug'] . '.html' : '#';
$block_introduce = $d->getContent(1455);
$block_video = $d->getContent(1456);

// Chuẩn bị dữ liệu JSON cho client để đổi thông tin khi hover
$teachersClientData = [];
if (!empty($teachers)) {
    foreach ($teachers as $idx => $t) {
        $teachersClientData[] = [
            'name'         => $t['full_name'] ?? '',
            'avatar'       => !empty($t['avatar']) ? getImageUrl($t['avatar']) : URLPATH . 'media/images/demo/img-teaching-staff-1.png',
            'link'         => URLPATH . 'giao-vien/' . ($t['slug'] ?? '') . '.html',
            'certificates' => !empty($t['certificates']) ? $t['certificates'] : '<p class="text-muted fst-italic">Đang cập nhật</p>',
            'experience'   => (!empty($t['experience']) ? $t['experience'] : ($t['education_experience'] ?? '')) ?: '<p class="text-muted fst-italic">Đang cập nhật</p>',
            'awards'       => !empty($t['awards']) ? $t['awards'] : '<p class="text-muted fst-italic">Đang cập nhật</p>',
            'education'    => !empty($t['education']) ? $t['education'] : '<p class="text-muted fst-italic">Đang cập nhật</p>',
        ];
    }
}

?>


<?php if ($block_introduce): ?>
    <div class="block block-header-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4">
                    <div class="header-page">
                        <div class="mb-3"><?= $block_introduce['noi_dung'] ?></div>
                        <button class="btn-custom btn-solid btn-gradient px-5" data-bs-toggle="modal" data-bs-target="#modal-register-contact">ĐĂNG KÝ NGAY</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-cover" style="background-image: url('<?= getImageUrl($block_introduce['background']) ?>'); background-size: contain; background-position: right bottom; background-color: #fdf9f5;"></div>
    </div>
<?php endif ?>

<div class="block">
    <div class="container-fluid">
        <div class="featured-teacher-card">
            <div class="row g-4 align-items-center">
                <div class="col-lg-3 text-center">
                    <div class="featured-teacher-avatar">
                        <a id="featAvatarLink" href="<?= $featuredLink ?>" style="display: block;">
                            <img id="featAvatar" src="<?= !empty($featured['avatar']) ? getImageUrl($featured['avatar']) : URLPATH . 'media/images/demo/img-teaching-staff-1.png' ?>" alt="<?= htmlspecialchars($featured['full_name'] ?? '') ?>" style="transition: opacity 0.15s ease;">
                        </a>
                    </div>
                </div>
                <div class="col-lg-9 px-4">
                    <div class="row justify-content-center g-3">
                        <div class="col-md-5">
                            <div class="teacher-feature-box">
                                <div class="teacher-feature-icon">
                                    <i class="fa-solid fa-award"></i>
                                </div>
                                <div class="teacher-feature-content">
                                    <h4>Chứng chỉ</h4>
                                    <div id="featCert" class="feat-box-body"><?= !empty($featured['certificates']) ? $featured['certificates'] : '<p class="text-muted fst-italic">Đang cập nhật</p>' ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="teacher-feature-box">
                                <div class="teacher-feature-icon">
                                    <i class="fa-solid fa-briefcase"></i>
                                </div>
                                <div class="teacher-feature-content">
                                    <h4>Kinh nghiệm</h4>
                                    <div id="featExp" class="feat-box-body"><?= (!empty($featured['experience']) ? $featured['experience'] : ($featured['education_experience'] ?? '')) ?: '<p class="text-muted fst-italic">Đang cập nhật</p>' ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="teacher-feature-box">
                                <div class="teacher-feature-icon">
                                    <i class="fa-solid fa-trophy"></i>
                                </div>
                                <div class="teacher-feature-content">
                                    <h4>Giải thưởng</h4>
                                    <div id="featAward" class="feat-box-body"><?= !empty($featured['awards']) ? $featured['awards'] : '<p class="text-muted fst-italic">Đang cập nhật</p>' ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="teacher-feature-box">
                                <div class="teacher-feature-icon">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </div>
                                <div class="teacher-feature-content">
                                    <h4>Học vấn</h4>
                                    <div id="featEdu" class="feat-box-body"><?= !empty($featured['education']) ? $featured['education'] : '<p class="text-muted fst-italic">Đang cập nhật</p>' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($teachers)): ?>
            <div class="wrapper-slide mt-4">
                <div class="swiper teachers-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($teachers as $idx => $t): 
                            $tAvatar = !empty($t['avatar']) ? getImageUrl($t['avatar']) : URLPATH . 'media/images/demo/img-teaching-staff-1.png';
                            $tLink = URLPATH . 'giao-vien/' . $t['slug'] . '.html';
                        ?>
                            <div class="swiper-slide" style="height: auto;">
                                <a href="<?= $tLink ?>" class="box-teacher-item box-hover-zoom <?= $idx === 0 ? 'active' : '' ?>" data-teacher-index="<?= $idx ?>">
                                    <div class="box-thumbnail">
                                        <div class="inner-thumbnail ratio ratio-4x3">
                                            <img src="<?= $tAvatar ?>" alt="<?= htmlspecialchars($t['full_name']) ?>" class="">
                                        </div>
                                        <span class="watermark-text">TEACHER</span>
                                    </div>
                                    <div class="box-content">
                                        <h3 class="title"><?= htmlspecialchars($t['full_name']) ?></h3>
                                        <?php if (!empty($t['title_badge'])): ?>
                                            <div class="box-badge"><?= htmlspecialchars($t['title_badge']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="swiper-button-next teachers-next"></div>
                <div class="swiper-button-prev teachers-prev"></div>
            </div>
        <?php else: ?>
            <div class="py-5 text-muted">
                <i class="fa-solid fa-user-tie fa-2x mb-3 d-block text-secondary"></i>
                Hiện chưa có thông tin giáo viên nào.
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="block">
    <div class="container-fluid">
        <div class="row align-items-lg-end g-4 g-lg-5" style="--bs-gutter-x: 2rem; --bs-gutter-y: 2rem;">
            <div class="col-lg-7 d-flex flex-column" style="gap: 2rem">
                <?php include _source."/includes/block-register-test-free.php"; ?>
                <?php include _source."/includes/block-companion.php"; ?>
            </div>
            <div class="col-lg-5">
                <h2 class="heading-title text-gradient d-inline-block mb-3">VIDEO GIỚI THIỆU CHUNG</h2>
                <div class="teacher-video-wrapper position-relative ratio" style="--bs-aspect-ratio: 40%;">
                    <a href="<?= $block_video['link'] ?>" data-fancybox="video" class="video-play position-absolute start-50 top-50 z-1 translate-middle">
                        <i class="fa-solid fa-play"></i>
                    </a>
                    <img src="<?= getImageUrl($block_video['hinh_anh']) ?>" alt="<?= $block_video['ten'] ?>" class="image-cover">
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($teachersClientData)): ?>
<script>
$(document).ready(function () {
    var teachersData = <?= json_encode($teachersClientData, JSON_UNESCAPED_UNICODE) ?>;
    if (!teachersData || !teachersData.length) return;

    var $featAvatar = $('#featAvatar');
    var $featLink = $('#featAvatarLink');
    var $featCert = $('#featCert');
    var $featExp = $('#featExp');
    var $featAward = $('#featAward');
    var $featEdu = $('#featEdu');
    var currentIndex = 0;
    var fadeTimeout = null;

    function renderTeacher(index) {
        if (index === currentIndex) return;
        var t = teachersData[index];
        if (!t) return;
        currentIndex = index;

        // Đổi active state trên slider (áp dụng cho cả slide gốc và duplicate do loop)
        $('.box-teacher-item').removeClass('active');
        $('.box-teacher-item[data-teacher-index="' + index + '"]').addClass('active');

        // Hiệu ứng chuyển động mượt mà
        var $targets = $('.feat-box-body, #featAvatar');
        $targets.css('opacity', '0.25');

        clearTimeout(fadeTimeout);
        fadeTimeout = setTimeout(function () {
            if ($featAvatar.length) {
                $featAvatar.attr('src', t.avatar).attr('alt', t.name);
            }
            if ($featLink.length) {
                $featLink.attr('href', t.link);
            }
            if ($featCert.length) $featCert.html(t.certificates);
            if ($featExp.length) $featExp.html(t.experience);
            if ($featAward.length) $featAward.html(t.awards);
            if ($featEdu.length) $featEdu.html(t.education);

            $targets.css('opacity', '1');
        }, 80);
    }

    // Lắng nghe sự kiện hover (mouseenter) qua delegation để hỗ trợ Swiper loop
    $(document).on('mouseenter', '.box-teacher-item[data-teacher-index]', function () {
        var idx = parseInt($(this).attr('data-teacher-index'), 10);
        if (!isNaN(idx)) {
            renderTeacher(idx);
        }
    });
});
</script>
<?php endif; ?>
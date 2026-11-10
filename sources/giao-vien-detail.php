<?php
if (!defined('_source')) {
    die('Error');
}

// Nếu chưa có biến $teacher_profile từ router, thử tìm qua $com
if (empty($teacher_profile) && !empty($com)) {
    $teacher_profile = $d->safeFetchOne(
        "SELECT p.*, t.full_name, t.teacher_code, t.gender 
         FROM #_lms_teacher_profiles p 
         JOIN #_lms_teachers t ON t.user_id = p.teacher_id 
         WHERE p.slug = ? AND p.show_on_website = 1 LIMIT 1",
        array($com)
    );
}

if (empty($teacher_profile)) {
    include _source . '404.php';
    return;
}

// Xử lý avatar
$avatar = !empty($teacher_profile['avatar'])
    ? getImageUrl($teacher_profile['avatar'])
    : URLPATH . 'media/images/demo/img-teaching-staff-1.png';


// Danh sách bằng cấp chuyên môn
$diplomas = json_decode($teacher_profile['diploma_images'] ?? '', true) ?: array();

// Video giới thiệu & ảnh thumbnail (upload từ media)
$videoFileUrl   = getVideoUrl($teacher_profile['intro_video'] ?? '');
$videoThumbnail = !empty($teacher_profile['video_thumbnail'])
    ? getImageUrl($teacher_profile['video_thumbnail'])
    : URLPATH . 'media/no-image.png';

// Khóa học phụ trách
$assignedCourseIds = array();
if (!empty($teacher_profile['course_ids'])) {
    $cids = json_decode($teacher_profile['course_ids'], true);
    if (is_array($cids)) {
        $assignedCourseIds = array_map('intval', $cids);
    }
}

$courses = array();
if (!empty($assignedCourseIds)) {
    $placeholders = implode(',', array_fill(0, count($assignedCourseIds), '?'));
    $courses = $d->safeFetch(
        "SELECT id, id_code, ten, alias, hinh_anh, mo_ta 
         FROM #_category 
         WHERE id IN ($placeholders) AND hien_thi = 1",
        $assignedCourseIds
    );
}

// Nếu chưa gắn khóa học, lấy 3 khóa học mẫu của hệ thống
if (empty($courses)) {
    $courses = $d->safeFetch(
        "SELECT id, id_code, ten, alias, hinh_anh, mo_ta 
         FROM #_category 
         WHERE module = 2 AND hien_thi = 1 AND parent = 0 
         ORDER BY so_thu_tu ASC LIMIT 3"
    );
}
?>

<div class="block block-teacher-page">
    <div class="container-fluid">
        <div class="row g-4 mb-5">
            <div class="col-lg-5 col-xl-4 text-center">
                <div class="teacher-detail-portrait">
                    <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($teacher_profile['full_name']) ?>" class="w-100">
                    <span class="watermark-text">TEACHER</span>
                </div>
            </div>
            <div class="col-lg-7 col-xl-8">
                <div class="ps-lg-3">
                    <span class="teacher-badge-role">Giáo viên</span>
                    <h1 class="teacher-detail-name"><?= htmlspecialchars($teacher_profile['full_name']) ?></h1>

                    <?php if (!empty($teacher_profile['title_badge'])): ?>
                        <div class="teacher-detail-title-badge text-gradient"><?= htmlspecialchars($teacher_profile['title_badge']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($teacher_profile['quote'])): ?>
                        <div class="teacher-detail-quote">"<?= htmlspecialchars($teacher_profile['quote']) ?>"</div>
                    <?php endif; ?>
                    <div class="teacher-detail-line"></div>
                    <?php if (!empty($teacher_profile['intro_text'])): ?>
                        <div class="teacher-detail-intro mb-4"><?= nl2br(htmlspecialchars($teacher_profile['intro_text'])) ?></div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="teacher-detail-box">
                                <div class="icon">
                                    <img src="/media/images/demo/icon-chung-chi.png" alt="" class="image-contain">
                                </div>
                                <h3 class="title">Chứng chỉ</h3>
                                <div class="content"><?= !empty($teacher_profile['certificates']) ? $teacher_profile['certificates'] : '' ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="teacher-detail-box">
                                <div class="icon">
                                    <img src="/media/images/demo/icon-giai-thuong.png" alt="" class="image-contain">
                                </div>
                                <h3 class="title">Kinh nghiệm</h3>
                                <div class="content"><?= !empty($teacher_profile['experience']) ? $teacher_profile['experience'] : ($teacher_profile['education_experience'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="teacher-detail-box">
                                <div class="icon">
                                    <img src="/media/images/demo/icon-hoc-van-kinh-nghiem.png" alt="" class="image-contain">
                                </div>
                                <h3 class="title">HỌC VẤN & KINH NGHIỆM</h3>
                                <div class="content">
                                    <?= !empty($teacher_profile['awards']) ? $teacher_profile['awards'] : '' ?>
                                    <?= !empty($teacher_profile['education']) ? $teacher_profile['education'] : '' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="block py-0">
    <div class="container-fluid">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="teacher-diploma-wrapper">
                    <h2 class="heading-title d-inline-block text-gradient mb-3">BẰNG CẤP CHUYÊN MÔN</h2>
                    <div class="wrapper-slide">
                        <div class="swiper certificate-slide">
                            <div class="swiper-wrapper">
                                <?php foreach ($diplomas as $idx => $dip): ?>
                                    <div class="swiper-slide" style="height: auto;">
                                        <a href="<?= getImageUrl($dip['image']) ?>" data-fancybox="diplomas" data-caption="<?= htmlspecialchars($dip['name'] ?? '') ?>" class="box-certificate">
                                            <div class="box-thumbnail">
                                                <div class="inner-thumbnail ratio ratio-4x3">
                                                    <img src="<?= getImageUrl($dip['image']) ?>" alt="<?= htmlspecialchars($dip['name']) ?>" class="image-contain">
                                                </div>
                                            </div>
                                            <div class="box-content">
                                                <h3 class="title"><?= htmlspecialchars($dip['name']) ?></h3>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                        <div class="swiper-pagination swiper-pagination-simple certificate-pagination mt-3"></div>
                        <!-- <div class="swiper-button-next certificate-next"></div> -->
                        <!-- <div class="swiper-button-prev certificate-prev"></div> -->
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="heading-title d-inline-block text-gradient mb-3">VIDEO GIỚI THIỆU</h2>
                <div class="teacher-video-wrapper position-relative ratio" style="--bs-aspect-ratio: 50%;">
                    <a href="<?= $videoFileUrl ?>" data-fancybox="video" class="video-play position-absolute start-50 top-50 z-1 translate-middle">
                        <i class="fa-solid fa-play"></i>
                    </a>
                    <img src="<?= $videoThumbnail ?>" alt="Video giới thiệu" class="image-cover">
                </div>
            </div>
        </div>
    </div>
</div>


<?php if (!empty($courses)): ?>
    <div class="block">
        <div class="container-fluid">
            <h2 class="heading-title d-inline-block text-gradient mb-3">CÁC KHÓA HỌC ĐANG DẠY</h2>
            <div class="wrapper-slide">
                <div class="swiper teacher-course-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($courses as $c):  ?>
                            <div class="swiper-slide" style="height: auto;">
                                <div class="box-course-teacher">
                                    <div class="box-thumbnail">
                                        <div class="inner-thumbnail ratio ratio-4x3">
                                            <img src="<?= getImageUrl($c['image']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" class="image-cover">
                                        </div>
                                    </div>
                                    <div class="box-content">
                                        <h3 class="title"><?= htmlspecialchars($c['ten']) ?></h3>
                                        <p>35 học viên</p>
                                        <button class="btn-custom btn-solid btn-gradient" data-bs-toggle="modal" data-bs-target="#modal-register-contact">ĐĂNG KÝ NGAY</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>


<div class="block pt-0">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-7">
                <?php include _source."/includes/block-register-test-free.php"; ?>
            </div>
            <div class="col-lg-5">
                <?php include _source."/includes/block-companion.php"; ?>
            </div>
        </div>
    </div>
</div>
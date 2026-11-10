<div class="block">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="heading-content heading-gradient"><?= $row['noi_dung'] ?></div>
            </div>
        </div>
    </div>
</div>

<?php 
	$block_statistics = $d->getContent('1445');
?>
<div class="block d-none">
	<div class="container-fluid">
		<div class="wrapper-examination-statistics">
			<div class="row align-items-center">
				<div class="col-lg-8"><?= $block_statistics['noi_dung'] ?></div>
				<div class="col-lg-2">
					<div class="box-statistics-count">
						<div>Đang có</div>
						<div class="label-count">0</div>
					</div>
				</div>
				<div class="col-lg-2">
					<div class="box-statistics-count">
						<div>Sắp tới</div>
						<div class="label-count">2</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="block pt-0 d-none">
	<div class="container-fluid">
		<div class="row g-4">
			<div class="col-12">
				<div class="examination-box examination-box-1">
					<div class="box-start">
						<h3 class="fw-bold">Bài test đầu vào</h3>
						<p>Danh sách các bài test đầu vào cho các cấp</p>
					</div>
					<div class="box-end d-flex align-items-center gap-3">
						<div class="examination-badge">12 bài thi</div>
						<a href="" class="btn-custom btn-solid">Xem chi tiết <i class="fa-regular fa-arrow-right"></i></a>
					</div>
				</div>
			</div>
			<div class="col-12">
				<div class="examination-box examination-box-2">
					<div class="box-start">
						<h3 class="fw-bold">Các kỳ thi đang diễn ra</h3>
						<p>Danh sách các bài test đầu vào cho các cấp</p>
					</div>
					<div class="box-end d-flex align-items-center gap-3">
						<div class="examination-badge">0 đang diễn ra</div>
						<a href="" class="btn-custom btn-solid">Xem chi tiết <i class="fa-regular fa-arrow-right"></i></a>
					</div>
				</div>
			</div>
			<div class="col-12">
				<div class="examination-box examination-box-3">
					<div class="box-start">
						<h3 class="fw-bold">Các kỳ thi sắp diễn ra</h3>
						<p>Danh sách các bài test đầu vào cho các cấp</p>
					</div>
					<div class="box-end d-flex align-items-center gap-3">
						<div class="examination-badge">2 sắp diễn ra</div>
						<a href="" class="btn-custom btn-solid">Xem chi tiết <i class="fa-regular fa-arrow-right"></i></a>
					</div>
				</div>
			</div>
			<div class="col-12">
				<div class="examination-box examination-box-4">
					<div class="box-start">
						<h3 class="fw-bold">MasterStudy Cup</h3>
						<p>Danh sách các bài test đầu vào cho các cấp</p>
					</div>
					<div class="box-end d-flex align-items-center gap-3">
						<div class="examination-badge">2 sắp diễn ra</div>
						<a href="" class="btn-custom btn-solid">Xem chi tiết <i class="fa-regular fa-arrow-right"></i></a>
					</div>
				</div>
			</div>
			<div class="col-12">
				<div class="examination-box examination-box-5">
					<div class="box-start">
						<h3 class="fw-bold">LMS</h3>
						<p>Danh sách các bài test đầu vào cho các cấp</p>
					</div>
					<div class="box-end d-flex align-items-center gap-3">
						<div class="examination-badge">2 sắp diễn ra</div>
						<a href="" class="btn-custom btn-solid">Xem chi tiết <i class="fa-regular fa-arrow-right"></i></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
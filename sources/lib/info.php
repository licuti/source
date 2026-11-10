<?php
	$info = $d->showthongtin();

	define("_logo",  URLPATH.'media/images/'.$info['icon_share']);
	define("_favicon",  URLPATH.'media/images/'.$info['favicon']);

	define("_coppy_right", $info['coppy_right']);

	define("_website",  $info['website']);
	define("_web_page",  $info['website'] ?? URLPATH);
	define("_ten_cong_ty",  $info['company']);
	define("_dia_chi",  $info['address']);
	define("_email",  $info['email']);
	define("_dien_thoai",  $info['dien_thoai']);
	define("_hotline",  $info['hotline']);
	define("_thoi_gian",  $info['thoi_gian']);

	define("_bando",  $info['map']);
	define("_link_map",  $info['link_map']);

	define("_zalo",  $info['zalo']);
	define("_messenger",  $info['messenger']);
	define("_skype",  $info['skype']);
	define("_facebook",  $info['facebook']);
	define("_twitter",  $info['twitter']);
	define("_linkedin",  $info['linkedin']);
	define("_youtube",  $info['youtube']);
	define("_pinterest",  $info['pinterest']);
	define("_instagram",  $info['instagram']);
	define("_telegram",  $info['telegram']);
	define("_whatsapp",  $info['whatsapp']);
	define("_tiktok",  $info['tiktok']);
	define("_shoppe",  $info['shoppe']);

	define("_sitekey",  $info['site_key']);
	define("_secretkey",  $info['secret_key']);
	define("_url_page",  $d->fullAddress());
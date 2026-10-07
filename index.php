<?php
    if(!isset($_SESSION)){
        session_set_cookie_params(['SameSite' => 'None']);
        session_start();
    }
    ob_start();
    error_reporting(0);

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    define('_source','./sources/');
    define('_lib','./admin/lib/');
    define('_source_lib','sources/lib/');


    global $d;
    global $lang;
    require_once __DIR__ . '/admin/lib/bootstrap.php';
    require_once(_lib."Mobile_Detect.php");
    $d = new func_index($config['database']);
    $detect = new Mobile_Detect;
    include_once _source_lib."lang.php";
    include_once _source_lib."info.php";
    include_once _source_lib."function.php";
    include_once _source_lib."file_router_index.php";

    if(!isset($_SESSION['token'])){
        token();
    }
?>

<!DOCTYPE html>
<html lang="<?=_lang?>" >
<head>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta name="format-detection" content="telephone=no">
<?php include _source."module/seo.php" ?>
<?php include _source."templates/css.php" ?>

<?php if($com!=''){ ?>
<?=$row['seo_head']?>
<?php } ?>

</head>
    <body>
        <?php //include 'limit.php'; ?>
        <?php include _source."_header.php"; ?>
        <?php include _source.$source.".php"; ?>
        <?php include _source."_footer.php"; ?>
        <?php include _source."/module/button-contact.php"; ?>
        <?php include _source."templates/js.php" ?>
        <?php include 'sitemap/seo_footer.inc';?>
        <?php if($com!=''){ ?>
            <?=$row['seo_body']?>
        <?php } ?>
    </body>
    <?php ?>
</html>

<?php $d->disconnect() ?>
<?php
    error_reporting(0);
    if(!isset($_SESSION))
    {
        session_start();
    }
    if(!isset($_SESSION["user_hash"]))
    {
        header("location: login.php"); die;
    }

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    @define('_template','/templates/');
    @define('_source','/sources/');
    @define('_lib','/lib/');
    include "lib/config.php";
    include "lib/function.php";
    include "lib/class.php";

    require_once '../app/autoload.php';

    // --- NẠP NGÔN NGỮ ĐỘNG (V3) ---
    Model::boot($config['database']);
    load_languages();

    // Nạp logic xử lý ngôn ngữ hiện tại
    include_once "../sources/lib/lang.php";

    // Đồng bộ điều kiện ngôn ngữ cho hệ thống Model mới
    if (defined('_where_lang')) Model::setGlobalConstraint(_where_lang);

    global $d;
    $d = new func_index($config['database']);
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    @include "lib/file_router_admin.php";
    $logo = $d->showthongtin('logo');
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="vi" lang="vi">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Administrator</title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link href="img/icon.png" rel="shortcut icon" type="image/x-icon" />
        <!-- Bootstrap 3.3.7 -->
        <link rel="stylesheet" href="public/plugin/bootstrap/css/bootstrap.min.css">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.13.0/css/all.css">
        <link rel="stylesheet" href="public/plugin/font-awesome/css/font-awesome.min.css">
        <!-- Ionicons -->
        <link rel="stylesheet" href="public/plugin/Ionicons/css/ionicons.min.css">
        <!-- Theme style -->
        <link rel="stylesheet" href="public/css/AdminLTE.min.css">
        <!-- AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load. -->
        <link rel="stylesheet" href="public/css/skins/_all-skins.min.css">
        <link rel="stylesheet" href="css/admin.css">
        <!-- Google Font -->
        <link rel="preconnect" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
        <!-- jQuery 3 -->
        <script src="public/plugin/jquery/dist/jquery.min.js"></script>
        <script src="public/js/jquery.fancybox.min.js"></script>
        <script src="public/js/notify.min.js"></script>
        <script src="js/jquery.twbsPagination.js"></script>
        <script src="ckeditor/ckeditor.js" type="text/javascript"></script>
    </head>
    <body class="hold-transition skin-blue sidebar-mini">
	
        <div class="wrapper">
            <?php @include('templates/header_tpl.php'); ?>
            <?php @include('templates/sidebar_tpl.php'); ?>
            <?php @include "templates/".$template."_tpl.php"; ?>
            <?php @include('templates/footer_tpl.php'); ?>
            <?php //@include('templates/sidebar_right_tpl.php'); ?>
            <?php $d->disconnect() ?>
        </div>        
        
        <!-- Bootstrap 3.3.7 -->
        <script src="public/plugin/bootstrap/js/bootstrap.min.js"></script>
        <!-- FastClick -->
        <script src="public/plugin/fastclick/fastclick.js"></script>
        <!-- AdminLTE App -->
        <script src="public/js/adminlte.min.js"></script>
        <!-- AdminLTE for demo purposes -->
        <script src="public/js/demo.js"></script>
        <!-- Select2 -->
        <link rel="stylesheet" href="public/plugin/select2/css/select2.min.css">
        <script src="public/plugin/select2/js/select2.min.js"></script>
        <script src="public/plugin/select2/js/select2.multi-checkboxes.js"></script>  
        <link rel="stylesheet" href="public/css/jquery.fancybox.min.css">

        <script src="js/admin.js"></script>
        
        <script>
            $('.iframe-btn').fancybox({
                'type'		: 'iframe',
                'autoScale'    	: false
            });

            CKEDITOR.dtd.$removeEmpty['i'] = false;
            CKEDITOR.dtd.$removeEmpty['span'] = false;


            function responsive_filemanager_callback(field_id){
                //console.log(field_id);
                var url=jQuery('#'+field_id).val();
                if(field_id=='album'){
                    var  text = '';
                    for (var i = 0; i < url.length; i++){
                        if(url[i]!=='[' && url[i]!==']' && url[i]!=='"'){
                            text +=url[i];
                        }
                    }
                    let arr = [];
                    arr = text.split(',');
                    for (var i = 0; i < arr.length; i++){
                        $('#list_'+field_id).append('<div class="col-sm-4 p10" ><div class="item-album"><input type="hidden" name="album[]" value="'+arr[i]+'" class="form-control" /><img src="../img_data/images/'+arr[i]+'" /></div><button onclick="$(this).parent().remove()" class="btn btn-delete-album" type="button" style="top: 0;right: 10px;"><i class="fa fa-trash"></i></button></div>');
                    }

                }else{
                    $('#review_'+field_id).attr('src','../img_data/images/'+url);
                    var data = $('#'+field_id).attr('data_view');
                    var id_thuoctinh = $('#'+field_id).attr('data_thuoctinh');
                    if(data!==''){
                        var  text = '';
                        for (var i = 0; i < url.length; i++){
                            if(url[i]!=='[' && url[i]!==']' && url[i]!=='"'){
                                text +=url[i];
                            }
                        }
                        let arr = [];
                        arr = text.split(',');
                        for (var i = 0; i < arr.length; i++){
                            $('#list_album_'+data).append('<div class="col-sm-3 p10" ><div class="item-album"><input type="hidden" name="album_'+id_thuoctinh+'_'+data+'[]" value="'+arr[i]+'" class="form-control" /><img src="../img_data/images/'+arr[i]+'" /></div><button onclick="$(this).parent().remove()" class="btn btn-delete-album" type="button" style="top: 0;right: 10px;"><i class="fa fa-trash"></i></button></div>');
                        }
                    }
                }
            }
             jQuery(function($) {
                $('.select2-multiple').select2MultiCheckboxes({
                    placeholder: "Choose your field",
                    width: "auto"
                })
                $('.select2-multiple2').select2MultiCheckboxes({
                  templateSelection: function(selected, total) {
                    return "Selected " + selected.length + " of " + total+' your field';
                  }
                })
                $('.select2').select2({
                  //placeholder: "Chọn",
                  width: "100%"
                })
            });
             CKEDITOR.on( 'dialogDefinition', function( ev ) {
                // Take the dialog name and its definition from the event data
                var dialogName = ev.data.name;
                var dialogDefinition = ev.data.definition;
                if ( dialogName == 'image' || dialogName == 'link' ) {
                // Remove upload tab
                dialogDefinition.removeContents('Upload');
                dialogDefinition.removeContents('upload');
                }
            });
        </script>
        <style>
            .fancybox-slide--iframe .fancybox-content {
                width  : 800px;
                height : 600px;
                max-width  : 80%;
                max-height : 80%;
                margin: 0;
            }
        </style>
    </body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/jquery.simplePagination.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.umd.js"></script>

<script src="https://cdn.jsdelivr.net/npm/swiper@8.4.7/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.22.1/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert@2.1.2/dist/sweetalert.min.js"></script>
<script src="<?=URLPATH?>templates/script/waypoints.min.js"></script>
<script src="<?=URLPATH?>templates/script/counterup.min.js"></script>
<script src="<?=URLPATH?>templates/script/toc.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script src="<?=URLPATH?>templates/script/shop.js?v=<?=time()?>"></script>
<script src="<?=URLPATH?>templates/script/site.js?v=<?=time()?>"></script>


<script src="https://www.google.com/recaptcha/api.js?onload=CaptchaCallback&render=explicit" async defer></script>
<script>
      var CaptchaCallback = function() {
        var widgetId1;
        var widgetId2;
        var widgetId3;
        if($('#RecaptchaField1').length > 0){
          widgetId1 = grecaptcha.render('RecaptchaField1', {'sitekey' : '<?=_sitekey?>', 'callback' : correctCaptcha_contact});
        }
        if($('#RecaptchaField2').length > 0){
          widgetId2 = grecaptcha.render('RecaptchaField2', {'sitekey' : '<?=_sitekey?>', 'callback' : ft_hiddenRecaptcha});
        }
        if($('#RecaptchaField3').length > 0){
          widgetId3 = grecaptcha.render('RecaptchaField3', {'sitekey' : '<?=_sitekey?>', 'callback' : dis_hiddenRecaptcha})
        }
    };

    var correctCaptcha_contact = function(response) {
        $("#ct_hiddenRecaptcha").val(response);
    };

    var ft_hiddenRecaptcha = function(response) {
        $("#ft_hiddenRecaptcha").val(response);
    };

    var dis_hiddenRecaptcha = function(response) {
        $("#dis_hiddenRecaptcha").val(response);
    };
</script>

<?php if(isset($thongbao_tt) and $thongbao_tt!=""){ ?>
    <script type="text/javascript">
            swal({
                title: '<?= $thongbao_tt ?>',
                text: '<?= $thongbao_content ?>',
                icon: '<?= $thongbao_icon ?>',
                button: false,
                timer: 2000
            }).then((value) => {
                window.location="<?= $thongbao_url ?>";
            });
    </script>
<?php } ?>
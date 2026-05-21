$(document).ready(function() {

    AOS.init();

    $(window).scroll(function() {
        if ($(window).scrollTop() > 300) {
            $('.back-to-top').addClass('show');
        }else {
            $('.back-to-top').removeClass('show');
        }
    });

    $('.back-to-top').on('click', function(e) {
        $('html, body').animate({
            scrollTop:0
        },'300');
    });
    
    // 
    var stickyOffset = $('.navbar').height();
    $(window).scroll(function(){
        if ($(window).width() >= 992){
            // on desktop
            if ($(window).scrollTop() >= 200) {
                $('.navbar').addClass('navbar-fixed');
            }
            else if($(window).scrollTop() == 0) {
                $('.navbar').removeClass('navbar-fixed');
            }
        }else{
            // on mobile
            if ($(window).scrollTop() >= stickyOffset) {
                $('.navbar').addClass('navbar-fixed');
            }
            else {
                $('.navbar').removeClass('navbar-fixed');
            }
        }
    });

    // menu click on mobile
    if ($(window).width() <= 992){
        $('.toggle-menu').on('click', function(){
            let dropdown_menu = $(this).parent().next();
            $(this).toggleClass('show');
            $(this).parent().toggleClass('show');
            dropdown_menu.slideToggle(300,'linear');
        });
    }

    // 
    $('.counter').counterUp({
        delay: 20,
        time: 500
    });

    $(".page-pagination").each(function(index){
        var items = $(this).find(".content-pagination").children();
        var numItems = items.length;
        var perPage = $(this).data("limit");
        items.slice(perPage).hide();

        $(this).find('.arrow-pagination').pagination({
            items: numItems,
            itemsOnPage: perPage,
            prevText: "<i class='fa-solid fa-angle-left'></i>",
            nextText: "<i class='fa-solid fa-angle-right'></i>",
            onPageClick: function (pageNumber) {
                var showFrom = perPage * (pageNumber - 1);
                var showTo = showFrom + perPage;
                items.hide().slice(showFrom, showTo).show();
            }
        });
    });

    Fancybox.bind("[data-fancybox]", {
        // Your custom options
    });

    var accordionNav = $(function(){
        $('.menu-toggle').click(function(e) {
            e.preventDefault();
            var toggleButton = $(this);
            if (toggleButton.next().hasClass('active')) {
                toggleButton.next().removeClass('active');
                toggleButton.next().slideUp(400);
                toggleButton.removeClass('rotate');
             } else {
                toggleButton.parent().parent().find('li .sub-menu').removeClass('active');
                toggleButton.parent().parent().find('li .sub-menu').slideUp(400);
                toggleButton.parent().parent().find('.menu-toggle').removeClass('rotate');
                toggleButton.next().toggleClass('active');
                toggleButton.next().slideToggle(400);
                toggleButton.toggleClass('rotate');
            }
        });
    });

    // $('.btn-add:not(.qty-up)').click(function () {
    //     if ($(this).prev().val() < 20) {
    //         $(this).prev().val(+$(this).prev().val() + 1);
    //     }
    // });
    // $('.btn-sub:not(.qty-down)').click(function () {
    //     if ($(this).next().val() > 1) {
    //         $(this).next().val(+$(this).next().val() - 1);
    //     }
    // });

});

var main_slide = new Swiper(".main-slide", {
    slidesPerView: 1,
    spaceBetween: 0,
    // loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1500,
    pagination: {
        el: '.main-slide .swiper-pagination',
        clickable: true,
      },
});

var category = new Swiper(".category-slide", {
    spaceBetween: 20,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1000,
    navigation: {
        nextEl: ".category-next",
        prevEl: ".category-prev",
    },
    pagination: {
        el: ".category-slide .swiper-pagination",
        clickable: true,
    },
    breakpoints: {
        320: {
            spaceBetween: 16,
            slidesPerView: 1,
        },
        768: {
            spaceBetween: 20,
            slidesPerView: 2,
        },
        1024: {
            spaceBetween: 24,
            slidesPerView: 3,
        },
    },
});

var partner = new Swiper(".partner-slide", {
    loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1000,
    navigation: {
        nextEl: ".partner-next",
        prevEl: ".partner-prev",
    },
    breakpoints: {
        320: {
            spaceBetween: 16,
            slidesPerView: 3,
        },
        768: {
            spaceBetween: 20,
            slidesPerView: 4,
        },
        1024: {
            spaceBetween: 24,
            slidesPerView: 5,
        },
    },
});

var review = new Swiper(".review-slide", {
    slidesPerView: 1,
    spaceBetween: 20,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1200,
    pagination: {
        el: ".review-slide .swiper-pagination",
        clickable: true,
    },
});

var product = new Swiper(".product-slide", {
    // loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1500,
    freeMode: true,
    navigation: {
        nextEl: ".product-next",
        prevEl: ".product-prev",
    },
    
    breakpoints: {
        320: {
            slidesPerView: 2,
            spaceBetween: 16,
        },
        768: {
            slidesPerView: 3,
            spaceBetween: 20,
        },
        1024: {
            slidesPerView: 4,
            spaceBetween: 24,
            grid: {
                fill: 'row',
                rows: 2
            },
        },
    },
});

const index_slideProduct = document.querySelectorAll(".slideProduct");
for (i = 0; i < index_slideProduct.length; i++) {
    index_slideProduct[i].classList.add("slideProduct-" + i);

    var slideProduct = new Swiper(".slideProduct-" + i, {
        loop: true,
        freeMode: true,
        breakpoints: {
            320: {
                slidesPerView: 2,
                spaceBetween: 16,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 24,
            },
        },
        pagination: {
            el: "slideProduct-" + i + " .swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".slideProduct-" + i + " .swiper-button-next",
            prevEl: ".slideProduct-" + i + " .swiper-button-prev",
        },
    });
}


var post = new Swiper(".post-slide", {
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1000,
    navigation: {
        nextEl: ".post-next",
        prevEl: ".post-prev",
    },
    breakpoints: {
        320: {
            spaceBetween: 8,
            slidesPerView: 1,
        },
        768: {
            spaceBetween: 16,
            slidesPerView: 2,
        },
        1024: {
            spaceBetween: 24,
            slidesPerView: 3,
        },
    },
});

var related_news = new Swiper(".related-news", {
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".related-news .swiper-pagination",
        clickable: true,
    },
    navigation: {
        nextEl: ".news-next",
        prevEl: ".news-prev",
    },
    breakpoints: {
        320: {
            slidesPerView: 1,
            spaceBetween: 8,
        },
        768: {
            slidesPerView: 2,
            spaceBetween: 16,
        },
        1024: {
            slidesPerView: 4,
            spaceBetween: 24,
        },
    },
});

var related_product = new Swiper(".related-product", {
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 1000,
    breakpoints: {
        320: {
            spaceBetween: 12,
            slidesPerView: 2,
        },
        768: {
            spaceBetween: 16,
            slidesPerView: 3,
        },
        1024: {
            spaceBetween: 20,
            slidesPerView: 4,
        },
    },
});

var slide_thumb = new Swiper(".slide_thumb", {
    // loop: true,
    spaceBetween: 8,
    slidesPerView: 4,
    freeMode: true,
    watchSlidesProgress: true,
    
    // breakpoints: {
    //     992: {
    //         direction: "vertical",
    //     },
    // },
    navigation: {
        nextEl: ".slide_thumb_next",
        prevEl: ".slide_thumb_prev",
    },
});

var slide_show = new Swiper(".slide_show", {
    // loop: true,
    spaceBetween: 0,
    navigation: {
        nextEl: ".slide_show_next",
        prevEl: ".slide_show_prev",
    },
    thumbs: {
        swiper: slide_thumb,
    },
});

// Variant logic moved to shop.js
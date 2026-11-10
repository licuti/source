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
        Carousel: {
            Thumbs: {
              type: "classic", // "modern" | "classic" | "scrollable"
            },
        },
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

var main_slide_review = new Swiper(".main-slide-review", {
    autoplay: {
        delay: 5000,
        disableOnInteraction: false,
    },
    speed: 1000,
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

var teaching_staff_slide = new Swiper(".teaching-staff-slide", {
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    breakpoints: {
        320: {
            spaceBetween: 16,
            slidesPerView: 1.2,
            centerInsufficientSlides: true,
            centeredSlides: true,
            centeredSlidesBounds: true,
            initialSlide: 1,
        },
        768: {
            spaceBetween: 16,
            slidesPerView: 2,
        },
        1024: {
            spaceBetween: 24,
            slidesPerView: 4,
        },
    },
    pagination: {
        el: ".pagination-teaching-staff",
        clickable: true,
    },
});

var review_slide = new Swiper(".review-slide", {
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    pagination: {
        el: ".pagination-review-slide",
        clickable: true,
    },
    breakpoints: {
        320: {
            slidesPerView: 1,
            spaceBetween: 40,
            centerInsufficientSlides: true,
            centeredSlides: true,
            centeredSlidesBounds: true,
            initialSlide: 1,
        },
        768: {
            slidesPerView: 2,
            spaceBetween: 40,
        },
        1024: {
            slidesPerView: 2,
            spaceBetween: 80,
        },
    },
});

var course_slide = new Swiper(".course-slide", {
    // loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    navigation: {
        nextEl: ".course-next",
        prevEl: ".course-prev",
    },
    pagination: {
        el: ".pagination-course",
        clickable: true,
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
            slidesPerView: 2,
            spaceBetween: 16,
            grid: {
                fill: 'row',
                rows: 2
            },
        },
    },
});



var program_slide = new Swiper(".program-slide", {
    loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    navigation: {
        nextEl: ".program-next",
        prevEl: ".program-prev",
    },
    pagination: {
        el: ".program-pagination",
        clickable: true,
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
            slidesPerView: 5,
            spaceBetween: 20,
        },
    },
});



var honor_roll_slide = new Swiper(".honor-roll-slide", {
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    loop: true,
    effect: 'coverflow',
    grabCursor: true,
    centeredSlides: true,
    coverflowEffect: {
        rotate: 0,
        stretch: -10,
        depth: 50,
        modifier: 2,
        slideShadows: false,
    },
    breakpoints: {
        320: {
            slidesPerView: 2,
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
    navigation: {
        nextEl: ".honor-roll-next",
        prevEl: ".honor-roll-prev",
    },
});


var teachers_slide = new Swiper(".teachers-slide", {
    loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    navigation: {
        nextEl: ".teachers-next",
        prevEl: ".teachers-prev",
    },
    pagination: {
        el: ".teachers-pagination",
        clickable: true,
    },
    breakpoints: {
        320: {
            slidesPerView: 1.2,
            spaceBetween: 16,
            centerInsufficientSlides: true,
            centeredSlides: true,
            centeredSlidesBounds: true,
            initialSlide: 1,
        },
        768: {
            slidesPerView: 3,
            spaceBetween: 20,
        },
        1024: {
            slidesPerView: 4,
            spaceBetween: 25,
        },
    },
});


var certificate_slide = new Swiper(".certificate-slide", {
    loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    pagination: {
        el: ".certificate-pagination",
        clickable: true,
    },
    breakpoints: {
        320: {
            slidesPerView: 2,
            spaceBetween: 16,
        },
        768: {
            slidesPerView: 3,
            spaceBetween: 16,
        },
        1024: {
            slidesPerView: 3,
            spaceBetween: 16,
        },
    },
});


var teacher_course_slide = new Swiper(".teacher-course-slide", {
    loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
    },
    speed: 1000,
    breakpoints: {
        320: {
            slidesPerView: 2,
            spaceBetween: 16,
        },
        768: {
            slidesPerView: 3,
            spaceBetween: 16,
        },
        1024: {
            slidesPerView: 3,
            spaceBetween: 24,
        },
    },
});



// =================================================================================
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


var post_slide = new Swiper(".post-slide", {
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    speed: 800,
    navigation: {
        nextEl: ".post-next",
        prevEl: ".post-prev",
    },
    pagination: {
        el: ".swiper-pagination-post",
        clickable: true,
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
    speed: 1000,
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
            slidesPerView: 3,
            spaceBetween: 16,
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
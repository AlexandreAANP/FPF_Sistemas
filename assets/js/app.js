//import $ from 'jquery';
//import 'popper.js';
//import 'bootstrap';
//import 'bigslide'
import {tns} from 'tiny-slider/src/tiny-slider';

$(function() {
    var homeSlider = $('#home-slider');
    if(homeSlider.length) {
        tns({
            container: '#home-slider',
            items: 1,
            slideBy: 'page',
            controls: false,
            navPosition: 'bottom',
        });
    }

    var maisProcuradosSlider = $('.mais-procurados-slider');
    if(maisProcuradosSlider.length) {
        tns({
            container: '.mais-procurados-slider',
            items: 1,
            slideBy: 'page',
            controls: false,
            nav: false,
            center: true,
            responsive: {
                768: {
                    fixedWidth: 720,
                },
                992: {
                    fixedWidth: 960,
                },
                1200: {
                    fixedWidth: 1140,
                }
            }
        });
    }

    $('.products-card').on('mouseenter click touchstart', function(e) {
        $('.products-card').removeClass('raise');
        $(this).addClass('raise');
    });

    $('.products-card').on('mouseleave', function(e) {
        $('.products-card').removeClass('raise');
    });

    var productsSliders = $('.products-slider');
    if(productsSliders.length) {
        const fixMargin = function() {
            $('.products-card').removeClass('raise')
        }

        const observeHeight = function(mutationsList, observer) {// Use traditional 'for loops' for IE 11
        for(const mutation of mutationsList) {
            if (mutation.type === 'attributes') {
                $('.tns-ovh').each((_, tns) => {
                    var raise = $(tns).find('.products-card.raise');
                    if(raise.length) {
                        var target = $(tns).find('.products-card.raise');
                        var height = target.height();
                        var marginBottom = parseInt(target.css('marginBottom').replace('px', ''));
                        $(tns).css({
                            transition: 'all .3s ease 0s',
                            height: height + marginBottom + 100,
                            marginBottom: -100
                        })
                    } else {
                        var target = $(tns).find('.products-card')[0];
                        var height = $(target).height();
                        var marginBottom = $(target).css('marginBottom');
                        if(typeof marginBottom != 'undefined') {
                            marginBottom = parseInt(marginBottom.replace('px', ''));
                        } else {
                            marginBottom = 0;
                        }

                        $(tns).css({
                            transition: 'all .3s ease 0s',
                            height: height + marginBottom,
                            marginBottom: 0
                        })
                    }

                })
            }
        }
        }

        var createProductSlider = function(pSlider) {
            var divParent = document.createElement('div');
            divParent.className = 'products-slider-container-controls';

            var prevChild = document.createElement('div');
            var prevIcon = document.createElement('i');
            prevIcon.className = 'mdi mdi-chevron-left';
            prevChild.appendChild(prevIcon);
            divParent.appendChild(prevChild);

            var nextChild = document.createElement('div');
            var nextIcon = document.createElement('i');
            nextIcon.className = 'mdi mdi-chevron-right';
            nextChild.appendChild(nextIcon);
            divParent.appendChild(nextChild);

            pSlider.parentElement.querySelector('.products-slider-top').appendChild(divParent);

            var tnsPSlider = tns({
                container: pSlider,
                autoHeight: true,
                items: 1,
                slideBy: 'page',
                controls: true,
                controlsContainer: divParent,
                nav: false,
                responsive: {
                    768: {
                        items: 2
                    },
                    992: {
                        items: 3
                    },
                    1200: {
                        items: 4
                    }
                }
            });

            tnsPSlider.events.on('transitionStart', fixMargin);
            tnsPSlider.events.on('dragStart', fixMargin);
            
            const config = { attributes: true, childList: true, subtree:true };
            const observer = new MutationObserver(observeHeight);
            observer.observe(pSlider.parentElement.parentElement, config);
        }
            
        for(var x = 0; x < productsSliders.length; x++) {
            let test = productsSliders[x];
            createProductSlider(test);
        }
    }

    var simpleSlider = $('.simple-slider');
    if(simpleSlider.length) {
        tns({
            container: '.simple-slider',
            items: 1,
            slideBy: 'page',
            controls: false,
            navPosition: 'bottom',
        });
    }

    var imageSelectorSlider = $('#image-selector-slider');
    if(imageSelectorSlider.length) {
        tns({
            container: '#image-selector-slider',
            items: 3,
            slideBy: 'page',
            controls: true,
            controlsText: [
                '<i class="mdi mdi-chevron-left"></i>',
                '<i class="mdi mdi-chevron-right"></i>',
            ],
            nav: false,
            gutter: 10,
            responsive: {
                375: { items: 4 },
                425: { items: 5 },
                576: { items: 6 },
                768: { items: 9 },
                992: { items: 4 },
                1200: { items: 5 }
            }
        });

        $('.products-large-image-small-image').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            $('.products-large-image-main').css({
                backgroundImage: "url('" + target + "')"
            })
        });
    }

    $('.main-menu').bigSlide({
        side: 'right'
    });

    $('.required-input').on('click', function() {
        var input = $(this).find('input, textarea');
        if(!input.is(':focus')) {
            input.trigger('focus');
        }
    });

    $('.required-input input, .required-input textarea').on('focusout input', function() {
        var val = $(this).val();
        var label = $(this).prev();
        if(val == "") {
            $(label[0]).fadeIn(0);
        } else {
            $(label[0]).fadeOut(0);
        }
    });

    var $select = $('.required-select select');
    $select.each(function() {
        var val = $(this).children(':selected').val();
        if(val != '') {
            $(this).parent('div').addClass('form-control-selected');
        }
    }).on('change', function(ev) {
        var val = $(this).children(':selected').val();
        if(val != '') {
            $(this).parent('div').addClass('form-control-selected');
        }
    });

    var collapses = [
        '#scoreCollapse',
        '#experienceCollapse',
        '#regionCollapse',
        '#offerCollapse',
        '#tipoCollapse'
    ];

    collapses.map(function(name) {
        $(name).on('show.bs.collapse', function () {
            $('.btn[data-target="' + name + '"] i.mdi').removeClass('mdi-chevron-down').addClass('mdi-chevron-up');
        });
        
        $(name).on('hide.bs.collapse', function () {
            $('.btn[data-target="' + name + '"] i.mdi').removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
        });
    });

    var cartCollapses = [
        '#addressCollapse',
        '#relatedProductsCollapse',
        '#paymentMethodCollapse',
        '#reviewCollapse',
    ];

    $('.cart-box-header a').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');

        cartCollapses.map(function(name) {
            if(name == href) {
                $(href).collapse('toggle');
            } else {
                $(name).collapse('hide');
            }
        });
    });

    cartCollapses.map(function(name) {
        $(name).on('show.bs.collapse', function () {
            $('.cart-box-header a[href="' + name + '"]').parent().parent().removeClass('closed')
            $('.cart-box-header a[href="' + name + '"] i.mdi').removeClass('mdi-plus').addClass('mdi-minus');
        });
        
        $(name).on('hidden.bs.collapse', function () {
            $('.cart-box-header a[href="' + name + '"]').parent().parent().addClass('closed')
            $('.cart-box-header a[href="' + name + '"] i.mdi').removeClass('mdi-minus').addClass('mdi-plus');
        });
    });

    var sliders = [
        '#priceSlider'
    ];

    sliders.map(function(name) {
        new rSlider({
            target: name,
            values: Array(150 + 1).fill().map((_, idx) => idx * 10),
            steps: 1,
            range: true,
            tooltip: false,
            scale: false,
            labels: false,
            set: [180, 1500],
            onChange: function(values) {
                values = values.split(',');
                $(name + ' + .rs-container .rs-scale span:first-child ins').html(values[0]);
                $(name + ' + .rs-container .rs-scale span:last-child ins').html(values[1]);
            }
        });
    });

    $('[data-target="#priceCollapse"]').trigger('click');


    $('.tabs .nav-tabs .nav-link').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.tabs .nav-tabs .nav-link').removeClass('active');
        $(this).addClass('active');

        $('.tabs-content > div').fadeOut(150);
        $('.tabs-content > div').promise().done(function() {
            $('.tabs-content ' + target).fadeIn(150);
        });
    });

    $('.to-score-stars .mdi-star').on('click mouseenter', function() {
        var idx =$(this).index();

        $('.to-score-stars .mdi-star').removeClass('scored');
        for(var i = 0; i <= idx; i++) {
            $('.to-score-stars .mdi-star').eq(i).addClass('scored');
        }
    })
});

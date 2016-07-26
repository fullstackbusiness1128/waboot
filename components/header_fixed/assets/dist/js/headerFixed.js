jQuery(document).ready(function($){


    var fixedHeader = $(wbHeaderFixed.fixed_class),
        mainWrapper = $('#main-wrapper'),
        headerHeight = fixedHeader.outerHeight(),
        mode = wbHeaderFixed.modality,
        breakpoint = wbHeaderFixed.breakpoint;

    var styleBefore = {
        'padding-top' : wbHeaderFixed.padding_before+'px',
        'padding-bottom': wbHeaderFixed.padding_before+'px',
        'background-color': wbHeaderFixed.color_before
    };

    var styleAfter = {
        'padding-top' : wbHeaderFixed.padding_after+'px',
        'padding-bottom': wbHeaderFixed.padding_after+'px',
        'background-color': wbHeaderFixed.color_after
    };


    fixedHeader.css(styleBefore).addClass('fixed-header-component');
    mainWrapper.css('padding-top', fixedHeader.outerHeight() );

    $( window ).scroll(function() {
        console.log($(document).scrollTop());
    });

    switch(mode) {

        case 'beginning':
            fixedHeader.addClass('transition-all');
            enterFixed();
            // now we can change the css checking if the position of the window is before or after the breakpoint specified by the user
            $( window ).scroll(function() {
                enterFixed();
            });

            break;


        case 'scrollUp':

            // da dove partiamo
            var initialScroll = $(document).scrollTop(),
                newHeaderHeight = fixedHeader.outerHeight(),
                sensitiveness = 15; // voglio filtrare per i movimenti decisi
            fixedHeader.addClass('transition-all');
            $( window ).scroll(function() {
                enterScrollUp(newHeaderHeight, sensitiveness);
            });

            break;


        case 'breakpoint':
            var newHeaderHeight = fixedHeader.outerHeight();
            fixedHeader.addClass('margin-top-animation');
            // then update the classes on window scroll
            $( window ).scroll(function() {
                enterAfter(newHeaderHeight);
            });

            break;
    }


    function enterScrollUp(headerHeight, sensitiveness) {
        var currentScroll = $(this).scrollTop(), // lo scroll attuale
            delta = currentScroll - initialScroll; // la differenza fra lo scroll attuale e il punto di partenza
        if (Math.abs(delta) > sensitiveness) {
            if (delta > 0) { // se il delta è maggiore di 0 stiamo andando giù
                // fixedHeader.removeClass('fixed-header-component');
                fixedHeader.css('margin-top', headerHeight * -1);
            } else if (delta < 0) { // altrimenti stiamo andando su
                fixedHeader.css('margin-top', 0).css(styleAfter);
            }
        }
        // anyhow just reset margin and class if we are at the top
        if (currentScroll < breakpoint && currentScroll <= newHeaderHeight) {
            fixedHeader.css({
                'top': scroll * -1 + 32,
                'margin-top': 0
            }).css(styleBefore);
        }
        initialScroll = currentScroll; // a ogni scroll dobbiamo aggiornare la posizione iniziale attualizzandola con la posizione corrente
    }


    function enterFixed() {
        var scroll = $(document).scrollTop();
        if (scroll > breakpoint) {
            fixedHeader.css(styleAfter);
        } else {
            fixedHeader.css(styleBefore);
        }
    }


    function enterAfter(newHeaderHeight) {
        var scroll = $(document).scrollTop();

        if (scroll < breakpoint && scroll <= newHeaderHeight) {
            fixedHeader.css({
                    'top': scroll*-1 + 32,
                    'margin-top': 0
            }).css(styleBefore);
        } else if (scroll < breakpoint && scroll > newHeaderHeight) {
            fixedHeader.css({
                'top': scroll*-1 + 32,
                'margin-top': newHeaderHeight*-1
            });
        } else {
            fixedHeader.css('margin-top', 0).css('top', '32px').css(styleAfter);
        }
    }
    //
});

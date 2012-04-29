(function($) {

    // My portfolio
    $.portfolio = {};

    // Global settings
    $.portfolio.settings = {
        header      : function() { return $('header.loadme') },
        loader      : function() { return $('#loader') },
        dragme_e    : function() { return $('<img src="/ui/img/dragme.png" id="dragme" alt="drag me" title="drag the handle to scroll" width="75" height="75" />') },
        dragme      : function() { return $('#dragme') },
        scrollwrap  : function() { return $('#scrollwrap') },
        scrollable  : function() { return $('#scrollable') },
        contents    : function() { return $('#scrollable > article') },
        handler     : function() { return $('#handler') },
        handle      : function() { return $('.handle') },
        slider      : function() { return $('.slider') }
    };

    // Available methods
    $.portfolio.methods = {

        // Content display (articles)
        displayContents: function(options) {

            // Local settings
            var settings = $.extend({
                delay   : 200,
                speed   : 200
            }, options);

            var d = 0;
            return $(this).each(function() {
                $(this).delay(d).fadeIn(settings.speed);
                d += settings.delay;
            });
        },

        // Slider factory (create, update, destroy actions)
        sliderFactory: function(action, options) {

            // Local settings
            var settings = $.extend({
                delay   : 2000,
                speed   : 1000
            }, options);

            // Available actions
            var actions = [ 'create', 'update', 'destroy' ];

            switch (action) {

                // Create action
                case actions[0]:

                    // Doesn't create the slider if the content width is not naturally scrolling
                    if ($.portfolio.settings.scrollable().width() <= $.portfolio.settings.scrollwrap().width()) {
                        return false;
                    }

                    // Always initialize the handle to the left
                    $.portfolio.settings.handle().css('left', 0);
                    // Adds the picture helper in the header
                    $.portfolio.settings.header().append($.portfolio.settings.dragme_e());
                    // Calculates the max value
                    $(this).attr('max', ($.portfolio.settings.scrollable().width() - ($.portfolio.settings.contents().margin().left * 2)) - $.portfolio.settings.scrollwrap().width());
                    // Calculates the step value
                    //$(this).attr('step', Math.abs($(this).attr('max') / 20));

                    // If the handles already exists and its CSS position is further than it should, sets it a the max value
                    if ($.portfolio.settings.handle().css('left') >= $(this).attr('max')) {
                        $.portfolio.settings.handle().css('left', $(this).attr('max'));
                    }
                    // Creates the slider
                    $(this).rangeinput({
                        speed: 0,
                        onSlide: function(ev, step) {
                            $.portfolio.settings.scrollable().css({left: -step});

                            // Fires the lazyloading
                            // $(window).trigger('scroll');
                        },
                        change: function(e, i) {
                            $.portfolio.settings.scrollable().animate({left: -i}, 'slow', 'easeInOutCirc');
                        }
                    });
                    // Fancy display
                    $.portfolio.settings.slider().delay(settings.delay).fadeIn(settings.speed);
                    $.portfolio.settings.dragme().delay(settings.delay).fadeIn(settings.speed);
                    break;

                // Update action
                case actions[1]:

                    // Destroys and creates the slider to update (looking forward for a ligher solution)
                    $(this).portfolio('sliderFactory', 'destroy');
                    $(this).portfolio('sliderFactory', 'create', { delay: 200 });
                    break;

                // Destroy action
                case actions[2]:
                    
                    // Remove the slider datas
                    $(this).removeData('rangeinput');
                    $.portfolio.settings.dragme().remove();
                    $.portfolio.settings.slider().remove();
                    break;

                // Warns if bad argument is provided
                default:
                    console.warn('portfolio.sliderFactory(): No valid action provided!');
                    break;
            }  
        },

        // Mouse handling factory (create, destroy actions)
        mouseGestureFactory: function(action) {

            // Available actions
            var actions = [ 'create', 'destroy' ];

            // Doesn't build the mouse handling as long as the slider is not built yet
            if (action == actions[0] && !$.fn.portfolio('isBuilt')) {
                return;
            }

            // Grabs the slider handler
            var handler = $.portfolio.settings.handler().data('rangeinput');

            switch(action) {

                // Create action
                case actions[0]:

                    // Creates the mouse handling
                    $(this).mousewheel(function(e, delta) {

                        // Gets the slider handler current value
                        var value = handler.getValue();
                        if (0 < delta) {
                            // Moves the slider following the mouse event
                            handler.setValue(value - (Math.abs(delta) * ($.portfolio.settings.contents().length * 10)));
                        } else if (0 > delta) {
                            // Moves the slider following the mouse event
                            handler.setValue(value + (Math.abs(delta) * ($.portfolio.settings.contents().length * 10)));
                        }
                        e.preventDefault();
                    });
                    break;

                // Destroy action
                case actions[1]:

                    // Unsets the mouse handling
                    $(this).unmousewheel();
                    break;

                // Warns if bad argument is provided
                default:
                    console.warn('portfolio.mouseGestureFactory(): No valid action provided!');
                    break;
            }
        },

        // Keyboard handling factory (create, destroy actions)
        kbFactory: function(action) {

            // Available actions
            var actions = [ 'create', 'destroy' ];

            // Doesn't build the keyboard handling as long as the slider is not built yet
            if (action == actions[0] && !$.fn.portfolio('isBuilt')) {
                return;
            }
            
            // Grabs the slider handler
            var handler = $.portfolio.settings.handler().data('rangeinput');

            switch(action) {

                // Create action
                case actions[0]:

                    // Creates the keyboard handling
                    $(this).keydown(function(e) {

                        // Updates the slider handler following the pressed key
                        if (39 == e.keyCode || e.keyCode == 40) {
                            handler.stepUp($.portfolio.settings.contents().length);
                        } else if (37 == e.keyCode || e.keyCode == 38) {
                            handler.stepDown($.portfolio.settings.contents().length);
                        }
                    });
                    break;

                // Destroy action
                case actions[1]:

                    // Unsets the keyboard handling
                    $(this).off('keydown');
                    break;

                // Warns if bad argument is provided
                default:
                    console.warn('portfolio.kbFactory(): No valid action provided!');
                    break;
            }
        },

        // Checks if the slider is built already
        isBuilt: function() {
            return !($.portfolio.settings.handler().data('rangeinput') == undefined);
        }
    };

    // Portfolio method manager
    $.fn.portfolio = function(method) {
        if ($.portfolio.methods[method]) {
            return $.portfolio.methods[method].apply($(this), Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply($(this), arguments);
        } else {
            $.error('Method ' +  method + ' does not exist on jQuery.portfolio');
        }
    };

    // View helper
    $.fn.portfolio.isViewportBuildable = function() {
        return !(($(window).width() + $.getScrollbarWidth()) <= 560 || $(window).height() <= 450);
    };

})(jQuery);

$(window).load(function() {
    if (!$.fn.portfolio.isViewportBuildable()) {
        $.portfolio.settings.loader().hide();
        return;
    }

    $.portfolio.settings.loader().fadeOut('slow');
    $.portfolio.settings.header().delay(0).animate({left: '4em'}, 1000, 'easeOutCirc');
    $.portfolio.settings.handle().css('left', 0);
});

$(document).ready(function() {
    $('header nav').delay(2000).slideDown('slow');
    $('article').delay(2000).portfolio('displayContents');

    /*
    $('img.lazy').show().lazyload({
        effect: 'fadeIn',
        skip_invisible: false
    });
    */

    if ($.fn.portfolio.isViewportBuildable()) {
        $.portfolio.settings.scrollable().width(($.portfolio.settings.contents().outerWidth(true) * $.portfolio.settings.contents().length) + $.getScrollbarWidth());
        $.portfolio.settings.contents().css('float', 'left');
        $.portfolio.settings.contents().css('height', $.portfolio.settings.header().height());
        $.portfolio.settings.handler().delay(2000).portfolio('sliderFactory', 'create');
        $(document).portfolio('mouseGestureFactory', 'create');
        $(document).portfolio('kbFactory', 'create');
    }
});

$(window).resize(function() {
    if ($.fn.portfolio.isViewportBuildable()) {
        $.portfolio.settings.header().css('left', '4em');
        $.portfolio.settings.scrollable().width(($.portfolio.settings.contents().outerWidth(true) * $.portfolio.settings.contents().length) + $.getScrollbarWidth());
        $.portfolio.settings.contents().css('float', 'left');
        $.portfolio.settings.contents().css('height', $.portfolio.settings.header().height());
        $('header nav').show();
        $('article').show();
        $.portfolio.settings.loader().hide();
        $.portfolio.settings.handler().clearQueue().portfolio('sliderFactory', 'update');
        $(document).clearQueue().portfolio('mouseGestureFactory', 'create');
        $(document).clearQueue().portfolio('kbFactory', 'create');
    } else {
        $.portfolio.settings.header().removeAttr('style');
        $('article').css('float', 'none');
        $('article').css('height', 'auto');
        $.portfolio.settings.scrollable().removeAttr('style');
        $.portfolio.settings.handler().clearQueue().portfolio('sliderFactory', 'destroy');
        $(document).clearQueue().portfolio('mouseGestureFactory', 'destroy');
        $(document).clearQueue().portfolio('kbFactory', 'destroy');
    }
});

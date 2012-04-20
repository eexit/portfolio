(function($) {

    $.portfolio = {};

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

    $.portfolio.methods = {
        displayContents: function(options) {
            var settings = $.extend({
                delay   : 200,
                speed   : 'fast'
            }, options);

            var d = 0;
            return $(this).each(function() {
                $(this).delay(d).fadeIn(settings.speed);
                d += settings.delay;
            });
        },
        sliderFactory: function(action, options) {
            var settings = $.extend({
                delay   : 2000,
                speed   : 1000
            }, options);

            var actions = [ 'create', 'update', 'destroy' ];
            switch (action) {
                // Create action
                case actions[0]:
                    if ($.portfolio.settings.scrollable().width() <= $.portfolio.settings.scrollwrap().width()) {
                        return false;
                    }

                    $.portfolio.settings.handle().css('left', 0);
                    $.portfolio.settings.header().append($.portfolio.settings.dragme_e());
                    $(this).attr('max', ($.portfolio.settings.scrollable().width() - ($.portfolio.settings.contents().margin().left * 2)) - $.portfolio.settings.scrollwrap().width());
                    $(this).attr('step', Math.abs($(this).attr('max') / 20));
                    if ($.portfolio.settings.handle().css('left') >= $(this).attr('max')) {
                        $.portfolio.settings.handle().css('left', $(this).attr('max'));
                    }
                    $(this).rangeinput({
                        speed: 0,
                        onSlide: function(ev, step) {
                            $.portfolio.settings.scrollable().css({left: -step});
                        },
                        change: function(e, i) {
                            $.portfolio.settings.scrollable().animate({left: -i}, 'slow', 'easeInOutCirc');
                        }
                    });
                    $.portfolio.settings.slider().delay(settings.delay).fadeIn(settings.speed);
                    $.portfolio.settings.dragme().delay(settings.delay).fadeIn(settings.speed);
                    break;

                // Update action
                case actions[1]:
                    $(this).portfolio('sliderFactory', 'destroy');
                    $(this).portfolio('sliderFactory', 'create', { delay: 200 });
                    break;

                // Destroy action
                case actions[2]:
                    $(this).removeData('rangeinput');
                    $.portfolio.settings.dragme().remove();
                    $.portfolio.settings.slider().remove();
                    break;
                default:
                    console.warn('portfolio.sliderFactory(): No valid action provided!');
                    break;
            }

            
        },

        mouseGestureFactory: function(action) {
            var actions = [ 'create', 'destroy' ];

            if (action == actions[0] && !$.fn.portfolio('isBuilt')) {
                return;
            }

            var handler = $.portfolio.settings.handler().data('rangeinput');

            switch(action) {
                case actions[0]:
                    $(this).mousewheel(function(e, delta) {
                        var value = handler.getValue();
                        if (0 < delta) {
                            handler.setValue(value - (Math.abs(delta) * 10));
                        } else if (0 > delta) {
                            handler.setValue(value + (Math.abs(delta) * 10));
                        }
                        e.preventDefault();
                    });
                    break;
                case actions[1]:
                    $(this).unmousewheel();
                    break;
                default:
                    console.warn('portfolio.mouseGestureFactory(): No valid action provided!');
                    break;
            }
        },

        kbFactory: function(action) {
            var actions = [ 'create', 'destroy' ];

            if (action == actions[0] && !$.fn.portfolio('isBuilt')) {
                return;
            }
            
            var handler = $.portfolio.settings.handler().data('rangeinput');

            switch(action) {
                case actions[0]:
                    $(this).keydown(function(e) {
                        if (39 == e.keyCode || e.keyCode == 40) {
                            handler.stepUp($.portfolio.settings.contents().length);
                        } else if (37 == e.keyCode || e.keyCode == 38) {
                            handler.stepDown($.portfolio.settings.contents().length);
                        }
                    });
                    break;
                case actions[1]:
                    $(this).off('keydown');
                    break;
                default:
                    console.warn('portfolio.kbFactory(): No valid action provided!');
                    break;
            }
        },

        isBuilt: function() {
            return !($.portfolio.settings.handler().data('rangeinput') == undefined);
        }
    };

    $.fn.portfolio = function(method) {
        if ($.portfolio.methods[method]) {
            return $.portfolio.methods[method].apply($(this), Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply($(this), arguments);
        } else {
            $.error('Method ' +  method + ' does not exist on jQuery.portfolio');
        }
    };

    $.fn.portfolio.isViewportBuildable = function() {
        return !($(window).width() < 560 || $(window).height() < 450);
    };

    $.fn.portfolio.initLayout = function() {
        if ($.fn.portfolio.isViewportBuildable()) {
            $.portfolio.settings.scrollable().width(($.portfolio.settings.contents().outerWidth(true) * $.portfolio.settings.contents().length) + $.getScrollbarWidth());
            $.portfolio.settings.contents().css('float', 'left');
            $.portfolio.settings.contents().css('height', $.portfolio.settings.header().height());
        } else {
            $.portfolio.settings.header().removeAttr('style');
        }
    };

    $.fn.portfolio.clearLayout = function() {
        $.portfolio.settings.header().removeAttr('style');
        $('article').css('float', 'none');
        $('article').css('height', 'auto');
        $.portfolio.settings.scrollable().removeAttr('style');
    }

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
    $.fn.portfolio.initLayout();

    if (!$.fn.portfolio.isViewportBuildable()) {
        $('header nav').show();
        $('article').show();
        return;
    }

    $('header nav').delay(2000).slideDown('slow');
    $('article').delay(2000).portfolio('displayContents');
    $.portfolio.settings.handler().delay(2000).portfolio('sliderFactory', 'create');
    $(document).portfolio('mouseGestureFactory', 'create');
    $(document).portfolio('kbFactory', 'create');
});

$(window).resize(function() {
    console.log($(window).width() + "x" + $(window).height());
    if ($.fn.portfolio.isViewportBuildable()) {
        $.portfolio.settings.header().css('left', '4em');
        $.fn.portfolio.initLayout();
        $('header nav').show();
        $('article').show();
        $.portfolio.settings.loader().hide();
        $.portfolio.settings.handler().clearQueue().portfolio('sliderFactory', 'update');
        $(document).clearQueue().portfolio('mouseGestureFactory', 'create');
        $(document).clearQueue().portfolio('kbFactory', 'create');
    } else {
        $.fn.portfolio.clearLayout();
        $.portfolio.settings.handler().clearQueue().portfolio('sliderFactory', 'destroy');
        $(document).clearQueue().portfolio('mouseGestureFactory', 'destroy');
        $(document).clearQueue().portfolio('kbFactory', 'destroy');
    }
});
$('.twipsies.well a').each(function () {
    var type = $(this).attr('data-title'), $anchor = $(this) , $twipsy = $('.twipsy.' + type), twipsy = {
        width: $twipsy.width() + 10,
        height: $twipsy.height() + 10
    }, anchor = {
        position: $anchor.position(),
        width: $anchor.width(),
        height: $anchor.height()
    }, offset = {
        above: {
            top: anchor.position.top - twipsy.height,
            left: anchor.position.left + (anchor.width/2) - (twipsy.width/2)
        },
        below: {
            top: anchor.position.top + anchor.height,
            left: anchor.position.left + (anchor.width/2) - (twipsy.width/2)
        },
        left: {
            top: anchor.position.top + (anchor.height/2) - (twipsy.height/2),
            left: anchor.position.left - twipsy.width - 5
        },
        right: {
            top: anchor.position.top + (anchor.height/2) - (twipsy.height/2),
            left: anchor.position.left + anchor.width + 5
        }
    }
    $twipsy.css(offset[type]).hide();
});

$('.twipsies.well a[href="#"]').hover(function() {
    $('.twipsy.' + $(this).attr('data-title')).fadeIn(200);
}, function() {
    $('.twipsy.' + $(this).attr('data-title')).fadeOut(200);
});
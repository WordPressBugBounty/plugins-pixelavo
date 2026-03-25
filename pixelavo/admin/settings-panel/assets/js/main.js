(function ($) {
    $('#toplevel_page_pixelavo .wp-submenu a').each(function() {
        if($(this)[0].hash === window.location.hash) {
            $(this).parent().addClass('current').siblings().removeClass('current');
        } else {
            return;
        }
    })
    $('#toplevel_page_pixelavo .wp-submenu a').on('click', function(e) {
        $(this).parent().addClass('current').siblings().removeClass('current');
    })
    $('.pixelavo-navigation-menu a').on('click', function(e) {
        $('#toplevel_page_pixelavo .wp-submenu a').each(function() {
            if($(this)[0].hash === e.currentTarget.hash) {
                $(this).parent().addClass('current').siblings().removeClass('current');
            } else {
                return;
            }
        })
    })

})(jQuery); 
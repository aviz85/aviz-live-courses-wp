jQuery(document).ready(function($) {
    $('#aviz-mark-complete').on('click', function() {
        var button = $(this);
        var contentId = button.data('content-id');
        var nonce = button.data('nonce');
        var isCompleted = button.hasClass('aviz-completed');

        $.ajax({
            url: aviz_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'aviz_toggle_content_complete',
                content_id: contentId,
                is_completed: isCompleted ? 0 : 1,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    if (isCompleted) {
                        button.removeClass('aviz-completed');
                        button.text('סמן כהושלם');
                        $('.aviz-next-content').addClass('aviz-disabled');
                    } else {
                        button.addClass('aviz-completed');
                        button.text('הושלם ✓');
                        $('.aviz-next-content').removeClass('aviz-disabled');
                    }
                }
            }
        });
    });

    $('.aviz-next-content.aviz-disabled').on('click', function(e) {
        e.preventDefault();
        alert('יש לסמן את התוכן הנוכחי כהושלם לפני המעבר לתוכן הבא.');
    });

    // Smooth scroll to anchor
    if (window.location.hash) {
        var target = $(window.location.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100 // Adjust the offset as needed
            }, 1000);
        }
    }
});
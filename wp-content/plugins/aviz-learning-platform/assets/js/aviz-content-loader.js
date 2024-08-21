jQuery(document).ready(function($) {
    $('.aviz-course-content').each(function() {
        var $content = $(this);
        var courseId = $content.data('course-id');

        $.ajax({
            url: aviz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aviz_check_course_access',
                course_id: courseId,
                nonce: aviz_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $content.addClass('authorized').show();
                } else {
                    $content.before('<p>You do not have access to this content.</p>');
                }
            }
        });
    });
});

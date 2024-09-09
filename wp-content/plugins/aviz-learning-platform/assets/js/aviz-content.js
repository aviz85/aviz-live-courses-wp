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
                    if (!isCompleted) {
                        // Content is now completed
                        button.addClass('aviz-completed');
                        button.text('הושלם ✓');
                        $('.aviz-next-content').removeClass('aviz-disabled');
                    } else {
                        // Content is now uncompleted
                        button.removeClass('aviz-completed');
                        button.text('סמן כהושלם');
                        $('.aviz-next-content').addClass('aviz-disabled');
                    }
                } else {
                    console.error('Error updating content status:', response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });
    });

    // Remove the click event from disabled next content button
    $(document).on('click', '.aviz-next-content.aviz-disabled', function(e) {
        e.preventDefault();
        alert('יש לסמן את התוכן הנוכחי כהושלם לפני המעבר לתוכן הבא.');
    });
});

function updateProgressBar(progress) {
    // Implement this function to update your progress bar
    // For example:
    // $('.progress-bar').css('width', progress + '%');
}
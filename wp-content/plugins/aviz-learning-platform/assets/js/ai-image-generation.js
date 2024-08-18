jQuery(document).ready(function($) {
    if (typeof aviz_ai_image === 'undefined') {
        console.error('aviz_ai_image is not defined');
        return;
    }

    $('#aviz-generate-ai-image').on('click', function() {
        var $button = $(this);
        var $result = $('#aviz-ai-image-result');
        var postTitle = $('#title').val(); // Get the title from the input field

        $button.prop('disabled', true).text('מייצר תמונה...');
        $result.html('');

        $.ajax({
            url: aviz_ai_image.ajax_url,
            type: 'POST',
            data: {
                action: 'aviz_generate_ai_image',
                nonce: aviz_ai_image.nonce,
                post_id: $('#post_ID').val(),
                post_title: postTitle // Send the title to the server
            },
            success: function(response) {
                if (response.success) {
                    $result.append('<p>' + response.data.message + '</p>');
                    // Save the post as a draft before reloading
                    wp.data.dispatch('core/editor').savePost().then(function() {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    });
                } else {
                    $result.html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function() {
                $result.html('<p class="error">אירעה שגיאה בתקשורת עם השרת. אנא נסה שוב.</p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('יצירת תמונה ב-AI');
            }
        });
    });

    function checkFeaturedImage(attachmentId, postId) {
        $.ajax({
            url: aviz_ai_image.ajax_url,
            type: 'POST',
            data: {
                action: 'aviz_check_featured_image',
                nonce: aviz_ai_image.nonce,
                attachment_id: attachmentId,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    $('#postimagediv').find('.inside').html(response.data.featured_image_html);
                    $('#aviz-ai-image-result').append('<p>התמונה הראשית עודכנה בהצלחה.</p>');
                } else {
                    setTimeout(function() {
                        checkFeaturedImage(attachmentId, postId);
                    }, 1000);
                }
            },
            error: function() {
                $('#aviz-ai-image-result').append('<p class="error">אירעה שגיאה בעדכון התמונה הראשית. אנא נסה שוב.</p>');
            }
        });
    }
});
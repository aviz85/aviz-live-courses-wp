<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_add_ai_image_metabox() {
    error_log('aviz_add_ai_image_metabox function called');
    add_meta_box(
        'aviz_ai_image_generation',
        'יצירת תמונה באמצעות AI',
        'aviz_ai_image_metabox_callback',
        'aviz_course',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_ai_image_metabox');

function aviz_ai_image_metabox_callback($post) {
    wp_nonce_field('aviz_ai_image_nonce', 'aviz_ai_image_nonce');
    ?>
    <button type="button" id="aviz-generate-ai-image" class="button">יצירת תמונה ב-AI</button>
    <div id="aviz-ai-image-result"></div>
    <?php
}

function aviz_debug_metabox_callback($post) {
    echo 'This is a debug metabox.';
}

function aviz_generate_ai_image() {
    error_log('aviz_generate_ai_image function called');
    check_ajax_referer('aviz_ai_image_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        error_log('User does not have permission to edit posts');
        wp_send_json_error('אין לך הרשאות מתאימות.');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_title = get_the_title($post_id);
    error_log('Generating image for post: ' . $post_title);

    // Generate image description using Llama 3
    $image_description = aviz_generate_image_description($post_title);
    error_log('Generated image description: ' . $image_description);

    // Generate image using Flux Schnell
    $image_url = aviz_generate_image($image_description);
    error_log('Generated image URL: ' . $image_url);

    if (!$image_url) {
        error_log('Failed to generate image');
        wp_send_json_error('אירעה שגיאה ביצירת התמונה. אנא נסה שוב.');
    }

    // Upload image to media library
    $upload = aviz_upload_image_to_media_library($image_url, $post_title);

    if (is_wp_error($upload)) {
        error_log('Failed to upload image to media library: ' . $upload->get_error_message());
        wp_send_json_error('אירעה שגיאה בהעלאת התמונה למדיה. ' . $upload->get_error_message());
    }

    // Set as featured image
    set_post_thumbnail($post_id, $upload['attachment_id']);
    error_log('Image set as featured image for post: ' . $post_id);

    // Save the post as a draft
    wp_update_post(array(
        'ID' => $post_id,
        'post_status' => 'draft'
    ));

    wp_send_json_success(array(
        'image_url' => $upload['url'],
        'message' => 'התמונה נוצרה והוגדרה בהצלחה כתמונה ראשית. הדף יתרענן כעת.',
        'attachment_id' => $upload['attachment_id'],
        'post_id' => $post_id
    ));
}
add_action('wp_ajax_aviz_generate_ai_image', 'aviz_generate_ai_image');

function aviz_generate_image_description($post_title) {
    error_log('aviz_generate_image_description function called for post: ' . $post_title);
    $api_token = defined('REPLICATE_API_TOKEN') ? REPLICATE_API_TOKEN : '';
    if (empty($api_token)) {
        error_log('REPLICATE_API_TOKEN is not defined in wp-config.php');
        return false;
    }

    $prompt = "Create a concise image description for a course titled '$post_title'. The description should be suitable for an AI image generation model to create a square image representing the course. Focus on visual elements and avoid text in the image.";
    error_log('Prompt for image description: ' . $prompt);

    $response = wp_remote_post('https://api.replicate.com/v1/predictions', array(
        'headers' => array(
            'Authorization' => "Token $api_token",
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'version' => "2c1608e18606fad2812020dc541930f2d0495ce32eee50074220b87300bc16e1",
            'input' => array(
                'prompt' => $prompt,
                'max_tokens' => 100,
                'temperature' => 0.7,
                'top_p' => 0.9,
            ),
        )),
    ));

    if (is_wp_error($response)) {
        error_log('Error generating image description: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('Replicate API response for image description: ' . print_r($body, true));

    if (isset($body['urls']['get'])) {
        $result = aviz_wait_for_prediction_result($body['urls']['get'], $api_token);
        if ($result) {
            $description = is_array($result) ? implode(' ', $result) : $result;
            error_log('Generated image description: ' . $description);
            return $description;
        }
    }

    error_log('Failed to generate image description');
    return false;
}

function aviz_wait_for_prediction_result($url, $api_token, $max_attempts = 10, $delay = 5) {
    error_log('Waiting for prediction result from: ' . $url);
    for ($i = 0; $i < $max_attempts; $i++) {
        $response = wp_remote_get($url, array(
            'headers' => array('Authorization' => "Token $api_token"),
        ));

        if (is_wp_error($response)) {
            error_log('Error checking prediction status: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        error_log('Prediction status response: ' . print_r($body, true));

        if (isset($body['status']) && $body['status'] === 'succeeded') {
            return isset($body['output']) ? $body['output'] : false;
        }

        if (isset($body['status']) && $body['status'] === 'failed') {
            error_log('Prediction failed: ' . print_r($body, true));
            return false;
        }

        sleep($delay);
    }

    error_log('Prediction timed out after ' . ($max_attempts * $delay) . ' seconds');
    return false;
}

function aviz_generate_image($description) {
    error_log('aviz_generate_image function called with description: ' . $description);
    if (empty($description)) {
        error_log('Empty description provided to aviz_generate_image');
        return false;
    }

    $api_token = defined('REPLICATE_API_TOKEN') ? REPLICATE_API_TOKEN : '';
    if (empty($api_token)) {
        error_log('REPLICATE_API_TOKEN is not defined in wp-config.php');
        return false;
    }

    $response = wp_remote_post('https://api.replicate.com/v1/models/black-forest-labs/flux-schnell/predictions', array(
        'headers' => array(
            'Authorization' => "Token $api_token",
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'input' => array(
                'prompt' => $description,
                'output_quality' => 90,
            ),
        )),
    ));

    if (is_wp_error($response)) {
        error_log('Error generating image: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('Replicate API response for image generation: ' . print_r($body, true));

    if (isset($body['urls']['get'])) {
        $result = aviz_wait_for_prediction_result($body['urls']['get'], $api_token);
        if ($result && is_array($result) && !empty($result[0])) {
            $image_url = $result[0];
            error_log('Generated image URL: ' . $image_url);
            return $image_url;
        }
    }

    error_log('Failed to generate image');
    return false;
}

function aviz_upload_image_to_media_library($image_url, $title) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $tmp = download_url($image_url);
    if (is_wp_error($tmp)) {
        return $tmp;
    }

    $file_array = array(
        'name' => basename($image_url),
        'tmp_name' => $tmp
    );

    $id = media_handle_sideload($file_array, 0, $title);

    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return $id;
    }

    return array(
        'attachment_id' => $id,
        'url' => wp_get_attachment_url($id),
    );
}

function aviz_check_featured_image() {
    check_ajax_referer('aviz_ai_image_nonce', 'nonce');

    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (get_post_thumbnail_id($post_id) == $attachment_id) {
        ob_start();
        _wp_post_thumbnail_html($attachment_id, $post_id);
        $featured_image_html = ob_get_clean();

        wp_send_json_success(array(
            'featured_image_html' => $featured_image_html
        ));
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_aviz_check_featured_image', 'aviz_check_featured_image');

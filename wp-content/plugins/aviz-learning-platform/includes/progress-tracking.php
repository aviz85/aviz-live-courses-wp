<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('aviz_get_user_progress')) {
    function aviz_get_user_progress($user_id, $course_id) {
        $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
        $viewed_content = is_array($viewed_content) ? $viewed_content : array();

        $course_contents = aviz_get_course_contents($course_id);
        $total_content = count($course_contents);
        
        if ($total_content === 0) {
            return array(
                'viewed' => 0,
                'total' => 0,
                'percentage' => 0
            );
        }

        $viewed_course_content = array_intersect(wp_list_pluck($course_contents, 'ID'), $viewed_content);
        $viewed_count = count($viewed_course_content);

        $percentage = round(($viewed_count / $total_content) * 100);

        return array(
            'viewed' => $viewed_count,
            'total' => $total_content,
            'percentage' => $percentage
        );
    }
}

if (!function_exists('aviz_get_course_contents')) {
    function aviz_get_course_contents($course_id) {
        $chapters = get_posts(array(
            'post_type' => 'aviz_chapter',
            'meta_query' => array(
                array(
                    'key' => '_aviz_associated_course',
                    'value' => $course_id,
                ),
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_aviz_chapter_order',
            'order' => 'ASC',
            'numberposts' => -1
        ));

        $contents = array();
        foreach ($chapters as $chapter) {
            $chapter_contents = get_posts(array(
                'post_type' => 'aviz_content',
                'meta_query' => array(
                    array(
                        'key' => '_aviz_associated_chapter',
                        'value' => $chapter->ID,
                    ),
                ),
                'orderby' => 'meta_value_num',
                'meta_key' => '_aviz_content_order',
                'order' => 'ASC',
                'numberposts' => -1
            ));
            $contents = array_merge($contents, $chapter_contents);
        }

        return $contents;
    }
}

function aviz_track_content_view($post_id) {
    // Remove the automatic tracking logic
    // This function can remain empty or be removed entirely
}

// Remove the automatic tracking action
remove_action('wp_head', function() {
    if (is_single() && get_post_type() === 'aviz_content') {
        aviz_track_content_view(get_the_ID());
    }
});

// Add the new tracking function to the content
add_filter('the_content', function($content) {
    ob_start();
    aviz_track_content_view(get_the_ID());
    $button = ob_get_clean();
    return $content . $button;
});

// Add an AJAX handler for marking content as viewed
add_action('wp_ajax_aviz_mark_content_completed', 'aviz_mark_content_completed_ajax');
function aviz_mark_content_completed_ajax() {
    check_ajax_referer('aviz_content_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $user_id = get_current_user_id();

    if ($post_id && $user_id) {
        $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
        if (!is_array($viewed_content)) $viewed_content = array();

        if (!in_array($post_id, $viewed_content)) {
            $viewed_content[] = $post_id;
            update_user_meta($user_id, 'aviz_viewed_content', $viewed_content);
            wp_send_json_success(array('message' => 'התוכן סומן כהושלם'));
        } else {
            wp_send_json_error(array('message' => 'התוכן כבר סומן כהושלם'));
        }
    } else {
        wp_send_json_error(array('message' => 'שגיאה בעיבוד הבקשה'));
    }
}

// Enqueue the JavaScript for handling the button click
function aviz_enqueue_content_scripts() {
    if (is_single() && get_post_type() === 'aviz_content') {
        wp_enqueue_script('aviz-content-completion', plugin_dir_url(__FILE__) . '../assets/js/content-completion.js', array('jquery'), '1.0', true);
        wp_localize_script('aviz-content-completion', 'aviz_content', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aviz_content_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_content_scripts');
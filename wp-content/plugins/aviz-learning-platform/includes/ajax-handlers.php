<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'course-functions.php';

function aviz_update_course_progress() {
    check_ajax_referer('aviz_content_nonce', 'nonce');

    $content_id = intval($_POST['content_id']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }

    $chapter_id = get_post_meta($content_id, '_aviz_associated_chapter', true);
    $course_id = get_post_meta($chapter_id, '_aviz_associated_course', true);

    $all_contents = aviz_get_course_contents($course_id);
    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    
    if (!is_array($viewed_content)) {
        $viewed_content = array();
    }

    $total_contents = count($all_contents);
    $viewed_contents = count(array_intersect(array_column($all_contents, 'ID'), $viewed_content));

    $progress = ($viewed_contents / $total_contents) * 100;

    update_user_meta($user_id, "aviz_course_{$course_id}_progress", $progress);

    wp_send_json_success(array('progress' => $progress));
}
add_action('wp_ajax_aviz_update_course_progress', 'aviz_update_course_progress');

function aviz_toggle_content_complete() {
    check_ajax_referer('aviz_content_nonce', 'nonce');

    $content_id = intval($_POST['content_id']);
    $is_completed = intval($_POST['is_completed']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }

    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    
    if (!is_array($viewed_content)) {
        $viewed_content = array();
    }

    if ($is_completed) {
        if (!in_array($content_id, $viewed_content)) {
            $viewed_content[] = $content_id;
        }
    } else {
        $viewed_content = array_diff($viewed_content, array($content_id));
    }

    update_user_meta($user_id, 'aviz_viewed_content', $viewed_content);

    wp_send_json_success();
}
add_action('wp_ajax_aviz_toggle_content_complete', 'aviz_toggle_content_complete');
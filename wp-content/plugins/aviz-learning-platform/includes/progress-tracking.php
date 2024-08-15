<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_track_content_view($post_id) {
    if (!is_user_logged_in() || get_post_type($post_id) !== 'aviz_content') return;

    $user_id = get_current_user_id();
    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    if (!is_array($viewed_content)) $viewed_content = array();

    if (!in_array($post_id, $viewed_content)) {
        $viewed_content[] = $post_id;
        update_user_meta($user_id, 'aviz_viewed_content', $viewed_content);
    }
}
add_action('wp_head', function() {
    if (is_single() && get_post_type() === 'aviz_content') {
        aviz_track_content_view(get_the_ID());
    }
});

function aviz_get_user_progress($user_id, $course_id) {
    $course_content = get_posts(array(
        'post_type' => 'aviz_content',
        'meta_query' => array(
            array(
                'key' => 'aviz_course',
                'value' => $course_id,
            ),
        ),
        'numberposts' => -1,
    ));

    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    if (!is_array($viewed_content)) $viewed_content = array();

    $total_content = count($course_content);
    $viewed_count = 0;

    foreach ($course_content as $content) {
        if (in_array($content->ID, $viewed_content)) {
            $viewed_count++;
        }
    }

    return array(
        'total' => $total_content,
        'viewed' => $viewed_count,
        'percentage' => $total_content > 0 ? ($viewed_count / $total_content) * 100 : 0,
    );
}
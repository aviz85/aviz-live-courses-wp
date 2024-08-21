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
    $chapters = get_post_meta($course_id, 'chapters', true);
    if (!is_array($chapters)) {
        return array('viewed' => 0, 'total' => 0, 'percentage' => 0);
    }

    $total_contents = 0;
    $viewed_contents = 0;
    $viewed_content_ids = get_user_meta($user_id, 'aviz_viewed_content', true);
    if (!is_array($viewed_content_ids)) {
        $viewed_content_ids = array();
    }

    foreach ($chapters as $chapter) {
        if (isset($chapter['contents']) && is_array($chapter['contents'])) {
            foreach ($chapter['contents'] as $content) {
                $total_contents++;
                if (in_array($content['content'], $viewed_content_ids)) {
                    $viewed_contents++;
                }
            }
        }
    }

    $percentage = ($total_contents > 0) ? round(($viewed_contents / $total_contents) * 100) : 0;

    return array(
        'viewed' => $viewed_contents,
        'total' => $total_contents,
        'percentage' => $percentage
    );
}
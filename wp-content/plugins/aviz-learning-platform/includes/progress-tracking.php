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
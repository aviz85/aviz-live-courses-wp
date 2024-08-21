<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_course_content($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'aviz_course_content');
    $course_id = intval($atts['id']);

    // Check access using Aviz_Access_Control
    $access = Aviz_Access_Control::user_has_access($course_id);
    if (!$access['has_access']) {
        return '<p>' . esc_html($access['message']) . '</p>';
    }

    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');
    $user_courses = $is_admin ? array($course_id) : get_user_meta($user_id, 'aviz_course_access', true);
    
    if (!is_array($user_courses) || (!in_array($course_id, $user_courses) && !$is_admin)) {
        return '<p>אין לך גישה לקורס זה.</p>';
    }

    $chapters = get_posts(array(
        'post_type' => 'aviz_chapter',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_aviz_associated_course',
                'value' => $course_id,
            ),
        ),
        'meta_key' => '_aviz_chapter_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    ));

    $output = '<div class="aviz-course-content">';
    $output .= '<div class="aviz-course-header">';
    $output .= get_the_post_thumbnail($course_id, 'full', array('class' => 'aviz-course-thumbnail'));
    $output .= '<h2 class="aviz-course-title">' . get_the_title($course_id) . '</h2>';
    $output .= '</div>';

    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    
    // וודא שהמערך קיים ולא ריק
    if (empty($viewed_content) || !is_array($viewed_content)) {
        $viewed_content = array();
    }

    $has_content = false;

    foreach ($chapters as $chapter) {
        $contents = get_posts(array(
            'post_type' => 'aviz_content',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aviz_associated_chapter',
                    'value' => $chapter->ID,
                ),
            ),
            'meta_key' => '_aviz_content_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        ));

        if (!empty($contents)) {
            $has_content = true;
            $output .= '<div id="chapter-' . $chapter->ID . '" class="aviz-chapter">';
            $output .= '<h2 class="aviz-chapter-title">' . esc_html($chapter->post_title) . '</h2>';
            $output .= '<ul class="aviz-content-list">';

            foreach ($contents as $content) {
                $content_type = get_post_meta($content->ID, '_aviz_content_type', true);
                $icon = aviz_get_content_type_icon($content_type);

                $is_viewed = in_array($content->ID, $viewed_content);
                $class = $is_viewed ? 'aviz-content-viewed' : '';

                $output .= '<li class="' . $class . '">';
                $output .= '<a href="' . get_permalink($content->ID) . '">' . $icon . ' <span class="aviz-content-title">' . esc_html($content->post_title) . '</span></a>';
                $output .= '</li>';
            }

            $output .= '</ul>';
            $output .= '</div>';
        }
    }

    if (!$has_content) {
        $output .= '<p>אין תוכן זמין בקורס זה כרגע.</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('aviz_course_content', 'aviz_course_content');

function aviz_get_content_type_icon($content_type) {
    switch ($content_type) {
        case 'lesson':
            return '<i class="fas fa-book aviz-content-icon"></i>';
        case 'quiz':
            return '<i class="fas fa-question-circle aviz-content-icon"></i>';
        case 'video':
            return '<i class="fas fa-video aviz-content-icon"></i>';
        default:
            return '<i class="fas fa-file aviz-content-icon"></i>';
    }
}
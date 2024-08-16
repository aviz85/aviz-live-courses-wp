<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_course_content($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'aviz_course_content');
    $course_id = intval($atts['id']);

    if (!$course_id || !is_user_logged_in()) {
        return '<p>אין גישה לתוכן זה.</p>';
    }

    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');
    $user_courses = $is_admin ? array($course_id) : get_user_meta($user_id, 'aviz_course_access', true);
    
    if (!is_array($user_courses) || (!in_array($course_id, $user_courses) && !$is_admin)) {
        return '<p>אין לך גישה לקורס זה.</p>';
    }

    $chapters = get_terms(array(
        'taxonomy' => 'aviz_chapter',
        'hide_empty' => false,
    ));

    $output = '<div class="aviz-course-content">';
    $output .= '<div class="aviz-course-header">';
    $output .= get_the_post_thumbnail($course_id, 'full', array('class' => 'aviz-course-thumbnail'));
    $output .= '<h2 class="aviz-course-title">' . get_the_title($course_id) . '</h2>';
    $output .= '</div>';

    foreach ($chapters as $chapter) {
        $chapter_content = get_posts(array(
            'post_type' => 'aviz_content',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_aviz_associated_course',
                    'value' => $course_id,
                ),
                array(
                    'key' => '_aviz_associated_chapter',
                    'value' => $chapter->term_id,
                ),
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'numberposts' => -1,
        ));

        if (!empty($chapter_content)) {
            $output .= '<div class="aviz-chapter">';
            $output .= '<h3 class="aviz-chapter-title">' . $chapter->name . '</h3>';
            $output .= '<ul class="aviz-content-list">';

            $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
            if (!is_array($viewed_content)) $viewed_content = array();

            foreach ($chapter_content as $content) {
                $content_type = get_post_meta($content->ID, '_aviz_content_type', true);
                $icon = aviz_get_content_type_icon($content_type);

                $is_viewed = in_array($content->ID, $viewed_content) || $is_admin;
                $class = $is_viewed ? 'aviz-content-viewed' : 'aviz-content-not-viewed';

                $output .= '<li class="' . $class . '">';
                $output .= '<a href="' . get_permalink($content->ID) . '">' . $icon . ' ' . $content->post_title . '</a>';
                if ($is_viewed) {
                    $output .= '<span class="aviz-viewed-indicator">✓</span>';
                }
                $output .= '</li>';
            }

            $output .= '</ul>';
            $output .= '</div>';
        }
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('aviz_course_content', 'aviz_course_content');

function aviz_get_content_type_icon($content_type) {
    switch ($content_type) {
        case 'lesson':
            return '<i class="fas fa-book"></i>';
        case 'quiz':
            return '<i class="fas fa-question-circle"></i>';
        case 'video':
            return '<i class="fas fa-video"></i>';
        default:
            return '<i class="fas fa-file"></i>';
    }
}
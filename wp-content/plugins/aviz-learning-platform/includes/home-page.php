<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_home_page_content() {
    if (!is_user_logged_in()) {
        return '<p>עליך להתחבר כדי לצפות בתוכן זה.</p>';
    }

    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');
    $user_courses = $is_admin ? get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1)) : get_user_meta($user_id, 'aviz_course_access', true);
    
    if (!is_array($user_courses) && !$is_admin) $user_courses = array();

    $output = '<div class="aviz-home-page">';
    $output .= '<h1>ברוכים הבאים לפלטפורמת הלמידה של אביץ</h1>';
    $output .= '<p class="aviz-intro">כאן תוכלו למצוא את כל הקורסים שלכם ולעקוב אחר ההתקדמות שלכם.</p>';

    if (empty($user_courses)) {
        $output .= '<p>אין קורסים זמינים כרגע. אנא פנה למנהל המערכת.</p>';
    } else {
        $output .= '<div class="aviz-course-grid">';
        foreach ($user_courses as $course) {
            $course_id = $is_admin ? $course->ID : $course;
            $course_title = get_the_title($course_id);
            $progress = aviz_get_user_progress($user_id, $course_id);
            $thumbnail = get_the_post_thumbnail($course_id, 'medium');
            
            $output .= '<div class="aviz-home-course-card">';
            $output .= '<h3>' . $course_title . '</h3>';
            if ($thumbnail) {
                $output .= '<div class="aviz-home-course-thumbnail">' . $thumbnail . '</div>';
            }
            $output .= '<div class="aviz-home-progress-bar"><div class="aviz-home-progress" style="width: ' . $progress['percentage'] . '%;"></div></div>';
            $output .= '<span class="aviz-home-progress-text">' . $progress['viewed'] . ' / ' . $progress['total'] . ' הושלמו</span>';
            $output .= '<a href="' . get_permalink($course_id) . '" class="aviz-home-course-button">המשך ללמוד</a>';
            $output .= '</div>';
        }
        $output .= '</div>'; // סגירת div של aviz-course-grid
    }
    
    $output .= '</div>'; // סגירת div של aviz-home-page
    return $output;
}

function aviz_home_page_shortcode() {
    return aviz_home_page_content();
}
add_shortcode('aviz_home_page', 'aviz_home_page_shortcode');
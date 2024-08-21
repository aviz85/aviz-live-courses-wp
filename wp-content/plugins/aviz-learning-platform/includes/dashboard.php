<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'progress-tracking.php';

function aviz_enqueue_dashboard_styles() {
    wp_enqueue_style('aviz-dashboard-styles', plugin_dir_url(__FILE__) . '../assets/css/dashboard.css');
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_dashboard_styles');

function aviz_dashboard_content() {
    if (!is_user_logged_in()) {
        return '<p>עליך להתחבר כדי לצפות בלוח הבקרה שלך.</p>';
    }

    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');
    $user_courses = $is_admin ? get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1)) : get_user_meta($user_id, 'aviz_course_access', true);
    
    if (!is_array($user_courses) && !$is_admin) $user_courses = array();

    $output = '<div class="aviz-dashboard">';
    $output .= '<h2>ברוכים הבאים לפלטפורמת הלמידה של אביץ</h2>';
    $output .= '<h3>הקורסים שלך:</h3>';

    if (empty($user_courses)) {
        $output .= '<p>אין לך גישה לקורסים כרגע. אנא פנה למנהל המערכת.</p>';
    } else {
        $output .= '<ul class="aviz-course-list">';
        foreach ($user_courses as $course) {
            $course_id = $is_admin ? $course->ID : $course;
            $course_title = get_the_title($course_id);
            $progress = aviz_get_user_progress($user_id, $course_id);
            
            $output .= '<li>';
            $output .= '<h4>' . $course_title . '</h4>';
            $output .= '<div class="aviz-progress-bar"><div class="aviz-progress" style="width: ' . $progress['percentage'] . '%;"></div></div>';
            $output .= '<span class="aviz-progress-text">' . $progress['viewed'] . ' / ' . $progress['total'] . ' הושלמו</span>';
            $output .= '<a href="' . get_permalink($course_id) . '" class="aviz-course-button">המשך ללמוד</a>';
            $output .= '</li>';
        }
        $output .= '</ul>';
    }

    $output .= '</div>';
    return $output;
}

function aviz_dashboard_shortcode() {
    return aviz_dashboard_content();
}
add_shortcode('aviz_dashboard', 'aviz_dashboard_shortcode');
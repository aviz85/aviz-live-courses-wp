<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_enqueue_styles() {
    wp_enqueue_style('aviz-learning-platform', plugin_dir_url(__FILE__) . '../assets/css/style.css');
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_styles');

function aviz_main_dashboard() {
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

    if (empty($user_courses) && !$is_admin) {
        $output .= '<p>אין לך גישה לקורסים כרגע.</p>';
    } else {
        $output .= '<ul class="aviz-course-list">';
        foreach ($user_courses as $course) {
            $course_id = $is_admin ? $course->ID : $course;
            $course = get_post($course_id);
            if ($course) {
                $progress = $is_admin ? array('percentage' => 100, 'viewed' => 'כל התכנים', 'total' => 'כל התכנים') : aviz_get_user_progress($user_id, $course_id);
                $output .= '<li>';
                $output .= '<h4><a href="' . get_permalink($course_id) . '">' . $course->post_title . '</a></h4>';
                $output .= '<div class="aviz-progress-bar"><div class="aviz-progress" style="width: ' . $progress['percentage'] . '%;"></div></div>';
                $output .= '<span class="aviz-progress-text">' . $progress['viewed'] . ' / ' . $progress['total'] . ' הושלמו</span>';
                $output .= '<a href="' . get_permalink($course_id) . '" class="aviz-course-button">המשך ללמוד</a>';
                $output .= '</li>';
            }
        }
        $output .= '</ul>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('aviz_main_dashboard', 'aviz_main_dashboard');

function aviz_course_content($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'aviz_course_content');
    $course_id = intval($atts['id']);

    if (!$course_id) {
        $course_id = get_the_ID();
    }

    if (!$course_id || !is_user_logged_in()) {
        return '<p>אין גישה לתוכן זה.</p>';
    }

    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');
    $user_courses = $is_admin ? array($course_id) : get_user_meta($user_id, 'aviz_course_access', true);
    
    if (!is_array($user_courses) || (!in_array($course_id, $user_courses) && !$is_admin)) {
        return '<p>אין לך גישה לקורס זה.</p>';
    }

    $course_content = get_posts(array(
        'post_type' => 'aviz_content',
        'meta_query' => array(
            array(
                'key' => '_aviz_associated_course',
                'value' => $course_id,
            ),
        ),
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'numberposts' => -1,
    ));

    $output = '<div class="aviz-course-content">';
    $output .= '<h2>' . get_the_title($course_id) . '</h2>';
    $output .= '<ul class="aviz-content-list">';

    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    if (!is_array($viewed_content)) $viewed_content = array();

    foreach ($course_content as $content) {
        $content_type = get_post_meta($content->ID, '_aviz_content_type', true);
        $icon = '';
        switch ($content_type) {
            case 'lesson':
                $icon = '📚';
                break;
            case 'quiz':
                $icon = '📝';
                break;
            case 'video':
                $icon = '🎥';
                break;
        }

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

    return $output;
}
add_shortcode('aviz_course_content', 'aviz_course_content');

function aviz_course_template($content) {
    if (is_singular('aviz_course')) {
        $course_id = get_the_ID();
        $content .= do_shortcode("[aviz_course_content id=\"$course_id\"]");
    }
    return $content;
}
add_filter('the_content', 'aviz_course_template');

function aviz_home_page() {
    $output = '<div class="aviz-home-page">';
    $output .= '<h1>ברוכים הבאים לפלטפורמת הלמידה של אביץ</h1>';
    $output .= '<p class="aviz-intro">כאן תוכלו למצוא את כל הקורסים והתכנים שלנו. התחילו ללמוד עוד היום!</p>';
    
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $user_courses = $is_admin ? get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1)) : get_user_meta($user_id, 'aviz_course_access', true);
        
        if (!empty($user_courses)) {
            $output .= '<h2>הקורסים שלך</h2>';
            $output .= '<div class="aviz-course-grid">';
            foreach ($user_courses as $course) {
                $course_id = $is_admin ? $course->ID : $course;
                $course = get_post($course_id);
                if ($course) {
                    $progress = $is_admin ? array('percentage' => 100) : aviz_get_user_progress($user_id, $course_id);
                    $output .= '<div class="aviz-course-card">';
                    $output .= '<h3>' . $course->post_title . '</h3>';
                    $output .= '<div class="aviz-progress-bar"><div class="aviz-progress" style="width: ' . $progress['percentage'] . '%;"></div></div>';
                    $output .= '<a href="' . get_permalink($course_id) . '" class="aviz-course-button">המשך ללמוד</a>';
                    $output .= '</div>';
                }
            }
            $output .= '</div>';
        } else {
            $output .= '<p>אין לך קורסים זמינים כרגע. פנה למנהל המערכת כדי לקבל גישה.</p>';
        }
    } else {
        $output .= '<p>אנא <a href="' . wp_login_url() . '">התחבר</a> כדי לראות את הקורסים שלך.</p>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('aviz_home_page', 'aviz_home_page');

// פונקציה זו הוסרה זמנית כדי לבדוק אם היא גורמת לבעיה
// function aviz_login_redirect($redirect_to, $request, $user) {
//     if (isset($user->roles) && is_array($user->roles)) {
//         // בדיקה אם המשתמש הוא מנהל מערכת
//         if (in_array('administrator', $user->roles)) {
//             // מנהלי מערכת ימשיכו להגיע ללוח הבקרה
//             return $redirect_to;
//         } else {
//             // משתמשים רגילים יופנו לדף הבית של הפלטפורמה
//             return home_url('/');
//         }
//     } else {
//         return $redirect_to;
//     }
// }
// add_filter('login_redirect', 'aviz_login_redirect', 10, 3);

add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<!-- Debug: aviz_home_page shortcode is ' . (shortcode_exists('aviz_home_page') ? 'registered' : 'not registered') . ' -->';
    }
});
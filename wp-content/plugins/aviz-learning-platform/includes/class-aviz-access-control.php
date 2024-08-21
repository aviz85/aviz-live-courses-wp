<?php
class Aviz_Access_Control {
    public static function user_has_access($content_id) {
        if (!is_user_logged_in()) {
            return array('has_access' => false, 'message' => 'Please log in to access this content.');
        }

        $user_id = get_current_user_id();
        
        // Check if user is an administrator
        if (current_user_can('administrator')) {
            return array('has_access' => true, 'message' => '');
        }
        
        // Get the associated course for this content
        $course_id = get_post_meta($content_id, '_aviz_associated_course', true);
        if (!$course_id) {
            $course_id = $content_id; // If it's already a course
        }
        
        // Get user's course access
        $user_courses = get_user_meta($user_id, 'aviz_course_access', true);
        
        if (is_array($user_courses) && in_array($course_id, $user_courses)) {
            return array('has_access' => true, 'message' => '');
        }
        
        return array('has_access' => false, 'message' => 'You do not have access to this content.');
    }

    public static function check_access() {
        global $post;
        if (!is_singular(['aviz_course', 'aviz_content'])) {
            return;
        }

        $access = self::user_has_access($post->ID);
        if (!$access['has_access']) {
            if (!is_user_logged_in()) {
                wp_safe_redirect(wp_login_url(get_permalink($post->ID)));
            } else {
                wp_safe_redirect(home_url('/unauthorized/'));
            }
            exit;
        }
    }
}

add_action('template_redirect', ['Aviz_Access_Control', 'check_access']);
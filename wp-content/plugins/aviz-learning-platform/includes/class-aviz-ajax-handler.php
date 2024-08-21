<?php
class Aviz_Ajax_Handler {
    public static function init() {
        add_action('wp_ajax_aviz_check_course_access', array(__CLASS__, 'check_course_access'));
        add_action('wp_ajax_nopriv_aviz_check_course_access', array(__CLASS__, 'check_course_access'));
    }

    public static function check_course_access() {
        check_ajax_referer('aviz_ajax_nonce', 'nonce');

        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

        if (Aviz_Access_Control::user_has_access($course_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}

Aviz_Ajax_Handler::init();

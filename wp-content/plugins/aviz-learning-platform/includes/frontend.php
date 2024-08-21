<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_enqueue_styles() {
    wp_enqueue_style('aviz-learning-platform', plugin_dir_url(__FILE__) . '../assets/css/style.css');
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_styles');

require_once plugin_dir_path(__FILE__) . 'dashboard.php';
require_once plugin_dir_path(__FILE__) . 'course-content.php';
require_once plugin_dir_path(__FILE__) . 'home-page.php';

function aviz_course_template($content) {
    if (is_singular('aviz_course')) {
        $course_id = get_the_ID();
        $content .= do_shortcode("[aviz_course_content id=\"$course_id\"]");
    }
    return $content;
}
add_filter('the_content', 'aviz_course_template');

function aviz_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        } else {
            return home_url('/');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'aviz_login_redirect', 10, 3);

function aviz_debug_login_redirect($user_login, $user) {
    error_log('User ' . $user_login . ' logged in. Redirect function called.');
}
add_action('wp_login', 'aviz_debug_login_redirect', 10, 2);

function aviz_enqueue_ai_image_scripts() {
    wp_enqueue_script('aviz-ai-image', plugin_dir_url(__FILE__) . '../assets/js/ai-image-generation.js', array('jquery', 'wp-data', 'wp-editor'), '1.0', true);
    wp_localize_script('aviz-ai-image', 'aviz_ai_image', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aviz_ai_image_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'aviz_enqueue_ai_image_scripts');

function aviz_enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_font_awesome');
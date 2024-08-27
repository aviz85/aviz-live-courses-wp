<?php
/*
Plugin Name: Aviz Learning Platform
Description: Custom learning management system for Aviz
Version: 1.0
Author: Aviz
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Include files
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-management.php';
require_once plugin_dir_path(__FILE__) . 'includes/progress-tracking.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend.php';
require_once plugin_dir_path(__FILE__) . 'includes/ai-image-generation.php';
require_once plugin_dir_path(__FILE__) . 'includes/course-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/bulk-user-registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-aviz-access-control.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-aviz-ajax-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/quiz-post-type.php';

// Activation hook
register_activation_hook(__FILE__, 'aviz_learning_platform_activate');

function aviz_learning_platform_activate() {
    // Activation tasks (if any)
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'aviz_learning_platform_deactivate');

function aviz_learning_platform_deactivate() {
    // Deactivation tasks (if any)
}

function aviz_enqueue_scripts() {
    wp_enqueue_style('aviz-styles', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_style('aviz-home-page', plugins_url('assets/css/aviz-home-page.css', __FILE__), array(), '1.0.0');
    wp_enqueue_script('aviz-content-script', plugins_url('assets/js/aviz-content.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('aviz-content-script', 'aviz_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aviz_content_nonce')
    ));
    wp_enqueue_script('aviz-content-loader', plugin_dir_url(__FILE__) . 'assets/js/aviz-content-loader.js', array('jquery'), '1.0', true);
    wp_localize_script('aviz-content-loader', 'aviz_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aviz_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_scripts');

function aviz_load_content_template($template) {
    if (is_singular('aviz_content')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-aviz_content.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('single_template', 'aviz_load_content_template');

function aviz_register_quiz_template($single_template) {
    global $post;

    if ($post->post_type === 'aviz_quiz') {
        $single_template = plugin_dir_path(__FILE__) . 'templates/single-aviz_quiz.php';
    }

    return $single_template;
}
add_filter('single_template', 'aviz_register_quiz_template');

function aviz_set_default_user_meta($user_id) {
    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    
    if (empty($viewed_content)) {
        update_user_meta($user_id, 'aviz_viewed_content', array());
    }
}
add_action('user_register', 'aviz_set_default_user_meta');
add_action('wp_login', 'aviz_set_default_user_meta', 10, 2);

function aviz_add_smooth_scroll_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Smooth scroll to anchor
        if (window.location.hash) {
            var target = $(window.location.hash);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100 // Adjust the offset as needed
                }, 1000);
            }
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'aviz_add_smooth_scroll_script');

function aviz_add_rewrite_rules() {
    add_rewrite_rule('^unauthorized/?', 'index.php?aviz_unauthorized=1', 'top');
}
add_action('init', 'aviz_add_rewrite_rules');

function aviz_query_vars($vars) {
    $vars[] = 'aviz_unauthorized';
    return $vars;
}
add_filter('query_vars', 'aviz_query_vars');

function aviz_template_include($template) {
    if (get_query_var('aviz_unauthorized')) {
        return plugin_dir_path(__FILE__) . 'templates/unauthorized.php';
    }
    return $template;
}
add_filter('template_include', 'aviz_template_include');
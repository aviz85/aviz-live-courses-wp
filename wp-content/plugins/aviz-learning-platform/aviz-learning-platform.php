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

// Change this function name
function aviz_plugin_enqueue_scripts() {
    wp_enqueue_style('aviz-styles', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_script('aviz-content-script', plugins_url('assets/js/aviz-content.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('aviz-content-script', 'aviz_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aviz_content_nonce')
    ));
}
// Update the action hook to use the new function name
add_action('wp_enqueue_scripts', 'aviz_plugin_enqueue_scripts');

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

// הוסף את הפונקציה הזו
function aviz_set_default_user_meta($user_id) {
    // בדוק אם המטא-דאטה כבר קיים
    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    
    // אם לא קיים, צור מערך ריק
    if (empty($viewed_content)) {
        update_user_meta($user_id, 'aviz_viewed_content', array());
    }
}

// הפעל את הפונקציה כאשר משתמש חדש נרשם
add_action('user_register', 'aviz_set_default_user_meta');

// הפעל את הפונקציה גם כאשר משתמש מתחבר (למקרה שמשתמשים קיימים חסר להם המטא-דאטה)
add_action('wp_login', 'aviz_set_default_user_meta', 10, 2);
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
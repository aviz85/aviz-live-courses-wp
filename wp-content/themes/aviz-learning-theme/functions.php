<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// הגדרת תמיכה בתכונות WordPress
function aviz_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    add_theme_support('automatic-feed-links');
    
    register_nav_menus(array(
        'primary' => __('תפריט ראשי', 'aviz-learning-theme'),
        'footer' => __('תפריט כותרת תחתונה', 'aviz-learning-theme'),
    ));

    // הוספת תמיכה ב-RTL
    add_theme_support('rtl');
}
add_action('after_setup_theme', 'aviz_theme_setup');

// טעינת סגנונות וסקריפטים
function aviz_theme_enqueue_scripts() {
    wp_enqueue_style('aviz-style', get_stylesheet_uri(), array(), filemtime(get_stylesheet_directory() . '/style.css'));
    wp_enqueue_style('aviz-rtl-style', get_template_directory_uri() . '/rtl.css', array('aviz-style'), filemtime(get_stylesheet_directory() . '/rtl.css'));
    wp_enqueue_style('heebo-font', 'https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;700&display=swap', array(), null);
    
    // הוספת תמיכה ב-RTL
    wp_style_add_data('aviz-style', 'rtl', 'replace');

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'aviz_theme_enqueue_scripts');

// הוספת אזורי ווידג'טים
function aviz_widgets_init() {
    register_sidebar(array(
        'name'          => __('סרגל צד', 'aviz-learning-theme'),
        'id'            => 'sidebar-1',
        'description'   => __('הוסף ווידג\'טים כאן.', 'aviz-learning-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'aviz_widgets_init');

// הוספת תמיכה בתרגום
function aviz_load_theme_textdomain() {
    load_theme_textdomain('aviz-learning-theme', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'aviz_load_theme_textdomain');
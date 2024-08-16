<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_register_post_types() {
    // Register Course post type
    register_post_type('aviz_course', array(
        'labels' => array(
            'name' => 'קורסים',
            'singular_name' => 'קורס',
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-book',
        'supports' => array('title', 'editor', 'thumbnail'),
        'rewrite' => array('slug' => 'course'),
    ));

    // Register Content post type (for lessons, quizzes, videos, etc.)
    register_post_type('aviz_content', array(
        'labels' => array(
            'name' => 'תכנים',
            'singular_name' => 'תוכן',
        ),
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title', 'editor', 'thumbnail', 'comments'),
    ));
}
add_action('init', 'aviz_register_post_types');

function aviz_add_content_type_meta_box() {
    add_meta_box(
        'aviz_content_type',
        'סוג התוכן',
        'aviz_content_type_callback',
        'aviz_content',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_content_type_meta_box');

function aviz_content_type_callback($post) {
    wp_nonce_field('aviz_content_type_nonce', 'aviz_content_type_nonce');
    $value = get_post_meta($post->ID, '_aviz_content_type', true);
    ?>
    <select name="aviz_content_type" id="aviz_content_type">
        <option value="lesson" <?php selected($value, 'lesson'); ?>>שיעור</option>
        <option value="quiz" <?php selected($value, 'quiz'); ?>>מבחן</option>
        <option value="video" <?php selected($value, 'video'); ?>>סרטון</option>
    </select>
    <?php
}

function aviz_save_content_type($post_id) {
    if (!isset($_POST['aviz_content_type_nonce']) || !wp_verify_nonce($_POST['aviz_content_type_nonce'], 'aviz_content_type_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['aviz_content_type'])) {
        update_post_meta($post_id, '_aviz_content_type', sanitize_text_field($_POST['aviz_content_type']));
    }
}
add_action('save_post_aviz_content', 'aviz_save_content_type');

// הוספת שדה מטא לשיוך תכנים לקורסים
function aviz_add_course_meta_box() {
    add_meta_box(
        'aviz_course_association',
        'שיוך לקורס',
        'aviz_course_association_callback',
        'aviz_content',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_course_meta_box');

function aviz_course_association_callback($post) {
    wp_nonce_field('aviz_course_association_nonce', 'aviz_course_association_nonce');
    $associated_course = get_post_meta($post->ID, '_aviz_associated_course', true);
    $associated_chapter = get_post_meta($post->ID, '_aviz_associated_chapter', true);
    
    $courses = get_posts(array(
        'post_type' => 'aviz_course',
        'numberposts' => -1,
    ));

    echo '<p><strong>קורס:</strong></p>';
    echo '<select name="aviz_associated_course" id="aviz_associated_course">';
    echo '<option value="">בחר קורס</option>';
    foreach ($courses as $course) {
        echo '<option value="' . esc_attr($course->ID) . '" ' . selected($associated_course, $course->ID, false) . '>' . esc_html($course->post_title) . '</option>';
    }
    echo '</select>';

    echo '<p><strong>פרק:</strong></p>';
    echo '<select name="aviz_associated_chapter" id="aviz_associated_chapter">';
    echo '<option value="">בחר פרק</option>';
    $chapters = get_terms(array(
        'taxonomy' => 'aviz_chapter',
        'hide_empty' => false,
    ));
    foreach ($chapters as $chapter) {
        echo '<option value="' . esc_attr($chapter->term_id) . '" ' . selected($associated_chapter, $chapter->term_id, false) . '>' . esc_html($chapter->name) . '</option>';
    }
    echo '</select>';
}

function aviz_save_course_association($post_id) {
    if (!isset($_POST['aviz_course_association_nonce']) || !wp_verify_nonce($_POST['aviz_course_association_nonce'], 'aviz_course_association_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['aviz_associated_course'])) {
        update_post_meta($post_id, '_aviz_associated_course', sanitize_text_field($_POST['aviz_associated_course']));
    }
    if (isset($_POST['aviz_associated_chapter'])) {
        update_post_meta($post_id, '_aviz_associated_chapter', sanitize_text_field($_POST['aviz_associated_chapter']));
    }
}
add_action('save_post_aviz_content', 'aviz_save_course_association');

function aviz_register_chapter_taxonomy() {
    $labels = array(
        'name'              => 'פרקים',
        'singular_name'     => 'פרק',
        'search_items'      => 'חפש פרקים',
        'all_items'         => 'כל הפרקים',
        'parent_item'       => 'פרק אב',
        'parent_item_colon' => 'פרק אב:',
        'edit_item'         => 'ערוך פרק',
        'update_item'       => 'עדכן פרק',
        'add_new_item'      => 'הוסף פרק חדש',
        'new_item_name'     => 'שם פרק חדש',
        'menu_name'         => 'פרקים',
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'chapter' ),
    );

    register_taxonomy( 'aviz_chapter', array( 'aviz_course' ), $args );
}
add_action( 'init', 'aviz_register_chapter_taxonomy', 0 );
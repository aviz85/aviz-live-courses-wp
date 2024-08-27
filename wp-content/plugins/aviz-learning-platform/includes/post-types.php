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

    // Register Chapter post type
    register_post_type('aviz_chapter', array(
        'labels' => array(
            'name' => 'פרקים',
            'singular_name' => 'פרק',
        ),
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-list-view',
        'supports' => array('title', 'editor'),
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

    // Register Quiz post type
    register_post_type('aviz_quiz', array(
        'labels' => array(
            'name' => 'מבחנים',
            'singular_name' => 'מבחן',
            'add_new' => 'הוסף מבחן חדש',
            'add_new_item' => 'הוסף מבחן חדש',
            'edit_item' => 'ערוך מבחן',
            'new_item' => 'מבחן חדש',
            'view_item' => 'צפה במבחן',
            'search_items' => 'חפש מבחנים',
            'not_found' => 'לא נמצא מבחנים',
            'not_found_in_trash' => 'לא נמצאו מבחנים בפח'
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-clipboard',
        'supports' => array('title', 'editor', 'thumbnail'),
        'rewrite' => array('slug' => 'quiz'),
    ));
}
add_action('init', 'aviz_register_post_types');

// Content Type Meta Box
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

// Content Order Meta Box
function aviz_add_content_order_field() {
    add_meta_box(
        'aviz_content_order',
        'סדר הופעה בפרק',
        'aviz_content_order_callback',
        'aviz_content',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_content_order_field');

function aviz_content_order_callback($post) {
    wp_nonce_field('aviz_content_order_nonce', 'aviz_content_order_nonce');
    $value = get_post_meta($post->ID, '_aviz_content_order', true);
    echo '<label for="aviz_content_order">סדר הופעה:</label> ';
    echo '<input type="number" id="aviz_content_order" name="aviz_content_order" value="' . esc_attr($value) . '" min="0" step="1">';
}

function aviz_save_content_order($post_id) {
    if (!isset($_POST['aviz_content_order_nonce']) || !wp_verify_nonce($_POST['aviz_content_order_nonce'], 'aviz_content_order_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['aviz_content_order'])) {
        update_post_meta($post_id, '_aviz_content_order', intval($_POST['aviz_content_order']));
    }
}
add_action('save_post_aviz_content', 'aviz_save_content_order');

// Chapter Order Meta Box
function aviz_add_chapter_order_field() {
    add_meta_box(
        'aviz_chapter_order',
        'סדר הופעה בקורס',
        'aviz_chapter_order_callback',
        'aviz_chapter',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_chapter_order_field');

function aviz_chapter_order_callback($post) {
    wp_nonce_field('aviz_chapter_order_nonce', 'aviz_chapter_order_nonce');
    $value = get_post_meta($post->ID, '_aviz_chapter_order', true);
    echo '<label for="aviz_chapter_order">סדר הופעה:</label> ';
    echo '<input type="number" id="aviz_chapter_order" name="aviz_chapter_order" value="' . esc_attr($value) . '" min="0" step="1">';
}

function aviz_save_chapter_order($post_id) {
    if (!isset($_POST['aviz_chapter_order_nonce']) || !wp_verify_nonce($_POST['aviz_chapter_order_nonce'], 'aviz_chapter_order_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    $course_id = get_post_meta($post_id, '_aviz_associated_course', true);
    
    if (!isset($_POST['aviz_chapter_order']) || $_POST['aviz_chapter_order'] === '') {
        // Set default order only if no value is provided
        $max_order = get_posts(array(
            'post_type' => 'aviz_chapter',
            'meta_query' => array(
                array(
                    'key' => '_aviz_associated_course',
                    'value' => $course_id,
                ),
            ),
            'meta_key' => '_aviz_chapter_order',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'numberposts' => 1,
        ));

        $new_order = empty($max_order) ? 1 : (intval(get_post_meta($max_order[0]->ID, '_aviz_chapter_order', true)) + 1);
    } else {
        $new_order = intval($_POST['aviz_chapter_order']);
    }

    update_post_meta($post_id, '_aviz_chapter_order', $new_order);
}
add_action('save_post_aviz_chapter', 'aviz_save_chapter_order');

// Content Course and Chapter Association
function aviz_add_content_course_chapter_fields() {
    add_meta_box(
        'aviz_content_course_chapter',
        'קורס ופרק משויכים',
        'aviz_content_course_chapter_callback',
        'aviz_content',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_content_course_chapter_fields');

function aviz_content_course_chapter_callback($post) {
    wp_nonce_field('aviz_content_course_chapter_nonce', 'aviz_content_course_chapter_nonce');
    $course_id = get_post_meta($post->ID, '_aviz_associated_course', true);
    $chapter_id = get_post_meta($post->ID, '_aviz_associated_chapter', true);

    $courses = get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1));
    echo '<p><label for="aviz_content_course">קורס:</label><br>';
    echo '<select name="aviz_content_course" id="aviz_content_course">';
    echo '<option value="">בחר קורס</option>';
    foreach ($courses as $course) {
        printf(
            '<option value="%s"%s>%s</option>',
            $course->ID,
            selected($course_id, $course->ID, false),
            esc_html($course->post_title)
        );
    }
    echo '</select></p>';

    $chapters = get_posts(array(
        'post_type' => 'aviz_chapter',
        'numberposts' => -1,
        'meta_key' => '_aviz_associated_course',
        'meta_value' => $course_id
    ));
    echo '<p><label for="aviz_content_chapter">פרק:</label><br>';
    echo '<select name="aviz_content_chapter" id="aviz_content_chapter">';
    echo '<option value="">בחר פרק</option>';
    foreach ($chapters as $chapter) {
        printf(
            '<option value="%s"%s>%s</option>',
            $chapter->ID,
            selected($chapter_id, $chapter->ID, false),
            esc_html($chapter->post_title)
        );
    }
    echo '</select></p>';
}

function aviz_save_content_course_chapter($post_id) {
    if (!isset($_POST['aviz_content_course_chapter_nonce']) || !wp_verify_nonce($_POST['aviz_content_course_chapter_nonce'], 'aviz_content_course_chapter_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['aviz_content_course'])) {
        update_post_meta($post_id, '_aviz_associated_course', intval($_POST['aviz_content_course']));
    }
    if (isset($_POST['aviz_content_chapter'])) {
        update_post_meta($post_id, '_aviz_associated_chapter', intval($_POST['aviz_content_chapter']));
    }
}
add_action('save_post_aviz_content', 'aviz_save_content_course_chapter');

// Chapter Course Association
function aviz_add_chapter_course_field() {
    add_meta_box(
        'aviz_chapter_course',
        'קורס משויך',
        'aviz_chapter_course_callback',
        'aviz_chapter',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_chapter_course_field');

function aviz_chapter_course_callback($post) {
    wp_nonce_field('aviz_chapter_course_nonce', 'aviz_chapter_course_nonce');
    $value = get_post_meta($post->ID, '_aviz_associated_course', true);
    $courses = get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1));
    echo '<select name="aviz_chapter_course">';
    echo '<option value="">בחר קורס</option>';
    foreach ($courses as $course) {
        printf(
            '<option value="%s"%s>%s</option>',
            $course->ID,
            selected($value, $course->ID, false),
            esc_html($course->post_title)
        );
    }
    echo '</select>';
}

function aviz_save_chapter_course($post_id) {
    if (!isset($_POST['aviz_chapter_course_nonce']) || !wp_verify_nonce($_POST['aviz_chapter_course_nonce'], 'aviz_chapter_course_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['aviz_chapter_course'])) {
        update_post_meta($post_id, '_aviz_associated_course', intval($_POST['aviz_chapter_course']));
    }
}
add_action('save_post_aviz_chapter', 'aviz_save_chapter_course');

// Add this function at the end of the file
function aviz_add_viewed_status_field() {
    add_meta_box(
        'aviz_viewed_status',
        'סטטוס צפייה',
        'aviz_viewed_status_callback',
        'aviz_content',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_viewed_status_field');

function aviz_viewed_status_callback($post) {
    wp_nonce_field('aviz_viewed_status_nonce', 'aviz_viewed_status_nonce');
    $value = get_post_meta($post->ID, '_aviz_viewed_status', true);
    echo '<label for="aviz_viewed_status">נצפה:</label> ';
    echo '<input type="checkbox" id="aviz_viewed_status" name="aviz_viewed_status" value="1" ' . checked($value, '1', false) . '>';
}

function aviz_save_viewed_status($post_id) {
    if (!isset($_POST['aviz_viewed_status_nonce']) || !wp_verify_nonce($_POST['aviz_viewed_status_nonce'], 'aviz_viewed_status_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    $viewed_status = isset($_POST['aviz_viewed_status']) ? '1' : '0';
    update_post_meta($post_id, '_aviz_viewed_status', $viewed_status);
}
add_action('save_post_aviz_content', 'aviz_save_viewed_status');

// יצירת טבלת תוצאות מבחנים
function aviz_create_quiz_results_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        quiz_id bigint(20) NOT NULL,
        score float NOT NULL,
        answers longtext NOT NULL,
        completion_date datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY quiz_id (quiz_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// הוסף את הפונקציה הזו לפונקציית ההפעלה של התוסף
function aviz_plugin_activation() {
    aviz_create_quiz_results_table();
    aviz_check_and_update_quiz_results_table();
}
register_activation_hook(__FILE__, 'aviz_plugin_activation');

// פונקציה לשמירת תוצאות מבחן
function aviz_save_quiz_result($user_id, $quiz_id, $score, $answers) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'score' => $score,
            'answers' => json_encode($answers),
            'completion_date' => current_time('mysql')
        ),
        array('%d', '%d', '%f', '%s', '%s')
    );

    return $wpdb->insert_id;
}

// פונקציה לקבלת תוצאות מבחן של משתמש
function aviz_get_user_quiz_results($user_id, $quiz_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND quiz_id = %d ORDER BY completion_date DESC",
        $user_id,
        $quiz_id
    ));
}

// פונקציה לבדיקה אם משתמש ביצע מבחן
function aviz_user_completed_quiz($user_id, $quiz_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND quiz_id = %d",
        $user_id,
        $quiz_id
    ));

    return $result > 0;
}

// פונקציה למחיקת תוצאות מבחן של משתמש
function aviz_delete_user_quiz_results($user_id, $quiz_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    return $wpdb->delete(
        $table_name,
        array(
            'user_id' => $user_id,
            'quiz_id' => $quiz_id
        ),
        array('%d', '%d')
    );
}

// פונקציה לבדיקה ועדכון מבנה הטבלה
function aviz_check_and_update_quiz_results_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    // בדוק אם העמודה completion_date קיימת
    $column = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'completion_date'",
        DB_NAME,
        $table_name
    ));

    // אם העמודה לא קיימת, הוסף אותה
    if (empty($column)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN completion_date datetime NOT NULL");
    }
}

// בדוק ועדכן את מבנה הטבלה בעת הפעלת התוסף
function aviz_plugin_update_check() {
    if (get_option('aviz_quiz_db_version') != '1.1') {
        aviz_check_and_update_quiz_results_table();
        update_option('aviz_quiz_db_version', '1.1');
    }
}
add_action('plugins_loaded', 'aviz_plugin_update_check');

function aviz_get_user_last_quiz_score($user_id, $quiz_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $score = $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM $table_name WHERE user_id = %d AND quiz_id = %d ORDER BY completion_date DESC LIMIT 1",
        $user_id,
        $quiz_id
    ));

    return $score !== null ? floatval($score) : null;
}

function aviz_get_user_last_quiz_answers($user_id, $quiz_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $answers = $wpdb->get_var($wpdb->prepare(
        "SELECT answers FROM $table_name WHERE user_id = %d AND quiz_id = %d ORDER BY completion_date DESC LIMIT 1",
        $user_id,
        $quiz_id
    ));

    return $answers ? json_decode($answers, true) : array();
}

function aviz_get_user_last_quiz_date($user_id, $quiz_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $completion_date = $wpdb->get_var($wpdb->prepare(
        "SELECT completion_date FROM $table_name WHERE user_id = %d AND quiz_id = %d ORDER BY completion_date DESC LIMIT 1",
        $user_id,
        $quiz_id
    ));

    return $completion_date;
}
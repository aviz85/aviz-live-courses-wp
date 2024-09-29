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
require_once plugin_dir_path(__FILE__) . 'includes/admin/file-upload-meta-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/file-upload-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-file-management.php';

// Activation hook
register_activation_hook(__FILE__, 'aviz_learning_platform_activate');

function aviz_learning_platform_activate() {
    aviz_create_uploaded_files_table();
    aviz_secure_upload_directory();
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

function aviz_create_uploaded_files_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        content_id bigint(20) NOT NULL,
        original_filename varchar(255) NOT NULL,
        stored_filename varchar(255) NOT NULL,
        upload_date datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_content (user_id, content_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function aviz_secure_upload_directory() {
    $upload_dir = wp_upload_dir();
    $aviz_upload_dir = $upload_dir['basedir'] . '/aviz_uploads';

    if (!file_exists($aviz_upload_dir)) {
        wp_mkdir_p($aviz_upload_dir);
    }

    $htaccess_file = $aviz_upload_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "Order Deny,Allow\nDeny from all";
        file_put_contents($htaccess_file, $htaccess_content);
    }

    $index_file = $aviz_upload_dir . '/index.php';
    if (!file_exists($index_file)) {
        file_put_contents($index_file, '<?php // Silence is golden');
    }
}

function aviz_register_user_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'course_id' => 0,
    ), $atts);

    $course_id = intval($atts['course_id']);

    if (!$course_id) {
        return 'מזהה קורס לא תקין';
    }

    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'aviz_course') {
        return 'קורס לא תקין';
    }

    ob_start();

    if (isset($_POST['aviz_register_step_1'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        
        if ($password !== $password_confirm) {
            echo '<p class="error">הסיסמאות אינן תואמות. אנא נסה שוב.</p>';
        } elseif (!email_exists($email)) {
            $verification_code = wp_generate_password(20, false);
            set_transient('aviz_verification_' . $verification_code, array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password,
                'course_id' => $course_id
            ), 24 * HOUR_IN_SECONDS);

            $verification_link = add_query_arg(array(
                'aviz_verify' => $verification_code,
                'course_id' => $course_id
            ), get_permalink());

            wp_mail(
                $email,
                'אימות כתובת האימייל שלך עבור ' . get_bloginfo('name'),
                "שלום $first_name,\n\nאנא לחץ על הקישור הבא כדי לאמת את כתובת האימייל שלך ו��השלים את ההרשמה:\n\n" . $verification_link
            );

            echo '<p class="success">נשלח אימייל אימות. אנא בדוק את תיבת הדואר הנכנס שלך להשלמת ההרשמה.</p>';
        } else {
            echo '<p class="error">כתובת האימייל הזו כבר רשומה. אנא השתמש בכתובת אימייל אחרת או התחבר לחשבון הקיים שלך.</p>';
        }
    } elseif (isset($_GET['aviz_verify'])) {
        $verification_code = $_GET['aviz_verify'];
        $verification_data = get_transient('aviz_verification_' . $verification_code);

        if ($verification_data) {
            $first_name = $verification_data['first_name'];
            $last_name = $verification_data['last_name'];
            $email = $verification_data['email'];
            $password = $verification_data['password'];
            $course_id = $verification_data['course_id'];

            $username = sanitize_user(current(explode('@', $email)));
            $counter = 1;
            $original_username = $username;
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }

            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                echo '<p class="error">' . $user_id->get_error_message() . '</p>';
            } else {
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name
                ));

                $user_courses = get_user_meta($user_id, 'aviz_course_access', true);
                if (!is_array($user_courses)) {
                    $user_courses = array();
                }
                $user_courses[] = $course_id;
                update_user_meta($user_id, 'aviz_course_access', $user_courses);

                delete_transient('aviz_verification_' . $verification_code);

                $login_url = wp_login_url();
                wp_mail(
                    $email,
                    'החשבון שלך נוצר ב-' . get_bloginfo('name'),
                    "שלום $first_name,\n\n" .
                    "החשבון שלך נוצר ונרשמת לקורס: {$course->post_title}\n\n" .
                    "אתה יכול להתחבר בכתובת: $login_url\n" .
                    "שם משתמש: $username\n\n" .
                    "אנא השתמש בסיסמה שהזנת בעת ההרשמה."
                );

                echo '<p class="success">האימייל אומת וההרשמה הושלמה בהצלחה. אתה יכול כעת להתחבר עם כתובת האימייל והסיסמה שלך.</p>';
            }
        } else {
            echo '<p class="error">קוד אימות לא תקין או שפג תוקפו. אנא נסה להירשם שוב.</p>';
        }
    } else {
        ?>
        <form method="post" class="aviz-register-user-form">
            <h2>הרשמה לקורס <?php echo esc_html($course->post_title); ?></h2>
            <p>
                <label for="first_name">שם פרטי:</label>
                <input type="text" name="first_name" id="first_name" required minlength="2" maxlength="50" pattern="[א-ת\s]+" title="אנא הזן שם פרטי בעברית">
            </p>
            <p>
                <label for="last_name">שם משפחה:</label>
                <input type="text" name="last_name" id="last_name" required minlength="2" maxlength="50" pattern="[א-ת\s]+" title="אנא הזן שם משפחה בעברית">
            </p>
            <p>
                <label for="email">כתובת אימייל:</label>
                <input type="email" name="email" id="email" required>
            </p>
            <p>
                <label for="password">סיסמה:</label>
                <input type="password" name="password" id="password" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה ומספר אחד">
            </p>
            <p>
                <label for="password_confirm">אימות סיסמה:</label>
                <input type="password" name="password_confirm" id="password_confirm" required>
            </p>
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            <p>
                <input type="submit" name="aviz_register_step_1" value="הרשמה">
            </p>
        </form>
        <script>
        document.querySelector('.aviz-register-user-form').addEventListener('submit', function(e) {
            var password = document.getElementById('password');
            var password_confirm = document.getElementById('password_confirm');
            if (password.value !== password_confirm.value) {
                e.preventDefault();
                alert('הסיסמאות אינן תואמות. אנא נסה שוב.');
            }
        });
        </script>
        <?php
    }

    return ob_get_clean();
}
add_shortcode('aviz_register_user_form', 'aviz_register_user_form_shortcode');

function aviz_add_post_id_metabox() {
    $screens = get_post_types([], 'names');
    foreach ($screens as $screen) {
        add_meta_box(
            'aviz_post_id_metabox',
            'Post ID',
            'aviz_post_id_metabox_callback',
            $screen,
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'aviz_add_post_id_metabox');

function aviz_post_id_metabox_callback($post) {
    echo '<p>The ID of this post is: <strong>' . $post->ID . '</strong></p>';
}
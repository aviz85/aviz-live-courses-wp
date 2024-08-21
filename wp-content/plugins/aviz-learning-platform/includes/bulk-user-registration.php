<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_bulk_register_users($users_data, $course_id) {
    $registered_users = array();
    $errors = array();

    foreach ($users_data as $user_data) {
        $username = sanitize_user($user_data['username']);
        $email = sanitize_email($user_data['email']);
        $first_name = sanitize_text_field($user_data['first_name']);
        $last_name = sanitize_text_field($user_data['last_name']);
        $password = wp_generate_password();

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            $errors[] = array(
                'username' => $username,
                'email' => $email,
                'error' => $user_id->get_error_message()
            );
        } else {
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name
            ));

            // Assign user to the course
            $user_courses = get_user_meta($user_id, 'aviz_course_access', true);
            if (!is_array($user_courses)) {
                $user_courses = array();
            }
            $user_courses[] = $course_id;
            update_user_meta($user_id, 'aviz_course_access', $user_courses);

            // Send email to the user with their login credentials
            wp_mail(
                $email,
                'Your new account on ' . get_bloginfo('name'),
                "Username: $username\nPassword: $password\n\nPlease login and change your password."
            );

            $registered_users[] = get_userdata($user_id);
        }
    }

    return array(
        'registered_users' => $registered_users,
        'errors' => $errors
    );
}

function aviz_add_bulk_registration_page() {
    add_submenu_page(
        'edit.php?post_type=aviz_course',
        'Bulk User Registration',
        'Bulk Registration',
        'manage_options',
        'aviz-bulk-registration',
        'aviz_bulk_registration_page'
    );
}
add_action('admin_menu', 'aviz_add_bulk_registration_page');

function aviz_bulk_registration_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $courses = get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1));
    $selected_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

    if (isset($_POST['aviz_bulk_register'])) {
        $users_data = array();
        $rows = explode("\n", $_POST['user_data']);
        $headers = str_getcsv(array_shift($rows));
        $header_map = array_flip($headers);

        foreach ($rows as $row) {
            $data = str_getcsv($row);
            if (count($data) === count($headers)) {
                $users_data[] = array(
                    'username' => $data[$header_map['username']],
                    'email' => $data[$header_map['email']],
                    'first_name' => $data[$header_map['first_name']],
                    'last_name' => $data[$header_map['last_name']]
                );
            }
        }

        $course_id = intval($_POST['course_id']);
        $result = aviz_bulk_register_users($users_data, $course_id);
        $registered_users = $result['registered_users'];

        echo '<div class="notice notice-success"><p>' . count($result['registered_users']) . ' users registered successfully.</p></div>';

        if (!empty($result['errors'])) {
            echo '<div class="notice notice-error"><p>Errors occurred:</p><ul>';
            foreach ($result['errors'] as $error) {
                echo '<li>' . $error['username'] . ' (' . $error['email'] . '): ' . $error['error'] . '</li>';
            }
            echo '</ul></div>';
        }
    }

    if (isset($_POST['aviz_send_reset_email'])) {
        $user_id = intval($_POST['aviz_send_reset_email']);
        $user = get_userdata($user_id);
        if ($user) {
            $reset_key = get_password_reset_key($user);
            $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
            $message = "שלום " . $user->display_name . ",\n\n";
            $message .= "לחץ על הקישור הבא כדי לאפס את הסיסמה שלך:\n\n";
            $message .= $reset_link . "\n\n";
            $message .= "אם לא ביקשת לאפס את הסיסמה, אנא התעלם מהודעה זו.";
            wp_mail($user->user_email, 'איפוס סיסמה לחשבונך', $message);
            echo "<script>alert('נשלח מייל איפוס סיסמה למשתמש " . esc_js($user->user_login) . ".');</script>";
        }
    }

    if (isset($_POST['aviz_send_reset_emails'])) {
        $user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : array();
        $reset_count = 0;
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $reset_key = get_password_reset_key($user);
                $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
                $message = "שלום " . $user->display_name . ",\n\n";
                $message .= "לחץ על הקישור הבא כדי לאפס את הסיסמה שלך:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "אם לא ביקשת לאפס את הסיסמה, אנא התעלם מהודעה זו.";
                wp_mail($user->user_email, 'איפוס סיסמה לחשבונך', $message);
                $reset_count++;
            }
        }
        echo "<script>alert('נשלחו $reset_count הודעות איפוס סיסמה בהצלחה.');</script>";
    }

    ?>
    <div class="wrap">
        <h1>Bulk User Registration</h1>
        <form method="post">
            <label for="user_data">User Data (CSV format with headers):</label><br>
            <textarea name="user_data" id="user_data" rows="10" cols="50" placeholder="username,email,first_name,last_name
john_doe,john@example.com,John,Doe
jane_smith,jane@example.com,Jane,Smith"></textarea><br>
            <label for="course_id">Assign to Course:</label>
            <select name="course_id" id="course_id">
                <?php foreach ($courses as $course) : ?>
                    <option value="<?php echo $course->ID; ?>"><?php echo esc_html($course->post_title); ?></option>
                <?php endforeach; ?>
            </select><br>
            <input type="submit" name="aviz_bulk_register" class="button button-primary" value="Register Users">
        </form>

        <h2>User Management</h2>
        <form method="get">
            <label for="course_filter">Filter by Course:</label>
            <select name="course_id" id="course_filter">
                <option value="0">All Courses</option>
                <?php foreach ($courses as $course) : ?>
                    <option value="<?php echo $course->ID; ?>" <?php selected($selected_course, $course->ID); ?>><?php echo esc_html($course->post_title); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" class="button" value="Filter">
        </form>

        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $args = array(
                        'number' => -1,
                        'orderby' => 'user_login',
                        'order' => 'ASC',
                    );
                    if ($selected_course) {
                        $args['meta_key'] = 'aviz_course_access';
                        $args['meta_value'] = $selected_course;
                        $args['meta_compare'] = 'LIKE';
                    }
                    $users = get_users($args);
                    foreach ($users as $user) :
                    ?>
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="<?php echo $user->ID; ?>"></td>
                            <td><?php echo $user->user_login; ?></td>
                            <td><?php echo $user->user_email; ?></td>
                            <td><?php echo $user->first_name . ' ' . $user->last_name; ?></td>
                            <td>
                                <button type="submit" name="aviz_send_reset_email" class="button" value="<?php echo $user->ID; ?>" onclick="return confirm('האם אתה בטוח שברצונך לשלוח מייל איפוס סיסמה למשתמש זה?');">Send Password Reset Email</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <input type="submit" name="aviz_send_reset_emails" class="button button-primary" value="Send Password Reset Email to Selected" onclick="return confirm('האם אתה בטוח שברצונך לשלוח מייל איפוס סיסמה לכל המשתמשים המסומנים?');">
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#select-all').on('change', function() {
            $('input[name="user_ids[]"]').prop('checked', this.checked);
        });
    });
    </script>
    <?php
}
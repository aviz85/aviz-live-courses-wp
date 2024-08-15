<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_add_user_course_access() {
    add_action('show_user_profile', 'aviz_user_course_access_fields');
    add_action('edit_user_profile', 'aviz_user_course_access_fields');
    add_action('personal_options_update', 'aviz_save_user_course_access');
    add_action('edit_user_profile_update', 'aviz_save_user_course_access');
}
add_action('init', 'aviz_add_user_course_access');

function aviz_user_course_access_fields($user) {
    if (!current_user_can('edit_users')) return;

    $user_courses = get_user_meta($user->ID, 'aviz_course_access', true);
    if (!is_array($user_courses)) $user_courses = array();

    $courses = get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1));
    ?>
    <h3>גישה לקורסים</h3>
    <table class="form-table">
        <tr>
            <th><label for="aviz_course_access">קורסים זמינים</label></th>
            <td>
                <?php foreach ($courses as $course) : ?>
                    <label>
                        <input type="checkbox" name="aviz_course_access[]" value="<?php echo $course->ID; ?>" <?php checked(in_array($course->ID, $user_courses)); ?>>
                        <?php echo $course->post_title; ?>
                    </label><br>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    <?php
}

function aviz_save_user_course_access($user_id) {
    if (!current_user_can('edit_users')) return false;

    if (isset($_POST['aviz_course_access'])) {
        $course_access = array_map('intval', $_POST['aviz_course_access']);
        update_user_meta($user_id, 'aviz_course_access', $course_access);
    } else {
        delete_user_meta($user_id, 'aviz_course_access');
    }
}
<?php
function aviz_handle_file_upload() {
    check_ajax_referer('aviz_file_upload', 'aviz_file_upload_nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('המשתמש אינו מחובר.');
    }

    $user_id = get_current_user_id();
    $content_id = intval($_POST['content_id']);

    if (aviz_user_has_uploaded_file($user_id, $content_id)) {
        wp_send_json_error('כבר העלית קובץ לתוכן זה. אנא מחק את הקובץ הקיים לפני העלאת קובץ חדש.');
    }

    if (!isset($_FILES['aviz_file_upload'])) {
        wp_send_json_error('לא נבחר קובץ.');
    }

    $file = $_FILES['aviz_file_upload'];

    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error('הקובץ גדול מדי. הגודל המקסימלי הוא 5MB.');
    }

    $upload_dir = wp_upload_dir();
    $aviz_upload_dir = $upload_dir['basedir'] . '/aviz_uploads';

    if (!file_exists($aviz_upload_dir)) {
        wp_mkdir_p($aviz_upload_dir);
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'user_' . $user_id . '_content_' . $content_id . '_' . time() . '.' . $file_extension;
    $new_file_path = $aviz_upload_dir . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
        aviz_save_uploaded_file_info($user_id, $content_id, $file['name'], $new_filename);
        wp_send_json_success('הקובץ הועלה בהצלחה.');
    } else {
        wp_send_json_error('שגיאה בהעלאת הקובץ.');
    }
}
add_action('wp_ajax_aviz_handle_file_upload', 'aviz_handle_file_upload');

function aviz_save_uploaded_file_info($user_id, $content_id, $original_filename, $stored_filename) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';

    $existing_file = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND content_id = %d",
        $user_id,
        $content_id
    ));

    if ($existing_file) {
        $wpdb->update(
            $table_name,
            array(
                'original_filename' => $original_filename,
                'stored_filename' => $stored_filename,
                'upload_date' => current_time('mysql')
            ),
            array('id' => $existing_file->id)
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'content_id' => $content_id,
                'original_filename' => $original_filename,
                'stored_filename' => $stored_filename,
                'upload_date' => current_time('mysql')
            )
        );
    }
}

function aviz_get_user_uploaded_file($user_id, $content_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';

    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND content_id = %d",
        $user_id,
        $content_id
    ), ARRAY_A);
}

function aviz_user_has_uploaded_file($user_id, $content_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND content_id = %d",
        $user_id,
        $content_id
    ));
    return $result > 0;
}

function aviz_handle_file_delete() {
    check_ajax_referer('aviz_file_delete', 'aviz_file_delete_nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('המשתמש אינו מחובר.');
    }

    $user_id = get_current_user_id();
    $content_id = intval($_POST['content_id']);

    $file_info = aviz_get_user_uploaded_file($user_id, $content_id);

    if (!$file_info) {
        wp_send_json_error('לא נמצא קובץ למחיקה.');
    }

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/aviz_uploads/' . $file_info['stored_filename'];

    if (file_exists($file_path)) {
        unlink($file_path);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';
    $wpdb->delete($table_name, array('id' => $file_info['id']));

    wp_send_json_success('הקובץ נמחק בהצלחה.');
}
add_action('wp_ajax_aviz_handle_file_delete', 'aviz_handle_file_delete');
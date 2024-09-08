<?php

function aviz_add_uploaded_files_meta_box() {
    add_meta_box(
        'aviz_uploaded_files',
        'קבצים שהועלו',
        'aviz_uploaded_files_callback',
        'aviz_content',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'aviz_add_uploaded_files_meta_box');

function aviz_uploaded_files_callback($post) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';
    $files = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE content_id = %d ORDER BY upload_date DESC",
        $post->ID
    ));

    if (empty($files)) {
        echo '<p>אין קבצים שהועלו לתוכן זה.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>שם משתמש</th><th>שם קובץ מקורי</th><th>גודל קובץ</th><th>תאריך העלאה</th><th>פעולות</th></tr></thead>';
    echo '<tbody>';
    foreach ($files as $file) {
        $user = get_user_by('id', $file->user_id);
        $file_path = wp_upload_dir()['basedir'] . '/aviz_uploads/' . $file->stored_filename;
        $file_size = file_exists($file_path) ? size_format(filesize($file_path), 2) : 'N/A';
        echo '<tr>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . esc_html($file->original_filename) . '</td>';
        echo '<td>' . esc_html($file_size) . '</td>';
        echo '<td>' . esc_html($file->upload_date) . '</td>';
        echo '<td><a href="' . esc_url(admin_url('admin-ajax.php?action=aviz_download_file&file_id=' . $file->id . '&nonce=' . wp_create_nonce('aviz_download_file'))) . '">הורד</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function aviz_download_file() {
    if (!current_user_can('manage_options')) {
        wp_die('אין לך הרשאות לגשת לדף זה.');
    }

    check_admin_referer('aviz_download_file', 'nonce');

    $file_id = intval($_GET['file_id']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_uploaded_files';
    $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $file_id));

    if (!$file) {
        wp_die('הקובץ לא נמצא.');
    }

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/aviz_uploads/' . $file->stored_filename;

    if (!file_exists($file_path)) {
        wp_die('הקובץ לא נמצא במערכת הקבצים.');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file->original_filename . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}
add_action('wp_ajax_aviz_download_file', 'aviz_download_file');

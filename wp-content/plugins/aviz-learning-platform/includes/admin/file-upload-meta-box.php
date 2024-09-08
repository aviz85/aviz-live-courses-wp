<?php

function aviz_add_file_upload_meta_box() {
    add_meta_box(
        'aviz_file_upload_settings',
        'הגדרות העלאת קבצים',
        'aviz_file_upload_settings_callback',
        'aviz_content',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'aviz_add_file_upload_meta_box');

function aviz_file_upload_settings_callback($post) {
    wp_nonce_field('aviz_file_upload_settings', 'aviz_file_upload_settings_nonce');
    $enable_file_upload = get_post_meta($post->ID, '_aviz_enable_file_upload', true);
    ?>
    <p>
        <input type="checkbox" id="aviz_enable_file_upload" name="aviz_enable_file_upload" value="1" <?php checked($enable_file_upload, '1'); ?>>
        <label for="aviz_enable_file_upload">אפשר העלאת קבצים</label>
    </p>
    <?php
}

function aviz_save_file_upload_settings($post_id) {
    if (!isset($_POST['aviz_file_upload_settings_nonce']) || !wp_verify_nonce($_POST['aviz_file_upload_settings_nonce'], 'aviz_file_upload_settings')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['aviz_enable_file_upload'])) {
        update_post_meta($post_id, '_aviz_enable_file_upload', '1');
    } else {
        delete_post_meta($post_id, '_aviz_enable_file_upload');
    }
}
add_action('save_post_aviz_content', 'aviz_save_file_upload_settings');

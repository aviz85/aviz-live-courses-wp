<?php

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
        date_taken datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'aviz_create_quiz_results_table');
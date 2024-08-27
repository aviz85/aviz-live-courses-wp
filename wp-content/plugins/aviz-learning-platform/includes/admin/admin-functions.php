function aviz_enqueue_admin_scripts($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    $screen = get_current_screen();
    if ('aviz_quiz' !== $screen->post_type) {
        return;
    }

    wp_enqueue_script('aviz-admin-quiz', plugin_dir_url(__FILE__) . '../../assets/js/admin-quiz.js', array('jquery'), '1.0', true);
    wp_localize_script('aviz-admin-quiz', 'aviz_quiz_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aviz_quiz_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'aviz_enqueue_admin_scripts');
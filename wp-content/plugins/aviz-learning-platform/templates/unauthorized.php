<?php
get_header();
?>

<div class="aviz-unauthorized">
    <h1><?php echo esc_html__('Access Denied', 'aviz-learning-platform'); ?></h1>
    <p><?php echo esc_html__('You do not have permission to access this content.', 'aviz-learning-platform'); ?></p>
    <p><?php echo esc_html__('Please contact an administrator if you believe this is an error.', 'aviz-learning-platform'); ?></p>
    <a href="<?php echo esc_url(home_url()); ?>" class="aviz-button"><?php echo esc_html__('Return to Homepage', 'aviz-learning-platform'); ?></a>
</div>

<?php
get_footer();
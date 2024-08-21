<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function aviz_home_page_content() {
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return "<script>window.location.href = '" . esc_js($login_url) . "';</script>";
    }

    $user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');
    $user_courses = $is_admin ? 
        get_posts(['post_type' => 'aviz_course', 'numberposts' => -1]) : 
        get_user_meta($user_id, 'aviz_course_access', true);
    
    $user_courses = is_array($user_courses) ? $user_courses : [];

    ob_start();
    ?>
    <div class="aviz-home-page">
        <h1>ברוכים הבאים לפלטפורמת הלמידה של אביץ</h1>
        <p class="aviz-intro">כאן תוכלו למצוא את כל הקורסים שלכם ולעקוב אחר ההתקדמות שלכם.</p>

        <?php if (empty($user_courses)) : ?>
            <p class="aviz-no-courses">אין קורסים זמינים כרגע. אנא פנה למנהל המערכת.</p>
        <?php else : ?>
            <div class="aviz-course-grid">
                <?php foreach ($user_courses as $course) :
                    $course_id = $is_admin ? $course->ID : $course;
                    $course_title = get_the_title($course_id);
                    $progress = aviz_get_user_progress($user_id, $course_id);
                    $thumbnail = get_the_post_thumbnail($course_id, 'medium');
                    ?>
                    <div class="aviz-home-course-card">
                        <h3><?php echo esc_html($course_title); ?></h3>
                        <?php if ($thumbnail) : ?>
                            <div class="aviz-home-course-thumbnail"><?php echo $thumbnail; ?></div>
                        <?php endif; ?>
                        <div class="aviz-home-progress-bar">
                            <div class="aviz-home-progress" style="width: <?php echo esc_attr($progress['percentage']); ?>%;"></div>
                        </div>
                        <span class="aviz-home-progress-text"><?php echo esc_html("{$progress['viewed']} / {$progress['total']} הושלמו"); ?></span>
                        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="aviz-home-course-button">המשך ללמוד</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function aviz_home_page_shortcode() {
    return aviz_home_page_content();
}
add_shortcode('aviz_home_page', 'aviz_home_page_shortcode');
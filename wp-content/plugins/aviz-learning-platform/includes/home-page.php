<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'progress-tracking.php';

function aviz_home_page_content() {
    ob_start();
    ?>
    <div class="aviz-home-page">
        <h1>ברוכים הבאים לפלטפורמת הלמידה של אביץ</h1>
        <p class="aviz-intro">כאן תוכלו למצוא את כל הקורסים שלכם ולעקוב אחר ההתקדמות שלכם.</p>

        <?php if (!is_user_logged_in()) : ?>
            <div class="aviz-guest-message">
                <h2>הצטרפו למסע של התפתחות והעשרה</h2>
                <p>פלטפורמת הלמידה של אביץ מציעה מגוון קורסים איכותיים המותאמים לצרכים המקצועיים של היום. גלו הזדמנויות חדשות להתקדם ולהצליח בקריירה שלכם.</p>
                <div class="aviz-cta-container">
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="aviz-login-button">כניסה למשתמשים רשומים</a>
                    <?php
                    $whatsapp_number = '972503973736';
                    $whatsapp_message = urlencode('היי, אני מעוניין לקבל פרטי גישה לפלטפורמת הלמידה של אביץ.');
                    $whatsapp_link = "https://wa.me/{$whatsapp_number}?text={$whatsapp_message}";
                    ?>
                    <p class="aviz-register-info">אין לך חשבון? <a href="<?php echo esc_url($whatsapp_link); ?>" target="_blank">צור קשר עם אביץ</a> לקבלת פרטי גישה.</p>
                </div>
            </div>
        <?php else : ?>
            <?php
            $user_id = get_current_user_id();
            $is_admin = current_user_can('manage_options');
            $user_courses = $is_admin ? 
                get_posts(['post_type' => 'aviz_course', 'numberposts' => -1]) : 
                get_user_meta($user_id, 'aviz_course_access', true);
            
            $user_courses = is_array($user_courses) ? $user_courses : [];

            if (empty($user_courses)) : ?>
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
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function aviz_home_page_shortcode() {
    return aviz_home_page_content();
}
add_shortcode('aviz_home_page', 'aviz_home_page_shortcode');
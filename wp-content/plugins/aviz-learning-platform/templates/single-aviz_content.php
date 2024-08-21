<?php
get_header();
require_once plugin_dir_path(__FILE__) . '../includes/course-functions.php';

while ( have_posts() ) :
    the_post();
    $content_id = get_the_ID();

    $user_id = get_current_user_id();
    $viewed_content = get_user_meta($user_id, 'aviz_viewed_content', true);
    $is_viewed = is_array($viewed_content) && in_array($content_id, $viewed_content);

    // Get associated chapter and course
    $chapter_id = get_post_meta($content_id, '_aviz_associated_chapter', true);
    $course_id = get_post_meta($chapter_id, '_aviz_associated_course', true);
    $chapter = get_post($chapter_id);
    $course = get_post($course_id);

    // Get all contents of the course
    $all_contents = aviz_get_course_contents($course_id);
    $current_index = array_search($content_id, array_column($all_contents, 'ID'));
    $prev_content = ($current_index > 0) ? $all_contents[$current_index - 1] : null;
    $next_content = ($current_index < count($all_contents) - 1) ? $all_contents[$current_index + 1] : null;
    ?>
    <div class="aviz-content-wrapper">
        <div class="aviz-course-header">
            <?php if (has_post_thumbnail($course_id)) : ?>
                <?php echo get_the_post_thumbnail($course_id, 'full', array('class' => 'aviz-course-thumbnail')); ?>
            <?php endif; ?>
            <h1 class="aviz-course-title"><?php echo esc_html($course->post_title); ?></h1>
        </div>

        <div class="aviz-content-navigation">
            <div class="aviz-breadcrumbs">
                <a href="<?php echo get_permalink($course_id); ?>"><?php echo esc_html($course->post_title); ?></a> &gt;
                <a href="<?php echo get_permalink($course_id) . '#chapter-' . $chapter_id; ?>"><?php echo esc_html($chapter->post_title); ?></a> &gt;
                <span><?php the_title(); ?></span>
            </div>
        </div>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('aviz-single-content'); ?>>
            <header class="entry-header">
                <h2 class="entry-title"><?php the_title(); ?></h2>
            </header>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>

            <footer class="entry-footer">
                <div class="aviz-content-navigation">
                    <?php if ($prev_content): ?>
                        <a href="<?php echo get_permalink($prev_content->ID); ?>" class="aviz-button aviz-prev-content">
                            <span class="aviz-nav-icon">&larr;</span> התוכן הקודם
                        </a>
                    <?php else: ?>
                        <a href="<?php echo get_permalink($course_id); ?>" class="aviz-button aviz-back-to-course">
                            <span class="aviz-nav-icon">&larr;</span> חזרה לקורס
                        </a>
                    <?php endif; ?>

                    <button id="aviz-mark-complete" class="aviz-button <?php echo $is_viewed ? 'aviz-completed' : ''; ?>" data-content-id="<?php echo $content_id; ?>" data-nonce="<?php echo wp_create_nonce('aviz_content_nonce'); ?>">
                        <?php echo $is_viewed ? 'הושלם ✓' : 'סמן כהושלם'; ?>
                    </button>

                    <?php if ($next_content): ?>
                        <a href="<?php echo get_permalink($next_content->ID); ?>" class="aviz-button aviz-next-content <?php echo !$is_viewed ? 'aviz-disabled' : ''; ?>">
                            התוכן הבא <span class="aviz-nav-icon">&rarr;</span>
                        </a>
                    <?php endif; ?>
                </div>
            </footer>
        </article>
    </div>
    <?php
endwhile;

get_footer();
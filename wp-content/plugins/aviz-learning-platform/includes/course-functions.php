<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('aviz_get_course_contents')) {
    function aviz_get_course_contents($course_id) {
        $chapters = get_posts(array(
            'post_type' => 'aviz_chapter',
            'meta_key' => '_aviz_associated_course',
            'meta_value' => $course_id,
            'orderby' => 'meta_value_num',
            'meta_key' => '_aviz_chapter_order',
            'order' => 'ASC',
            'numberposts' => -1
        ));

        $contents = array();
        foreach ($chapters as $chapter) {
            $chapter_contents = get_posts(array(
                'post_type' => 'aviz_content',
                'meta_key' => '_aviz_associated_chapter',
                'meta_value' => $chapter->ID,
                'orderby' => 'meta_value_num',
                'meta_key' => '_aviz_content_order',
                'order' => 'ASC',
                'numberposts' => -1
            ));
            $contents = array_merge($contents, $chapter_contents);
        }

        return $contents;
    }
}
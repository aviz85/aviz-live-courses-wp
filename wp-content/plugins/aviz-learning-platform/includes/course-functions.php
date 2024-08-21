<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('aviz_get_course_contents')) {
    function aviz_get_course_contents($course_id) {
        $chapters = get_posts(array(
            'post_type' => 'aviz_chapter',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aviz_associated_course',
                    'value' => $course_id,
                ),
            ),
            'meta_key' => '_aviz_chapter_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        ));

        $all_contents = array();
        foreach ($chapters as $chapter) {
            $contents = get_posts(array(
                'post_type' => 'aviz_content',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_aviz_associated_chapter',
                        'value' => $chapter->ID,
                    ),
                ),
                'meta_key' => '_aviz_content_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC'
            ));
            $all_contents = array_merge($all_contents, $contents);
        }
        return $all_contents;
    }
}
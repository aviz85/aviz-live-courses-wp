<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function aviz_register_quiz_post_type() {
    $labels = array(
        'name'               => 'מבחנים',
        'singular_name'      => 'מבחן',
        'menu_name'          => 'מבחנים',
        'add_new'            => 'הוסף מבחן חדש',
        'add_new_item'       => 'הוסף מבחן חדש',
        'edit_item'          => 'ערוך מבחן',
        'new_item'           => 'מבחן חדש',
        'view_item'          => 'צפה במבחן',
        'search_items'       => 'חפש מבחנים',
        'not_found'          => 'לא נמצאו מבחנים',
        'not_found_in_trash' => 'לא נמצאו מבחנים בפח'
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'quiz'),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => null,
        'supports'            => array('title', 'editor', 'thumbnail'),
        'menu_icon'           => 'dashicons-clipboard'
    );

    register_post_type('aviz_quiz', $args);
}
add_action('init', 'aviz_register_quiz_post_type');

function aviz_add_quiz_meta_boxes() {
    add_meta_box(
        'aviz_quiz_details',
        'פרטי המבחן',
        'aviz_quiz_details_callback',
        'aviz_quiz',
        'side',
        'high'
    );

    add_meta_box(
        'aviz_quiz_questions',
        'שאלות המבחן',
        'aviz_quiz_questions_callback',
        'aviz_quiz',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'aviz_add_quiz_meta_boxes');

function aviz_quiz_details_callback($post) {
    wp_nonce_field('aviz_save_quiz_details', 'aviz_quiz_details_nonce');

    $course_id = get_post_meta($post->ID, '_aviz_quiz_course', true);
    $chapter_id = get_post_meta($post->ID, '_aviz_quiz_chapter', true);
    $quiz_order = get_post_meta($post->ID, '_aviz_quiz_order', true);
    $passing_grade = get_post_meta($post->ID, '_aviz_quiz_passing_grade', true);
    $time_limit = get_post_meta($post->ID, '_aviz_quiz_time_limit', true);

    $courses = get_posts(array('post_type' => 'aviz_course', 'numberposts' => -1));

    ?>
    <p>
        <label for="aviz_quiz_course">קורס משויך:</label><br>
        <select name="aviz_quiz_course" id="aviz_quiz_course" style="width: 100%;">
            <option value="">בחר קורס</option>
            <?php foreach ($courses as $course) : ?>
                <option value="<?php echo $course->ID; ?>" <?php selected($course_id, $course->ID); ?>>
                    <?php echo $course->post_title; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="aviz_quiz_chapter">פרק משויך:</label><br>
        <select name="aviz_quiz_chapter" id="aviz_quiz_chapter" style="width: 100%;">
            <!-- Options will be populated via AJAX -->
        </select>
    </p>
    <p>
        <label for="aviz_quiz_order">סדר הופעה בפרק:</label><br>
        <input type="number" name="aviz_quiz_order" id="aviz_quiz_order" value="<?php echo esc_attr($quiz_order); ?>" min="0" style="width: 100%;">
    </p>
    <p>
        <label for="aviz_quiz_passing_grade">ציון עובר:</label><br>
        <input type="number" name="aviz_quiz_passing_grade" id="aviz_quiz_passing_grade" value="<?php echo esc_attr($passing_grade); ?>" min="0" max="100" style="width: 100%;">
    </p>
    <p>
        <label for="aviz_quiz_time_limit">הגבלת זמן (בדקות):</label><br>
        <input type="number" name="aviz_quiz_time_limit" id="aviz_quiz_time_limit" value="<?php echo esc_attr($time_limit); ?>" min="0" style="width: 100%;">
        <small>0 ללא הגבלה</small>
    </p>
    <?php
}

function aviz_quiz_questions_callback($post) {
    wp_nonce_field('aviz_save_quiz_questions', 'aviz_quiz_questions_nonce');
    $questions = get_post_meta($post->ID, '_aviz_quiz_questions', true);
    if (!is_array($questions)) {
        $questions = array();
    }
    ?>
    <div id="aviz-quiz-questions">
        <?php
        foreach ($questions as $index => $question) {
            aviz_render_question_fields($index, $question);
        }
        ?>
    </div>
    <button type="button" id="add-question" class="button">הוסף שאלה</button>
    <script type="text/template" id="question-template">
        <?php aviz_render_question_fields('{{INDEX}}', array()); ?>
    </script>
    <?php
}

function aviz_render_question_fields($index, $question = array()) {
    $question = wp_parse_args($question, array(
        'text' => '',
        'answers' => array('', '', '', ''),
        'correct_answer' => 0
    ));
    ?>
    <div class="question" data-index="<?php echo esc_attr($index); ?>">
        <h3>שאלה <?php echo esc_html(is_numeric($index) ? $index + 1 : $index); ?></h3>
        <p>
            <label for="question_text_<?php echo esc_attr($index); ?>">תוכן השאלה:</label>
            <textarea name="aviz_quiz_questions[<?php echo esc_attr($index); ?>][text]" id="question_text_<?php echo esc_attr($index); ?>" rows="3" cols="50"><?php echo esc_textarea($question['text']); ?></textarea>
        </p>
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <p>
                <label for="question_answer_<?php echo esc_attr($index); ?>_<?php echo esc_attr($i); ?>">תשובה <?php echo esc_html($i + 1); ?>:</label>
                <input type="text" name="aviz_quiz_questions[<?php echo esc_attr($index); ?>][answers][]" id="question_answer_<?php echo esc_attr($index); ?>_<?php echo esc_attr($i); ?>" value="<?php echo esc_attr($question['answers'][$i]); ?>">
                <label>
                    <input type="radio" name="aviz_quiz_questions[<?php echo esc_attr($index); ?>][correct_answer]" value="<?php echo esc_attr($i); ?>" <?php checked($question['correct_answer'], $i); ?>>
                    תשובה נכונה
                </label>
            </p>
        <?php endfor; ?>
        <button type="button" class="button remove-question">הסר שאלה</button>
    </div>
    <?php
}

function aviz_save_quiz_meta($post_id) {
    if (!isset($_POST['aviz_quiz_details_nonce']) || !wp_verify_nonce($_POST['aviz_quiz_details_nonce'], 'aviz_save_quiz_details')) {
        return;
    }

    if (!isset($_POST['aviz_quiz_questions_nonce']) || !wp_verify_nonce($_POST['aviz_quiz_questions_nonce'], 'aviz_save_quiz_questions')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save quiz details
    $fields = array(
        'aviz_quiz_course',
        'aviz_quiz_chapter',
        'aviz_quiz_order',
        'aviz_quiz_passing_grade',
        'aviz_quiz_time_limit'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Save quiz questions
    if (isset($_POST['aviz_quiz_questions']) && is_array($_POST['aviz_quiz_questions'])) {
        $questions = array();
        foreach ($_POST['aviz_quiz_questions'] as $index => $question_data) {
            $questions[] = array(
                'text' => sanitize_textarea_field($question_data['text']),
                'answers' => array_map('sanitize_text_field', $question_data['answers']),
                'correct_answer' => intval($question_data['correct_answer'])
            );
        }
        update_post_meta($post_id, '_aviz_quiz_questions', $questions);
    }
}
add_action('save_post_aviz_quiz', 'aviz_save_quiz_meta');

function aviz_enqueue_quiz_admin_scripts($hook) {
    global $post;

    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ('aviz_quiz' === $post->post_type) {
            wp_enqueue_script('aviz-quiz-admin', plugin_dir_url(__FILE__) . '../assets/js/admin-quiz.js', array('jquery'), '1.0', true);
            wp_localize_script('aviz-quiz-admin', 'aviz_quiz_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aviz_quiz_admin_nonce')
            ));
        }
    }
}
add_action('admin_enqueue_scripts', 'aviz_enqueue_quiz_admin_scripts');

function aviz_get_chapters_for_course() {
    check_ajax_referer('aviz_quiz_admin_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $chapters = get_posts(array(
        'post_type' => 'aviz_chapter',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_aviz_chapter_course',
                'value' => $course_id,
            ),
        ),
    ));

    $options = '<option value="">בחר פרק</option>';
    foreach ($chapters as $chapter) {
        $options .= sprintf('<option value="%d">%s</option>', $chapter->ID, esc_html($chapter->post_title));
    }

    wp_send_json_success($options);
}
add_action('wp_ajax_aviz_get_chapters_for_course', 'aviz_get_chapters_for_course');

function aviz_enqueue_quiz_scripts() {
    if (is_singular('aviz_quiz')) {
        wp_enqueue_style('aviz-quiz-style', plugin_dir_url(__FILE__) . '../assets/css/quiz-style.css');
        wp_enqueue_script('aviz-quiz', plugin_dir_url(__FILE__) . '../assets/js/aviz-quiz.js', array('jquery'), '1.0', true);
        wp_localize_script('aviz-quiz', 'aviz_quiz', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aviz_quiz_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'aviz_enqueue_quiz_scripts');

function aviz_submit_quiz() {
    check_ajax_referer('aviz_quiz_nonce', 'nonce');

    $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
    $answers = isset($_POST['answers']) ? $_POST['answers'] : array();

    if (!$quiz_id || empty($answers)) {
        wp_send_json_error(array('message' => 'נתונים חסרים'));
    }

    $questions = get_post_meta($quiz_id, '_aviz_quiz_questions', true);
    $correct_answers = 0;
    $total_questions = count($questions);

    foreach ($answers as $answer) {
        $question_index = str_replace('question_', '', $answer['name']);
        $selected_answer = intval($answer['value']);

        if (isset($questions[$question_index]) && $questions[$question_index]['correct_answer'] === $selected_answer) {
            $correct_answers++;
        }
    }

    $score = ($correct_answers / $total_questions) * 100;

    // Save quiz results to database
    $result_id = wp_insert_post(array(
        'post_type' => 'aviz_quiz_result',
        'post_title' => 'תוצאת מבחן - ' . get_the_title($quiz_id),
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    ));

    if ($result_id) {
        update_post_meta($result_id, '_aviz_quiz_id', $quiz_id);
        update_post_meta($result_id, '_aviz_quiz_score', $score);
        update_post_meta($result_id, '_aviz_quiz_answers', $answers);
    }

    wp_send_json_success(array('score' => round($score, 2)));
}
add_action('wp_ajax_aviz_submit_quiz', 'aviz_submit_quiz');
add_action('wp_ajax_nopriv_aviz_submit_quiz', 'aviz_submit_quiz');
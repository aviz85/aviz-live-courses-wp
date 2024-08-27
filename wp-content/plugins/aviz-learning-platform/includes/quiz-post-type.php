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
    $allow_retake = get_post_meta($post->ID, '_aviz_quiz_allow_retake', true);
    $show_correct_answers = get_post_meta($post->ID, '_aviz_quiz_show_correct_answers', true);

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
    <p>
        <label for="aviz_quiz_allow_retake">
            <input type="checkbox" name="aviz_quiz_allow_retake" id="aviz_quiz_allow_retake" value="1" <?php checked($allow_retake, '1'); ?>>
            מותר לעשות את המבחן יותר מפעם אחת
        </label>
    </p>
    <p>
        <label for="aviz_quiz_show_correct_answers">
            <input type="checkbox" name="aviz_quiz_show_correct_answers" id="aviz_quiz_show_correct_answers" value="1" <?php checked($show_correct_answers, '1'); ?>>
            הצג תשובות נכונות בסוף המבחן
        </label>
    </p>
    <?php
}

function aviz_quiz_questions_callback($post) {
    wp_nonce_field('aviz_save_quiz_questions', 'aviz_quiz_questions_nonce');

    $questions = get_post_meta($post->ID, '_aviz_quiz_questions', true);
    ?>
    <div id="aviz-quiz-questions">
        <?php
        if (!empty($questions)) {
            foreach ($questions as $index => $question) {
                aviz_render_question_fields($index, $question);
            }
        }
        ?>
    </div>
    <button type="button" id="add-question" class="button">הוסף שאלה</button>

    <script type="text/template" id="question-template">
        <?php aviz_render_question_fields('{{INDEX}}'); ?>
    </script>
    <?php
}

function aviz_render_question_fields($index, $question = array()) {
    $question = wp_parse_args($question, array(
        'text' => '',
        'answers' => array('', '', '', ''),
        'correct_answer' => 0,
        'explanation' => ''
    ));
    ?>
    <div class="question" data-index="<?php echo esc_attr($index); ?>">
        <h3>שאלה <?php echo esc_html(is_numeric($index) ? (intval($index) + 1) : $index); ?></h3>
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
        <p>
            <label for="question_explanation_<?php echo esc_attr($index); ?>">הסבר על התשובה הנכונה:</label>
            <textarea name="aviz_quiz_questions[<?php echo esc_attr($index); ?>][explanation]" id="question_explanation_<?php echo esc_attr($index); ?>" rows="3" cols="50"><?php echo esc_textarea($question['explanation']); ?></textarea>
        </p>
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
        'aviz_quiz_time_limit',
        'aviz_quiz_allow_retake',
        'aviz_quiz_show_correct_answers'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = ($field === 'aviz_quiz_allow_retake' || $field === 'aviz_quiz_show_correct_answers') ? 
                     (isset($_POST[$field]) ? '1' : '0') : 
                     sanitize_text_field($_POST[$field]);
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

    // Save quiz questions
    if (isset($_POST['aviz_quiz_questions']) && is_array($_POST['aviz_quiz_questions'])) {
        $questions = array();
        foreach ($_POST['aviz_quiz_questions'] as $index => $question_data) {
            $questions[] = array(
                'text' => sanitize_textarea_field($question_data['text']),
                'answers' => array_map('sanitize_text_field', $question_data['answers']),
                'correct_answer' => intval($question_data['correct_answer']),
                'explanation' => sanitize_textarea_field($question_data['explanation'])
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
    $answers = isset($_POST['answers']) ? wp_parse_args($_POST['answers']) : array();
    $user_id = get_current_user_id();

    if (!$quiz_id || empty($answers) || !$user_id) {
        wp_send_json_error(array('message' => 'נתונים חסרים'));
    }

    $questions = get_post_meta($quiz_id, '_aviz_quiz_questions', true);
    $correct_answers = 0;
    $user_answers = array();

    foreach ($answers as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $question_index = substr($key, 9);
            $selected_answer = intval($value);
            $user_answers[$question_index] = $selected_answer;

            if (isset($questions[$question_index]) && intval($questions[$question_index]['correct_answer']) === $selected_answer) {
                $correct_answers++;
            }
        }
    }

    $total_questions = count($questions);
    $score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;

    // Get existing attempts or initialize new array
    $attempts = get_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_attempts', true);
    if (!is_array($attempts)) {
        $attempts = array();
    }

    // Add new attempt
    $attempts[] = array(
        'date' => current_time('mysql'),
        'score' => $score,
        'answers' => $user_answers
    );

    // Update user meta with new attempts
    update_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_attempts', $attempts);

    // Update latest score for backwards compatibility
    update_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_completed', true);
    update_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_score', $score);
    update_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_answers', $user_answers);
    update_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_completion_date', current_time('timestamp'));

    $show_correct_answers = get_post_meta($quiz_id, '_aviz_quiz_show_correct_answers', true);

    wp_send_json_success(array(
        'score' => round($score, 2),
        'show_correct_answers' => $show_correct_answers === '1',
        'correct_answers' => $correct_answers,
        'total_questions' => $total_questions
    ));
}
add_action('wp_ajax_aviz_submit_quiz', 'aviz_submit_quiz');

function aviz_add_quiz_reports_page() {
    add_submenu_page(
        'edit.php?post_type=aviz_quiz',
        'דוחות מבחנים',
        'דוחות',
        'manage_options',
        'aviz-quiz-reports',
        'aviz_quiz_reports_page'
    );
}
add_action('admin_menu', 'aviz_add_quiz_reports_page');

function aviz_quiz_reports_page() {
    ?>
    <div class="wrap">
        <h1>דוחות מבחנים</h1>
        <?php
        // בדיקה אם נבחר מבחן סציפי
        $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
        
        if ($quiz_id) {
            aviz_display_quiz_report($quiz_id);
        } else {
            aviz_display_quizzes_list();
        }
        ?>
    </div>
    <?php
}

function aviz_display_quizzes_list() {
    $quizzes = get_posts(array(
        'post_type' => 'aviz_quiz',
        'numberposts' => -1
    ));

    if (empty($quizzes)) {
        echo '<p>אין מבחנים זמינים.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>שם המבחן</th><th>מספר משתתפים</th><th>ציון ממוצע</th><th>פעולות</th></tr></thead>';
    echo '<tbody>';

    foreach ($quizzes as $quiz) {
        $participants = aviz_get_quiz_participants($quiz->ID);
        $average_score = aviz_get_quiz_average_score($quiz->ID);

        echo '<tr>';
        echo '<td>' . esc_html($quiz->post_title) . '</td>';
        echo '<td>' . count($participants) . '</td>';
        echo '<td>' . number_format($average_score, 2) . '%</td>';
        echo '<td><a href="' . admin_url('edit.php?post_type=aviz_quiz&page=aviz-quiz-reports&quiz_id=' . $quiz->ID) . '">צפה בדוח מפורט</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function aviz_display_quiz_report($quiz_id) {
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'aviz_quiz') {
        echo '<p>מבחן לא נמצא.</p>';
        return;
    }

    echo '<h2>' . esc_html($quiz->post_title) . '</h2>';

    // הוסף את הקריאה לפונקציית הלוג כאן
    aviz_log_quiz_meta_counts($quiz_id);

    $participants = aviz_get_quiz_participants($quiz_id);
    if (empty($participants)) {
        echo '<p>אין משתתפים במבחן זה.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>שם משתמש</th><th>מספר ניסיונות</th><th>ציון אחרון</th><th>ציון הטוב ביותר</th><th>תאריך אחרון</th></tr></thead>';
    echo '<tbody>';

    foreach ($participants as $user_id) {
        $user = get_userdata($user_id);
        $attempts = get_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_attempts', true);
        
        if (!is_array($attempts)) {
            $attempts = array();
        }

        $last_attempt = end($attempts);
        $best_score = 0;
        foreach ($attempts as $attempt) {
            if ($attempt['score'] > $best_score) {
                $best_score = $attempt['score'];
            }
        }

        echo '<tr>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . count($attempts) . '</td>';
        echo '<td>' . (isset($last_attempt['score']) ? number_format($last_attempt['score'], 2) : 'N/A') . '%</td>';
        echo '<td>' . number_format($best_score, 2) . '%</td>';
        echo '<td>' . (isset($last_attempt['date']) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_attempt['date'])) : 'N/A') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Add detailed view for each user
    foreach ($participants as $user_id) {
        $user = get_userdata($user_id);
        $attempts = get_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_attempts', true);

        if (!is_array($attempts)) {
            continue;
        }

        echo '<h3>פירוט ניסיונות: ' . esc_html($user->display_name) . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>תאריך</th><th>ציון</th></tr></thead>';
        echo '<tbody>';

        foreach ($attempts as $attempt) {
            echo '<tr>';
            echo '<td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($attempt['date'])) . '</td>';
            echo '<td>' . number_format($attempt['score'], 2) . '%</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

function aviz_get_quiz_participants($quiz_id) {
    global $wpdb;
    $meta_key = 'aviz_quiz_' . $quiz_id . '_attempts';
    return $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
        $meta_key
    ));
}

function aviz_get_quiz_average_score($quiz_id) {
    $participants = aviz_get_quiz_participants($quiz_id);
    if (empty($participants)) {
        return 0;
    }

    $total_score = 0;
    foreach ($participants as $user_id) {
        $attempts = get_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_attempts', true);
        if (is_array($attempts)) {
            $last_attempt = end($attempts);
            $total_score += floatval($last_attempt['score']);
        }
    }

    return $total_score / count($participants);
}

function aviz_enqueue_admin_styles($hook) {
    if ('aviz_quiz_page_aviz-quiz-reports' === $hook) {
        wp_enqueue_style('aviz-admin-style', plugins_url('assets/css/admin-style.css', dirname(__FILE__)));
    }
}
add_action('admin_enqueue_scripts', 'aviz_enqueue_admin_styles');

function aviz_reset_quiz() {
    check_ajax_referer('aviz_quiz_nonce', 'nonce');

    $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
    $user_id = get_current_user_id();

    if (!$quiz_id || !$user_id) {
        wp_send_json_error(array('message' => 'נתונים חסרים'));
    }

    // מחיקת המטא-דאטה של המבחן עבור המשתמש
    delete_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_completed');
    delete_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_score');
    delete_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_answers');
    delete_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_attempts');
    delete_user_meta($user_id, 'aviz_quiz_' . $quiz_id . '_completion_date');

    wp_send_json_success(array('message' => 'המבחן אופס בהצלחה'));
}
add_action('wp_ajax_aviz_reset_quiz', 'aviz_reset_quiz');

// הוסף את הפונקציה הזו בסוף הקובץ

function aviz_log_quiz_meta_counts($quiz_id) {
    global $wpdb;
    
    $meta_keys = array(
        'aviz_quiz_' . $quiz_id . '_completed',
        'aviz_quiz_' . $quiz_id . '_score',
        'aviz_quiz_' . $quiz_id . '_answers',
        'aviz_quiz_' . $quiz_id . '_attempts'
    );
    
    echo '<h3>לוג מספר רשומות מטא-דאטה:</h3>';
    echo '<ul>';
    
    foreach ($meta_keys as $meta_key) {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            $meta_key
        ));
        
        echo '<li>' . esc_html($meta_key) . ': ' . intval($count) . ' רשומות</li>';
    }
    
    echo '</ul>';
}
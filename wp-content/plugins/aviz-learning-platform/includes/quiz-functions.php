<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function aviz_quiz_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'aviz_quiz');
    $quiz_id = intval($atts['id']);

    if (!$quiz_id) {
        return 'מזהה מבחן לא תקין';
    }

    $questions = get_post_meta($quiz_id, 'aviz_quiz_questions', true);

    if (empty($questions)) {
        return 'לא נמצאו שאלות למבחן זה';
    }

    ob_start();
    include(plugin_dir_path(__FILE__) . '../templates/single-aviz_quiz.php');
    return ob_get_clean();
}
add_shortcode('aviz_quiz', 'aviz_quiz_shortcode');

function aviz_submit_quiz() {
    check_ajax_referer('aviz_quiz_nonce', 'nonce');

    $quiz_id = intval($_POST['quiz_id']);
    $answers = $_POST['answers'];
    parse_str($answers, $parsed_answers);

    $questions = get_post_meta($quiz_id, '_aviz_quiz_questions', true);
    $total_questions = count($questions);
    $correct_answers = 0;

    foreach ($questions as $index => $question) {
        $user_answer = isset($parsed_answers['question_' . $index]) ? intval($parsed_answers['question_' . $index]) : -1;
        if ($user_answer === intval($question['correct'])) {
            $correct_answers++;
        }
    }

    $score = ($correct_answers / $total_questions) * 100;
    $score = round($score, 2);

    // Save quiz result
    $user_id = get_current_user_id();
    $quiz_result = array(
        'quiz_id' => $quiz_id,
        'user_id' => $user_id,
        'score' => $score,
        'answers' => $parsed_answers,
        'date' => current_time('mysql')
    );
    add_user_meta($user_id, 'aviz_quiz_results', $quiz_result);

    // Mark quiz as completed
    $completed_quizzes = get_user_meta($user_id, 'aviz_completed_quizzes', true);
    if (!is_array($completed_quizzes)) {
        $completed_quizzes = array();
    }
    if (!in_array($quiz_id, $completed_quizzes)) {
        $completed_quizzes[] = $quiz_id;
        update_user_meta($user_id, 'aviz_completed_quizzes', $completed_quizzes);
    }

    wp_send_json_success(array('score' => $score));
}
add_action('wp_ajax_aviz_submit_quiz', 'aviz_submit_quiz');

function aviz_is_quiz_completed($quiz_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE quiz_id = %d AND user_id = %d",
        $quiz_id,
        $user_id
    ));
    
    return $result > 0;
}
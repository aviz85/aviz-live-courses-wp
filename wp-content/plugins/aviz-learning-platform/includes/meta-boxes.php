<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function aviz_add_quiz_meta_boxes() {
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
        <?php aviz_render_question_fields('{{INDEX}}'); ?>
    </script>
    <?php
}

function aviz_render_question_fields($index, $question = array()) {
    $question = wp_parse_args($question, array(
        'text' => '',
        'answers' => array('', '', '', ''),
        'correct' => 0,
        'explanation' => ''
    ));
    ?>
    <div class="question" data-index="<?php echo esc_attr($index); ?>">
        <h3>שאלה <?php echo $index + 1; ?></h3>
        <p>
            <label for="question-<?php echo $index; ?>-text">טקסט השאלה:</label>
            <textarea name="aviz_quiz_questions[<?php echo $index; ?>][text]" id="question-<?php echo $index; ?>-text" rows="3" cols="50"><?php echo esc_textarea($question['text']); ?></textarea>
        </p>
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <p>
                <label for="question-<?php echo $index; ?>-answer-<?php echo $i; ?>">תשובה <?php echo $i + 1; ?>:</label>
                <input type="text" name="aviz_quiz_questions[<?php echo $index; ?>][answers][]" id="question-<?php echo $index; ?>-answer-<?php echo $i; ?>" value="<?php echo esc_attr($question['answers'][$i]); ?>" />
            </p>
        <?php endfor; ?>
        <p>
            <label for="question-<?php echo $index; ?>-correct">תשובה נכונה:</label>
            <select name="aviz_quiz_questions[<?php echo $index; ?>][correct]" id="question-<?php echo $index; ?>-correct">
                <?php for ($i = 0; $i < 4; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($question['correct'], $i); ?>><?php echo $i + 1; ?></option>
                <?php endfor; ?>
            </select>
        </p>
        <p>
            <label for="question-<?php echo $index; ?>-explanation">הסבר:</label>
            <textarea name="aviz_quiz_questions[<?php echo $index; ?>][explanation]" id="question-<?php echo $index; ?>-explanation" rows="3" cols="50"><?php echo esc_textarea($question['explanation']); ?></textarea>
        </p>
        <button type="button" class="remove-question button">הסר שאלה</button>
    </div>
    <?php
}

function aviz_save_quiz_questions($post_id) {
    if (!isset($_POST['aviz_quiz_questions_nonce']) || !wp_verify_nonce($_POST['aviz_quiz_questions_nonce'], 'aviz_save_quiz_questions')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['aviz_quiz_questions'])) {
        $questions = $_POST['aviz_quiz_questions'];
        array_walk_recursive($questions, 'sanitize_text_field');
        update_post_meta($post_id, '_aviz_quiz_questions', $questions);
    }
}
add_action('save_post_aviz_quiz', 'aviz_save_quiz_questions');
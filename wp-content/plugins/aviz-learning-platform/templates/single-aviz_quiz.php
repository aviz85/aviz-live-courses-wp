<?php
get_header();

while (have_posts()) :
    the_post();
    $quiz_id = get_the_ID();
    $time_limit = get_post_meta($quiz_id, '_aviz_quiz_time_limit', true);
    ?>

    <div class="aviz-quiz-container">
        <h1><?php the_title(); ?></h1>
        
        <?php
        $questions = get_post_meta(get_the_ID(), '_aviz_quiz_questions', true);
        $time_limit = get_post_meta(get_the_ID(), '_aviz_quiz_time_limit', true);
        ?>

        <?php if (!empty($questions)) : ?>
            <form id="aviz-quiz-form" data-quiz-id="<?php echo get_the_ID(); ?>">
                <?php foreach ($questions as $index => $question) : ?>
                    <div class="aviz-quiz-question">
                        <h3>שאלה <?php echo $index + 1; ?></h3>
                        <p><?php echo esc_html($question['text']); ?></p>
                        <?php foreach ($question['answers'] as $answer_index => $answer) : ?>
                            <label>
                                <input type="radio" name="question_<?php echo $index; ?>" value="<?php echo $answer_index; ?>">
                                <?php echo esc_html($answer); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="aviz-quiz-submit">הגש מבחן</button>
            </form>
            <?php if ($time_limit > 0) : ?>
                <div id="aviz-quiz-timer">זמן נותר: <span><?php echo $time_limit; ?>:00</span></div>
            <?php endif; ?>
        <?php else : ?>
            <p>אין שאלות במבחן זה.</p>
        <?php endif; ?>

        <div id="aviz-quiz-result"></div>
    </div>

<?php
endwhile;

get_footer();
?>
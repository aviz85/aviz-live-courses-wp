<?php
get_header();

while (have_posts()) :
    the_post();
    $quiz_id = get_the_ID();
    $time_limit = get_post_meta($quiz_id, '_aviz_quiz_time_limit', true);
    $show_correct_answers = get_post_meta($quiz_id, '_aviz_quiz_show_correct_answers', true);
    $user_id = get_current_user_id();
    $quiz_completed = aviz_user_completed_quiz($user_id, $quiz_id);
    $last_score = $quiz_completed ? aviz_get_user_last_quiz_score($user_id, $quiz_id) : null;
    $last_attempt_date = $quiz_completed ? aviz_get_user_last_quiz_date($user_id, $quiz_id) : null;
    ?>

    <div class="aviz-quiz-container">
        <h1><?php the_title(); ?></h1>
        
        <?php
        $questions = get_post_meta($quiz_id, '_aviz_quiz_questions', true);
        ?>

        <?php if (!$quiz_completed) : ?>
            <form id="aviz-quiz-form" data-quiz-id="<?php echo $quiz_id; ?>">
                <?php foreach ($questions as $index => $question) : ?>
                    <div class="aviz-quiz-question">
                        <h3>שאלה <?php echo $index + 1; ?></h3>
                        <p><?php echo esc_html($question['text']); ?></p>
                        <?php foreach ($question['answers'] as $answer_index => $answer) : ?>
                            <label>
                                <input type="radio" name="question_<?php echo $index; ?>" value="<?php echo $answer_index; ?>" required>
                                <?php echo esc_html($answer); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="aviz-quiz-button">הגש מבחן</button>
            </form>
        <?php else : ?>
            <div id="aviz-quiz-result">
                <h2>תוצאות המבחן האחרון</h2>
                <p>הציון שלך: <?php echo number_format($last_score, 2); ?>%</p>
                <?php if ($last_attempt_date) : ?>
                    <p>תאריך: <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_attempt_date)); ?></p>
                <?php endif; ?>
            </div>

            <button id="retake-quiz" class="aviz-quiz-button">בצע את המבחן מחדש</button>

            <?php if ($show_correct_answers === '1') : ?>
                <button id="show-quiz-solution" class="aviz-quiz-button">הצג את הפתרון הנכון</button>
                <div id="aviz-quiz-answers" style="display: none;">
                    <?php 
                    $user_answers = aviz_get_user_last_quiz_answers($user_id, $quiz_id);
                    foreach ($questions as $index => $question) : 
                    ?>
                        <div class="aviz-quiz-question">
                            <h4>שאלה <?php echo $index + 1; ?></h4>
                            <p class="question-text"><?php echo esc_html($question['text']); ?></p>
                            
                            <?php 
                            $user_answer = isset($user_answers[$index]) ? $user_answers[$index] : null;
                            $correct_answer = $question['correct_answer'];
                            ?>

                            <ul class="aviz-quiz-answers">
                                <?php foreach ($question['answers'] as $answer_index => $answer) : ?>
                                    <li class="<?php 
                                        if ($answer_index == $correct_answer) {
                                            echo 'correct';
                                        } elseif ($answer_index == $user_answer && $user_answer != $correct_answer) {
                                            echo 'incorrect';
                                        }
                                    ?>">
                                        <?php echo esc_html($answer); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($user_answer != $correct_answer && !empty($question['explanation'])) : ?>
                                <div class="aviz-quiz-explanation">
                                    <strong>הסבר:</strong> <?php echo esc_html($question['explanation']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#show-quiz-solution').on('click', function() {
            $('#aviz-quiz-answers').slideToggle();
            $(this).text(function(i, text) {
                return text === "הצג את הפתרון הנכון" ? "הסתר את הפתרון" : "הצג את הפתרון הנכון";
            });
        });

        $('#retake-quiz').on('click', function() {
            $.ajax({
                url: aviz_quiz.ajax_url,
                type: 'POST',
                data: {
                    action: 'aviz_reset_quiz',
                    nonce: aviz_quiz.nonce,
                    quiz_id: <?php echo get_the_ID(); ?>
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('אירעה שגיאה בעת איפוס המבחן. אנא נסה שוב.');
                    }
                },
                error: function() {
                    alert('אירעה שגיאה בעת איפוס המבחן. אנא נסה שוב.');
                }
            });
        });

        $('#aviz-quiz-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var quizId = $(this).data('quiz-id');

            $.ajax({
                url: aviz_quiz.ajax_url,
                type: 'POST',
                data: {
                    action: 'aviz_submit_quiz',
                    nonce: aviz_quiz.nonce,
                    quiz_id: quizId,
                    answers: formData
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('אירעה שגיאה בעת שליחת המבחן. אנא נסה שוב.');
                    }
                },
                error: function() {
                    alert('אירעה שגיאה בעת שליחת המבחן. אנא נסה שוב.');
                }
            });
        });
    });
    </script>

<?php
endwhile;

get_footer();
?>
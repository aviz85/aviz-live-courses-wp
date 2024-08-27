jQuery(document).ready(function($) {
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
                    var resultHtml = '<p>הציון שלך: ' + response.data.score + '%</p>';
                    if (typeof response.data.correct_answers !== 'undefined' && typeof response.data.total_questions !== 'undefined') {
                        resultHtml += '<p>תשובות נכונות: ' + response.data.correct_answers + ' מתוך ' + response.data.total_questions + '</p>';
                    }
                    $('#aviz-quiz-result').html(resultHtml);
                    $('#aviz-quiz-form').hide();
                    console.log('Quiz submission response:', response); // For debugging
                } else {
                    console.error('Server error:', response);
                    $('#aviz-quiz-result').html('אירעה שגיאה: ' + (response.data ? response.data.message : 'שגיאה לא ידועה'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                $('#aviz-quiz-result').html('אירעה שגיאה בשליחת המבחן');
            }
        });
    });

    var $questionsContainer = $('#aviz-quiz-questions');
    var questionTemplate = $('#question-template').html();

    $('#add-question').on('click', function() {
        var index = $questionsContainer.children().length;
        var newQuestion = questionTemplate.replace(/\{\{INDEX\}\}/g, index);
        $questionsContainer.append(newQuestion);
        updateQuestionNumbers();
    });

    $questionsContainer.on('click', '.remove-question', function() {
        $(this).closest('.question').remove();
        updateQuestionNumbers();
    });

    function updateQuestionNumbers() {
        $('.question').each(function(index) {
            $(this).find('h3').text('שאלה ' + (index + 1));
            $(this).attr('data-index', index);
            $(this).find('input, textarea, select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                }
                var id = $(this).attr('id');
                if (id) {
                    $(this).attr('id', id.replace(/\_\d+\_/, '_' + index + '_'));
                }
            });
        });
    }
});
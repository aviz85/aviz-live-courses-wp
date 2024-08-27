jQuery(document).ready(function($) {
    console.log('Quiz admin script loaded');

    var $questionsContainer = $('#aviz-quiz-questions');
    console.log('Questions container:', $questionsContainer.length);

    var questionTemplate = $('#question-template').html();
    console.log('Question template:', questionTemplate);

    $('#add-question').on('click', function() {
        console.log('Add question button clicked');
        var index = $questionsContainer.children().length;
        var newQuestion = questionTemplate.replace(/\{\{INDEX\}\}/g, index);
        $questionsContainer.append(newQuestion);
        updateQuestionNumbers();
    });

    $questionsContainer.on('click', '.remove-question', function() {
        console.log('Remove question button clicked');
        $(this).closest('.question').remove();
        updateQuestionNumbers();
    });

    function updateQuestionNumbers() {
        console.log('Updating question numbers');
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
                    $(this).attr('id', id.replace(/\-\d+\-/, '-' + index + '-'));
                }
            });
        });
    }

    $('#aviz_quiz_course').on('change', function() {
        var courseId = $(this).val();
        var $chapterSelect = $('#aviz_quiz_chapter');

        if (courseId) {
            $.ajax({
                url: aviz_quiz_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aviz_get_chapters_for_course',
                    nonce: aviz_quiz_admin.nonce,
                    course_id: courseId
                },
                success: function(response) {
                    if (response.success) {
                        $chapterSelect.html(response.data);
                    } else {
                        console.error('Error fetching chapters:', response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                }
            });
        } else {
            $chapterSelect.html('<option value="">בחר פרק</option>');
        }
    });
});
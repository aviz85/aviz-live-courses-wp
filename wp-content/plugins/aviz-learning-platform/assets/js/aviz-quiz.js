jQuery(document).ready(function($) {
    var quizForm = $('#aviz-quiz-form');
    var quizTimer = $('#aviz-quiz-timer span');
    var timeLimit = quizTimer.length ? parseInt(quizTimer.text()) : 0;
    var timerInterval;

    if (timeLimit > 0) {
        startTimer();
    }

    function startTimer() {
        timerInterval = setInterval(function() {
            timeLimit--;
            var minutes = Math.floor(timeLimit / 60);
            var seconds = timeLimit % 60;
            quizTimer.text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);

            if (timeLimit <= 0) {
                clearInterval(timerInterval);
                quizForm.submit();
            }
        }, 1000);
    }

    quizForm.on('submit', function(e) {
        e.preventDefault();
        clearInterval(timerInterval);

        var formData = $(this).serializeArray();
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
                    $('#aviz-quiz-result').html('הציון שלך: ' + response.data.score + '%');
                    quizForm.hide();
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
});
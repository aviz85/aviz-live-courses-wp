jQuery(document).ready(function($) {
    function importQuestions(jsonData) {
        try {
            var quizData = JSON.parse(jsonData);
            
            // Clear existing questions
            $('#aviz-quiz-questions').empty();
            
            // Import new questions
            quizData.questions.forEach(function(question, index) {
                var template = $('#question-template').html();
                template = template.replace(/\{\{INDEX\}\}/g, index);
                var $question = $(template);
                
                $question.find('input[name$="[text]"]').val(question.text);
                question.answers.forEach(function(answer, i) {
                    $question.find('input[name$="[answers][' + i + ']"]').val(answer);
                });
                $question.find('select[name$="[correct]"]').val(question.correct);
                $question.find('textarea[name$="[explanation]"]').val(question.explanation);
                
                $('#aviz-quiz-questions').append($question);
            });
            
            // Set show answers checkbox
            $('#aviz_show_answers').prop('checked', quizData.show_answers);
            
            alert('השאלות יובאו בהצלחה!');
        } catch (error) {
            console.error("JSON parsing error:", error);
            console.log("Problematic JSON:", jsonData);
            alert('אירעה שגיאה בעת ניתוח ה-JSON. אנא בדוק את הפורמט ונסה שוב.');
        }
    }

    $('#aviz_import_json').on('click', function() {
        var jsonData = $('#aviz_json_import_textarea').val().trim();
        console.log("JSON Data:", jsonData);
        
        if (!jsonData) {
            alert('אנא הכנס JSON לפני הייבוא.');
            return;
        }
        
        importQuestions(jsonData);
    });

    $('#aviz_copy_json_example').on('click', function() {
        var $temp = $("<textarea>");
        $("body").append($temp);
        $temp.val(avizJsonExample).select();
        document.execCommand("copy");
        $temp.remove();
        alert('הדוגמה הועתקה ללוח!');
    });

    $('#aviz_json_import_textarea').on('input', function() {
        console.log("Textarea value changed:", $(this).val());
    });
});
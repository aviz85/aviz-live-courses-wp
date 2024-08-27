<?php
function aviz_quiz_reports_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    // בדיקה אם נבחר מבחן ספציפי
    $selected_quiz = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

    // שאילתה לקבלת כל המבחנים
    $quizzes = $wpdb->get_results("SELECT DISTINCT quiz_id FROM $table_name");

    // שאילתה לקבלת התוצאות
    $query = "SELECT r.*, u.display_name, p.post_title as quiz_title 
              FROM $table_name r
              JOIN {$wpdb->users} u ON r.user_id = u.ID
              JOIN {$wpdb->posts} p ON r.quiz_id = p.ID";
    
    if ($selected_quiz) {
        $query .= $wpdb->prepare(" WHERE r.quiz_id = %d", $selected_quiz);
    }
    
    $results = $wpdb->get_results($query);

    // הצגת הדוח
    ?>
    <div class="wrap">
        <h1>דוחות מבחנים</h1>
        
        <form method="get">
            <input type="hidden" name="page" value="aviz-quiz-reports">
            <select name="quiz_id">
                <option value="">כל המבחנים</option>
                <?php foreach ($quizzes as $quiz) : ?>
                    <option value="<?php echo $quiz->quiz_id; ?>" <?php selected($selected_quiz, $quiz->quiz_id); ?>>
                        <?php echo get_the_title($quiz->quiz_id); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="סנן" class="button">
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>משתמש</th>
                    <th>מבחן</th>
                    <th>ציון</th>
                    <th>תאריך</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result) : ?>
                    <tr>
                        <td><?php echo $result->display_name; ?></td>
                        <td><?php echo $result->quiz_title; ?></td>
                        <td><?php echo $result->score; ?>%</td>
                        <td><?php echo $result->date_taken; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=aviz-quiz-report-detail&id=' . $result->id); ?>">
                                צפה בפרטים
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php aviz_quiz_statistics($results); ?>

        <?php aviz_add_export_button(); ?>
    </div>
    <?php
}

function aviz_quiz_report_detail_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $result_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $result_id));

    if (!$result) {
        wp_die('תוצאת מבחן לא נמצאה');
    }

    $quiz = get_post($result->quiz_id);
    $user = get_user_by('id', $result->user_id);
    $questions = get_post_meta($result->quiz_id, '_aviz_quiz_questions', true);
    $user_answers = maybe_unserialize($result->answers);

    ?>
    <div class="wrap">
        <h1>פרטי מבחן: <?php echo $quiz->post_title; ?></h1>
        <p><strong>משתמש:</strong> <?php echo $user->display_name; ?></p>
        <p><strong>ציון:</strong> <?php echo $result->score; ?>%</p>
        <p><strong>תאריך:</strong> <?php echo $result->date_taken; ?></p>

        <h2>תשובות</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>שאלה</th>
                    <th>תשובת המשתמש</th>
                    <th>תשובה ��כונה</th>
                    <th>נכון/לא נכון</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $index => $question) : ?>
                    <tr>
                        <td><?php echo $question['text']; ?></td>
                        <td><?php echo $question['answers'][$user_answers['question_' . $index]]; ?></td>
                        <td><?php echo $question['answers'][$question['correct']]; ?></td>
                        <td><?php echo ($user_answers['question_' . $index] == $question['correct']) ? 'נכון' : 'לא נכון'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function aviz_add_admin_menu() {
    add_menu_page('דוחות מבחנים', 'דוחות מבחנים', 'manage_options', 'aviz-quiz-reports', 'aviz_quiz_reports_page', 'dashicons-chart-bar', 30);
    add_submenu_page(null, 'פרטי מבחן', 'פרטי מבחן', 'manage_options', 'aviz-quiz-report-detail', 'aviz_quiz_report_detail_page');
}
add_action('admin_menu', 'aviz_add_admin_menu');

function aviz_export_quiz_results() {
    if (isset($_GET['action']) && $_GET['action'] == 'export_quiz_results') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aviz_quiz_results';

        $results = $wpdb->get_results("
            SELECT r.*, u.display_name, p.post_title as quiz_title 
            FROM $table_name r
            JOIN {$wpdb->users} u ON r.user_id = u.ID
            JOIN {$wpdb->posts} p ON r.quiz_id = p.ID
        ");

        // הגדרת כותרות הקובץ
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=quiz_results.csv');

        // פתיחת ה-output stream
        $output = fopen('php://output', 'w');

        // הוספת ה-BOM לתמיכה ב-UTF-8 ב-Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // כתיבת כותרות העמודות
        fputcsv($output, array('משתמש', 'מבחן', 'ציון', 'תאריך'));

        // כתיבת הנתונים
        foreach ($results as $result) {
            fputcsv($output, array(
                $result->display_name,
                $result->quiz_title,
                $result->score,
                $result->date_taken
            ));
        }

        fclose($output);
        exit;
    }
}
add_action('admin_init', 'aviz_export_quiz_results');

// הוסף כפתור ייצוא לעמוד הדוחות
function aviz_add_export_button() {
    $export_url = add_query_arg('action', 'export_quiz_results', admin_url('admin.php?page=aviz-quiz-reports'));
    echo '<a href="' . $export_url . '" class="button">ייצא לאקסל</a>';
}

function aviz_quiz_statistics($results) {
    $total_quizzes = count($results);
    $total_score = 0;
    $score_distribution = array(
        '0-20' => 0,
        '21-40' => 0,
        '41-60' => 0,
        '61-80' => 0,
        '81-100' => 0
    );

    foreach ($results as $result) {
        $total_score += $result->score;
        if ($result->score <= 20) $score_distribution['0-20']++;
        elseif ($result->score <= 40) $score_distribution['21-40']++;
        elseif ($result->score <= 60) $score_distribution['41-60']++;
        elseif ($result->score <= 80) $score_distribution['61-80']++;
        else $score_distribution['81-100']++;
    }

    $average_score = $total_quizzes > 0 ? round($total_score / $total_quizzes, 2) : 0;

    ?>
    <h2>סטטיסטיקות</h2>
    <p><strong>מספר מבחנים שבוצעו:</strong> <?php echo $total_quizzes; ?></p>
    <p><strong>ציון ממוצע:</strong> <?php echo $average_score; ?>%</p>
    
    <h3>התפלגות ציונים</h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>טווח ציונים</th>
                <th>מספר מבחנים</th>
                <th>אחוז</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($score_distribution as $range => $count) : ?>
                <tr>
                    <td><?php echo $range; ?></td>
                    <td><?php echo $count; ?></td>
                    <td><?php echo round(($count / $total_quizzes) * 100, 2); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// הוסף את הפונקציות הבאות לקובץ הקיים

function aviz_quiz_type_report_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
    if (!$quiz_id) {
        wp_die('מזהה מבחן לא תקין');
    }

    $quiz = get_post($quiz_id);
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE quiz_id = %d ORDER BY date_taken DESC",
        $quiz_id
    ));

    $questions = get_post_meta($quiz_id, '_aviz_quiz_questions', true);

    ?>
    <div class="wrap">
        <h1>דוח מפורט: <?php echo $quiz->post_title; ?></h1>

        <?php aviz_quiz_type_statistics($results); ?>

        <h2>ניתוח שאלות</h2>
        <?php aviz_quiz_question_analysis($results, $questions); ?>

        <h2>מגמות לאורך זמן</h2>
        <?php aviz_quiz_time_trends($results); ?>
    </div>
    <?php
}

function aviz_quiz_type_statistics($results) {
    $total_attempts = count($results);
    $total_score = 0;
    $score_distribution = array_fill(0, 10, 0); // 0-10, 11-20, ..., 91-100

    foreach ($results as $result) {
        $total_score += $result->score;
        $score_distribution[floor($result->score / 10)]++;
    }

    $average_score = $total_attempts > 0 ? round($total_score / $total_attempts, 2) : 0;

    ?>
    <h2>סטטיסטיקות כלליות</h2>
    <p><strong>מספר ניסיונות:</strong> <?php echo $total_attempts; ?></p>
    <p><strong>ציון ממוצע:</strong> <?php echo $average_score; ?>%</p>

    <h3>התפלגות ציונים</h3>
    <canvas id="scoreDistributionChart" width="400" height="200"></canvas>
    <table id="scoreDistributionTable" style="display:none;">
        <thead>
            <tr>
                <th>טווח ציונים</th>
                <th>מספר ניסיונות</th>
                <th>אחוז</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i < 10; $i++) : ?>
                <tr>
                    <td><?php echo ($i * 10 + 1) . '-' . (($i + 1) * 10); ?></td>
                    <td><?php echo $score_distribution[$i]; ?></td>
                    <td><?php echo round(($score_distribution[$i] / $total_attempts) * 100, 2); ?>%</td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    <?php
}

function aviz_quiz_question_analysis($results, $questions) {
    $question_stats = array();

    foreach ($questions as $index => $question) {
        $question_stats[$index] = array(
            'text' => $question['text'],
            'correct' => 0,
            'total' => 0
        );
    }

    foreach ($results as $result) {
        $answers = maybe_unserialize($result->answers);
        foreach ($answers as $q_index => $answer) {
            $q_num = substr($q_index, 9); // Remove 'question_' prefix
            $question_stats[$q_num]['total']++;
            if ($answer == $questions[$q_num]['correct']) {
                $question_stats[$q_num]['correct']++;
            }
        }
    }

    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>שאלה</th>
                <th>אחוז תשובות נכונות</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($question_stats as $stat) : ?>
                <tr>
                    <td><?php echo esc_html($stat['text']); ?></td>
                    <td><?php echo $stat['total'] > 0 ? round(($stat['correct'] / $stat['total']) * 100, 2) : 0; ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function aviz_quiz_time_trends($results) {
    $monthly_stats = array();

    foreach ($results as $result) {
        $month = date('Y-m', strtotime($result->date_taken));
        if (!isset($monthly_stats[$month])) {
            $monthly_stats[$month] = array('total' => 0, 'sum' => 0);
        }
        $monthly_stats[$month]['total']++;
        $monthly_stats[$month]['sum'] += $result->score;
    }

    ksort($monthly_stats);

    ?>
    <canvas id="timeTrendsChart" width="400" height="200"></canvas>
    <table id="timeTrendsTable" style="display:none;">
        <thead>
            <tr>
                <th>חודש</th>
                <th>מספר ניסיונות</th>
                <th>ציון ממוצע</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly_stats as $month => $stat) : ?>
                <tr>
                    <td><?php echo $month; ?></td>
                    <td><?php echo $stat['total']; ?></td>
                    <td><?php echo round($stat['sum'] / $stat['total'], 2); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// הוסף את הדף החדש לתפריט
function aviz_add_quiz_type_report_page() {
    add_submenu_page(
        null,
        'דוח מפורט למבחן',
        'דוח מפורט למבחן',
        'manage_options',
        'aviz-quiz-type-report',
        'aviz_quiz_type_report_page'
    );
}
add_action('admin_menu', 'aviz_add_quiz_type_report_page');

// עדכן את הטבלה הראשית בדף הדוחות כדי לכלול קישור לדוח המפורט
function aviz_update_reports_table($results) {
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>מבחן</th>
                <th>מספר ניסיונות</th>
                <th>ציון ממוצע</th>
                <th>פעולות</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $quiz_stats = array();
            foreach ($results as $result) {
                if (!isset($quiz_stats[$result->quiz_id])) {
                    $quiz_stats[$result->quiz_id] = array(
                        'title' => $result->quiz_title,
                        'attempts' => 0,
                        'total_score' => 0
                    );
                }
                $quiz_stats[$result->quiz_id]['attempts']++;
                $quiz_stats[$result->quiz_id]['total_score'] += $result->score;
            }
            foreach ($quiz_stats as $quiz_id => $stat) :
            ?>
                <tr>
                    <td><?php echo $stat['title']; ?></td>
                    <td><?php echo $stat['attempts']; ?></td>
                    <td><?php echo round($stat['total_score'] / $stat['attempts'], 2); ?>%</td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=aviz-quiz-type-report&quiz_id=' . $quiz_id); ?>">
                            צפה בדוח מפורט
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// עדכן את הפונקציה aviz_quiz_reports_page כדי להשתמש בטבלה החדשה
function aviz_quiz_reports_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aviz_quiz_results';

    $results = $wpdb->get_results("
        SELECT r.*, p.post_title as quiz_title 
        FROM $table_name r
        JOIN {$wpdb->posts} p ON r.quiz_id = p.ID
    ");

    ?>
    <div class="wrap">
        <h1>דוחות מבחנים</h1>
        <?php aviz_add_export_button(); ?>
        <?php aviz_update_reports_table($results); ?>
        <?php aviz_quiz_statistics($results); ?>
    </div>
    <?php
}
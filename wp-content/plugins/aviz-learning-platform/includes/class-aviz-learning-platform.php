<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Aviz_Learning_Platform {
    private $version;

    public function __construct() {
        $this->version = '1.0.0'; // Set your plugin version
        
        add_action('init', array($this, 'register_post_types'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('single_template', array($this, 'register_templates'));
        add_action('wp_ajax_aviz_submit_quiz', array($this, 'handle_quiz_submission'));
        add_action('wp_ajax_nopriv_aviz_submit_quiz', array($this, 'handle_quiz_submission'));
    }

    public function enqueue_scripts($hook) {
        // Frontend scripts
        if (!is_admin()) {
            wp_enqueue_script('aviz-quiz', plugin_dir_url(__FILE__) . '../assets/js/aviz-quiz.js', array('jquery'), $this->version, true);
            wp_localize_script('aviz-quiz', 'aviz_quiz', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aviz_quiz_nonce')
            ));
        }
        
        // Admin scripts
        if (is_admin()) {
            $screen = get_current_screen();
            if ($screen && ($screen->post_type === 'aviz_quiz' || $screen->id === 'aviz_quiz')) {
                wp_enqueue_script('aviz-admin-quiz', plugin_dir_url(__FILE__) . '../assets/js/admin-quiz.js', array('jquery'), $this->version, true);
                wp_localize_script('aviz-admin-quiz', 'aviz_admin_quiz', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('aviz_admin_quiz_nonce')
                ));
                
                // Debug information
                error_log('Enqueuing admin quiz script');
                error_log('Script URL: ' . plugin_dir_url(__FILE__) . '../assets/js/admin-quiz.js');
                error_log('Current screen: ' . $screen->id);
            }
        }
    }

    public function register_post_types() {
        register_post_type('aviz_quiz', array(
            'labels' => array(
                'name' => 'מבחנים',
                'singular_name' => 'מבחן',
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'quizzes'),
        ));
    }

    public function register_templates($template) {
        if (is_singular('aviz_quiz')) {
            $new_template = plugin_dir_path(__FILE__) . '../templates/single-aviz_quiz.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    public function handle_quiz_submission() {
        check_ajax_referer('aviz_quiz_nonce', 'nonce');
        
        $quiz_id = intval($_POST['quiz_id']);
        $answers = $_POST['answers'];
        $user_id = get_current_user_id();
        
        $score = $this->calculate_quiz_score($quiz_id, $answers);
        
        $this->save_quiz_result($quiz_id, $user_id, $score);
        
        wp_send_json_success(array('score' => $score));
    }

    private function calculate_quiz_score($quiz_id, $answers) {
        // Implement your score calculation logic here
        return 80; // Example score
    }

    private function save_quiz_result($quiz_id, $user_id, $score) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aviz_quiz_results';
        
        $wpdb->insert(
            $table_name,
            array(
                'quiz_id' => $quiz_id,
                'user_id' => $user_id,
                'score' => $score,
                'date_taken' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s')
        );
    }
}
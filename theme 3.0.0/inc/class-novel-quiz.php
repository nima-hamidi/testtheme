<?php
/**
 * Novel Quiz Battle System
 * 
 * سیستم مسابقه کتابخوانی آنلاین با سوالات چندگزینه‌ای،
 * شمارش معکوس، امتیازدهی بر اساس سرعت، لیدربورد، جایزه سکه
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Quiz {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Shortcodes
        add_shortcode('novel_quiz', [$this, 'render_quiz']);
        add_shortcode('novel_quizzes', [$this, 'render_quiz_list']);
        add_shortcode('novel_quiz_leaderboard', [$this, 'render_leaderboard']);

        // AJAX
        add_action('wp_ajax_novel_start_quiz', [$this, 'ajax_start']);
        add_action('wp_ajax_novel_answer_question', [$this, 'ajax_answer']);
        add_action('wp_ajax_novel_finish_quiz', [$this, 'ajax_finish']);
        add_action('wp_ajax_novel_get_quiz_status', [$this, 'ajax_status']);

        // Cron
        add_action('novel_cron_daily_quiz', [$this, 'generate_daily_quiz']);
        if (!wp_next_scheduled('novel_cron_daily_quiz')) {
            $start_hour = (int)get_option('novel_quiz_daily_hour', 0);
            $next = strtotime("today {$start_hour}:00");
            if ($next < time()) $next = strtotime("tomorrow {$start_hour}:00");
            wp_schedule_event($next, 'daily', 'novel_cron_daily_quiz');
        }

        add_action('novel_cron_weekly_quiz', [$this, 'generate_weekly_quiz']);
        if (!wp_next_scheduled('novel_cron_weekly_quiz')) {
            wp_schedule_event(strtotime('next saturday'), 'weekly', 'novel_cron_weekly_quiz');
        }

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * جداول
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quizzes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(300) NOT NULL,
            description TEXT NULL,
            novel_id BIGINT UNSIGNED NULL,
            chapter_range_start INT UNSIGNED NULL,
            chapter_range_end INT UNSIGNED NULL,
            difficulty ENUM('easy','medium','hard','mixed') DEFAULT 'medium',
            time_per_question INT UNSIGNED DEFAULT 30,
            total_questions INT UNSIGNED DEFAULT 10,
            reward_coins_1st INT UNSIGNED DEFAULT 20,
            reward_coins_2nd INT UNSIGNED DEFAULT 10,
            reward_coins_3rd INT UNSIGNED DEFAULT 5,
            reward_coins_participation INT UNSIGNED DEFAULT 1,
            max_participants INT UNSIGNED DEFAULT 0,
            quiz_type ENUM('daily','weekly','event','custom') DEFAULT 'custom',
            start_time DATETIME NULL,
            end_time DATETIME NULL,
            is_repeatable TINYINT DEFAULT 0,
            status ENUM('draft','active','closed','archived') DEFAULT 'draft',
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_novel (novel_id),
            INDEX idx_status (status),
            INDEX idx_type (quiz_type),
            INDEX idx_start (start_time)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quiz_questions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quiz_id BIGINT UNSIGNED NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('multiple','true_false') DEFAULT 'multiple',
            option_a VARCHAR(500) NOT NULL,
            option_b VARCHAR(500) NOT NULL,
            option_c VARCHAR(500) NULL,
            option_d VARCHAR(500) NULL,
            correct_answer ENUM('a','b','c','d') NOT NULL,
            explanation TEXT NULL,
            chapter_reference INT UNSIGNED NULL,
            difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
            points INT UNSIGNED DEFAULT 10,
            sort_order INT UNSIGNED DEFAULT 0,
            INDEX idx_quiz (quiz_id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quiz_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quiz_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            score INT UNSIGNED DEFAULT 0,
            total_points INT UNSIGNED DEFAULT 0,
            correct_count INT UNSIGNED DEFAULT 0,
            wrong_count INT UNSIGNED DEFAULT 0,
            time_spent INT UNSIGNED DEFAULT 0,
            coins_earned INT UNSIGNED DEFAULT 0,
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            status ENUM('in_progress','completed','abandoned') DEFAULT 'in_progress',
            INDEX idx_quiz_user (quiz_id, user_id),
            INDEX idx_user (user_id),
            INDEX idx_score (quiz_id, score DESC)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quiz_answers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attempt_id BIGINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            user_answer ENUM('a','b','c','d') NULL,
            is_correct TINYINT DEFAULT 0,
            time_spent INT UNSIGNED DEFAULT 0,
            points_earned INT UNSIGNED DEFAULT 0,
            answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_attempt (attempt_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Assets
     */
    public function enqueue_assets() {
        if (!$this->should_load()) return;

        wp_enqueue_style('novel-quiz', get_template_directory_uri() . '/assets/css/quiz.css', [], JEsuspended_DEVELOPER_VERSION);
        wp_enqueue_script('novel-quiz', get_template_directory_uri() . '/assets/js/quiz.js', ['jquery'], JEsuspended_DEVELOPER_VERSION, true);

        wp_localize_script('novel-quiz', 'NovelQuiz', [
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('novel_quiz_nonce'),
            'is_logged' => is_user_logged_in(),
            'strings'   => [
                'correct'       => '✅ صحیح!',
                'wrong'         => '❌ اشتباه!',
                'timeout'       => '⏰ وقت تمام شد!',
                'loading'       => 'در حال بارگذاری...',
                'next'          => 'سوال بعدی',
                'finish'        => 'پایان مسابقه',
                'login'         => 'برای شرکت ابتدا وارد شوید.',
                'error'         => 'خطایی رخ داد.',
                'confirm_start' => 'آیا آماده شروع مسابقه هستید؟',
                'points'        => 'امتیاز',
                'rank'          => 'رتبه',
            ],
        ]);
    }

    private function should_load() {
        global $post;
        if (!$post) return false;
        if (has_shortcode($post->post_content, 'novel_quiz')) return true;
        if (has_shortcode($post->post_content, 'novel_quizzes')) return true;
        if (has_shortcode($post->post_content, 'novel_quiz_leaderboard')) return true;
        if (is_singular('novel')) return true;
        if (is_front_page()) return true;
        return false;
    }

    // ═══════════════════════════════════════
    // CRUD
    // ═══════════════════════════════════════

    public function get_quiz($id) {
        global $wpdb;
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}quizzes WHERE id = %d", $id
        ));
        if (!$quiz) return null;

        $quiz->participant_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}quiz_attempts WHERE quiz_id = %d AND status = 'completed'", $id
        ));
        $quiz->effective_status = $this->get_effective_status($quiz);
        return $quiz;
    }

    private function get_effective_status($quiz) {
        if ($quiz->status === 'draft' || $quiz->status === 'archived') return $quiz->status;
        $now = current_time('mysql');
        if ($quiz->start_time && $now < $quiz->start_time) return 'upcoming';
        if ($quiz->end_time && $now > $quiz->end_time) return 'closed';
        if ($quiz->status === 'closed') return 'closed';
        return 'active';
    }

    public function get_questions($quiz_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}quiz_questions WHERE quiz_id = %d ORDER BY sort_order ASC", $quiz_id
        ));
    }

    public function get_quizzes($args = []) {
        global $wpdb;
        $defaults = ['status' => 'active', 'type' => '', 'novel_id' => 0, 'limit' => 10, 'page' => 1];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE q.status != 'draft'";
        $now = current_time('mysql');

        if ($args['status'] === 'active') {
            $where .= " AND q.status = 'active' AND (q.start_time IS NULL OR q.start_time <= '{$now}') AND (q.end_time IS NULL OR q.end_time > '{$now}')";
        } elseif ($args['status'] === 'closed') {
            $where .= " AND (q.status = 'closed' OR (q.end_time IS NOT NULL AND q.end_time <= '{$now}'))";
        }
        if ($args['type']) $where .= $wpdb->prepare(" AND q.quiz_type = %s", $args['type']);
        if ($args['novel_id']) $where .= $wpdb->prepare(" AND q.novel_id = %d", $args['novel_id']);

        $offset = ($args['page'] - 1) * $args['limit'];

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quizzes q {$where}");

        $quizzes = $wpdb->get_results(
            "SELECT q.*, (SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}quiz_attempts WHERE quiz_id = q.id AND status='completed') as participant_count
             FROM {$wpdb->prefix}quizzes q {$where}
             ORDER BY q.created_at DESC LIMIT {$args['limit']} OFFSET {$offset}"
        );

        foreach ($quizzes as &$q) $q->effective_status = $this->get_effective_status($q);

        return ['quizzes' => $quizzes, 'total' => (int)$total, 'pages' => ceil($total / $args['limit'])];
    }

    public function get_daily_quiz() {
        global $wpdb;
        $today = current_time('Y-m-d');
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}quizzes WHERE quiz_type = 'daily' AND DATE(created_at) = %s AND status = 'active' LIMIT 1", $today
        ));
        return $id ? $this->get_quiz($id) : null;
    }

    // ═══════════════════════════════════════
    // Attempt Management
    // ═══════════════════════════════════════

    public function has_attempted($quiz_id, $user_id = null) {
        global $wpdb;
        if (!$user_id) $user_id = get_current_user_id();
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}quiz_attempts WHERE quiz_id = %d AND user_id = %d AND status = 'completed'",
            $quiz_id, $user_id
        ));
    }

    public function get_active_attempt($quiz_id, $user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}quiz_attempts WHERE quiz_id = %d AND user_id = %d AND status = 'in_progress' ORDER BY started_at DESC LIMIT 1",
            $quiz_id, $user_id
        ));
    }

    public function get_attempt_result($attempt_id) {
        global $wpdb;
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, u.display_name FROM {$wpdb->prefix}quiz_attempts a
             INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.id = %d", $attempt_id
        ));
        if (!$attempt) return null;

        $attempt->answers = $wpdb->get_results($wpdb->prepare(
            "SELECT qa.*, qq.question_text, qq.option_a, qq.option_b, qq.option_c, qq.option_d,
                    qq.correct_answer, qq.explanation, qq.chapter_reference
             FROM {$wpdb->prefix}quiz_answers qa
             INNER JOIN {$wpdb->prefix}quiz_questions qq ON qa.question_id = qq.id
             WHERE qa.attempt_id = %d ORDER BY qa.answered_at ASC", $attempt_id
        ));

        return $attempt;
    }

    public function get_leaderboard_data($quiz_id, $limit = 20) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name,
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = a.user_id AND meta_key = 'novel_avatar' LIMIT 1) as avatar_id,
                    RANK() OVER (ORDER BY a.score DESC, a.time_spent ASC) as ranking
             FROM {$wpdb->prefix}quiz_attempts a
             INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.quiz_id = %d AND a.status = 'completed'
             ORDER BY a.score DESC, a.time_spent ASC
             LIMIT %d", $quiz_id, $limit
        ));
    }

    public function get_user_rank($quiz_id, $user_id) {
        global $wpdb;
        $user_score = $wpdb->get_row($wpdb->prepare(
            "SELECT score, time_spent FROM {$wpdb->prefix}quiz_attempts WHERE quiz_id = %d AND user_id = %d AND status = 'completed' LIMIT 1",
            $quiz_id, $user_id
        ));
        if (!$user_score) return null;

        $rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM {$wpdb->prefix}quiz_attempts
             WHERE quiz_id = %d AND status = 'completed'
             AND (score > %d OR (score = %d AND time_spent < %d))",
            $quiz_id, $user_score->score, $user_score->score, $user_score->time_spent
        ));

        return (int)$rank;
    }

    // ═══════════════════════════════════════
    // AJAX Handlers
    // ═══════════════════════════════════════

    public function ajax_start() {
        check_ajax_referer('novel_quiz_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'ابتدا وارد شوید.']);

        $user_id = get_current_user_id();
        $quiz_id = absint($_POST['quiz_id'] ?? 0);
        $quiz = $this->get_quiz($quiz_id);

        if (!$quiz || $quiz->effective_status !== 'active') wp_send_json_error(['message' => 'مسابقه فعال نیست.']);
        if (!$quiz->is_repeatable && $this->has_attempted($quiz_id, $user_id)) wp_send_json_error(['message' => 'قبلاً شرکت کرده‌اید.']);

        // بررسی attempt in_progress
        $active = $this->get_active_attempt($quiz_id, $user_id);
        if ($active) {
            // ادامه attempt قبلی
            $answered = $this->get_answered_count($active->id);
            $questions = $this->get_questions($quiz_id);
            $next_index = $answered;

            if ($next_index >= count($questions)) {
                wp_send_json_error(['message' => 'تمام سوالات پاسخ داده شده.']);
            }

            $q = $questions[$next_index];
            wp_send_json_success([
                'attempt_id'      => (int)$active->id,
                'question_index'  => $next_index,
                'total_questions' => count($questions),
                'question'        => $this->format_question($q, $quiz),
                'current_score'   => (int)$active->score,
            ]);
            return;
        }

        global $wpdb;

        // ساخت attempt جدید
        $wpdb->insert("{$wpdb->prefix}quiz_attempts", [
            'quiz_id' => $quiz_id,
            'user_id' => $user_id,
            'total_points' => $this->get_total_points($quiz_id),
        ]);
        $attempt_id = $wpdb->insert_id;

        $questions = $this->get_questions($quiz_id);
        if (empty($questions)) wp_send_json_error(['message' => 'سوالی یافت نشد.']);

        $q = $questions[0];

        wp_send_json_success([
            'attempt_id'      => (int)$attempt_id,
            'question_index'  => 0,
            'total_questions' => count($questions),
            'question'        => $this->format_question($q, $quiz),
            'current_score'   => 0,
        ]);
    }

    public function ajax_answer() {
        check_ajax_referer('novel_quiz_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $user_id    = get_current_user_id();
        $attempt_id = absint($_POST['attempt_id'] ?? 0);
        $question_id = absint($_POST['question_id'] ?? 0);
        $answer     = sanitize_text_field($_POST['answer'] ?? '');
        $time_spent = absint($_POST['time_spent'] ?? 0);

        global $wpdb;

        // Verify attempt
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}quiz_attempts WHERE id = %d AND user_id = %d AND status = 'in_progress'",
            $attempt_id, $user_id
        ));
        if (!$attempt) wp_send_json_error(['message' => 'Attempt نامعتبر.']);

        // Verify question
        $question = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}quiz_questions WHERE id = %d AND quiz_id = %d",
            $question_id, $attempt->quiz_id
        ));
        if (!$question) wp_send_json_error(['message' => 'سوال نامعتبر.']);

        // Check duplicate answer
        $already = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}quiz_answers WHERE attempt_id = %d AND question_id = %d",
            $attempt_id, $question_id
        ));
        if ($already) wp_send_json_error(['message' => 'این سوال قبلاً پاسخ داده شده.']);

        $quiz = $this->get_quiz($attempt->quiz_id);
        $is_correct = ($answer === $question->correct_answer);

        // محاسبه امتیاز بر اساس سرعت
        $points_earned = 0;
        if ($is_correct) {
            $max_time = (int)$quiz->time_per_question;
            $actual_time = min($time_spent, $max_time);
            $time_ratio = max(0, ($max_time - $actual_time) / $max_time);
            $points_earned = max(1, round((int)$question->points * (0.5 + 0.5 * $time_ratio)));
        }

        // ثبت پاسخ
        $wpdb->insert("{$wpdb->prefix}quiz_answers", [
            'attempt_id'    => $attempt_id,
            'question_id'   => $question_id,
            'user_answer'   => in_array($answer, ['a','b','c','d']) ? $answer : null,
            'is_correct'    => $is_correct ? 1 : 0,
            'time_spent'    => $time_spent,
            'points_earned' => $points_earned,
        ]);

        // بروزرسانی attempt
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}quiz_attempts SET
             score = score + %d,
             correct_count = correct_count + %d,
             wrong_count = wrong_count + %d,
             time_spent = time_spent + %d
             WHERE id = %d",
            $points_earned,
            $is_correct ? 1 : 0,
            $is_correct ? 0 : 1,
            $time_spent,
            $attempt_id
        ));

        // سوال بعدی
        $answered_count = $this->get_answered_count($attempt_id);
        $questions = $this->get_questions($attempt->quiz_id);
        $is_last = ($answered_count >= count($questions));

        $response = [
            'is_correct'    => $is_correct,
            'correct_answer' => $question->correct_answer,
            'explanation'   => $question->explanation ?: '',
            'points_earned' => $points_earned,
            'current_score' => (int)$attempt->score + $points_earned,
            'is_last'       => $is_last,
        ];

        if (!$is_last) {
            $next_q = $questions[$answered_count];
            $response['next_question'] = $this->format_question($next_q, $quiz);
            $response['question_index'] = $answered_count;
        }

        wp_send_json_success($response);
    }

    public function ajax_finish() {
        check_ajax_referer('novel_quiz_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $user_id    = get_current_user_id();
        $attempt_id = absint($_POST['attempt_id'] ?? 0);

        global $wpdb;

        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}quiz_attempts WHERE id = %d AND user_id = %d AND status = 'in_progress'",
            $attempt_id, $user_id
        ));
        if (!$attempt) wp_send_json_error();

        // Mark completed
        $wpdb->update("{$wpdb->prefix}quiz_attempts", [
            'status'       => 'completed',
            'completed_at' => current_time('mysql'),
        ], ['id' => $attempt_id]);

        // Calculate rank and coins
        $quiz = $this->get_quiz($attempt->quiz_id);
        $rank = $this->get_user_rank($attempt->quiz_id, $user_id);

        $coins = (int)$quiz->reward_coins_participation;
        if ($rank === 1) $coins = (int)$quiz->reward_coins_1st;
        elseif ($rank === 2) $coins = (int)$quiz->reward_coins_2nd;
        elseif ($rank === 3) $coins = (int)$quiz->reward_coins_3rd;

        // Award coins
        if ($coins > 0 && class_exists('Novel_Coins')) {
            Novel_Coins::get_instance()->add_coins($user_id, $coins, 'quiz_reward',
                sprintf('جایزه مسابقه: %s (رتبه %d)', $quiz->title, $rank)
            );
        }

        $wpdb->update("{$wpdb->prefix}quiz_attempts", ['coins_earned' => $coins], ['id' => $attempt_id]);

        // Achievement check
        if (class_exists('Novel_Achievements')) {
            do_action('novel_quiz_completed', $user_id, [
                'quiz_id' => $attempt->quiz_id,
                'score'   => $attempt->score,
                'rank'    => $rank,
                'perfect' => ($attempt->wrong_count == 0 && $attempt->correct_count > 0),
            ]);
        }

        // Get updated attempt
        $final = $this->get_attempt_result($attempt_id);
        $leaderboard = $this->get_leaderboard_data($attempt->quiz_id, 10);

        wp_send_json_success([
            'score'        => (int)$final->score,
            'total_points' => (int)$final->total_points,
            'correct'      => (int)$final->correct_count,
            'wrong'        => (int)$final->wrong_count,
            'time_spent'   => (int)$final->time_spent,
            'rank'         => $rank,
            'coins'        => $coins,
            'total_participants' => $quiz->participant_count + 1,
            'percentage'   => $final->total_points > 0 ? round(($final->score / $final->total_points) * 100) : 0,
            'leaderboard'  => array_map(function($l) {
                return [
                    'name'  => $l->display_name,
                    'score' => (int)$l->score,
                    'time'  => (int)$l->time_spent,
                    'rank'  => (int)$l->ranking,
                    'coins' => (int)$l->coins_earned,
                ];
            }, $leaderboard),
            'answers'      => array_map(function($a) {
                return [
                    'question'    => $a->question_text,
                    'user_answer' => $a->user_answer,
                    'correct'     => $a->correct_answer,
                    'is_correct'  => (bool)$a->is_correct,
                    'points'      => (int)$a->points_earned,
                    'explanation' => $a->explanation ?: '',
                ];
            }, $final->answers),
        ]);
    }

    // ═══════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════

    private function format_question($q, $quiz) {
        $data = [
            'id'       => (int)$q->id,
            'text'     => $q->question_text,
            'type'     => $q->question_type,
            'options'  => [
                'a' => $q->option_a,
                'b' => $q->option_b,
            ],
            'points'    => (int)$q->points,
            'time'      => (int)$quiz->time_per_question,
            'reference' => $q->chapter_reference ? (int)$q->chapter_reference : null,
        ];
        if ($q->option_c) $data['options']['c'] = $q->option_c;
        if ($q->option_d) $data['options']['d'] = $q->option_d;
        return $data;
    }

    private function get_answered_count($attempt_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}quiz_answers WHERE attempt_id = %d", $attempt_id
        ));
    }

    private function get_total_points($quiz_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(points), 0) FROM {$wpdb->prefix}quiz_questions WHERE quiz_id = %d", $quiz_id
        ));
    }

    // ═══════════════════════════════════════
    // Auto Quiz Generation
    // ═══════════════════════════════════════

    public function generate_daily_quiz() {
        global $wpdb;

        $min_questions = (int)get_option('novel_quiz_min_bank', 50);
        $bank_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_questions");
        if ($bank_count < $min_questions) return;

        $today_title = 'مسابقه روزانه - ' . wp_date('j F Y');

        $already = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}quizzes WHERE quiz_type = 'daily' AND DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        if ($already) return;

        // Select random questions
        $questions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}quiz_questions ORDER BY RAND() LIMIT 10"
        );
        if (count($questions) < 5) return;

        $wpdb->insert("{$wpdb->prefix}quizzes", [
            'title'          => $today_title,
            'quiz_type'      => 'daily',
            'difficulty'     => 'mixed',
            'time_per_question' => 30,
            'total_questions' => count($questions),
            'reward_coins_1st'  => 20,
            'reward_coins_2nd'  => 10,
            'reward_coins_3rd'  => 5,
            'reward_coins_participation' => 1,
            'status'         => 'active',
            'end_time'       => date('Y-m-d 23:59:59', current_time('timestamp')),
            'created_by'     => 0,
        ]);
        $quiz_id = $wpdb->insert_id;

        foreach ($questions as $i => $q) {
            $wpdb->insert("{$wpdb->prefix}quiz_questions", [
                'quiz_id'        => $quiz_id,
                'question_text'  => $q->question_text,
                'question_type'  => $q->question_type,
                'option_a'       => $q->option_a,
                'option_b'       => $q->option_b,
                'option_c'       => $q->option_c,
                'option_d'       => $q->option_d,
                'correct_answer' => $q->correct_answer,
                'explanation'    => $q->explanation,
                'difficulty'     => $q->difficulty,
                'points'         => $q->points,
                'sort_order'     => $i,
            ]);
        }
    }

    public function generate_weekly_quiz() {
        // مشابه daily ولی ۲۰ سوال و سخت‌تر
        // ... (ساده‌سازی برای کوتاهی)
    }

    // ═══════════════════════════════════════
    // Shortcode Renders
    // ═══════════════════════════════════════

    public function render_quiz($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $quiz = $this->get_quiz(absint($atts['id']));
        if (!$quiz) return '<p>مسابقه یافت نشد.</p>';
        ob_start();
        include get_template_directory() . '/templates/quiz/quiz-lobby.php';
        return ob_get_clean();
    }

    public function render_quiz_list($atts) {
        $atts = shortcode_atts(['status' => 'active', 'limit' => 10], $atts);
        $result = $this->get_quizzes(['status' => $atts['status'], 'limit' => $atts['limit']]);
        ob_start();
        include get_template_directory() . '/templates/quiz/quiz-list.php';
        return ob_get_clean();
    }

    public function render_leaderboard($atts) {
        $atts = shortcode_atts(['id' => 0, 'limit' => 20], $atts);
        $quiz_id = absint($atts['id']);
        $leaderboard = $quiz_id ? $this->get_leaderboard_data($quiz_id, $atts['limit']) : [];
        ob_start();
        include get_template_directory() . '/templates/quiz/quiz-leaderboard.php';
        return ob_get_clean();
    }
}
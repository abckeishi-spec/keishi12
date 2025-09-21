<?php
/**
 * Plugin Name: Grant Insight Jグランツ・インポーター 改善版
 * Plugin URI: https://grant-insight.com/
 * Description: JグランツAPIと統合したAI自動化助成金情報管理システム。キーワード検索修正、インポート件数制限修正、高度なAIカスタマイズ機能搭載。
 * Version: 2.0.0
 * Author: Grant Insight Team
 * Text Domain: grant-insight-jgrants-importer-improved
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// セキュリティ: 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの基本定数定義
define('GIJI_IMPROVED_PLUGIN_VERSION', '2.0.0');
define('GIJI_IMPROVED_PLUGIN_FILE', __FILE__);
define('GIJI_IMPROVED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GIJI_IMPROVED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GIJI_IMPROVED_PLUGIN_BASENAME', plugin_basename(__FILE__));

// デバッグモード（開発時のみ有効）
define('GIJI_DEBUG', WP_DEBUG);

/**
 * メインプラグインクラス
 */
class Grant_Insight_JGrants_Importer_Improved {
    
    private static $instance = null;
    private $jgrants_client;
    private $ai_client;
    private $data_processor;
    private $automation_controller;
    private $admin_manager;
    private $logger;
    private $security_manager;
    
    /**
     * シングルトンパターン
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * プラグイン初期化
     */
    public function init() {
        // 言語ファイルの読み込み
        load_plugin_textdomain('grant-insight-jgrants-importer-improved', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // クラスファイルの読み込み
        $this->load_dependencies();
        
        // WordPressの初期化が完了してからコンポーネントを初期化
        add_action('wp_loaded', array($this, 'init_components'));
        
        // 改良プロンプト更新用のアクションフック
        add_action('admin_post_giji_improved_update_prompts', array($this, 'force_update_prompts'));
        
        // 管理画面の初期化（さらに遅らせる）
        if (is_admin()) {
            add_action('wp_loaded', array($this, 'init_admin'));
        }
        
        // Cronスケジュールの追加
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
    }
    
    /**
     * コンポーネントの初期化
     */
    public function init_components() {
        try {
            // セキュリティマネージャーの初期化
            if (class_exists('GIJI_Security_Manager')) {
                $this->security_manager = new GIJI_Security_Manager();
            }
            
            // ロガーの初期化
            if (class_exists('GIJI_Logger')) {
                $this->logger = new GIJI_Logger();
            }
            
            // APIクライアントの初期化
            if (class_exists('GIJI_JGrants_API_Client')) {
                $this->jgrants_client = new GIJI_JGrants_API_Client($this->logger);
            }
            
            if (class_exists('GIJI_Unified_AI_Client')) {
                $this->ai_client = new GIJI_Unified_AI_Client($this->logger, $this->security_manager);
            }
            
            // データプロセッサーの初期化
            if (class_exists('GIJI_Grant_Data_Processor')) {
                $this->data_processor = new GIJI_Grant_Data_Processor(
                    $this->jgrants_client,
                    $this->ai_client,
                    $this->logger
                );
            }
            
            // 自動化コントローラーの初期化
            if (class_exists('GIJI_Automation_Controller')) {
                $this->automation_controller = new GIJI_Automation_Controller(
                    $this->data_processor,
                    $this->logger
                );
            }
            
            // 投稿タイプとタクソノミーの登録
            $this->register_post_types_and_taxonomies();
            
            if ($this->logger) {
                $this->logger->log('Grant Insight Jグランツ・インポーター改善版のコンポーネント初期化完了');
            }
            
        } catch (Exception $e) {
            error_log('Grant Insight Jグランツ・インポーター改善版初期化エラー: ' . $e->getMessage());
            
            // フォールバック用の簡易ロガー
            if (!$this->logger) {
                $this->logger = new stdClass();
                $this->logger->log = function($message, $level = 'error') {
                    error_log("GIJI: [{$level}] {$message}");
                };
            }
        }
    }
    
    /**
     * 管理画面の初期化
     */
    public function init_admin() {
        if (class_exists('GIJI_Admin_Manager') && $this->automation_controller && $this->logger && $this->security_manager) {
            $this->admin_manager = new GIJI_Admin_Manager(
                $this->automation_controller,
                $this->logger,
                $this->security_manager
            );
        }
    }
    
    /**
     * 必要なクラスファイルを読み込み
     */
    private function load_dependencies() {
        $class_files = array(
            'includes/class-security-manager.php',
            'includes/class-logger.php',
            'includes/class-jgrants-api-client-improved.php',
            'includes/class-unified-ai-client-improved.php',
            'includes/class-grant-data-processor-improved.php',
            'includes/class-automation-controller-improved.php',
            'admin/class-admin-manager-improved.php'
        );
        
        foreach ($class_files as $file) {
            $file_path = GIJI_IMPROVED_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Grant Insight Jグランツ・インポーター改善版: ファイルが見つかりません: {$file_path}");
            }
        }
        
        // ACFフィールド設定の読み込み
        $acf_file = GIJI_IMPROVED_PLUGIN_DIR . 'acf-fields-improved.php';
        if (file_exists($acf_file)) {
            require_once $acf_file;
        }
        
        // 改良プロンプトの読み込み
        $improved_prompts_file = GIJI_IMPROVED_PLUGIN_DIR . 'improved-prompts.php';
        if (file_exists($improved_prompts_file)) {
            require_once $improved_prompts_file;
        }
    }
    
    /**
     * カスタム投稿タイプとタクソノミーの登録
     */
    public function register_post_types_and_taxonomies() {
        // カスタム投稿タイプ「助成金」
        $args = array(
            'labels' => array(
                'name' => __('助成金', 'grant-insight-jgrants-importer-improved'),
                'singular_name' => __('助成金', 'grant-insight-jgrants-importer-improved'),
                'add_new' => __('新規追加', 'grant-insight-jgrants-importer-improved'),
                'add_new_item' => __('新しい助成金を追加', 'grant-insight-jgrants-importer-improved'),
                'edit_item' => __('助成金を編集', 'grant-insight-jgrants-importer-improved'),
                'new_item' => __('新しい助成金', 'grant-insight-jgrants-importer-improved'),
                'view_item' => __('助成金を表示', 'grant-insight-jgrants-importer-improved'),
                'search_items' => __('助成金を検索', 'grant-insight-jgrants-importer-improved'),
                'not_found' => __('助成金が見つかりません', 'grant-insight-jgrants-importer-improved'),
                'not_found_in_trash' => __('ゴミ箱に助成金はありません', 'grant-insight-jgrants-importer-improved'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'excerpt', 'custom-fields', 'thumbnail'),
            'menu_icon' => 'dashicons-money-alt',
            'rewrite' => array('slug' => 'grants'),
            'show_in_rest' => true,
        );
        register_post_type('grant', $args);
        
        // カスタムタクソノミー「補助対象地域」
        register_taxonomy('grant_prefecture', 'grant', array(
            'labels' => array(
                'name' => __('補助対象地域', 'grant-insight-jgrants-importer-improved'),
                'singular_name' => __('補助対象地域', 'grant-insight-jgrants-importer-improved'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'grant-prefecture'),
        ));
        
        // カスタムタクソノミー「利用目的」
        register_taxonomy('grant_category', 'grant', array(
            'labels' => array(
                'name' => __('利用目的', 'grant-insight-jgrants-importer-improved'),
                'singular_name' => __('利用目的', 'grant-insight-jgrants-importer-improved'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'grant-category'),
        ));
        
        // カスタムタクソノミー「実施組織」
        register_taxonomy('grant_organization', 'grant', array(
            'labels' => array(
                'name' => __('実施組織', 'grant-insight-jgrants-importer-improved'),
                'singular_name' => __('実施組織', 'grant-insight-jgrants-importer-improved'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'grant-organization'),
        ));
    }
    
    /**
     * データベーステーブルの確認・作成
     */
    public function check_database_tables() {
        if ($this->logger) {
            $this->logger->create_log_tables();
        }
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function activate() {
        // カスタム投稿タイプとタクソノミーの登録
        $this->register_post_types_and_taxonomies();
        
        // リライトルールの更新
        flush_rewrite_rules();
        
        // デフォルト設定の保存
        $this->save_default_settings();
        
        // データベーステーブルの作成
        $logger = new GIJI_Logger();
        $logger->create_log_tables();
        
        // Cronイベントのスケジュール
        if (!wp_next_scheduled('giji_improved_auto_import_hook')) {
            wp_schedule_event(time(), 'daily', 'giji_improved_auto_import_hook');
        }
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function deactivate() {
        // Cronイベントの削除
        wp_clear_scheduled_hook('giji_improved_auto_import_hook');
        
        // リライトルールの更新
        flush_rewrite_rules();
    }
    
    /**
     * デフォルト設定の保存
     */
    private function save_default_settings() {
        // API設定のデフォルト値
        if (!get_option('giji_improved_ai_provider')) {
            update_option('giji_improved_ai_provider', 'gemini');
        }
        
        // 自動化設定のデフォルト値
        if (!get_option('giji_improved_cron_schedule')) {
            update_option('giji_improved_cron_schedule', 'daily');
        }
        
        // AI生成設定のデフォルト値
        $default_ai_settings = array(
            'content' => true,
            'excerpt' => true,
            'summary' => true,
            'organization' => true,
            'difficulty' => true,
            'success_rate' => true,
            'keywords' => true,
            'target_audience' => true,
            'application_tips' => true,
            'requirements' => true
        );
        
        if (!get_option('giji_improved_ai_generation_enabled')) {
            update_option('giji_improved_ai_generation_enabled', $default_ai_settings);
        }
        
        // 検索設定のデフォルト値
        $default_search_settings = array(
            'keyword' => '補助金',
            'min_amount' => 0,
            'max_amount' => 0,
            'target_areas' => array(),
            'use_purposes' => array(),
            'acceptance_only' => true,
            'exclude_zero_amount' => true
        );
        
        if (!get_option('giji_improved_search_settings')) {
            update_option('giji_improved_search_settings', $default_search_settings);
        }
        
        // 高度なAI設定のデフォルト値
        $default_ai_advanced = array(
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'top_p' => 0.9,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
            'retry_count' => 3,
            'timeout' => 60,
            'fallback_enabled' => true
        );
        
        if (!get_option('giji_improved_ai_advanced_settings')) {
            update_option('giji_improved_ai_advanced_settings', $default_ai_advanced);
        }
        
        // デフォルトプロンプトテンプレートの設定
        $this->save_default_prompts();
    }
    
    /**
     * デフォルトプロンプトテンプレートの保存
     */
    private function save_default_prompts() {
        // 改良プロンプトが利用可能な場合はそちらを使用
        if (class_exists('GIJI_Improved_Prompts')) {
            $enhanced_prompts = array(
                'content_prompt' => GIJI_Improved_Prompts::get_enhanced_content_prompt(),
                'excerpt_prompt' => GIJI_Improved_Prompts::get_enhanced_excerpt_prompt(),
                'summary_prompt' => GIJI_Improved_Prompts::get_enhanced_summary_prompt(),
                'keywords_prompt' => GIJI_Improved_Prompts::get_keywords_prompt(),
                'target_audience_prompt' => GIJI_Improved_Prompts::get_target_audience_prompt(),
                'application_tips_prompt' => GIJI_Improved_Prompts::get_application_tips_prompt(),
                'requirements_prompt' => GIJI_Improved_Prompts::get_requirements_prompt(),
                'organization_prompt' => GIJI_Improved_Prompts::get_organization_prompt(),
                'difficulty_prompt' => GIJI_Improved_Prompts::get_difficulty_prompt(),
                'success_rate_prompt' => GIJI_Improved_Prompts::get_success_rate_prompt(),
            );
            
            foreach ($enhanced_prompts as $key => $prompt) {
                // 既存のプロンプトがない場合、または強制更新フラグがある場合に更新
                if (!get_option('giji_improved_' . $key) || get_option('giji_improved_force_update_prompts', false)) {
                    update_option('giji_improved_' . $key, $prompt);
                }
            }
            
            // 強制更新フラグをリセット
            delete_option('giji_improved_force_update_prompts');
        } else {
            // フォールバック: 基本的なプロンプト
            $default_prompts = array(
                'content_prompt' => "助成金情報を基に、わかりやすく魅力的な記事を作成してください。

【助成金名】: [title]
【概要】: [overview]
【補助額上限】: [max_amount]
【募集終了日】: [deadline_text]
【実施組織】: [organization]
【公式URL】: [official_url]

以下の構成で1000-1500文字程度の記事を作成してください：

## この助成金の特徴
- 対象者や利用目的について説明
- 補助額や条件の魅力を伝える

## 申請のポイント
- 申請時の注意点
- 成功のコツ

## まとめ
- なぜこの助成金がおすすめなのか

読者が申請を検討したくなるような、親しみやすい文章でお願いします。",

                'excerpt_prompt' => "以下の助成金情報から、100文字程度の魅力的な抜粋を作成してください。

【助成金名】: [title]
【概要】: [overview]
【補助額上限】: [max_amount]

読者が興味を持ち、詳細を読みたくなるような簡潔で魅力的な文章にしてください。",

                'summary_prompt' => "以下の助成金情報を3行で要約してください。

【助成金名】: [title]
【概要】: [overview]
【補助額上限】: [max_amount]
【募集終了日】: [deadline_text]

各行は50文字程度で、要点を分かりやすくまとめてください。"
            );
            
            foreach ($default_prompts as $key => $prompt) {
                if (!get_option('giji_improved_' . $key)) {
                    update_option('giji_improved_' . $key, $prompt);
                }
            }
        }
    }
    
    /**
     * Cronスケジュールの追加
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_6_hours'] = array(
            'interval' => 6 * 60 * 60, // 6時間
            'display' => __('6時間ごと', 'grant-insight-jgrants-importer-improved')
        );
        
        $schedules['every_12_hours'] = array(
            'interval' => 12 * 60 * 60, // 12時間
            'display' => __('12時間ごと', 'grant-insight-jgrants-importer-improved')
        );
        
        return $schedules;
    }
    
    /**
     * 公開メソッド：コンポーネントへのアクセス
     */
    public function get_logger() {
        return $this->logger;
    }
    
    public function get_automation_controller() {
        return $this->automation_controller;
    }
    
    public function get_data_processor() {
        return $this->data_processor;
    }
    
    /**
     * 改良プロンプトの強制更新
     */
    public function force_update_prompts() {
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'giji_improved_update_prompts')) {
            wp_die('無効なリクエストです。');
        }
        
        // 強制更新フラグを設定
        update_option('giji_improved_force_update_prompts', true);
        
        // プロンプトを再設定
        $this->save_default_prompts();
        
        $message = '改良プロンプトが正常に更新されました。';
        
        // 管理画面にリダイレクト
        wp_redirect(admin_url('admin.php?page=giji-improved-prompts&message=' . urlencode($message)));
        exit;
    }
}

// プラグインの初期化
function giji_improved_init() {
    return Grant_Insight_JGrants_Importer_Improved::get_instance();
}

// WordPress読み込み後にプラグインを初期化
add_action('plugins_loaded', 'giji_improved_init');

// 下位互換性のための関数
function giji_improved_get_instance() {
    return Grant_Insight_JGrants_Importer_Improved::get_instance();
}

// エラーハンドリング
if (!class_exists('WP_Error')) {
    // WordPressが完全に読み込まれていない場合のフォールバック
    function giji_improved_error_fallback($message) {
        error_log('Grant Insight Jグランツ・インポーター改善版エラー: ' . $message);
    }
}

// プラグインのヘルスチェック
function giji_improved_health_check() {
    $health = array(
        'plugin_version' => GIJI_IMPROVED_PLUGIN_VERSION,
        'wp_version' => get_bloginfo('version'),
        'php_version' => phpversion(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'wp_debug' => WP_DEBUG,
        'giji_debug' => GIJI_DEBUG
    );
    
    return $health;
}

// Cronジョブの健全性チェック
function giji_improved_check_cron_health() {
    if (!wp_next_scheduled('giji_improved_auto_import_hook')) {
        $schedule = get_option('giji_improved_cron_schedule', 'daily');
        if ($schedule !== 'disabled') {
            wp_schedule_event(time(), $schedule, 'giji_improved_auto_import_hook');
        }
    }
}

// WordPress初期化後にCronチェックを実行
add_action('wp_loaded', 'giji_improved_check_cron_health');

// PHP致命的エラーの処理
function giji_improved_shutdown_handler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('Grant Insight Jグランツ・インポーター改善版で致命的エラーが発生: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
    }
}
register_shutdown_function('giji_improved_shutdown_handler');
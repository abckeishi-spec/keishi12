<?php
/**
 * 自動化・公開制御クラス（改善版）
 */

if (!defined('ABSPATH')) {
    exit;
}

class GIJI_Automation_Controller {
    
    private $data_processor;
    private $logger;
    
    const CRON_HOOK = 'giji_improved_auto_import_hook';
    
    public function __construct($data_processor, $logger) {
        $this->data_processor = $data_processor;
        $this->logger = $logger;
        
        // フックの登録を初期化時に実行
        $this->init_hooks();
    }
    
    /**
     * フックの初期化
     */
    private function init_hooks() {
        // Cronフックの登録
        add_action(self::CRON_HOOK, array($this, 'execute_auto_import'));
    }
    
    /**
     * 自動インポートの実行（改善版）
     */
    public function execute_auto_import() {
        $this->logger->log('自動インポート開始');
        
        if (!$this->is_auto_import_enabled()) {
            $this->logger->log('自動インポートが無効のため処理を停止');
            return;
        }
        
        // メモリ制限の設定
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5分
        
        try {
            // JグランツAPIクライアントの取得
            $jgrants_client = new GIJI_JGrants_API_Client($this->logger);
            
            // APIの接続テスト
            if (!$jgrants_client->test_connection()) {
                $this->logger->log('JグランツAPIに接続できませんでした', 'error');
                return;
            }
            
            // 検索パラメータの取得
            $search_params = $this->get_search_parameters();
            $max_process_count = $this->get_max_process_count();
            
            $this->logger->log("自動インポート設定: 最大処理件数={$max_process_count}, キーワード=" . $search_params['keyword']);
            
            // 助成金一覧の取得
            $response = $jgrants_client->get_subsidies($search_params);
            
            if (is_wp_error($response)) {
                $this->logger->log('助成金一覧取得エラー: ' . $response->get_error_message(), 'error');
                return;
            }
            
            if (!isset($response['result']) || empty($response['result'])) {
                $this->logger->log('取得できた助成金データがありません');
                return;
            }
            
            // バッチ処理での詳細データ取得
            $subsidies = array_slice($response['result'], 0, $max_process_count);
            $results = $this->process_subsidies_batch($subsidies, $jgrants_client);
            
            // 処理結果の保存
            $this->save_import_result($results['processed'], count($subsidies), $results['errors']);
            
            $this->logger->log("自動インポート完了: 成功={$results['processed']}件, エラー={$results['errors']}件");
            
        } catch (Exception $e) {
            $this->logger->log('自動インポート中に例外発生: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * バッチ処理で助成金を処理
     */
    private function process_subsidies_batch($subsidies, $jgrants_client) {
        $processed_count = 0;
        $error_count = 0;
        $batch_size = 5; // バッチサイズ
        
        $batches = array_chunk($subsidies, $batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            $this->logger->log("バッチ処理 " . ($batch_index + 1) . "/" . count($batches) . " 開始");
            
            foreach ($batch as $subsidy) {
                try {
                    // 詳細データの取得
                    $detail_response = $jgrants_client->get_subsidy_detail($subsidy['id']);
                    
                    if (is_wp_error($detail_response)) {
                        $this->logger->log('詳細データ取得エラー (ID: ' . $subsidy['id'] . '): ' . $detail_response->get_error_message(), 'warning');
                        $error_count++;
                        continue;
                    }
                    
                    if (isset($detail_response['result'][0])) {
                        $detail_data = $detail_response['result'][0];
                        
                        // データの処理と保存
                        $result = $this->data_processor->process_and_save_grant($detail_data);
                        
                        if (!is_wp_error($result)) {
                            $processed_count++;
                            $this->logger->log('助成金データ処理成功: ' . $detail_data['title'] . ' (投稿ID: ' . $result . ')');
                        } else {
                            $error_count++;
                            $this->logger->log('助成金データ処理エラー: ' . $result->get_error_message(), 'warning');
                        }
                    }
                    
                } catch (Exception $e) {
                    $error_count++;
                    $this->logger->log('処理中に例外発生 (ID: ' . $subsidy['id'] . '): ' . $e->getMessage(), 'error');
                }
                
                // API制限を考慮した待機
                sleep(2);
            }
            
            // バッチ間の待機
            if ($batch_index < count($batches) - 1) {
                $this->logger->log("バッチ処理完了、次のバッチまで5秒待機");
                sleep(5);
            }
            
            // メモリクリーンアップ
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return array(
            'processed' => $processed_count,
            'errors' => $error_count
        );
    }
    
    /**
     * 手動インポートの実行（改善版・バッファリング取得対応）
     */
    public function execute_manual_import($search_params = array(), $max_count = 5) {
        $this->logger->log('手動インポート開始: 最大' . $max_count . '件');
        
        // メモリ制限の設定
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        
        try {
            // JグランツAPIクライアントの取得
            $jgrants_client = new GIJI_JGrants_API_Client($this->logger);
            
            // APIの接続テスト
            if (!$jgrants_client->test_connection()) {
                return array(
                    'success' => false,
                    'message' => 'JグランツAPIに接続できませんでした。'
                );
            }
            
            // デフォルトパラメータとマージ
            $default_search_params = $this->get_search_parameters();
            $search_params = wp_parse_args($search_params, $default_search_params);
            $search_params['per_page'] = intval($max_count);
            
            $this->logger->log('検索パラメータ: ' . wp_json_encode($search_params));
            
            // バッファリング取得を使用して指定件数を確実に取得
            $response = $jgrants_client->get_subsidies_with_guaranteed_count($search_params);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => '助成金一覧取得エラー: ' . $response->get_error_message()
                );
            }
            
            if (!isset($response['result']) || empty($response['result'])) {
                return array(
                    'success' => true,
                    'message' => '指定条件に該当する助成金が見つかりませんでした。',
                    'results' => array(
                        'success' => 0,
                        'error' => 0,
                        'duplicate' => 0,
                        'excluded' => 0,
                        'details' => array()
                    )
                );
            }
            
            // 詳細データの処理（改善版）
            $subsidies = $response['result'];
            $results = $this->process_manual_import_batch_improved($subsidies, $jgrants_client, $max_count);
            
            // 成功メッセージの作成
            $message = sprintf(
                '手動インポート完了: 成功=%d件, エラー=%d件, 重複=%d件, 除外=%d件 (API呼び出し=%d回)',
                $results['success'],
                $results['error'],
                $results['duplicate'],
                $results['excluded'],
                $response['api_calls'] ?? 1
            );
            
            return array(
                'success' => true,
                'message' => $message,
                'results' => $results
            );
            
        } catch (Exception $e) {
            $this->logger->log('手動インポート中に例外発生: ' . $e->getMessage(), 'error');
            return array(
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * 手動インポートのバッチ処理（改善版）
     */
    private function process_manual_import_batch_improved($subsidies, $jgrants_client, $target_count) {
        $results = array(
            'success' => 0,
            'error' => 0,
            'duplicate' => 0,
            'excluded' => 0,
            'details' => array()
        );
        
        $processed_count = 0;
        $duplicate_ids = $this->get_existing_jgrants_ids(); // 事前に重複IDを取得
        
        foreach ($subsidies as $subsidy) {
            if ($processed_count >= $target_count) {
                break; // 目標件数に達したら終了
            }
            
            try {
                // 事前重複チェック（高速化）
                if (in_array($subsidy['id'], $duplicate_ids)) {
                    $results['duplicate']++;
                    $results['details'][] = array(
                        'id' => $subsidy['id'],
                        'title' => isset($subsidy['title']) ? $subsidy['title'] : 'ID: ' . $subsidy['id'],
                        'status' => 'duplicate',
                        'message' => '既に登録済み（事前チェック）'
                    );
                    continue;
                }
                
                // 詳細データの取得
                $detail_response = $jgrants_client->get_subsidy_detail($subsidy['id']);
                
                if (is_wp_error($detail_response)) {
                    $results['error']++;
                    $results['details'][] = array(
                        'id' => $subsidy['id'],
                        'title' => isset($subsidy['title']) ? $subsidy['title'] : 'ID: ' . $subsidy['id'],
                        'status' => 'error',
                        'message' => $detail_response->get_error_message()
                    );
                    continue;
                }
                
                if (isset($detail_response['result'][0])) {
                    $detail_data = $detail_response['result'][0];
                    
                    // データの処理と保存
                    $result = $this->data_processor->process_and_save_grant($detail_data);
                    
                    if (!is_wp_error($result)) {
                        $results['success']++;
                        $processed_count++;
                        $results['details'][] = array(
                            'id' => $detail_data['id'],
                            'title' => $detail_data['title'],
                            'status' => 'success',
                            'post_id' => $result
                        );
                    } else {
                        $error_code = $result->get_error_code();
                        if ($error_code === 'duplicate_grant') {
                            $results['duplicate']++;
                            $results['details'][] = array(
                                'id' => $detail_data['id'],
                                'title' => $detail_data['title'],
                                'status' => 'duplicate',
                                'message' => '既に登録済み'
                            );
                        } elseif (in_array($error_code, ['invalid_amount', 'amount_too_low_minimum', 'no_amount_data', 'invalid_title', 'invalid_overview'])) {
                            $results['excluded']++;
                            $results['details'][] = array(
                                'id' => $detail_data['id'],
                                'title' => $detail_data['title'],
                                'status' => 'excluded',
                                'message' => $result->get_error_message()
                            );
                        } else {
                            $results['error']++;
                            $results['details'][] = array(
                                'id' => $detail_data['id'],
                                'title' => $detail_data['title'],
                                'status' => 'error',
                                'message' => $result->get_error_message()
                            );
                        }
                    }
                }
                
            } catch (Exception $e) {
                $results['error']++;
                $results['details'][] = array(
                    'id' => $subsidy['id'],
                    'title' => isset($subsidy['title']) ? $subsidy['title'] : 'ID: ' . $subsidy['id'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                );
            }
            
            // API制限を考慮した待機
            sleep(1);
        }
        
        return $results;
    }
    
    /**
     * 既存のJグランツIDを効率的に取得
     */
    private function get_existing_jgrants_ids() {
        global $wpdb;
        
        $ids = $wpdb->get_col("
            SELECT meta_value 
            FROM {$wpdb->postmeta} pm 
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_key = 'jgrants_id' 
            AND p.post_type = 'grant' 
            AND p.post_status IN ('publish', 'draft', 'private')
        ");
        
        return array_map('strval', $ids); // 文字列として統一
    }
    
    /**
     * 手動インポートのバッチ処理
     */
    private function process_manual_import_batch($subsidies, $jgrants_client) {
        $results = array(
            'success' => 0,
            'error' => 0,
            'duplicate' => 0,
            'details' => array()
        );
        
        foreach ($subsidies as $subsidy) {
            try {
                // 詳細データの取得
                $detail_response = $jgrants_client->get_subsidy_detail($subsidy['id']);
                
                if (is_wp_error($detail_response)) {
                    $results['error']++;
                    $results['details'][] = array(
                        'id' => $subsidy['id'],
                        'title' => isset($subsidy['title']) ? $subsidy['title'] : 'ID: ' . $subsidy['id'],
                        'status' => 'error',
                        'message' => $detail_response->get_error_message()
                    );
                    continue;
                }
                
                if (isset($detail_response['result'][0])) {
                    $detail_data = $detail_response['result'][0];
                    
                    // データの処理と保存
                    $result = $this->data_processor->process_and_save_grant($detail_data);
                    
                    if (!is_wp_error($result)) {
                        $results['success']++;
                        $results['details'][] = array(
                            'id' => $detail_data['id'],
                            'title' => $detail_data['title'],
                            'status' => 'success',
                            'post_id' => $result
                        );
                    } else {
                        if ($result->get_error_code() === 'duplicate_grant') {
                            $results['duplicate']++;
                            $results['details'][] = array(
                                'id' => $detail_data['id'],
                                'title' => $detail_data['title'],
                                'status' => 'duplicate',
                                'message' => '既に登録済み'
                            );
                        } else {
                            $results['error']++;
                            $results['details'][] = array(
                                'id' => $detail_data['id'],
                                'title' => $detail_data['title'],
                                'status' => 'error',
                                'message' => $result->get_error_message()
                            );
                        }
                    }
                }
                
            } catch (Exception $e) {
                $results['error']++;
                $results['details'][] = array(
                    'id' => $subsidy['id'],
                    'title' => isset($subsidy['title']) ? $subsidy['title'] : 'ID: ' . $subsidy['id'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                );
            }
            
            // API制限を考慮した待機
            sleep(1);
        }
        
        return $results;
    }
    
    /**
     * 手動公開の実行（改善版）
     */
    public function execute_manual_publish($count) {
        $this->logger->log('手動公開開始: ' . $count . '件');
        
        // 優先順位に基づく複合ソート条件で下書きを取得
        $draft_posts = $this->get_prioritized_draft_posts(intval($count));
        
        $results = array(
            'success' => 0,
            'error' => 0,
            'skipped' => 0,
            'details' => array()
        );
        
        if (empty($draft_posts)) {
            $this->logger->log('公開対象の下書きがありません');
            return $results;
        }
        
        foreach ($draft_posts as $post_data) {
            $post_id = $post_data['ID'];
            $post = get_post($post_id);
            if (!$post) continue;
            
            // 公開前の検証
            $validation_result = $this->validate_post_for_publishing($post_id);
            if (is_wp_error($validation_result)) {
                $results['skipped']++;
                $results['details'][] = array(
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'status' => 'skipped',
                    'message' => $validation_result->get_error_message(),
                    'deadline' => $post_data['deadline'] ?? 'なし',
                    'priority_score' => $post_data['priority_score'] ?? 0
                );
                $this->logger->log('公開スキップ (ID: ' . $post_id . '): ' . $validation_result->get_error_message(), 'warning');
                continue;
            }
            
            // 公開処理
            $update_result = wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish',
                'post_date' => current_time('mysql'), // 公開日時を現在に設定
                'post_date_gmt' => current_time('mysql', 1)
            ), true);
            
            if (is_wp_error($update_result)) {
                $results['error']++;
                $results['details'][] = array(
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'status' => 'error',
                    'message' => $update_result->get_error_message(),
                    'deadline' => $post_data['deadline'] ?? 'なし',
                    'priority_score' => $post_data['priority_score'] ?? 0
                );
                $this->logger->log('公開エラー (ID: ' . $post_id . '): ' . $update_result->get_error_message(), 'error');
            } else {
                $results['success']++;
                $results['details'][] = array(
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'status' => 'success',
                    'deadline' => $post_data['deadline'] ?? 'なし',
                    'priority_score' => $post_data['priority_score'] ?? 0
                );
                $this->logger->log('公開成功: ' . $post->post_title . ' (優先度: ' . $post_data['priority_score'] . ')');
                
                // 公開後の処理
                $this->post_publish_actions($post_id);
            }
        }
        
        $this->logger->log('手動公開完了: 成功 ' . $results['success'] . '件、エラー ' . $results['error'] . '件、スキップ ' . $results['skipped'] . '件');
        
        return $results;
    }
    
    /**
     * 優先順位に基づく下書き投稿の取得
     */
    private function get_prioritized_draft_posts($count) {
        global $wpdb;
        
        // 複雑なソート条件を使用したカスタムクエリ
        $query = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                deadline.meta_value as deadline_date,
                amount.meta_value as max_amount_numeric,
                (
                    CASE 
                        WHEN deadline.meta_value IS NOT NULL AND deadline.meta_value != '' 
                        THEN 
                            CASE 
                                WHEN STR_TO_DATE(deadline.meta_value, '%Y%m%d') <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 100
                                WHEN STR_TO_DATE(deadline.meta_value, '%Y%m%d') <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 80
                                WHEN STR_TO_DATE(deadline.meta_value, '%Y%m%d') <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 60
                                ELSE 40
                            END
                        ELSE 20
                    END +
                    CASE 
                        WHEN CAST(amount.meta_value AS UNSIGNED) >= 10000000 THEN 30  -- 1000万円以上
                        WHEN CAST(amount.meta_value AS UNSIGNED) >= 5000000 THEN 25   -- 500万円以上
                        WHEN CAST(amount.meta_value AS UNSIGNED) >= 1000000 THEN 20   -- 100万円以上
                        WHEN CAST(amount.meta_value AS UNSIGNED) >= 500000 THEN 15    -- 50万円以上
                        ELSE 10
                    END +
                    CASE 
                        WHEN p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 15   -- 新しい投稿
                        WHEN p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 10
                        ELSE 5
                    END
                ) as priority_score,
                DATE_FORMAT(STR_TO_DATE(deadline.meta_value, '%Y%m%d'), '%Y-%m-%d') as deadline
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} deadline ON p.ID = deadline.post_id AND deadline.meta_key = 'deadline_date'
            LEFT JOIN {$wpdb->postmeta} amount ON p.ID = amount.post_id AND amount.meta_key = 'max_amount_numeric'
            WHERE p.post_type = 'grant' 
            AND p.post_status = 'draft'
            AND p.post_title != ''
            ORDER BY priority_score DESC, 
                     STR_TO_DATE(deadline.meta_value, '%Y%m%d') ASC,
                     CAST(amount.meta_value AS UNSIGNED) DESC,
                     p.post_date DESC
            LIMIT %d
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $count), ARRAY_A);
        
        $this->logger->log('優先順位付き下書き取得: ' . count($results) . '件取得（要求: ' . $count . '件）');
        
        // 取得したデータをログに記録
        foreach ($results as $result) {
            $this->logger->log("公開候補: ID={$result['ID']}, 優先度={$result['priority_score']}, 締切={$result['deadline']}, タイトル=" . substr($result['post_title'], 0, 30) . '...');
        }
        
        return $results;
    }
    
    /**
     * 投稿の公開前検証
     */
    private function validate_post_for_publishing($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('invalid_post', '投稿が見つかりません');
        }
        
        // タイトルの検証
        if (empty(trim($post->post_title)) || strlen(trim($post->post_title)) < 5) {
            return new WP_Error('invalid_title', 'タイトルが無効または短すぎます');
        }
        
        // 本文の検証
        if (empty(trim($post->post_content)) || strlen(trim($post->post_content)) < 100) {
            return new WP_Error('invalid_content', '本文が無効または短すぎます');
        }
        
        // 必要なカスタムフィールドの検証
        $required_fields = array('jgrants_id', 'max_amount_numeric', 'deadline_date');
        foreach ($required_fields as $field) {
            $value = get_post_meta($post_id, $field, true);
            if (empty($value)) {
                return new WP_Error('missing_field', "必須フィールド '{$field}' が不足しています");
            }
        }
        
        // 締切日の検証
        $deadline_date = get_post_meta($post_id, 'deadline_date', true);
        if (!empty($deadline_date)) {
            $deadline_timestamp = strtotime($deadline_date);
            if ($deadline_timestamp && $deadline_timestamp < strtotime('-1 day')) {
                return new WP_Error('expired_deadline', '募集締切が過ぎています (' . $deadline_date . ')');
            }
        }
        
        return true;
    }
    
    /**
     * 公開後のアクション
     */
    private function post_publish_actions($post_id) {
        // 公開日時のメタデータを更新
        update_post_meta($post_id, 'published_at', current_time('mysql'));
        
        // 公開ログの記録
        $post = get_post($post_id);
        $this->logger->log('投稿公開完了: ID=' . $post_id . ', タイトル=' . $post->post_title);
        
        // 必要に応じて他のアクション（通知、キャッシュクリア等）を追加
    }
    
    /**
     * 下書き一括削除の実行（改善版）
     */
    public function execute_bulk_delete_drafts() {
        $this->logger->log('下書き一括削除開始');
        
        $results = array(
            'success' => 0,
            'error' => 0,
            'details' => array()
        );
        
        $batch_size = 50;
        
        while (true) {
            $draft_posts = get_posts(array(
                'post_type' => 'grant',
                'post_status' => 'draft',
                'posts_per_page' => $batch_size,
                'fields' => 'ids'
            ));
            
            if (empty($draft_posts)) {
                break;
            }
            
            foreach ($draft_posts as $post_id) {
                $delete_result = wp_delete_post($post_id, true);
                
                if ($delete_result !== false) {
                    $results['success']++;
                    $results['details'][] = array(
                        'id' => $post_id,
                        'status' => 'success'
                    );
                } else {
                    $results['error']++;
                    $results['details'][] = array(
                        'id' => $post_id,
                        'status' => 'error',
                        'message' => '削除に失敗しました'
                    );
                    $this->logger->log('削除エラー (ID: ' . $post_id . ')', 'error');
                }
            }
            
            // メモリクリーンアップ
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        $this->logger->log('下書き一括削除完了: 成功 ' . $results['success'] . '件、エラー ' . $results['error'] . '件');
        
        return $results;
    }
    
    /**
     * Cronスケジュールの設定（改善版）
     */
    public function set_cron_schedule($schedule) {
        wp_clear_scheduled_hook(self::CRON_HOOK);
        
        if ($schedule !== 'disabled') {
            $next_run = wp_schedule_event(time(), $schedule, self::CRON_HOOK);
            if ($next_run === false) {
                $this->logger->log('Cronスケジュール設定に失敗: ' . $schedule, 'error');
                return false;
            }
            $this->logger->log('Cronスケジュール設定: ' . $schedule);
        } else {
            $this->logger->log('Cronスケジュール無効化');
        }
        
        update_option('giji_improved_cron_schedule', $schedule);
        return true;
    }
    
    /**
     * 次回実行予定時刻の取得
     */
    public function get_next_scheduled_time() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        return false;
    }
    
    /**
     * 自動インポートが有効かどうかを確認
     */
    private function is_auto_import_enabled() {
        $schedule = get_option('giji_improved_cron_schedule', 'daily');
        return $schedule !== 'disabled';
    }
    
    /**
     * 検索パラメータの取得（改善版）
     */
    private function get_search_parameters() {
        $search_settings = get_option('giji_improved_search_settings', array());
        
        $default_params = array(
            'keyword' => '補助金',
            'sort' => 'created_date',
            'order' => 'DESC',
            'acceptance' => '1',
            'per_page' => 10
        );
        
        $params = wp_parse_args($search_settings, $default_params);
        
        // 手動インポート用の設定を適用
        if (isset($search_settings['min_amount']) && $search_settings['min_amount'] > 0) {
            $params['min_amount'] = $search_settings['min_amount'];
        }
        
        if (isset($search_settings['max_amount']) && $search_settings['max_amount'] > 0) {
            $params['max_amount'] = $search_settings['max_amount'];
        }
        
        if (isset($search_settings['target_areas']) && is_array($search_settings['target_areas'])) {
            $params['target_areas'] = $search_settings['target_areas'];
        }
        
        if (isset($search_settings['use_purposes']) && is_array($search_settings['use_purposes'])) {
            $params['use_purposes'] = $search_settings['use_purposes'];
        }
        
        return $params;
    }
    
    /**
     * 最大処理件数の取得
     */
    private function get_max_process_count() {
        $max_count = intval(get_option('giji_improved_max_process_count', 10));
        return max(1, min(50, $max_count)); // 1-50の範囲に制限
    }
    
    /**
     * インポート結果の保存（改善版）
     */
    private function save_import_result($processed_count, $total_count, $error_count = 0) {
        $result = array(
            'timestamp' => current_time('mysql'),
            'processed_count' => intval($processed_count),
            'total_count' => intval($total_count),
            'error_count' => intval($error_count),
            'status' => 'completed'
        );
        
        update_option('giji_improved_last_import_result', $result);
        
        // 履歴の保存（最新10件）
        $history = get_option('giji_improved_import_history', array());
        array_unshift($history, $result);
        $history = array_slice($history, 0, 10);
        update_option('giji_improved_import_history', $history);
    }
    
    /**
     * 最後のインポート結果の取得
     */
    public function get_last_import_result() {
        return get_option('giji_improved_last_import_result', false);
    }
    
    /**
     * インポート履歴の取得
     */
    public function get_import_history() {
        return get_option('giji_improved_import_history', array());
    }
    
    /**
     * 下書き投稿数の取得
     */
    public function get_draft_count() {
        $count = wp_count_posts('grant');
        return isset($count->draft) ? intval($count->draft) : 0;
    }
    
    /**
     * 公開投稿数の取得
     */
    public function get_published_count() {
        $count = wp_count_posts('grant');
        return isset($count->publish) ? intval($count->publish) : 0;
    }
    
    /**
     * 統計情報の取得
     */
    public function get_statistics() {
        global $wpdb;
        
        // 基本統計
        $stats = array(
            'draft_count' => $this->get_draft_count(),
            'published_count' => $this->get_published_count(),
            'total_count' => 0
        );
        
        $stats['total_count'] = $stats['draft_count'] + $stats['published_count'];
        
        // 今月の統計
        $current_month = date('Y-m');
        $monthly_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as monthly_total,
                SUM(CASE WHEN post_status = 'publish' THEN 1 ELSE 0 END) as monthly_published,
                SUM(CASE WHEN post_status = 'draft' THEN 1 ELSE 0 END) as monthly_draft
             FROM {$wpdb->posts} 
             WHERE post_type = 'grant' 
             AND DATE_FORMAT(post_date, '%%Y-%%m') = %s",
            $current_month
        ));
        
        if ($monthly_stats) {
            $stats['monthly_total'] = intval($monthly_stats->monthly_total);
            $stats['monthly_published'] = intval($monthly_stats->monthly_published);
            $stats['monthly_draft'] = intval($monthly_stats->monthly_draft);
        } else {
            $stats['monthly_total'] = 0;
            $stats['monthly_published'] = 0;
            $stats['monthly_draft'] = 0;
        }
        
        return $stats;
    }
}
<?php
/**
 * 改良されたプロンプトテンプレート（HTMLとCSSデザインを活用した本文生成用）
 * Grant Insight Jグランツ・インポーター改善版
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTMLとCSSを活用した改良版プロンプト集
 */
class GIJI_Improved_Prompts {
    
    /**
     * 本文生成用プロンプト（HTML/CSSを活用した改良版）
     */
    public static function get_enhanced_content_prompt() {
        return "助成金情報を基に、HTML構造とCSSスタイルを活用した視覚的に魅力的で読みやすいコンテンツを作成してください。

【助成金データ】
助成金名: [title]
概要: [overview]
補助額上限: [max_amount]
募集終了日: [deadline_text]
実施組織: [organization]
公式URL: [official_url]
補助率: [subsidy_rate]
利用目的: [use_purpose]
対象地域: [target_area]

【出力要求】
以下のHTML構造とインラインCSSスタイルを使用して、1500-2000文字程度の魅力的な記事を作成してください。

<div style=\"max-width: 800px; margin: 0 auto; font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.7; color: #333;\">

<!-- ヘッダーセクション -->
<div style=\"background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); color: white; padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(0,115,170,0.3);\">
    <h1 style=\"margin: 0 0 10px 0; font-size: 28px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3);\">[title]</h1>
    <div style=\"background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; margin-top: 20px;\">
        <div style=\"display: inline-block; margin: 0 20px; text-align: center;\">
            <div style=\"font-size: 24px; font-weight: bold; margin-bottom: 5px;\">[max_amount]</div>
            <div style=\"font-size: 14px; opacity: 0.9;\">補助額上限</div>
        </div>
        <div style=\"display: inline-block; margin: 0 20px; text-align: center;\">
            <div style=\"font-size: 18px; font-weight: bold; margin-bottom: 5px;\">[deadline_text]</div>
            <div style=\"font-size: 14px; opacity: 0.9;\">募集締切</div>
        </div>
    </div>
</div>

<!-- 概要セクション -->
<div style=\"background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px; border-left: 5px solid #28a745;\">
    <h2 style=\"color: #28a745; margin: 0 0 15px 0; font-size: 22px; display: flex; align-items: center;\">
        <span style=\"background: #28a745; color: white; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 16px;\">📋</span>
        この助成金の特徴
    </h2>
    <div style=\"font-size: 16px; line-height: 1.8;\">
        [概要に基づく詳細な説明を3-4行で記述。対象者、利用目的、条件などを含める]
    </div>
</div>

<!-- 重要情報テーブル -->
<div style=\"background: white; border: 2px solid #e9ecef; border-radius: 12px; overflow: hidden; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);\">
    <div style=\"background: #0073aa; color: white; padding: 15px; text-align: center;\">
        <h3 style=\"margin: 0; font-size: 18px; font-weight: 600;\">📊 助成金詳細情報</h3>
    </div>
    <table style=\"width: 100%; border-collapse: collapse;\">
        <tr style=\"border-bottom: 1px solid #e9ecef;\">
            <td style=\"padding: 15px; background: #f8f9fa; font-weight: 600; width: 30%; border-right: 1px solid #e9ecef;\">💰 補助額上限</td>
            <td style=\"padding: 15px; font-size: 20px; font-weight: bold; color: #dc3545;\">[max_amount]</td>
        </tr>
        <tr style=\"border-bottom: 1px solid #e9ecef;\">
            <td style=\"padding: 15px; background: #f8f9fa; font-weight: 600; border-right: 1px solid #e9ecef;\">📈 補助率</td>
            <td style=\"padding: 15px; font-weight: 600; color: #28a745;\">[subsidy_rate]</td>
        </tr>
        <tr style=\"border-bottom: 1px solid #e9ecef;\">
            <td style=\"padding: 15px; background: #f8f9fa; font-weight: 600; border-right: 1px solid #e9ecef;\">🏢 実施組織</td>
            <td style=\"padding: 15px;\">[organization]</td>
        </tr>
        <tr>
            <td style=\"padding: 15px; background: #f8f9fa; font-weight: 600; border-right: 1px solid #e9ecef;\">🌍 対象地域</td>
            <td style=\"padding: 15px;\">[target_area]</td>
        </tr>
    </table>
</div>

<!-- 申請のポイント -->
<div style=\"background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #ffc107;\">
    <h2 style=\"color: #856404; margin: 0 0 20px 0; font-size: 22px; display: flex; align-items: center;\">
        <span style=\"background: #ffc107; color: #212529; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 16px;\">💡</span>
        申請成功のポイント
    </h2>
    <div style=\"display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;\">
        <div style=\"background: rgba(255,255,255,0.7); padding: 20px; border-radius: 8px;\">
            <h4 style=\"color: #856404; margin: 0 0 10px 0; font-size: 16px;\">✅ 申請時の注意点</h4>
            <p style=\"margin: 0; font-size: 14px; line-height: 1.6;\">[申請時の重要な注意点を2-3点で説明]</p>
        </div>
        <div style=\"background: rgba(255,255,255,0.7); padding: 20px; border-radius: 8px;\">
            <h4 style=\"color: #856404; margin: 0 0 10px 0; font-size: 16px;\">🎯 採択される秘訣</h4>
            <p style=\"margin: 0; font-size: 14px; line-height: 1.6;\">[採択されるためのコツを2-3点で説明]</p>
        </div>
    </div>
</div>

<!-- 対象者・利用目的 -->
<div style=\"background: #e8f5e8; padding: 25px; border-radius: 12px; margin-bottom: 30px; border-left: 5px solid #28a745;\">
    <h2 style=\"color: #155724; margin: 0 0 15px 0; font-size: 22px; display: flex; align-items: center;\">
        <span style=\"background: #28a745; color: white; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 16px;\">🎯</span>
        こんな方におすすめ
    </h2>
    <div style=\"display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px;\">
        [利用目的に基づいて、対象者や利用場面を3-4個のタグ形式で表示]
        <span style=\"background: #28a745; color: white; padding: 8px 16px; border-radius: 25px; font-size: 14px; font-weight: 600;\">新規事業者</span>
        <span style=\"background: #17a2b8; color: white; padding: 8px 16px; border-radius: 25px; font-size: 14px; font-weight: 600;\">販路拡大希望</span>
        <span style=\"background: #6f42c1; color: white; padding: 8px 16px; border-radius: 25px; font-size: 14px; font-weight: 600;\">設備投資計画</span>
    </div>
</div>

<!-- CTA（行動喚起）セクション -->
<div style=\"background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 20px; box-shadow: 0 8px 25px rgba(220,53,69,0.3);\">
    <h2 style=\"margin: 0 0 15px 0; font-size: 24px; font-weight: 700;\">🚀 今すぐ申請を検討しましょう</h2>
    <p style=\"margin: 0 0 20px 0; font-size: 16px; opacity: 0.95; line-height: 1.6;\">[deadline_text]が締切です。申請には準備期間が必要なため、お早めの検討をおすすめします。</p>
    <div style=\"background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; display: inline-block;\">
        <a href=\"[official_url]\" style=\"color: white; text-decoration: none; font-weight: 600; font-size: 16px;\">📄 詳細・申請はこちら</a>
    </div>
</div>

<!-- 注意事項 -->
<div style=\"background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 5px solid #dc3545; margin-bottom: 20px;\">
    <h4 style=\"color: #721c24; margin: 0 0 10px 0; font-size: 16px;\">⚠️ 重要な注意事項</h4>
    <ul style=\"margin: 0; padding-left: 20px; color: #721c24;\">
        <li style=\"margin-bottom: 8px;\">申請書類は締切日の数日前には提出することをおすすめします</li>
        <li style=\"margin-bottom: 8px;\">詳細な要件は必ず公式サイトでご確認ください</li>
        <li style=\"margin-bottom: 8px;\">不明点は実施組織に直接お問い合わせください</li>
    </ul>
</div>

</div>

このような構成で、視覚的に訴求力があり、読者の行動を促進する魅力的な記事を作成してください。各セクションの内容は、提供されたデータに基づいて具体的で実用的な情報を記載してください。";
    }
    
    /**
     * 抜粋生成用プロンプト（改良版）
     */
    public static function get_enhanced_excerpt_prompt() {
        return "以下の助成金情報から、読者の注意を引く魅力的な抜粋を120文字以内で作成してください。

【助成金情報】
助成金名: [title]
概要: [overview]
補助額上限: [max_amount]
募集終了日: [deadline_text]

【出力要件】
- 具体的な補助額を含める
- 緊急性や魅力を伝える
- 読者が「詳細を読みたい」と感じる表現
- 120文字以内で簡潔に

例：「最大[max_amount]の補助が受けられる[title]。[deadline_text]締切のため要チェック！事業拡大を考えている方は必見です。」

このような形式で、インパクトのある抜粋を作成してください。";
    }
    
    /**
     * 要約生成用プロンプト（改良版）
     */
    public static function get_enhanced_summary_prompt() {
        return "以下の助成金情報を、要点を整理した3行の要約にまとめてください。

【助成金情報】
助成金名: [title]
概要: [overview]
補助額上限: [max_amount]
募集終了日: [deadline_text]
実施組織: [organization]

【出力要件】
1行目：助成金の目的と対象（50文字程度）
2行目：補助額・補助率などの条件（50文字程度）
3行目：締切と申請のポイント（50文字程度）

【出力例】
💡 [利用目的]を支援する助成金制度
💰 最大[max_amount]、[補助率情報]で資金サポート
⏰ [deadline_text]締切、早期の申請準備が重要

このような形式で、視覚的にわかりやすい要約を作成してください。";
    }
    
    /**
     * キーワード生成用プロンプト
     */
    public static function get_keywords_prompt() {
        return "以下の助成金情報から、SEO効果の高いキーワードを10個程度抽出してください。

【助成金情報】
助成金名: [title]
概要: [overview]
利用目的: [use_purpose]
対象地域: [target_area]
実施組織: [organization]

【キーワード抽出条件】
- 検索されやすい実用的なキーワード
- 業界用語と一般用語のバランス
- 地域名を含む（該当する場合）
- 補助金、助成金、支援金などの関連語彙
- カンマ区切りで出力

出力例：補助金, 助成金, 新規事業, 設備投資, [対象地域], 中小企業支援, [業界名], 資金調達, 事業拡大, 創業支援";
    }
    
    /**
     * 対象者説明生成用プロンプト
     */
    public static function get_target_audience_prompt() {
        return "以下の助成金情報から、対象者を具体的かつわかりやすく説明してください。

【助成金情報】
助成金名: [title]
概要: [overview]
利用目的: [use_purpose]
対象地域: [target_area]

【出力要件】
- 具体的な業種・事業者タイプ
- 事業規模（従業員数、売上等）の目安
- 地域的な制限
- 申請条件を満たす典型的なケース
- 200文字程度で簡潔に

【出力例】
「◆対象：[対象地域]で事業を営む中小企業・個人事業主
◆業種：[利用目的に関連する業種]
◆規模：従業員○名以下、年商○億円以下
◆条件：新規事業展開や設備投資を計画している事業者
◆特に：創業3年以内のスタートアップ企業におすすめ」

このような形式で、申請を検討すべき対象者を明確に示してください。";
    }
    
    /**
     * 申請のコツ生成用プロンプト
     */
    public static function get_application_tips_prompt() {
        return "以下の助成金の申請成功率を高めるための実践的なアドバイスを作成してください。

【助成金情報】
助成金名: [title]
概要: [overview]
実施組織: [organization]
募集終了日: [deadline_text]

【出力要件】
- 申請書作成のポイント
- 必要書類の準備方法
- 審査で重視されるポイント
- よくある失敗例とその対策
- 300文字程度

【出力構成】
📝 申請書のポイント：[具体的なアドバイス]
📋 必要書類：[重要な書類と注意点]
⭐ 審査通過のコツ：[審査官が見るポイント]
⚠️ 注意事項：[よくある失敗とその回避法]
⏰ スケジュール：[効率的な準備スケジュール]

実用的で具体的なアドバイスを提供してください。";
    }
    
    /**
     * 要件整理用プロンプト
     */
    public static function get_requirements_prompt() {
        return "以下の助成金の申請要件を体系的に整理して、チェックリスト形式でまとめてください。

【助成金情報】
助成金名: [title]
概要: [overview]
対象地域: [target_area]
利用目的: [use_purpose]

【出力要件】
- 必須要件と推奨要件を区別
- チェック可能な項目として記載
- 数値基準があれば具体的に記載
- 400文字程度

【出力構成】
■ 必須要件（すべて満たす必要があります）
☑️ 事業所在地：[target_area]に本店または主たる事業所を有する
☑️ 事業規模：[具体的な従業員数・売上基準等]
☑️ 事業内容：[利用目的に関連する事業内容]
☑️ 申請時期：[具体的な時期的要件]

■ 推奨要件（満たしていると採択率向上）
☑️ [審査で有利になる条件1]
☑️ [審査で有利になる条件2]
☑️ [審査で有利になる条件3]

■ 除外要件（以下に該当する場合は申請不可）
❌ [申請できない条件1]
❌ [申請できない条件2]

このような形式で、申請者が自己チェックできる要件リストを作成してください。";
    }
    
    /**
     * 実施組織抽出用プロンプト
     */
    public static function get_organization_prompt() {
        return "以下の助成金情報から実施組織名を正確に抽出し、正式名称で回答してください。

【助成金情報】
助成金名: [title]
概要: [overview]
実施組織情報: [organization]

【抽出条件】
- 正式な組織名称（略称ではなく）
- 「○○省」「○○庁」「○○市」「○○協会」など
- 複数の組織が関わる場合は主たる実施組織
- 100文字以内で簡潔に

組織名のみを回答してください。例：「経済産業省」「○○市産業振興課」「一般社団法人○○協会」";
    }
    
    /**
     * 申請難易度判定用プロンプト
     */
    public static function get_difficulty_prompt() {
        return "以下の助成金の申請難易度を判定してください。

【助成金情報】
助成金名: [title]
概要: [overview]
実施組織: [organization]

【判定基準】
- 必要書類の複雑さ
- 審査基準の厳格さ
- 競争率の高さ
- 申請手続きの煩雑さ

【難易度レベル】
- 「易しい」：必要書類が少なく、審査基準が明確
- 「普通」：一般的な申請手続き、中程度の競争率
- 「難しい」：複雑な審査、高い競争率、専門的な書類が必要

「易しい」「普通」「難しい」のいずれかで回答してください。";
    }
    
    /**
     * 採択率推定用プロンプト
     */
    public static function get_success_rate_prompt() {
        return "以下の助成金の一般的な採択率を推定してください。

【助成金情報】
助成金名: [title]
概要: [overview]
補助額上限: [max_amount]
実施組織: [organization]

【推定要素】
- 実施組織の種類（国・自治体・民間）
- 補助金額の規模
- 申請条件の厳格さ
- 過去の傾向や類似制度の実績

【出力形式】
推定採択率：XX%
（例：30%、50%、70%など）

0-100%の数値で回答してください。";
    }
}

/**
 * プロンプトテンプレートの設定関数
 */
function giji_improved_set_enhanced_prompts() {
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
        update_option('giji_improved_' . $key, $prompt);
    }
}
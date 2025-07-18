<?php

namespace Breadfish\BasicGuard;

/**
 * Gutenbergブロック機能を管理するクラス
 */
class Block {

    /**
     * 初期化処理
     */
    public function init() {
        add_action( 'init', array( $this, 'register_block' ), 20 );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
    }

        /**
     * ブロックを登録します
     */
    public function register_block() {
        $build_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'build/';
        $block_json_path = $build_dir . 'block.json';

        error_log('BF Basic Guard: Attempting to register block...');
        error_log('BF Basic Guard: Block JSON path: ' . $block_json_path);
        error_log('BF Basic Guard: Block JSON exists: ' . (file_exists($block_json_path) ? 'yes' : 'no'));

        if ( file_exists( $block_json_path ) ) {
            // block.jsonで "render": "file:./render.php" が指定されているので
            // WordPressが自動的にrender.phpを読み込みます
            $result = register_block_type( $block_json_path );

            if ( $result ) {
                error_log('BF Basic Guard: Block registered successfully: ' . $result->name);
            } else {
                error_log('BF Basic Guard: Block registration failed');
            }
        } else {
            error_log('BF Basic Guard: Block JSON file not found');
        }
    }



            /**
     * エディタ用アセットをエンキューします
     */
    public function enqueue_editor_assets() {
        $build_dir_path = plugin_dir_path( dirname( __FILE__ ) ) . 'build/';
        $build_dir_url = plugin_dir_url( dirname( __FILE__ ) ) . 'build/';

        error_log('BF Basic Guard: Enqueuing editor assets...');
        error_log('BF Basic Guard: Build dir path: ' . $build_dir_path);

        // JavaScriptファイルの読み込み
        $js_file = $build_dir_path . 'index.js';
        $asset_file = $build_dir_path . 'index.asset.php';

        error_log('BF Basic Guard: JS file exists: ' . (file_exists($js_file) ? 'yes' : 'no'));
        error_log('BF Basic Guard: Asset file exists: ' . (file_exists($asset_file) ? 'yes' : 'no'));

        if ( file_exists( $js_file ) && file_exists( $asset_file ) ) {
            $asset_data = include $asset_file;

            wp_enqueue_script(
                'bf-basic-guard-editor',
                $build_dir_url . 'index.js',
                $asset_data['dependencies'],
                $asset_data['version']
            );

            // エディタ用のデータを渡す
            wp_localize_script(
                'bf-basic-guard-editor',
                'bfBasicGuardEditor',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'bf_basic_guard_file_list_nonce' ),
                    'baseDirectory' => get_option( 'bf_basic_guard_target_directory', '' ),
                )
            );

            error_log('BF Basic Guard: Editor script enqueued successfully');
        }

        // CSSファイルの読み込み
        $css_file = $build_dir_path . 'index.css';
        if ( file_exists( $css_file ) ) {
            wp_enqueue_style(
                'bf-basic-guard-editor',
                $build_dir_url . 'index.css',
                array(),
                filemtime( $css_file )
            );

            error_log('BF Basic Guard: Editor CSS enqueued successfully');
        }
    }

    /**
     * フロントエンド用スタイルをエンキューします
     */
    public function enqueue_frontend_styles() {
        // ブロックが使用されている場合のみスタイルとスクリプトを読み込み
        if ( has_block( 'bf-basic-guard/downloader' ) ) {
            $build_dir_path = plugin_dir_path( dirname( __FILE__ ) ) . 'build/';
            $build_dir_url = plugin_dir_url( dirname( __FILE__ ) ) . 'build/';
            $css_file = $build_dir_path . 'index.css';

            if ( file_exists( $css_file ) ) {
                wp_enqueue_style(
                    'bf-basic-guard-frontend',
                    $build_dir_url . 'index.css',
                    array(),
                    filemtime( $css_file )
                );
            }

            // jQuery依存のスクリプトを読み込み（AJAX機能のため）
            wp_enqueue_script( 'jquery' );

            // AJAX用の設定をlocalize
            wp_localize_script( 'jquery', 'bf_basic_guard_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'bf_basic_guard_nonce' ),
                'strings' => array(
                    'password_required' => __( 'パスワードが必要です', 'bf-basic-guard' ),
                    'enter_password' => __( 'パスワードを入力してください:', 'bf-basic-guard' ),
                    'incorrect_password' => __( 'パスワードが正しくありません', 'bf-basic-guard' ),
                    'download_error' => __( 'ダウンロードエラーが発生しました', 'bf-basic-guard' ),
                    'processing' => __( '処理中...', 'bf-basic-guard' ),
                )
            ));

            // カスタムスタイル用CSS - エディタスタイルと統一
            $custom_css = '
                /* デフォルトスタイル（ボタン）の追加調整 */
                .wp-block-bf-basic-guard-downloader .bf-download-btn {
                    font-family: inherit;
                    line-height: 1.4;
                }

                /* リンクスタイルの調整 */
                .wp-block-bf-basic-guard-downloader.is-style-link .bf-download-btn {
                    background: none !important;
                    color: #0073aa !important;
                    text-decoration: underline !important;
                    padding: 0 !important;
                    border: none !important;
                    box-shadow: none !important;
                    font-size: 16px !important;
                    font-weight: 500 !important;
                    display: inline-block !important;
                    transform: none !important;
                    transition: color 0.2s ease !important;
                }
                .wp-block-bf-basic-guard-downloader.is-style-link .bf-download-btn:hover {
                    background: none !important;
                    color: #005177 !important;
                    text-decoration: underline !important;
                    transform: none !important;
                    box-shadow: none !important;
                }
            ';
            wp_add_inline_style( 'bf-basic-guard-frontend', $custom_css );
        }
    }
}
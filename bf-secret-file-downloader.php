<?php
/**
 * Plugin Name: BF Secret File Downloader
 * Plugin URI:
 * Description: BASIC認証もしくは非公開エリアに置かれたディレクトリに配置されたファイルを管理するWordPressプラグイン。ファイル管理、ディレクトリ管理、ダウンロード機能、ダウンロード用ボタンブロック機能を提供します。
 * Version: 1.0.0
 * Author:
 * Author URI:
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bf-secret-file-downloader
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package BfSecretFileDownloader
 */

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグインの定数を定義
define( 'BF_SECRET_FILE_DOWNLOADER_VERSION', '1.0.0' );
define( 'BF_SECRET_FILE_DOWNLOADER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BF_SECRET_FILE_DOWNLOADER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * オートローダー関数
 * 名前空間に基づいてクラスファイルを自動読み込みします
 *
 * @param string $class_name クラス名（完全修飾名）
 */
function bf_secret_file_downloader_autoloader( $class_name ) {
    // 名前空間のプレフィックスをチェック
    $prefix = 'Breadfish\\SecretFileDownloader\\';
    $len = strlen( $prefix );

    if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
        return;
    }

    // クラス名から名前空間プレフィックスを削除
    $relative_class = substr( $class_name, $len );

    // ファイルパスを構築
    $file = BF_SECRET_FILE_DOWNLOADER_PLUGIN_DIR . 'inc/' . str_replace( '\\', '/', $relative_class ) . '.php';

    // ファイルが存在する場合は読み込み
    if ( file_exists( $file ) ) {
        require $file;
    }
}

// オートローダーを登録
spl_autoload_register( 'bf_secret_file_downloader_autoloader' );

/**
 * プラグインを初期化します
 */
function bf_secret_file_downloader_init() {
    // テキストドメインを読み込み
    load_plugin_textdomain( 'bf-secret-file-downloader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // 管理画面でのみ実行
    if ( is_admin() ) {
        $admin = new \Breadfish\SecretFileDownloader\Admin();
        $admin->init(); // フックを明示的に初期化
    }

    // フロントエンド機能を初期化
    $frontend = new \Breadfish\SecretFileDownloader\FrontEnd();
    $frontend->init();

    // Gutenbergブロック機能を初期化
    $block = new \Breadfish\SecretFileDownloader\Block();
    $block->init();
}

// プラグインを初期化
add_action( 'init', 'bf_secret_file_downloader_init' );
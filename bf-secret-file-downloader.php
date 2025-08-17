<?php
/**
 * Plugin Name: BF Secret File Downloader
 * Plugin URI: https://sfd.breadfish.jp/
 * Description: Manage and provide download functionality for files protected by Basic Authentication or located in private areas. Provides file management, directory management, download functionality, and Gutenberg block features.
 * Version: 1.0.0
 * Author: Breadfish
 * Author URI: https://breadfish.jp/
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
 * テキストドメインを読み込みます
 */
function bf_secret_file_downloader_load_textdomain() {
    // サイトのロケールを取得
    $locale = determine_locale();
    // 翻訳ファイルのパスを指定
    $path = plugin_dir_path( __FILE__ ) . 'languages';

    // デバッグ用ログ
    error_log( 'BF Secret File Downloader: Current locale: ' . $locale );
    error_log( 'BF Secret File Downloader: Translation path: ' . $path );
    error_log( 'BF Secret File Downloader: WPLANG option: ' . get_option( 'WPLANG', 'not set' ) );
    error_log( 'BF Secret File Downloader: get_locale(): ' . get_locale() );

    // 英語の設定のみ翻訳ファイルを読み込み
    if ( strpos( $locale, 'en' ) === 0 ) {
        error_log( 'BF Secret File Downloader: Loading English translation file' );
        // PHPファイルの翻訳読み込み
        $mo_file = $path . '/bf-secret-file-downloader-en_US.mo';
        error_log( 'BF Secret File Downloader: MO file path: ' . $mo_file );
        error_log( 'BF Secret File Downloader: MO file exists: ' . ( file_exists( $mo_file ) ? 'yes' : 'no' ) );

        $result = load_textdomain( 'bf-secret-file-downloader', $mo_file );
        error_log( 'BF Secret File Downloader: Translation load result: ' . ( $result ? 'success' : 'failed' ) );

    } else {
        error_log( 'BF Secret File Downloader: Not loading translation file (non-English locale)' );
    }

    // 翻訳テスト
    $test_string = __( 'BF Secret File Downloader', 'bf-secret-file-downloader' );
    error_log( 'BF Secret File Downloader: Test translation: ' . $test_string );

    // より詳細な翻訳テスト
    $test_string2 = __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' );
    error_log( 'BF Secret File Downloader: Test translation 2: ' . $test_string2 );

    // 管理画面用の翻訳テスト
    $test_string3 = __( 'BASIC認証で保護されたファイルを管理します。', 'bf-secret-file-downloader' );
    error_log( 'BF Secret File Downloader: Test translation 3: ' . $test_string3 );

    // メニュー用の翻訳テスト
    $test_string4 = __( 'BF Secret File Downloader', 'bf-secret-file-downloader' );
    error_log( 'BF Secret File Downloader: Test translation 4: ' . $test_string4 );
}

/**
 * プラグインを初期化します
 */
function bf_secret_file_downloader_init() {

    // テキストドメインを読み込み
    bf_secret_file_downloader_load_textdomain();

    // 管理画面でのみ実行
    if ( is_admin() ) {
        $admin = new \Breadfish\SecretFileDownloader\Admin();
        $admin->init(); // フックを明示的に初期化
    }

    // フロントエンド機能を初期化
    $frontend = new \Breadfish\SecretFileDownloader\FrontEnd();
    $frontend->init();

}

add_action( 'init', 'bf_secret_file_downloader_init' );

/**
 * プラグインアクティベーション時の処理
 */
function bf_secret_file_downloader_activate() {
    // デフォルト設定を追加
    if ( ! get_option( 'bf_basic_guard_auth_methods' ) ) {
        add_option( 'bf_basic_guard_auth_methods', array( 'logged_in' ) );
    }

    if ( ! get_option( 'bf_basic_guard_allowed_roles' ) ) {
        add_option( 'bf_basic_guard_allowed_roles', array( 'administrator' ) );
    }

    if ( ! get_option( 'bf_basic_guard_simple_auth_password' ) ) {
        add_option( 'bf_basic_guard_simple_auth_password', '' );
    }
}

register_activation_hook( __FILE__, 'bf_secret_file_downloader_activate' );

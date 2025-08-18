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
 * セキュアディレクトリのパスを取得します
 *
 * @return string セキュアディレクトリのパス（存在しない場合は空文字列）
 */
function bf_secret_file_downloader_get_secure_directory() {
    $secure_id = get_option( 'bf_sfd_secure_directory_id', '' );
    if ( ! $secure_id ) {
        return '';
    }
    
    $uploads_dir = wp_upload_dir();
    return $uploads_dir['basedir'] . '/bf-secret-file-downloader/' . $secure_id;
}

/**
 * セキュアなディレクトリを作成します
 */
function bf_secret_file_downloader_create_secure_directory() {
    // 既にディレクトリIDが設定されている場合はスキップ
    if ( get_option( 'bf_sfd_secure_directory_id' ) ) {
        return;
    }

    // ランダムな文字列を生成（32文字の英数字）
    $random_id = bin2hex( random_bytes( 16 ) );
    
    // wp-content/uploads配下にディレクトリを作成
    $uploads_dir = wp_upload_dir();
    $secure_base_dir = $uploads_dir['basedir'] . '/bf-secret-file-downloader';
    $secure_dir = $secure_base_dir . '/' . $random_id;

    // ディレクトリを作成
    if ( ! wp_mkdir_p( $secure_dir ) ) {
        error_log( 'BF Secret File Downloader: Failed to create secure directory: ' . $secure_dir );
        return;
    }

    // .htaccessファイルを作成してアクセスを完全に遮断
    $htaccess_content = "# Deny all access\nDeny from all\n";
    file_put_contents( $secure_dir . '/.htaccess', $htaccess_content );

    // indexファイルも作成してさらなる保護（拡張子なしで目立たなくする）
    $index_content = "<?php\n// Silence is golden.\nexit;";
    file_put_contents( $secure_dir . '/index', $index_content );

    // ディレクトリIDをoptionsに保存
    add_option( 'bf_sfd_secure_directory_id', $random_id );
    
    // 対象ディレクトリを自動設定
    add_option( 'bf_sfd_target_directory', $secure_dir );

    error_log( 'BF Secret File Downloader: Secure directory created: ' . $secure_dir );
}

/**
 * プラグインアクティベーション時の処理
 */
function bf_secret_file_downloader_activate() {
    // セキュアなディレクトリを作成
    bf_secret_file_downloader_create_secure_directory();

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

/**
 * 安全なパスを構築します（ディレクトリトラバーサル防止）
 *
 * @param string $base_directory ベースディレクトリ
 * @param string $relative_path 相対パス
 * @return string 安全なパス
 */
function bf_secret_file_downloader_build_safe_path( $base_directory, $relative_path ) {
    // 空の相対パスの場合はベースディレクトリを返す
    if ( empty( $relative_path ) ) {
        return $base_directory;
    }

    // 危険な文字列をチェック
    if ( strpos( $relative_path, '..' ) !== false || strpos( $relative_path, '//' ) !== false ) {
        return $base_directory;
    }

    // パスを構築
    $full_path = $base_directory . DIRECTORY_SEPARATOR . ltrim( $relative_path, DIRECTORY_SEPARATOR );

    // パスを正規化
    $normalized_path = realpath( $full_path );
    
    // 正規化に失敗した場合や、ベースディレクトリ外の場合はベースディレクトリを返す
    if ( $normalized_path === false || strpos( $normalized_path, realpath( $base_directory ) ) !== 0 ) {
        return $base_directory;
    }

    return $normalized_path;
}

/**
 * ディレクトリアクセスが許可されているかチェックします
 *
 * @param string $path チェックするパス
 * @return bool 許可されている場合はtrue
 */
function bf_secret_file_downloader_is_allowed_directory( $path ) {
    $real_path = realpath( $path );
    if ( $real_path === false ) {
        return false;
    }

    // シンボリックリンクの場合は拒否
    if ( is_link( $path ) ) {
        error_log( 'BF Secret File Downloader: シンボリックリンクへのアクセス試行を検出: ' . $path );
        return false;
    }

    // 基本となる対象ディレクトリを取得
    $target_directory = bf_secret_file_downloader_get_secure_directory();
    if ( empty( $target_directory ) ) {
        return false;
    }

    $real_target_directory = realpath( $target_directory );
    if ( $real_target_directory === false ) {
        return false;
    }

    // セキュアディレクトリ内かつ存在するディレクトリのみ許可
    return strpos( $real_path, $real_target_directory ) === 0 && is_dir( $real_path );
}

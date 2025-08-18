<?php
/**
 * 設定ページを管理するクラス
 *
 * @package BfBasicGuard
 */

namespace Breadfish\SecretFileDownloader\Admin;


// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SettingsPage クラス
 * 設定機能を管理します
 */
class SettingsPage {

    /**
     * ページスラッグ
     */
    const PAGE_SLUG = 'bf-secret-file-downloader-settings';

    /**
     * コンストラクタ
     */
    public function __construct() {
        // コンストラクタではフックを登録しない
    }

    /**
     * フックを初期化します
     */
    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_bf_sfd_reset_settings', array( $this, 'ajax_reset_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * 設定を登録します
     */
    public function register_settings() {
        register_setting( 'bf_sfd_settings', 'bf_sfd_max_file_size', array(
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => array( $this, 'sanitize_file_size' )
        ) );

        // 認証設定を追加
        register_setting( 'bf_sfd_settings', 'bf_sfd_auth_methods', array(
            'type' => 'array',
            'default' => array(),
            'sanitize_callback' => array( $this, 'sanitize_auth_methods' )
        ) );

        register_setting( 'bf_sfd_settings', 'bf_sfd_allowed_roles', array(
            'type' => 'array',
            'default' => array(),
            'sanitize_callback' => array( $this, 'sanitize_roles' )
        ) );

        register_setting( 'bf_sfd_settings', 'bf_sfd_simple_auth_password', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => array( $this, 'sanitize_password' )
        ) );
    }


    /**
     * 設定をリセットします
     */
    public function ajax_reset_settings() {
        // セキュリティチェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_sfd_browse_nonce', 'nonce' );

        // 設定を削除
        delete_option( 'bf_sfd_target_directory' );
        delete_option( 'bf_sfd_secure_directory_id' );
        delete_option( 'bf_sfd_max_file_size' );
        delete_option( 'bf_sfd_auth_methods' );
        delete_option( 'bf_sfd_allowed_roles' );
        delete_option( 'bf_sfd_simple_auth_password' );

        // ディレクトリパスワードもクリア
        $this->clear_all_directory_passwords();

        wp_send_json_success( array( 'message' => '設定がリセットされました。' ) );
    }

    /**
     * ページを表示します
     */
        public function render() {
        // ビューで使用するデータを準備
        $import = $this->prepare_data();

        // ViewRendererを使用してビューをレンダリング
        \Breadfish\SecretFileDownloader\ViewRenderer::admin( 'settings.php', $import );
    }



    /**
     * ビューで使用するデータを準備します
     *
     * @return array ビューで使用するデータ
     */
    private function prepare_data() {
        return array(
            'enable_auth' => $this->get_enable_auth(),
            'max_file_size' => $this->get_max_file_size(),
            'log_downloads' => $this->get_log_downloads(),
            'security_level' => $this->get_security_level(),
            'target_directory' => $this->get_target_directory(),
            'auth_methods' => $this->get_auth_methods(),
            'allowed_roles' => $this->get_allowed_roles(),
            'simple_auth_password' => $this->get_simple_auth_password(),

            'nonce' => wp_create_nonce( 'bf_sfd_browse_nonce' ),
        );
    }

    /**
     * BASIC認証設定を取得します
     *
     * @return bool BASIC認証有効フラグ
     */
    private function get_enable_auth() {
        return (bool) get_option( 'bf_sfd_enable_auth', false );
    }

    /**
     * 最大ファイルサイズ設定を取得します
     *
     * @return int 最大ファイルサイズ（MB）
     */
    private function get_max_file_size() {
        return (int) get_option( 'bf_sfd_max_file_size', 10 );
    }

    /**
     * ダウンロードログ設定を取得します
     *
     * @return bool ダウンロードログ有効フラグ
     */
    private function get_log_downloads() {
        return (bool) get_option( 'bf_sfd_log_downloads', true );
    }

    /**
     * セキュリティレベル設定を取得します
     *
     * @return string セキュリティレベル
     */
    private function get_security_level() {
        return get_option( 'bf_sfd_security_level', 'medium' );
    }

    /**
     * 対象ディレクトリ設定を取得します
     *
     * @return string 対象ディレクトリ
     */
    private function get_target_directory() {
        return bf_secret_file_downloader_get_secure_directory();
    }

    /**
     * 認証方法設定を取得します
     *
     * @return array 認証方法の配列
     */
    private function get_auth_methods() {
        return get_option( 'bf_sfd_auth_methods', array() );
    }

    /**
     * 許可するユーザーロール設定を取得します
     *
     * @return array 許可するユーザーロールの配列
     */
    private function get_allowed_roles() {
        return get_option( 'bf_sfd_allowed_roles', array() );
    }

    /**
     * 簡易認証パスワード設定を取得します
     *
     * @return string 簡易認証パスワード
     */
    private function get_simple_auth_password() {
        return get_option( 'bf_sfd_simple_auth_password', '' );
    }



    /**
     * サニタイズ: ブール値
     *
     * @param mixed $value 値
     * @return bool サニタイズされたブール値
     */
    public function sanitize_boolean( $value ) {
        return (bool) $value;
    }

    /**
     * サニタイズ: パスワード
     *
     * @param string $value パスワード
     * @return string サニタイズされたパスワード
     */
    public function sanitize_password( $value ) {
        $sanitized_value = sanitize_text_field( $value );

        // 簡易認証が有効かチェック
        $auth_methods = $_POST['bf_sfd_auth_methods'] ?? array();
        if ( is_array( $auth_methods ) && in_array( 'simple_auth', $auth_methods ) ) {
            // 簡易認証が有効でパスワードが空の場合
            if ( empty( $sanitized_value ) ) {
                add_settings_error(
                    'bf_sfd_simple_auth_password',
                    'password_required',
                    __( '簡易認証を有効にする場合は、パスワードの設定が必要です。', 'bf-secret-file-downloader' ),
                    'error'
                );
                // 現在のパスワードを維持（空にしない）
                return get_option( 'bf_sfd_simple_auth_password', '' );
            }
        }

        return $sanitized_value;
    }

    /**
     * サニタイズ: ファイルサイズ
     *
     * @param mixed $value ファイルサイズ
     * @return int サニタイズされたファイルサイズ
     */
    public function sanitize_file_size( $value ) {
        $size = (int) $value;
        return max( 1, min( 100, $size ) ); // 1-100MBの範囲に制限
    }

    /**
     * 認証方法をサニタイズします
     *
     * @param array $value 認証方法の配列
     * @return array サニタイズされた認証方法の配列
     */
    public function sanitize_auth_methods( $value ) {
        $allowed_methods = array( 'logged_in', 'simple_auth' );

        // $valueがnullまたは配列でない場合は空配列を返す
        if ( ! is_array( $value ) ) {
            return array();
        }

        return array_intersect( $allowed_methods, $value );
    }

    /**
     * 許可するユーザーロールをサニタイズします
     *
     * @param array $value ユーザーロールの配列
     * @return array サニタイズされたユーザーロールの配列
     */
    public function sanitize_roles( $value ) {
        $allowed_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

        // $valueがnullまたは配列でない場合は空配列を返す
        if ( ! is_array( $value ) ) {
            return array();
        }

        return array_intersect( $allowed_roles, $value );
    }



    /**
     * すべてのディレクトリパスワードをクリアします
     */
    private function clear_all_directory_passwords() {
        delete_option( 'bf_sfd_directory_passwords' );
    }


    /**
     * ページタイトルを取得します
     *
     * @return string ページタイトル
     */
    public function get_page_title() {
        return __( '設定', 'bf-secret-file-downloader' );
    }

    /**
     * メニュータイトルを取得します
     *
     * @return string メニュータイトル
     */
    public function get_menu_title() {
        return __( '設定', 'bf-secret-file-downloader' );
    }

    /**
     * 管理画面のアセット（CSS/JS）をエンキューします
     *
     * @param string $hook_suffix 現在の管理画面のフックサフィックス
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // 設定ページでのみアセットを読み込む
        if ( strpos( $hook_suffix, self::PAGE_SLUG ) === false ) {
            return;
        }

        // CSSファイルをエンキュー
        wp_enqueue_style(
            'bf-sfd-admin-settings',
            plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/admin-settings.css',
            array(),
            '1.0.0'
        );
    }

}
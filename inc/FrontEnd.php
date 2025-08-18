<?php
/**
 * フロントエンド側のファイルダウンローダーを管理するクラス
 *
 * @package BfBasicGuard
 */

namespace Breadfish\SecretFileDownloader;


// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * FrontEnd クラス
 * フロントエンド側のファイルダウンロード機能を管理します
 */
class FrontEnd {

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
        // セッション開始
        if ( ! session_id() ) {
            session_start();
        }

        // フロントエンドでのダウンロード処理をフック
        add_action( 'template_redirect', array( $this, 'handle_file_download' ) );
    }

    /**
     * フロントエンドでのファイルダウンロード処理
     */
    public function handle_file_download() {
        // pathパラメータが存在するかチェック（ダウンロード要求の確認）
        $file_path = sanitize_text_field( $_GET['path'] ?? '' );
        if ( empty( $file_path ) ) {
            return; // ダウンロード要求でない場合は処理を終了
        }

        // ダウンロードフラグを取得（デフォルトはダウンロード）
        $download_flag = sanitize_text_field( $_GET['dflag'] ?? 'download' );

        // ベースディレクトリを取得
        $base_directory = bf_secret_file_downloader_get_secure_directory();
        if ( empty( $base_directory ) ) {
            wp_die( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ), 500 );
        }

        // フルパスを構築
        $full_path = bf_secret_file_downloader_build_safe_path( $base_directory, $file_path );

        // セキュリティチェック：許可されたディレクトリのみ
        if ( ! bf_secret_file_downloader_is_allowed_directory( dirname( $full_path ) ) ) {
            wp_die( __( 'このファイルへのアクセスは許可されていません。', 'bf-secret-file-downloader' ), 403 );
        }

        // ファイル存在チェック
        if ( ! file_exists( $full_path ) || ! is_file( $full_path ) ) {
            wp_die( __( '指定されたファイルが見つかりません。', 'bf-secret-file-downloader' ), 404 );
        }

        // 読み込み権限チェック
        if ( ! is_readable( $full_path ) ) {
            wp_die( __( 'このファイルを読み取る権限がありません。', 'bf-secret-file-downloader' ), 403 );
        }

        // 認証チェック
        if ( ! $this->check_authentication() ) {
            $this->show_authentication_form();
            exit;
        }

        // ファイル情報を取得
        $filename = basename( $full_path );
        $filesize = filesize( $full_path );
        $mime_type = wp_check_filetype( $filename )['type'] ?? 'application/octet-stream';

        // ダウンロードログを記録（設定が有効な場合）
        if ( get_option( 'bf_sfd_log_downloads', false ) ) {
            $this->log_download( $file_path, $filename );
        }

        // ヘッダーを設定
        if ( ! headers_sent() ) {
            // キャッシュ制御
            header( 'Cache-Control: no-cache, must-revalidate' );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );

            if ( $download_flag === 'display' ) {
                // その場で表示
                header( 'Content-Type: ' . $mime_type );
                header( 'Content-Length: ' . $filesize );
            } else {
                // ダウンロード
                header( 'Content-Type: ' . $mime_type );
                header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
                header( 'Content-Length: ' . $filesize );
            }

            // ファイルを出力
            readfile( $full_path );
        }

        exit;
    }

    /**
     * ダウンロードログを記録します
     *
     * @param string $file_path ファイルパス
     * @param string $filename ファイル名
     */
    private function log_download( $file_path, $filename ) {
        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'file_path' => $file_path,
            'filename' => $filename,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        // ログをデータベースに保存（簡易版）
        $download_logs = get_option( 'bf_sfd_download_logs', array() );
        $download_logs[] = $log_entry;

        // ログ数を制限（最新1000件）
        if ( count( $download_logs ) > 1000 ) {
            $download_logs = array_slice( $download_logs, -1000 );
        }

        update_option( 'bf_sfd_download_logs', $download_logs );
    }

    /**
     * クライアントのIPアドレスを取得します
     *
     * @return string IPアドレス
     */
    private function get_client_ip() {
        $ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

        /**
     * 認証チェックを行います
     *
     * @return bool 認証成功フラグ
     */
    private function check_authentication() {
        // 現在のファイルパスからディレクトリパスを取得
        $file_path = sanitize_text_field( $_GET['path'] ?? '' );
        $directory_path = dirname( $file_path );
        if ( $directory_path === '.' ) {
            $directory_path = '';
        }

        // ディレクトリ固有の認証設定をチェック
        $directory_auth = $this->get_directory_auth( $directory_path );
        if ( $directory_auth !== false ) {
            // ディレクトリ固有の認証設定が優先
            return $this->check_directory_auth( $directory_auth );
        }

        // 共通設定の認証チェック
        $auth_methods = get_option( 'bf_sfd_auth_methods', array( 'logged_in' ) );

        // 認証方法が設定されていない場合はアクセス拒否
        if ( empty( $auth_methods ) ) {
            return false;
        }

        // ログインユーザー認証チェック
        if ( in_array( 'logged_in', $auth_methods ) ) {
            if ( is_user_logged_in() ) {
                // ユーザーロールチェック
                if ( $this->check_user_role() ) {
                    return true;
                }
            }
        }

        // 簡易認証チェック
        if ( in_array( 'simple_auth', $auth_methods ) ) {
            if ( $this->check_simple_auth() ) {
                return true;
            }
        }

        return false;
    }

    /**
     * ユーザーロールをチェックします
     *
     * @return bool ロール許可フラグ
     */
    private function check_user_role() {
        $allowed_roles = get_option( 'bf_sfd_allowed_roles', array( 'administrator' ) );

        if ( empty( $allowed_roles ) ) {
            return false; // ロールが選択されていない場合はアクセス拒否
        }

        $user = wp_get_current_user();
        if ( ! $user->exists() ) {
            return false;
        }

        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, $user->roles ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * ディレクトリ固有の認証設定をチェックします
     *
     * @param array $directory_auth ディレクトリの認証設定
     * @return bool 認証成功フラグ
     */
    private function check_directory_auth( $directory_auth ) {
        $auth_methods = $directory_auth['auth_methods'] ?? array();
        $allowed_roles = $directory_auth['allowed_roles'] ?? array();
        $simple_auth_password = $directory_auth['simple_auth_password'] ?? '';

        // ログインユーザー認証チェック
        if ( in_array( 'logged_in', $auth_methods ) ) {
            if ( is_user_logged_in() ) {
                // ユーザーロールチェック
                if ( $this->check_user_role_for_directory( $allowed_roles ) ) {
                    return true;
                }
            }
        }

        // 簡易認証チェック
        if ( in_array( 'simple_auth', $auth_methods ) ) {
            if ( $this->check_simple_auth_for_directory( $simple_auth_password ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * ディレクトリ固有のユーザーロールをチェックします
     *
     * @param array $allowed_roles 許可するユーザーロールの配列
     * @return bool ロール許可フラグ
     */
    private function check_user_role_for_directory( $allowed_roles ) {
        if ( empty( $allowed_roles ) ) {
            return false; // ロールが選択されていない場合はアクセス拒否
        }

        $user = wp_get_current_user();
        if ( ! $user->exists() ) {
            return false;
        }

        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, $user->roles ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * ディレクトリ固有の簡易認証をチェックします
     *
     * @param string $directory_password ディレクトリの簡易認証パスワード
     * @return bool 簡易認証成功フラグ
     */
    private function check_simple_auth_for_directory( $directory_password ) {
        // セッションから簡易認証済みかチェック
        if ( isset( $_SESSION['bf_directory_simple_auth_verified'] ) && $_SESSION['bf_directory_simple_auth_verified'] === true ) {
            return true;
        }

        // POSTで簡易認証パスワードが送信された場合
        if ( isset( $_POST['simple_auth_password'] ) ) {
            $submitted_password = sanitize_text_field( $_POST['simple_auth_password'] );

            if ( ! empty( $directory_password ) && $submitted_password === $directory_password ) {
                // セッションに認証情報を保存
                $_SESSION['bf_directory_simple_auth_verified'] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * ディレクトリの認証設定を取得します
     *
     * @param string $relative_path 相対パス
     * @return array|false 認証設定、または失敗時はfalse
     */
    private function get_directory_auth( $relative_path ) {
        $directory_auths = get_option( 'bf_sfd_directory_auths', array() );

        if ( ! isset( $directory_auths[ $relative_path ] ) ) {
            return false;
        }

        $auth_data = $directory_auths[ $relative_path ];
        if ( ! is_array( $auth_data ) ) {
            return false;
        }

        $result = array(
            'auth_methods' => $auth_data['auth_methods'] ?? array(),
            'allowed_roles' => $auth_data['allowed_roles'] ?? array(),
        );

        // 簡易認証パスワードを復号化
        if ( isset( $auth_data['simple_auth_encrypted'] ) ) {
            $result['simple_auth_password'] = $this->decrypt_password( $auth_data['simple_auth_encrypted'] );
        }

        return $result;
    }

    /**
     * 簡易認証をチェックします
     *
     * @return bool 簡易認証成功フラグ
     */
    private function check_simple_auth() {
        // セッションから簡易認証済みかチェック
        if ( isset( $_SESSION['bf_simple_auth_verified'] ) && $_SESSION['bf_simple_auth_verified'] === true ) {
            return true;
        }

        // POSTで簡易認証パスワードが送信された場合
        if ( isset( $_POST['simple_auth_password'] ) ) {
            $submitted_password = sanitize_text_field( $_POST['simple_auth_password'] );
            $stored_password = get_option( 'bf_sfd_simple_auth_password', '' );

            if ( ! empty( $stored_password ) && $submitted_password === $stored_password ) {
                // セッションに認証情報を保存
                $_SESSION['bf_simple_auth_verified'] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * パスワードを復号化します
     *
     * @param string $encrypted_password 暗号化されたパスワード
     * @return string|false 復号化されたパスワード、または失敗時はfalse
     */
    private function decrypt_password( $encrypted_password ) {
        if ( ! function_exists( 'openssl_decrypt' ) ) {
            return base64_decode( $encrypted_password ); // フォールバック
        }

        $data = base64_decode( $encrypted_password );
        if ( $data === false || strlen( $data ) < 16 ) {
            return false;
        }

        $key = $this->get_encryption_key();
        $iv = substr( $data, 0, 16 );
        $encrypted = substr( $data, 16 );

        return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
    }

    /**
     * 暗号化キーを取得します
     *
     * @return string 暗号化キー
     */
    private function get_encryption_key() {
        // WordPressのソルトを使用してキーを生成
        $salt_keys = array( AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, NONCE_KEY );
        return hash( 'sha256', implode( '', $salt_keys ) );
    }

    /**
     * ディレクトリにパスワードが設定されているかチェックします
     *
     * @param string $relative_path 相対パス
     * @return bool パスワード設定フラグ
     */
    private function has_directory_password( $relative_path ) {
        $directory_passwords = get_option( 'bf_sfd_directory_passwords', array() );

        if ( ! isset( $directory_passwords[ $relative_path ] ) ) {
            return false;
        }

        // 新しい配列形式をチェック
        if ( is_array( $directory_passwords[ $relative_path ] ) ) {
            return ! empty( $directory_passwords[ $relative_path ]['hash'] );
        }

        // 古い文字列形式（後方互換性）
        return ! empty( $directory_passwords[ $relative_path ] );
    }

    /**
     * ディレクトリへのアクセス権限を検証します
     *
     * @param string $relative_path 相対パス
     * @return bool アクセス許可フラグ
     */
    private function verify_directory_access( $relative_path ) {
        // セッションから認証済みかチェック
        if ( isset( $_SESSION['bf_sfd_auth'][ $relative_path ] ) ) {
            return true;
        }

        // POSTでパスワードが送信された場合
        if ( isset( $_POST['password'] ) && isset( $_POST['directory_path'] ) ) {
            $submitted_password = sanitize_text_field( $_POST['password'] );
            $submitted_path = sanitize_text_field( $_POST['directory_path'] );

            if ( $submitted_path === $relative_path && $this->verify_directory_password( $relative_path, $submitted_password ) ) {
                // セッションに認証情報を保存
                if ( ! isset( $_SESSION['bf_sfd_auth'] ) ) {
                    $_SESSION['bf_sfd_auth'] = array();
                }
                $_SESSION['bf_sfd_auth'][ $relative_path ] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * ディレクトリのパスワードを検証します
     *
     * @param string $relative_path 相対パス
     * @param string $password 入力されたパスワード
     * @return bool パスワード一致フラグ
     */
    private function verify_directory_password( $relative_path, $password ) {
        $directory_passwords = get_option( 'bf_sfd_directory_passwords', array() );

        if ( ! isset( $directory_passwords[ $relative_path ] ) ) {
            return false;
        }

        $password_data = $directory_passwords[ $relative_path ];

        // 新しい配列形式
        if ( is_array( $password_data ) && isset( $password_data['hash'] ) ) {
            return wp_check_password( $password, $password_data['hash'] );
        }

        // 古い文字列形式（後方互換性）
        if ( is_string( $password_data ) ) {
            return wp_check_password( $password, $password_data );
        }

        return false;
    }

    /**
     * 認証フォームを表示します
     */
    private function show_authentication_form() {
        $current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // 現在のファイルパスからディレクトリパスを取得
        $file_path = sanitize_text_field( $_GET['path'] ?? '' );
        $directory_path = dirname( $file_path );
        if ( $directory_path === '.' ) {
            $directory_path = '';
        }

        // ディレクトリ固有の認証設定を取得
        $directory_auth = $this->get_directory_auth( $directory_path );
        if ( $directory_auth !== false ) {
            $auth_methods = $directory_auth['auth_methods'] ?? array();
            $simple_auth_password = $directory_auth['simple_auth_password'] ?? '';
        } else {
            $auth_methods = get_option( 'bf_sfd_auth_methods', array( 'logged_in' ) );
            $simple_auth_password = '';
        }

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html( __( '認証が必要です', 'bf-secret-file-downloader' ) ); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
                .auth-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .auth-title { text-align: center; margin-bottom: 30px; color: #333; }
                .auth-description { text-align: center; margin-bottom: 20px; color: #666; }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; color: #555; }
                input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
                .submit-btn { width: 100%; padding: 12px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
                .submit-btn:hover { background-color: #005a87; }
                .error-message { color: #d63638; margin-top: 10px; text-align: center; }
                .login-link { text-align: center; margin-top: 15px; }
                .login-link a { color: #0073aa; text-decoration: none; }
                .login-link a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="auth-container">
                <h2 class="auth-title"><?php echo esc_html( __( '認証が必要です', 'bf-secret-file-downloader' ) ); ?></h2>
                <p class="auth-description"><?php echo esc_html( __( 'ファイルにアクセスするには認証が必要です。', 'bf-secret-file-downloader' ) ); ?></p>

                <?php if ( in_array( 'simple_auth', $auth_methods ) ): ?>
                <form method="post" action="<?php echo esc_url( $current_url ); ?>">
                    <div class="form-group">
                        <label for="simple_auth_password"><?php echo esc_html( __( '簡易認証パスワード', 'bf-secret-file-downloader' ) ); ?></label>
                        <input type="password" id="simple_auth_password" name="simple_auth_password" required>
                    </div>
                    <button type="submit" class="submit-btn"><?php echo esc_html( __( '認証', 'bf-secret-file-downloader' ) ); ?></button>
                </form>
                <?php endif; ?>

                <?php if ( in_array( 'logged_in', $auth_methods ) ): ?>
                <div class="login-link">
                    <a href="<?php echo esc_url( wp_login_url( $current_url ) ); ?>"><?php echo esc_html( __( 'ログインしてアクセス', 'bf-secret-file-downloader' ) ); ?></a>
                </div>
                <?php endif; ?>

                <?php if ( isset( $_POST['simple_auth_password'] ) ): ?>
                    <div class="error-message"><?php echo esc_html( __( 'パスワードが正しくありません。', 'bf-secret-file-downloader' ) ); ?></div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * パスワード認証フォームを表示します
     *
     * @param string $relative_path 相対パス
     */
    private function show_password_form( $relative_path ) {
        $current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html( __( '認証が必要です', 'bf-secret-file-downloader' ) ); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
                .auth-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .auth-title { text-align: center; margin-bottom: 30px; color: #333; }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; color: #555; }
                input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
                .submit-btn { width: 100%; padding: 12px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
                .submit-btn:hover { background-color: #005a87; }
                .error-message { color: #d63638; margin-top: 10px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="auth-container">
                <h2 class="auth-title"><?php echo esc_html( __( '認証が必要です', 'bf-secret-file-downloader' ) ); ?></h2>
                <form method="post" action="<?php echo esc_url( $current_url ); ?>">
                    <input type="hidden" name="directory_path" value="<?php echo esc_attr( $relative_path ); ?>">
                    <div class="form-group">
                        <label for="password"><?php echo esc_html( __( 'パスワードを入力してください', 'bf-secret-file-downloader' ) ); ?></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn"><?php echo esc_html( __( '認証', 'bf-secret-file-downloader' ) ); ?></button>
                </form>
                <?php if ( isset( $_POST['password'] ) ): ?>
                    <div class="error-message"><?php echo esc_html( __( 'パスワードが正しくありません。', 'bf-secret-file-downloader' ) ); ?></div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }


}
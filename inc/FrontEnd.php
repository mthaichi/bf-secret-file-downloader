<?php
/**
 * フロントエンド側のファイルダウンローダーを管理するクラス
 *
 * @package BfBasicGuard
 */

namespace Breadfish\BasicGuard;

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
        // pathパラメータが存在するかチェック
        $file_path = sanitize_text_field( $_GET['path'] ?? '' );
        if ( empty( $file_path ) ) {
            return;
        }

        // ダウンロードフラグを取得（デフォルトはダウンロード）
        $download_flag = sanitize_text_field( $_GET['dflag'] ?? 'download' );

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_die( __( '対象ディレクトリが設定されていません。', 'bf-basic-guard' ), 500 );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $file_path );

        // セキュリティチェック：許可されたディレクトリのみ
        if ( ! $this->is_allowed_directory( dirname( $full_path ) ) ) {
            wp_die( __( 'このファイルへのアクセスは許可されていません。', 'bf-basic-guard' ), 403 );
        }

        // ファイル存在チェック
        if ( ! file_exists( $full_path ) || ! is_file( $full_path ) ) {
            wp_die( __( '指定されたファイルが見つかりません。', 'bf-basic-guard' ), 404 );
        }

        // 読み込み権限チェック
        if ( ! is_readable( $full_path ) ) {
            wp_die( __( 'このファイルを読み取る権限がありません。', 'bf-basic-guard' ), 403 );
        }

        // 認証が必要な場合のチェック
        $relative_path = dirname( $file_path );
        if ( $this->has_directory_password( $relative_path ) ) {
            // パスワード認証が必要
            if ( ! $this->verify_directory_access( $relative_path ) ) {
                $this->show_password_form( $relative_path );
                exit;
            }
        }

        // ファイル情報を取得
        $filename = basename( $full_path );
        $filesize = filesize( $full_path );
        $mime_type = wp_check_filetype( $filename )['type'] ?? 'application/octet-stream';

        // ダウンロードログを記録（設定が有効な場合）
        if ( get_option( 'bf_basic_guard_log_downloads', false ) ) {
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
     * ベースディレクトリからの相対パスでフルパスを構築します
     *
     * @param string $base_directory ベースディレクトリ
     * @param string $relative_path 相対パス
     * @return string フルパス
     */
    private function build_full_path( $base_directory, $relative_path = '' ) {
        // 相対パスが空の場合はベースディレクトリをそのまま返す
        if ( empty( $relative_path ) || $relative_path === '.' ) {
            return rtrim( $base_directory, DIRECTORY_SEPARATOR );
        }

        // 危険な文字列をチェック
        if ( strpos( $relative_path, '..' ) !== false || strpos( $relative_path, '//' ) !== false ) {
            return $base_directory;
        }

        // パスを正規化
        $relative_path = trim( $relative_path, DIRECTORY_SEPARATOR );

        return $base_directory . DIRECTORY_SEPARATOR . $relative_path;
    }

    /**
     * ディレクトリへのアクセスが許可されているかチェックします
     *
     * @param string $path ディレクトリパス
     * @return bool アクセス許可フラグ
     */
    private function is_allowed_directory( $path ) {
        $real_path = realpath( $path );
        if ( $real_path === false ) {
            return false;
        }

        // 基本となる対象ディレクトリを取得
        $target_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $target_directory ) ) {
            return false;
        }

        $real_target_directory = realpath( $target_directory );
        if ( $real_target_directory === false ) {
            return false;
        }

        // 対象ディレクトリまたはそのサブディレクトリのみ許可
        return strpos( $real_path, $real_target_directory ) === 0;
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
        $download_logs = get_option( 'bf_basic_guard_download_logs', array() );
        $download_logs[] = $log_entry;

        // ログ数を制限（最新1000件）
        if ( count( $download_logs ) > 1000 ) {
            $download_logs = array_slice( $download_logs, -1000 );
        }

        update_option( 'bf_basic_guard_download_logs', $download_logs );
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
     * ディレクトリにパスワードが設定されているかチェックします
     *
     * @param string $relative_path 相対パス
     * @return bool パスワード設定フラグ
     */
    private function has_directory_password( $relative_path ) {
        $directory_passwords = get_option( 'bf_basic_guard_directory_passwords', array() );

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
        if ( isset( $_SESSION['bf_basic_guard_auth'][ $relative_path ] ) ) {
            return true;
        }

        // POSTでパスワードが送信された場合
        if ( isset( $_POST['password'] ) && isset( $_POST['directory_path'] ) ) {
            $submitted_password = sanitize_text_field( $_POST['password'] );
            $submitted_path = sanitize_text_field( $_POST['directory_path'] );

            if ( $submitted_path === $relative_path && $this->verify_directory_password( $relative_path, $submitted_password ) ) {
                // セッションに認証情報を保存
                if ( ! isset( $_SESSION['bf_basic_guard_auth'] ) ) {
                    $_SESSION['bf_basic_guard_auth'] = array();
                }
                $_SESSION['bf_basic_guard_auth'][ $relative_path ] = true;
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
        $directory_passwords = get_option( 'bf_basic_guard_directory_passwords', array() );

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
            <title><?php echo esc_html( __( '認証が必要です', 'bf-basic-guard' ) ); ?></title>
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
                <h2 class="auth-title"><?php echo esc_html( __( '認証が必要です', 'bf-basic-guard' ) ); ?></h2>
                <form method="post" action="<?php echo esc_url( $current_url ); ?>">
                    <input type="hidden" name="directory_path" value="<?php echo esc_attr( $relative_path ); ?>">
                    <div class="form-group">
                        <label for="password"><?php echo esc_html( __( 'パスワードを入力してください', 'bf-basic-guard' ) ); ?></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn"><?php echo esc_html( __( '認証', 'bf-basic-guard' ) ); ?></button>
                </form>
                <?php if ( isset( $_POST['password'] ) ): ?>
                    <div class="error-message"><?php echo esc_html( __( 'パスワードが正しくありません。', 'bf-basic-guard' ) ); ?></div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }


}
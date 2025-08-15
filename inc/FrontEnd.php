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
        // pathパラメータが存在するかチェック
        $file_path = sanitize_text_field( $_GET['path'] ?? '' );
        if ( empty( $file_path ) ) {
            return;
        }

        // ダウンロードフラグを取得（デフォルトはダウンロード）
        $download_flag = sanitize_text_field( $_GET['dflag'] ?? 'download' );

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_sfd_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_die( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ), 500 );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $file_path );

        // セキュリティチェック：許可されたディレクトリのみ
        if ( ! $this->is_allowed_directory( dirname( $full_path ) ) ) {
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
        // パスがnullまたは空の場合は拒否
        if ( $path === null || $path === '' ) {
            return false;
        }

        // シンボリックリンクの直接拒否
        if ( is_link( $path ) ) {
            error_log( 'BF Secret File Downloader: シンボリックリンクへのアクセス試行を検出: ' . $path );
            return false;
        }

        // パス内にシンボリックリンクが含まれていないかチェック
        $path_parts = explode( DIRECTORY_SEPARATOR, $path );
        $current_path = '';
        foreach ( $path_parts as $part ) {
            if ( empty( $part ) && empty( $current_path ) ) {
                $current_path = DIRECTORY_SEPARATOR; // ルートディレクトリ
                continue;
            } elseif ( empty( $part ) ) {
                continue; // 空の部分はスキップ
            }

            $current_path .= ( $current_path === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR ) . $part;

            if ( is_link( $current_path ) ) {
                error_log( 'BF Secret File Downloader: パス内にシンボリックリンクを検出: ' . $current_path . ' (フルパス: ' . $path . ')' );
                return false;
            }
        }

        $real_path = realpath( $path );
        if ( $real_path === false ) {
            return false;
        }

        // 基本となる対象ディレクトリを取得
        $target_directory = get_option( 'bf_sfd_target_directory', '' );
        if ( empty( $target_directory ) ) {
            return false;
        }

        // セキュリティチェック: 危険なターゲットディレクトリの即座拒否
        $dangerous_target_directories = [
            '/',                    // ルートディレクトリ - 極めて危険
            '/etc',                 // システム設定ディレクトリ
            '/usr',                 // システムユーティリティ
            '/usr/bin',             // システムバイナリ
            '/usr/sbin',            // システム管理バイナリ
            '/var/log',             // システムログ
            '/root',                // root ユーザーホーム
            '/tmp',                 // テンポラリディレクトリ
            '/proc',                // プロセスファイルシステム
            '/sys',                 // システムファイルシステム
            '/dev',                 // デバイスファイル
            '/bin',                 // 基本バイナリ
            '/sbin',                // システムバイナリ
            '/boot',                // ブートファイル

            // WordPress関連の危険なディレクトリ
            ABSPATH,                // WordPressルートディレクトリ - wp-config.php等が含まれる
            dirname( ABSPATH ),     // WordPressの親ディレクトリ
            ABSPATH . 'wp-admin',   // WordPress管理ディレクトリ
            ABSPATH . 'wp-includes', // WordPressコアファイル
        ];

        // wp-contentディレクトリ内の危険な場所も追加
        if ( defined( 'WP_CONTENT_DIR' ) ) {
            $dangerous_target_directories[] = WP_CONTENT_DIR . '/plugins';     // プラグインディレクトリ
            $dangerous_target_directories[] = WP_CONTENT_DIR . '/themes';      // テーマディレクトリ
            $dangerous_target_directories[] = WP_CONTENT_DIR . '/mu-plugins'; // Must-useプラグイン
        }

        // シンボリックリンク攻撃の検出
        if ( is_link( $target_directory ) ) {
            error_log( 'BF Secret File Downloader: シンボリックリンク攻撃を検出: ' . $target_directory );
            return false;
        }

        // ターゲットディレクトリが危険な場合は即座に拒否（シンボリックリンク解決前と後の両方）
        $check_paths = [ $target_directory ]; // 元のパス
        $real_target_check = realpath( $target_directory );
        if ( $real_target_check !== false && $real_target_check !== $target_directory ) {
            $check_paths[] = $real_target_check; // 解決されたパス
        }

        foreach ( $check_paths as $check_path ) {
            foreach ( $dangerous_target_directories as $dangerous_dir ) {
                $real_dangerous = realpath( $dangerous_dir );

                // 元のパスと解決されたパスの両方をチェック
                $paths_to_check = [ $dangerous_dir ];
                if ( $real_dangerous !== false && $real_dangerous !== $dangerous_dir ) {
                    $paths_to_check[] = $real_dangerous;
                }

                foreach ( $paths_to_check as $dangerous_path ) {
                    if ( $check_path === $dangerous_path ||
                         strpos( $check_path, $dangerous_path . DIRECTORY_SEPARATOR ) === 0 ) {

                        // ログに記録（本番環境では削除推奨）
                        error_log( 'BF Secret File Downloader: 危険なターゲットディレクトリが設定されています: ' . $target_directory . ' (解決先: ' . $check_path . ' -> ' . $dangerous_path . ')' );

                        return false;
                    }
                }
            }
        }

        $real_target_directory = realpath( $target_directory );
        if ( $real_target_directory === false ) {
            return false;
        }

        // 新しいセキュリティチェック: パス内にWordPressの機密ファイルやディレクトリが含まれていないかチェック
        if ( ! $this->is_path_safe_from_wordpress_secrets( $real_path ) ) {
            error_log( 'BF Secret File Downloader: パス内にWordPressの機密ファイルやディレクトリが検出されました: ' . $real_path );
            return false;
        }

        // 対象ディレクトリまたはそのサブディレクトリのみ許可
        return strpos( $real_path, $real_target_directory ) === 0;
    }

    /**
     * パス内にWordPressの機密ファイルやディレクトリが含まれていないかチェックします
     *
     * @param string $path チェックするパス
     * @return bool 安全な場合はtrue、危険な場合はfalse
     */
    private function is_path_safe_from_wordpress_secrets( $path ) {
        // WordPressの機密ファイルやディレクトリのパターン
        $wordpress_secret_patterns = [
            // WordPressルートディレクトリの機密ファイル
            'wp-config.php',
            'wp-config-sample.php',
            '.htaccess',
            'readme.html',
            'license.txt',
            'wp-config.php.bak',
            'wp-config.php.old',
            'wp-config.php.backup',

            // WordPress管理ディレクトリ
            'wp-admin',
            'wp-admin/',
            '/wp-admin',
            '/wp-admin/',

            // WordPressコアファイルディレクトリ
            'wp-includes',
            'wp-includes/',
            '/wp-includes',
            '/wp-includes/',

            // WordPressコンテンツディレクトリ内の機密場所
            'wp-content/plugins',
            'wp-content/themes',
            'wp-content/mu-plugins',
            'wp-content/uploads/plugins',
            'wp-content/uploads/themes',

            // データベース関連ファイル
            '.sql',
            '.sql.gz',
            '.sql.bak',
            'database.sql',
            'backup.sql',

            // ログファイル
            'error_log',
            'debug.log',
            'access.log',
            '.log',

            // 設定ファイル
            'config.php',
            'configuration.php',
            'settings.php',
            '.env',
            '.env.local',
            '.env.production',

            // バックアップファイル
            '.bak',
            '.backup',
            '.old',
            '.orig',

            // 一時ファイル
            '.tmp',
            '.temp',
            'temp/',
            'tmp/',

            // Git関連
            '.git',
            '.gitignore',
            '.gitattributes',

            // その他の機密ファイル
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'yarn.lock',
            'npm-debug.log',

            // セッションファイル
            'sessions/',
            'session/',
            'cache/',
            'caches/',
        ];

        // パスを小文字に変換してチェック（大文字小文字を区別しない）
        $path_lower = strtolower( $path );

        // パスを正規化（バックスラッシュをスラッシュに統一）
        $path_normalized = str_replace( '\\', '/', $path_lower );

        // 各パターンをチェック
        foreach ( $wordpress_secret_patterns as $pattern ) {
            $pattern_lower = strtolower( $pattern );
            $pattern_normalized = str_replace( '\\', '/', $pattern_lower );

            // パターンがパスに含まれているかチェック
            if ( strpos( $path_normalized, $pattern_normalized ) !== false ) {
                // より厳密なチェック：ディレクトリ区切り文字で囲まれているかチェック
                $pattern_regex = '/[\/\\\\]' . preg_quote( $pattern_normalized, '/' ) . '[\/\\\\]?/';
                if ( preg_match( $pattern_regex, $path_normalized ) ) {
                    return false;
                }

                // ファイル名の完全一致チェック
                $basename = basename( $path_normalized );
                if ( $basename === $pattern_normalized ) {
                    return false;
                }
            }
        }

        // WordPressの定数が定義されている場合の追加チェック
        if ( defined( 'ABSPATH' ) ) {
            $abspath_lower = strtolower( str_replace( '\\', '/', ABSPATH ) );

            // WordPressルートディレクトリ内の機密ファイルへのアクセスをチェック
            $wp_secret_files = [
                'wp-config.php',
                'wp-config-sample.php',
                '.htaccess',
                'readme.html',
                'license.txt',
            ];

            foreach ( $wp_secret_files as $secret_file ) {
                $secret_path = $abspath_lower . strtolower( $secret_file );
                if ( $path_normalized === $secret_path ) {
                    return false;
                }
            }

            // wp-adminディレクトリへのアクセスをチェック
            $wp_admin_path = $abspath_lower . 'wp-admin';
            if ( strpos( $path_normalized, $wp_admin_path ) === 0 ) {
                return false;
            }

            // wp-includesディレクトリへのアクセスをチェック
            $wp_includes_path = $abspath_lower . 'wp-includes';
            if ( strpos( $path_normalized, $wp_includes_path ) === 0 ) {
                return false;
            }
        }

        // wp-contentディレクトリの機密場所へのアクセスをチェック
        if ( defined( 'WP_CONTENT_DIR' ) ) {
            $wp_content_lower = strtolower( str_replace( '\\', '/', WP_CONTENT_DIR ) );

            $wp_content_secrets = [
                '/plugins',
                '/themes',
                '/mu-plugins',
                '/uploads/plugins',
                '/uploads/themes',
            ];

            foreach ( $wp_content_secrets as $secret_dir ) {
                $secret_path = $wp_content_lower . strtolower( $secret_dir );
                if ( strpos( $path_normalized, $secret_path ) === 0 ) {
                    return false;
                }
            }
        }

        return true;
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
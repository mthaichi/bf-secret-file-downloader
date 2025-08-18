<?php
/**
 * ディレクトリセキュリティチェック機能を提供するクラス
 *
 * @package BfSecretFileDownloader
 */

namespace Breadfish\SecretFileDownloader;

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DirectorySecurity クラス
 * ディレクトリのセキュリティチェック機能を統一管理します
 */
class DirectorySecurity {


    /**
     * WordPressファイルの危険性をチェックします
     *
     * @param string $directory_path チェックするディレクトリパス
     * @return bool 危険な場合はtrue
     */
    public static function check_wordpress_danger( $directory_path ) {
        if ( empty( $directory_path ) || ! is_dir( $directory_path ) ) {
            return false;
        }

        // WordPress確実判定: 複数の特徴的ファイルが同時に存在するかチェック
        $wordpress_core_files = array(
            'wp-config.php',
            'wp-config-sample.php'
        );

        $wordpress_core_dirs = array(
            'wp-admin',
            'wp-includes'
        );

        // ディレクトリ内をスキャン
        $items = scandir( $directory_path );
        $found_core_files = 0;
        $found_core_dirs = 0;

        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }

            $item_path = $directory_path . DIRECTORY_SEPARATOR . $item;

            // WordPress設定ファイルのチェック
            if ( in_array( $item, $wordpress_core_files ) ) {
                $found_core_files++;
            }

            // WordPressコアディレクトリのチェック  
            if ( in_array( $item, $wordpress_core_dirs ) && is_dir( $item_path ) ) {
                $found_core_dirs++;
            }
        }

        // WordPress判定: コア設定ファイルまたは2つ以上のコアディレクトリが存在
        return ( $found_core_files > 0 || $found_core_dirs >= 2 );
    }



    /**
     * WordPressルートディレクトリが直接指定されているかチェックします
     *
     * @param string $directory_path チェックするディレクトリパス
     * @return bool WordPressルートディレクトリの場合はtrue
     */
    public static function is_wordpress_root_directory( $directory_path ) {
        if ( empty( $directory_path ) ) {
            return false;
        }

        $abspath_real = realpath( ABSPATH );
        $target_real = realpath( $directory_path );
        
        return ( $directory_path === ABSPATH || $target_real === $abspath_real );
    }

    /**
     * 危険なシステムディレクトリのリストを取得します
     *
     * @return array 危険なディレクトリのリスト
     */
    public static function get_dangerous_directories() {
        $dangerous_directories = array(
            '/',                    // ルートディレクトリ - 極めて危険
            '/etc',                 // システム設定ディレクトリ
            '/usr',                 // システムユーティリティ
            '/usr/bin',             // システムバイナリ
            '/usr/sbin',            // システム管理バイナリ
            '/var',                 // 可変データディレクトリ（一部例外あり）
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
            ABSPATH . 'wp-admin',   // WordPress管理ディレクトリ
            ABSPATH . 'wp-includes', // WordPressコアファイル
        );

        // wp-contentディレクトリ内の危険な場所も追加
        if ( defined( 'WP_CONTENT_DIR' ) ) {
            $dangerous_directories[] = WP_CONTENT_DIR . '/plugins';     // プラグインディレクトリ
            $dangerous_directories[] = WP_CONTENT_DIR . '/themes';      // テーマディレクトリ
            $dangerous_directories[] = WP_CONTENT_DIR . '/mu-plugins'; // Must-useプラグイン
        }

        return $dangerous_directories;
    }


    /**
     * 対象ディレクトリの安全性を包括的にチェックします
     *
     * @param string $directory_path チェックするディレクトリパス
     * @return array チェック結果 ['is_safe' => bool, 'danger_reason' => string]
     */
    public static function check_directory_safety( $directory_path ) {
        $result = array(
            'is_safe' => true,
            'danger_reason' => '',
            'is_wordpress_root' => false,
            'has_wordpress_files' => false,
            'is_dangerous_system_dir' => false
        );

        if ( empty( $directory_path ) ) {
            return $result;
        }

        // WordPressルートディレクトリの直接指定チェック
        if ( self::is_wordpress_root_directory( $directory_path ) ) {
            $result['is_safe'] = false;
            $result['is_wordpress_root'] = true;
            $result['danger_reason'] = __( 'WordPressルートディレクトリの直接指定は禁止されています。', 'bf-secret-file-downloader' );
            return $result;
        }

        // 危険なシステムディレクトリチェック
        $dangerous_directories = self::get_dangerous_directories();
        $check_paths = array( $directory_path );

        // シンボリックリンクがある場合は解決されたパスもチェック
        $real_value = realpath( $directory_path );
        if ( $real_value !== false && $real_value !== $directory_path ) {
            $check_paths[] = $real_value;
        }

        $is_dangerous = false;
        foreach ( $check_paths as $check_path ) {
            foreach ( $dangerous_directories as $dangerous_dir ) {
                $real_dangerous = realpath( $dangerous_dir );

                // 元のパスと解決されたパスの両方をチェック
                $paths_to_check = array( $dangerous_dir );
                if ( $real_dangerous !== false && $real_dangerous !== $dangerous_dir ) {
                    $paths_to_check[] = $real_dangerous;
                }

                foreach ( $paths_to_check as $dangerous_path ) {
                    if ( $check_path === $dangerous_path ||
                         strpos( $check_path, rtrim( $dangerous_path, '/' ) . '/' ) === 0 ) {
                        $is_dangerous = true;
                        break 3; // 3重ループを抜ける
                    }
                }
            }
        }

        if ( $is_dangerous ) {
            $result['is_safe'] = false;
            $result['is_dangerous_system_dir'] = true;
            $result['danger_reason'] = __( '危険なシステムディレクトリまたはWordPress重要ディレクトリです。', 'bf-secret-file-downloader' );
            return $result;
        }

        // WordPress関連ファイルの存在チェック
        if ( self::check_wordpress_danger( $directory_path ) ) {
            $result['is_safe'] = false;
            $result['has_wordpress_files'] = true;
            $result['danger_reason'] = __( 'ディレクトリ内にWordPress関連ファイルが検出されました。', 'bf-secret-file-downloader' );
            return $result;
        }

        return $result;
    }

    /**
     * 対象ディレクトリの安全性をチェックし、危険フラグを更新します
     *
     * @param string $directory_path チェックするディレクトリパス
     * @return array チェック結果
     */
    public static function check_and_update_directory_safety( $directory_path ) {
        if ( empty( $directory_path ) ) {
            return array( 'is_safe' => true, 'danger_reason' => '' );
        }

        $safety_check = self::check_directory_safety( $directory_path );
        
        return $safety_check;
    }


    /**
     * パストラバーサル対策を含む安全なパス構築を行います
     *
     * @param string $base_directory ベースディレクトリ
     * @param string $relative_path 相対パス
     * @return string 安全なフルパス
     */
    public static function build_safe_path( $base_directory, $relative_path ) {
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
     * シンボリックリンクのチェックを行います
     *
     * @param string $path チェックするパス
     * @return bool シンボリックリンクの場合はtrue
     */
    public static function is_symbolic_link( $path ) {
        return is_link( $path );
    }

    /**
     * ディレクトリが許可されているかチェックします
     *
     * @param string $path チェックするディレクトリパス
     * @return bool 許可されている場合はtrue
     */
    public static function is_allowed_directory( $path ) {
        $real_path = realpath( $path );
        if ( $real_path === false ) {
            return false;
        }

        // シンボリックリンクの場合は拒否
        if ( self::is_symbolic_link( $path ) ) {
            error_log( 'BF Secret File Downloader: シンボリックリンクへのアクセス試行を検出: ' . $path );
            return false;
        }

        // 固定セキュアディレクトリ環境では機密ファイルチェックは不要
        // セキュアディレクトリ内にはWordPress機密ファイルが配置されることはない

        // 基本となる対象ディレクトリを取得
        $target_directory = bf_secret_file_downloader_get_secure_directory();
        if ( empty( $target_directory ) ) {
            return false;
        }

        $real_target_directory = realpath( $target_directory );
        if ( $real_target_directory === false ) {
            return false;
        }

        // 対象ディレクトリまたはそのサブディレクトリかチェック
        return ( $real_path === $real_target_directory || strpos( $real_path, $real_target_directory . DIRECTORY_SEPARATOR ) === 0 );
    }

    /**
     * ファイルアップロード時のセキュリティチェックを実行します
     *
     * @param string $filename ファイル名
     * @param string $upload_path アップロード先パス
     * @return array チェック結果 ['allowed' => bool, 'error_message' => string]
     */
    public static function check_file_upload_security( $filename, $upload_path ) {
        $result = array(
            'allowed' => false,
            'error_message' => ''
        );

        // ファイル名の基本チェック
        if ( empty( $filename ) ) {
            $result['error_message'] = __( 'ファイル名が指定されていません。', 'bf-secret-file-downloader' );
            return $result;
        }

        // 危険なファイル拡張子のチェック
        $dangerous_extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi' );
        $file_extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        
        if ( in_array( $file_extension, $dangerous_extensions ) ) {
            $result['error_message'] = __( 'このファイル形式はセキュリティ上の理由でアップロードできません。', 'bf-secret-file-downloader' );
            return $result;
        }

        // アップロード先ディレクトリのチェック
        if ( ! self::is_allowed_directory( $upload_path ) ) {
            $result['error_message'] = __( 'このディレクトリへのアップロードは許可されていません。', 'bf-secret-file-downloader' );
            return $result;
        }

        $result['allowed'] = true;
        return $result;
    }

    /**
     * AJAXディレクトリ作成用のセキュリティチェックを実行します
     *
     * @param string $parent_path 親ディレクトリパス
     * @param string $directory_name 作成するディレクトリ名
     * @return array チェック結果 ['allowed' => bool, 'error_message' => string]
     */
    public static function check_ajax_create_directory_security( $parent_path, $directory_name ) {
        $result = array(
            'allowed' => false,
            'error_message' => ''
        );

        // 入力値チェック
        if ( empty( $parent_path ) || empty( $directory_name ) ) {
            $result['error_message'] = __( 'パスまたはディレクトリ名が指定されていません。', 'bf-secret-file-downloader' );
            return $result;
        }

        // ディレクトリ名のバリデーション
        if ( ! preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $directory_name ) ) {
            $result['error_message'] = __( 'ディレクトリ名に使用できない文字が含まれています。', 'bf-secret-file-downloader' );
            return $result;
        }

        // 親ディレクトリの存在チェック
        $real_parent_path = realpath( $parent_path );
        if ( $real_parent_path === false || ! is_dir( $real_parent_path ) ) {
            $result['error_message'] = __( '親ディレクトリが存在しません。', 'bf-secret-file-downloader' );
            return $result;
        }

        // セキュリティ：WordPress内のディレクトリのみ許可
        $allowed_base_paths = array(
            ABSPATH,
            WP_CONTENT_DIR,
            ABSPATH . 'wp-content',
            dirname( ABSPATH ),
        );

        // WordPressシステムディレクトリでの作成を禁止
        $wp_system_paths = array(
            ABSPATH . 'wp-content',
            ABSPATH . 'wp-includes',
            ABSPATH . 'wp-admin',
            WP_CONTENT_DIR . '/themes',
            WP_CONTENT_DIR . '/plugins',
            WP_CONTENT_DIR . '/mu-plugins',
        );

        // WordPressシステムディレクトリチェック（サブディレクトリも含む）
        foreach ( $wp_system_paths as $wp_system_path ) {
            $real_wp_system_path = realpath( $wp_system_path );
            if ( $real_wp_system_path !== false && ( $real_parent_path === $real_wp_system_path || strpos( $real_parent_path, $real_wp_system_path . DIRECTORY_SEPARATOR ) === 0 ) ) {
                $result['error_message'] = __( 'WordPressシステムディレクトリまたはそのサブディレクトリ内にはディレクトリを作成できません。', 'bf-secret-file-downloader' );
                return $result;
            }
        }

        $is_allowed = false;
        foreach ( $allowed_base_paths as $base_path ) {
            $real_base_path = realpath( $base_path );
            if ( $real_base_path !== false && strpos( $real_parent_path, $real_base_path ) === 0 ) {
                $is_allowed = true;
                break;
            }
        }

        if ( ! $is_allowed ) {
            $result['error_message'] = __( 'このディレクトリには作成権限がありません。', 'bf-secret-file-downloader' );
            return $result;
        }

        // 新しいディレクトリのパス
        $new_directory_path = $parent_path . DIRECTORY_SEPARATOR . $directory_name;

        // 既存チェック
        if ( file_exists( $new_directory_path ) ) {
            $result['error_message'] = __( '同名のディレクトリまたはファイルが既に存在します。', 'bf-secret-file-downloader' );
            return $result;
        }

        $result['allowed'] = true;
        return $result;
    }
}
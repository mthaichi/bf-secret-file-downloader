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
     * 危険フラグのオプション名
     */
    const DANGER_FLAG_OPTION = 'bf_sfd_directory_danger_flag';

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
     * 危険フラグを設定します
     *
     * @param bool $is_dangerous 危険かどうか
     */
    public static function set_danger_flag( $is_dangerous ) {
        update_option( self::DANGER_FLAG_OPTION, $is_dangerous );
    }

    /**
     * 危険フラグを取得します
     *
     * @return bool 危険フラグの状態
     */
    public static function get_danger_flag() {
        return (bool) get_option( self::DANGER_FLAG_OPTION, false );
    }

    /**
     * 危険フラグをクリアします
     */
    public static function clear_danger_flag() {
        delete_option( self::DANGER_FLAG_OPTION );
    }

    /**
     * 危険フラグが設定されているかチェックします（エイリアス）
     *
     * @return bool 危険フラグが設定されている場合はtrue
     */
    public static function is_danger_flag_set() {
        return self::get_danger_flag();
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
     * ディレクトリが危険なディレクトリに該当するかチェックします
     *
     * @param string $directory_path チェックするディレクトリパス
     * @return bool 危険なディレクトリの場合はtrue
     */
    public static function is_dangerous_directory( $directory_path ) {
        if ( empty( $directory_path ) ) {
            return false;
        }

        $dangerous_directories = self::get_dangerous_directories();
        $check_paths = array( $directory_path );

        // シンボリックリンクがある場合は解決されたパスもチェック
        $real_value = realpath( $directory_path );
        if ( $real_value !== false && $real_value !== $directory_path ) {
            $check_paths[] = $real_value;
        }

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
                         strpos( $check_path, $dangerous_path . DIRECTORY_SEPARATOR ) === 0 ) {
                        return true;
                    }
                }
            }
        }

        return false;
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
        if ( self::is_dangerous_directory( $directory_path ) ) {
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
            // ディレクトリが設定されていない場合は危険フラグをクリア
            self::clear_danger_flag();
            return array( 'is_safe' => true, 'danger_reason' => '' );
        }

        $safety_check = self::check_directory_safety( $directory_path );
        
        // 危険フラグを更新
        self::set_danger_flag( ! $safety_check['is_safe'] );

        return $safety_check;
    }

    /**
     * AJAXディレクトリブラウジング用のセキュリティチェックを実行します
     *
     * @param string $path チェックするパス
     * @return array チェック結果 ['allowed' => bool, 'error_message' => string]
     */
    public static function check_ajax_browse_directory_security( $path ) {
        $result = array(
            'allowed' => false,
            'error_message' => ''
        );

        // ルートパスが指定されていない場合は、WordPressのindex.phpが配置されているディレクトリから開始
        if ( empty( $path ) ) {
            $path = ABSPATH;
        }

        // 許可されたベースパスの定義
        $allowed_base_paths = array(
            ABSPATH,              // WordPressルートディレクトリ
            WP_CONTENT_DIR,       // wp-contentディレクトリ
            ABSPATH . 'wp-content',
            dirname( ABSPATH ),   // WordPressの親ディレクトリ（公開ディレクトリ外アクセス用）
        );

        $real_path = realpath( $path );

        // 危険なシステムディレクトリを明示的に禁止
        $forbidden_paths = array(
            '/etc',
            '/var/log',
            '/var/cache',
            '/usr/bin',
            '/usr/sbin',
            '/bin',
            '/sbin',
            '/root',
            '/tmp',
            '/proc',
            '/sys',
            '/dev',
        );

        // 禁止パスチェック（real_pathが取得できた場合のみ）
        if ( $real_path !== false ) {
            // システムディレクトリチェック
            foreach ( $forbidden_paths as $forbidden_path ) {
                if ( strpos( $real_path, $forbidden_path ) === 0 ) {
                    $result['error_message'] = 'Access to system directories is forbidden: ' . $path;
                    return $result;
                }
            }
        }

        // realpathがfalseを返す場合（ディレクトリが存在しない）
        if ( $real_path === false || ! is_dir( $real_path ) ) {
            $result['error_message'] = 'Directory does not exist: ' . $path;
            return $result;
        }

        // 読み取り権限チェック
        if ( ! is_readable( $real_path ) ) {
            $result['error_message'] = 'Permission denied: Cannot read directory';
            return $result;
        }

        // 許可されたベースパス内にあるかチェック
        $is_allowed = false;
        foreach ( $allowed_base_paths as $base_path ) {
            $real_base_path = realpath( $base_path );
            if ( $real_base_path !== false && strpos( $real_path, $real_base_path ) === 0 ) {
                $is_allowed = true;
                break;
            }
        }

        if ( ! $is_allowed ) {
            $result['error_message'] = 'Access denied to directory: ' . $path;
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
            $result['error_message'] = 'このディレクトリには作成権限がありません。';
            return $result;
        }

        // 新しいディレクトリのパス
        $new_directory_path = $parent_path . DIRECTORY_SEPARATOR . $directory_name;

        // 既存チェック
        if ( file_exists( $new_directory_path ) ) {
            $result['error_message'] = '同名のディレクトリまたはファイルが既に存在します。';
            return $result;
        }

        $result['allowed'] = true;
        return $result;
    }
}
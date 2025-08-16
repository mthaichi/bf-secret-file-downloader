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

        // WordPressの危険ファイル・ディレクトリのリスト
        $wordpress_danger_items = array(
            'wp-config.php',
            'wp-config-sample.php',
            'wp-admin',
            'wp-includes',
            '.htaccess',
            'readme.html',
            'license.txt'
        );

        // ディレクトリ内をスキャン
        $items = scandir( $directory_path );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }

            // 危険アイテムが存在するかチェック
            if ( in_array( $item, $wordpress_danger_items ) ) {
                return true;
            }
        }

        return false;
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
}
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
        add_action( 'wp_ajax_bf_sfd_browse_directory', array( $this, 'ajax_browse_directory' ) );
        add_action( 'wp_ajax_bf_sfd_create_directory', array( $this, 'ajax_create_directory' ) );
        add_action( 'wp_ajax_bf_sfd_reset_settings', array( $this, 'ajax_reset_settings' ) );
    }

    /**
     * 設定を登録します
     */
    public function register_settings() {
        register_setting( 'bf_sfd_settings', 'bf_sfd_target_directory', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => array( $this, 'sanitize_directory' )
        ) );

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
     * ディレクトリブラウズのAJAXハンドラ
     */
    public function ajax_browse_directory() {
        // セキュリティチェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_sfd_browse_nonce', 'nonce' );

        $path = sanitize_text_field( $_POST['path'] ?? '' );

        // ルートパスが指定されていない場合は、WordPressのindex.phpが配置されているディレクトリから開始
        if ( empty( $path ) ) {
            $path = ABSPATH;
        }

                // セキュリティ：安全なディレクトリのみ許可
        $allowed_base_paths = array(
            ABSPATH,              // WordPressルートディレクトリ
            WP_CONTENT_DIR,       // wp-contentディレクトリ
            ABSPATH . 'wp-content',
            dirname( ABSPATH ),   // WordPressの親ディレクトリ（公開ディレクトリ外アクセス用）
        );

        $is_allowed = false;
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
                    wp_send_json_error( 'Access to system directories is forbidden: ' . $path );
                }
            }
        }

        // パーミッションチェック（現在のディレクトリを保持するためのフォールバック処理）
        $fallback_to_current = isset( $_POST['fallback_current'] ) ? sanitize_text_field( $_POST['fallback_current'] ) : '';

        // realpathがfalseを返す場合（ディレクトリが存在しない）
        if ( $real_path === false || ! is_dir( $real_path ) ) {
            if ( ! empty( $fallback_to_current ) && is_dir( $fallback_to_current ) && is_readable( $fallback_to_current ) ) {
                // フォールバック: 現在のディレクトリに戻る
                $path = $fallback_to_current;
                $real_path = realpath( $path );

                // 現在のディレクトリの内容を取得
                $directories = array();
                $files = array();

                try {
                    $items = scandir( $path );
                    foreach ( $items as $item ) {
                        if ( $item === '.' || $item === '..' ) {
                            continue;
                        }

                        $full_path = $path . DIRECTORY_SEPARATOR . $item;

                        if ( is_dir( $full_path ) ) {
                            $directories[] = array(
                                'name' => $item,
                                'path' => $full_path,
                                'type' => 'directory'
                            );
                        } else {
                            $files[] = array(
                                'name' => $item,
                                'path' => $full_path,
                                'type' => 'file'
                            );
                        }
                    }

                    // ディレクトリを先頭に、ファイルを後に並べる
                    usort( $directories, function( $a, $b ) {
                        return strcmp( $a['name'], $b['name'] );
                    });
                    usort( $files, function( $a, $b ) {
                        return strcmp( $a['name'], $b['name'] );
                    });

                    wp_send_json_success( array(
                        'current_path' => $path,
                        'parent_path' => dirname( $path ),
                        'items' => array_merge( $directories, $files ),
                        'warning' => 'ディレクトリが存在しないため、現在のディレクトリを維持しました。'
                    ));
                    return;
                } catch ( \Exception $e ) {
                    wp_send_json_error( 'Directory does not exist: ' . $path );
                }
            } else {
                wp_send_json_error( 'Directory does not exist: ' . $path );
            }
        }

        // 読み取り権限チェック
        if ( ! is_readable( $real_path ) ) {
            if ( ! empty( $fallback_to_current ) && is_dir( $fallback_to_current ) && is_readable( $fallback_to_current ) ) {
                // フォールバック: 現在のディレクトリの情報を再取得
                $path = $fallback_to_current;
                $real_path = realpath( $path );

                // 現在のディレクトリの内容を取得して表示を維持
                $directories = array();
                $files = array();

                try {
                    $items = scandir( $path );
                    foreach ( $items as $item ) {
                        if ( $item === '.' || $item === '..' ) {
                            continue;
                        }

                        $full_path = $path . DIRECTORY_SEPARATOR . $item;

                        if ( is_dir( $full_path ) ) {
                            $directories[] = array(
                                'name' => $item,
                                'path' => $full_path,
                                'type' => 'directory'
                            );
                        } else {
                            $files[] = array(
                                'name' => $item,
                                'path' => $full_path,
                                'type' => 'file'
                            );
                        }
                    }

                    // ディレクトリを先頭に、ファイルを後に並べる
                    usort( $directories, function( $a, $b ) {
                        return strcmp( $a['name'], $b['name'] );
                    });
                    usort( $files, function( $a, $b ) {
                        return strcmp( $a['name'], $b['name'] );
                    });

                    wp_send_json_success( array(
                        'current_path' => $path,
                        'parent_path' => dirname( $path ),
                        'items' => array_merge( $directories, $files ),
                        'warning' => 'アクセス権限がないため、現在のディレクトリを維持しました。'
                    ));
                    return;

                } catch ( \Exception $e ) {
                    wp_send_json_error( 'Permission denied: Cannot read directory' );
                }
            } else {
                wp_send_json_error( 'Permission denied: Cannot read directory' );
            }
        }

        foreach ( $allowed_base_paths as $base_path ) {
            $real_base_path = realpath( $base_path );
            if ( $real_base_path !== false && strpos( $real_path, $real_base_path ) === 0 ) {
                $is_allowed = true;
                break;
            }
        }

        if ( ! $is_allowed ) {
            if ( ! empty( $fallback_to_current ) && is_dir( $fallback_to_current ) && is_readable( $fallback_to_current ) ) {
                // フォールバック: 現在のディレクトリに戻る
                $path = $fallback_to_current;
                $real_path = realpath( $path );
                // セキュリティチェックを再実行
                $is_allowed = false;
                foreach ( $allowed_base_paths as $base_path ) {
                    $real_base_path = realpath( $base_path );
                    if ( $real_base_path !== false && strpos( $real_path, $real_base_path ) === 0 ) {
                        $is_allowed = true;
                        break;
                    }
                }
                if ( ! $is_allowed ) {
                    wp_send_json_error( 'Access denied to directory: ' . $path );
                }

                // セキュリティチェックを通過した場合、現在のディレクトリの内容を取得して返す
                $directories = array();
                $files = array();

                try {
                    $items = scandir( $path );
                    foreach ( $items as $item ) {
                        if ( $item === '.' || $item === '..' ) {
                            continue;
                        }

                        $full_path = $path . DIRECTORY_SEPARATOR . $item;

                        if ( is_dir( $full_path ) ) {
                            $directories[] = array(
                                'name' => $item,
                                'path' => $full_path,
                                'type' => 'directory'
                            );
                        } else {
                            $files[] = array(
                                'name' => $item,
                                'path' => $full_path,
                                'type' => 'file'
                            );
                        }
                    }

                    // ディレクトリを先頭に、ファイルを後に並べる
                    usort( $directories, function( $a, $b ) {
                        return strcmp( $a['name'], $b['name'] );
                    });
                    usort( $files, function( $a, $b ) {
                        return strcmp( $a['name'], $b['name'] );
                    });

                    wp_send_json_success( array(
                        'current_path' => $path,
                        'parent_path' => dirname( $path ),
                        'items' => array_merge( $directories, $files ),
                        'warning' => 'アクセス権限がないため、現在のディレクトリを維持しました。'
                    ));
                    return;

                } catch ( \Exception $e ) {
                    wp_send_json_error( 'Access denied to directory: ' . $path );
                }
            } else {
                wp_send_json_error( 'Access denied to directory: ' . $path );
            }
        }

        $directories = array();
        $files = array();

        try {
            $items = scandir( $path );
            foreach ( $items as $item ) {
                if ( $item === '.' || $item === '..' ) {
                    continue;
                }

                $full_path = $path . DIRECTORY_SEPARATOR . $item;

                if ( is_dir( $full_path ) ) {
                    $directories[] = array(
                        'name' => $item,
                        'path' => $full_path,
                        'type' => 'directory'
                    );
                } else {
                    $files[] = array(
                        'name' => $item,
                        'path' => $full_path,
                        'type' => 'file'
                    );
                }
            }

            // ディレクトリを先頭に、ファイルを後に並べる
            usort( $directories, function( $a, $b ) {
                return strcmp( $a['name'], $b['name'] );
            });
            usort( $files, function( $a, $b ) {
                return strcmp( $a['name'], $b['name'] );
            });

            $response_data = array(
                'current_path' => $path,
                'parent_path' => dirname( $path ),
                'items' => array_merge( $directories, $files )
            );

            // フォールバック処理が行われた場合の警告メッセージ
            if ( ! empty( $fallback_to_current ) && $path === $fallback_to_current ) {
                $response_data['warning'] = 'アクセス権限がないため、現在のディレクトリを維持しました。';
            }

            wp_send_json_success( $response_data );

        } catch ( \Exception $e ) {
            wp_send_json_error( 'Failed to read directory' );
        }
    }

    /**
     * ディレクトリ作成のAJAXハンドラ
     */
    public function ajax_create_directory() {
        // セキュリティチェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_sfd_browse_nonce', 'nonce' );

        $parent_path = sanitize_text_field( $_POST['parent_path'] ?? '' );
        $directory_name = sanitize_text_field( $_POST['directory_name'] ?? '' );

        // 入力値チェック
        if ( empty( $parent_path ) || empty( $directory_name ) ) {
            wp_send_json_error( __( 'パスまたはディレクトリ名が指定されていません。', 'bf-secret-file-downloader' ) );
        }

        // ディレクトリ名のバリデーション
        if ( ! preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $directory_name ) ) {
            wp_send_json_error( __( 'ディレクトリ名に使用できない文字が含まれています。', 'bf-secret-file-downloader' ) );
        }

        // 親ディレクトリの存在チェック
        $real_parent_path = realpath( $parent_path );
        if ( $real_parent_path === false || ! is_dir( $real_parent_path ) ) {
            wp_send_json_error( __( '親ディレクトリが存在しません。', 'bf-secret-file-downloader' ) );
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
                wp_send_json_error( __( 'WordPressシステムディレクトリまたはそのサブディレクトリ内にはディレクトリを作成できません。', 'bf-secret-file-downloader' ) );
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
            wp_send_json_error( 'このディレクトリには作成権限がありません。' );
        }

        // 新しいディレクトリのパス
        $new_directory_path = $parent_path . DIRECTORY_SEPARATOR . $directory_name;

        // 既存チェック
        if ( file_exists( $new_directory_path ) ) {
            wp_send_json_error( '同名のディレクトリまたはファイルが既に存在します。' );
        }

        // ディレクトリ作成
        if ( wp_mkdir_p( $new_directory_path ) ) {
            wp_send_json_success( array(
                'message' => 'ディレクトリを作成しました。',
                'new_directory' => $new_directory_path,
                'parent_path' => $parent_path
            ));
        } else {
            wp_send_json_error( 'ディレクトリの作成に失敗しました。' );
        }
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
        return get_option( 'bf_sfd_target_directory', '' );
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
        return sanitize_text_field( $value );
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
     * パスを正規化します
     *
     * @param string $path パス
     * @return string 正規化されたパス
     */
    private function normalize_path( $path ) {
        // 連続するスラッシュを単一スラッシュに変換し、末尾のスラッシュを削除
        return rtrim( preg_replace( '#/+#', '/', $path ), '/' );
    }

    /**
     * パスが絶対パスかどうかをチェックします
     *
     * @param string $path パス
     * @return bool 絶対パスの場合true
     */
    private function is_absolute_path( $path ) {
        // Unix/Linux系の場合は '/' で始まる
        if ( strpos( $path, '/' ) === 0 ) {
            return true;
        }

        // Windows系の場合は 'C:' のようなドライブレターで始まる
        if ( preg_match( '/^[a-zA-Z]:[\/\\\\]/', $path ) ) {
            return true;
        }

        return false;
    }

    /**
     * サニタイズ: ディレクトリパス
     *
     * @param string $value ディレクトリパス
     * @return string サニタイズされたディレクトリパス
     */
    public function sanitize_directory( $value ) {
        $value = sanitize_text_field( $value );

        // パスを正規化
        $value = $this->normalize_path( $value );

        // 現在の対象ディレクトリを取得
        $current_directory = get_option( 'bf_sfd_target_directory', '' );

        // 空の場合はそのまま返す
        if ( empty( $value ) ) {
            return '';
        }

        // セキュリティチェック: 危険なディレクトリの拒否
        $dangerous_directories = [
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
            ABSPATH,                // WordPressルートディレクトリ - wp-config.php等が含まれる
            dirname( ABSPATH ),     // WordPressの親ディレクトリ
            ABSPATH . 'wp-admin',   // WordPress管理ディレクトリ
            ABSPATH . 'wp-includes', // WordPressコアファイル
        ];

        // wp-contentディレクトリ内の危険な場所も追加
        if ( defined( 'WP_CONTENT_DIR' ) ) {
            $dangerous_directories[] = WP_CONTENT_DIR . '/plugins';     // プラグインディレクトリ
            $dangerous_directories[] = WP_CONTENT_DIR . '/themes';      // テーマディレクトリ
            $dangerous_directories[] = WP_CONTENT_DIR . '/mu-plugins'; // Must-useプラグイン
        }

        // シンボリックリンク攻撃の検出
        if ( is_link( $value ) ) {
            add_action( 'admin_footer', function() use ( $value ) {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    alert('<?php echo esc_js( sprintf(
                        __( 'セキュリティ上の理由により、シンボリックリンク "%s" は設定できません。シンボリックリンクを介した攻撃を防ぐため、実際のディレクトリパスを指定してください。', 'bf-secret-file-downloader' ),
                        $value
                    ) ); ?>');
                });
                </script>
                <?php
            });
            return '';
        }

        // 危険なディレクトリのチェック（シンボリックリンク解決前と後の両方）
        $check_paths = [ $value ]; // 元のパス
        $real_value = realpath( $value );
        if ( $real_value !== false && $real_value !== $value ) {
            $check_paths[] = $real_value; // 解決されたパス
        }

        foreach ( $check_paths as $check_path ) {
            foreach ( $dangerous_directories as $dangerous_dir ) {
                $real_dangerous = realpath( $dangerous_dir );

                // 元のパスと解決されたパスの両方をチェック
                $paths_to_check = [ $dangerous_dir ];
                if ( $real_dangerous !== false && $real_dangerous !== $dangerous_dir ) {
                    $paths_to_check[] = $real_dangerous;
                }

                foreach ( $paths_to_check as $dangerous_path ) {
                    if ( $check_path === $dangerous_path ||
                         strpos( $check_path, $dangerous_path . DIRECTORY_SEPARATOR ) === 0 ) {

                        // セキュリティアラートを表示
                        add_action( 'admin_footer', function() use ( $value, $check_path, $dangerous_path ) {
                            ?>
                            <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                alert('<?php echo esc_js( sprintf(
                                    __( 'セキュリティ上の理由により、ディレクトリ "%s" は設定できません。このパス（または解決先: %s）は危険なディレクトリ "%s" に解決されます。', 'bf-secret-file-downloader' ),
                                    $value, $check_path, $dangerous_path
                                ) ); ?>');
                            });
                            </script>
                            <?php
                        });

                        // 空文字を返して設定を拒否
                        return '';
                    }
                }
            }
        }

        // 相対パスや不正なパスのチェック
        if ( strpos( $value, '..' ) !== false || ! $this->is_absolute_path( $value ) ) {
            add_action( 'admin_footer', function() {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    alert('<?php echo esc_js( __( '相対パスや不正なパスは設定できません。絶対パスを使用してください。', 'bf-secret-file-downloader' ) ); ?>');
                });
                </script>
                <?php
            });
            return '';
        }

        // ディレクトリが存在し、読み取り可能な場合のみ保存
        if ( is_dir( $value ) && is_readable( $value ) ) {
            // ディレクトリが変更された場合
            if ( ! empty( $current_directory ) && $current_directory !== $value ) {
                // 既存のディレクトリパスワードをすべてクリア
                $this->clear_all_directory_passwords();

                // JavaScript側でアラートを表示するためのフラグを設定
                add_action( 'admin_footer', array( $this, 'show_directory_change_alert' ) );
            }

            return $value;
        }

        // 無効なディレクトリの場合は空文字を返す
        return '';
    }

    /**
     * すべてのディレクトリパスワードをクリアします
     */
    private function clear_all_directory_passwords() {
        delete_option( 'bf_sfd_directory_passwords' );
    }

    /**
     * ディレクトリ変更アラートを表示するJavaScriptを出力します
     */
    public function show_directory_change_alert() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            alert('<?php esc_html_e( '対象ディレクトリが変更されました。既存のディレクトリパスワードはすべてクリアされました。', 'bf-secret-file-downloader' ); ?>');
        });
        </script>
        <?php
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
}
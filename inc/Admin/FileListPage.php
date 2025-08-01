<?php
/**
 * ファイルリストページを管理するクラス
 *
 * @package BfBasicGuard
 */

namespace Breadfish\SecretFileDownloader\Admin;

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * FileListPage クラス
 * ファイルリスト機能を管理します
 */
class FileListPage {

    /**
     * ページスラッグ
     */
    const PAGE_SLUG = 'bf-secret-file-downloader';

    /**
     * 1ページあたりのファイル表示数
     */
    const FILES_PER_PAGE = 20;

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
        add_action( 'wp_ajax_bf_basic_guard_browse_files', array( $this, 'ajax_browse_files' ) );
        add_action( 'wp_ajax_bf_basic_guard_upload_file', array( $this, 'ajax_upload_file' ) );
        add_action( 'wp_ajax_bf_basic_guard_create_directory', array( $this, 'ajax_create_directory' ) );
        add_action( 'wp_ajax_bf_basic_guard_delete_file', array( $this, 'ajax_delete_file' ) );
        add_action( 'wp_ajax_bf_basic_guard_bulk_delete', array( $this, 'ajax_bulk_delete' ) );
        add_action( 'wp_ajax_bf_basic_guard_download_file', array( $this, 'ajax_download_file' ) );
        add_action( 'wp_ajax_bf_basic_guard_set_directory_password', array( $this, 'ajax_set_directory_password' ) );
        add_action( 'wp_ajax_bf_basic_guard_get_directory_password', array( $this, 'ajax_get_directory_password' ) );
        add_action( 'admin_post_nopriv_bf_basic_guard_file_download', array( $this, 'handle_file_download' ) );
        add_action( 'admin_post_bf_basic_guard_file_download', array( $this, 'handle_file_download' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * 管理画面用のスクリプトとスタイルをエンキューします
     */
    public function enqueue_admin_scripts( $hook ) {
        // 現在のページが該当するページかチェック
        if ( $hook !== 'toplevel_page_bf-secret-file-downloader' ) {
            return;
        }

        // Dashiconsを確実に読み込む
        wp_enqueue_style( 'dashicons' );
    }

    /**
     * ファイルブラウズのAJAXハンドラ
     */
    public function ajax_browse_files() {
        // セキュリティチェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['path'] ?? '' );
        $page = intval( $_POST['page'] ?? 1 );
        $sort_by = sanitize_text_field( $_POST['sort_by'] ?? 'name' );
        $sort_order = sanitize_text_field( $_POST['sort_order'] ?? 'asc' );

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $relative_path );

        // セキュリティチェック
        if ( ! $this->is_allowed_directory( $full_path ) ) {
            wp_send_json_error( __( 'このディレクトリへのアクセスは許可されていません。', 'bf-secret-file-downloader' ) );
        }

        // ディレクトリの存在チェック
        if ( ! is_dir( $full_path ) || ! is_readable( $full_path ) ) {
            wp_send_json_error( __( 'ディレクトリにアクセスできません。', 'bf-secret-file-downloader' ) );
        }

        try {
            $files_data = $this->get_directory_contents( $full_path, $relative_path, $page, $sort_by, $sort_order );
            wp_send_json_success( $files_data );
        } catch ( \Exception $e ) {
            wp_send_json_error( __( 'ファイルリストの取得に失敗しました。', 'bf-secret-file-downloader' ) );
        }
    }

    /**
     * ファイルアップロードのAJAXハンドラ
     */
    public function ajax_upload_file() {
        // セキュリティチェック
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['target_path'] ?? '' );

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $target_path = $this->build_full_path( $base_directory, $relative_path );

        // アップロード先ディレクトリのチェック
        if ( ! $this->is_allowed_directory( $target_path ) ) {
            wp_send_json_error( __( 'このディレクトリへのアップロードは許可されていません。', 'bf-secret-file-downloader' ) );
        }

        if ( ! is_dir( $target_path ) || ! is_writable( $target_path ) ) {
            wp_send_json_error( __( 'アップロード先ディレクトリに書き込み権限がありません。', 'bf-secret-file-downloader' ) );
        }

        // ファイルがアップロードされているかチェック
        if ( ! isset( $_FILES['file'] ) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( __( 'ファイルのアップロードに失敗しました。', 'bf-secret-file-downloader' ) );
        }

        $uploaded_file = $_FILES['file'];

        // ファイル名のサニタイゼーション
        $filename = sanitize_file_name( $uploaded_file['name'] );
        if ( empty( $filename ) ) {
            wp_send_json_error( __( '無効なファイル名です。', 'bf-secret-file-downloader' ) );
        }

        // ファイルサイズチェック
        $max_size = get_option( 'bf_basic_guard_max_file_size', 10 ) * 1024 * 1024; // MB to bytes
        if ( $uploaded_file['size'] > $max_size ) {
            wp_send_json_error( sprintf(
                __( 'ファイルサイズが制限を超えています。（最大: %sMB）', 'bf-secret-file-downloader' ),
                get_option( 'bf_basic_guard_max_file_size', 10 )
            ));
        }

        // 危険なファイル拡張子のチェック
        $dangerous_extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi' );
        $file_extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( in_array( $file_extension, $dangerous_extensions ) ) {
            wp_send_json_error( __( 'このファイル形式はセキュリティ上の理由でアップロードできません。', 'bf-secret-file-downloader' ) );
        }

        // アップロード先のファイルパス
        $target_file_path = $target_path . DIRECTORY_SEPARATOR . $filename;

        // 既存ファイルの確認
        if ( file_exists( $target_file_path ) ) {
            // ファイル名に連番を追加
            $file_info = pathinfo( $filename );
            $counter = 1;
            do {
                $new_filename = $file_info['filename'] . '_' . $counter;
                if ( isset( $file_info['extension'] ) ) {
                    $new_filename .= '.' . $file_info['extension'];
                }
                $target_file_path = $target_path . DIRECTORY_SEPARATOR . $new_filename;
                $counter++;
            } while ( file_exists( $target_file_path ) );
            $filename = $new_filename;
        }

        // ファイルを移動
        if ( move_uploaded_file( $uploaded_file['tmp_name'], $target_file_path ) ) {
            // アップロード成功
            wp_send_json_success( array(
                'message' => sprintf( __( '%s をアップロードしました。', 'bf-secret-file-downloader' ), $filename ),
                'filename' => $filename,
                'relative_path' => $relative_path
            ));
        } else {
            wp_send_json_error( __( 'ファイルの保存に失敗しました。', 'bf-secret-file-downloader' ) );
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

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['parent_path'] ?? '' );
        $directory_name = sanitize_text_field( $_POST['directory_name'] ?? '' );

        // 入力値チェック
        if ( empty( $directory_name ) ) {
            wp_send_json_error( __( 'ディレクトリ名が指定されていません。', 'bf-secret-file-downloader' ) );
        }

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $parent_path = $this->build_full_path( $base_directory, $relative_path );

        // ディレクトリ名のバリデーション
        if ( ! preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $directory_name ) ) {
            wp_send_json_error( __( 'ディレクトリ名に使用できない文字が含まれています。英数字、アンダーバー、ハイフン、ドットのみ使用可能です。', 'bf-secret-file-downloader' ) );
        }

        // ドットで始まるディレクトリ名を禁止
        if ( strpos( $directory_name, '.' ) === 0 ) {
            wp_send_json_error( __( 'ドットで始まるディレクトリ名は作成できません。', 'bf-secret-file-downloader' ) );
        }

        // 親ディレクトリの存在チェック
        if ( ! is_dir( $parent_path ) ) {
            wp_send_json_error( __( '親ディレクトリが存在しません。', 'bf-secret-file-downloader' ) );
        }

        // セキュリティ：許可されたディレクトリのみ
        if ( ! $this->is_allowed_directory( $parent_path ) ) {
            wp_send_json_error( __( 'このディレクトリには作成権限がありません。', 'bf-secret-file-downloader' ) );
        }

        // 書き込み権限チェック
        if ( ! is_writable( $parent_path ) ) {
            wp_send_json_error( __( '親ディレクトリに書き込み権限がありません。', 'bf-secret-file-downloader' ) );
        }

        // 新しいディレクトリのパス
        $new_directory_path = $parent_path . DIRECTORY_SEPARATOR . $directory_name;

        // 既存チェック
        if ( file_exists( $new_directory_path ) ) {
            wp_send_json_error( __( '同名のディレクトリまたはファイルが既に存在します。', 'bf-secret-file-downloader' ) );
        }

        // ディレクトリ作成
        if ( wp_mkdir_p( $new_directory_path ) ) {
            wp_send_json_success( array(
                'message' => sprintf( __( 'ディレクトリ「%s」を作成しました。', 'bf-secret-file-downloader' ), $directory_name ),
                'new_directory' => $new_directory_path,
                'parent_path' => $parent_path
            ));
        } else {
            wp_send_json_error( __( 'ディレクトリの作成に失敗しました。', 'bf-secret-file-downloader' ) );
        }
    }

    /**
     * ファイル削除のAJAXハンドラ
     */
    public function ajax_delete_file() {
        // セキュリティチェック
        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['file_path'] ?? '' );

        // 入力値チェック
        if ( empty( $relative_path ) ) {
            wp_send_json_error( __( 'ファイルパスが指定されていません。', 'bf-secret-file-downloader' ) );
        }

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $relative_path );

        // セキュリティチェック：許可されたディレクトリのみ
        if ( ! $this->is_allowed_directory( dirname( $full_path ) ) ) {
            wp_send_json_error( __( 'このファイルの削除は許可されていません。', 'bf-secret-file-downloader' ) );
        }

        // ファイル存在チェック
        if ( ! file_exists( $full_path ) ) {
            wp_send_json_error( __( '指定されたファイルが見つかりません。', 'bf-secret-file-downloader' ) );
        }

        // 削除権限チェック
        $parent_dir = dirname( $full_path );
        if ( ! is_writable( $parent_dir ) ) {
            wp_send_json_error( __( 'このファイルを削除する権限がありません。', 'bf-secret-file-downloader' ) );
        }

        $filename = basename( $full_path );
        $is_directory = is_dir( $full_path );

        // 削除実行
        if ( $is_directory ) {
            // ディレクトリの削除
            if ( $this->delete_directory_recursive( $full_path ) ) {
                // 親ディレクトリの相対パスを取得
                $parent_relative_path = dirname( $relative_path );
                if ( $parent_relative_path === '.' ) {
                    $parent_relative_path = '';
                }

                wp_send_json_success( array(
                    'message' => sprintf( __( 'ディレクトリ「%s」を削除しました。', 'bf-secret-file-downloader' ), $filename ),
                    'deleted_path' => $relative_path,
                    'parent_path' => $parent_relative_path,
                    'type' => 'directory'
                ));
            } else {
                wp_send_json_error( __( 'ディレクトリの削除に失敗しました。', 'bf-secret-file-downloader' ) );
            }
        } else {
            // ファイルの削除
            if ( unlink( $full_path ) ) {
                // 親ディレクトリの相対パスを取得
                $parent_relative_path = dirname( $relative_path );
                if ( $parent_relative_path === '.' ) {
                    $parent_relative_path = '';
                }

                wp_send_json_success( array(
                    'message' => sprintf( __( 'ファイル「%s」を削除しました。', 'bf-secret-file-downloader' ), $filename ),
                    'deleted_path' => $relative_path,
                    'parent_path' => $parent_relative_path,
                    'type' => 'file'
                ));
            } else {
                wp_send_json_error( __( 'ファイルの削除に失敗しました。', 'bf-secret-file-downloader' ) );
            }
        }
    }

    /**
     * 一括削除のAJAXハンドラ
     */
    public function ajax_bulk_delete() {
        // セキュリティチェック
        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $file_paths = $_POST['file_paths'] ?? array();

        // 入力値チェック
        if ( empty( $file_paths ) || ! is_array( $file_paths ) ) {
            wp_send_json_error( __( '削除するファイルが選択されていません。', 'bf-secret-file-downloader' ) );
        }

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        $deleted_files = array();
        $failed_files = array();
        $current_path_deleted = false;
        $redirect_path = '';

        foreach ( $file_paths as $relative_path ) {
            $relative_path = sanitize_text_field( $relative_path );

            if ( empty( $relative_path ) ) {
                continue;
            }

            // フルパスを構築
            $full_path = $this->build_full_path( $base_directory, $relative_path );

            // セキュリティチェック：許可されたディレクトリのみ
            if ( ! $this->is_allowed_directory( dirname( $full_path ) ) ) {
                $failed_files[] = array(
                    'path' => $relative_path,
                    'error' => __( 'このファイルの削除は許可されていません。', 'bf-secret-file-downloader' )
                );
                continue;
            }

            // ファイル存在チェック
            if ( ! file_exists( $full_path ) ) {
                $failed_files[] = array(
                    'path' => $relative_path,
                    'error' => __( 'ファイルが見つかりません。', 'bf-secret-file-downloader' )
                );
                continue;
            }

            // 削除権限チェック
            $parent_dir = dirname( $full_path );
            if ( ! is_writable( $parent_dir ) ) {
                $failed_files[] = array(
                    'path' => $relative_path,
                    'error' => __( 'このファイルを削除する権限がありません。', 'bf-secret-file-downloader' )
                );
                continue;
            }

            $filename = basename( $full_path );
            $is_directory = is_dir( $full_path );

            // 削除実行
            $delete_success = false;
            if ( $is_directory ) {
                $delete_success = $this->delete_directory_recursive( $full_path );
            } else {
                $delete_success = unlink( $full_path );
            }

            if ( $delete_success ) {
                $deleted_files[] = array(
                    'path' => $relative_path,
                    'name' => $filename,
                    'type' => $is_directory ? 'directory' : 'file'
                );

                // 現在のパスが削除されたかチェック
                if ( $is_directory ) {
                    $current_path = sanitize_text_field( $_POST['current_path'] ?? '' );
                    if ( $current_path === $relative_path ||
                         ( $current_path && $relative_path && strpos( $current_path, $relative_path . '/' ) === 0 ) ) {
                        $current_path_deleted = true;
                        if ( empty( $redirect_path ) ) {
                            $redirect_path = dirname( $relative_path );
                            if ( $redirect_path === '.' ) {
                                $redirect_path = '';
                            }
                        }
                    }
                }
            } else {
                $failed_files[] = array(
                    'path' => $relative_path,
                    'error' => $is_directory ? __( 'ディレクトリの削除に失敗しました。', 'bf-secret-file-downloader' ) : __( 'ファイルの削除に失敗しました。', 'bf-secret-file-downloader' )
                );
            }
        }

        // 結果をまとめる
        $response_data = array(
            'deleted_files' => $deleted_files,
            'failed_files' => $failed_files,
            'deleted_count' => count( $deleted_files ),
            'failed_count' => count( $failed_files ),
            'current_path_deleted' => $current_path_deleted,
            'redirect_path' => $redirect_path
        );

        if ( count( $deleted_files ) > 0 && count( $failed_files ) === 0 ) {
            // 全て成功
            $message = sprintf(
                _n(
                    '%d個のアイテムを削除しました。',
                    '%d個のアイテムを削除しました。',
                    count( $deleted_files ),
                    'bf-secret-file-downloader'
                ),
                count( $deleted_files )
            );
            $response_data['message'] = $message;
            wp_send_json_success( $response_data );
        } elseif ( count( $deleted_files ) > 0 && count( $failed_files ) > 0 ) {
            // 一部成功
            $message = sprintf(
                __( '%d個のアイテムを削除しました。%d個のアイテムで失敗しました。', 'bf-secret-file-downloader' ),
                count( $deleted_files ),
                count( $failed_files )
            );
            $response_data['message'] = $message;
            wp_send_json_success( $response_data );
        } else {
            // 全て失敗
            wp_send_json_error( __( '選択されたアイテムの削除に失敗しました。', 'bf-secret-file-downloader' ) );
        }
    }

    /**
     * ファイルダウンロードのAJAXハンドラ
     */
    public function ajax_download_file() {
        // セキュリティチェック
        if ( ! current_user_can( 'read' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['file_path'] ?? '' );

        // 入力値チェック
        if ( empty( $relative_path ) ) {
            wp_send_json_error( __( 'ファイルパスが指定されていません。', 'bf-secret-file-downloader' ) );
        }

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $relative_path );

        // セキュリティチェック：許可されたディレクトリのみ
        if ( ! $this->is_allowed_directory( dirname( $full_path ) ) ) {
            wp_send_json_error( __( 'このファイルのダウンロードは許可されていません。', 'bf-secret-file-downloader' ) );
        }

        // ファイル存在チェック
        if ( ! file_exists( $full_path ) || ! is_file( $full_path ) ) {
            wp_send_json_error( __( '指定されたファイルが見つかりません。', 'bf-secret-file-downloader' ) );
        }

        // 読み込み権限チェック
        if ( ! is_readable( $full_path ) ) {
            wp_send_json_error( __( 'このファイルを読み取る権限がありません。', 'bf-secret-file-downloader' ) );
        }

        // ダウンロード用の一時的なトークンを生成
        $download_token = wp_generate_password( 32, false );
        $token_data = array(
            'file_path' => $relative_path,
            'user_id' => get_current_user_id(),
            'expires' => time() + 300, // 5分間有効
        );

        // トークンをトランジェントとして保存
        set_transient( 'bf_basic_guard_download_' . $download_token, $token_data, 300 );

        // ダウンロードURLを生成
        $download_url = add_query_arg( array(
            'action' => 'bf_basic_guard_file_download',
            'bf_download' => $download_token
        ), admin_url( 'admin-post.php' ) );

        wp_send_json_success( array(
            'download_url' => $download_url,
            'filename' => basename( $full_path )
        ));
    }

    /**
     * ファイルダウンロード処理
     */
    public function handle_file_download() {
        $download_token = sanitize_text_field( $_GET['bf_download'] ?? '' );

        if ( empty( $download_token ) ) {
            wp_die( __( '無効なダウンロードトークンです。', 'bf-secret-file-downloader' ), 400 );
        }

        // トークンを検証
        $token_data = get_transient( 'bf_basic_guard_download_' . $download_token );
        if ( $token_data === false ) {
            wp_die( __( 'ダウンロードトークンが無効または期限切れです。', 'bf-secret-file-downloader' ), 400 );
        }

        // トークンを削除（一回限りの使用）
        delete_transient( 'bf_basic_guard_download_' . $download_token );

        // トークンの有効期限をチェック
        if ( time() > $token_data['expires'] ) {
            wp_die( __( 'ダウンロードトークンの有効期限が切れています。', 'bf-secret-file-downloader' ), 400 );
        }

        // ユーザー権限チェック
        if ( ! current_user_can( 'read' ) ) {
            wp_die( __( 'ファイルをダウンロードする権限がありません。', 'bf-secret-file-downloader' ), 403 );
        }

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_die( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ), 500 );
        }

        // フルパスを構築
        $relative_path = $token_data['file_path'];
        $full_path = $this->build_full_path( $base_directory, $relative_path );

        // セキュリティチェック
        if ( ! $this->is_allowed_directory( dirname( $full_path ) ) ) {
            wp_die( __( 'このファイルのダウンロードは許可されていません。', 'bf-secret-file-downloader' ), 403 );
        }

        // ファイル存在チェック
        if ( ! file_exists( $full_path ) || ! is_file( $full_path ) ) {
            wp_die( __( '指定されたファイルが見つかりません。', 'bf-secret-file-downloader' ), 404 );
        }

        // 読み込み権限チェック
        if ( ! is_readable( $full_path ) ) {
            wp_die( __( 'このファイルを読み取る権限がありません。', 'bf-secret-file-downloader' ), 403 );
        }

        // ファイル情報を取得
        $filename = basename( $full_path );
        $filesize = filesize( $full_path );
        $mime_type = wp_check_filetype( $filename )['type'] ?? 'application/octet-stream';

        // ダウンロード用のヘッダーを設定
        if ( ! headers_sent() ) {
            header( 'Content-Type: ' . $mime_type );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Content-Length: ' . $filesize );
            header( 'Cache-Control: no-cache, must-revalidate' );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );

            // ファイルを出力
            readfile( $full_path );
        }

        exit;
    }

    /**
     * ページを表示します
     */
    public function render() {
        // ビューで使用するデータを準備
        $import = $this->prepare_data();

        // ViewRendererを使用してビューをレンダリング
        \Breadfish\SecretFileDownloader\ViewRenderer::admin( 'file-list.php', $import );
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
     * フルパスから相対パスを取得します
     *
     * @param string $full_path フルパス
     * @param string $base_directory ベースディレクトリ
     * @return string 相対パス
     */
    private function get_relative_path( $full_path, $base_directory ) {
        $base_directory = rtrim( $base_directory, DIRECTORY_SEPARATOR );
        if ( strpos( $full_path, $base_directory ) === 0 ) {
            $relative_path = substr( $full_path, strlen( $base_directory ) );
            return trim( $relative_path, DIRECTORY_SEPARATOR );
        }
        return '';
    }

    /**
     * 現在のパスを取得します（相対パス）
     *
     * @return string 現在の相対パス
     */
    private function get_current_path() {
        return sanitize_text_field( $_GET['path'] ?? '' );
    }

    /**
     * アイテムをソートします
     *
     * @param array $items ソート対象のアイテム配列
     * @param string $sort_by ソートフィールド
     * @param string $sort_order ソート順序
     * @return array ソート済みアイテム配列
     */
    private function sort_items( $items, $sort_by, $sort_order ) {
        usort( $items, function( $a, $b ) use ( $sort_by, $sort_order ) {
            $result = 0;

            switch ( $sort_by ) {
                case 'name':
                    $result = strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
                    break;
                case 'size':
                    // ディレクトリのサイズは比較対象外
                    if ( $a['size'] === '-' && $b['size'] === '-' ) {
                        $result = strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
                    } elseif ( $a['size'] === '-' ) {
                        $result = -1;
                    } elseif ( $b['size'] === '-' ) {
                        $result = 1;
                    } else {
                        $result = $a['size'] - $b['size'];
                    }
                    break;
                case 'modified':
                    $result = $a['modified'] - $b['modified'];
                    break;
                default:
                    $result = strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
                    break;
            }

            return $sort_order === 'desc' ? -$result : $result;
        });

        return $items;
    }

    /**
     * ファイルタイプに応じたCSSクラスを取得します
     *
     * @param string $filename ファイル名
     * @return string CSSクラス
     */
    private function get_file_type_class( $filename ) {
        $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

        // 画像ファイル
        $image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico' );
        if ( in_array( $extension, $image_extensions ) ) {
            return 'image-file';
        }

        // ドキュメントファイル
        $document_extensions = array( 'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'pages' );
        if ( in_array( $extension, $document_extensions ) ) {
            return 'document-file';
        }

        // アーカイブファイル
        $archive_extensions = array( 'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz' );
        if ( in_array( $extension, $archive_extensions ) ) {
            return 'archive-file';
        }

        return '';
    }

    /**
     * ビューで使用するデータを準備します
     *
     * @return array ビューで使用するデータ
     */
    private function prepare_data() {
        $relative_path = $this->get_current_path();
        $page = $this->get_current_page();
        $sort_by = $this->get_current_sort_by();
        $sort_order = $this->get_current_sort_order();

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            return array(
                'files' => array(),
                'total_files' => 0,
                'upload_limit' => $this->get_upload_limit(),
                'current_user_can_upload' => current_user_can( 'upload_files' ),
                'current_path' => '',
                'current_path_display' => '',
                'page' => $page,
                'total_pages' => 0,
                'files_per_page' => self::FILES_PER_PAGE,
                'nonce' => wp_create_nonce( 'bf_basic_guard_file_list_nonce' ),
                'target_directory_set' => false,
                'pagination_html' => '',
                'current_path_writable' => false,
                'max_file_size_mb' => get_option( 'bf_basic_guard_max_file_size', 10 ),
            );
        }

        $full_path = $this->build_full_path( $base_directory, $relative_path );
        $files = $this->get_files( $full_path, $relative_path, $page, $sort_by, $sort_order );
        $total_pages = $this->get_total_pages( $full_path );

                // ファイルデータにフォーマット済みサイズとタイプクラスを追加
        $formatted_files = array();
        foreach ( $files as $file ) {
            $formatted_file = $file;
            if ( $file['size'] !== '-' ) {
                $formatted_file['formatted_size'] = $this->format_file_size( $file['size'] );
            } else {
                $formatted_file['formatted_size'] = '-';
            }

            // ファイルタイプクラスを追加
            if ( $file['type'] === 'file' ) {
                $formatted_file['type_class'] = $this->get_file_type_class( $file['name'] );
            } else {
                $formatted_file['type_class'] = '';
            }

            // デバッグ用ログ
            error_log( 'BF Basic Guard: File - ' . $file['name'] . ', Type: ' . $file['type'] . ', Type Class: ' . $formatted_file['type_class'] );

            $formatted_files[] = $formatted_file;
        }

        return array(
            'files' => $formatted_files,
            'total_files' => $this->get_total_files( $full_path ),
            'upload_limit' => $this->get_upload_limit(),
            'current_user_can_upload' => current_user_can( 'upload_files' ),
            'current_path' => $relative_path,
            'current_path_display' => empty( $relative_path ) ? __( 'ルートディレクトリ', 'bf-secret-file-downloader' ) : $relative_path,
            'page' => $page,
            'total_pages' => $total_pages,
            'files_per_page' => self::FILES_PER_PAGE,
            'nonce' => wp_create_nonce( 'bf_basic_guard_file_list_nonce' ),
            'target_directory_set' => true,
            'pagination_html' => $this->render_pagination( $page, $total_pages, $relative_path, $sort_by, $sort_order ),
            'current_path_writable' => ! empty( $full_path ) && is_writable( $full_path ),
            'max_file_size_mb' => get_option( 'bf_basic_guard_max_file_size', 10 ),
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'current_directory_has_password' => $this->has_directory_password( $relative_path ),
        );
    }

    /**
     * ファイルリストを取得します
     *
     * @param string $full_path フルパス
     * @param string $relative_path 相対パス
     * @param int $page ページ番号
     * @param string $sort_by ソートフィールド
     * @param string $sort_order ソート順序
     * @return array ファイルリスト
     */
    private function get_files( $full_path = '', $relative_path = '', $page = 1, $sort_by = 'name', $sort_order = 'asc' ) {
        if ( empty( $full_path ) ) {
            return array();
        }

        if ( ! $this->is_allowed_directory( $full_path ) || ! is_dir( $full_path ) || ! is_readable( $full_path ) ) {
            return array();
        }

        try {
            $files_data = $this->get_directory_contents( $full_path, $relative_path, $page, $sort_by, $sort_order );
            return $files_data['items'] ?? array();
        } catch ( \Exception $e ) {
            return array();
        }
    }

    /**
     * ディレクトリの内容を取得します
     *
     * @param string $full_path フルパス
     * @param string $relative_path 相対パス
     * @param int $page ページ番号
     * @param string $sort_by ソートフィールド
     * @param string $sort_order ソート順序
     * @return array ディレクトリの内容
     */
    private function get_directory_contents( $full_path, $relative_path = '', $page = 1, $sort_by = 'name', $sort_order = 'asc' ) {
        $directories = array();
        $files = array();
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );

        $items = scandir( $full_path );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }

            // 隠しファイル（ドットから始まるファイル）を除外
            if ( strpos( $item, '.' ) === 0 ) {
                continue;
            }

            $full_item_path = $full_path . DIRECTORY_SEPARATOR . $item;

            // 相対パスを構築
            $item_relative_path = empty( $relative_path )
                ? $item
                : $relative_path . DIRECTORY_SEPARATOR . $item;

            if ( is_dir( $full_item_path ) ) {
                $directories[] = array(
                    'name' => $item,
                    'path' => $item_relative_path,
                    'type' => 'directory',
                    'size' => '-',
                    'modified' => filemtime( $full_item_path ),
                    'readable' => is_readable( $full_item_path ),
                );
            } else {
                $files[] = array(
                    'name' => $item,
                    'path' => $item_relative_path,
                    'type' => 'file',
                    'size' => filesize( $full_item_path ),
                    'modified' => filemtime( $full_item_path ),
                    'readable' => is_readable( $full_item_path ),
                    'type_class' => $this->get_file_type_class( $item ),
                );
            }
        }

        // ソート処理
        $directories = $this->sort_items( $directories, $sort_by, $sort_order );
        $files = $this->sort_items( $files, $sort_by, $sort_order );

        // 全アイテムを結合（ディレクトリを先頭に）
        $all_items = array_merge( $directories, $files );
        $total_items = count( $all_items );

        // ページング処理
        $offset = ( $page - 1 ) * self::FILES_PER_PAGE;
        $paged_items = array_slice( $all_items, $offset, self::FILES_PER_PAGE );

        return array(
            'current_path' => $relative_path,
            'parent_path' => $this->get_parent_relative_path( $relative_path ),
            'items' => $paged_items,
            'total_items' => $total_items,
            'total_pages' => ceil( $total_items / self::FILES_PER_PAGE ),
            'current_page' => $page,
            'has_parent' => $this->can_navigate_to_parent( $relative_path ),
            'current_directory_has_password' => $this->has_directory_password( $relative_path ),
        );
    }

    /**
     * 親ディレクトリの相対パスを取得します
     *
     * @param string $relative_path 現在の相対パス
     * @return string 親ディレクトリの相対パス
     */
    private function get_parent_relative_path( $relative_path ) {
        if ( empty( $relative_path ) ) {
            return '';
        }

        $parts = explode( DIRECTORY_SEPARATOR, trim( $relative_path, DIRECTORY_SEPARATOR ) );
        array_pop( $parts );

        return implode( DIRECTORY_SEPARATOR, $parts );
    }

    /**
     * 親ディレクトリへのナビゲーションが可能かチェックします
     *
     * @param string $relative_path 現在の相対パス
     * @return bool ナビゲーション可能フラグ
     */
    private function can_navigate_to_parent( $relative_path ) {
        // ルートディレクトリの場合はfalse
        return ! empty( $relative_path );
    }

    /**
     * ページタイトルを取得します
     *
     * @return string ページタイトル
     */
    public function get_page_title() {
        return __( 'ファイルリスト', 'bf-secret-file-downloader' );
    }

    /**
     * ページングHTMLを生成します
     *
     * @param int $current_page 現在のページ
     * @param int $total_pages 総ページ数
     * @param string $current_path 現在のパス
     * @param string $sort_by ソートフィールド
     * @param string $sort_order ソート順序
     * @return string ページングHTML
     */
    public function render_pagination( $current_page, $total_pages, $current_path, $sort_by = 'name', $sort_order = 'asc' ) {
        if ( $total_pages <= 1 ) {
            return '';
        }

        $html = '<span class="pagination-links">';

        // 前のページ
        if ( $current_page > 1 ) {
            $prev_url = add_query_arg( array(
                'page' => 'bf-secret-file-downloader',
                'path' => urlencode( $current_path ),
                'paged' => $current_page - 1,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ), admin_url( 'admin.php' ) );
            $html .= '<a href="' . esc_url( $prev_url ) . '">&laquo; ' . __( '前', 'bf-secret-file-downloader' ) . '</a>';
        }

        // ページ番号
        $start_page = max( 1, $current_page - 2 );
        $end_page = min( $total_pages, $current_page + 2 );

        for ( $i = $start_page; $i <= $end_page; $i++ ) {
            if ( $i == $current_page ) {
                $html .= '<span class="current">' . $i . '</span>';
            } else {
                $page_url = add_query_arg( array(
                    'page' => 'bf-secret-file-downloader',
                    'path' => urlencode( $current_path ),
                    'paged' => $i,
                    'sort_by' => $sort_by,
                    'sort_order' => $sort_order
                ), admin_url( 'admin.php' ) );
                $html .= '<a href="' . esc_url( $page_url ) . '">' . $i . '</a>';
            }
        }

        // 次のページ
        if ( $current_page < $total_pages ) {
            $next_url = add_query_arg( array(
                'page' => 'bf-secret-file-downloader',
                'path' => urlencode( $current_path ),
                'paged' => $current_page + 1,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ), admin_url( 'admin.php' ) );
            $html .= '<a href="' . esc_url( $next_url ) . '">' . __( '次', 'bf-secret-file-downloader' ) . ' &raquo;</a>';
        }

        $html .= '</span>';
        return $html;
    }

    /**
     * ファイルサイズをフォーマットします
     *
     * @param int $bytes バイト数
     * @return string フォーマット済みファイルサイズ
     */
    public function format_file_size( $bytes ) {
        if ( $bytes == 0 ) {
            return '0 B';
        }

        $k = 1024;
        $sizes = array( 'B', 'KB', 'MB', 'GB', 'TB' );
        $i = floor( log( $bytes ) / log( $k ) );

        return round( $bytes / pow( $k, $i ), 2 ) . ' ' . $sizes[ $i ];
    }

    /**
     * メニュータイトルを取得します
     *
     * @return string メニュータイトル
     */
    public function get_menu_title() {
        return __( 'ファイルリスト', 'bf-secret-file-downloader' );
    }

    /**
     * ファイル総数を取得します
     *
     * @param string $path ディレクトリパス
     * @return int ファイル総数
     */
    private function get_total_files( $path = '' ) {
        if ( empty( $path ) ) {
            return 0;
        }

        if ( ! $this->is_allowed_directory( $path ) || ! is_dir( $path ) || ! is_readable( $path ) ) {
            return 0;
        }

        try {
            $items = scandir( $path );
            $count = 0;
            foreach ( $items as $item ) {
                if ( $item !== '.' && $item !== '..' ) {
                    // 隠しファイル（ドットから始まるファイル）を除外
                    if ( strpos( $item, '.' ) === 0 ) {
                        continue;
                    }
                    $count++;
                }
            }
            return $count;
        } catch ( \Exception $e ) {
            return 0;
        }
    }

    /**
     * 総ページ数を取得します
     *
     * @param string $path ディレクトリパス
     * @return int 総ページ数
     */
    private function get_total_pages( $path = '' ) {
        $total_files = $this->get_total_files( $path );
        return ceil( $total_files / self::FILES_PER_PAGE );
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
     * アップロード制限を取得します
     *
     * @return string アップロード制限
     */
    private function get_upload_limit() {
        $max_size = get_option( 'bf_basic_guard_max_file_size', 10 );
        return $max_size . 'MB';
    }

    /**
     * 現在のページ番号を取得します
     *
     * @return int 現在のページ番号
     */
    private function get_current_page() {
        return max( 1, intval( $_GET['paged'] ?? 1 ) );
    }

    /**
     * 現在のソートフィールドを取得します
     *
     * @return string 現在のソートフィールド
     */
    private function get_current_sort_by() {
        $sort_by = sanitize_text_field( $_GET['sort_by'] ?? 'name' );
        $allowed_sorts = array( 'name', 'size', 'modified' );
        return in_array( $sort_by, $allowed_sorts ) ? $sort_by : 'name';
    }

    /**
     * 現在のソート順序を取得します
     *
     * @return string 現在のソート順序
     */
    private function get_current_sort_order() {
        $sort_order = sanitize_text_field( $_GET['sort_order'] ?? 'asc' );
        return in_array( $sort_order, array( 'asc', 'desc' ) ) ? $sort_order : 'asc';
    }

    /**
     * ディレクトリを再帰的に削除します
     *
     * @param string $directory_path 削除するディレクトリパス
     * @return bool 削除成功フラグ
     */
    private function delete_directory_recursive( $directory_path ) {
        if ( ! is_dir( $directory_path ) ) {
            return false;
        }

        $items = scandir( $directory_path );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }

            $item_path = $directory_path . DIRECTORY_SEPARATOR . $item;

            if ( is_dir( $item_path ) ) {
                // サブディレクトリを再帰的に削除
                if ( ! $this->delete_directory_recursive( $item_path ) ) {
                    return false;
                }
            } else {
                // ファイルを削除
                if ( ! unlink( $item_path ) ) {
                    return false;
                }
            }
        }

        // 空になったディレクトリを削除
        return rmdir( $directory_path );
    }

    /**
     * ディレクトリパスワード設定のAJAXハンドラ
     */
    public function ajax_set_directory_password() {
        // セキュリティチェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['path'] ?? '' );
        $password = sanitize_text_field( $_POST['password'] ?? '' );
        $action_type = sanitize_text_field( $_POST['action_type'] ?? 'set' ); // set, remove

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $relative_path );

        // セキュリティチェック
        if ( ! $this->is_allowed_directory( $full_path ) ) {
            wp_send_json_error( __( 'このディレクトリへのアクセスは許可されていません。', 'bf-secret-file-downloader' ) );
        }

        // ディレクトリの存在チェック
        if ( ! is_dir( $full_path ) ) {
            wp_send_json_error( __( 'ディレクトリが存在しません。', 'bf-secret-file-downloader' ) );
        }

        if ( $action_type === 'remove' ) {
            // パスワードを削除
            $this->remove_directory_password( $relative_path );
            wp_send_json_success( array(
                'message' => __( 'ディレクトリのパスワード保護を解除しました。', 'bf-secret-file-downloader' ),
                'has_password' => false
            ));
        } else {
            // パスワードを設定
            if ( empty( $password ) ) {
                wp_send_json_error( __( 'パスワードを入力してください。', 'bf-secret-file-downloader' ) );
            }

            $this->set_directory_password( $relative_path, $password );
            wp_send_json_success( array(
                'message' => __( 'ディレクトリにパスワード保護を設定しました。', 'bf-secret-file-downloader' ),
                'has_password' => true
            ));
        }
    }

    /**
     * ディレクトリパスワード取得のAJAXハンドラ
     */
    public function ajax_get_directory_password() {
        // セキュリティチェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_ajax_referer( 'bf_basic_guard_file_list_nonce', 'nonce' );

        $relative_path = sanitize_text_field( $_POST['path'] ?? '' );

        // ベースディレクトリを取得
        $base_directory = get_option( 'bf_basic_guard_target_directory', '' );
        if ( empty( $base_directory ) ) {
            wp_send_json_error( __( '対象ディレクトリが設定されていません。', 'bf-secret-file-downloader' ) );
        }

        // フルパスを構築
        $full_path = $this->build_full_path( $base_directory, $relative_path );

        // セキュリティチェック
        if ( ! $this->is_allowed_directory( $full_path ) ) {
            wp_send_json_error( __( 'このディレクトリへのアクセスは許可されていません。', 'bf-secret-file-downloader' ) );
        }

        // パスワードを取得
        $password = $this->get_directory_password( $relative_path );

        if ( $password !== false ) {
            wp_send_json_success( array(
                'password' => $password
            ));
        } else {
            wp_send_json_error( __( 'パスワードを取得できませんでした。', 'bf-secret-file-downloader' ) );
        }
    }

    /**
     * ディレクトリにパスワードを設定します
     *
     * @param string $relative_path 相対パス
     * @param string $password パスワード
     */
    private function set_directory_password( $relative_path, $password ) {
        $directory_passwords = get_option( 'bf_basic_guard_directory_passwords', array() );

        // パスワードを暗号化して保存（管理者確認用）
        $encrypted_password = $this->encrypt_password( $password );

        $directory_passwords[ $relative_path ] = array(
            'hash' => wp_hash_password( $password ), // 認証用ハッシュ
            'encrypted' => $encrypted_password       // 管理者確認用暗号化パスワード
        );

        update_option( 'bf_basic_guard_directory_passwords', $directory_passwords );
    }

    /**
     * ディレクトリのパスワードを削除します
     *
     * @param string $relative_path 相対パス
     */
    private function remove_directory_password( $relative_path ) {
        $directory_passwords = get_option( 'bf_basic_guard_directory_passwords', array() );

        if ( isset( $directory_passwords[ $relative_path ] ) ) {
            unset( $directory_passwords[ $relative_path ] );
            update_option( 'bf_basic_guard_directory_passwords', $directory_passwords );
        }
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
     * ディレクトリのパスワードを取得します（管理者用）
     *
     * @param string $relative_path 相対パス
     * @return string|false 復号化されたパスワード、または失敗時はfalse
     */
    private function get_directory_password( $relative_path ) {
        $directory_passwords = get_option( 'bf_basic_guard_directory_passwords', array() );

        if ( ! isset( $directory_passwords[ $relative_path ] ) ) {
            return false;
        }

        $password_data = $directory_passwords[ $relative_path ];

        // 新しい配列形式でencryptedフィールドがある場合
        if ( is_array( $password_data ) && isset( $password_data['encrypted'] ) ) {
            return $this->decrypt_password( $password_data['encrypted'] );
        }

        return false;
    }

    /**
     * パスワードを暗号化します
     *
     * @param string $password 平文パスワード
     * @return string 暗号化されたパスワード
     */
    private function encrypt_password( $password ) {
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            return base64_encode( $password ); // フォールバック
        }

        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes( 16 );
        $encrypted = openssl_encrypt( $password, 'AES-256-CBC', $key, 0, $iv );

        return base64_encode( $iv . $encrypted );
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
}
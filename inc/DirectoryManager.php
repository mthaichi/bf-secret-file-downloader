<?php
/**
 * セキュアディレクトリ管理機能を提供するクラス
 *
 * @package BfSecretFileDownloader
 */

namespace Breadfish\SecretFileDownloader;

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DirectoryManager クラス
 * セキュアディレクトリの作成・取得・管理機能を提供します
 */
class DirectoryManager {

    /**
     * セキュアなディレクトリを作成
     *
     * @param bool $force_create 既存ディレクトリがあっても強制的に新しいディレクトリを作成するか
     * @return bool 作成に成功した場合はtrue
     */
    public static function create_secure_directory( $force_create = false ) {
        // 既にディレクトリIDが設定されている場合は、force_createがfalseならスキップ
        if ( ! $force_create && get_option( 'bf_sfd_secure_directory_id' ) ) {
            return true;
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
            return false;
        }

        // .htaccessファイルを作成してアクセスを完全に遮断
        $htaccess_content = "# Deny all access\nDeny from all\n";
        file_put_contents( $secure_dir . '/.htaccess', $htaccess_content );

        // index.phpファイルも作成してさらなる保護
        $index_content = "<?php\n// Silence is golden.\nexit;";
        file_put_contents( $secure_dir . '/index.php', $index_content );

        // 既存の設定を更新（add_optionではなくupdate_optionを使用）
        update_option( 'bf_sfd_secure_directory_id', $random_id );
        update_option( 'bf_sfd_target_directory', $secure_dir );

        error_log( 'BF Secret File Downloader: Secure directory created: ' . $secure_dir );
        return true;
    }

    /**
     * セキュアディレクトリのパスを取得
     *
     * @return string セキュアディレクトリのパス（存在しない場合は空文字）
     */
    public static function get_secure_directory() {
        $secure_id = get_option( 'bf_sfd_secure_directory_id', '' );
        if ( empty( $secure_id ) ) {
            return '';
        }

        $uploads_dir = wp_upload_dir();
        return $uploads_dir['basedir'] . '/bf-secret-file-downloader/' . $secure_id;
    }

    /**
     * セキュアディレクトリが存在するかチェック
     *
     * @return bool 存在する場合はtrue
     */
    public static function secure_directory_exists() {
        $secure_dir = self::get_secure_directory();
        return ! empty( $secure_dir ) && is_dir( $secure_dir );
    }

    /**
     * セキュアディレクトリの保護ファイルが適切に設置されているかチェック
     *
     * @return bool 保護ファイルが適切な場合はtrue
     */
    public static function is_secure_directory_protected() {
        $secure_dir = self::get_secure_directory();
        if ( empty( $secure_dir ) || ! is_dir( $secure_dir ) ) {
            return false;
        }

        // .htaccessファイルの存在チェック
        $htaccess_path = $secure_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_path ) ) {
            return false;
        }

        // .htaccessの内容チェック
        $htaccess_content = file_get_contents( $htaccess_path );
        if ( strpos( $htaccess_content, 'Deny from all' ) === false ) {
            return false;
        }

        // index.phpファイルの存在チェック
        $index_path = $secure_dir . '/index.php';
        if ( ! file_exists( $index_path ) ) {
            return false;
        }

        return true;
    }

    /**
     * セキュアディレクトリの保護ファイルを修復
     *
     * @return bool 修復に成功した場合はtrue
     */
    public static function repair_secure_directory_protection() {
        $secure_dir = self::get_secure_directory();
        if ( empty( $secure_dir ) || ! is_dir( $secure_dir ) ) {
            return false;
        }

        // .htaccessファイルを再作成
        $htaccess_content = "# Deny all access\nDeny from all\n";
        if ( file_put_contents( $secure_dir . '/.htaccess', $htaccess_content ) === false ) {
            return false;
        }

        // index.phpファイルを再作成
        $index_content = "<?php\n// Silence is golden.\nexit;";
        if ( file_put_contents( $secure_dir . '/index.php', $index_content ) === false ) {
            return false;
        }

        return true;
    }

    /**
     * セキュアディレクトリのIDを取得
     *
     * @return string セキュアディレクトリのID
     */
    public static function get_secure_directory_id() {
        return get_option( 'bf_sfd_secure_directory_id', '' );
    }

    /**
     * セキュアディレクトリとその設定を削除
     *
     * @param bool $delete_files ファイルも削除するかどうか（デフォルト: true）
     * @return bool 削除に成功した場合はtrue
     */
    public static function remove_secure_directory( $delete_files = true ) {
        $secure_dir = self::get_secure_directory();

        if ( $delete_files && ! empty( $secure_dir ) && is_dir( $secure_dir ) ) {
            // ディレクトリ内のファイルを削除
            $files = scandir( $secure_dir );
            foreach ( $files as $file ) {
                if ( $file !== '.' && $file !== '..' ) {
                    $file_path = $secure_dir . '/' . $file;
                    if ( is_file( $file_path ) ) {
                        wp_delete_file( $file_path );
                    }
                }
            }

            // ディレクトリを削除
            global $wp_filesystem;
            if ( empty( $wp_filesystem ) ) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            $wp_filesystem->rmdir( $secure_dir );
        }

        // オプションを削除
        delete_option( 'bf_sfd_secure_directory_id' );
        delete_option( 'bf_sfd_target_directory' );

        return true;
    }

    /**
     * セキュアディレクトリ内のユーザーファイルのみを削除（保護ファイルは残す）
     *
     * @return bool 削除に成功した場合はtrue
     */
    public static function clear_user_files() {
        $secure_dir = self::get_secure_directory();

        if ( empty( $secure_dir ) || ! is_dir( $secure_dir ) ) {
            return false;
        }

        $files = scandir( $secure_dir );
        $protected_files = array( '.', '..', '.htaccess', 'index.php' );

        foreach ( $files as $file ) {
            if ( ! in_array( $file, $protected_files ) ) {
                $file_path = $secure_dir . '/' . $file;
                if ( is_file( $file_path ) ) {
                    wp_delete_file( $file_path );
                }
            }
        }

        return true;
    }
}
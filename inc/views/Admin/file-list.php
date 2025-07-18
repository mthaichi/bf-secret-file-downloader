<?php
/**
 * ファイルリストページのビューファイル
 *
 * @package BfBasicGuard
 *
 * 利用可能な変数:
 * @var array    $files                    ファイルリスト
 * @var int      $total_files             ファイル総数
 * @var string   $upload_limit            アップロード制限
 * @var bool     $current_user_can_upload アップロード権限
 * @var string   $current_path            現在のパス
 * @var int      $page                    現在のページ
 * @var int      $total_pages             総ページ数
 * @var int      $files_per_page          1ページあたりのファイル数
 * @var string   $nonce                   ナンス
 * @var bool     $target_directory_set    対象ディレクトリが設定されているか
 * @var bool     $current_directory_has_password 現在のディレクトリにパスワード設定があるか
 *
 * 利用可能な関数:
 * @var callable $__                      翻訳関数
 * @var callable $esc_html               HTMLエスケープ関数
 * @var callable $esc_html_e             HTMLエスケープ出力関数
 * @var callable $get_admin_page_title   ページタイトル取得関数
 */

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="bf-basic-guard-file-list">
        <div class="bf-basic-guard-header">
            <p><?php esc_html_e( 'BASIC認証で保護されたファイルを管理します。', 'bf-basic-guard' ); ?></p>
        </div>

        <?php if ( ! $target_directory_set ) : ?>
            <div class="notice notice-warning">
                <p>
                    <?php esc_html_e( '対象ディレクトリが設定されていません。', 'bf-basic-guard' ); ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bf-basic-guard-settings' ) ); ?>">
                        <?php esc_html_e( '設定ページ', 'bf-basic-guard' ); ?>
                    </a>
                    <?php esc_html_e( 'でディレクトリを指定してください。', 'bf-basic-guard' ); ?>
                </p>
            </div>
        <?php else : ?>
            <div class="bf-basic-guard-content">
                <!-- 現在のパス表示 -->
                <div class="bf-basic-guard-path">
                    <div class="bf-path-info">
                        <strong><?php esc_html_e( '現在のディレクトリ:', 'bf-basic-guard' ); ?></strong>
                        <code id="current-path-display"><?php echo esc_html( $current_path_display ); ?></code>
                        <input type="hidden" id="current-path" value="<?php echo esc_attr( $current_path ); ?>">
                        <?php if ( isset( $current_directory_has_password ) && $current_directory_has_password ) : ?>
                            <span class="bf-password-indicator">
                                <span class="dashicons dashicons-lock"></span>
                                <span class="bf-password-status-text"><?php esc_html_e( 'パスワード保護中', 'bf-basic-guard' ); ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="bf-path-actions">
                        <?php if ( ! empty( $current_path ) ) : ?>
                            <button type="button" id="go-up-btn" class="button button-small">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                                <?php esc_html_e( '上の階層へ', 'bf-basic-guard' ); ?>
                            </button>
                        <?php endif; ?>
                        <?php if ( current_user_can( 'manage_options' ) ) : ?>
                                                        <button type="button" id="directory-password-btn" class="button button-small">
                                <span class="dashicons dashicons-admin-network"></span>
                                <?php echo ( isset( $current_directory_has_password ) && $current_directory_has_password )
                                    ? esc_html__( 'パスワード管理', 'bf-basic-guard' )
                                    : esc_html__( 'パスワード設定', 'bf-basic-guard' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ファイル操作エリア -->
                <?php if ( $current_user_can_upload && $current_path_writable ) : ?>
                    <!-- ディレクトリ作成とファイルアップロード -->
                    <div class="bf-basic-guard-actions">
                        <div class="bf-actions-header">
                            <h3><?php esc_html_e( 'ファイル操作', 'bf-basic-guard' ); ?></h3>
                            <div class="bf-action-buttons">
                                <button type="button" id="create-directory-btn" class="button">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php esc_html_e( 'ディレクトリ作成', 'bf-basic-guard' ); ?>
                                </button>
                                <button type="button" id="select-files-btn" class="button">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e( 'ファイル選択', 'bf-basic-guard' ); ?>
                                </button>
                            </div>
                        </div>

                        <!-- ディレクトリ作成フォーム -->
                        <div id="create-directory-form" class="bf-create-directory-form" style="display: none;">
                            <div class="form-group">
                                <label for="directory-name-input"><?php esc_html_e( 'ディレクトリ名:', 'bf-basic-guard' ); ?></label>
                                <input type="text" id="directory-name-input" class="regular-text" placeholder="<?php esc_attr_e( 'ディレクトリ名を入力', 'bf-basic-guard' ); ?>">
                                <div class="form-actions">
                                    <button type="button" id="create-directory-submit" class="button button-primary"><?php esc_html_e( '作成', 'bf-basic-guard' ); ?></button>
                                    <button type="button" id="create-directory-cancel" class="button"><?php esc_html_e( 'キャンセル', 'bf-basic-guard' ); ?></button>
                                </div>
                            </div>
                            <p class="description">
                                <?php esc_html_e( '英数字、アンダーバー（_）、ハイフン（-）、ドット（.）が使用できます。', 'bf-basic-guard' ); ?>
                            </p>
                        </div>

                        <!-- ファイルアップロードエリア -->
                        <div id="drop-zone" class="bf-basic-guard-drop-zone">
                            <div class="drop-zone-content">
                                <span class="dashicons dashicons-upload"></span>
                                <p><strong><?php esc_html_e( 'ファイルをここにドラッグ＆ドロップ', 'bf-basic-guard' ); ?></strong></p>
                                <p><?php echo sprintf( __( '（最大: %sMB）', 'bf-basic-guard' ), esc_html( $max_file_size_mb ) ); ?></p>
                                <input type="file" id="file-input" multiple style="display: none;">
                            </div>
                            <div class="drop-zone-overlay" style="display: none;">
                                <p><?php esc_html_e( 'ファイルをドロップしてください', 'bf-basic-guard' ); ?></p>
                            </div>
                        </div>

                        <!-- アップロード進捗表示 -->
                        <div id="upload-progress" style="display: none; margin: 20px 0;">
                            <div class="upload-progress-bar" style="background: #f1f1f1; border-radius: 3px; overflow: hidden;">
                                <div class="upload-progress-fill" style="background: #0073aa; height: 20px; width: 0%; transition: width 0.3s;"></div>
                            </div>
                            <p id="upload-status"></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ファイル統計 -->
                <div class="bf-basic-guard-stats">
                    <p>
                        <?php
                        if ( $total_files > 0 ) {
                            echo sprintf(
                                __( '%d個のアイテムが見つかりました。', 'bf-basic-guard' ),
                                (int) $total_files
                            );
                        } else {
                            esc_html_e( 'アイテムが見つかりませんでした。', 'bf-basic-guard' );
                        }
                        ?>
                    </p>
                </div>

                <!-- 一括操作とページング（上部） -->
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( '一括操作を選択', 'bf-basic-guard' ); ?></label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php esc_html_e( '一括操作', 'bf-basic-guard' ); ?></option>
                            <?php if ( current_user_can( 'delete_posts' ) ) : ?>
                                <option value="delete"><?php esc_html_e( '削除', 'bf-basic-guard' ); ?></option>
                            <?php endif; ?>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( '適用', 'bf-basic-guard' ); ?>">
                    </div>
                    <?php if ( $total_pages > 1 ) : ?>
                        <div class="tablenav-pages">
                            <?php echo $pagination_html; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ファイルリストテーブル -->
                <div class="bf-basic-guard-file-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column" style="width: 40px;">
                                    <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'すべて選択', 'bf-basic-guard' ); ?></label>
                                    <input id="cb-select-all-1" type="checkbox">
                                </td>
                                <th class="manage-column column-name sortable <?php echo $sort_by === 'name' ? 'sorted ' . esc_attr( $sort_order ) : ''; ?>" style="width: 45%;">
                                    <a href="#" class="sort-link" data-sort="name">
                                        <span><?php esc_html_e( 'ファイル名', 'bf-basic-guard' ); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-type" style="width: 15%;">
                                    <?php esc_html_e( 'タイプ', 'bf-basic-guard' ); ?>
                                </th>
                                <th class="manage-column column-size sortable <?php echo $sort_by === 'size' ? 'sorted ' . esc_attr( $sort_order ) : ''; ?>" style="width: 15%;">
                                    <a href="#" class="sort-link" data-sort="size">
                                        <span><?php esc_html_e( 'サイズ', 'bf-basic-guard' ); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-modified sortable <?php echo $sort_by === 'modified' ? 'sorted ' . esc_attr( $sort_order ) : ''; ?>" style="width: 20%;">
                                    <a href="#" class="sort-link" data-sort="modified">
                                        <span><?php esc_html_e( '更新日', 'bf-basic-guard' ); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="file-list-tbody">
                            <?php if ( ! empty( $files ) ) : ?>
                                <?php foreach ( $files as $file ) : ?>
                                    <tr data-path="<?php echo esc_attr( $file['path'] ); ?>"
                                        data-type="<?php echo esc_attr( $file['type'] ); ?>"
                                        <?php if ( $file['type'] === 'directory' && $file['readable'] ) : ?>
                                            class="clickable-directory"
                                            style="cursor: pointer;"
                                        <?php endif; ?>
                                    >
                                        <th scope="row" class="check-column">
                                            <input type="checkbox"
                                                   name="file_paths[]"
                                                   value="<?php echo esc_attr( $file['path'] ); ?>"
                                                   data-file-name="<?php echo esc_attr( $file['name'] ); ?>"
                                                   data-file-type="<?php echo esc_attr( $file['type'] ); ?>">
                                        </th>
                                        <td class="column-name has-row-actions">
                                            <?php if ( $file['type'] === 'directory' ) : ?>
                                                <span class="bf-icon-wrapper">
                                                    <span class="dashicons dashicons-folder bf-directory-icon" style="font-size: 20px !important; margin-right: 8px; vertical-align: middle; font-family: dashicons !important;"></span>
                                                    <span class="bf-fallback-icon" style="display: none; font-size: 18px; margin-right: 8px; vertical-align: middle;">📁</span>
                                                </span>
                                                <?php if ( $file['readable'] ) : ?>
                                                    <strong class="bf-directory-name row-title"><?php echo esc_html( $file['name'] ); ?></strong>
                                                <?php else : ?>
                                                    <span class="bf-directory-name-disabled row-title"><?php echo esc_html( $file['name'] ); ?></span>
                                                    <small class="bf-access-denied">(<?php esc_html_e( 'アクセス不可', 'bf-basic-guard' ); ?>)</small>
                                                <?php endif; ?>
                                                <div class="row-actions">
                                                    <?php if ( $file['readable'] ) : ?>
                                                        <span class="open"><a href="#" class="open-directory"
                                                                data-path="<?php echo esc_attr( $file['path'] ); ?>"><?php esc_html_e( '開く', 'bf-basic-guard' ); ?></a> | </span>
                                                    <?php endif; ?>
                                                    <?php if ( current_user_can( 'delete_posts' ) ) : ?>
                                                        <span class="delete"><a href="#" class="delete-file-link"
                                                                data-file-path="<?php echo esc_attr( $file['path'] ); ?>"
                                                                data-file-name="<?php echo esc_attr( $file['name'] ); ?>"
                                                                data-file-type="<?php echo esc_attr( $file['type'] ); ?>"><?php esc_html_e( '削除', 'bf-basic-guard' ); ?></a></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else : ?>
                                                <span class="bf-icon-wrapper">
                                                    <span class="dashicons dashicons-media-default bf-file-icon" style="font-size: 16px !important; margin-right: 8px; vertical-align: middle; font-family: dashicons !important;"></span>
                                                    <span class="bf-fallback-icon" style="display: none; font-size: 16px; margin-right: 8px; vertical-align: middle;">
                                                        <?php
                                                        $emoji = '📄';
                                                        if ( $file['type_class'] === 'image-file' ) $emoji = '🖼️';
                                                        else if ( $file['type_class'] === 'document-file' ) $emoji = '📝';
                                                        else if ( $file['type_class'] === 'archive-file' ) $emoji = '📦';
                                                        echo $emoji;
                                                        ?>
                                                    </span>
                                                </span>
                                                <span class="bf-file-name row-title"><?php echo esc_html( $file['name'] ); ?></span>
                                                                                <div class="row-actions">
                                    <span class="download"><a href="#" class="download-file-link"
                                            data-file-path="<?php echo esc_attr( $file['path'] ); ?>"
                                            data-file-name="<?php echo esc_attr( $file['name'] ); ?>"><?php esc_html_e( 'ダウンロード', 'bf-basic-guard' ); ?></a> | </span>
                                    <span class="copy-url"><a href="#" class="copy-url-link"
                                            data-file-path="<?php echo esc_attr( $file['path'] ); ?>"
                                            data-file-name="<?php echo esc_attr( $file['name'] ); ?>"><?php esc_html_e( 'URLをコピー', 'bf-basic-guard' ); ?></a><?php if ( current_user_can( 'delete_posts' ) ) : ?> | <?php endif; ?></span>
                                    <?php if ( current_user_can( 'delete_posts' ) ) : ?>
                                        <span class="delete"><a href="#" class="delete-file-link"
                                                data-file-path="<?php echo esc_attr( $file['path'] ); ?>"
                                                data-file-name="<?php echo esc_attr( $file['name'] ); ?>"
                                                data-file-type="<?php echo esc_attr( $file['type'] ); ?>"><?php esc_html_e( '削除', 'bf-basic-guard' ); ?></a></span>
                                    <?php endif; ?>
                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="column-type">
                                            <?php if ( $file['type'] === 'directory' ) : ?>
                                                <?php esc_html_e( 'ディレクトリ', 'bf-basic-guard' ); ?>
                                            <?php else : ?>
                                                <?php esc_html_e( 'ファイル', 'bf-basic-guard' ); ?>
                                            <?php endif; ?>
                                        </td>
                                                                 <td class="column-size">
                             <?php echo esc_html( $file['formatted_size'] ); ?>
                         </td>
                                                                                <td class="column-modified">
                                            <?php echo esc_html( wp_date( 'Y-m-d H:i:s', $file['modified'] ) ); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <?php esc_html_e( 'ファイルまたはディレクトリが見つかりませんでした。', 'bf-basic-guard' ); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ページング（下部） -->
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php echo $pagination_html; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ローディング表示 -->
    <div id="bf-basic-guard-loading" style="display: none; text-align: center; margin: 20px;">
        <span class="spinner is-active"></span>
        <span><?php esc_html_e( '読み込み中...', 'bf-basic-guard' ); ?></span>
    </div>

</div>

<!-- ディレクトリパスワード設定モーダル -->
<div id="bf-directory-password-modal" class="bf-modal" style="display: none;">
    <div class="bf-modal-content" style="width: 60%; max-width: 600px;">
        <div class="bf-modal-header">
            <h3 id="bf-password-modal-title"><?php esc_html_e( 'ディレクトリパスワード設定', 'bf-basic-guard' ); ?></h3>
            <span class="bf-modal-close">&times;</span>
        </div>
        <div class="bf-modal-body">
            <!-- 現在の状態表示 -->
            <div id="bf-current-status" class="bf-status-box">
                <div class="bf-status-content">
                    <span class="bf-status-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </span>
                    <div class="bf-status-text">
                        <strong id="bf-status-title"><?php esc_html_e( '現在の状態', 'bf-basic-guard' ); ?></strong>
                        <p id="bf-status-description"><?php esc_html_e( 'このディレクトリはパスワード保護されていません。', 'bf-basic-guard' ); ?></p>
                    </div>
                </div>
            </div>

            <p id="bf-password-modal-description">
                <?php esc_html_e( 'このディレクトリ内のファイルをダウンロードする際に要求するパスワードを設定してください。', 'bf-basic-guard' ); ?>
            </p>

            <div class="bf-password-form">
                <label for="bf-directory-password-input"><?php esc_html_e( 'パスワード:', 'bf-basic-guard' ); ?></label>
                <div class="bf-password-input-group">
                    <input type="password" id="bf-directory-password-input" class="regular-text"
                           placeholder="<?php esc_attr_e( 'パスワードを入力', 'bf-basic-guard' ); ?>" />
                    <button type="button" id="bf-password-toggle" class="button">
                        <?php esc_html_e( '表示', 'bf-basic-guard' ); ?>
                    </button>
                    <button type="button" id="bf-show-current-password" class="button" style="display: none;">
                        <?php esc_html_e( '現在のパスワード', 'bf-basic-guard' ); ?>
                    </button>
                </div>
                <p class="description">
                    <?php esc_html_e( '安全性のため、8文字以上の複雑なパスワードを設定することをお勧めします。', 'bf-basic-guard' ); ?>
                </p>
            </div>
        </div>
        <div class="bf-modal-footer">
            <div class="bf-action-buttons-left">
                <button type="button" id="bf-remove-password" class="button button-secondary bf-danger-button" style="display: none;">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e( 'パスワード保護を解除', 'bf-basic-guard' ); ?>
                </button>
            </div>
            <div class="bf-action-buttons-right">
                <button type="button" id="bf-save-password" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e( '保存', 'bf-basic-guard' ); ?>
                </button>
                <button type="button" id="bf-cancel-password" class="button">
                    <?php esc_html_e( 'キャンセル', 'bf-basic-guard' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.bf-basic-guard-path {
    background: #f1f1f1;
    padding: 10px;
    margin: 10px 0;
    border-left: 4px solid #0073aa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.bf-path-info {
    flex: 1;
    min-width: 200px;
}

.bf-path-actions {
    flex-shrink: 0;
    display: flex;
    gap: 8px;
    align-items: center;
}

.bf-path-actions .button {
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

.bf-path-actions .dashicons {
    font-size: 16px;
}

/* レスポンシブ対応 */
@media (max-width: 600px) {
    .bf-basic-guard-path {
        flex-direction: column;
        align-items: stretch;
    }

    .bf-path-info {
        min-width: auto;
        margin-bottom: 10px;
    }

    .bf-path-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
}

/* ソート機能のスタイル */
.sortable {
    cursor: pointer;
}

.sortable a.sort-link {
    text-decoration: none;
    color: inherit;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.sortable a.sort-link:hover {
    color: #0073aa;
}

.sorting-indicator {
    font-size: 10px;
    opacity: 0.5;
    line-height: 1;
    margin-left: 6px;
}

.sorting-indicator:before {
    content: "▲▼";
    font-size: 8px;
}

.sortable.sorted.asc .sorting-indicator:before {
    content: "▲";
    opacity: 1;
    font-size: 10px;
}

.sortable.sorted.desc .sorting-indicator:before {
    content: "▼";
    opacity: 1;
    font-size: 10px;
}

.bf-basic-guard-stats {
    margin: 15px 0;
}

.clickable-directory:hover {
    background-color: #f0f8ff !important;
}

.bf-basic-guard-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

/* WordPress形式の行アクション */
.has-row-actions .row-actions {
    visibility: hidden;
    color: #ddd;
    margin-top: 8px;
    font-size: 13px;
}

.has-row-actions:hover .row-actions {
    visibility: visible;
    color: #000;
}

.row-actions a {
    color: #0073aa;
    text-decoration: none;
}

.row-actions a:hover {
    color: #005177;
}

.row-actions .delete a {
    color: #d63638;
}

.row-actions .delete a:hover {
    color: #a00;
}

.row-actions .copy-url a {
    color: #0073aa;
}

.row-actions .copy-url a:hover {
    color: #005177;
}

.row-title {
    font-weight: 600;
}

/* ディレクトリクリックの無効化（行アクションから操作） */
.has-row-actions.clickable-directory {
    cursor: default;
}

.has-row-actions.clickable-directory:hover {
    background-color: inherit;
}

.bf-actions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

.bf-actions-header h3 {
    margin: 0;
    color: #333;
}

.bf-action-buttons {
    display: flex;
    gap: 10px;
}

.bf-action-buttons .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.bf-create-directory-form {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin: 15px 0;
}

.bf-create-directory-form .form-group {
    margin-bottom: 10px;
}

.bf-create-directory-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.bf-create-directory-form .form-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.bf-create-directory-form .description {
    margin: 10px 0 0 0;
    font-style: italic;
    color: #666;
}

.bf-basic-guard-drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    margin: 20px 0;
    background: #fafafa;
    position: relative;
    transition: all 0.3s ease;
}

.bf-basic-guard-drop-zone:hover {
    border-color: #0073aa;
    background: #f0f8ff;
}

.bf-basic-guard-drop-zone.dragover {
    border-color: #0073aa;
    background: #e6f3ff;
    transform: scale(1.02);
}

.bf-basic-guard-drop-zone .dashicons-upload {
    font-size: 48px !important;
    color: #0073aa !important;
    display: block !important;
    margin: 0 auto 20px !important;
    text-align: center !important;
    width: 100% !important;
    line-height: 1 !important;
    font-family: dashicons !important;
}

.drop-zone-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.drop-zone-content p {
    margin: 10px 0;
    color: #666;
    text-align: center;
}

.drop-zone-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 115, 170, 0.9);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 18px;
    font-weight: bold;
}

.bf-basic-guard-file-table .dashicons {
    margin-right: 8px !important;
    vertical-align: middle !important;
    font-size: 18px !important;
    font-family: dashicons !important;
    display: inline-block !important;
    width: auto !important;
    height: auto !important;
    line-height: 1 !important;
    text-decoration: none !important;
    text-align: center !important;
    speak: none !important;
    font-weight: normal !important;
    font-variant: normal !important;
    text-transform: none !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

/* ディレクトリアイコンのスタイル */
.bf-directory-icon {
    font-size: 20px !important;
}

.bf-directory-icon:before {
    content: "\f318" !important;
}

.bf-directory-name {
    font-weight: bold !important;
}

.bf-directory-name-disabled {
    color: #999 !important;
}

.bf-access-denied {
    color: #999 !important;
    font-style: italic !important;
}

/* ファイルアイコンのスタイル */
.bf-file-icon {
    font-size: 16px !important;
}

.bf-file-icon:before {
    content: "\f123" !important;
}

/* ホバー効果 */
.clickable-directory:hover .bf-directory-icon {
    transform: scale(1.1) !important;
    transition: all 0.2s ease !important;
}

.tablenav-pages {
    float: right;
}

.tablenav-pages .pagination-links {
    font-size: 13px;
    line-height: 1.8;
}

.tablenav-pages a,
.tablenav-pages span.current {
    display: inline-block;
    padding: 3px 5px;
    margin-right: 5px;
    text-decoration: none;
    border: 1px solid #ddd;
    background: #f7f7f7;
}

.tablenav-pages a:hover {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.tablenav-pages span.current {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
    font-weight: bold;
}

/* パスワード関連のスタイル */
.bf-password-indicator {
    margin-left: 10px;
    color: #0073aa;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    background-color: rgba(0, 115, 170, 0.1);
    border-radius: 4px;
    border: 1px solid rgba(0, 115, 170, 0.3);
}

.bf-password-indicator .dashicons {
    font-size: 16px;
}

.bf-password-status-text {
    font-size: 12px;
    font-weight: 600;
}

/* モーダルスタイル */
.bf-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.bf-modal-content {
    background-color: #fff;
    margin: 5% auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    animation: bf-modal-appear 0.3s;
}

@keyframes bf-modal-appear {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.bf-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
    background-color: #f9f9f9;
    border-radius: 8px 8px 0 0;
}

.bf-modal-header h3 {
    margin: 0;
    color: #333;
}

.bf-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.bf-modal-close:hover,
.bf-modal-close:focus {
    color: #000;
    text-decoration: none;
}

.bf-modal-body {
    padding: 20px;
}

.bf-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    background-color: #f9f9f9;
    border-radius: 0 0 8px 8px;
}

.bf-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bf-action-buttons-left {
    flex: 1;
}

.bf-action-buttons-right {
    display: flex;
    gap: 10px;
}

.bf-danger-button {
    color: #d63638 !important;
    border-color: #d63638 !important;
}

.bf-danger-button:hover {
    background-color: #d63638 !important;
    color: #fff !important;
}

.bf-status-box {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.bf-status-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bf-status-icon {
    flex-shrink: 0;
}

.bf-status-icon .dashicons {
    font-size: 24px;
    color: #0073aa;
}

.bf-status-text {
    flex: 1;
}

.bf-status-text strong {
    display: block;
    margin-bottom: 4px;
    color: #333;
}

.bf-status-text p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.bf-password-form {
    margin: 20px 0;
}

.bf-password-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.bf-password-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.bf-password-input-group input {
    flex: 1;
}

.bf-password-input-group button {
    flex-shrink: 0;
}

#bf-show-current-password {
    background-color: #f0f8ff;
    border-color: #0073aa;
    color: #0073aa;
}

#bf-show-current-password:hover {
    background-color: #0073aa;
    color: #fff;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('ページ読み込み完了');
    console.log('フォールバックアイコンの数:', $('.bf-fallback-icon').length);

    // Dashiconsが読み込まれているかチェック
    checkDashicons();

    // ディレクトリクリック時の処理
    $('.clickable-directory').on('click', function(e) {
        e.preventDefault();
        var path = $(this).data('path');
        if (path) {
            navigateToDirectory(path, 1);
        }
    });

    // ディレクトリパスワード設定ボタンのクリック処理
    $('#directory-password-btn').on('click', function(e) {
        e.preventDefault();
        openDirectoryPasswordModal();
    });

    // モーダル関連イベント
    $('.bf-modal-close, #bf-cancel-password').on('click', function() {
        closeDirectoryPasswordModal();
    });

    // モーダル外クリックで閉じる
    $('#bf-directory-password-modal').on('click', function(e) {
        if (e.target === this) {
            closeDirectoryPasswordModal();
        }
    });

    // パスワード表示/非表示切り替え
    $('#bf-password-toggle').on('click', function() {
        var passwordField = $('#bf-directory-password-input');
        var button = $(this);

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            button.text('<?php esc_html_e( '非表示', 'bf-basic-guard' ); ?>');
        } else {
            passwordField.attr('type', 'password');
            button.text('<?php esc_html_e( '表示', 'bf-basic-guard' ); ?>');
        }
    });

    // パスワード保存ボタン
    $('#bf-save-password').on('click', function() {
        saveDirectoryPassword();
    });

    // パスワード削除ボタン
    $('#bf-remove-password').on('click', function() {
        removeDirectoryPassword();
    });

    // 現在のパスワード表示ボタン
    $('#bf-show-current-password').on('click', function() {
        showCurrentPassword();
    });

    // Enterキーでパスワード保存
    $('#bf-directory-password-input').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            saveDirectoryPassword();
        }
    });

    // 上の階層へボタンのクリック処理
    $('#go-up-btn').on('click', function(e) {
        e.preventDefault();
        var currentPath = $('#current-path').val();
        if (currentPath) {
            var parentPath = getParentPath(currentPath);
            navigateToDirectory(parentPath, 1);
        }
    });

    // ソートリンクのクリック処理
    $(document).on('click', '.sort-link', function(e) {
        e.preventDefault();
        var sortBy = $(this).data('sort');
        var currentPath = $('#current-path').val();
        var currentSortBy = getCurrentSortBy();
        var currentSortOrder = getCurrentSortOrder();

        // 同じカラムをクリックした場合は順序を逆転
        var newSortOrder = 'asc';
        if (sortBy === currentSortBy && currentSortOrder === 'asc') {
            newSortOrder = 'desc';
        }

        navigateToDirectoryWithSort(currentPath, 1, sortBy, newSortOrder);
    });

    // ページングリンクのクリック処理
    $(document).on('click', '.pagination-links a', function(e) {
        e.preventDefault();
        var url = new URL(this.href);
        var page = url.searchParams.get('paged') || 1;
        var path = url.searchParams.get('path') || $('#current-path').val();
        navigateToDirectory(path, page);
    });

    // ディレクトリ作成ボタンのクリック処理
    $('#create-directory-btn').on('click', function(e) {
        e.preventDefault();
        $('#create-directory-form').slideDown();
        $('#directory-name-input').focus();
    });

    // ディレクトリ作成フォームのキャンセル
    $('#create-directory-cancel').on('click', function(e) {
        e.preventDefault();
        $('#create-directory-form').slideUp();
        $('#directory-name-input').val('');
    });

    // ディレクトリ作成の実行
    $('#create-directory-submit').on('click', function(e) {
        e.preventDefault();
        createDirectory();
    });

    // Enterキーでディレクトリ作成
    $('#directory-name-input').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            createDirectory();
        }
    });

    // 削除リンクのイベント（マウスオーバーメニューから）
    $(document).on('click', '.delete-file-link', function(e) {
        e.preventDefault();
        var $link = $(this);
        var filePath = $link.data('file-path');
        var fileName = $link.data('file-name');
        var fileType = $link.data('file-type');

        deleteFile(filePath, fileName, fileType);
    });

    // ダウンロードリンクのイベント
    $(document).on('click', '.download-file-link', function(e) {
        e.preventDefault();
        var $link = $(this);
        var filePath = $link.data('file-path');
        var fileName = $link.data('file-name');

        downloadFile(filePath, fileName);
    });

    // URLコピーリンクのイベント
    $(document).on('click', '.copy-url-link', function(e) {
        e.preventDefault();
        var $link = $(this);
        var filePath = $link.data('file-path');
        var fileName = $link.data('file-name');

        copyUrlToClipboard(filePath, fileName);
    });

        // ディレクトリを開くリンクのイベント
    $(document).on('click', '.open-directory', function(e) {
        e.preventDefault();
        var $link = $(this);
        var path = $link.data('path');

        if (path) {
            navigateToDirectory(path, 1);
        }
    });

    // 全選択チェックボックスのイベント
    $(document).on('change', '#cb-select-all-1', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="file_paths[]"]').prop('checked', isChecked);
    });

    // 個別チェックボックスのイベント
    $(document).on('change', 'input[name="file_paths[]"]', function() {
        var totalCheckboxes = $('input[name="file_paths[]"]').length;
        var checkedCheckboxes = $('input[name="file_paths[]"]:checked').length;

        // 全てチェックされている場合、全選択チェックボックスもチェック
        $('#cb-select-all-1').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // 一括操作ボタンのイベント
    $(document).on('click', '#doaction', function(e) {
        e.preventDefault();

        var action = $('#bulk-action-selector-top').val();
        if (action === '-1') {
            alert('<?php esc_html_e( '操作を選択してください。', 'bf-basic-guard' ); ?>');
            return;
        }

        var checkedFiles = $('input[name="file_paths[]"]:checked');
        if (checkedFiles.length === 0) {
            alert('<?php esc_html_e( '削除するアイテムを選択してください。', 'bf-basic-guard' ); ?>');
            return;
        }

        if (action === 'delete') {
            bulkDeleteFiles();
        }
    });

    // ファイル選択ボタンのクリック処理
    $('#select-files-btn').on('click', function(e) {
        e.preventDefault();
        $('#file-input').click();
    });

    // ファイル選択時の処理
    $('#file-input').on('change', function(e) {
        var files = e.target.files;
        if (files.length > 0) {
            uploadFiles(files);
        }
    });

    // ドラッグアンドドロップの処理
    var dropZone = $('#drop-zone');

    if (dropZone.length > 0) {
        // ドラッグエンター
        dropZone.on('dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
            $('.drop-zone-overlay').show();
        });

        // ドラッグオーバー
        dropZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });

        // ドラッグリーブ
        dropZone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var rect = this.getBoundingClientRect();
            var x = e.originalEvent.clientX;
            var y = e.originalEvent.clientY;

            // ドロップゾーンの外に出た場合のみ処理
            if (x <= rect.left || x >= rect.right || y <= rect.top || y >= rect.bottom) {
                $(this).removeClass('dragover');
                $('.drop-zone-overlay').hide();
            }
        });

        // ドロップ
        dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            $('.drop-zone-overlay').hide();

            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                uploadFiles(files);
            }
        });

        // ページ全体のデフォルトドラッグアンドドロップを無効化
        $(document).on('dragenter dragover drop', function(e) {
            e.preventDefault();
        });
    }

    function getCurrentSortBy() {
        return $('.sortable.sorted').length > 0 ?
            $('.sortable.sorted').find('.sort-link').data('sort') : 'name';
    }

    function getCurrentSortOrder() {
        if ($('.sortable.sorted.asc').length > 0) return 'asc';
        if ($('.sortable.sorted.desc').length > 0) return 'desc';
        return 'asc';
    }

    function navigateToDirectoryWithSort(path, page, sortBy, sortOrder) {
        $('#bf-basic-guard-loading').show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_browse_files',
                path: path,
                page: page,
                sort_by: sortBy,
                sort_order: sortOrder,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateFileListWithSort(response.data, sortBy, sortOrder);
                    // URLを更新（ブラウザ履歴に追加）
                    var newUrl = new URL(window.location);
                    newUrl.searchParams.set('path', path);
                    newUrl.searchParams.set('paged', page);
                    newUrl.searchParams.set('sort_by', sortBy);
                    newUrl.searchParams.set('sort_order', sortOrder);
                    window.history.pushState({path: path, page: page, sortBy: sortBy, sortOrder: sortOrder}, '', newUrl);
                } else {
                    // ディレクトリにアクセスできない場合は親ディレクトリに移動を試行
                    var errorMessage = response.data || '<?php esc_html_e( 'エラーが発生しました', 'bf-basic-guard' ); ?>';

                    if (errorMessage.indexOf('<?php esc_html_e( 'ディレクトリにアクセスできません', 'bf-basic-guard' ); ?>') !== -1 ||
                        errorMessage.indexOf('アクセスできません') !== -1) {
                        // ディレクトリアクセスエラーの場合、親ディレクトリに移動を試行
                        var parentPath = getParentPath(path);
                        if (parentPath !== path) {
                            console.log('ディレクトリアクセスエラー。親ディレクトリに移動します: ' + parentPath);
                            navigateToDirectoryWithSort(parentPath, 1, sortBy, sortOrder);
                            return;
                        }
                    }

                    alert(errorMessage);
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                $('#bf-basic-guard-loading').hide();
            }
        });
    }

    function navigateToDirectory(path, page) {
        var currentSortBy = getCurrentSortBy();
        var currentSortOrder = getCurrentSortOrder();
        navigateToDirectoryWithSort(path, page, currentSortBy, currentSortOrder);
    }

    function navigateToDirectoryOld(path, page) {
        $('#bf-basic-guard-loading').show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_browse_files',
                path: path,
                page: page,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateFileList(response.data);
                    // URLを更新（ブラウザ履歴に追加）
                    var newUrl = new URL(window.location);
                    newUrl.searchParams.set('path', path);
                    newUrl.searchParams.set('paged', page);
                    window.history.pushState({path: path, page: page}, '', newUrl);
                } else {
                    alert(response.data || '<?php esc_html_e( 'エラーが発生しました', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                $('#bf-basic-guard-loading').hide();
            }
        });
    }

        function updateFileListWithSort(data, sortBy, sortOrder) {
        // ソート状態を更新
        $('.sortable').removeClass('sorted asc desc');
        $('.sortable').each(function() {
            var linkSortBy = $(this).find('.sort-link').data('sort');
            if (linkSortBy === sortBy) {
                $(this).addClass('sorted ' + sortOrder);
            }
        });

        updateFileList(data);
    }

        function updateFileList(data) {
        // 現在のパス更新
        $('#current-path').val(data.current_path);
        $('#current-path-display').text(data.current_path || '<?php esc_html_e( "ルートディレクトリ", "bf-basic-guard" ); ?>');

        // パスワードインジケーターの更新
        var hasPassword = data.current_directory_has_password || false;
        updatePasswordIndicator(hasPassword);

        // パス表示エリア全体を再構築
        var pathHtml = '<div class="bf-path-info">' +
            '<strong><?php esc_html_e( '現在のディレクトリ:', 'bf-basic-guard' ); ?></strong>' +
            '<code id="current-path-display">' + (data.current_path || '<?php esc_html_e( "ルートディレクトリ", "bf-basic-guard" ); ?>') + '</code>' +
            '<input type="hidden" id="current-path" value="' + (data.current_path || '') + '">';

        // パスワードインジケーターを追加
        if (hasPassword) {
            pathHtml += '<span class="bf-password-indicator">' +
                '<span class="dashicons dashicons-lock"></span>' +
                '<span class="bf-password-status-text"><?php esc_html_e( 'パスワード保護中', 'bf-basic-guard' ); ?></span>' +
                '</span>';
        }

        pathHtml += '</div>' +
            '<div class="bf-path-actions">';

        // 上の階層へボタン
        if (data.current_path && data.current_path !== '') {
            pathHtml += '<button type="button" id="go-up-btn" class="button button-small">' +
                '<span class="dashicons dashicons-arrow-up-alt2"></span>' +
                '<?php esc_html_e( '上の階層へ', 'bf-basic-guard' ); ?>' +
                '</button>';
        }

                // パスワード設定ボタン（管理者権限がある場合）
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
        var passwordButtonText = hasPassword
            ? '<?php esc_html_e( 'パスワード管理', 'bf-basic-guard' ); ?>'
            : '<?php esc_html_e( 'パスワード設定', 'bf-basic-guard' ); ?>';

        pathHtml += '<button type="button" id="directory-password-btn" class="button button-small">' +
            '<span class="dashicons dashicons-admin-network"></span>' +
            passwordButtonText +
            '</button>';
        <?php endif; ?>

        pathHtml += '</div>';

        // パス表示エリアを更新
        $('.bf-basic-guard-path').html(pathHtml);

        // イベントハンドラを再設定
        $('#go-up-btn').on('click', function(e) {
            e.preventDefault();
            var currentPath = $('#current-path').val();
            if (currentPath) {
                var parentPath = getParentPath(currentPath);
                navigateToDirectory(parentPath, 1);
            }
        });

        $('#directory-password-btn').on('click', function(e) {
            e.preventDefault();
            openDirectoryPasswordModal();
        });

        // 統計情報更新
        $('.bf-basic-guard-stats p').text(
            data.total_items > 0
                ? '<?php echo esc_js( __( '%d個のアイテムが見つかりました。', 'bf-basic-guard' ) ); ?>'.replace('%d', data.total_items)
                : '<?php echo esc_js( __( 'アイテムが見つかりませんでした。', 'bf-basic-guard' ) ); ?>'
        );

        // ファイルリスト更新
        var tbody = $('#file-list-tbody');
        tbody.empty();

        if (data.items && data.items.length > 0) {
            $.each(data.items, function(index, file) {
                                var row = $('<tr></tr>')
                    .attr('data-path', file.path)
                    .attr('data-type', file.type);

                if (file.type === 'directory' && file.readable) {
                    row.addClass('clickable-directory').css('cursor', 'pointer');
                }

                // チェックボックス列
                var checkboxCell = $('<th scope="row" class="check-column"></th>');
                var checkbox = $('<input type="checkbox" name="file_paths[]">')
                    .attr('value', file.path)
                    .attr('data-file-name', file.name)
                    .attr('data-file-type', file.type);
                checkboxCell.append(checkbox);

                var nameCell = $('<td class="column-name"></td>');

                nameCell.addClass('has-row-actions');

                if (file.type === 'directory') {
                    var iconWrapper = '<span class="bf-icon-wrapper">' +
                        '<span class="dashicons dashicons-folder bf-directory-icon" style="font-size: 20px !important; margin-right: 8px; vertical-align: middle; font-family: dashicons !important;"></span>' +
                        '<span class="bf-fallback-icon" style="display: none; font-size: 18px; margin-right: 8px; vertical-align: middle;">📁</span>' +
                        '</span>';

                    var rowActions = '<div class="row-actions">';
                    if (file.readable) {
                        nameCell.html(iconWrapper + '<strong class="bf-directory-name row-title">' + $('<div>').text(file.name).html() + '</strong>');
                        rowActions += '<span class="open"><a href="#" class="open-directory" data-path="' + $('<div>').text(file.path).html() + '"><?php esc_html_e( '開く', 'bf-basic-guard' ); ?></a> | </span>';
                    } else {
                        nameCell.html(iconWrapper + '<span class="bf-directory-name-disabled row-title">' + $('<div>').text(file.name).html() + '</span>' +
                                     '<small class="bf-access-denied">(<?php esc_html_e( 'アクセス不可', 'bf-basic-guard' ); ?>)</small>');
                    }

                    <?php if ( current_user_can( 'delete_posts' ) ) : ?>
                    rowActions += '<span class="delete"><a href="#" class="delete-file-link" ' +
                        'data-file-path="' + $('<div>').text(file.path).html() + '" ' +
                        'data-file-name="' + $('<div>').text(file.name).html() + '" ' +
                        'data-file-type="' + $('<div>').text(file.type).html() + '"><?php esc_html_e( '削除', 'bf-basic-guard' ); ?></a></span>';
                    <?php endif; ?>
                    rowActions += '</div>';

                    nameCell.append(rowActions);
                } else {
                    // サーバーから送られてくるtype_classを使用
                    var iconClass = file.type_class || '';
                    var fallbackEmoji = '📄';

                    if (iconClass === 'image-file') {
                        fallbackEmoji = '🖼️';
                    } else if (iconClass === 'document-file') {
                        fallbackEmoji = '📝';
                    } else if (iconClass === 'archive-file') {
                        fallbackEmoji = '📦';
                    }

                    var iconWrapper = '<span class="bf-icon-wrapper">' +
                        '<span class="dashicons dashicons-media-default bf-file-icon" style="font-size: 16px !important; margin-right: 8px; vertical-align: middle; font-family: dashicons !important;"></span>' +
                        '<span class="bf-fallback-icon" style="display: none; font-size: 16px; margin-right: 8px; vertical-align: middle;">' + fallbackEmoji + '</span>' +
                        '</span>';
                    nameCell.html(iconWrapper + '<span class="bf-file-name row-title">' + $('<div>').text(file.name).html() + '</span>');

                    var rowActions = '<div class="row-actions">';
                    rowActions += '<span class="download"><a href="#" class="download-file-link" ' +
                        'data-file-path="' + $('<div>').text(file.path).html() + '" ' +
                        'data-file-name="' + $('<div>').text(file.name).html() + '"><?php esc_html_e( 'ダウンロード', 'bf-basic-guard' ); ?></a> | </span>';
                    rowActions += '<span class="copy-url"><a href="#" class="copy-url-link" ' +
                        'data-file-path="' + $('<div>').text(file.path).html() + '" ' +
                        'data-file-name="' + $('<div>').text(file.name).html() + '"><?php esc_html_e( 'URLをコピー', 'bf-basic-guard' ); ?></a>' +
                        '<?php if ( current_user_can( 'delete_posts' ) ) : ?> | <?php endif; ?></span>';
                    <?php if ( current_user_can( 'delete_posts' ) ) : ?>
                    rowActions += '<span class="delete"><a href="#" class="delete-file-link" ' +
                        'data-file-path="' + $('<div>').text(file.path).html() + '" ' +
                        'data-file-name="' + $('<div>').text(file.name).html() + '" ' +
                        'data-file-type="' + $('<div>').text(file.type).html() + '"><?php esc_html_e( '削除', 'bf-basic-guard' ); ?></a></span>';
                    <?php endif; ?>
                    rowActions += '</div>';

                    nameCell.append(rowActions);
                }

                var typeCell = $('<td class="column-type"></td>').text(
                    file.type === 'directory'
                        ? '<?php esc_html_e( 'ディレクトリ', 'bf-basic-guard' ); ?>'
                        : '<?php esc_html_e( 'ファイル', 'bf-basic-guard' ); ?>'
                );

                var sizeCell = $('<td class="column-size"></td>').text(
                    file.size === '-' ? '-' : formatFileSize(file.size)
                );

                var modifiedCell = $('<td class="column-modified"></td>').text(
                    new Date(file.modified * 1000).toLocaleString('ja-JP')
                );

                row.append(checkboxCell, nameCell, typeCell, sizeCell, modifiedCell);
                tbody.append(row);
            });

            // ディレクトリクリックイベントを再バインド（行アクション付きのものは除外）
            $('.clickable-directory').not('.has-row-actions').on('click', function(e) {
                e.preventDefault();
                var path = $(this).data('path');
                if (path) {
                    navigateToDirectory(path, 1);
                }
            });
        } else {
            tbody.append(
                '<tr><td colspan="5" style="text-align: center; padding: 40px;">' +
                '<?php esc_html_e( 'ファイルまたはディレクトリが見つかりませんでした。', 'bf-basic-guard' ); ?>' +
                '</td></tr>'
            );
        }

        // ページング更新
        updatePagination(data);
    }

    function updatePagination(data) {
        // 既存のページング要素を削除
        $('.tablenav').remove();

        // 一括操作メニューを含む上部tablenav
        var topTablenav = '<div class="tablenav top">' +
            '<div class="alignleft actions bulkactions">' +
            '<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( '一括操作を選択', 'bf-basic-guard' ); ?></label>' +
            '<select name="action" id="bulk-action-selector-top">' +
            '<option value="-1"><?php esc_html_e( '一括操作', 'bf-basic-guard' ); ?></option>' +
            '<?php if ( current_user_can( 'delete_posts' ) ) : ?>' +
            '<option value="delete"><?php esc_html_e( '削除', 'bf-basic-guard' ); ?></option>' +
            '<?php endif; ?>' +
            '</select>' +
            '<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( '適用', 'bf-basic-guard' ); ?>">' +
            '</div>';

        if (data.total_pages > 1) {
            var pagination = generatePaginationHtml(data.current_page, data.total_pages, data.current_path);
            topTablenav += '<div class="tablenav-pages">' + pagination + '</div>';
        }

        topTablenav += '</div>';

        // テーブルの前に上部tablenavを配置
        $('.bf-basic-guard-file-table').before(topTablenav);

        // ページングがある場合は下部tablenav も追加
        if (data.total_pages > 1) {
            var pagination = generatePaginationHtml(data.current_page, data.total_pages, data.current_path);
            $('.bf-basic-guard-file-table').after('<div class="tablenav bottom"><div class="tablenav-pages">' + pagination + '</div></div>');
        }
    }

    function generatePaginationHtml(currentPage, totalPages, currentPath) {
        var html = '<span class="pagination-links">';

        // 前のページ
        if (currentPage > 1) {
            html += '<a href="?page=bf-basic-guard&path=' + encodeURIComponent(currentPath) + '&paged=' + (currentPage - 1) + '">&laquo; <?php esc_html_e( '前', 'bf-basic-guard' ); ?></a>';
        }

        // ページ番号
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        for (var i = startPage; i <= endPage; i++) {
            if (i == currentPage) {
                html += '<span class="current">' + i + '</span>';
            } else {
                html += '<a href="?page=bf-basic-guard&path=' + encodeURIComponent(currentPath) + '&paged=' + i + '">' + i + '</a>';
            }
        }

        // 次のページ
        if (currentPage < totalPages) {
            html += '<a href="?page=bf-basic-guard&path=' + encodeURIComponent(currentPath) + '&paged=' + (currentPage + 1) + '"><?php esc_html_e( '次', 'bf-basic-guard' ); ?> &raquo;</a>';
        }

        html += '</span>';
        return html;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

        function getFileIconClass(fileExtension) {
        // 画像ファイル
        var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico'];
        if (imageExtensions.includes(fileExtension)) {
            return 'image-file';
        }

        // ドキュメントファイル
        var documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'pages'];
        if (documentExtensions.includes(fileExtension)) {
            return 'document-file';
        }

        // アーカイブファイル
        var archiveExtensions = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'];
        if (archiveExtensions.includes(fileExtension)) {
            return 'archive-file';
        }

        return '';
    }

            function getParentPath(currentPath) {
        if (!currentPath || currentPath === '') {
            return '';
        }

        // パスをセパレータで分割
        var parts = currentPath.split('/').filter(function(part) {
            return part !== '';
        });

        // 最後の部分を削除
        parts.pop();

        // 親パスを再構築
        return parts.join('/');
    }

    function checkDashicons() {
        console.log('Dashiconsチェック開始');

        // Dashiconsフォントが読み込まれているかチェック
        var testElement = $('<span class="dashicons dashicons-folder" style="font-family: dashicons; position: absolute; left: -9999px;"></span>');
        $('body').append(testElement);

        // フォントが読み込まれているかチェック
        setTimeout(function() {
            var computedStyle = window.getComputedStyle(testElement[0]);
            var fontFamily = computedStyle.getPropertyValue('font-family');

            console.log('フォントファミリー:', fontFamily);

            if (fontFamily.indexOf('dashicons') !== -1) {
                console.log('Dashiconsが利用可能です - Dashiconsを表示します');
                // Dashiconsが読み込まれている場合、Dashiconsを表示してフォールバックを非表示
                $('.dashicons').css('display', 'inline-block !important').show();
                $('.bf-fallback-icon').hide();

                                // 追加のスタイル強制適用
                $('.bf-directory-icon').css({
                    'display': 'inline-block',
                    'font-family': 'dashicons',
                    'font-size': '20px',
                    'margin-right': '8px',
                    'vertical-align': 'middle'
                });

                $('.bf-file-icon').css({
                    'display': 'inline-block',
                    'font-family': 'dashicons',
                    'font-size': '16px',
                    'margin-right': '8px',
                    'vertical-align': 'middle'
                });

            } else {
                console.log('Dashiconsが利用できません。フォールバックアイコンを使用します');
                $('.dashicons').hide();
                $('.bf-fallback-icon').show();
            }

            testElement.remove();
        }, 1000);
    }

    function uploadFiles(files) {
        var currentPath = $('#current-path').val();
        // 相対パスなので空文字でもOK（ルートディレクトリ）

        var maxFileSize = <?php echo esc_js( $max_file_size_mb ?? 10 ); ?> * 1024 * 1024; // MB to bytes
        var uploadedCount = 0;
        var totalFiles = files.length;
        var errors = [];

        $('#upload-progress').show();
        updateUploadProgress(0, '<?php esc_html_e( 'アップロードを開始しています...', 'bf-basic-guard' ); ?>');

        // 各ファイルを順番にアップロード
        function uploadNextFile(index) {
            if (index >= totalFiles) {
                // 全てのアップロードが完了
                $('#upload-progress').hide();

                if (errors.length > 0) {
                    alert('<?php esc_html_e( '一部のファイルでエラーが発生しました:', 'bf-basic-guard' ); ?>\n' + errors.join('\n'));
                } else {
                    // 成功メッセージを表示
                    showSuccessMessage(uploadedCount + '<?php esc_html_e( '個のファイルをアップロードしました。', 'bf-basic-guard' ); ?>');
                }

                // ファイルリストを更新
                navigateToDirectory(currentPath, 1);
                return;
            }

            var file = files[index];
            var fileName = file.name;

            // ファイルサイズチェック
            if (file.size > maxFileSize) {
                errors.push(fileName + ': <?php esc_html_e( 'ファイルサイズが制限を超えています', 'bf-basic-guard' ); ?>');
                uploadNextFile(index + 1);
                return;
            }

            // 危険なファイル拡張子チェック
            var dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi'];
            var fileExtension = fileName.split('.').pop().toLowerCase();
            if (dangerousExtensions.includes(fileExtension)) {
                errors.push(fileName + ': <?php esc_html_e( 'セキュリティ上の理由でアップロードできません', 'bf-basic-guard' ); ?>');
                uploadNextFile(index + 1);
                return;
            }

            // FormDataを作成
            var formData = new FormData();
            formData.append('action', 'bf_basic_guard_upload_file');
            formData.append('target_path', currentPath);
            formData.append('file', file);
            formData.append('nonce', '<?php echo esc_js( $nonce ); ?>');

            // アップロード進捗を更新
            var progress = Math.round(((index + 1) / totalFiles) * 100);
            updateUploadProgress(progress, '<?php esc_html_e( 'アップロード中:', 'bf-basic-guard' ); ?> ' + fileName);

            // AJAX送信
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        uploadedCount++;
                    } else {
                        errors.push(fileName + ': ' + (response.data || '<?php esc_html_e( 'アップロードに失敗しました', 'bf-basic-guard' ); ?>'));
                    }
                    uploadNextFile(index + 1);
                },
                error: function() {
                    errors.push(fileName + ': <?php esc_html_e( '通信エラーが発生しました', 'bf-basic-guard' ); ?>');
                    uploadNextFile(index + 1);
                }
            });
        }

        // アップロード開始
        uploadNextFile(0);
    }

    function updateUploadProgress(percent, message) {
        $('.upload-progress-fill').css('width', percent + '%');
        $('#upload-status').text(message);
    }

    function showSuccessMessage(message) {
        // 成功メッセージの表示（簡易版）
        $('<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p>' + message + '</p></div>')
            .insertAfter('.bf-basic-guard-header')
            .delay(5000)
            .fadeOut();
    }

    function createDirectory() {
        var currentPath = $('#current-path').val();
        var directoryName = $('#directory-name-input').val().trim();

        // 相対パスなので空文字でもOK（ルートディレクトリ）

        if (!directoryName) {
            alert('<?php esc_html_e( 'ディレクトリ名を入力してください。', 'bf-basic-guard' ); ?>');
            $('#directory-name-input').focus();
            return;
        }

        // ディレクトリ名のバリデーション
        var validPattern = /^[a-zA-Z0-9_\-\.]+$/;
        if (!validPattern.test(directoryName)) {
            alert('<?php esc_html_e( 'ディレクトリ名に使用できない文字が含まれています。英数字、アンダーバー、ハイフン、ドットのみ使用できます。', 'bf-basic-guard' ); ?>');
            $('#directory-name-input').focus();
            return;
        }

        // ドットで始まるディレクトリ名をチェック
        if (directoryName.charAt(0) === '.') {
            alert('<?php esc_html_e( 'ドットで始まるディレクトリ名は作成できません。', 'bf-basic-guard' ); ?>');
            $('#directory-name-input').focus();
            return;
        }

        // ボタンを無効化
        $('#create-directory-submit').prop('disabled', true).text('<?php esc_html_e( '作成中...', 'bf-basic-guard' ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_create_directory',
                parent_path: currentPath,
                directory_name: directoryName,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    $('#create-directory-form').slideUp();
                    $('#directory-name-input').val('');

                    // ファイルリストを更新
                    navigateToDirectory(currentPath, 1);
                } else {
                    alert(response.data || '<?php esc_html_e( 'ディレクトリの作成に失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                // ボタンを有効化
                $('#create-directory-submit').prop('disabled', false).text('<?php esc_html_e( '作成', 'bf-basic-guard' ); ?>');
            }
        });
    }

    function downloadFile(filePath, fileName) {
        if (!filePath) {
            alert('<?php esc_html_e( 'ファイルパスが無効です。', 'bf-basic-guard' ); ?>');
            return;
        }

        // ダウンロード処理開始のメッセージ
        showSuccessMessage('<?php esc_html_e( 'ダウンロードを準備しています...', 'bf-basic-guard' ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_download_file',
                file_path: filePath,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success && response.data.download_url) {
                    // ダウンロード用の非表示リンクを作成してクリック
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename || fileName;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    showSuccessMessage('<?php esc_html_e( 'ダウンロードを開始しました。', 'bf-basic-guard' ); ?>');
                } else {
                    alert(response.data || '<?php esc_html_e( 'ダウンロードに失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            }
        });
    }

        function deleteFile(filePath, fileName, fileType) {
        var confirmMessage = fileType === 'directory'
            ? '<?php esc_html_e( 'ディレクトリ「%s」とその中身すべてを削除しますか？この操作は取り消せません。', 'bf-basic-guard' ); ?>'
            : '<?php esc_html_e( 'ファイル「%s」を削除しますか？この操作は取り消せません。', 'bf-basic-guard' ); ?>';

        if (!confirm(confirmMessage.replace('%s', fileName))) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_delete_file',
                file_path: filePath,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);

                    // 削除後の適切なディレクトリに移動
                    var currentPath = $('#current-path').val();
                    var targetPath = currentPath;
                    var deletedPath = response.data.deleted_path;

                    // 削除されたのがディレクトリの場合、現在のパスが削除されたディレクトリ内にあるかチェック
                    if (fileType === 'directory') {
                        // 削除されたディレクトリパスと現在のパスを比較
                        if (currentPath === deletedPath ||
                            (currentPath && deletedPath && currentPath.indexOf(deletedPath + '/') === 0)) {
                            // 現在のパスが削除されたディレクトリまたはそのサブディレクトリの場合、
                            // サーバーから返された親パスに移動
                            targetPath = response.data.parent_path || '';
                            console.log('削除されたディレクトリ内にいたため、親ディレクトリに移動: ' + targetPath);
                        }
                    }

                    // ファイルリストを更新
                    navigateToDirectory(targetPath, 1);
                } else {
                    alert(response.data || '<?php esc_html_e( '削除に失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            }
        });
    }

    function bulkDeleteFiles() {
        var checkedFiles = $('input[name="file_paths[]"]:checked');
        var filePaths = [];
        var fileNames = [];
        var hasDirectories = false;

        checkedFiles.each(function() {
            filePaths.push($(this).val());
            fileNames.push($(this).data('file-name'));
            if ($(this).data('file-type') === 'directory') {
                hasDirectories = true;
            }
        });

        // 確認メッセージ
        var confirmMessage;
        if (hasDirectories) {
            confirmMessage = '<?php esc_html_e( '選択された%d個のアイテム（ディレクトリを含む）とその中身すべてを削除しますか？この操作は取り消せません。', 'bf-basic-guard' ); ?>';
        } else {
            confirmMessage = '<?php esc_html_e( '選択された%d個のアイテムを削除しますか？この操作は取り消せません。', 'bf-basic-guard' ); ?>';
        }

        if (!confirm(confirmMessage.replace('%d', filePaths.length))) {
            return;
        }

        // 一括削除のボタンを無効化
        $('#doaction').prop('disabled', true).val('<?php esc_html_e( '削除中...', 'bf-basic-guard' ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_bulk_delete',
                file_paths: filePaths,
                current_path: $('#current-path').val(),
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);

                    // 削除結果の詳細表示（失敗があった場合）
                    if (response.data.failed_count > 0) {
                        console.log('削除に失敗したファイル:', response.data.failed_files);
                    }

                    // 現在のパスが削除された場合の処理
                    var targetPath = $('#current-path').val();
                    if (response.data.current_path_deleted && response.data.redirect_path !== undefined) {
                        targetPath = response.data.redirect_path;
                        console.log('現在のディレクトリが削除されたため、親ディレクトリに移動: ' + targetPath);
                    }

                    // ファイルリストを更新
                    navigateToDirectory(targetPath, 1);
                } else {
                    alert(response.data || '<?php esc_html_e( '一括削除に失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                // ボタンを有効化
                $('#doaction').prop('disabled', false).val('<?php esc_attr_e( '適用', 'bf-basic-guard' ); ?>');

                // チェックボックスをクリア
                $('input[name="file_paths[]"]').prop('checked', false);
                $('#cb-select-all-1').prop('checked', false);
            }
        });
    }

        // ディレクトリパスワードモーダルを開く
    function openDirectoryPasswordModal() {
        var currentPath = $('#current-path').val();
        var currentPathDisplay = $('#current-path-display').text();
        var hasPassword = checkCurrentDirectoryHasPassword();

        // モーダルタイトルの更新
        if (hasPassword) {
            $('#bf-password-modal-title').text('<?php esc_html_e( 'ディレクトリパスワード管理', 'bf-basic-guard' ); ?>');
        } else {
            $('#bf-password-modal-title').text('<?php esc_html_e( 'ディレクトリパスワード設定', 'bf-basic-guard' ); ?>');
        }

        // 現在の状態表示を更新
        var statusIcon = $('.bf-status-icon .dashicons');
        var statusDescription = $('#bf-status-description');

        if (hasPassword) {
            statusIcon.removeClass('dashicons-unlock').addClass('dashicons-lock');
            statusIcon.css('color', '#d63638');
            statusDescription.html('<?php esc_html_e( 'このディレクトリ（', 'bf-basic-guard' ); ?><code>' + currentPathDisplay + '</code><?php esc_html_e( '）は現在パスワード保護されています。', 'bf-basic-guard' ); ?>');
            $('#bf-password-modal-description').text('<?php esc_html_e( '新しいパスワードを入力して変更するか、下の「パスワード保護を解除」ボタンで保護を解除できます。', 'bf-basic-guard' ); ?>');
            $('#bf-remove-password').show();
            $('#bf-show-current-password').show();
        } else {
            statusIcon.removeClass('dashicons-lock').addClass('dashicons-unlock');
            statusIcon.css('color', '#46b450');
            statusDescription.html('<?php esc_html_e( 'このディレクトリ（', 'bf-basic-guard' ); ?><code>' + currentPathDisplay + '</code><?php esc_html_e( '）はパスワード保護されていません。', 'bf-basic-guard' ); ?>');
            $('#bf-password-modal-description').text('<?php esc_html_e( 'このディレクトリ内のファイルをダウンロードする際に要求するパスワードを設定してください。', 'bf-basic-guard' ); ?>');
            $('#bf-remove-password').hide();
            $('#bf-show-current-password').hide();
        }

        // パスワードフィールドをクリア
        $('#bf-directory-password-input').val('').attr('type', 'password');
        $('#bf-password-toggle').text('<?php esc_html_e( '表示', 'bf-basic-guard' ); ?>');

        // モーダルを表示
        $('#bf-directory-password-modal').fadeIn(300);
        $('#bf-directory-password-input').focus();
    }

    // ディレクトリパスワードモーダルを閉じる
    function closeDirectoryPasswordModal() {
        $('#bf-directory-password-modal').fadeOut(300);
    }

    // 現在のディレクトリにパスワードが設定されているかチェック
    function checkCurrentDirectoryHasPassword() {
        return $('.bf-password-indicator').length > 0;
    }

    // ディレクトリパスワードを保存
    function saveDirectoryPassword() {
        var currentPath = $('#current-path').val();
        var password = $('#bf-directory-password-input').val().trim();

        if (!password) {
            alert('<?php esc_html_e( 'パスワードを入力してください。', 'bf-basic-guard' ); ?>');
            $('#bf-directory-password-input').focus();
            return;
        }

        if (password.length < 4) {
            alert('<?php esc_html_e( 'パスワードは4文字以上で入力してください。', 'bf-basic-guard' ); ?>');
            $('#bf-directory-password-input').focus();
            return;
        }

        // ボタンを無効化
        $('#bf-save-password').prop('disabled', true).text('<?php esc_html_e( '保存中...', 'bf-basic-guard' ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_set_directory_password',
                path: currentPath,
                password: password,
                action_type: 'set',
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    closeDirectoryPasswordModal();
                    updatePasswordIndicator(response.data.has_password);
                } else {
                    alert(response.data || '<?php esc_html_e( 'パスワードの設定に失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                // ボタンを有効化
                $('#bf-save-password').prop('disabled', false).text('<?php esc_html_e( '保存', 'bf-basic-guard' ); ?>');
            }
        });
    }

    // ディレクトリパスワードを削除
    function removeDirectoryPassword() {
        if (!confirm('<?php esc_html_e( 'このディレクトリのパスワード保護を解除しますか？', 'bf-basic-guard' ); ?>')) {
            return;
        }

        var currentPath = $('#current-path').val();

        // ボタンを無効化
        $('#bf-remove-password').prop('disabled', true).text('<?php esc_html_e( '削除中...', 'bf-basic-guard' ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_set_directory_password',
                path: currentPath,
                action_type: 'remove',
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    closeDirectoryPasswordModal();
                    updatePasswordIndicator(response.data.has_password);
                } else {
                    alert(response.data || '<?php esc_html_e( 'パスワードの削除に失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                // ボタンを有効化
                $('#bf-remove-password').prop('disabled', false).text('<?php esc_html_e( 'パスワードを削除', 'bf-basic-guard' ); ?>');
            }
        });
    }

            // パスワードインジケーターを更新
    function updatePasswordIndicator(hasPassword) {
        // この関数はupdateFileList内で呼ばれるため、
        // パス表示エリア全体の再構築で処理されるので、
        // 個別の更新は不要です。
        // ただし、モーダルでの操作後の更新用に残しておきます。
        var passwordIndicator = $('.bf-password-indicator');
        var passwordButton = $('#directory-password-btn');

        if (passwordButton.length > 0) {
            if (hasPassword) {
                if (passwordIndicator.length === 0) {
                    $('#current-path').after('<span class="bf-password-indicator">' +
                        '<span class="dashicons dashicons-lock"></span>' +
                        '<span class="bf-password-status-text"><?php esc_html_e( 'パスワード保護中', 'bf-basic-guard' ); ?></span>' +
                        '</span>');
                }
                passwordButton.html('<span class="dashicons dashicons-admin-network"></span><?php esc_html_e( 'パスワード管理', 'bf-basic-guard' ); ?>');
            } else {
                passwordIndicator.remove();
                passwordButton.html('<span class="dashicons dashicons-admin-network"></span><?php esc_html_e( 'パスワード設定', 'bf-basic-guard' ); ?>');
            }
        }
    }

    // 現在のパスワードを表示
    function showCurrentPassword() {
        var currentPath = $('#current-path').val();

        // ボタンを無効化
        $('#bf-show-current-password').prop('disabled', true).text('<?php esc_html_e( '取得中...', 'bf-basic-guard' ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_basic_guard_get_directory_password',
                path: currentPath,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_html_e( '現在のパスワード: ', 'bf-basic-guard' ); ?>' + response.data.password);
                } else {
                    alert(response.data || '<?php esc_html_e( 'パスワードの取得に失敗しました。', 'bf-basic-guard' ); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e( '通信エラーが発生しました。', 'bf-basic-guard' ); ?>');
            },
            complete: function() {
                // ボタンを有効化
                $('#bf-show-current-password').prop('disabled', false).text('<?php esc_html_e( '現在のパスワード', 'bf-basic-guard' ); ?>');
            }
        });
    }

    // URLをクリップボードにコピー
    function copyUrlToClipboard(filePath, fileName) {
        // URLを生成
        var downloadUrl = '<?php echo esc_url( home_url() ); ?>/?path=' + encodeURIComponent(filePath) + '&dflag=download';

        // モダンブラウザのClipboard APIを使用
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(downloadUrl).then(function() {
                showSuccessMessage('<?php esc_html_e( 'ダウンロードURLをクリップボードにコピーしました:', 'bf-basic-guard' ); ?> ' + downloadUrl);
            }).catch(function(err) {
                console.error('<?php esc_html_e( 'クリップボードへのコピーに失敗しました:', 'bf-basic-guard' ); ?>', err);
                copyUrlFallback(downloadUrl);
            });
        } else {
            // フォールバック（古いブラウザ用）
            copyUrlFallback(downloadUrl);
        }
    }

    // URLコピーのフォールバック（古いブラウザ用）
    function copyUrlFallback(url) {
        var textArea = document.createElement('textarea');
        textArea.value = url;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.width = '2em';
        textArea.style.height = '2em';
        textArea.style.padding = '0';
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';
        textArea.style.background = 'transparent';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showSuccessMessage('<?php esc_html_e( 'ダウンロードURLをクリップボードにコピーしました:', 'bf-basic-guard' ); ?> ' + url);
            } else {
                showUrlPrompt(url);
            }
        } catch (err) {
            console.error('<?php esc_html_e( 'クリップボードへのコピーに失敗しました:', 'bf-basic-guard' ); ?>', err);
            showUrlPrompt(url);
        }

        document.body.removeChild(textArea);
    }

    // URLを手動コピー用のプロンプトで表示
    function showUrlPrompt(url) {
        prompt('<?php esc_html_e( '以下のダウンロードURLをコピーしてください:', 'bf-basic-guard' ); ?>', url);
    }
});
</script>
<?php
/**
 * 設定ページのビューファイル
 *
 * @package BfBasicGuard
 *
 * 利用可能な変数:
 * @var bool   $enable_auth        BASIC認証有効フラグ
 * @var int    $max_file_size      最大ファイルサイズ
 * @var bool   $log_downloads      ダウンロードログ有効フラグ
 * @var string $security_level     セキュリティレベル
 * @var string $target_directory    対象ディレクトリ
 * @var array  $auth_methods       認証方法の配列
 * @var array  $allowed_roles      許可するユーザーロールの配列
 * @var string $simple_auth_password 簡易認証パスワード

 * @var string $nonce              AJAXノンス
 *
 * 利用可能な関数:
 * @var callable $__                    翻訳関数
 * @var callable $esc_html             HTMLエスケープ関数
 * @var callable $esc_html_e           HTMLエスケープ出力関数
 * @var callable $get_admin_page_title ページタイトル取得関数
 * @var callable $settings_fields      設定フィールド関数
 * @var callable $do_settings_sections 設定セクション関数
 * @var callable $submit_button        送信ボタン関数
 */

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="bf-secret-file-downloader-settings">
        <div class="bf-secret-file-downloader-header">
            <p><?php esc_html_e( 'BF Secret File Downloaderの設定を管理します。ファイルアクセスには認証が必要で、ログインユーザーまたは簡易認証パスワードでの認証が可能です。', 'bf-secret-file-downloader' ); ?></p>
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e( '認証設定について:', 'bf-secret-file-downloader' ); ?></strong>
                    <?php esc_html_e( 'このページで設定する認証は共通設定として適用されます。各ディレクトリには個別の認証設定も可能で、ディレクトリ毎設定がある場合は共通設定を上書きします。', 'bf-secret-file-downloader' ); ?>
                </p>
            </div>
        </div>

        <div class="bf-secret-file-downloader-content">
            <h2><?php esc_html_e( '基本設定', 'bf-secret-file-downloader' ); ?></h2>



            <!-- 設定フォーム -->
            <div class="bf-secret-file-downloader-settings-form">
                <form method="post" action="options.php">
                    <?php settings_fields( 'bf_sfd_settings' ); ?>
                    <?php do_settings_sections( 'bf_sfd_settings' ); ?>

                    <!-- 対象ディレクトリ設定 -->
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( '対象ディレクトリ', 'bf-secret-file-downloader' ); ?></th>
                            <td>
                                <div class="bf-directory-item">
                                    <input type="text" name="bf_sfd_target_directory"
                                           value="<?php echo esc_attr( $target_directory ?? '' ); ?>"
                                           class="regular-text bf-directory-path" readonly />
                                    <button type="button" class="button bf-browse-directory"><?php esc_html_e( '参照', 'bf-secret-file-downloader' ); ?></button>
                                </div>
                                <p class="description"><?php esc_html_e( 'プラグインで管理するディレクトリを指定してください。', 'bf-secret-file-downloader' ); ?></p>

                            </td>
                        </tr>
                    </table>



                    <!-- 認証設定 -->
                    <h3><?php esc_html_e( '認証設定', 'bf-secret-file-downloader' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( '認証方法', 'bf-secret-file-downloader' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php esc_html_e( '認証方法', 'bf-secret-file-downloader' ); ?></legend>
                                    <label>
                                        <input type="checkbox" name="bf_sfd_auth_methods[]" value="logged_in"
                                               <?php echo in_array( 'logged_in', $auth_methods ?? array() ) ? 'checked' : ''; ?> />
                                        <?php esc_html_e( 'ログインしているユーザー', 'bf-secret-file-downloader' ); ?>
                                    </label>
                                    <div id="allowed_roles_section" style="margin-top: 10px; padding: 10px; background-color: #f9f9f9; border-left: 4px solid #0073aa; <?php echo in_array( 'logged_in', $auth_methods ?? array() ) ? '' : 'display: none;'; ?>">
                                        <label for="bf_sfd_allowed_roles">
                                            <strong><?php esc_html_e( '許可するユーザーロール', 'bf-secret-file-downloader' ); ?></strong>
                                        </label>
                                        <div class="bf-role-selection-controls" style="margin: 10px 0;">
                                            <button type="button" id="bf-select-all-roles" class="button button-small"><?php esc_html_e( 'すべて選択', 'bf-secret-file-downloader' ); ?></button>
                                            <button type="button" id="bf-deselect-all-roles" class="button button-small"><?php esc_html_e( 'すべて解除', 'bf-secret-file-downloader' ); ?></button>
                                        </div>
                                        <fieldset>
                                            <legend class="screen-reader-text"><?php esc_html_e( '許可するユーザーロール', 'bf-secret-file-downloader' ); ?></legend>
                                            <?php
                                            $roles = array(
                                                'administrator' => __( '管理者', 'bf-secret-file-downloader' ),
                                                'editor' => __( '編集者', 'bf-secret-file-downloader' ),
                                                'author' => __( '投稿者', 'bf-secret-file-downloader' ),
                                                'contributor' => __( '寄稿者', 'bf-secret-file-downloader' ),
                                                'subscriber' => __( '購読者', 'bf-secret-file-downloader' )
                                            );
                                            foreach ( $roles as $role => $label ) :
                                            ?>
                                            <label>
                                                <input type="checkbox" name="bf_sfd_allowed_roles[]" value="<?php echo esc_attr( $role ); ?>" class="bf-role-checkbox"
                                                       <?php echo in_array( $role, $allowed_roles ?? array() ) ? 'checked' : ''; ?> />
                                                <?php echo esc_html( $label ); ?>
                                            </label>
                                            <br>
                                            <?php endforeach; ?>
                                        </fieldset>
                                        <p class="description" style="margin-top: 10px;"><?php esc_html_e( 'ファイルアクセスを許可するユーザーロールを選択してください。複数選択可能です。', 'bf-secret-file-downloader' ); ?></p>
                                    </div>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="bf_sfd_auth_methods[]" value="simple_auth" id="simple_auth_checkbox"
                                               <?php echo in_array( 'simple_auth', $auth_methods ?? array() ) ? 'checked' : ''; ?> />
                                        <?php esc_html_e( '簡易認証を通過したユーザー', 'bf-secret-file-downloader' ); ?>
                                    </label>
                                    <div id="simple_auth_password_section" style="margin-top: 10px; padding: 10px; background-color: #f9f9f9; border-left: 4px solid #0073aa; <?php echo in_array( 'simple_auth', $auth_methods ?? array() ) ? '' : 'display: none;'; ?>">
                                        <label for="bf_sfd_simple_auth_password">
                                            <strong><?php esc_html_e( '簡易認証パスワード', 'bf-secret-file-downloader' ); ?></strong>
                                        </label>
                                        <br>
                                        <input type="password" name="bf_sfd_simple_auth_password" id="bf_sfd_simple_auth_password"
                                               value="<?php echo esc_attr( $simple_auth_password ?? '' ); ?>"
                                               class="regular-text" style="margin-top: 5px;" />
                                        <p class="description" style="margin-top: 5px;"><?php esc_html_e( '簡易認証で使用するパスワードを設定してください。', 'bf-secret-file-downloader' ); ?></p>
                                    </div>
                                </fieldset>
                                <p class="description"><?php esc_html_e( 'ファイルアクセスを許可する認証方法を選択してください。複数選択可能です。', 'bf-secret-file-downloader' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <!-- その他の設定 -->
                    <h3><?php esc_html_e( 'その他の設定', 'bf-secret-file-downloader' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'アップロード制限', 'bf-secret-file-downloader' ); ?></th>
                            <td>
                                <input type="number" name="bf_sfd_max_file_size"
                                       value="<?php echo isset( $max_file_size ) ? esc_html( $max_file_size ) : '10'; ?>"
                                       min="1" max="100" />
                                <span><?php esc_html_e( 'MB', 'bf-secret-file-downloader' ); ?></span>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>

                <!-- 設定リセットセクション -->
                <div class="bf-reset-settings-section" style="margin-top: 30px; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <h3 style="margin-top: 0; color: #856404;"><?php esc_html_e( '設定のリセット', 'bf-secret-file-downloader' ); ?></h3>
                    <p style="margin-bottom: 15px; color: #856404;">
                        <?php esc_html_e( 'このボタンをクリックすると、すべての設定が初期状態にリセットされます。この操作は取り消すことができません。', 'bf-secret-file-downloader' ); ?>
                    </p>
                    <button type="button" id="bf-reset-settings" class="button button-secondary" style="background-color: #dc3545; border-color: #dc3545; color: white;">
                        <?php esc_html_e( '設定をリセット', 'bf-secret-file-downloader' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ディレクトリブラウザモーダル -->
<div id="bf-directory-browser-modal" class="bf-modal" style="display: none;">
    <div class="bf-modal-content">
        <div class="bf-modal-header">
                                <h3><?php esc_html_e( 'ディレクトリを選択', 'bf-secret-file-downloader' ); ?></h3>
            <span class="bf-modal-close">&times;</span>
        </div>
        <div class="bf-modal-body">
            <div class="bf-directory-navigation">
                                            <button type="button" id="bf-nav-up" class="button" disabled><?php esc_html_e( '上へ', 'bf-secret-file-downloader' ); ?></button>
                                  <button type="button" id="bf-create-directory" class="button"><?php esc_html_e( '新しいフォルダ', 'bf-secret-file-downloader' ); ?></button>
                <span id="bf-current-path"></span>
            </div>
            <div class="bf-directory-list">
                <div class="bf-loading" style="display: none;"><?php esc_html_e( '読み込み中...', 'bf-secret-file-downloader' ); ?></div>
                <ul id="bf-directory-items"></ul>
            </div>
        </div>
        <div class="bf-modal-footer">
                              <button type="button" id="bf-select-directory" class="button button-primary"><?php esc_html_e( '選択', 'bf-secret-file-downloader' ); ?></button>
                  <button type="button" id="bf-cancel-directory" class="button"><?php esc_html_e( 'キャンセル', 'bf-secret-file-downloader' ); ?></button>
        </div>
    </div>
</div>

<style>
.bf-directory-item {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bf-directory-path {
    flex: 1;
}

.bf-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.bf-modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 70%;
    max-width: 800px;
    height: 70%;
    display: flex;
    flex-direction: column;
}

.bf-modal-header {
    padding: 20px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bf-modal-header h3 {
    margin: 0;
}

.bf-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.bf-modal-close:hover,
.bf-modal-close:focus {
    color: black;
}

.bf-modal-body {
    padding: 20px;
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.bf-directory-navigation {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bf-directory-list {
    flex: 1;
    overflow-y: auto;
    border: 1px solid #ddd;
    background-color: #fff;
}

#bf-directory-items {
    margin: 0;
    padding: 0;
    list-style: none;
}

.bf-directory-item-list {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bf-directory-item-list:hover {
    background-color: #f0f0f0;
}

.bf-directory-item-list.selected {
    background-color: #0073aa;
    color: white;
}

.bf-directory-icon {
    font-size: 16px;
    width: 20px;
}

.bf-modal-footer {
    padding: 20px;
    background-color: #f1f1f1;
    border-top: 1px solid #ddd;
    text-align: right;
}

.bf-modal-footer .button {
    margin-left: 10px;
}

.bf-loading {
    text-align: center;
    padding: 20px;
    font-style: italic;
    color: #666;
}


</style>

<script>
jQuery(document).ready(function($) {
    var currentTargetInput = null;
    var currentPath = '';
    var selectedPath = '';



    // ディレクトリ参照
    $('.bf-browse-directory').on('click', function() {
        currentTargetInput = $(this).siblings('.bf-directory-path');
        selectedPath = '';
        loadDirectory('');
        $('#bf-directory-browser-modal').show();
    });

    // モーダル閉じる
    $('.bf-modal-close, #bf-cancel-directory').on('click', function() {
        $('#bf-directory-browser-modal').hide();
    });

        // ディレクトリ選択
    $('#bf-select-directory').on('click', function() {
        if (selectedPath && currentTargetInput) {
            // パスを正規化
            var normalizedPath = normalizePath(selectedPath);

            // 選択禁止ディレクトリのチェック
            if (isRestrictedDirectory(normalizedPath)) {
                alert('<?php esc_html_e( "このディレクトリは選択できません。", "bf-secret-file-downloader" ); ?>');
                return;
            }
            currentTargetInput.val(normalizedPath);
        }
        $('#bf-directory-browser-modal').hide();
    });

    // 上のディレクトリへ
    $('#bf-nav-up').on('click', function() {
        if (currentPath) {
            // サーバーから受け取った親パスを使用
            var parentPath = $('#bf-nav-up').data('parent-path');
            if (parentPath && parentPath !== currentPath) {
                // 現在のパスをフォールバックとして渡す
                loadDirectory(parentPath, currentPath);
            }
        }
    });

    // 新しいディレクトリ作成
    $('#bf-create-directory').on('click', function() {
        if (currentPath) {
            var directoryName = prompt('<?php esc_html_e( "新しいフォルダ名を入力してください：", "bf-secret-file-downloader" ); ?>');
            if (directoryName && directoryName.trim()) {
                createDirectory(currentPath, directoryName.trim());
            }
        }
    });

    // ディレクトリ項目クリック
    $(document).on('click', '.bf-directory-item-list', function() {
        $('.bf-directory-item-list').removeClass('selected');
        $(this).addClass('selected');
        selectedPath = $(this).data('path');

        if ($(this).data('type') === 'directory') {
            $('#bf-select-directory').prop('disabled', false);
        }
    });

    // ディレクトリ項目ダブルクリック
    $(document).on('dblclick', '.bf-directory-item-list[data-type="directory"]', function() {
        var path = $(this).data('path');
        // 現在のパスをフォールバックとして渡す
        loadDirectory(path, currentPath);
    });

        // ディレクトリ読み込み関数
    function loadDirectory(path, fallbackCurrent) {
        $('.bf-loading').show();
        // エラー時に内容を維持するため、権限エラーの場合のみアイテムをクリアしない
        var shouldClearItems = !fallbackCurrent;
        if (shouldClearItems) {
            $('#bf-directory-items').empty();
        }
        $('#bf-select-directory').prop('disabled', true);

        var requestData = {
            action: 'bf_sfd_browse_directory',
            path: path,
            nonce: '<?php echo esc_js( $nonce ); ?>'
        };

        // フォールバック用の現在ディレクトリを追加
        if (fallbackCurrent) {
            requestData.fallback_current = fallbackCurrent;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                $('.bf-loading').hide();

                                if (response.success) {
                    currentPath = response.data.current_path;
                    $('#bf-current-path').text(currentPath);

                    // 警告メッセージがある場合は表示
                    if (response.data.warning) {
                        alert(response.data.warning);
                    }

                    // 上へボタンの状態更新
                    if (response.data.parent_path && response.data.parent_path !== currentPath) {
                        $('#bf-nav-up').prop('disabled', false).data('parent-path', response.data.parent_path);
                    } else {
                        $('#bf-nav-up').prop('disabled', true).removeData('parent-path');
                    }

                    // 成功時は常にアイテムリストをクリアして再表示
                    $('#bf-directory-items').empty();

                    // アイテム表示
                    response.data.items.forEach(function(item) {
                        var icon = item.type === 'directory' ? '📁' : '📄';
                        var listItem = $('<li class="bf-directory-item-list" data-path="' + item.path + '" data-type="' + item.type + '">' +
                            '<span class="bf-directory-icon">' + icon + '</span>' +
                            '<span>' + item.name + '</span>' +
                            '</li>');
                        $('#bf-directory-items').append(listItem);
                    });
                } else {
                    console.log('Directory load error:', response);
                    alert('<?php esc_html_e( "ディレクトリの読み込みに失敗しました。", "bf-secret-file-downloader" ); ?>' + (response.data ? ': ' + response.data : ''));

                    // エラー時でも現在のディレクトリ内容を維持（再読み込みしない）
                    // フォールバックが指定されている場合は、フォールバック先を再読み込み
                    if (fallbackCurrent && fallbackCurrent !== path) {
                        loadDirectory(fallbackCurrent);
                    }
                }
            },
            error: function(xhr, status, error) {
                $('.bf-loading').hide();
                console.log('AJAX error:', xhr, status, error);
                alert('<?php esc_html_e( "エラーが発生しました。", "bf-secret-file-downloader" ); ?>' + ': ' + error);

                // AJAX エラー時でも現在のディレクトリ内容を維持
                // フォールバックが指定されている場合は、フォールバック先を再読み込み
                if (fallbackCurrent && fallbackCurrent !== path) {
                    loadDirectory(fallbackCurrent);
                }
            }
        });
    }

    // ディレクトリ作成関数
    function createDirectory(parentPath, directoryName) {
        $('.bf-loading').show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bf_sfd_create_directory',
                parent_path: parentPath,
                directory_name: directoryName,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                $('.bf-loading').hide();

                if (response.success) {
                    alert(response.data.message);
                    // 現在のディレクトリを再読み込みして新しいフォルダを表示
                    loadDirectory(parentPath);
                } else {
                    console.log('Directory creation error:', response);
                    alert('<?php esc_html_e( "フォルダの作成に失敗しました。", "bf-secret-file-downloader" ); ?>' + (response.data ? ': ' + response.data : ''));
                }
            },
            error: function(xhr, status, error) {
                $('.bf-loading').hide();
                console.log('Directory creation AJAX error:', xhr, status, error);
                alert('<?php esc_html_e( "エラーが発生しました。", "bf-secret-file-downloader" ); ?>' + ': ' + error);
            }
        });
    }

    // パス正規化関数
    function normalizePath(path) {
        // 連続するスラッシュを単一スラッシュに変換
        return path.replace(/\/+/g, '/').replace(/\/+$/, '');
    }

    // 選択禁止ディレクトリのチェック関数
    function isRestrictedDirectory(path) {
        // パスの正規化
        var normalizedPath = normalizePath(path);

        // WordPressシステムディレクトリの厳密なチェック（完全一致またはサブディレクトリ）
        var wpSystemPaths = [
            '<?php echo esc_js( ABSPATH . "wp-content" ); ?>',
            '<?php echo esc_js( ABSPATH . "wp-includes" ); ?>',
            '<?php echo esc_js( ABSPATH . "wp-admin" ); ?>',
            '<?php echo esc_js( WP_CONTENT_DIR . "/themes" ); ?>',
            '<?php echo esc_js( WP_CONTENT_DIR . "/plugins" ); ?>',
            '<?php echo esc_js( WP_CONTENT_DIR . "/mu-plugins" ); ?>'
        ];

        // WordPressシステムディレクトリのチェック
        for (var i = 0; i < wpSystemPaths.length; i++) {
            var systemPath = normalizePath(wpSystemPaths[i]);
            // 完全一致またはそのサブディレクトリかチェック
            if (normalizedPath === systemPath || normalizedPath.indexOf(systemPath + '/') === 0) {
                return true;
            }
        }

        // ドキュメントルート自体のチェック（サブディレクトリは許可）
        <?php if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && ! empty( $_SERVER['DOCUMENT_ROOT'] ) ): ?>
        var documentRoot = normalizePath('<?php echo esc_js( $_SERVER['DOCUMENT_ROOT'] ); ?>');
        if (normalizedPath === documentRoot) {
            return true; // ドキュメントルート自体は選択禁止
        }
        <?php endif; ?>

        return false;
    }



    // モーダル外クリックで閉じる
    $(document).on('click', '#bf-directory-browser-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    // 簡易認証チェックボックスの制御
    $('#simple_auth_checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#simple_auth_password_section').show();
        } else {
            $('#simple_auth_password_section').hide();
        }
    });

    // ログインユーザーチェックボックスの制御
    $('input[name="bf_sfd_auth_methods[]"][value="logged_in"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('#allowed_roles_section').show();
        } else {
            $('#allowed_roles_section').hide();
        }
    });

    // ロール選択の制御
    $('#bf-select-all-roles').on('click', function() {
        $('.bf-role-checkbox').prop('checked', true);
    });

    $('#bf-deselect-all-roles').on('click', function() {
        $('.bf-role-checkbox').prop('checked', false);
    });

    // 設定リセットボタンの制御
    $('#bf-reset-settings').on('click', function() {
        if (confirm('<?php esc_html_e( "本当にすべての設定をリセットしますか？この操作は取り消すことができません。", "bf-secret-file-downloader" ); ?>')) {
            // ボタンを無効化してローディング状態に
            var $button = $(this);
            var originalText = $button.text();
            $button.prop('disabled', true).text('<?php esc_html_e( "リセット中...", "bf-secret-file-downloader" ); ?>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bf_sfd_reset_settings',
                    nonce: '<?php echo esc_js( $nonce ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // ページをリロードして設定を反映
                        location.reload();
                    } else {
                        alert('<?php esc_html_e( "設定のリセットに失敗しました。", "bf-secret-file-downloader" ); ?>');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Reset settings AJAX error:', xhr, status, error);
                    alert('<?php esc_html_e( "エラーが発生しました。", "bf-secret-file-downloader" ); ?>' + ': ' + error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    });
});
</script>
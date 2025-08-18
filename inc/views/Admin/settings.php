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

    <?php settings_errors(); ?>

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
                                    <code><?php echo esc_html( $target_directory ?: 'ディレクトリが設定されていません' ); ?></code>
                                </div>
                                <p class="description"><?php esc_html_e( 'プラグイン有効化時に自動作成されたセキュアなディレクトリです。外部からのアクセスは.htaccessにより完全に遮断されています。', 'bf-secret-file-downloader' ); ?></p>
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


<script>
jQuery(document).ready(function($) {
    // フィールドフォーカス時にエラーハイライトを解除
    $('input[type="text"], input[type="password"]').on('focus', function() {
        $(this).removeClass('form-field-error');
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
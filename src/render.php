<?php
// 危険フラグをチェック
$danger_flag_set = (bool) get_option( 'bf_sfd_directory_danger_flag', false );

if ( $danger_flag_set ) {
    // 危険フラグが設定されている場合は警告メッセージを表示
    echo '<div class="bf-sfd-danger-warning" style="background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; text-align: center;">';
    echo '<strong>' . esc_html__( 'セキュリティ警告:', 'bf-secret-file-downloader' ) . '</strong> ';
    echo esc_html__( '対象ディレクトリにWordPressファイルが検出されたため、ダウンロード機能は無効化されています。', 'bf-secret-file-downloader' );
    echo '</div>';
} else {
    echo $content;
    // JS/CSSだけを出力
    \Breadfish\SecretFileDownloader\ViewRenderer::frontend('downloader.php', ['js_only' => true]);
}
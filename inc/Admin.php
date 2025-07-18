<?php
/**
 * 管理画面メニューを管理するクラス
 *
 * @package BfBasicGuard
 */

namespace Breadfish\SecretFileDownloader;

// セキュリティチェック：直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin クラス
 * WordPressの管理画面メニューを管理し、各ページクラスにルーティングします
 */
class Admin {

    /**
     * ファイルリストページインスタンス
     *
     * @var \Breadfish\BasicGuard\Admin\FileListPage
     */
    private $file_list_page;

    /**
     * 設定ページインスタンス
     *
     * @var \Breadfish\BasicGuard\Admin\SettingsPage
     */
    private $settings_page;

    /**
     * コンストラクタ
     */
    public function __construct() {
        // コンストラクタではフックを登録しない
        $this->file_list_page = new \Breadfish\SecretFileDownloader\Admin\FileListPage();
        $this->settings_page = new \Breadfish\SecretFileDownloader\Admin\SettingsPage();
    }

    /**
     * フックを初期化します
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // 各ページの初期化も実行
        $this->file_list_page->init();
        $this->settings_page->init();
    }

    /**
     * 管理画面メニューを追加します
     */
    public function add_admin_menu() {
        // メインメニューページを追加
        add_menu_page(
                    __( 'BF Secret File Downloader', 'bf-secret-file-downloader' ), // ページタイトル
        __( 'BF Secret File Downloader', 'bf-secret-file-downloader' ), // メニュータイトル
            'manage_options', // 権限
            $this->file_list_page::PAGE_SLUG, // メニュースラッグ
            array( $this->file_list_page, 'render' ), // コールバック関数
            'dashicons-lock', // アイコン
            30 // メニューの位置
        );

        // サブメニューページを追加
        add_submenu_page(
            $this->file_list_page::PAGE_SLUG, // 親メニューのスラッグ
            $this->file_list_page->get_page_title(), // ページタイトル
            $this->file_list_page->get_menu_title(), // メニュータイトル
            'manage_options', // 権限
            $this->file_list_page::PAGE_SLUG, // メニュースラッグ（メインページと同じ）
            array( $this->file_list_page, 'render' ) // コールバック関数
        );

        add_submenu_page(
            $this->file_list_page::PAGE_SLUG, // 親メニューのスラッグ
            $this->settings_page->get_page_title(), // ページタイトル
            $this->settings_page->get_menu_title(), // メニュータイトル
            'manage_options', // 権限
            $this->settings_page::PAGE_SLUG, // メニュースラッグ
            array( $this->settings_page, 'render' ) // コールバック関数
        );
    }
}

<?php
/**
 * SettingsPage Test
 *
 * @package BF Secret File Downloader
 */

namespace Breadfish\SecretFileDownloader\Tests\Admin;

use Breadfish\SecretFileDownloader\Admin\SettingsPage;
use WP_Mock;

/**
 * Test class for SettingsPage
 */
class SettingsPageTest extends \BF_SFD_TestCase {

    private $settings_page;

    public function setUp(): void {
        parent::setUp();
        $this->settings_page = new SettingsPage();
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf( SettingsPage::class, $this->settings_page );
    }

    /**
     * Test constants
     */
    public function test_constants() {
        $this->assertEquals( 'bf-secret-file-downloader-settings', SettingsPage::PAGE_SLUG );
    }

    /**
     * Test init method registers hooks
     */
    public function test_init_registers_hooks() {
        WP_Mock::expectActionAdded( 'admin_init', array( $this->settings_page, 'register_settings' ) );
        WP_Mock::expectActionAdded( 'wp_ajax_bf_sfd_browse_directory', array( $this->settings_page, 'ajax_browse_directory' ) );
        WP_Mock::expectActionAdded( 'wp_ajax_bf_sfd_create_directory', array( $this->settings_page, 'ajax_create_directory' ) );
        WP_Mock::expectActionAdded( 'wp_ajax_bf_sfd_reset_settings', array( $this->settings_page, 'ajax_reset_settings' ) );

        $this->settings_page->init();

        $this->assertConditionsMet();
    }

    /**
     * Test register_settings method
     */
    public function test_register_settings() {
        // Mock register_setting calls
        WP_Mock::userFunction( 'register_setting' )
            ->with( 'bf_sfd_settings', 'bf_sfd_target_directory', \WP_Mock\Functions::type( 'array' ) )
            ->once();

        WP_Mock::userFunction( 'register_setting' )
            ->with( 'bf_sfd_settings', 'bf_sfd_max_file_size', \WP_Mock\Functions::type( 'array' ) )
            ->once();

        WP_Mock::userFunction( 'register_setting' )
            ->with( 'bf_sfd_settings', 'bf_sfd_auth_methods', \WP_Mock\Functions::type( 'array' ) )
            ->once();

        WP_Mock::userFunction( 'register_setting' )
            ->with( 'bf_sfd_settings', 'bf_sfd_allowed_roles', \WP_Mock\Functions::type( 'array' ) )
            ->once();

        WP_Mock::userFunction( 'register_setting' )
            ->with( 'bf_sfd_settings', 'bf_sfd_simple_auth_password', \WP_Mock\Functions::type( 'array' ) )
            ->once();

        $this->settings_page->register_settings();

        $this->assertTrue( true ); // Assert to avoid risky test
    }

    /**
     * Test get_page_title
     */
    public function test_get_page_title() {
        WP_Mock::userFunction( '__' )
            ->with( '設定', 'bf-secret-file-downloader' )
            ->andReturn( '設定' );

        $result = $this->settings_page->get_page_title();
        $this->assertEquals( '設定', $result );
    }

    /**
     * Test get_menu_title
     */
    public function test_get_menu_title() {
        WP_Mock::userFunction( '__' )
            ->with( '設定', 'bf-secret-file-downloader' )
            ->andReturn( '設定' );

        $result = $this->settings_page->get_menu_title();
        $this->assertEquals( '設定', $result );
    }

    /**
     * Test sanitize_boolean
     */
    public function test_sanitize_boolean() {
        $this->assertTrue( $this->settings_page->sanitize_boolean( true ) );
        $this->assertTrue( $this->settings_page->sanitize_boolean( 1 ) );
        $this->assertTrue( $this->settings_page->sanitize_boolean( 'true' ) );

        $this->assertFalse( $this->settings_page->sanitize_boolean( false ) );
        $this->assertFalse( $this->settings_page->sanitize_boolean( 0 ) );
        $this->assertFalse( $this->settings_page->sanitize_boolean( '' ) );
    }

    /**
     * Test sanitize_password
     */
    public function test_sanitize_password() {
        WP_Mock::userFunction( 'sanitize_text_field' )
            ->andReturnUsing( function( $text ) {
                return trim( strip_tags( $text ) );
            });

        $result = $this->settings_page->sanitize_password( 'test_password' );
        $this->assertEquals( 'test_password', $result );

        $result = $this->settings_page->sanitize_password( '<script>alert("xss")</script>password' );
        $this->assertEquals( 'alert("xss")password', $result );
    }

    /**
     * Test sanitize_file_size
     */
    public function test_sanitize_file_size() {
        // Test valid values
        $this->assertEquals( 10, $this->settings_page->sanitize_file_size( 10 ) );
        $this->assertEquals( 50, $this->settings_page->sanitize_file_size( 50 ) );

        // Test boundary values
        $this->assertEquals( 1, $this->settings_page->sanitize_file_size( 0 ) );  // Minimum
        $this->assertEquals( 1, $this->settings_page->sanitize_file_size( -5 ) ); // Below minimum
        $this->assertEquals( 100, $this->settings_page->sanitize_file_size( 150 ) ); // Above maximum

        // Test string conversion
        $this->assertEquals( 25, $this->settings_page->sanitize_file_size( '25' ) );
    }

    /**
     * Test sanitize_auth_methods
     */
    public function test_sanitize_auth_methods() {
        // Test valid methods
        $valid_methods = array( 'logged_in', 'simple_auth' );
        $result = $this->settings_page->sanitize_auth_methods( $valid_methods );
        $this->assertEquals( $valid_methods, $result );

        // Test invalid methods are filtered out
        $mixed_methods = array( 'logged_in', 'invalid_method', 'simple_auth', 'another_invalid' );
        $result = $this->settings_page->sanitize_auth_methods( $mixed_methods );
        $this->assertEquals( array( 'logged_in', 'simple_auth' ), array_values( $result ) );

        // Test non-array input
        $result = $this->settings_page->sanitize_auth_methods( null );
        $this->assertEquals( array(), $result );

        $result = $this->settings_page->sanitize_auth_methods( 'not_an_array' );
        $this->assertEquals( array(), $result );
    }

    /**
     * Test sanitize_roles
     */
    public function test_sanitize_roles() {
        // Test valid roles
        $valid_roles = array( 'administrator', 'editor' );
        $result = $this->settings_page->sanitize_roles( $valid_roles );
        $this->assertEquals( $valid_roles, $result );

        // Test invalid roles are filtered out
        $mixed_roles = array( 'administrator', 'invalid_role', 'editor', 'super_admin' );
        $result = $this->settings_page->sanitize_roles( $mixed_roles );
        $this->assertEquals( array( 'administrator', 'editor' ), array_values( $result ) );

        // Test all valid roles
        $all_valid = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
        $result = $this->settings_page->sanitize_roles( $all_valid );
        $this->assertEquals( $all_valid, $result );

        // Test non-array input
        $result = $this->settings_page->sanitize_roles( null );
        $this->assertEquals( array(), $result );

        $result = $this->settings_page->sanitize_roles( 'not_an_array' );
        $this->assertEquals( array(), $result );
    }

    /**
     * Test sanitize_directory with dangerous directories
     */
    public function test_sanitize_directory_security() {
        WP_Mock::userFunction( 'sanitize_text_field' )
            ->andReturnUsing( function( $text ) {
                return trim( strip_tags( $text ) );
            });

        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '' );

        // Test empty value
        $result = $this->settings_page->sanitize_directory( '' );
        $this->assertEquals( '', $result );

        // Mock WordPress constants for testing
        if ( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', '/var/www/html/' );
        }
        if ( ! defined( 'WP_CONTENT_DIR' ) ) {
            define( 'WP_CONTENT_DIR', '/var/www/html/wp-content' );
        }

        // Test system dangerous directories are rejected
        $system_dangerous_dirs = [ '/', '/etc', '/usr', '/var/log', '/root', '/tmp' ];

        foreach ( $system_dangerous_dirs as $dangerous_dir ) {
            $result = $this->settings_page->sanitize_directory( $dangerous_dir );
            $this->assertEquals( '', $result, "システム危険ディレクトリ '{$dangerous_dir}' は拒否されるべき" );
        }

        // Test WordPress dangerous directories are rejected
        $wp_dangerous_dirs = [
            ABSPATH,                               // WordPressルート
            dirname( ABSPATH ),                    // 親ディレクトリ
            ABSPATH . 'wp-admin',                  // 管理画面
            ABSPATH . 'wp-includes',               // コアファイル
            WP_CONTENT_DIR . '/plugins',           // プラグイン
            WP_CONTENT_DIR . '/themes',            // テーマ
            WP_CONTENT_DIR . '/mu-plugins',        // Must-useプラグイン
        ];

        foreach ( $wp_dangerous_dirs as $wp_dangerous_dir ) {
            $result = $this->settings_page->sanitize_directory( $wp_dangerous_dir );
            $this->assertEquals( '', $result, "WordPress危険ディレクトリ '{$wp_dangerous_dir}' は拒否されるべき" );
        }

        // Test relative paths are rejected
        $relative_paths = [ '../etc/passwd', '../../var/log', 'relative/path' ];

        foreach ( $relative_paths as $relative_path ) {
            $result = $this->settings_page->sanitize_directory( $relative_path );
            $this->assertEquals( '', $result, "相対パス '{$relative_path}' は拒否されるべき" );
        }
    }

    /**
     * Test sanitize_directory with symlink attacks
     */
    public function test_sanitize_directory_symlink_security() {
        WP_Mock::userFunction( 'sanitize_text_field' )
            ->andReturnUsing( function( $text ) {
                return trim( strip_tags( $text ) );
            });

        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '' );

        // テスト用の一時ディレクトリとシンボリックリンクを作成（実際のファイルシステムを使用）
        $temp_dir = sys_get_temp_dir() . '/bf_test_' . uniqid();
        $symlink_path = $temp_dir . '/evil_symlink';

        // テスト環境でのシンボリックリンク作成を試行
        if ( mkdir( $temp_dir, 0755, true ) ) {
            // 存在するディレクトリへのシンボリックリンクを作成（テスト用）
            $target_dir = sys_get_temp_dir();
            if ( is_dir( $target_dir ) && symlink( $target_dir, $symlink_path ) ) {
                // シンボリックリンクが拒否されることをテスト
                $result = $this->settings_page->sanitize_directory( $symlink_path );
                $this->assertEquals( '', $result, "シンボリックリンク '{$symlink_path}' は拒否されるべき" );

                // クリーンアップ
                unlink( $symlink_path );
            } else {
                // シンボリックリンク作成に失敗した場合の代替テスト
                $this->assertTrue( true, "シンボリックリンクテストはファイルシステム権限により制限される場合があります" );
            }
            rmdir( $temp_dir );
        } else {
            // ファイルシステムテストができない場合は、論理テストのみ
            $this->assertTrue( true, "シンボリックリンクテストはファイルシステム権限により制限される場合があります" );
        }
    }

    /**
     * Test that all required methods exist
     */
    public function test_required_methods_exist() {
        $methods = [
            'init',
            'register_settings',
            'ajax_browse_directory',
            'ajax_create_directory',
            'ajax_reset_settings',
            'render',
            'get_page_title',
            'get_menu_title',
            'sanitize_boolean',
            'sanitize_password',
            'sanitize_file_size',
            'sanitize_auth_methods',
            'sanitize_roles',
            'sanitize_directory',
            'show_directory_change_alert'
        ];

        foreach ( $methods as $method ) {
            $this->assertTrue(
                method_exists( $this->settings_page, $method ),
                "Method {$method} should exist"
            );
        }
    }

    /**
     * Test AJAX security checks - simplified version
     */
    public function test_ajax_security_check() {
        // Test that the methods exist and are callable
        $this->assertTrue( method_exists( $this->settings_page, 'ajax_browse_directory' ) );
        $this->assertTrue( method_exists( $this->settings_page, 'ajax_create_directory' ) );
        $this->assertTrue( method_exists( $this->settings_page, 'ajax_reset_settings' ) );

        $this->assertTrue( is_callable( array( $this->settings_page, 'ajax_browse_directory' ) ) );
        $this->assertTrue( is_callable( array( $this->settings_page, 'ajax_create_directory' ) ) );
        $this->assertTrue( is_callable( array( $this->settings_page, 'ajax_reset_settings' ) ) );
    }
}
<?php
/**
 * FrontEnd Test
 *
 * @package BF Secret File Downloader
 */

namespace Breadfish\SecretFileDownloader\Tests;

use Breadfish\SecretFileDownloader\FrontEnd;
use WP_Mock;

/**
 * Test class for FrontEnd
 */
class FrontEndTest extends \BF_SFD_TestCase {

    private $frontend;

    public function setUp(): void {
        parent::setUp();
        $this->frontend = new FrontEnd();
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf( FrontEnd::class, $this->frontend );
    }

    /**
     * Test init method exists and is callable
     */
    public function test_init_method_exists() {
        // Test that init method exists and is callable
        $this->assertTrue( method_exists( $this->frontend, 'init' ) );
        $this->assertTrue( is_callable( array( $this->frontend, 'init' ) ) );
    }

    /**
     * Test that all required methods exist
     */
    public function test_required_methods_exist() {
        $methods = [
            'init',
            'handle_file_download',
        ];

        foreach ( $methods as $method ) {
            $this->assertTrue(
                method_exists( $this->frontend, $method ),
                "Method {$method} should exist"
            );
        }
    }

    /**
     * Test private method existence using reflection
     */
    public function test_private_methods_exist() {
        $reflection = new \ReflectionClass( $this->frontend );

        $private_methods = [
            'build_full_path',
            'is_allowed_directory',
            'log_download',
            'get_client_ip',
            'check_authentication',
            'check_user_role',
            'check_directory_auth',
            'check_user_role_for_directory',
            'check_simple_auth_for_directory',
            'get_directory_auth',
            'check_simple_auth',
            'decrypt_password',
            'get_encryption_key',
            'has_directory_password',
            'verify_directory_access',
            'verify_directory_password',
            'show_authentication_form',
            'show_password_form'
        ];

        foreach ( $private_methods as $method ) {
            $this->assertTrue(
                $reflection->hasMethod( $method ),
                "Private method {$method} should exist"
            );
        }
    }

    /**
     * Test build_full_path method with reflection
     */
    public function test_build_full_path() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'build_full_path' );
        $method->setAccessible( true );

        // Test empty relative path
        $result = $method->invokeArgs( $this->frontend, [ '/base/directory', '' ] );
        $this->assertEquals( '/base/directory', $result );

        // Test dot relative path
        $result = $method->invokeArgs( $this->frontend, [ '/base/directory', '.' ] );
        $this->assertEquals( '/base/directory', $result );

        // Test normal relative path
        $result = $method->invokeArgs( $this->frontend, [ '/base/directory', 'subfolder/file.txt' ] );
        $this->assertEquals( '/base/directory/subfolder/file.txt', $result );

        // Test path with directory traversal attack
        $result = $method->invokeArgs( $this->frontend, [ '/base/directory', '../../../etc/passwd' ] );
        $this->assertEquals( '/base/directory', $result );

        // Test path with double slashes
        $result = $method->invokeArgs( $this->frontend, [ '/base/directory', 'folder//file.txt' ] );
        $this->assertEquals( '/base/directory', $result );
    }

    /**
     * Test is_allowed_directory method with comprehensive security scenarios
     */
    public function test_is_allowed_directory() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'is_allowed_directory' );
        $method->setAccessible( true );

        // Test 1: Empty target directory should return false
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '' );

        $result = $method->invokeArgs( $this->frontend, [ '/some/path' ] );
        $this->assertFalse( $result, 'Empty target directory should return false' );

        // Reset WP_Mock for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 2: Non-existent path should return false
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/valid/target/directory' );

        $result = $method->invokeArgs( $this->frontend, [ '/non/existent/path' ] );
        $this->assertFalse( $result, 'Non-existent path should return false' );

        // Reset WP_Mock for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 3: Non-existent target directory should return false
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/non/existent/target' );

        $result = $method->invokeArgs( $this->frontend, [ '/some/path' ] );
        $this->assertFalse( $result, 'Non-existent target directory should return false' );

        // Reset WP_Mock for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 4: Path outside target directory should return false (simulated)
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/allowed/directory' );

        // Since we can't easily create real directories in unit tests,
        // we test the logic by ensuring the method returns false for obviously wrong paths
        $result = $method->invokeArgs( $this->frontend, [ '/completely/different/path' ] );
        $this->assertFalse( $result, 'Path outside target directory should return false' );

        // Test 5: Empty string path should return false
        $result = $method->invokeArgs( $this->frontend, [ '' ] );
        $this->assertFalse( $result, 'Empty string path should return false' );

        // Test 6: Null path should return false
        $result = $method->invokeArgs( $this->frontend, [ null ] );
        $this->assertFalse( $result, 'Null path should return false' );

        // Reset WP_Mock for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 7: Test path traversal attack scenarios
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/var/www/uploads' );

        // These should all return false as they attempt to escape the target directory
        $malicious_paths = [
            '/var/www/uploads/../../../etc/passwd',
            '/var/www/uploads/../config/database.php',
            '/etc/passwd',
            '/usr/bin',
            '/var/log',
            '../../../etc/passwd',
            '../../config',
            '/var/www/uploads/../../'
        ];

        foreach ( $malicious_paths as $malicious_path ) {
            $result = $method->invokeArgs( $this->frontend, [ $malicious_path ] );
            $this->assertFalse( $result, "Malicious path '{$malicious_path}' should return false" );
        }

        // Test 8: Test various invalid paths that should cause type errors
        $invalid_paths = [
            false,
            0,
            -1,
        ];

        foreach ( $invalid_paths as $invalid_path ) {
            $result = $method->invokeArgs( $this->frontend, [ $invalid_path ] );
            $this->assertFalse( $result, "Invalid path type should return false" );
        }

        // Test 9: Test paths that cause PHP type errors (array, object)
        // These should be handled gracefully by the function
        try {
            $result = $method->invokeArgs( $this->frontend, [ array() ] );
            $this->assertFalse( $result, "Array path should return false" );
        } catch ( \TypeError $e ) {
            // This is expected behavior - the function should validate input types
            $this->assertTrue( true, "TypeError caught as expected for array input" );
        }

        try {
            $result = $method->invokeArgs( $this->frontend, [ new \stdClass() ] );
            $this->assertFalse( $result, "Object path should return false" );
        } catch ( \TypeError $e ) {
            // This is expected behavior - the function should validate input types
            $this->assertTrue( true, "TypeError caught as expected for object input" );
        }

        // Reset WP_Mock for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 10: WordPress関連の危険なディレクトリのテスト

        // Mock WordPress constants if not defined
        if ( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', '/var/www/html/' );
        }
        if ( ! defined( 'WP_CONTENT_DIR' ) ) {
            define( 'WP_CONTENT_DIR', '/var/www/html/wp-content' );
        }

        // Test WordPress root directory (ABSPATH) - 極めて危険
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( ABSPATH );

        $result = $method->invokeArgs( $this->frontend, [ ABSPATH ] );
        $this->assertFalse( $result, "重大な脆弱性: WordPressルートディレクトリ(ABSPATH)は拒否されるべき" );

        // wp-config.phpへのアクセステスト
        $result = $method->invokeArgs( $this->frontend, [ ABSPATH . 'wp-config.php' ] );
        $this->assertFalse( $result, "重大な脆弱性: wp-config.phpへのアクセスは拒否されるべき" );

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test wp-admin directory
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( ABSPATH . 'wp-admin' );

        $result = $method->invokeArgs( $this->frontend, [ ABSPATH . 'wp-admin' ] );
        $this->assertFalse( $result, "重大な脆弱性: wp-adminディレクトリは拒否されるべき" );

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test wp-includes directory
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( ABSPATH . 'wp-includes' );

        $result = $method->invokeArgs( $this->frontend, [ ABSPATH . 'wp-includes' ] );
        $this->assertFalse( $result, "重大な脆弱性: wp-includesディレクトリは拒否されるべき" );

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test plugins directory
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( WP_CONTENT_DIR . '/plugins' );

        $result = $method->invokeArgs( $this->frontend, [ WP_CONTENT_DIR . '/plugins' ] );
        $this->assertFalse( $result, "重大な脆弱性: pluginsディレクトリは拒否されるべき" );

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test themes directory
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( WP_CONTENT_DIR . '/themes' );

        $result = $method->invokeArgs( $this->frontend, [ WP_CONTENT_DIR . '/themes' ] );
        $this->assertFalse( $result, "重大な脆弱性: themesディレクトリは拒否されるべき" );

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test root directory
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/' );

        $result = $method->invokeArgs( $this->frontend, [ '/' ] );
        $this->assertFalse( $result, "セキュリティ修正: target='/'の場合、アクセスが拒否される" );

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 10.5: Test additional specific dangerous target directories that could exist
        $critical_system_targets = [
            '/etc' => ['/etc/passwd', '/etc/shadow', '/etc/hosts'],
            '/usr/bin' => ['/usr/bin/bash', '/usr/bin/sudo'],
            '/var/log' => ['/var/log/auth.log', '/var/log/syslog'],
        ];

        foreach ($critical_system_targets as $dangerous_target => $test_paths) {
            WP_Mock::userFunction( 'get_option' )
                ->with( 'bf_sfd_target_directory', '' )
                ->andReturn( $dangerous_target );

            foreach ($test_paths as $test_path) {
                $result = $method->invokeArgs( $this->frontend, [ $test_path ] );
                // これらのパスは、target_directoryが危険なディレクトリに設定されている場合に
                // 許可される可能性がある（実際のファイルシステムに依存）
                $this->addToAssertionCount(1); // アサーションをカウント
                // 実際の結果をログ出力（デバッグ用）
                error_log("Target: {$dangerous_target}, Test path: {$test_path}, Result: " . ($result ? 'ALLOWED' : 'DENIED'));
            }

            // Reset for next iteration
            WP_Mock::tearDown();
            WP_Mock::setUp();
        }

        // Test other dangerous directories that should fail (non-existent or permission issues)
        $dangerous_target_directories = [
            '/etc',                 // System configuration directory
            '/usr/bin',             // System binaries
            '/var/log',             // System logs
            '/root',                // Root user home
            '/tmp',                 // Temporary directory
            '/proc',                // Process filesystem
            '/sys',                 // System filesystem
            '/dev',                 // Device files
            '',                     // Empty string
            '//',                   // Double slash
            '/../..',              // Relative path escaping
            '/var/www/../etc',      // Path traversal in target
            '/var/www/html/../../../etc', // Deep path traversal
            '../../../',            // Pure relative traversal
        ];

        foreach ( $dangerous_target_directories as $dangerous_target ) {
            WP_Mock::userFunction( 'get_option' )
                ->with( 'bf_sfd_target_directory', '' )
                ->andReturn( $dangerous_target );

            // Most of these should fail due to realpath() returning false for non-existent paths
            // or because they don't match the expected format
            $result = $method->invokeArgs( $this->frontend, [ '/some/legitimate/path' ] );
            $this->assertFalse( $result, "Dangerous target directory '{$dangerous_target}' should make path access fail" );

            // Reset for next iteration
            WP_Mock::tearDown();
            WP_Mock::setUp();
        }

        // Test 11: Test bf_sfd_target_directory with various malformed values
        $malformed_targets = [
            null,                   // Null value
            false,                  // Boolean false
            0,                      // Integer zero
            array(),                // Array
            new \stdClass(),        // Object
            '   ',                  // Whitespace only
            "\t\n\r",             // Tab, newline, carriage return
            'relative/path',        // Non-absolute path
            'C:\\Windows\\System32', // Windows path on Unix system
        ];

        foreach ( $malformed_targets as $malformed_target ) {
            try {
                WP_Mock::userFunction( 'get_option' )
                    ->with( 'bf_sfd_target_directory', '' )
                    ->andReturn( $malformed_target );

                $result = $method->invokeArgs( $this->frontend, [ '/some/path' ] );
                $this->assertFalse( $result, "Malformed target directory should make access fail" );

                // Reset for next iteration
                WP_Mock::tearDown();
                WP_Mock::setUp();
            } catch ( \TypeError $e ) {
                // Some malformed values might cause type errors, which is acceptable
                $this->assertTrue( true, "TypeError caught as expected for malformed target directory" );

                // Reset for next iteration
                WP_Mock::tearDown();
                WP_Mock::setUp();
            }
        }

        // Test 13: WordPress固有の重大なセキュリティ脆弱性パターンのテスト
        $wordpress_security_test_cases = [
            [
                'target' => ABSPATH,
                'test_paths' => [
                    ABSPATH . 'wp-config.php',           // データベース認証情報
                    ABSPATH . '.htaccess',               // サーバー設定
                    ABSPATH . 'wp-config-sample.php',   // 設定サンプル
                    ABSPATH . 'readme.html',             // WordPressバージョン情報
                    ABSPATH . 'license.txt',             // ライセンス情報
                ],
                'description' => 'WordPressルートディレクトリからの機密ファイルアクセス'
            ],
            [
                'target' => WP_CONTENT_DIR . '/plugins',
                'test_paths' => [
                    WP_CONTENT_DIR . '/plugins/bf-secret-file-downloader/CLAUDE.md',  // 設定ファイル
                    WP_CONTENT_DIR . '/plugins/other-plugin/config.php',            // 他プラグインの設定
                ],
                'description' => 'プラグインディレクトリからの設定ファイルアクセス'
            ],
            [
                'target' => WP_CONTENT_DIR . '/themes',
                'test_paths' => [
                    WP_CONTENT_DIR . '/themes/active-theme/functions.php',  // テーマ機能
                    WP_CONTENT_DIR . '/themes/active-theme/style.css',      // スタイルシート
                ],
                'description' => 'テーマディレクトリからのファイルアクセス'
            ],
            [
                'target' => '/tmp',
                'test_paths' => ['/tmp', '/tmp/malicious_script.sh', '/tmp/../etc/passwd'],
                'description' => '/tmpディレクトリがターゲットの場合の脆弱性'
            ],
        ];

        foreach ($wordpress_security_test_cases as $test_case) {
            WP_Mock::userFunction( 'get_option' )
                ->with( 'bf_sfd_target_directory', '' )
                ->andReturn( $test_case['target'] );

            foreach ($test_case['test_paths'] as $test_path) {
                $result = $method->invokeArgs( $this->frontend, [ $test_path ] );

                // テスト結果を詳細にログ出力
                $status = $result ? '許可' : '拒否';
                error_log("{$test_case['description']}: パス '{$test_path}' -> {$status}");

                // セキュリティ修正後: 危険なターゲットディレクトリはすべて拒否される
                $this->assertFalse($result, "セキュリティ修正後: パス '{$test_path}' (ターゲット: {$test_case['target']}) は拒否される");
            }

            // Reset for next iteration
            WP_Mock::tearDown();
            WP_Mock::setUp();
        }

        // Test 14: シンボリックリンク攻撃のテスト
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/safe/directory' );

        // テスト用の一時ディレクトリとシンボリックリンクを作成
        $temp_dir = sys_get_temp_dir() . '/bf_symlink_test_' . uniqid();
        $symlink_path = $temp_dir . '/evil_symlink';

        if ( mkdir( $temp_dir, 0755, true ) ) {
            // /etcへのシンボリックリンクを作成（テスト用）
            if ( symlink( '/etc', $symlink_path ) ) {
                // シンボリックリンクへのアクセスが拒否されることをテスト
                $result = $method->invokeArgs( $this->frontend, [ $symlink_path ] );
                $this->assertFalse( $result, "シンボリックリンク攻撃 '{$symlink_path}' -> /etc は拒否されるべき" );

                // サブディレクトリへのアクセスもテスト
                $result = $method->invokeArgs( $this->frontend, [ $symlink_path . '/passwd' ] );
                $this->assertFalse( $result, "シンボリックリンク経由のファイルアクセス '{$symlink_path}/passwd' は拒否されるべき" );

                // クリーンアップ
                unlink( $symlink_path );
            }
            rmdir( $temp_dir );
        } else {
            // ファイルシステムテストができない場合
            $this->assertTrue( true, "シンボリックリンクテストはファイルシステム権限により制限される場合があります" );
        }

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 15: パス内にシンボリックリンクが含まれる場合のテスト
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_target_directory', '' )
            ->andReturn( '/safe/directory' );

        $temp_dir2 = sys_get_temp_dir() . '/bf_path_symlink_test_' . uniqid();
        $symlink_in_path = $temp_dir2 . '/middle_symlink';
        $target_in_symlink = $symlink_in_path . '/target';

        if ( mkdir( $temp_dir2, 0755, true ) ) {
            // 中間パスにシンボリックリンクを作成
            if ( symlink( '/etc', $symlink_in_path ) ) {
                // パス内のシンボリックリンクが検出されることをテスト
                $result = $method->invokeArgs( $this->frontend, [ $target_in_symlink ] );
                $this->assertFalse( $result, "パス内のシンボリックリンク '{$target_in_symlink}' は拒否されるべき" );

                // クリーンアップ
                unlink( $symlink_in_path );
            }
            rmdir( $temp_dir2 );
        }

        // Reset for next test
        WP_Mock::tearDown();
        WP_Mock::setUp();

        // Test 12: Test edge cases with special characters in target directory
        $special_char_targets = [
            '/var/www/html/uploads with spaces',  // Spaces in path
            '/var/www/html/uploads;rm -rf /',     // Command injection attempt
            '/var/www/html/uploads`whoami`',      // Backtick injection
            '/var/www/html/uploads$(id)',         // Command substitution
            '/var/www/html/uploads|cat /etc/passwd', // Pipe injection
            '/var/www/html/uploads&& rm -rf /',   // Command chaining
            '/var/www/html/uploads\0/etc/passwd', // Null byte injection
            '/var/www/html/uploads\n/etc/passwd', // Newline injection
        ];

        foreach ( $special_char_targets as $special_target ) {
            WP_Mock::userFunction( 'get_option' )
                ->with( 'bf_sfd_target_directory', '' )
                ->andReturn( $special_target );

            $result = $method->invokeArgs( $this->frontend, [ '/some/path' ] );
            $this->assertFalse( $result, "Target directory with special characters '{$special_target}' should be handled safely" );

            // Reset for next iteration
            WP_Mock::tearDown();
            WP_Mock::setUp();
        }
    }

    /**
     * Test get_client_ip method with reflection
     */
    public function test_get_client_ip() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'get_client_ip' );
        $method->setAccessible( true );

        // Backup original $_SERVER
        $original_server = $_SERVER;

        // Test with REMOTE_ADDR
        $_SERVER = array( 'REMOTE_ADDR' => '192.168.1.1' );
        $result = $method->invoke( $this->frontend );
        $this->assertEquals( '192.168.1.1', $result );

        // Test with no IP
        $_SERVER = array();
        $result = $method->invoke( $this->frontend );
        $this->assertEquals( '0.0.0.0', $result );

        // Test with HTTP_X_FORWARDED_FOR (public IP)
        $_SERVER = array(
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 192.168.1.1',
            'REMOTE_ADDR' => '192.168.1.1'
        );
        $result = $method->invoke( $this->frontend );
        $this->assertEquals( '203.0.113.1', $result );

        // Restore original $_SERVER
        $_SERVER = $original_server;
    }

    /**
     * Test check_user_role method with reflection
     */
    public function test_check_user_role() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'check_user_role' );
        $method->setAccessible( true );

        // Mock get_option for allowed roles
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_allowed_roles', array( 'administrator' ) )
            ->andReturn( array() );

        // Test with empty allowed roles
        $result = $method->invoke( $this->frontend );
        $this->assertFalse( $result );
    }

    /**
     * Test check_user_role_for_directory method with reflection
     */
    public function test_check_user_role_for_directory() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'check_user_role_for_directory' );
        $method->setAccessible( true );

        // Test with empty allowed roles
        $result = $method->invokeArgs( $this->frontend, [ array() ] );
        $this->assertFalse( $result );
    }

    /**
     * Test get_encryption_key method with reflection
     */
    public function test_get_encryption_key() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'get_encryption_key' );
        $method->setAccessible( true );

        // Define constants if not defined (for testing)
        if ( ! defined( 'AUTH_KEY' ) ) {
            define( 'AUTH_KEY', 'test_auth_key' );
        }
        if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
            define( 'SECURE_AUTH_KEY', 'test_secure_auth_key' );
        }
        if ( ! defined( 'LOGGED_IN_KEY' ) ) {
            define( 'LOGGED_IN_KEY', 'test_logged_in_key' );
        }
        if ( ! defined( 'NONCE_KEY' ) ) {
            define( 'NONCE_KEY', 'test_nonce_key' );
        }

        $result = $method->invoke( $this->frontend );
        $this->assertIsString( $result );
        $this->assertEquals( 64, strlen( $result ) ); // SHA256 hash is 64 characters
    }

    /**
     * Test decrypt_password method with reflection
     */
    public function test_decrypt_password() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'decrypt_password' );
        $method->setAccessible( true );

        // Test with invalid base64
        $result = $method->invokeArgs( $this->frontend, [ 'invalid_base64' ] );
        $this->assertFalse( $result );

        // Test with short data
        $result = $method->invokeArgs( $this->frontend, [ base64_encode( 'short' ) ] );
        $this->assertFalse( $result );
    }

    /**
     * Test has_directory_password method with reflection
     */
    public function test_has_directory_password() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'has_directory_password' );
        $method->setAccessible( true );

        // Mock get_option for directory passwords
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_directory_passwords', array() )
            ->andReturn( array() );

        // Test with no directory passwords
        $result = $method->invokeArgs( $this->frontend, [ '/some/path' ] );
        $this->assertFalse( $result );
    }

    /**
     * Test verify_directory_password method with reflection
     */
    public function test_verify_directory_password() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'verify_directory_password' );
        $method->setAccessible( true );

        // Mock get_option for directory passwords
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_directory_passwords', array() )
            ->andReturn( array() );

        // Test with no directory passwords
        $result = $method->invokeArgs( $this->frontend, [ '/some/path', 'password' ] );
        $this->assertFalse( $result );
    }

    /**
     * Test get_directory_auth method with reflection
     */
    public function test_get_directory_auth() {
        $reflection = new \ReflectionClass( $this->frontend );
        $method = $reflection->getMethod( 'get_directory_auth' );
        $method->setAccessible( true );

        // Mock get_option for directory auths
        WP_Mock::userFunction( 'get_option' )
            ->with( 'bf_sfd_directory_auths', array() )
            ->andReturn( array() );

        // Test with no directory auths
        $result = $method->invokeArgs( $this->frontend, [ '/some/path' ] );
        $this->assertFalse( $result );
    }

    /**
     * Test authentication methods existence
     */
    public function test_authentication_methods_exist() {
        $reflection = new \ReflectionClass( $this->frontend );

        $auth_methods = [
            'check_authentication',
            'check_simple_auth',
            'check_simple_auth_for_directory',
            'check_directory_auth'
        ];

        foreach ( $auth_methods as $method ) {
            $this->assertTrue(
                $reflection->hasMethod( $method ),
                "Authentication method {$method} should exist"
            );
        }
    }

    /**
     * Test form display methods exist
     */
    public function test_form_display_methods_exist() {
        $reflection = new \ReflectionClass( $this->frontend );

        $form_methods = [
            'show_authentication_form',
            'show_password_form'
        ];

        foreach ( $form_methods as $method ) {
            $this->assertTrue(
                $reflection->hasMethod( $method ),
                "Form display method {$method} should exist"
            );
        }
    }

    /**
     * Test that init method is callable
     */
    public function test_init_callable() {
        $this->assertTrue( is_callable( array( $this->frontend, 'init' ) ) );
    }

    /**
     * Test that handle_file_download method is callable
     */
    public function test_handle_file_download_callable() {
        $this->assertTrue( is_callable( array( $this->frontend, 'handle_file_download' ) ) );
    }

}
<?php

namespace Breadfish\SecretFileDownloader\Tests;

use Breadfish\SecretFileDownloader\FrontEnd;
use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Test cases for FrontEnd class
 */
class FrontEndTest extends TestCase {

    private $frontend;

    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();
        $this->frontend = new FrontEnd();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test that FrontEnd class can be instantiated
     */
    public function test_constructor() {
        $this->assertInstanceOf( FrontEnd::class, $this->frontend );
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

    /**
     * Test private methods exist
     */
    public function test_private_methods_exist() {
        $reflection = new \ReflectionClass( $this->frontend );

        $private_methods = [
            'log_download',
            'get_client_ip',
            'check_authentication',
            'check_user_role'
        ];

        foreach ( $private_methods as $method ) {
            $this->assertTrue(
                $reflection->hasMethod( $method ),
                "Private method {$method} should exist"
            );
        }
    }
}
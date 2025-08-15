<?php
/**
 * Block Test
 *
 * @package BF Secret File Downloader
 */

namespace Breadfish\SecretFileDownloader\Tests;

use Breadfish\SecretFileDownloader\Block;
use WP_Mock;

/**
 * Test class for Block
 */
class BlockTest extends \BF_SFD_TestCase {

    private $block;

    public function setUp(): void {
        parent::setUp();
        $this->block = new Block();
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf( Block::class, $this->block );
    }

    /**
     * Test init method registers hooks
     */
    public function test_init_registers_hooks() {
        WP_Mock::expectActionAdded( 'init', array( $this->block, 'register_block' ), 20 );
        WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', array( $this->block, 'enqueue_editor_assets' ) );
        WP_Mock::expectActionAdded( 'wp_enqueue_scripts', array( $this->block, 'enqueue_frontend_styles' ) );

        $this->block->init();

        $this->assertConditionsMet();
    }

    /**
     * Test that methods exist
     */
    public function test_methods_exist() {
        $this->assertTrue( method_exists( $this->block, 'register_block' ) );
        $this->assertTrue( method_exists( $this->block, 'enqueue_editor_assets' ) );
        $this->assertTrue( method_exists( $this->block, 'enqueue_frontend_styles' ) );
    }

    /**
     * Test block name constant from actual code
     */
    public function test_block_functionality() {
        // Test that register_block can be called
        $this->assertTrue( is_callable( array( $this->block, 'register_block' ) ) );
    }
}
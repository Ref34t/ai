<?php
/**
 * Tests for the Global_Settings class.
 *
 * @package WordPress\AI\Tests\Integration\Admin
 */

namespace WordPress\AI\Tests\Integration\Admin;

use WordPress\AI\Admin\Global_Settings;
use WP_UnitTestCase;

/**
 * Global_Settings test case.
 *
 * @since 0.1.0
 */
class Global_Settings_Test extends WP_UnitTestCase {
	/**
	 * Global settings instance.
	 *
	 * @var Global_Settings
	 */
	private $settings;

	/**
	 * Setup test case.
	 *
	 * @since 0.1.0
	 */
	public function setUp(): void {
		parent::setUp();
		$this->settings = new Global_Settings();
		delete_option( 'ai_experiments_enabled' );
	}

	/**
	 * Teardown test case.
	 *
	 * @since 0.1.0
	 */
	public function tearDown(): void {
		delete_option( 'ai_experiments_enabled' );
		parent::tearDown();
	}

	/**
	 * Test register_settings registers the option.
	 *
	 * @since 0.1.0
	 */
	public function test_register_settings() {
		$this->settings->register_settings();

		$registered = get_registered_settings();
		$this->assertArrayHasKey( 'ai_experiments_enabled', $registered, 'Setting should be registered' );
		$this->assertEquals( 'boolean', $registered['ai_experiments_enabled']['type'], 'Setting type should be boolean' );
	}

	/**
	 * Test are_experiments_enabled returns false by default.
	 *
	 * @since 0.1.0
	 */
	public function test_are_experiments_enabled_returns_false_by_default() {
		$this->assertFalse( Global_Settings::are_experiments_enabled(), 'Should return false by default' );
	}

	/**
	 * Test are_experiments_enabled returns true when enabled.
	 *
	 * @since 0.1.0
	 */
	public function test_are_experiments_enabled_returns_true_when_enabled() {
		update_option( 'ai_experiments_enabled', true );

		$this->assertTrue( Global_Settings::are_experiments_enabled(), 'Should return true when enabled' );
	}

	/**
	 * Test are_experiments_enabled returns false when disabled.
	 *
	 * @since 0.1.0
	 */
	public function test_are_experiments_enabled_returns_false_when_disabled() {
		update_option( 'ai_experiments_enabled', false );

		$this->assertFalse( Global_Settings::are_experiments_enabled(), 'Should return false when disabled' );
	}

	/**
	 * Test are_experiments_enabled respects filter hook.
	 *
	 * @since 0.1.0
	 */
	public function test_are_experiments_enabled_respects_filter() {
		add_filter( 'ai_experiments_enabled_default', '__return_true' );

		$this->assertTrue( Global_Settings::are_experiments_enabled(), 'Should return filtered default value' );

		remove_filter( 'ai_experiments_enabled_default', '__return_true' );
	}

	/**
	 * Test sanitize_toggle converts values to boolean.
	 *
	 * @since 0.1.0
	 */
	public function test_sanitize_toggle_converts_to_boolean() {
		$this->assertTrue( $this->settings->sanitize_toggle( 1 ), 'Should convert 1 to true' );
		$this->assertTrue( $this->settings->sanitize_toggle( '1' ), 'Should convert "1" to true' );
		$this->assertTrue( $this->settings->sanitize_toggle( 'true' ), 'Should convert "true" to true' );
		$this->assertFalse( $this->settings->sanitize_toggle( 0 ), 'Should convert 0 to false' );
		$this->assertFalse( $this->settings->sanitize_toggle( '' ), 'Should convert empty string to false' );
		$this->assertFalse( $this->settings->sanitize_toggle( null ), 'Should convert null to false' );
	}

	/**
	 * Test are_experiments_enabled always returns boolean.
	 *
	 * @since 0.1.0
	 */
	public function test_are_experiments_enabled_always_returns_boolean() {
		update_option( 'ai_experiments_enabled', '1' );
		$this->assertIsBool( Global_Settings::are_experiments_enabled(), 'Should return boolean' );

		update_option( 'ai_experiments_enabled', 'yes' );
		$this->assertIsBool( Global_Settings::are_experiments_enabled(), 'Should return boolean for invalid value' );
	}

	/**
	 * Test option is not autoloaded.
	 *
	 * @since 0.1.0
	 */
	public function test_option_not_autoloaded() {
		update_option( 'ai_experiments_enabled', true, false );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$autoload = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s",
				'ai_experiments_enabled'
			)
		);

		$this->assertContains( $autoload, array( 'no', 'off' ), 'Option should not be autoloaded for performance' );
	}
}
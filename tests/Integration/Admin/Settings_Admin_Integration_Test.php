<?php
/**
 * Integration tests for admin initialization wiring.
 *
 * @package WordPress\AI\Tests\Integration\Admin
 */

namespace WordPress\AI\Tests\Integration\Admin;

use WP_REST_Request;
use WP_UnitTestCase;
use WordPress\AI\Admin\Global_Settings;
use function WordPress\AI\get_rest_settings_controller;
use function WordPress\AI\initialize_admin;

/**
 * Settings_Admin_Integration test case.
 *
 * @since 0.1.0
 */
class Settings_Admin_Integration_Test extends WP_UnitTestCase {
	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up test case.
	 *
	 * @since 0.1.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
		set_current_screen( 'dashboard' );
	}

	/**
	 * Tear down test case.
	 *
	 * @since 0.1.0
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Test that initialize_admin registers settings and menu.
	 *
	 * @since 0.1.0
	 */
	public function test_initialize_admin_registers_settings_and_menu() {
		initialize_admin();

		$registered = get_registered_settings();
		$this->assertArrayHasKey(
			'ai_experiments_enabled',
			$registered,
			'Global experiments option should be registered'
		);

		// Trigger menu registration then confirm the options page exists.
		do_action( 'admin_menu' );

		$this->assertNotFalse(
			menu_page_url( 'ai-experiments', false ),
			'Settings → AI Experiments menu should be available'
		);
	}

	/**
	 * Test REST integration updates the experiments option.
	 *
	 * @since 0.1.0
	 */
	public function test_rest_updates_experiments_option() {
		initialize_admin();

		$controller = get_rest_settings_controller();
		$controller->register_hooks();
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		$request->set_param( 'experimentsEnabled', true );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'REST request should succeed' );
		$this->assertTrue( $data['data']['experimentsEnabled'], 'Response should reflect enabled state' );
		$this->assertTrue( Global_Settings::are_experiments_enabled(), 'Option should be updated in database' );
	}
}

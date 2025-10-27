<?php
/**
 * Tests for the REST_Settings_Controller class.
 *
 * @package WordPress\AI\Tests\Integration\Admin
 */

namespace WordPress\AI\Tests\Integration\Admin;

use WordPress\AI\Admin\REST_Settings_Controller;
use WordPress\AI\Admin\Global_Settings;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * REST_Settings_Controller test case.
 *
 * @since 0.1.0
 */
class REST_Settings_Controller_Test extends WP_UnitTestCase {
	/**
	 * REST controller instance.
	 *
	 * @var REST_Settings_Controller
	 */
	private $controller;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private $editor_user;

	/**
	 * Setup test case.
	 *
	 * @since 0.1.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new REST_Settings_Controller();

		add_action( 'rest_api_init', array( $this->controller, 'register_routes' ) );
		do_action( 'rest_api_init' );

		// Create test users.
		$this->admin_user  = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->editor_user = $this->factory->user->create( array( 'role' => 'editor' ) );

		// Clean option.
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
	 * Test route registration.
	 *
	 * @since 0.1.0
	 */
	public function test_route_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wp/v2/ai-experiments/settings', $routes, 'Route should be registered' );
	}

	/**
	 * Test GET request returns current settings.
	 *
	 * @since 0.1.0
	 */
	public function test_get_settings() {
		wp_set_current_user( $this->admin_user );
		update_option( 'ai_experiments_enabled', true );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/ai-experiments/settings' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Should return 200 status' );
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertTrue( $data['data']['experimentsEnabled'], 'Should return enabled status' );
	}

	/**
	 * Test GET request without permission is denied.
	 *
	 * @since 0.1.0
	 */
	public function test_get_settings_without_permission() {
		wp_set_current_user( $this->editor_user );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/ai-experiments/settings' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Should return 403 forbidden' );
	}

	/**
	 * Test POST request updates settings.
	 *
	 * @since 0.1.0
	 */
	public function test_update_settings() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		$request->set_param( 'experimentsEnabled', true );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Should return 200 status' );
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertTrue( $data['data']['experimentsEnabled'], 'Should return new enabled status' );
		$this->assertTrue( Global_Settings::are_experiments_enabled(), 'Option should be updated in database' );
	}

	/**
	 * Test POST request disables settings.
	 *
	 * @since 0.1.0
	 */
	public function test_update_settings_disable() {
		wp_set_current_user( $this->admin_user );
		update_option( 'ai_experiments_enabled', true );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		$request->set_param( 'experimentsEnabled', false );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Should return 200 status' );
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertFalse( $data['data']['experimentsEnabled'], 'Should return disabled status' );
		$this->assertFalse( Global_Settings::are_experiments_enabled(), 'Option should be disabled in database' );
	}

	/**
	 * Test POST request without permission is denied.
	 *
	 * @since 0.1.0
	 */
	public function test_update_settings_without_permission() {
		wp_set_current_user( $this->editor_user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		$request->set_param( 'experimentsEnabled', true );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Should return 403 forbidden' );
		$this->assertFalse( Global_Settings::are_experiments_enabled(), 'Option should not be updated' );
	}

	/**
	 * Test POST request with invalid boolean returns error.
	 *
	 * @since 0.1.0
	 */
	public function test_update_settings_invalid_boolean() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		$request->set_param( 'experimentsEnabled', 'not-a-boolean' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'Should return 400 bad request' );
	}

	/**
	 * Test POST request without required parameter returns error.
	 *
	 * @since 0.1.0
	 */
	public function test_update_settings_missing_parameter() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		// No parameter set.

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'Should return 400 bad request for missing parameter' );
	}

	/**
	 * Test permission callback respects filter.
	 *
	 * @since 0.1.0
	 */
	public function test_permission_filter() {
		wp_set_current_user( $this->editor_user );

		// Allow editors to manage settings.
		add_filter(
			'ai_experiments_settings_capability',
			function () {
				return 'edit_posts';
			}
		);

		$request  = new WP_REST_Request( 'GET', '/wp/v2/ai-experiments/settings' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'Editor should have access with filtered capability' );

		remove_all_filters( 'ai_experiments_settings_capability' );
	}

	/**
	 * Test schema is properly defined.
	 *
	 * @since 0.1.0
	 */
	public function test_schema() {
		$schema = $this->controller->get_settings_schema();

		$this->assertIsArray( $schema, 'Schema should be an array' );
		$this->assertArrayHasKey( 'properties', $schema, 'Schema should have properties' );
		$this->assertArrayHasKey( 'experimentsEnabled', $schema['properties'], 'Schema should define experimentsEnabled' );
		$this->assertEquals( 'boolean', $schema['properties']['experimentsEnabled']['type'], 'experimentsEnabled should be boolean' );
	}

	/**
	 * Test endpoint args are properly defined.
	 *
	 * @since 0.1.0
	 */
	public function test_endpoint_args() {
		$args = $this->controller->get_endpoint_args();

		$this->assertIsArray( $args, 'Args should be an array' );
		$this->assertArrayHasKey( 'experimentsEnabled', $args, 'Should have experimentsEnabled arg' );
		$this->assertEquals( 'boolean', $args['experimentsEnabled']['type'], 'Type should be boolean' );
		$this->assertTrue( $args['experimentsEnabled']['required'], 'Should be required' );
		$this->assertEquals( 'rest_sanitize_boolean', $args['experimentsEnabled']['sanitize_callback'], 'Should have sanitize callback' );
	}

	/**
	 * Test GET returns false when option not set.
	 *
	 * @since 0.1.0
	 */
	public function test_get_settings_returns_false_when_not_set() {
		wp_set_current_user( $this->admin_user );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/ai-experiments/settings' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertFalse( $data['data']['experimentsEnabled'], 'Should return false when option not set' );
	}

	/**
	 * Test successful POST returns message.
	 *
	 * @since 0.1.0
	 */
	public function test_update_settings_returns_message() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/ai-experiments/settings' );
		$request->set_param( 'experimentsEnabled', true );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'message', $data, 'Response should include message' );
		$this->assertIsString( $data['message'], 'Message should be a string' );
	}
}
<?php
/**
 * Tests for the Settings_Page class.
 *
 * @package WordPress\AI\Tests\Integration\Admin
 */

namespace WordPress\AI\Tests\Integration\Admin;

use WordPress\AI\Admin\Settings_Page;
use WordPress\AI\Admin\Settings_Registry;
use WP_UnitTestCase;

/**
 * Settings_Page test case.
 *
 * @since 0.1.0
 */
class Settings_Page_Test extends WP_UnitTestCase {
	/**
	 * Settings registry instance.
	 *
	 * @var Settings_Registry
	 */
	private $registry;

	/**
	 * Settings page instance.
	 *
	 * @var Settings_Page
	 */
	private $settings_page;

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

		$this->registry      = new Settings_Registry();
		$this->settings_page = new Settings_Page( $this->registry );

		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
	}

	/**
	 * Tear down test case.
	 *
	 * @since 0.1.0
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		wp_dequeue_script( 'ai-admin-settings' );
		wp_deregister_script( 'ai-admin-settings' );

		parent::tearDown();
	}

	/**
	 * Test register() attaches admin hooks.
	 *
	 * @since 0.1.0
	 */
	public function test_register_attaches_hooks() {
		remove_all_actions( 'admin_menu' );
		remove_all_actions( 'admin_enqueue_scripts' );

		$this->settings_page->register();

		$this->assertSame(
			10,
			has_action( 'admin_menu', array( $this->settings_page, 'register_menu' ) ),
			'register_menu callback should be hooked to admin_menu'
		);

		$this->assertSame(
			10,
			has_action( 'admin_enqueue_scripts', array( $this->settings_page, 'enqueue_assets' ) ),
			'enqueue_assets callback should be hooked to admin_enqueue_scripts'
		);
	}

	/**
	 * Test register_menu adds options page.
	 *
	 * @since 0.1.0
	 */
	public function test_register_menu_adds_options_page() {
		set_current_screen( 'dashboard' );

		$this->settings_page->register_menu();

		$url = menu_page_url( 'ai-experiments', false );

		$this->assertNotFalse( $url, 'Menu page URL should be available' );
		$this->assertStringContainsString(
			'options-general.php?page=ai-experiments',
			$url,
			'Menu URL should point to options-general.php'
		);
	}

	/**
	 * Test render_page outputs expected markup.
	 *
	 * @since 0.1.0
	 */
	public function test_render_page_outputs_mount_point() {
		ob_start();
		$this->settings_page->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'ai-experiments-settings-root', $output, 'Should output React mount point' );
	}

	/**
	 * Test enqueue_assets only runs on the settings screen.
	 *
	 * @since 0.1.0
	 */
	public function test_enqueue_assets_only_on_settings_screen() {
		$this->settings_page->enqueue_assets( 'dashboard_page' );
		$this->assertFalse(
			wp_script_is( 'ai-admin-settings', 'enqueued' ),
			'Script should not be enqueued on other screens'
		);

		$this->settings_page->enqueue_assets( 'settings_page_ai-experiments' );
		$this->assertTrue(
			wp_script_is( 'ai-admin-settings', 'enqueued' ),
			'Script should be enqueued on settings screen'
		);

		global $wp_scripts;
		$localized_data = $wp_scripts->get_data( 'ai-admin-settings', 'data' );

		$this->assertIsString( $localized_data, 'Localized script data should be set' );
		$this->assertStringContainsString( 'aiSettingsScreen', $localized_data, 'Localized data should define aiSettingsScreen' );
	}
}

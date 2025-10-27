<?php
/**
 * Admin settings page handler.
 *
 * @package WordPress\AI\Admin
 * @since 0.1.0
 */

declare( strict_types=1 );

namespace WordPress\AI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings_Page
 *
 * Registers the admin settings screen and related assets.
 *
 * @since 0.1.0
 */
class Settings_Page {
	/**
	 * Menu slug for the settings page.
	 *
	 * @since 0.1.0
	 */
	private const MENU_SLUG = 'ai-experiments';

	/**
	 * Script handle used for the settings experience.
	 *
	 * @since 0.1.0
	 */
	private const SCRIPT_HANDLE = 'ai-admin-settings';

	/**
	 * Settings registry instance.
	 *
	 * @since 0.1.0
	 * @var Settings_Registry
	 */
	private Settings_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Settings_Registry $registry Registry instance for settings sections.
	 */
	public function __construct( Settings_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Registers admin hooks for the settings page.
	 *
	 * @since 0.1.0
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the settings page under the Settings menu.
	 *
	 * @since 0.1.0
	 */
	public function register_menu(): void {
		$capability = apply_filters( 'ai_experiments_settings_capability', 'manage_options' );

		add_options_page(
			__( 'AI Experiments', 'ai' ),
			__( 'AI Experiments', 'ai' ),
			$capability,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues scripts and data for the settings page.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_suffix Current page hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $this->get_screen_id() !== $hook_suffix ) {
			return;
		}

		$this->register_script_dependencies();

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'aiSettingsScreen',
			$this->get_script_data()
		);

		// Provide a lightweight placeholder until the React app is introduced.
		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.aiSettingsScreen = window.aiSettingsScreen || {};',
			'before'
		);

		wp_enqueue_script( self::SCRIPT_HANDLE );

		/**
		 * Fires after admin settings page assets are enqueued.
		 *
		 * @since 0.1.0
		 *
		 * @param string $hook_suffix Current screen hook.
		 */
		do_action( 'ai_admin_settings_enqueue', $hook_suffix );
	}

	/**
	 * Renders the admin settings page output.
	 *
	 * @since 0.1.0
	 */
	public function render_page(): void {
		$template = trailingslashit( AI_PLUGIN_DIR ) . 'includes/Admin/views/settings-page.php';

		if ( file_exists( $template ) ) {
			// Make registry available to the template for future use.
			$registry = $this->registry;
			include $template;
			return;
		}

		echo '<div class="notice notice-error"><p>';
		esc_html_e( 'Settings template is missing.', 'ai' );
		echo '</p></div>';
	}

	/**
	 * Registers the script handle and dependencies used by the settings screen.
	 *
	 * @since 0.1.0
	 */
	private function register_script_dependencies(): void {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			false,
			array( 'wp-api-fetch', 'wp-element', 'wp-i18n' ),
			AI_VERSION,
			true
		);
	}

	/**
	 * Retrieves the localized data for the settings script.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed> Script data.
	 */
	private function get_script_data(): array {
		return array(
			'restUrl'            => rest_url( 'wp/v2/ai-experiments/settings' ),
			'nonce'              => wp_create_nonce( 'wp_rest' ),
			'experimentsEnabled' => Global_Settings::are_experiments_enabled(),
			'availableSections'  => $this->registry->get_sections(),
			'strings'            => array(
				'pageTitle' => __( 'AI Experiments', 'ai' ),
				'save'      => __( 'Save Changes', 'ai' ),
			),
		);
	}

	/**
	 * Gets the expected screen ID for the settings page.
	 *
	 * @since 0.1.0
	 *
	 * @return string Screen identifier.
	 */
	private function get_screen_id(): string {
		return 'settings_page_' . self::MENU_SLUG;
	}
}

<?php
/**
 * Global settings handler for AI Experiments.
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
 * Class Global_Settings
 *
 * Handles global plugin settings.
 *
 * @since 0.1.0
 */
class Global_Settings {
	/**
	 * Option name for experiments enabled setting.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const OPTION_NAME = 'ai_experiments_enabled';

	/**
	 * Registers settings with WordPress.
	 *
	 * @since 0.1.0
	 */
	public function register_settings(): void {
		register_setting(
			'ai_experiments',
			self::OPTION_NAME,
			array(
				'type'              => 'boolean',
				'description'       => __( 'Enable experimental AI features', 'ai' ),
				'sanitize_callback' => array( $this, 'sanitize_toggle' ),
				'show_in_rest'      => false,
				'default'           => $this->get_default_value(),
			)
		);
	}

	/**
	 * Sanitizes the toggle value.
	 *
	 * @since 0.1.0
	 * @param mixed $value The value to sanitize.
	 * @return bool Sanitized boolean value.
	 */
	public function sanitize_toggle( $value ): bool {
		return rest_sanitize_boolean( $value );
	}

	/**
	 * Checks if experimental features are enabled.
	 *
	 * @since 0.1.0
	 * @return bool True if experiments are enabled, false otherwise.
	 */
	public static function are_experiments_enabled(): bool {
		$default = apply_filters( 'ai_experiments_enabled_default', false );
		return (bool) get_option( self::OPTION_NAME, $default );
	}

	/**
	 * Gets the default value for the setting.
	 *
	 * @since 0.1.0
	 * @return bool Default value.
	 */
	private function get_default_value(): bool {
		return (bool) apply_filters( 'ai_experiments_enabled_default', false );
	}
}

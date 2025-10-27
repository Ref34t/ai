<?php
/**
 * REST API controller for AI Experiments settings.
 *
 * @package WordPress\AI\Admin
 * @since 0.1.0
 */

declare( strict_types=1 );

namespace WordPress\AI\Admin;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WordPress\AI\Admin\Global_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_Settings_Controller
 *
 * Handles REST API endpoints for settings.
 *
 * @since 0.1.0
 */
class REST_Settings_Controller extends WP_REST_Controller {
	/**
	 * REST API namespace.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $namespace = 'wp/v2';

	/**
	 * REST API base route.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'ai-experiments/settings';

	/**
	 * Registers hooks for the controller.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the routes for settings.
	 *
	 * @since 0.1.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_settings_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => $this->get_endpoint_args(),
				),
				'schema' => array( $this, 'get_settings_schema' ),
			)
		);
	}

	/**
	 * Retrieves current settings.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_settings( $request ) {
		$experiments_enabled = Global_Settings::are_experiments_enabled();

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'experimentsEnabled' => $experiments_enabled,
				),
			)
		);
	}

	/**
	 * Updates settings.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_settings( $request ) {
		$experiments_enabled = $request->get_param( 'experimentsEnabled' );

		// Validate boolean.
		if ( ! is_bool( $experiments_enabled ) ) {
			return new WP_Error(
				'ai_experiments_invalid_param',
				__( 'The experimentsEnabled parameter must be a boolean.', 'ai' ),
				array( 'status' => 400 )
			);
		}

		// Update option.
		$updated = update_option( 'ai_experiments_enabled', $experiments_enabled, false );

		if ( ! $updated && Global_Settings::are_experiments_enabled() !== $experiments_enabled ) {
			return new WP_Error(
				'ai_experiments_update_failed',
				__( 'Failed to update settings.', 'ai' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully.', 'ai' ),
				'data'    => array(
					'experimentsEnabled' => $experiments_enabled,
				),
			)
		);
	}

	/**
	 * Checks if a given request has permission to read settings.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_settings_permissions_check( $request ) {
		$capability = apply_filters( 'ai_experiments_settings_capability', 'manage_options' );

		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'ai_experiments_cannot_read',
				__( 'Sorry, you are not allowed to read settings.', 'ai' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Checks if a given request has permission to update settings.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has update access, WP_Error object otherwise.
	 */
	public function update_settings_permissions_check( $request ) {
		$capability = apply_filters( 'ai_experiments_settings_capability', 'manage_options' );

		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'ai_experiments_cannot_update',
				__( 'Sorry, you are not allowed to update settings.', 'ai' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Retrieves the settings schema, conforming to JSON Schema.
	 *
	 * @since 0.1.0
	 * @return array Item schema data.
	 */
	public function get_settings_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ai-experiments-settings',
			'type'       => 'object',
			'properties' => array(
				'experimentsEnabled' => array(
					'description' => __( 'Whether experimental features are enabled.', 'ai' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}

	/**
	 * Retrieves the query params for the endpoint.
	 *
	 * @since 0.1.0
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args(): array {
		return array(
			'experimentsEnabled' => array(
				'description'       => __( 'Whether to enable experimental features.', 'ai' ),
				'type'              => 'boolean',
				'required'          => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}

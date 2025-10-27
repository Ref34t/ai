<?php
/**
 * Settings registry for AI Experiments.
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
 * Class Settings_Registry
 *
 * Manages registration of settings sections from features.
 *
 * @since 0.1.0
 */
class Settings_Registry {
	/**
	 * Registered settings sections.
	 *
	 * @since 0.1.0
	 * @var array<string, array<string, mixed>>
	 */
	private array $sections = array();

	/**
	 * Registers a settings section.
	 *
	 * @since 0.1.0
	 * @param string $id   Unique section identifier.
	 * @param array  $args Section arguments.
	 * @return bool True on success, false if section already exists.
	 */
	public function register_section( string $id, array $args ): bool {
		if ( isset( $this->sections[ $id ] ) ) {
			return false;
		}

		$defaults = array(
			'title'       => '',
			'description' => '',
			'callback'    => null,
			'fields'      => array(),
			'priority'    => 10,
		);

		$this->sections[ $id ] = wp_parse_args( $args, $defaults );

		return true;
	}

	/**
	 * Retrieves all registered sections.
	 *
	 * @since 0.1.0
	 * @return array<string, array<string, mixed>> Registered sections.
	 */
	public function get_sections(): array {
		// Sort by priority.
		uasort(
			$this->sections,
			function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		return $this->sections;
	}

	/**
	 * Retrieves a specific section.
	 *
	 * @since 0.1.0
	 * @param string $id Section identifier.
	 * @return array<string, mixed>|null Section data or null if not found.
	 */
	public function get_section( string $id ): ?array {
		return $this->sections[ $id ] ?? null;
	}

	/**
	 * Checks if a section is registered.
	 *
	 * @since 0.1.0
	 * @param string $id Section identifier.
	 * @return bool True if section exists, false otherwise.
	 */
	public function has_section( string $id ): bool {
		return isset( $this->sections[ $id ] );
	}

	/**
	 * Unregisters a section.
	 *
	 * @since 0.1.0
	 * @param string $id Section identifier.
	 * @return bool True on success, false if section doesn't exist.
	 */
	public function unregister_section( string $id ): bool {
		if ( ! isset( $this->sections[ $id ] ) ) {
			return false;
		}

		unset( $this->sections[ $id ] );

		return true;
	}
}

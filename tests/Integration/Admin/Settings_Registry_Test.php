<?php
/**
 * Tests for the Settings_Registry class.
 *
 * @package WordPress\AI\Tests\Integration\Admin
 */

namespace WordPress\AI\Tests\Integration\Admin;

use WordPress\AI\Admin\Settings_Registry;
use WP_UnitTestCase;

/**
 * Settings_Registry test case.
 *
 * @since 0.1.0
 */
class Settings_Registry_Test extends WP_UnitTestCase {
	/**
	 * Settings registry instance.
	 *
	 * @var Settings_Registry
	 */
	private $registry;

	/**
	 * Setup test case.
	 *
	 * @since 0.1.0
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry = new Settings_Registry();
	}

	/**
	 * Test registering a section.
	 *
	 * @since 0.1.0
	 */
	public function test_register_section() {
		$result = $this->registry->register_section(
			'test-section',
			array(
				'title'       => 'Test Section',
				'description' => 'A test section',
			)
		);

		$this->assertTrue( $result, 'Section should register successfully' );
		$this->assertTrue( $this->registry->has_section( 'test-section' ), 'Section should exist in registry' );
	}

	/**
	 * Test registering duplicate section fails.
	 *
	 * @since 0.1.0
	 */
	public function test_register_duplicate_section_fails() {
		$this->registry->register_section(
			'test-section',
			array( 'title' => 'Test Section' )
		);

		$result = $this->registry->register_section(
			'test-section',
			array( 'title' => 'Duplicate Section' )
		);

		$this->assertFalse( $result, 'Duplicate section registration should fail' );
	}

	/**
	 * Test getting a registered section.
	 *
	 * @since 0.1.0
	 */
	public function test_get_section() {
		$this->registry->register_section(
			'test-section',
			array(
				'title'       => 'Test Section',
				'description' => 'A test description',
			)
		);

		$section = $this->registry->get_section( 'test-section' );

		$this->assertIsArray( $section, 'Should return array' );
		$this->assertEquals( 'Test Section', $section['title'], 'Should return correct title' );
		$this->assertEquals( 'A test description', $section['description'], 'Should return correct description' );
	}

	/**
	 * Test getting non-existent section returns null.
	 *
	 * @since 0.1.0
	 */
	public function test_get_nonexistent_section_returns_null() {
		$section = $this->registry->get_section( 'nonexistent-section' );

		$this->assertNull( $section, 'Non-existent section should return null' );
	}

	/**
	 * Test section defaults are applied.
	 *
	 * @since 0.1.0
	 */
	public function test_section_defaults_applied() {
		$this->registry->register_section(
			'test-section',
			array( 'title' => 'Test Section' )
		);

		$section = $this->registry->get_section( 'test-section' );

		$this->assertArrayHasKey( 'description', $section, 'Should have description key' );
		$this->assertArrayHasKey( 'callback', $section, 'Should have callback key' );
		$this->assertArrayHasKey( 'fields', $section, 'Should have fields key' );
		$this->assertArrayHasKey( 'priority', $section, 'Should have priority key' );
		$this->assertEquals( 10, $section['priority'], 'Default priority should be 10' );
		$this->assertIsArray( $section['fields'], 'Fields should be an array' );
	}

	/**
	 * Test getting all sections.
	 *
	 * @since 0.1.0
	 */
	public function test_get_all_sections() {
		$this->registry->register_section( 'section-1', array( 'title' => 'Section 1' ) );
		$this->registry->register_section( 'section-2', array( 'title' => 'Section 2' ) );

		$sections = $this->registry->get_sections();

		$this->assertIsArray( $sections, 'Should return array' );
		$this->assertCount( 2, $sections, 'Should have two sections' );
		$this->assertArrayHasKey( 'section-1', $sections, 'Should contain section-1' );
		$this->assertArrayHasKey( 'section-2', $sections, 'Should contain section-2' );
	}

	/**
	 * Test sections are sorted by priority.
	 *
	 * @since 0.1.0
	 */
	public function test_sections_sorted_by_priority() {
		$this->registry->register_section(
			'section-low',
			array(
				'title'    => 'Low Priority',
				'priority' => 50,
			)
		);
		$this->registry->register_section(
			'section-high',
			array(
				'title'    => 'High Priority',
				'priority' => 5,
			)
		);
		$this->registry->register_section(
			'section-medium',
			array(
				'title'    => 'Medium Priority',
				'priority' => 20,
			)
		);

		$sections = $this->registry->get_sections();
		$keys     = array_keys( $sections );

		$this->assertEquals( 'section-high', $keys[0], 'Highest priority (5) should be first' );
		$this->assertEquals( 'section-medium', $keys[1], 'Medium priority (20) should be second' );
		$this->assertEquals( 'section-low', $keys[2], 'Lowest priority (50) should be third' );
	}

	/**
	 * Test has_section returns true for existing section.
	 *
	 * @since 0.1.0
	 */
	public function test_has_section_returns_true_for_existing_section() {
		$this->registry->register_section( 'test-section', array( 'title' => 'Test' ) );

		$this->assertTrue( $this->registry->has_section( 'test-section' ), 'Should find existing section' );
	}

	/**
	 * Test has_section returns false for non-existent section.
	 *
	 * @since 0.1.0
	 */
	public function test_has_section_returns_false_for_nonexistent_section() {
		$this->assertFalse( $this->registry->has_section( 'nonexistent-section' ), 'Should not find non-existent section' );
	}

	/**
	 * Test unregistering a section.
	 *
	 * @since 0.1.0
	 */
	public function test_unregister_section() {
		$this->registry->register_section( 'test-section', array( 'title' => 'Test' ) );

		$result = $this->registry->unregister_section( 'test-section' );

		$this->assertTrue( $result, 'Should successfully unregister section' );
		$this->assertFalse( $this->registry->has_section( 'test-section' ), 'Section should no longer exist' );
	}

	/**
	 * Test unregistering non-existent section returns false.
	 *
	 * @since 0.1.0
	 */
	public function test_unregister_nonexistent_section_returns_false() {
		$result = $this->registry->unregister_section( 'nonexistent-section' );

		$this->assertFalse( $result, 'Unregistering non-existent section should return false' );
	}

	/**
	 * Test section with custom callback.
	 *
	 * @since 0.1.0
	 */
	public function test_section_with_callback() {
		$callback = function () {
			return 'test output';
		};

		$this->registry->register_section(
			'test-section',
			array(
				'title'    => 'Test',
				'callback' => $callback,
			)
		);

		$section = $this->registry->get_section( 'test-section' );

		$this->assertIsCallable( $section['callback'], 'Callback should be callable' );
		$this->assertEquals( 'test output', call_user_func( $section['callback'] ), 'Callback should return expected value' );
	}

	/**
	 * Test section with fields array.
	 *
	 * @since 0.1.0
	 */
	public function test_section_with_fields() {
		$fields = array(
			array(
				'id'    => 'field-1',
				'label' => 'Field 1',
			),
			array(
				'id'    => 'field-2',
				'label' => 'Field 2',
			),
		);

		$this->registry->register_section(
			'test-section',
			array(
				'title'  => 'Test',
				'fields' => $fields,
			)
		);

		$section = $this->registry->get_section( 'test-section' );

		$this->assertIsArray( $section['fields'], 'Fields should be array' );
		$this->assertCount( 2, $section['fields'], 'Should have 2 fields' );
		$this->assertEquals( 'field-1', $section['fields'][0]['id'], 'First field ID should match' );
	}
}
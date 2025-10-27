<?php
/**
 * Admin settings page template.
 *
 * @package WordPress\AI\Admin
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters the settings page title.
 *
 * @since 0.1.0
 *
 * @param string $title Page title.
 */
$page_title = apply_filters( 'ai_admin_settings_page_title', __( 'AI Experiments', 'ai' ) );

/**
 * Fires before the admin settings page content.
 *
 * @since 0.1.0
 *
 * @param Settings_Registry $registry Registered settings sections.
 */
do_action( 'ai_admin_settings_page_before', $registry );
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>
	<div id="ai-experiments-settings-root"></div>
</div>
<?php
/**
 * Fires after the admin settings page content.
 *
 * @since 0.1.0
 *
 * @param Settings_Registry $registry Registered settings sections.
 */
do_action( 'ai_admin_settings_page_after', $registry );


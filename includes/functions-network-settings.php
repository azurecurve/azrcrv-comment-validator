<?php
/*
	tab output on settings page
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

/**
 * Get options including defaults.
 */
function get_site_option_with_defaults( $option_name ) {

	$defaults = array(
		'min_length'                   => 10,
		'max_length'                   => 500,
		'mod_length'                   => 250,
		'prevent_unreg_using_reg_name' => 1,
		'use_network'                  => 1,
	);

	$options = get_site_option( $option_name, $defaults );

	$options = wp_parse_args( $options, $defaults );

	return $options;

}

/**
 * Display Settings page.
 */
function display_network_options() {
	if ( ! current_user_can( 'manage_network_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'azrcrv-cv' ) );
	}

	// Retrieve plugin configuration options from database.
	$options = get_site_option_with_defaults( PLUGIN_HYPHEN );

	echo '<div id="' . esc_attr( PLUGIN_HYPHEN ) . '-general" class="wrap">';

		echo '<h1>';
			echo '<a href="' . esc_url_raw( DEVELOPER_RAW_LINK ) . esc_attr( PLUGIN_SHORT_SLUG ) . '/"><img src="' . esc_url_raw( plugins_url( '../assets/images/logo.svg', __FILE__ ) ) . '" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
			echo esc_html( get_admin_page_title() );
		echo '</h1>';

	// phpcs:ignore.
	if ( isset( $_GET['settings-updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible">
					<p><strong>' . esc_html__( 'Settings have been saved.', 'azrcrv-cv' ) . '</strong></p>
				</div>';
	}

		require_once 'tab-network-settings.php';
		require_once 'tab-instructions.php';
		require_once 'tab-other-plugins.php';
		require_once 'tabs-output.php';
	?>
		
	</div>
	<?php
}

/**
 * Save settings.
 */
function save_network_options() {
	// Check that user has proper security level.
	if ( ! current_user_can( 'manage_network_options' ) ) {
		wp_die( esc_html__( 'You do not have permissions to perform this action', 'azrcrv-cv' ) );
	}
	// Check that nonce field created in configuration form is present.
	if ( ! empty( $_POST ) && check_admin_referer( PLUGIN_HYPHEN, PLUGIN_HYPHEN . '-nonce' ) ) {

		// Retrieve original plugin options array.
		$options = get_site_option_with_defaults( PLUGIN_HYPHEN );

		$option_name = 'prevent_unreg_using_reg_name';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = 1;
		} else {
			$options[ $option_name ] = 0;
		}

		$option_name = 'min_length';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = sanitize_text_field( intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'max_length';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = sanitize_text_field( intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'mod_length';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = sanitize_text_field( intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'use_network';
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( isset( $_POST[ $option_name ] ) ) {
				$options[ $option_name ] = 1;
			} else {
				$options[ $option_name ] = 0;
			}
		} else {
			$options[ $option_name ] = 0;
		}

		// Store updated options array to database.
		update_site_option( PLUGIN_HYPHEN, $options );

		// Redirect the page to the configuration form that was processed.
		wp_safe_redirect( add_query_arg( 'page', PLUGIN_HYPHEN . '&settings-updated', admin_url( 'settings.php' ) ) );
		exit;
	}
}

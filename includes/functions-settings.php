<?php
/*
	Settings functions - Comment Validator (Enhanced)
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

/**
 * Get options including defaults.
 */
function get_option_with_defaults( $option_name ) {

	$defaults = array(
		'min_length'                   => 10,
		'max_length'                   => 500,
		'mod_length'                   => 250,
		'prevent_unreg_using_reg_name' => 1,
		'honeypot_enabled'             => 0,
		'honeypot_name'                => 'honeypot',
		'time_delay_enabled'           => 0,
		'time_delay_seconds'           => 5,
		'rate_limit_per_minute'        => 5,
		'use_network'                  => 1,
	);

	$options = get_option( $option_name, $defaults );
	$options = wp_parse_args( $options, $defaults );

	return $options;
}

/**
 * Display Settings page.
 */
function display_options() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'azrcrv-cv' ) );
	}

	$options = get_option_with_defaults( PLUGIN_HYPHEN );

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

		require_once 'tab-settings.php';
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
function save_options() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permissions to perform this action', 'azrcrv-cv' ) );
	}
	if ( ! empty( $_POST ) && check_admin_referer( PLUGIN_HYPHEN, PLUGIN_HYPHEN . '-nonce' ) ) {

		$options = get_option_with_defaults( PLUGIN_HYPHEN );

		$option_name = 'prevent_unreg_using_reg_name';
		$options[ $option_name ] = isset( $_POST[ $option_name ] ) ? 1 : 0;

		$option_name = 'min_length';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = abs( intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'max_length';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = abs( intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'mod_length';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = abs( intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'honeypot_enabled';
		$options[ $option_name ] = isset( $_POST[ $option_name ] ) ? 1 : 0;

		$option_name = 'honeypot_name';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = sanitize_key( $_POST[ $option_name ] );
		}

		$option_name = 'time_delay_enabled';
		$options[ $option_name ] = isset( $_POST[ $option_name ] ) ? 1 : 0;

		$option_name = 'time_delay_seconds';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = max( 1, intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'rate_limit_per_minute';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = max( 1, intval( $_POST[ $option_name ] ) );
		}

		$option_name = 'use_network';
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$options[ $option_name ] = isset( $_POST[ $option_name ] ) ? 1 : 0;
		} else {
			$options[ $option_name ] = 0;
		}

		update_option( PLUGIN_HYPHEN, $options );

		// Save scoring weights
		$allowed_weights = [
			'honeypot', 'rate_limit', 'too_fast', 'missing_token',
			'links', 'keywords', 'no_js', 'interaction', 'no_agent',
			'all_caps', 'repeated_chars', 'duplicate', 'spam_email', 'ip_rep',
		];
		$weights = [];
		foreach ( $allowed_weights as $key ) {
			if ( isset( $_POST['cvp_weights'][ $key ] ) ) {
				$weights[ $key ] = max( 0, intval( $_POST['cvp_weights'][ $key ] ) );
			}
		}
		update_option( 'azrcrv-cv-weights', $weights );

		// Save scoring thresholds
		$thresholds = [];
		foreach ( [ 'block', 'moderate' ] as $key ) {
			if ( isset( $_POST['cvp_thresholds'][ $key ] ) ) {
				$thresholds[ $key ] = max( 0, intval( $_POST['cvp_thresholds'][ $key ] ) );
			}
		}
		update_option( 'azrcrv-cv-thresholds', $thresholds );

		wp_safe_redirect( add_query_arg( 'page', PLUGIN_HYPHEN . '&settings-updated', admin_url( 'admin.php' ) ) );
		exit;
	}
}

function maybe_upgrade() {
    $installed = get_option( 'azrcrv_cv_db_version', '0' );
    if ( version_compare( $installed, '3.0.0', '<' ) ) {
        create_ip_reputation_table();
        update_option( 'azrcrv_cv_db_version', '3.0.0' );
    }
}
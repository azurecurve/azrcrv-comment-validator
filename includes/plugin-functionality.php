<?php
/*
	tab output on settings page
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

/**
 * Validate Comment.
 */
function validate_comment( $commentdata ) {

	global $wpdb;

	$options = get_option_with_defaults( PLUGIN_HYPHEN );

	if ( $options['use_network'] == 1 ) {

		$options = get_site_option_with_defaults( PLUGIN_HYPHEN );

	}

	// is username protected?
	if ( $options['prevent_unreg_using_reg_name'] == 1 ) {

		if ( ! is_user_logged_in() ) {

			$sql = "select COUNT(ID) FROM $wpdb->users where user_login = '%s' OR user_nicename = %s OR display_name = %s";

			$is_used = $wpdb->get_var( $wpdb->prepare( $sql, $commentdata['comment_author'], $commentdata['comment_author'], $commentdata['comment_author'] ) );

			if ( $is_used > 0 ) {

				$error = new \WP_Error( 'not_found', '<p><p>' . esc_html__( 'This name is reserved.', 'azrcrv-cv' ) . '</p></p><p><a href="javascript:history.back()">&laquo; ' . esc_html__( 'Back', 'azrcrv-cv' ) . '</a></p>', array( 'response' => '200' ) );

				if ( is_wp_error( $error ) ) {

					wp_die( esc_html( $error->get_error_message() ), '', $error->get_error_data() );

				}
			}
		}
	}

	if ( $options['honeypot_enabled'] == 1 ) {
		
		if ( !empty ( $_POST[ $options['honeypot_name'] ] ) ) {
		
			$error = new \WP_Error( 'not_found', esc_html__( 'There was an error with your comment preventing it from being submitted.', 'azrcrv-cv' ), array( 'response' => '200' ) );

			if ( is_wp_error( $error ) ) {

				wp_die( esc_html( $error->get_error_message() ), '', $error->get_error_data() );

			}
		
		}
		
	}

	if ( $options['time_delay_enabled'] == 1 ) {
		
		$page_load_time = $_POST['page_load_time'];
		$submission_time = time();
		$time_diff = $submission_time - $page_load_time;
		
		if ( $time_diff < $options[ 'time_delay_seconds' ] ) {
		
			$error = new \WP_Error( 'not_found', esc_html__( 'Your comment submission was too fast; please try to resubmit your comment after a short delay.', 'azrcrv-cv' ), array( 'response' => '200' ) );

			if ( is_wp_error( $error ) ) {

				wp_die( esc_html( $error->get_error_message() ), '', $error->get_error_data() );

			}
		
		}
		
	}

	// minimum length met?
	if ( strlen( $commentdata['comment_content'] ) < $options['min_length'] ) {

		$error = new \WP_Error( 'min_length', esc_html__( 'This comment is shorter than the minimum allowed size.', 'azrcrv-cv' ), array( 'response' => '200' ) );

		if ( is_wp_error( $error ) ) {

			wp_die( esc_html( $error->get_error_message() ), '', $error->get_error_data() );

		}

		// maximum length met?
	} elseif ( strlen( $commentdata['comment_content'] ) > $options['max_length'] && $options['max_length'] > 0 ) {

		$error = new \WP_Error( 'max_length', esc_html__( 'This comment is longer than the maximum allowed size.', 'azrcrv-cv' ), array( 'response' => '200' ) );

		if ( is_wp_error( $error ) ) {

			wp_die( esc_html( $error->get_error_message() ), '', $error->get_error_data() );

		}

		// moderation length met?
	} elseif ( strlen( $commentdata['comment_content'] ) > $options['mod_length'] && $options['mod_length'] > 0 ) {

		add_filter( 'pre_comment_approved', 'return_validated_comment', '99', 2 );

	}   

	return $commentdata;
}

/**
 * Return Validated Comment.
 */
function return_validated_comment( $approved, $commentdata ) {

	if ( 'spam' != $approved ) {

		return 0;

	} else {

		return $approved;

	}

}

/**
 * Add honeypot to comment form.
 */
function add_honeypot_field() {
	
	$options = get_option_with_defaults( PLUGIN_HYPHEN );
	
	if ( $options['honeypot_enabled'] == 1 ) {
	
		echo '<div class="azrcrv-cv"><label for="' . esc_attr( $options['honeypot_name'] ) . '">' . esc_attr( $options['honeypot_name'] ) . '</label><input type="text" name="' . esc_attr( $options['honeypot_name'] ) . '" id="' . esc_attr( $options['honeypot_name'] ) . '"></div>';
	
	}
	
	echo ' <input type="hidden" name="page_load_time" value="' . time() . '">';
	
}
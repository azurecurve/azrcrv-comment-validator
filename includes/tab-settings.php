<?php
/*
	Settings tab - Comment Validator (Enhanced)
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

$tab_settings_label = PLUGIN_NAME . ' ' . esc_html__( 'Settings', 'azrcrv-cv' );

$weights    = get_option( 'azrcrv-cv-weights', [] );
$thresholds = get_option( 'azrcrv-cv-thresholds', [] );

$weight_defaults = [
	'honeypot'       => 5,
	'rate_limit'     => 3,
	'too_fast'       => 2,
	'missing_token'  => 2,
	'links'          => 3,
	'keywords'       => 3,
	'no_js'          => 3,
	'interaction'    => 2,
	'no_agent'       => 2,
	'all_caps'       => 2,
	'repeated_chars' => 1,
	'duplicate'      => 4,
	'spam_email'     => 4,
];

$tab_settings = '
<table class="form-table azrcrv-settings">

	<tr>
		<th scope="row" colspan="2">
			<label for="explanation">
				' . esc_html__( 'Comment Validator ensures comments meet certain requirements before they can be submitted.', 'azrcrv-cv' ) . '
			</label>
		</th>
	</tr>';

if ( ! function_exists( 'is_multisite' ) && is_multisite() ) {

	$tab_settings .= '
	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Network Settings', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row">' . esc_html__( 'Use network settings?', 'azrcrv-cv' ) . '</th>
		<td>
			<input name="use_network" type="checkbox" id="use_network" value="1" ' . checked( '1', $options['use_network'], false ) . ' />
			<label for="use_network">' . esc_html__( 'Use network settings?', 'azrcrv-cv' ) . '</label>
			<p><span class="description">' . esc_html__( 'If marked, the settings below will be ignored in preference of network settings', 'azrcrv-cv' ) . '</span></p>
		</td>
	</tr>';
}

$tab_settings .= '

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Protect Usernames', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row">' . esc_html__( 'Protect Registered Usernames?', 'azrcrv-cv' ) . '</th>
		<td>
			<input name="prevent_unreg_using_reg_name" type="checkbox" id="prevent_unreg_using_reg_name" value="1" ' . checked( '1', $options['prevent_unreg_using_reg_name'], false ) . ' />
			<label for="prevent_unreg_using_reg_name">' . esc_html__( 'Prevent unregistered users using name of registered user?', 'azrcrv-cv' ) . '</label>
			<p><span class="description">' . esc_html__( 'Prevents unregistered user using name of registered user', 'azrcrv-cv' ) . '</span></p>
		</td>
	</tr>

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Comment Length', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row"><label for="min_length">' . esc_html__( 'Minimum Length', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="min_length" type="number" step="1" min="0" id="min_length" value="' . esc_attr( $options['min_length'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Minimum comment length; set to 0 for no minimum.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="max_length">' . esc_html__( 'Maximum Length', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="max_length" type="number" step="1" min="0" id="max_length" value="' . esc_attr( $options['max_length'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Maximum comment length; set to 0 for no maximum.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="mod_length">' . esc_html__( 'Moderation Length', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="mod_length" type="number" step="1" min="0" id="mod_length" value="' . esc_attr( $options['mod_length'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Moderation comment length; set to 0 for no moderation.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Honeypot', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row">' . esc_html__( 'Enable honeypot?', 'azrcrv-cv' ) . '</th>
		<td>
			<input name="honeypot_enabled" type="checkbox" id="honeypot_enabled" value="1" ' . checked( '1', $options['honeypot_enabled'], false ) . ' />
			<label for="honeypot_enabled">' . esc_html__( 'Enable honeypot?', 'azrcrv-cv' ) . '</label>
			<p><span class="description">' . esc_html__( 'Adds honeypot field to comment form.', 'azrcrv-cv' ) . '</span></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="honeypot_name">' . esc_html__( 'Honeypot Field Name', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="honeypot_name" type="text" id="honeypot_name" value="' . esc_attr( $options['honeypot_name'] ) . '" class="regular-text" />
			<p class="description">' . esc_html__( 'Name of the hidden honeypot field. Avoid generic names like "website" that bots may intentionally leave blank.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Time Delay', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row">' . esc_html__( 'Enable time delay?', 'azrcrv-cv' ) . '</th>
		<td>
			<input name="time_delay_enabled" type="checkbox" id="time_delay_enabled" value="1" ' . checked( '1', $options['time_delay_enabled'], false ) . ' />
			<label for="time_delay_enabled">' . esc_html__( 'Enable time delay?', 'azrcrv-cv' ) . '</label>
			<p><span class="description">' . esc_html__( 'Enforces a delay between page load and comment submission.', 'azrcrv-cv' ) . '</span></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="time_delay_seconds">' . esc_html__( 'Time Delay in Seconds', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="time_delay_seconds" type="number" step="1" min="1" id="time_delay_seconds" value="' . esc_attr( $options['time_delay_seconds'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Minimum seconds before a comment can be submitted.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Rate Limiting', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row"><label for="rate_limit_per_minute">' . esc_html__( 'Submissions per Minute', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="rate_limit_per_minute" type="number" step="1" min="1" id="rate_limit_per_minute" value="' . esc_attr( $options['rate_limit_per_minute'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Maximum comment submissions allowed per IP per minute before scoring penalty applies.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Scoring Thresholds', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<th scope="row"><label for="cvp_threshold_moderate">' . esc_html__( 'Moderation Threshold', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="cvp_thresholds[moderate]" type="number" step="1" min="0" id="cvp_threshold_moderate" value="' . esc_attr( $thresholds['moderate'] ?? 3 ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Score at or above this value sends the comment to moderation.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="cvp_threshold_block">' . esc_html__( 'Spam Threshold', 'azrcrv-cv' ) . '</label></th>
		<td>
			<input name="cvp_thresholds[block]" type="number" step="1" min="0" id="cvp_threshold_block" value="' . esc_attr( $thresholds['block'] ?? 7 ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Score at or above this value marks the comment as spam.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>

	<tr>
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Scoring Weights', 'azrcrv-cv' ) . '</h2>
		</th>
	</tr>
	<tr>
		<td colspan=2>
			<p class="description">' . esc_html__( 'Each weight is added to the total score when that check fails. Adjust to tune sensitivity.', 'azrcrv-cv' ) . '</p>
		</td>
	</tr>';

$weight_labels = [
	'honeypot'       => __( 'Honeypot triggered', 'azrcrv-cv' ),
	'rate_limit'     => __( 'Rate limit exceeded', 'azrcrv-cv' ),
	'too_fast'       => __( 'Submitted too fast', 'azrcrv-cv' ),
	'missing_token'  => __( 'Missing time token', 'azrcrv-cv' ),
	'links'          => __( 'Excessive links (>2)', 'azrcrv-cv' ),
	'keywords'       => __( 'Spam keywords detected', 'azrcrv-cv' ),
	'no_js'          => __( 'No JavaScript detected', 'azrcrv-cv' ),
	'interaction'    => __( 'No/fast user interaction', 'azrcrv-cv' ),
	'no_agent'       => __( 'Missing user agent', 'azrcrv-cv' ),
	'all_caps'       => __( 'Excessive capitals (>60%)', 'azrcrv-cv' ),
	'repeated_chars' => __( 'Repeated characters (7+)', 'azrcrv-cv' ),
	'duplicate'      => __( 'Duplicate comment', 'azrcrv-cv' ),
	'spam_email'     => __( 'Disposable email domain', 'azrcrv-cv' ),
];

foreach ( $weight_labels as $key => $label ) {
	$val          = isset( $weights[ $key ] ) ? (int) $weights[ $key ] : $weight_defaults[ $key ];
	$tab_settings .= '
	<tr>
		<th scope="row"><label for="cvp_weight_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>
		<td>
			<input name="cvp_weights[' . esc_attr( $key ) . ']" type="number" step="1" min="0" max="10"
				id="cvp_weight_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" class="small-text" />
		</td>
	</tr>';
}

$tab_settings .= '
</table>';

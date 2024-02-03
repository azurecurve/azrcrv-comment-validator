<?php
/*
	other plugins tab on settings page
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

/**
 * Instructions tab.
 */
$tab_instructions_label = esc_html__( 'Instructions', 'azrcrv-cv' );
$tab_instructions       = '
<table class="form-table azrcrv-settings">

	<tr>
	
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			
				<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Protect Usernames', 'azrcrv-cv' ) . '</h2>
			
		</th>

	</tr>';


if ( ! function_exists( 'is_multisite' ) && is_multisite() ) {

	$tab_instructions .= '

	<tr>
	
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			
				<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Network Settings', 'azrcrv-cv' ) . '</h2>
			
		</th>

	</tr>
	
	<tr>
	
		<td scope="row" colspan=2>
		
			<p>' .

				esc_html__( 'Mark this box to use network defined settings for comment validation instead of the local site settings; if this option is marked, the other local settings are ignored.', 'azrcrv-cv' ) . '
			</p>
			
		</td>
		
	</tr>';
}

$tab_instructions .= '
	<tr>
	
		<td scope="row" colspan=2>
		
			<p>' .

				esc_html__( 'Standard functionality allows users to use a registered when submitting their comment; this setting allows you to prevent this.', 'azrcrv-cv' ) . '
					
			</p>
		
		</td>
	
	</tr>

	<tr>
	
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			
				<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Comment Length', 'azrcrv-cv' ) . '</h2>
			
		</th>

	</tr>

	<tr>
	
		<td scope="row" colspan=2>
		
			<p>' .

				esc_html__( 'Settings can be configured for minimum and maximum comment lengths which are permitted; you can also specify a comment length over which a comment must be moderated.', 'azrcrv-cv' ) . '
			</p>
			
		</td>
	
	</tr>

	<tr>
	
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			
				<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Honeypot', 'azrcrv-cv' ) . '</h2>
			
		</th>

	</tr>

	<tr>
	
		<td scope="row" colspan=2>
		
			<p>' .

				esc_html__( 'Settings can be configured to add a honeypot field to the comment form; the name of the honeypot field can be set as desired.', 'azrcrv-cv' ) . '
			</p>
		
			<p>' .

				esc_html__( 'The comment will be rejected if the honeypot field is not blank.', 'azrcrv-cv' ) . '
			</p>
			
		</td>
	
	</tr>

	<tr>
	
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			
				<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Time Delay', 'azrcrv-cv' ) . '</h2>
			
		</th>

	</tr>

	<tr>
	
		<td scope="row" colspan=2>
		
			<p>' .

				esc_html__( 'Settings can be configured to enforce a delay between page load and comment submission.', 'azrcrv-cv' ) . '
			</p>
			
		</td>
	
	</tr>
	
</table>';

<?php
/*
	other plugins tab on settings page
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

/**
 * Settings tab.
 */

$tab_settings_label = PLUGIN_NAME . ' ' . esc_html__( 'Settings', 'azrcrv-cv' );
$tab_settings       = '
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
	
		<th scope="row">
		
			' . esc_html__( 'Use network settings?', 'azrcrv-cv' ) . '
			
		</th>
		
		<td>
			
			<input name="use_network" type="checkbox" id="use_network" value="1" ' . checked( '1', $options['use_network'], false ) . ' />
			
			<label for="use_network">
				' . esc_html__( 'Use network settings?', 'azrcrv-cv' ) . '
			</label>
			
			<p>
				<span class="description">
					' . esc_html__( 'If marked, the settings below will be ignored in preference of network settings', 'azrcrv-cv' ) . '
				</span>
			</p>
			
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
	
		<th scope="row">
		
			' . esc_html__( 'Protect Registered Usernames?', 'azrcrv-cv' ) . '
			
		</th>
		
		<td>
			
			<input name="prevent_unreg_using_reg_name" type="checkbox" id="prevent_unreg_using_reg_name" value="1" ' . checked( '1', $options['prevent_unreg_using_reg_name'], false ) . ' />
			
			<label for="prevent_unreg_using_reg_name">
				' . esc_html__( 'Prevent unregistered users using name of registered user?', 'azrcrv-cv' ) . '
			</label>
			
			<p>
				<span class="description">
					' . esc_html__( 'Prevents unregistered user using name of registered user', 'azrcrv-cv' ) . '
				</span>
			</p>
			
		</td>
		
	</tr>

	<tr>
	
		<th scope="row" colspan=2 class="azrcrv-settings-section-heading">
			
				<h2 class="azrcrv-settings-section-heading">' . esc_html__( 'Comment Length Settings', 'azrcrv-cv' ) . '</h2>
			
		</th>

	</tr>

	<tr>
	
		<th scope="row">
		
			<label for="min_length">
			
				' . esc_html__( 'Minimum Length', 'azrcrv-cv' ) . '
				
			</label>
			
		</th>
		
		<td>
		
			<input name="min_length" type="number" step="1" min="0" id="min_length" value="' . esc_attr( $options['min_length'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Minimum comment length; set to 0 for no minimum.', 'azrcrv-cv' ) . '</p>
			
		</td>
		
	</tr>

	<tr>
	
		<th scope="row">
		
			<label for="max_length">
			
				' . esc_html__( 'Maximum Length', 'azrcrv-cv' ) . '
				
			</label>
			
		</th>
		
		<td>
		
			<input name="max_length" type="number" step="1" min="0" id="max_length" value="' . esc_attr( $options['max_length'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Minimum comment length; set to 0 for no maximum.', 'azrcrv-cv' ) . '</p>
			
		</td>
		
	</tr>

	<tr>
	
		<th scope="row">
		
			<label for="mod_length">
			
				' . esc_html__( 'Moderation Length', 'azrcrv-cv' ) . '
				
			</label>
			
		</th>
		
		<td>
		
			<input name="mod_length" type="number" step="1" min="0" id="mod_length" value="' . esc_attr( $options['mod_length'] ) . '" class="small-text" />
			<p class="description">' . esc_html__( 'Moderation comment length; set to 0 for no moderation.', 'azrcrv-cv' ) . '</p>
			
		</td>
		
	</tr>

</table>';

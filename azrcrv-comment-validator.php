<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name: Comment Validator
 * Description: Checks comment to ensure they are longer than the minimum, shorter than the maximum and also allows comments to be forced into moderation based on length.
 * Version: 1.3.1
 * Author: azurecurve
 * Author URI: https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI: https://development.azurecurve.co.uk/classicpress-plugins/comment-validator/
 * Text Domain: comment-validator
 * Domain Path: /languages
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

// include plugin menu
require_once(dirname( __FILE__).'/pluginmenu/menu.php');
add_action('admin_init', 'azrcrv_create_plugin_menu_cv');

// include update client
require_once(dirname(__FILE__).'/libraries/updateclient/UpdateClient.class.php');

/**
 * Setup actions, filters and shortcodes.
 *
 * @since 1.0.0
 *
 */
// add actions
add_action('admin_menu', 'azrcrv_cv_create_admin_menu');
add_action('admin_post_azrcrv_cv_save_options', 'azrcrv_cv_save_options');
add_action('network_admin_menu', 'azrcrv_cv_create_network_admin_menu');
add_action('network_admin_edit_azrcrv_cv_save_network_options', 'azrcrv_cv_save_network_options');
add_action('plugins_loaded', 'azrcrv_cv_load_languages');

// add filters
add_filter('plugin_action_links', 'azrcrv_cv_add_plugin_action_link', 10, 2);
add_filter('preprocess_comment' , 'azrcrv_cv_validate_comment', 20);
add_filter('codepotent_update_manager_image_path', 'azrcrv_cv_custom_image_path');
add_filter('codepotent_update_manager_image_url', 'azrcrv_cv_custom_image_url');

/**
 * Load language files.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_load_languages() {
    $plugin_rel_path = basename(dirname(__FILE__)).'/languages';
    load_plugin_textdomain('comment-validator', false, $plugin_rel_path);
}

/**
 * Custom plugin image path.
 *
 * @since 1.2.0
 *
 */
function azrcrv_cv_custom_image_path($path){
    if (strpos($path, 'azrcrv-comment-validator') !== false){
        $path = plugin_dir_path(__FILE__).'assets/pluginimages';
    }
    return $path;
}

/**
 * Custom plugin image url.
 *
 * @since 1.2.0
 *
 */
function azrcrv_cv_custom_image_url($url){
    if (strpos($url, 'azrcrv-comment-validator') !== false){
        $url = plugin_dir_url(__FILE__).'assets/pluginimages';
    }
    return $url;
}

/**
 * Get options including defaults.
 *
 * @since 1.2.0
 *
 */
function azrcrv_cv_get_option($option_name){
 
	$defaults = array(
						'min_length' => 10,
						'max_length' => 500,
						'mod_length' => 250,
						'prevent_unreg_using_reg_name' => 1,
						'use_network' => 1,
					);

	$options = get_option($option_name, $defaults);

	$options = wp_parse_args($options, $defaults);

	return $options;

}

/**
 * Add Comment Validator action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.admin_url('admin.php?page=azrcrv-cv').'"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-top: 2px; margin-right: -5px; height: 16px; width: 16px;" alt="azurecurve" />'.esc_html__('Settings' ,'comment-validator').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_create_admin_menu(){
	//global $admin_page_hooks;
	
	add_submenu_page("azrcrv-plugin-menu"
						,esc_html__("Comment Validator Settings", "comment-validator")
						,esc_html__("Comment Validator", "comment-validator")
						,'manage_options'
						,'azrcrv-cv'
						,'azrcrv_cv_display_options');
}

/**
 * Display Settings page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_display_options(){
	if (!current_user_can('manage_options')){
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'comment-validator'));
    }
	
	// Retrieve plugin configuration options from database
	$options = azrcrv_cv_get_option('azrcrv-cv');
	?>
	<div id="azrcrv-cv-general" class="wrap">
		<fieldset>
			<h1>
				<?php
					echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
					esc_html_e(get_admin_page_title());
				?>
			</h1>
			
			<?php if(isset($_GET['settings-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Settings have been saved.', 'comment-validator'); ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
				
				<input type="hidden" name="action" value="azrcrv_cv_save_options" />
				<input name="page_options" type="hidden" value="min_length,max_length,mod_length,use_network" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-cv', 'azrcrv-cv-nonce'); ?>
				<table class="form-table">
				
				<?php
				if (!function_exists('is_multisite') && is_multisite()){
				?>
				<tr><th scope="row"><?php esc_html_e('Use Network Settings', 'comment-validator'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span><?php esc_html_e("Use Network Settings", "comment-validator"); ?></span></legend>
					<label for="use_network"><input name="use_network" type="checkbox" id="use_network" value="1" <?php checked('1', $options['use_network']); ?> /><?php esc_html_e('Settings below will be ignored in preference of network settings.', 'comment validator'); ?></label>
					</fieldset>
				</td></tr>
				<?php
				}
				?>
				
				<tr><th scope="row"><?php esc_html_e('Prevent unreg user using name of registered user?', 'comment-validator'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span><?php esc_html_e("Prevent unreg user using name of registered user", "comment-validator"); ?></span></legend>
					<label for="prevent_unreg_using_reg_name"><input name="prevent_unreg_using_reg_name" type="checkbox" id="prevent_unreg_using_reg_name" value="1" <?php checked('1', $options['prevent_unreg_using_reg_name']); ?> /><?php esc_html_e('Prevents unregistered user using name of registered user..', 'comment validator'); ?></label>
					</fieldset>
				</td></tr>
				
				<tr><th scope="row"><label for="min_length"><?php esc_html_e('Minimum Length', 'comment-validator'); ?></label></th><td>
					<input type="text" name="min_length" value="<?php echo esc_html(stripslashes($options['min_length'])); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Minimum comment length; set to 0 for no minimum.', 'comment-validator'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><label for="max_length"><?php esc_html_e('Maximum Length', 'comment-validator'); ?></label></th><td>
					<input type="text" name="max_length" value="<?php echo esc_html(stripslashes($options['max_length'])); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Maximum comment length; set to 0 for no maximum.', 'comment-validator'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><label for="mod_length"><?php esc_html_e('Moderation Length', 'comment-validator'); ?></label></th><td>
					<input type="text" name="mod_length" value="<?php echo esc_html(stripslashes($options['mod_length'])); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Moderation comment length; set to 0 for no moderation.', 'comment-validator'); ?></p>
				</td></tr>
				
				</table>
				<input type="submit" value="Save Changes" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_save_options(){
	// Check that user has proper security level
	echo 'here';
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'comment-validator'));
	}
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azrcrv-cv', 'azrcrv-cv-nonce')){
	
		// Retrieve original plugin options array
		$options = get_option('azrcrv-cv');
		
		$option_name = 'prevent_unreg_using_reg_name';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'min_length';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'max_length';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'mod_length';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'use_network';
		if (function_exists('is_multisite') && is_multisite()){
			if (isset($_POST[$option_name])){
				$options[$option_name] = 1;
			}else{
				$options[$option_name] = 0;
			}
		}else{
			$options[$option_name] = 0;
		}
		
		// Store updated options array to database
		update_option('azrcrv-cv', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azrcrv-cv&settings-updated', admin_url('admin.php')));
		exit;
	}
}

/**
 * Add to Network menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_create_network_admin_menu(){
	if (function_exists('is_multisite') && is_multisite()){
		add_submenu_page(
					'settings.php'
					,esc_html__("Comment Validator Settings", "comment-validator")
					,esc_html__("Comment Validator", "comment-validator")
					,'manage_network_options'
					,'azrcrv-cv'
					,'azrcrv_cv_network_settings'
					);
	}
}

/**
 * Display network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_network_settings(){
	if(!current_user_can('manage_network_options')) wp_die(esc_html__('You do not have permissions to perform this action', 'azurecurve-comment-validator'));
	$options = get_site_option('azrcrv-cv');

	?>
	<div id="azrcrv-cv-general" class="wrap">
		<fieldset>
			<h1>
				<?php
					echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
					esc_html_e(get_admin_page_title());
				?>
			</h1>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azrcrv_cv_save_network_options" />
				<input name="page_options" type="hidden" value="smallest, largest, number" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-cv', 'azrcrv-cv-nonce'); ?>
				<table class="form-table">
				
				<tr><th scope="row"><?php esc_html_e('Prevent unreg user using name of registered user?', 'comment-validator'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span><?php esc_html_e("Prevent unreg user using name of registered user", "comment-validator"); ?></span></legend>
					<label for="prevent_unreg_using_reg_name"><input name="prevent_unreg_using_reg_name" type="checkbox" id="prevent_unreg_using_reg_name" value="1" <?php checked('1', $options['prevent_unreg_using_reg_name']); ?> /><?php esc_html_e('Prevents unregistered user using name of registered user..', 'comment validator'); ?></label>
					</fieldset>
				</td></tr>
				
				<tr><th scope="row"><label for="min_length"><?php esc_html_e('Minimum Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="min_length" value="<?php echo esc_html(stripslashes($options['min_length'])); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Minimum comment length; set to 0 for no minimum', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><label for="max_length"><?php esc_html_e('Maximum Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="max_length" value="<?php echo esc_html(stripslashes($options['max_length'])); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Maximum comment length; set to 0 for no maximum', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><label for="mod_length"><?php esc_html_e('Moderation Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="mod_length" value="<?php echo esc_html(stripslashes($options['mod_length'])); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Moderation comment length; set to 0 for no moderation', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				
				</table>
				<input type="submit" value="Save Changes" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_save_network_options(){     
	if(!current_user_can('manage_network_options')) wp_die(esc_html__('You do not have permissions to perform this action', 'azurecurve-comment-validator'));
	if (! empty($_POST) && check_admin_referer('azrcrv-cv', 'azrcrv-cv-nonce')){
		// Retrieve original plugin options array
		$options = get_site_option('azrcrv-cv');
		
		$option_name = 'prevent_unreg_using_reg_name';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'min_length';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'max_length';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'mod_length';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		update_site_option('azrcrv-cv', $options);

		wp_redirect(network_admin_url('settings.php?page=azrcrv-cv&settings-updated'));
		exit;  
	}
}

/**
 * Validate Comment.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_validate_comment($commentdata){
	$options = azrcrv_cv_get_option('azrcrv-cv');
	if ($options['use_network'] == 1){
		$options = get_site_option('azrcrv-cv');
	}
	
	if ($options['prevent_unreg_using_reg_name'] == 1){
		if (!is_user_logged_in()){
			global $wpdb;
			
			$sql =  "select COUNT(ID) FROM $wpdb->users where user_login = '%s' OR user_nicename = %s OR display_name = %s";
			
			$is_used = $wpdb->get_var($wpdb->prepare($sql, $commentdata['comment_author'], $commentdata['comment_author'], $commentdata['comment_author']));
			
			if ($is_used > 0){
				$error = new WP_Error('not_found', '<p><p>'.esc_html__('This name is reserved.' , 'comment-validator').'</p></p><p><a href="javascript:history.back()">&laquo; '.esc_html__('Back', 'comment-validator').'</a></p>', array('response' => '200'));
				if(is_wp_error($error)){
					wp_die($error, '', $error->get_error_data());
				}
			}
		}
	}
	
	if (strlen($commentdata['comment_content']) < $options['min_length']){
		$error = new WP_Error('not_found', esc_html__('This comment is shorter than the minimum allowed size.' , 'comment-validator'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
	}elseif (strlen($commentdata['comment_content']) > $options['max_length'] && $options['max_length'] > 0){
		$error = new WP_Error('not_found', esc_html__('This comment is longer than the maximum allowed size.', 'comment-validator'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
	}elseif (strlen($commentdata['comment_content']) > $options['mod_length'] && $options['mod_length'] > 0){
		add_filter('pre_comment_approved', 'azrcrv_cv_return_validated_comment', '99', 2);
	}
    return $commentdata;
}

/**
 * Return Validated Comment.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cv_return_validated_comment($approved, $commentdata){
	if ('spam' != $approved) return 0;
	else return $approved;
}

?>
<?php
/*
Plugin Name: azurecurve Floating Featured Image
Plugin URI: http://wordpress.azurecurve.co.uk/plugins/floating-featured-image/
Description: Shortcode allowing a floating featured image to be placed at the top of a post
Version: 1.0.1
Author: azurecurve
Author URI: http://wordpress.azurecurve.co.uk/

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt
 */

register_activation_hook( __FILE__, 'azc_ffi_set_default_options' );

function azc_ffi_set_default_options($networkwide) {
	
	$new_options = array(
				'default_path' => plugin_dir_url(__FILE__).'images/',
				'default_image' => '',
				'default_title' => '',
				'default_alt' => '',
				'default_taxonomy' => '',
				'default_taxonomy_is_tag' => 0
			);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			global $wpdb;

			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				if ( get_option( 'azc_ffi_options' ) === false ) {
					add_option( 'azc_ffi_options', $new_options );
				}
			}

			switch_to_blog( $original_blog_id );
		}else{
			if ( get_option( 'azc_ffi_options' ) === false ) {
				add_option( 'azc_ffi_options', $new_options );
			}
		}
	}
	//set defaults for single site
	else{
		if ( get_option( 'azc_ffi_options' ) === false ) {
			add_option( 'azc_ffi_options', $new_options );
		}
	}
}

add_shortcode( 'featured-image', 'azc_floating_featurd_image' );
add_action('wp_enqueue_scripts', 'azc_ffi_load_css');

function azc_ffi_load_css(){
	wp_enqueue_style( 'azurecurve-floating-featured-image', plugins_url( 'style.css', __FILE__ ), '', '1.0.0' );
}

function azc_floating_featurd_image($atts, $content = null) {
	// Retrieve plugin configuration options from database
	$options = get_option( 'azc_ffi_options' );
	
	extract(shortcode_atts(array(
		'path' => stripslashes($options['default_path']),
		'image' => stripslashes($options['default_image']),
		'title' => stripslashes($options['default_title']),
		'alt' => stripslashes($options['default_alt']),
		'taxonomy' => stripslashes($options['default_taxonomy']),
		'is_tag' => 0
	), $atts));
		
	$output = "<span class='azc_ffi'>";
	if (strlen($taxonomy) > 0 and $is_tag == 0){
		$category_url = get_category_link(get_cat_ID($taxonomy));
		if (strlen($category_url) == 0){ // if taxonomy not name then check if slug
			$category = get_term_by('slug', $taxonomy, 'category');
			$category_url = get_category_link(get_cat_ID($category->name));
		}
		$output .= "<a href='$category_url'>";
	}elseif (strlen($taxonomy) > 0){
		$tag = get_term_by('name', $taxonomy, 'post_tag');
		$tag_url = get_tag_link($tag->term_id);
		if (strlen($tag_url) == 0){ // if taxonomy not name then check if slug
			$tag = get_term_by('slug', $taxonomy, 'post_tag');
			$tag_url = get_tag_link($tag->term_id);
		}
		$output .= "<a href='$tag_url'>";
	}
	$output .= "<img src='$path$image' title='$title' alt='$alt' />";
	if (strlen($taxonomy) > 0){
		$output .= "</a>";
	}
	$output .= "</span>";
	
	if (strlen($image) == 0){
		$output = '';
	}
	
	return $output;
}

add_filter('plugin_action_links', 'azc_ffi_plugin_action_links', 10, 2);

function azc_ffi_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=azurecurve-featured-floating-image">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}


add_action( 'admin_menu', 'azc_ffi_settings_menu' );

function azc_ffi_settings_menu() {
	add_options_page( 'azurecurve Floating Featured Image Settings',
	'azurecurve Floating Featured Image', 'manage_options',
	'azurecurve-floating-featured-image', 'azc_ffi_config_page' );
}

function azc_ffi_config_page() {
	if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option( 'azc_ffi_options' );
	?>
	<div id="azc-ffi-general" class="wrap">
		<fieldset>
			<h2>azurecurve Floating Featured Image Configuration</h2>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_ffi_options" />
				<input name="page_options" type="hidden" value="default_path, default_image, default_title default_alt, default_taxonomy_is_tag, default_taxonomy" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_ffi' ); ?>
				<table class="form-table">
				<tr><td colspan=2>
					<p>Set the default path for where you will be storing the images; default is to the plugin/images folder.</p>
					
					<p>Use the &#91;featured-image&#93; shortcode to place the image in a post or on a page. With the default stylesheet it will float to the right.</p>
					
					<p>Add image attribute to use an image other than the default; title and alt attributes can also be set to override the defaults.</p>
					
					<p>Add is_tag=1 attribute to use the tag instead of the category taxonomy.</p>
					
					<p>Add taxonomy attribute to have the image hyperlinked (category will be used if both are supplied).</p>
				</td></tr>
				<tr><th scope="row"><label for="width">Default Path</label></th><td>
					<input type="text" name="default_path" value="<?php echo esc_html( stripslashes($options['default_path']) ); ?>" class="regular-text" />
					<p class="description">Set default folder for images</p>
				</td></tr>
				<tr><th scope="row"><label for="width">Default Image</label></th><td>
					<input type="text" name="default_image" value="<?php echo esc_html( stripslashes($options['default_image']) ); ?>" class="regular-text" />
					<p class="description">Set default image used when no img attribute set</p>
				</td></tr>
				<tr><th scope="row"><label for="width">Default Title</label></th><td>
					<input type="text" name="default_title" value="<?php echo esc_html( stripslashes($options['default_title']) ); ?>" class="regular-text" />
					<p class="description">Set default title for image</p>
				</td></tr>
				<tr><th scope="row"><label for="width">Default Alt</label></th><td>
					<input type="text" name="default_alt" value="<?php echo esc_html( stripslashes($options['default_alt']) ); ?>" class="regular-text" />
					<p class="description">Set default alt text for image</p>
				</td></tr>
				<tr><th scope="row">Default Taxonomy Is Tag</th><td>
					<fieldset><legend class="screen-reader-text"><span>Default Taxonomy Is Tag</span></legend>
					<label for="enable_header"><input name="enable_header" type="checkbox" id="enable_header" value="1" <?php checked( '1', $options['default_taxonomy_is_tag'] ); ?> />Default Taxonomy Is Tag?</label>
					</fieldset>
				</td></tr>
				<tr><th scope="row"><label for="width">Default Taxonomy</label></th><td>
					<input type="text" name="default_taxonomy" value="<?php echo esc_html( stripslashes($options['default_taxonomy']) ); ?>" class="regular-text" />
					<p class="description">Set default taxonomy to hyperlink image (default is to use category unless Is Tag is marked)</p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
<?php }

add_action( 'admin_init', 'azc_ffi_admin_init' );

function azc_ffi_admin_init() {
	add_action( 'admin_post_save_azc_ffi_options', 'process_azc_ffi_options' );
}

function process_azc_ffi_options() {
	/*	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) ){
		wp_die( 'Not allowed' );
	}
	// Check that nonce field created in configuration form is present
	check_admin_referer( 'azc_ffi' );*/
	settings_fields('azc_ffi');
	
	// Retrieve original plugin options array
	$options = get_option( 'azc_ffi_options' );
	
	$option_name = 'default_path';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	$option_name = 'default_image';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	$option_name = 'default_title';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	$option_name = 'default_alt';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	$option_name = 'default_taxonomy_is_set';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = 1;
	}else{
		$options[$option_name] = 0;
	}
	
	$option_name = 'default_taxonomy';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	// Store updated options array to database
	update_option( 'azc_ffi_options', $options );
	
	// Redirect the page to the configuration form that was processed
	wp_redirect( add_query_arg( 'page', 'azurecurve-floating-featured-image', admin_url( 'options-general.php' ) ) );
	exit;
}

?>
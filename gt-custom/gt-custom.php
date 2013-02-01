<?php
/*
Plugin Name: GT Theme Customizer Preview for Guests
Plugin URI: http://green.cx
Description: Allows guests to preview theme options
Version: 1.01
Author: Jason Green
Author URI: http://green.cx/

    Copyright 2013 Jason Green (http://green.cx)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (isset($_GET['gtlo'])) {
	//TODO: Fix the static path here
	require('/var/www/citycx/public/wp-blog-header.php');
	$user_login = 'test';
	$user = get_userdatabylogin($user_login);
	$user_id = $user->ID;
	wp_set_current_user(user_id, $user_login);
	wp_set_auth_cookie($user_id);
	do_action('wp_login', $user_login);
	wp_redirect(plugins_url('/includes/gt-customize.php' , __FILE__ ));
	exit;
}

$gt_user= new WP_User( null, 'test' );
$gt_user->add_cap('edit_theme_options');
/**
 * Includes and instantiates the WP_Customize_Manager class.
 *
 * Fires when ?wp_customize=on or on wp-admin/customize.php.
 *
 * @since 3.4.0
 */
 
 
function _gt_wp_customize_include() {
	if ( ! ( ( isset( $_REQUEST['gt_customize'] ) && 'on' == $_REQUEST['wp_customize'] )
		|| ( 'gt-customize.php' == basename( $_SERVER['PHP_SELF'] ) )
	) )
		return;

	require( ABSPATH . WPINC . '/class-wp-customize-manager.php' );
	// Init Customize class
	$GLOBALS['wp_customize'] = new WP_Customize_Manager;
}
add_action( 'plugins_loaded', '_gt_wp_customize_include' );

/**
 * Adds settings for the customize-loader script.
 *
 * @since 3.4.0
 */
function _gt_wp_customize_loader_settings() {
	global $wp_scripts;

	$admin_origin = parse_url( admin_url() );
	$home_origin  = parse_url( home_url() );
	$cross_domain = ( strtolower( $admin_origin[ 'host' ] ) != strtolower( $home_origin[ 'host' ] ) );

	$browser = array(
		'mobile' => wp_is_mobile(),
		'ios'    => wp_is_mobile() && preg_match( '/iPad|iPod|iPhone/', $_SERVER['HTTP_USER_AGENT'] ),
	);

	$settings = array(
		'url'           => esc_url( plugins_url() . 'includes/gt-customize.php'  ),
		'isCrossDomain' => $cross_domain,
		'browser'       => $browser,
	);

	$script = 'var _wpCustomizeLoaderSettings = ' . json_encode( $settings ) . ';';

	$data = $wp_scripts->get_data( 'customize-loader', 'data' );
	if ( $data )
		$script = "$data\n$script";

	$wp_scripts->add_data( 'customize-loader', 'data', $script );
}
//add_action( 'admin_enqueue_scripts', '_gt_wp_customize_loader_settings' );

/**
 * Returns a URL to load the theme customizer.
 *
 * @since 3.4.0
 *
 * @param string $stylesheet Optional. Theme to customize. Defaults to current theme.
 * 	The theme's stylesheet will be urlencoded if necessary.
 */
function gt_wp_customize_url( $stylesheet = null ) {
	$url = plugins_url('/includes/gt-customize.php' , __FILE__ );
	if ( $stylesheet )
		$url .= '?theme=' . urlencode( $stylesheet );
	return esc_url( $url );
}


/**
 *	Expose the Customizer Preview by adding a link in the admin bar.
 */
add_action ('admin_bar_menu', 'gt_customize_menu');
function gt_customize_menu($admin_bar) {
	
	$admin_bar->add_menu( array (
	'id' => 'customizer-preview',
	'title' => 'Customizer Preview',
	'href' => plugins_url('/includes/gt-customize.php' , __FILE__ ),
	'meta' => array(
		'title' => __('Greenth.me Customizer Preview'),
		),
	));
}

//IF the test user tries to view admin, take them back home
function gt_restrict_admin_with_redirect() {
	if (!current_user_can('manage_options') && $_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php' && $_SERVER['PHP_SELF'] != '/wp-admin/admin.php' && $_SERVER['PHP_SELF'] != '/wp-content/plugins/gt-custom/includes/gt-customize.php' ) {
			wp_redirect(site_url() ); exit;
	}
}
add_action('admin_init', 'gt_restrict_admin_with_redirect');



//Create Shortcode to drop the login and redirect link
// Usage: [GTCustomizer]Preview Theme[/GTCustomizer]
function gt_autologin_link($atts, $content = null) {
	extract(shortcode_atts(array('link' => plugins_url('/gt-custom.php?gtlo' , __FILE__ )), $atts));
	return '<a class="button" href="'.$link.'"><span>' . do_shortcode($content) . '</span></a>';
}
add_shortcode('GTCustomizer' , 'gt_autologin_link');

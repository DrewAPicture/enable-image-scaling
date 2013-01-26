<?php
/*
Plugin Name: Enable Image Scaling on Upload
Plugin URI: http://www.werdswords.com
Description: This allows you to set a maximum height and width for original-sized images to be scaled down to when uploaded in WordPress.
Version: 1.0.3.1
Author: Drew Jaynes (DrewAPicture)
Author URI: http://www.werdswords.com
License: GPLv2
*/

/******************************************************************
/*	  DEVELOPER NOTES

About 50 percent of the code in version 1.0 of this plugin is due
to making it backward-compatible with 3.3 - 3.4.2. Throughout the code,
you'll see numerous uses of $this->is_current which is a glorified
version check against 3.5+.

There are two methods that handle the setting and unsetting of the
upload_resize user setting on a user-by-user basis. When the plugin
is deactivated, we run a routine on the deactivation hook that resets
the user-setting for all qualified users.

It should be noted that it isn't possible to set custom dimensions
in pre-3.5, because of the way handlers.js takes the hard-coded
values and processes them. And so, in pre-3.5, the scaling dimensions
are defined by the maximum values set for the large image size.

******************************************************************/

/**
 * Enable Image Scaling Class
 *
 * @since 1.0
 */
class WW_Image_Scaling {
	
	var $enabled;
	var $width;
	var $height;
	var $large_w;
	var $large_h;
	var $multiplier;
	var $is_current;
	
	/**
	 * Initialize
	 *
	 * @uses get_option() gets our 3 options, a checkbox value and two number fields
	 * @since 1.0
	 */
	function __construct() {

		load_plugin_textdomain( 'ww_image_scaling', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) ); 

		// Options
		$this->enabled = get_option( 'ww_scaling_enabled' );
		$this->width = get_option( 'ww_scaling_width' );
		$this->height = get_option( 'ww_scaling_height' );

		// Large image size dimensions
		$this->large_w = get_option( 'large_size_w' );
		$this->large_h = get_option( 'large_size_h' );
		$this->multiplier = apply_filters( 'ww_scaling_size_multiplier', 1.5 );

		// Are we in 3.5+?
		global $wp_version;
		$this->is_current = version_compare( $wp_version, '3.4.2', '>' );

		// Action and filter hooks
		$this->hooks();
	}

	/**
	 * Activation Functions
	 *
	 * @uses add_option() to add default option values
	 * @since 1.0
	 */
	function activate() {
		add_option( 'ww_scaling_enabled', 1 );
		add_option( 'ww_scaling_width', $this->large_w * $this->multiplier );
		add_option( 'ww_scaling_height', $this->large_h * $this->multiplier );
	}

	/**
	 * Deactivation Functions
	 *
	 * Upon deactivation, we need to reset the upload_resize user setting for all users
	 *
	 * @uses get_users() to retrieve all users author and above because they have upload_files cap
	 * @uses get_user_option() to retrieve the user-settings option string
	 * @uses update_user_option to update the user-settings option string
	 * @return null
	 * @since 1.0
	 */
	function deactivate() {
		$users = get_users( array( 'who' => 'authors' ) );
		foreach ( $users as $user ) {
			$settings = get_user_option( 'user-settings', $user->ID );
			$reset = str_replace( array( '&upload_resize=1', 'upload_resize=1' ), '', $settings );
			update_user_option( $user->ID, 'user-settings', $reset );
		}
	}

	/**
	 * Action & Filter Hooks
	 *
	 * @uses add_filter() to filter plupload defaults 3.5+
	 * @uses add_action() to add various actions
	 * @since 1.0
	 */
	function hooks() {
		if ( $this->enabled ) {
			add_filter( 'plupload_default_settings', array( $this, 'uploads_scaling' ) );
			add_action( 'pre-plupload-upload-ui', array( $this, 'media_new_scaling' ) );
			add_action( 'post-upload-ui', array( $this, 'uploader_message' ) );
			add_action( 'plugins_loaded', array( $this, 'backcompat_set_defaults' ) );
		} else {
			add_action( 'plugins_loaded', array( $this, 'backcompat_unset_defaults' ) );
		}

		add_action( 'admin_init', array( $this, 'setup_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );
		add_action( 'admin_head-options-media.php', array( $this, 'help_tabs' ) );		
	}

	/*************************
	/*		SETTINGS
	*************************/

	/**
	 * Setup Media Settings
	 *
	 * @todo Find a way to serialize the settings while still allowing unique callbacks
	 * @uses add_settings_section() to register the scaling options on options-media.php.
	 * @uses register_setting() to register our 3 settings, a checkbox and 2 number inputs.
	 * @uses add_settings_field() to setup display callbacks for the 3 registered settings.
	 * @since 1.0
	 */
	function setup_settings() {
		add_settings_section( 'scaling_options', __( 'Image Scaling Options', 'ww_image_scaling' ), array( $this, 'section_cb' ), 'media' );

		register_setting( 'media', 'ww_scaling_enabled' );
		register_setting( 'media', 'ww_scaling_width', 'intval' );
		register_setting( 'media', 'ww_scaling_height', 'intval' );

		add_settings_field( 'ww_scaling_enabled', __( 'Image Scaling', 'ww_image_scaling' ), array( $this, 'checkbox_cb'), 'media', 'scaling_options' );
		add_settings_field( 'ww_scaling_width', __( 'Max Width', 'ww_image_scaling' ), array( $this, 'width_cb' ), 'media', 'scaling_options' );
		add_settings_field( 'ww_scaling_height', __( 'Max Height', 'ww_image_scaling' ), array( $this, 'height_cb' ), 'media', 'scaling_options' );		
	}

	/**
	 * Section Callback
	 *
	 * Print some contextual help text below the section title on options-media.php
	 *
	 * @since 1.0
	 */
	function section_cb() {
		if ( $this->is_current )
			/*  Translators: If WP 3.5+ */
			_e( 'Scaling will resize your original images on upload. Empty or 0 settings default to one and a half times the large size value.', 'ww_image_scaling' );
		else
			/* Translators: If WP 3.4.2 or before */
			_e( 'Scaling will resize your original images on upload. In your WordPress version, dimensions equal one and a half times the large image size value.', 'ww_image_scaling' );
	}

	/**
	 * Checkbox Callback
	 *
	 * Print our 'Enable' checkbox and label
	 *
	 * @uses checked() to handle the checkbox field and state
	 * @return null
	 * @since 1.0
	 */
	function checkbox_cb() {
		printf( '<input name="ww_scaling_enabled" type="checkbox" value="1" class="code" %1$s/>%2$s',
			checked( esc_attr( get_option( 'ww_scaling_enabled' ) ), true, false ),
			/* Translators: The leading space is intentional to space the text away from the checkbox */
			_e( ' Enable image scaling on upload', 'ww_image_scaling' )
		);
	}

	/**
	 * Width Setting Callback
	 *
	 * @uses get_option() to pull our width setting. Defaults to 1.5 times the large size width if empty or zero.
	 * @uses update_option() to update ww_scaling_width to the value of large_size_w if less than 1.
	 * @return null
	 * @since 1.0
	 */
	function width_cb() {
		if ( $this->width < 1 ) {
			$width_val = $this->large_w * $this->multiplier;
			update_option( 'ww_scaling_width', $this->large_w * $this->multiplier );
		} else {
			$width_val = $this->width;
		}

		$disabled = $this->is_current ? '' : 'disabled style="background-color:lightgrey"';
		printf( '<input name="ww_scaling_width" class="small-text" type="number" min="1" value="%1$d" %2$s />px', esc_attr( $width_val ), esc_attr( $disabled ) );
	}

	/**
	 * Height Setting Callback
	 *
	 * @uses get_option() to pull our height setting. Defaults to the 1.5 times the large size height if empty or zero.
	 * @uses update_option() to update ww_scaling_height to the value of large_size_h if less than 1.
	 * @return null
	 * @since 1.0
	 */
	function height_cb() {
		if ( $this->height < 1 ) {
			$height_val = $this->large_h * $this->multiplier;
			update_option( 'ww_scaling_height', $this->large_h * $this->multiplier );
		} else {
			$height_val = $this->height;
		}

		$disabled = $this->is_current ? '' : 'disabled style="background-color:lightgrey"';
		printf( '<input name="ww_scaling_height" class="small-text" type="number" min="0" value="%1$d" %2$s />px', esc_attr( $height_val ), esc_attr( $disabled ) );

		if ( ! $this->is_current )
			printf( '<p><strong>%s</strong></p>', __( 'Upgrade to <a href="http://wordpress.org/download/" target="new">WordPress 3.5</a> to set custom dimensions!', 'ww_image_scaling' ) );
	}

	/**
	 * 'Settings' Link
	 *
	 * @since 1.0
	*/
	function settings_link( $links ) {
		return array_merge( array( 'settings' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'options-media.php' ) ), __( 'Settings', 'ww_image_scaling' ) ) ), $links );
	}
	
	/*************************
	/*	  RESIZING SETUP
	*************************/

	/**
	 * Filter plupload defaults 3.5+
	 *
	 * 3.5+ is so easy, we just filter the plupload $defaults array.
	 *
	 * @uses apply_filters() to make the quality setting filterable (3.5+)
	 * @return array $defaults
	 * @since 1.0
	 */
	function uploads_scaling( $defaults ) {
		$defaults['resize'] = array(
			'width' => absint( $this->width ),
			'height' => absint( $this->height ),
			'quality' => apply_filters( 'ww_scaling_quality_filter', 100 )
		);
		return $defaults;
	}

	/**
	 * Override plupload defaults (3.5 media-new.php)
	 *
	 * This function adds support for the media-new.php uploader in 3.5 that still relies 
	 * on logic in handlers.js. We override the resize_width and resize_height JavaScript
	 * variables hard-coded by core by printing our own values on a nearby action hook.
	 * 
	 * Unlike pre-3.5, we can set custom dimensions for our height and width overrides.
	 * 
	 * @uses global $wp_version to get the WordPress version
	 * @uses get_current_screen() to retrieve the screen object
	 * @return null
	 * @since 1.0
	 */
	function media_new_scaling() {
		global $pagenow;
		if ( $this->is_current && 'media-new.php' == $pagenow )
			printf( '<script type="text/javascript">var resize_width = %1$d; resize_height = %2$d;</script>', absint( $this->width ), absint( $this->height ) );			
	}

	/*************************
	/*	PRE-3.5 BACK-COMPAT
	*************************/

	/**
	 * Back-compat user settings
	 *
	 * The following two methods, backcompat_set_defaults() and backcompat_unset_defaults()
	 * enable and disable the upload_resize user setting needed to enable image scaling pre-3.5
	 *
	 * @since 1.0
	 */
	function backcompat_set_defaults() {
		set_user_setting( 'upload_resize', 1 );
	}
	function backcompat_unset_defaults() {
		delete_user_setting( 'upload_resize' );
	}

	/*************************
	/*	   INFORMATIONAL
	*************************/

	/**
	 * Uploader Message / Back-compat hidden input
	 *
	 * This function serves a dual purpose:
	 * 	1. Prints a helpful message on the upload screen / media modal informing the user about the image scaling.
	 * 	2. Prints a hidden checkbox for pre-3.5 use. The checkbox, when paired with a user setting we enable in
	 * 	   $this->backcompat_set_defaults(), allows users to scale images based on the large image size max dimensions.
	 * 
	 * @uses current_user_can() to check if the user can manage options. If no manage_options cap, the anchor is not linked.
	 * @uses admin_url() to generate the admin URL for options-media.php
	 * @return null
	 * @since 1.0
	 */
	function uploader_message() {
		$link = current_user_can( 'manage_options' ) ? sprintf( '<a href="%1$s" target="_new">%2$s</a>', esc_url( admin_url( 'options-media.php' ) ), __( 'media settings', 'ww_image_scaling' ) ) : __( 'media settings', 'ww_image_scaling' );
		if ( ! $this->is_current ) {
			$this->width = $this->large_w;
			$this->height = $this->large_h;

			// 3.3 and 3.4 rely on _both_ the upload_resize user setting and the value of the checkbox
			// The user setting is enabled via $this->backcompat_set_defaults()

			/* WP 3.4.2 and earlier */
			echo '<input name="image_resize" type="checkbox" id="image_resize" style="display:none" value="true" checked="true" />';							
		}
			/* Translators: This is for WP 3.5 and later */
			echo '<p>' . sprintf( __( 'Per your %1$s, all images will be scaled with max-dimensions of %2$d x %3$dpx', 'ww_image_scaling' ), $link,  esc_attr( $this->width ), esc_attr( $this->height ) ) . '</p>';
	}

	/**
	 * Help Tabs
	 *
	 * Here we add a help tab on options-media.php to better explain the benefits of image scaling, etc.
	 * 
	 * @uses get_current_screen()::add_help_tab() to add the help tab to media.php
	 * @since 1.0
	 */ 
	function help_tabs() {
		$image_scaling_text = '<p>' . __( 'When this option is enabled, every time you upload an image, the original will be scaled to fix the maximum height and width dimensions you have set. This process is very similar to how the max height and width dimensions work with thumbnail, medium, and large image sizes.', 'ww_image_scaling' ) . '</p>';
		$image_scaling_text .= '<p>' . __( 'What is the benefit of scaling on upload? Original images can have huge, unrealistic dimensions and can take up a lot of space on your server. Scaling images down helps to keep your server costs lower.', 'ww_image_scaling' ) . '</p>';

		get_current_screen()->add_help_tab( array( 
			'id' => 'scaling-overview',
			'title' => __( 'Image Scaling', 'ww_image_scaling' ),
			'content' => $image_scaling_text
		) );
	}
} // WW_Image_Scaling

new WW_Image_Scaling;
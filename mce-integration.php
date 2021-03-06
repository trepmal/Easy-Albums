<?php
if ( ! defined('ABSPATH') )
	die('-1');

$albums_mce_button = new Albums_MCE_Button();

class Albums_MCE_Button {

	var $pluginname = 'albums';
	var $internalVersion = 600;

	/**
	 * the constructor
	 *
	 * @return void
	 */
	function __construct()  {

		// init process for button control
		add_action('init', array( &$this, 'init') );

		// init process for ajax popup
		add_action('wp_ajax_albums_tinymce', array( &$this, 'window_cb') );

		// localize
		add_filter( 'mce_external_languages', array( &$this, 'mce_external_languages' ) );

	}

	/**
	 * ::init()
	 *
	 * @return void
	 */
	function init() {

		// Don't bother doing this stuff if the current user lacks permissions
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') )
			return;

		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {

			// add the button for wp2.5 in a new way
			add_filter( 'mce_external_plugins', array( &$this, 'mce_external_plugins' ));
			add_filter( 'mce_buttons', array( &$this, 'mce_buttons' ), 0);
		}
	}

	/**
	 * ::mce_buttons()
	 * used to insert button in wordpress 2.5x editor
	 *
	 * @return $buttons
	 */
	function mce_buttons( $buttons ) {

		array_push( $buttons, $this->pluginname );

		return $buttons;
	}

	/**
	 * ::mce_external_plugins()
	 * Load the TinyMCE plugin : editor_plugin.js
	 *
	 * @return $plugin_array
	 */
	function mce_external_plugins($plugin_array) {

		$plugin_array[ $this->pluginname ] =  plugins_url( 'mce/editor_plugin.js', __FILE__ );

		return $plugin_array;
	}

	/**
	 * ::window_cb()
	 * create the popup windo
	 *
	 * @return void
	 */
	function window_cb() {

		// check for rights
		if ( !current_user_can('edit_pages') && !current_user_can('edit_posts') )
			die(__("You are not allowed to be here"));

		$window = plugin_dir_path(__FILE__) .'mce/window.php';
		include_once( $window );

		die();
	}

	function mce_external_languages( $s ) {
		$s[ $this->pluginname ] = plugin_dir_path(__FILE__) .'mce/lang.php';
		// $s[] = plugin_dir_path(__FILE__) .'mce/lang.php';
		return $s;
	}
}

//eof
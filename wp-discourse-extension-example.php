<?php
/**
 * Plugin Name: WP Discourse Extension Example
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourse\ExtensionExample;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Admin\OptionsPage' ) ) {
		$options_page = new \WPDiscourse\Admin\OptionsPage();
//		new ExtensionExample( $options_page );
	}
}

class ExtensionExample {
	protected $options_page;

	protected $extension_options = array(
		'option_one' => 'foo',
		'option_two' => 'bar',
	);

	public function __construct( $options_page ) {
		$this->options_page = $options_page;

		add_action( 'init', array( $this, 'initialize_plugin_configuration' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function initialize_plugin_configuration() {
		add_option( 'wpdc_extension_example', $this->extension_options );
	}

	public function admin_menu() {
		$extension_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Extension Options', 'wpdc-extension' ),
			__( 'Extension Options', 'wpdc-extension' ),
			'manage_options',
			'extension_options',
			array( $this, 'extension_options_tab' )
		);
		add_action( 'load-' . $extension_settings, array( $this->options_page, 'connection_status_notice' ) );
	}

	public function extension_options_tab() {
		$this->options_page->options_pages_display( 'extension_options' );
	}
}
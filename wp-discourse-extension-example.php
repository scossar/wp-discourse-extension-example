<?php
/**
 * Plugin Name: WP Discourse Extension Example
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourse\ExtensionExample;

use WPDiscourse\Admin\OptionsPage as OptionsPage;
use WPDiscourse\Admin\OptionInput as OptionInput;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Admin\OptionsPage' ) ) {
		$options_page = OptionsPage::get_instance();
		$option_input = OptionInput::get_instance();
		new ExtensionExample( $options_page, $option_input );
	}
}

class ExtensionExample {
	protected $options_page;
	protected $input_helper;
	protected $extended_options;

	protected $extension_options = array(
		'option-one' => 'foo',
		'option-two' => 'bar',
	);

	public function __construct( $options_page, $option_input = null ) {
		$this->options_page = $options_page;
		$this->input_helper = $option_input;

		add_action( 'admin_init', array( $this, 'options_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'discourse/admin/options-page/after-settings-tabs', array( $this, 'add_settings_tab' ) );
		add_action( 'discourse/admin/options-page/after-tab-switch', array( $this, 'extension_settings_fields' ) );
		add_filter( 'discourse/utilities/options-array', array( $this, 'add_options' ) );
	}

	public function options_init() {
		add_option( 'wpdc_extension_options', $this->extension_options );

		add_settings_section( 'wpdc_extension_settings_section', __( 'Example Extension Settings', 'wpdc-extension' ), array(
			$this,
			'extension_settings_details',
		), 'wpdc_extension_options' );

		add_settings_field( 'wpdc_extension_option_one', __( 'Option One', 'wpdc-extension' ), array(
			$this,
			'option_one_input',
		), 'wpdc_extension_options', 'wpdc_extension_settings_section' );

		add_settings_field( 'wpdc_extension_option_two', __( 'Option Two', 'wpdc-extension' ), array(
			$this,
			'option_two_input',
		), 'wpdc_extension_options', 'wpdc_extension_settings_section' );

		register_setting( 'wpdc_extension_options', 'wpdc_extension_options', array( $this, 'validate_options' ) );
	}

	public function add_options( $discourse_options ) {
		static $options = [];

		if ( empty( $extended_options ) ) {
			$added_options  = get_option( 'wpdc_extension_options' );
			$options = array_merge( $discourse_options, $added_options );
			$this->extended_options = $options;
		}

		return $options;
	}

	public function extension_settings_fields( $tab ) {
		if ( 'wpdc_extension_options' === $tab ) {
			settings_fields( 'wpdc_extension_options' );
			do_settings_sections( 'wpdc_extension_options' );
		}
	}

	public function add_settings_tab( $tab ) {
		?>
        <a href="?page=wp_discourse_options&tab=wpdc_extension_options"
           class="nav-tab <?php echo 'wpdc_extension_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Extension Options', 'wpdc-extension' ); ?>
        </a>
		<?php
	}


	public function admin_menu() {
		$extension_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Extension Options', 'wpdc-extension' ),
			__( 'Extension Options', 'wpdc-extension' ),
			'manage_options',
			'wpdc_extension_options',
			array( $this, 'extension_options_tab' )
		);
		add_action( 'load-' . $extension_settings, array( $this->options_page, 'connection_status_notice' ) );
	}

	public function extension_options_tab() {
		$this->options_page->options_pages_display( 'wpdc_extension_options' );
	}

	public function extension_settings_details() {
		?>
        <p>Extension Settings Tab details.</p>
		<?php
	}

	public function option_one_input() {
		$this->input_helper->text_input( 'option-one', 'wpdc_extension_options', 'Option One' );
	}

	public function option_two_input() {
		$this->input_helper->text_input( 'option-two', 'wpdc_extension_options', 'Option Two' );
	}

	public function validate_options( $inputs ) {
		$output = [];
		foreach ( $inputs as $key => $value ) {
			$output[ $key ] = sanitize_text_field( $value );
		}

		write_log( 'output', $output );

		return $output;
	}
}
<?php
/**
 * Plugin Name: WP Discourse Extension Example
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourse\TopicLink;

require_once( __DIR__ . '/discourse-permalink.php' );

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		if ( is_admin() ) {
			$options_page = \WPDiscourse\Admin\OptionsPage::get_instance();
			$option_input = \WPDiscourse\Admin\OptionInput::get_instance();
			new Admin( $options_page, $option_input );
		}
        new \WPDiscourse\TopicLink\DiscoursePermalink();
	}
}

class Admin {
	protected $options_page;
	protected $input_helper;
	protected $extended_options;

	// Initial values.
	protected $extension_options = array(
		'add-topic-link'  => 0,
		'topic-link-text' => 'Join the Discussion',
	);

	public function __construct( $options_page, $option_input = null ) {
		$this->options_page = $options_page;
		$this->input_helper = $option_input;

		add_action( 'admin_init', array( $this, 'options_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'discourse/admin/options-page/after-settings-tabs', array( $this, 'add_settings_tab' ) );
		add_action( 'discourse/admin/options-page/after-tab-switch', array( $this, 'topic_link_settings_fields' ) );
		add_filter( 'discourse/utilities/options-array', array( $this, 'add_options' ) );

	}

	public function options_init() {
		// Add option if it doesn't exist.
		add_option( 'wpdc-topic-link', $this->extension_options );

		add_settings_section( 'wpdc_topic_link_settings_section', __( 'Discourse Topic Link Settings', 'wpdc-extension' ), array(
			$this,
			'topic_link_settings_details',
		), 'wpdc-topic-link' );

		add_settings_field( 'wpdc_add_topic_link', __( 'Add Topic Link', 'wpdc-extension' ), array(
			$this,
			'add_topic_link_checkbox',
		), 'wpdc-topic-link', 'wpdc_topic_link_settings_section' );

		add_settings_field( 'wpdc_topic_link_text', __( 'Topic Link Text', 'wpdc-extension' ), array(
			$this,
			'topic_link_text_input',
		), 'wpdc-topic-link', 'wpdc_topic_link_settings_section' );

		register_setting( 'wpdc-topic-link', 'wpdc-topic-link', array( $this, 'validate_options' ) );
	}

	public function add_options( $discourse_options ) {
		static $options = [];

		if ( empty( $options ) ) {
			$added_options          = get_option( 'wpdc-topic-link' );
			$options                = array_merge( $discourse_options, $added_options );
			$this->extended_options = $options;
		}

		return $options;
	}

	public function topic_link_settings_fields( $tab ) {
		if ( 'topic_link_options' === $tab ) {
			settings_fields( 'wpdc-topic-link' );
			do_settings_sections( 'wpdc-topic-link' );
		}
	}

	public function add_settings_tab( $tab ) {
		?>
        <a href="?page=wp_discourse_options&tab=topic_link_options"
           class="nav-tab <?php echo 'topic_link_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Topic Link', 'wpdc-extension' ); ?>
        </a>
		<?php
	}

	public function admin_menu() {
		$topic_link_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Topic Link', 'wpdc-extension' ),
			__( 'Topic Link', 'wpdc-extension' ),
			'manage_options',
			'topic_link_options',
			array( $this, 'topic_link_options_tab' )
		);
		add_action( 'load-' . $topic_link_settings, array( $this->options_page, 'connection_status_notice' ) );
	}

	public function topic_link_options_tab() {
		$this->options_page->options_pages_display( 'topic_link_options' );
	}

	public function topic_link_settings_details() {
		?>
        <p>If you choose to not display Discourse comments on your WordPress site, you can use this option to display a
            link back to the Discourse Topic.</p>
		<?php
	}

	public function add_topic_link_checkbox() {
		$this->input_helper->checkbox_input( 'add-topic-link', 'wpdc-topic-link', __( 'Add a link to the Discourse topic at the bottom of each post that is published to Discourse.', 'wpdc-extension' ) );
	}

	public function topic_link_text_input() {
		$this->input_helper->text_input( 'topic-link-text', 'wpdc-topic-link', 'Topic Link Text', __( 'The link text to use.', 'wpdc-extenstion' ) );
	}

	public function validate_options( $inputs ) {
		$output = [];
		foreach ( $inputs as $key => $value ) {
			if ( 'add-topic-link' === $key ) {
				$value          = 1 === intval( $value ) ? 1 : 0;
				$output[ $key ] = $value;

			} elseif ( 'topic-link-text' === $key ) {

				$output[ $key ] = trim( sanitize_text_field( $value ) );

			}
		}

		return $output;
	}
}

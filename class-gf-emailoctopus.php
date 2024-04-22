<?php
// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Gravity Forms EmailOctopus Add-On.
 *
 * @since     1.0.0
 * @package   EmailOctopus
 * @author    Rocketgenius
 * @copyright Copyright (c) 2019, Rocketgenius
 */

// Include the Gravity Forms Feed Add-On Framework.
GFForms::include_feed_addon_framework();

/**
 * Initialize EmailOctopus feeds and API.
 */
class GF_EmailOctopus extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @var    GF_EmailOctopus $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Gravity Forms EmailOctopus Add-On.
	 *
	 * @since  1.0
	 * @var    string $_version Contains the version.
	 */
	protected $_version = GF_EMAILOCTOPUS_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = GF_EMAILOCTOPUS_MIN_GF_VERSION;

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsemailoctopus';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsemailoctopus/emailoctopus.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this add-on can be found.
	 *
	 * @since  1.0
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'https://gravityforms.com';

	/**
	 * Defines the title of this add-on.
	 *
	 * @since  1.0
	 * @var    string $_title The title of the add-on.
	 */
	protected $_title = 'Gravity Forms EmailOctopus Add-On';

	/**
	 * Defines the short title of the add-on.
	 *
	 * @since  1.0
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'EmailOctopus';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capabilities needed for the EmailOctopus Add-On
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_emailoctopus', 'gravityforms_emailoctopus_uninstall' );

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_emailoctopus';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_emailoctopus';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_emailoctopus_uninstall';

	/**
	 * Holds the object for the EmailOctopus API helper.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    GF_EmailOctopus_API $api The API instance for EmailOctopus.
	 */
	protected $api = null;

	/**
	 * Enabling background feed processing to prevent performance issues delaying form submission completion.
	 *
	 * @since 1.3
	 *
	 * @var bool
	 */
	protected $_async_feed_processing = true;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since  1.0
	 *
	 * @return GF_EmailOctopus $_instance An instance of the GF_EmailOctopus class
	 */
	public static function get_instance() {

		if ( self::$_instance === null ) {
			self::$_instance = new GF_EmailOctopus();
		}

		return self::$_instance;
	}

	/**
	 * Feed starting point.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to EmailOctopus only when payment is received.', 'gravityformsemailoctopus' ),
			)
		);
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  1.0.0
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				/* Translators: %s is the website address of EmailOctopus */
				'description' => '<p>' . esc_html__( 'EmailOctopus is an affordable, easy-to-use email marketing platform for anyone with an audience.', 'gravityformsemailoctopus' ) . ' ' . sprintf( esc_html__( 'Go to %s to sign up.', 'gravityformsemailoctopus' ), sprintf( '<a href="%s" target="_blank">%s</a>', 'https://emailoctopus.com', esc_html__( 'EmailOctopus.com', 'gravityformsemailoctopus' ) ) ) . '</p>',
				'fields'      => array(
					array(
						'name'              => 'api_key',
						'tooltip'           => esc_html__( 'Enter your EmailOctopus API Key, which can be retrieved when you login to EmailOctopus.com.', 'gravityformsemailoctopus' ),
						'label'             => esc_html__( 'EmailOctopus API Key', 'gravityformsemailoctopus' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
				),
			),
		);
	}

	/**
	 * Saves the plugin settings if the submit button was pressed
	 *
	 * @since 1.0.0
	 */
	public function maybe_save_plugin_settings() {
		if ( isset( $_POST['_gaddon_setting_api_key'] ) ) {
			$_POST['_gaddon_setting_api_key'] = sanitize_text_field( $_POST['_gaddon_setting_api_key'] ); // remove space in front and end of string.
		}
		parent::maybe_save_plugin_settings();
	}

	/**
	 * Clears the cached lists when the plugin settings are saved.
	 *
	 * @since 1.3
	 *
	 * @param array $settings The settings to be saved.
	 *
	 * @return void
	 */
	public function update_plugin_settings( $settings ) {
		GFCache::delete( $this->get_slug() . '_lists' );
		parent::update_plugin_settings( $settings );
	}

	/**
	 * Initializes the EmailOctopus API if API credentials are valid.
	 *
	 * @since  1.0
	 *
	 * @return bool|null API initialization state. Returns null if no API key is provided.
	 */
	public function initialize_api() {

		// If the API is already initializes, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Initialize EmailOctopus API library.
		if ( ! class_exists( 'GF_EmailOctopus_API' ) ) {
			require_once 'includes/class-gf-emailoctopus-api.php';
		}

		// Get the API key.
		$api_key = $this->get_plugin_setting( 'api_key' );

		// If the API key is not set, return null.
		if ( rgblank( $api_key ) ) {
			return null;
		}

		// Initialize a new EmailOctopus API instance.
		$emailoctopus = new GF_EmailOctopus_API( $api_key );

		// Test the API Key by getting 1 list.
		$response = $emailoctopus->is_api_key_valid();

		if ( is_wp_error( $response ) ) {
			$this->log_debug( __METHOD__ . '(): EmailOctopus API key could not be validated. ' . $response->get_error_message() );

			return false;
		}

		$this->log_debug( __METHOD__ . '(): EmailOctopus API key is valid.' );
		$this->api = $emailoctopus;

		return true;
	}

	/**
	 * Return an array of EmailOctopus list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Field map or empty array on failure.
	 */
	public function merge_vars_field_map() {
		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): EmailOctopus API could not be initialized.' );
			return array();
		}

		// Get current list ID.
		$list_id = $this->get_setting( 'emailoctopuslist' );
		if ( empty( $list_id ) ) {
			return array();
		}

		$fields = $this->get_list_fields( $list_id );
		if ( empty( $fields ) ) {
			return array();
		}

		// Initialize field map array.
		$field_map = array(
			'EmailAddress' => array(
				'name'       => 'EmailAddress',
				'label'      => esc_html__( 'Email Address', 'gravityformsemailoctopus' ),
				'required'   => true,
				'field_type' => array( 'email', 'hidden' ),
			),
		);

		foreach ( $fields as $field ) {
			if ( 'EmailAddress' === $field['tag'] ) {
				continue;
			}

			switch ( $field['type'] ) {
				case 'NUMBER':
					$field_type = array( 'number' );
					break;
				case 'DATE':
					$field_type = array( 'date', 'hidden' );
					break;
				default:
					$field_type = null;
					break;
			}

			$field_map[ $field['tag'] ] = array(
				'name'       => $field['tag'],
				'label'      => $field['label'],
				'required'   => false,
				'field_type' => $field_type,
			);
		}

		return $field_map;
	}

	/**
	 * Returns the fields for the specified list.
	 *
	 * @since 1.3
	 *
	 * @param string $list_id The list ID.
	 *
	 * @return array
	 */
	public function get_list_fields( $list_id ) {
		$cache_key = $this->get_slug() . '_list_' . $list_id . '_fields';
		$fields    = GFCache::get( $cache_key );

		if ( is_array( $fields ) ) {
			return $fields;
		}

		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): EmailOctopus API could not be initialized.' );

			return array();
		}

		$list = $this->api->get_list( $list_id );
		if ( is_wp_error( $list ) ) {
			$this->log_debug( __METHOD__ . "(): Could not retrieve list ({$list_id}) from API. " . $list->get_error_message() );

			return array();
		}

		if ( empty( $list['fields'] ) ) {
			$this->log_error( __METHOD__ . "(): No fields found for selected list ({$list_id})." );

			return array();
		}

		$fields = $list['fields'];
		GFCache::set( $cache_key, $fields, true, HOUR_IN_SECONDS );

		return $fields;
	}

	/**
	 * Returns the specified list field.
	 *
	 * @since 1.3
	 *
	 * @param string $list_id   The list ID.
	 * @param string $field_tag The field tag.
	 *
	 * @return array
	 */
	public function get_list_field( $list_id, $field_tag ) {
		$fields = $this->get_list_fields( $list_id );
		if ( empty( $fields ) ) {
			return array();
		}

		foreach ( $fields as $field ) {
			if ( $field['tag'] === $field_tag ) {
				return $field;
			}
		}

		return array();
	}

	/**
	 * Form settings page title
	 *
	 * @since 1.0.0
	 * @return string Form Settings Title
	 */
	public function feed_settings_title() {
		return esc_html__( 'Feed Settings', 'gravityformsemailoctopus' );
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' );

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.0.0
	 *
	 * @param int $id Feed ID requesting duplication.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {
		return true;
	}

	/**
	 * Feed settings
	 *
	 * @since 1.0.0
	 * @return array feed settings
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityformsemailoctopus' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformsemailoctopus' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsemailoctopus' )
						),
					),
					array(
						'name'       => 'emailoctopuslist',
						'label'      => esc_html__( 'EmailOctopus List', 'gravityformsemailoctopus' ),
						'type'       => 'select',
						'choices'    => $this->get_emailoctopus_lists(),
						'required'   => true,
						'no_choices' => esc_html__( 'Please create a EmailOctopus list to continue setup.', 'gravityformsemailoctopus' ),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'EmailOctopus List', 'gravityformsemailoctopus' ),
							esc_html__( 'Select the EmailOctopus list you would like to add your contacts to.', 'gravityformsemailoctopus' )
						),
						'onchange'   => 'jQuery(this).parents("form").submit();',
					),
				),
			),
			array(
				'dependency' => 'emailoctopuslist',
				'fields'     => array(
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'gravityformsemailoctopus' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformsemailoctopus' ),
							esc_html__( 'Associate your EmailOctopus fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformsemailoctopus' )
						),
					),
					array(
						'name'    => 'optinCondition',
						'label'   => esc_html__( 'Conditional Logic', 'gravityformsemailoctopus' ),
						'type'    => 'feed_condition',
						'tooltip' => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformsemailoctopus' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to EmailOctopus when the conditions are met. When disabled all form submissions will be exported.', 'gravityformsemailoctopus' )
						),
					),
					array( 'type' => 'save' ),
				),
			),
		);
	}

	/**
	 * Determines if a user has double-opt-in available.
	 *
	 * @since  1.0.0
	 *
	 * @param string $list_id The list ID to retrieve double-opt-in status for.
	 *
	 * @return bool true if double-opt-in enabled, false if not
	 */
	private function is_double_opt_in( $list_id ) {
		$found                      = true;
		$emailoctopus_double_opt_in = GFCache::get( 'emailoctopus_double_opt_in_' . $list_id, $found );
		if ( $found ) {
			return filter_var( $emailoctopus_double_opt_in, FILTER_VALIDATE_BOOLEAN );
		}
		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): EmailOctopus API could not be initialized.' );
			return false;
		}

		$list = $this->api->get_list( $list_id );
		if ( is_wp_error( $list ) ) {
			$this->log_debug( __METHOD__ . '(): Could not retrieve EmailOctopus list from API. ' . $lists->get_error_message() );
			return false;
		}
		$double_opt_in = filter_var( $list['double_opt_in'], FILTER_VALIDATE_BOOLEAN );
		GFCache::set( 'emailoctopus_double_opt_in_' . $list_id, $double_opt_in, true, DAY_IN_SECONDS );
		return $double_opt_in;
	}

	/**
	 * Process the feed, subscribe the user to the list.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 */
	public function process_feed( $feed, $entry, $form ) {

		// Get field map values.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Get mapped email address.
		$email = $this->get_field_value( $form, $entry, $field_map['EmailAddress'] );

		// If email address is invalid, log error and return.
		if ( GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'A valid Email address must be provided.', 'gravityformsemailoctopus' ), $feed, $entry, $form );
			return;
		}

		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Unable to connect to the EmailOctopus API', 'gravityformsemailoctopus' ), $feed, $entry, $form );

			return;
		}

		$list_id = rgars( $feed, 'meta/emailoctopuslist' );

		// Initialize array to store merge vars.
		$merge_vars = array();

		// Loop through field map.
		foreach ( $field_map as $name => $field_id ) {

			// If no field is mapped, skip it.
			if ( rgblank( $field_id ) ) {
				continue;
			}

			if ( 'EmailAddress' === $name ) {
				continue;
			}

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			if ( empty( $field_value ) ) {
				continue;
			}

			$list_field = $this->get_list_field( $list_id, $name );
			if ( rgar( $list_field, 'type' ) === 'DATE' ) {
				$field = GFFormsModel::get_field( $form, $field_id );
				if ( ! $field instanceof GF_Field_Date ) {
					$timestamp = strtotime( $field_value );
					if ( ! $timestamp ) {
						continue;
					}
					$field_value = date( 'Y-m-d', $timestamp );
				}
			}

			$merge_vars[ $name ] = $field_value;
		}

		$this->log_debug( __METHOD__ . sprintf( '(): Creating contact %s with fields => %s', $email, json_encode( $merge_vars ) ) );
		$response = $this->api->create_contact( $list_id, $email, $merge_vars );

		// Check response.
		if ( is_wp_error( $response ) ) {
			$this->add_feed_error( esc_html__( 'Unable to add subscriber to EmailOctopus: ', 'gravityformsemailoctopus' ) . $response->get_error_message(), $feed, $entry, $form );
		} else {
			$this->log_debug( __METHOD__ . '(): Result => ' . json_encode( $response ) );
			if ( $this->is_double_opt_in( $list_id ) ) {
				$this->add_note( $entry['id'], __( 'The user email has been sent a notification to subscribe.', 'gravityformsemailoctopus' ), 'success' );
			} else {
				$this->add_note( $entry['id'], __( 'The user email has been added to your EmailOctopus list.', 'gravityformsemailoctopus' ), 'success' );
			}
		}
	}

	/**
	 * Return list options when creating a field.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array List choices. Empty array on failure.
	 */
	public function get_emailoctopus_lists() {
		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): EmailOctopus API could not be initialized.' );
			return array();
		}
		// Get API List.
		$lists = $this->get_lists();
		if ( is_wp_error( $lists ) ) {
			$this->log_debug( __METHOD__ . '(): Could not retrieve EmailOctopus lists from API. ' . $lists->get_error_message() );
			return array();
		}
		if ( empty( $lists ) ) {
			$this->log_debug( __METHOD__ . '(): Could not retrieve any EmailOctopus lists.' );
			return array();
		}

		// Initialize select options.
		$options = array();

		// Loop through EmailOctopus lists.
		foreach ( $lists as $list ) {

			// Add list to select options.
			$options[] = array(
				'label' => esc_html( $list['name'] ),
				'value' => esc_attr( $list['id'] ),
			);
		}

		usort( $options, function ( $a, $b ) {
			return strnatcasecmp( $a['label'], $b['label'] );
		} );

		array_unshift( $options, array(
			'label' => esc_html__( 'Select an EmailOctopus List', 'gravityformsemailoctopus' ),
			'value' => '',
		) );

		return $options;
	}

	/**
	 * Gets the lists.
	 *
	 * @since 1.3
	 *
	 * @return array|WP_Error
	 */
	public function get_lists() {
		$cache_key = $this->get_slug() . '_lists';

		$lists = GFCache::get( $cache_key );
		if ( ! empty( $lists ) ) {
			return $lists;
		}

		$lists = $this->api->get_lists();
		if ( ! is_wp_error( $lists ) && ! empty( $lists ) ) {
			GFCache::set( $cache_key, $lists, true, HOUR_IN_SECONDS );
		}

		return $lists;
	}

	/**
	 * Prevent feeds being listed or created if the API key isn't valid.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return bool
	 */
	public function can_create_feed() {
		return $this->initialize_api();
	}

	/**
	 * Returns the value to be displayed in the EmailOctopus List column.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string EmailOctopus List Name. Empty string on failure.
	 */
	public function get_column_value_emailoctopus_list_name( $feed ) {
		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): EmailOctopus API could not be initialized.' );
			return '';
		}

		// Get API List.
		$list_id = isset( $feed['meta']['emailoctopuslist'] ) ? $feed['meta']['emailoctopuslist'] : '';
		if ( empty( $list_id ) ) {
			$this->log_debug( __METHOD__ . '(): The EmailOctopus list is not set in the feed settings.' );
			return;
		}

		// Get API List.
		$list = $this->api->get_list( $list_id );

		if ( is_wp_error( $list ) ) {
			$this->log_debug( __METHOD__ . '(): Could not retrieve EmailOctopus list name from API. ' . $list->get_error_message() );
			return '';
		}
		if ( empty( $list ) ) {
			$this->log_debug( __METHOD__ . '(): Could not retrieve the EmailOctopus list.' );
			return '';
		}

		if ( isset( $list['name'] ) ) {
			return $list['name'];
		};

		$this->log_debug( __METHOD__ . '(): Could not match any EmailOctopus list with feed settings.' );
		return '';
	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'               => esc_html__( 'Name', 'gravityformsemailoctopus' ),
			'emailoctopus_list_name' => esc_html__( 'EmailOctopus List', 'gravityformsemailoctopus' ),
		);
	}
}

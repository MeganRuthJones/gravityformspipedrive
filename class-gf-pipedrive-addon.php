<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

GFForms::include_feed_addon_framework();

class GFPipedriveAddOn extends GFFeedAddOn {

	protected $_version                  = GF_PIPEDRIVE_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug                     = 'gravityformspipedrive';
	protected $_path                     = 'gravityformspipedrive/gravityformspipedrive.php';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'Gravity Forms Pipedrive Add-On';
	protected $_short_title              = 'Pipedrive';
	protected $_enable_rg_autoupgrade    = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance === null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	// ---------------------------------------------------------------------
	// Plugin settings (API key)
	// ---------------------------------------------------------------------

	public function plugin_settings_fields() {
		$api_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://app.pipedrive.com/settings/personal/api' ),
			esc_html__( 'find your API token here', 'gravityformspipedrive' )
		);

		$description = sprintf(
			__( 'Enter your Pipedrive API token to connect your account. You can %s (log in to Pipedrive first, then click on the Personal → API tab). Each token is tied to a specific Pipedrive user — whoever owns the token will appear as the owner of records created by this add-on.', 'gravityformspipedrive' ),
			$api_link
		);

		return array(
			array(
				'title'  => esc_html__( 'Pipedrive Settings', 'gravityformspipedrive' ),
				'fields' => array(
					array(
						'name'     => 'pipedrive_api_key',
						'label'    => esc_html__( 'Pipedrive API Token', 'gravityformspipedrive' ),
						'type'     => 'text',
						'class'    => 'medium',
						'required' => true,
						'desc'     => $description,
					),
				),
			),
		);
	}

	// ---------------------------------------------------------------------
	// Feed settings
	// ---------------------------------------------------------------------

	public function feed_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Pipedrive Feed Settings', 'gravityformspipedrive' ),
				'fields' => array(
					array(
						'name'     => 'feed_name',
						'label'    => esc_html__( 'Feed Name', 'gravityformspipedrive' ),
						'type'     => 'text',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'name'          => 'pipedrive_object',
						'label'         => esc_html__( 'Pipedrive Object', 'gravityformspipedrive' ),
						'type'          => 'select',
						'choices'       => array(
							array( 'label' => esc_html__( 'Person', 'gravityformspipedrive' ),       'value' => 'person' ),
							array( 'label' => esc_html__( 'Organization', 'gravityformspipedrive' ), 'value' => 'organization' ),
							array( 'label' => esc_html__( 'Deal', 'gravityformspipedrive' ),         'value' => 'deal' ),
						),
						'required'      => true,
						'default_value' => 'person',
						'tooltip'       => esc_html__( 'Select the type of record this feed creates in Pipedrive. Fill in the matching section below; leave the others empty.', 'gravityformspipedrive' ),
					),
				),
			),
			array(
				'title'       => esc_html__( 'Person Fields', 'gravityformspipedrive' ),
				'description' => esc_html__( 'Person Name is required. Mapped Organization names are created if they don\'t exist. Existing Persons are matched by email.', 'gravityformspipedrive' ),
				'fields'      => array(
					array(
						'name'      => 'person_field_map',
						'label'     => esc_html__( 'Map Person Fields', 'gravityformspipedrive' ),
						'type'      => 'field_map',
						'field_map' => array(
							array( 'name' => 'person_name',         'label' => esc_html__( 'Person Name', 'gravityformspipedrive' ),                  'required' => false ),
							array( 'name' => 'person_email',        'label' => esc_html__( 'Email', 'gravityformspipedrive' ),                        'required' => false ),
							array( 'name' => 'person_phone',        'label' => esc_html__( 'Phone', 'gravityformspipedrive' ),                        'required' => false ),
							array( 'name' => 'person_organization', 'label' => esc_html__( 'Organization (will be linked)', 'gravityformspipedrive' ), 'required' => false ),
						),
					),
				),
			),
			array(
				'title'       => esc_html__( 'Organization Fields', 'gravityformspipedrive' ),
				'description' => esc_html__( 'Organization Name is required. Existing Organizations are matched by name.', 'gravityformspipedrive' ),
				'fields'      => array(
					array(
						'name'      => 'organization_field_map',
						'label'     => esc_html__( 'Map Organization Fields', 'gravityformspipedrive' ),
						'type'      => 'field_map',
						'field_map' => array(
							array( 'name' => 'organization_name',    'label' => esc_html__( 'Organization Name', 'gravityformspipedrive' ),    'required' => false ),
							array( 'name' => 'organization_address', 'label' => esc_html__( 'Organization Address', 'gravityformspipedrive' ), 'required' => false ),
						),
					),
				),
			),
			array(
				'title'       => esc_html__( 'Deal Fields', 'gravityformspipedrive' ),
				'description' => esc_html__( 'Deal Title is required. Each Deal must link to a Person or Organization — map at least one below. If the Email or Organization Name matches an existing record in Pipedrive, the Deal links to it. Otherwise, a new record is created.', 'gravityformspipedrive' ),
				'fields'      => array(
					array(
						'name'      => 'deal_field_map',
						'label'     => esc_html__( 'Map Deal Fields', 'gravityformspipedrive' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'     => 'deal_title',
								'label'    => esc_html__( 'Deal Title', 'gravityformspipedrive' ),
								'required' => false,
								'tooltip'  => esc_html__( 'The name of the deal as it will appear in Pipedrive. This is the headline shown on the deal card in your sales pipeline, so make it something your sales team can recognise at a glance — for example, the person\'s name, the service they are interested in, or the project title.', 'gravityformspipedrive' ),
							),
							array( 'name' => 'person_name',       'label' => esc_html__( 'Person Name', 'gravityformspipedrive' ),       'required' => false ),
							array( 'name' => 'person_email',      'label' => esc_html__( 'Person Email', 'gravityformspipedrive' ),      'required' => false ),
							array( 'name' => 'person_phone',      'label' => esc_html__( 'Person Phone', 'gravityformspipedrive' ),      'required' => false ),
							array( 'name' => 'organization_name', 'label' => esc_html__( 'Organization Name', 'gravityformspipedrive' ), 'required' => false ),
						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Feed Conditional Logic', 'gravityformspipedrive' ),
				'fields' => array(
					array(
						'name'         => 'feed_condition',
						'label'        => esc_html__( 'Conditional Logic', 'gravityformspipedrive' ),
						'type'         => 'feed_condition',
						'instructions' => esc_html__( 'Process this feed if', 'gravityformspipedrive' ),
					),
				),
			),
		);
	}

	public function save_feed_settings( $feed_id, $form_id, $settings ) {

		$object = rgar( $settings, 'pipedrive_object' );

		if ( $object === 'person' ) {
			$field_map = rgar( $settings, 'person_field_map', array() );
			if ( empty( rgar( $field_map, 'person_name' ) ) ) {
				GFCommon::add_error_message( esc_html__( 'Person feeds require Person Name to be mapped.', 'gravityformspipedrive' ) );
			}
		}

		if ( $object === 'organization' ) {
			$field_map = rgar( $settings, 'organization_field_map', array() );
			if ( empty( rgar( $field_map, 'organization_name' ) ) ) {
				GFCommon::add_error_message( esc_html__( 'Organization feeds require Organization Name to be mapped.', 'gravityformspipedrive' ) );
			}
		}

		if ( $object === 'deal' ) {
			$field_map = rgar( $settings, 'deal_field_map', array() );

			if ( empty( rgar( $field_map, 'deal_title' ) ) ) {
				GFCommon::add_error_message( esc_html__( 'Deal feeds require Deal Title to be mapped.', 'gravityformspipedrive' ) );
			}

			$has_link = false;
			foreach ( array( 'person_name', 'person_email', 'person_phone', 'organization_name' ) as $key ) {
				if ( ! empty( rgar( $field_map, $key ) ) ) {
					$has_link = true;
					break;
				}
			}
			if ( ! $has_link ) {
				GFCommon::add_error_message( esc_html__( 'Deal feeds must be linked to a Person or Organization. Please map at least one Person or Organization field before saving.', 'gravityformspipedrive' ) );
			}
		}

		return parent::save_feed_settings( $feed_id, $form_id, $settings );
	}

	public function feed_list_columns() {
		return array(
			'feed_name'        => esc_html__( 'Name', 'gravityformspipedrive' ),
			'pipedrive_object' => esc_html__( 'Pipedrive Object', 'gravityformspipedrive' ),
		);
	}

	public function get_column_value_pipedrive_object( $feed ) {
		$object = rgars( $feed, 'meta/pipedrive_object' );
		return ucfirst( $object );
	}

	// ---------------------------------------------------------------------
	// Feed processing
	// ---------------------------------------------------------------------

	public function process_feed( $feed, $entry, $form ) {

		$api_key = $this->get_plugin_setting( 'pipedrive_api_key' );
		if ( empty( $api_key ) ) {
			$this->log_error( __METHOD__ . '(): No Pipedrive API token set; aborting feed.' );
			return false;
		}

		$object = rgars( $feed, 'meta/pipedrive_object' );
		$this->log_debug( __METHOD__ . '(): Processing feed as ' . $object );

		$this->warn_on_unused_sections( $feed, $object );

		switch ( $object ) {
			case 'person':
				return $this->process_person_feed( $feed, $entry, $form, $api_key );
			case 'organization':
				return $this->process_organization_feed( $feed, $entry, $form, $api_key );
			case 'deal':
				return $this->process_deal_feed( $feed, $entry, $form, $api_key );
			default:
				$this->log_error( __METHOD__ . '(): Unknown Pipedrive object: ' . $object );
				return false;
		}
	}

	private function warn_on_unused_sections( $feed, $object ) {

		$unused = array();
		$sections = array(
			'person'       => 'person_field_map',
			'organization' => 'organization_field_map',
			'deal'         => 'deal_field_map',
		);

		foreach ( $sections as $section_object => $meta_key ) {
			if ( $section_object === $object ) {
				continue;
			}
			$map = rgars( $feed, 'meta/' . $meta_key );
			if ( is_array( $map ) ) {
				foreach ( $map as $value ) {
					if ( ! empty( $value ) ) {
						$unused[] = $section_object;
						break;
					}
				}
			}
		}

		if ( ! empty( $unused ) ) {
			$this->log_debug( sprintf(
				'%s(): Warning — this feed has mappings in the following sections that do not match the chosen object (%s) and will be ignored: %s.',
				__METHOD__,
				$object,
				implode( ', ', $unused )
			) );
		}
	}

	private function process_person_feed( $feed, $entry, $form, $api_key ) {

		$field_map = $this->get_field_map_fields( $feed, 'person_field_map' );

		$name  = $this->get_mapped_value( $field_map, 'person_name', $entry, $form );
		$email = $this->get_mapped_value( $field_map, 'person_email', $entry, $form );
		$phone = $this->get_mapped_value( $field_map, 'person_phone', $entry, $form );
		$org   = $this->get_mapped_value( $field_map, 'person_organization', $entry, $form );

		$this->log_debug( sprintf( '%s(): Person values — name: "%s", email: "%s", phone: "%s", organization: "%s"', __METHOD__, $name, $email, $phone, $org ) );

		if ( empty( $name ) ) {
			$this->log_error( __METHOD__ . '(): Person Name resolved empty; cannot create Person.' );
			return $entry;
		}

		$org_id = null;
		if ( ! empty( $org ) ) {
			$org_id = $this->upsert_organization( $org, $api_key );
		}

		$this->upsert_person( $name, $email, $phone, $org_id, $api_key );

		return $entry;
	}

	private function process_organization_feed( $feed, $entry, $form, $api_key ) {

		$field_map = $this->get_field_map_fields( $feed, 'organization_field_map' );
		$name      = $this->get_mapped_value( $field_map, 'organization_name', $entry, $form );
		$address   = $this->get_mapped_value( $field_map, 'organization_address', $entry, $form );

		$this->log_debug( sprintf( '%s(): Organization values — name: "%s", address: "%s"', __METHOD__, $name, $address ) );

		if ( empty( $name ) ) {
			$this->log_error( __METHOD__ . '(): Organization Name resolved empty; cannot create Organization.' );
			return $entry;
		}

		$this->upsert_organization( $name, $api_key, $address );

		return $entry;
	}

	private function process_deal_feed( $feed, $entry, $form, $api_key ) {

		$field_map = $this->get_field_map_fields( $feed, 'deal_field_map' );

		$title        = $this->get_mapped_value( $field_map, 'deal_title', $entry, $form );
		$person_name  = $this->get_mapped_value( $field_map, 'person_name', $entry, $form );
		$person_email = $this->get_mapped_value( $field_map, 'person_email', $entry, $form );
		$person_phone = $this->get_mapped_value( $field_map, 'person_phone', $entry, $form );
		$org_name     = $this->get_mapped_value( $field_map, 'organization_name', $entry, $form );

		$this->log_debug( sprintf(
			'%s(): Deal values — title: "%s", person_name: "%s", person_email: "%s", org_name: "%s"',
			__METHOD__,
			$title,
			$person_name,
			$person_email,
			$org_name
		) );

		if ( empty( $title ) ) {
			$this->log_error( __METHOD__ . '(): Deal Title resolved empty; cannot create Deal.' );
			return $entry;
		}

		$org_id    = null;
		$person_id = null;

		if ( ! empty( $org_name ) ) {
			$org_id = $this->upsert_organization( $org_name, $api_key );
		}

		if ( ! empty( $person_name ) || ! empty( $person_email ) ) {
			$person_id = $this->upsert_person( $person_name, $person_email, $person_phone, $org_id, $api_key );
		}

		if ( empty( $person_id ) && empty( $org_id ) ) {
			$this->log_error( __METHOD__ . '(): Deal creation skipped — no Person or Organization could be created or matched. Mapped link values were empty, or an upstream Pipedrive API call failed (check earlier log lines).' );
			return $entry;
		}

		$this->create_deal( $title, $person_id, $org_id, $api_key );

		return $entry;
	}

	// ---------------------------------------------------------------------
	// Pipedrive API helpers
	// ---------------------------------------------------------------------

	private function api_base() {
		return 'https://api.pipedrive.com/v1/';
	}

	private function get_mapped_value( $field_map, $key, $entry, $form ) {
		$field_id = rgar( (array) $field_map, $key );
		if ( empty( $field_id ) ) {
			return '';
		}
		return (string) $this->get_field_value( $form, $entry, $field_id );
	}

	private function pipedrive_request( $method, $url, $body = array() ) {

		$args = array(
			'method'  => $method,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 20,
		);

		if ( in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log_error( __METHOD__ . '(): Request error: ' . $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$this->log_error( sprintf( '%s(): Pipedrive returned HTTP %d for %s %s. Body: %s', __METHOD__, $code, $method, $url, $raw ) );
			return null;
		}

		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			$this->log_error( __METHOD__ . '(): Pipedrive response not successful. Body: ' . $raw );
			return null;
		}

		return isset( $data['data'] ) ? $data['data'] : null;
	}

	private function upsert_organization( $name, $api_key, $address = '' ) {

		$search_url = add_query_arg(
			array(
				'term'        => $name,
				'fields'      => 'name',
				'exact_match' => 'true',
				'api_token'   => $api_key,
			),
			$this->api_base() . 'organizations/search'
		);

		$search = $this->pipedrive_request( 'GET', $search_url );
		if ( is_array( $search ) && ! empty( $search['items'] ) ) {
			$org_id = (int) $search['items'][0]['item']['id'];
			$this->log_debug( __METHOD__ . '(): Matched existing organization ID ' . $org_id );

			if ( ! empty( $address ) ) {
				$update_url = add_query_arg( array( 'api_token' => $api_key ), $this->api_base() . 'organizations/' . $org_id );
				$this->pipedrive_request( 'PUT', $update_url, array( 'address' => $address ) );
				$this->log_debug( __METHOD__ . '(): Updated organization ID ' . $org_id . ' with new address.' );
			}

			return $org_id;
		}

		$create_body = array( 'name' => $name );
		if ( ! empty( $address ) ) {
			$create_body['address'] = $address;
		}

		$create_url = add_query_arg( array( 'api_token' => $api_key ), $this->api_base() . 'organizations' );
		$created    = $this->pipedrive_request( 'POST', $create_url, $create_body );

		if ( ! empty( $created['id'] ) ) {
			$this->log_debug( __METHOD__ . '(): Created new organization ID ' . $created['id'] );
			return (int) $created['id'];
		}

		return null;
	}

	private function upsert_person( $name, $email, $phone, $org_id, $api_key ) {

		$body = array();
		if ( ! empty( $name ) ) {
			$body['name'] = $name;
		}
		if ( ! empty( $email ) ) {
			$body['email'] = array( $email );
		}
		if ( ! empty( $phone ) ) {
			$body['phone'] = array( $phone );
		}
		if ( ! empty( $org_id ) ) {
			$body['org_id'] = $org_id;
		}

		if ( ! empty( $email ) ) {
			$search_url = add_query_arg(
				array(
					'term'        => $email,
					'fields'      => 'email',
					'exact_match' => 'true',
					'api_token'   => $api_key,
				),
				$this->api_base() . 'persons/search'
			);

			$search = $this->pipedrive_request( 'GET', $search_url );
			if ( is_array( $search ) && ! empty( $search['items'] ) ) {
				$person_id  = (int) $search['items'][0]['item']['id'];
				$update_url = add_query_arg( array( 'api_token' => $api_key ), $this->api_base() . 'persons/' . $person_id );
				$this->pipedrive_request( 'PUT', $update_url, $body );
				$this->log_debug( __METHOD__ . '(): Updated existing person ID ' . $person_id );
				return $person_id;
			}
		}

		if ( empty( $body['name'] ) ) {
			$this->log_error( __METHOD__ . '(): Cannot create person without a name.' );
			return null;
		}

		$create_url = add_query_arg( array( 'api_token' => $api_key ), $this->api_base() . 'persons' );
		$created    = $this->pipedrive_request( 'POST', $create_url, $body );

		if ( ! empty( $created['id'] ) ) {
			$this->log_debug( __METHOD__ . '(): Created new person ID ' . $created['id'] );
			return (int) $created['id'];
		}

		return null;
	}

	private function create_deal( $title, $person_id, $org_id, $api_key ) {

		$body = array( 'title' => $title );
		if ( ! empty( $person_id ) ) {
			$body['person_id'] = $person_id;
		}
		if ( ! empty( $org_id ) ) {
			$body['org_id'] = $org_id;
		}

		$create_url = add_query_arg( array( 'api_token' => $api_key ), $this->api_base() . 'deals' );
		$created    = $this->pipedrive_request( 'POST', $create_url, $body );

		if ( ! empty( $created['id'] ) ) {
			$this->log_debug( __METHOD__ . '(): Created new deal ID ' . $created['id'] );
			return (int) $created['id'];
		}

		$this->log_error( __METHOD__ . '(): Deal creation failed.' );
		return null;
	}
}
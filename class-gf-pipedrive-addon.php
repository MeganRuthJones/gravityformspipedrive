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

	/**
	 * Get an instance of this class.
	 *
	 * @return GFPipedriveAddOn
	 */
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
						'desc'     => esc_html__( 'Enter your Pipedrive API token. You can find this in Pipedrive under Settings → Personal preferences → API.', 'gravityformspipedrive' ),
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
					),
				),
			),
			array(
				'title'  => esc_html__( 'Field Mapping', 'gravityformspipedrive' ),
				'fields' => array(
					array(
						'name'      => 'field_map',
						'label'     => esc_html__( 'Map Fields', 'gravityformspipedrive' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'     => 'person_name',
								'label'    => esc_html__( 'Person Name', 'gravityformspipedrive' ),
								'required' => false,
							),
							array(
								'name'     => 'person_email',
								'label'    => esc_html__( 'Person Email', 'gravityformspipedrive' ),
								'required' => false,
							),
							array(
								'name'     => 'person_phone',
								'label'    => esc_html__( 'Person Phone', 'gravityformspipedrive' ),
								'required' => false,
							),
							array(
								'name'     => 'organization',
								'label'    => esc_html__( 'Organization', 'gravityformspipedrive' ),
								'required' => false,
							),
							array(
								'name'     => 'deal_title',
								'label'    => esc_html__( 'Deal Title', 'gravityformspipedrive' ),
								'required' => false,
							),
							array(
								'name'     => 'deal_value',
								'label'    => esc_html__( 'Deal Value', 'gravityformspipedrive' ),
								'required' => false,
							),
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

	/**
	 * Columns shown on the feed list page.
	 *
	 * @return array
	 */
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

	/**
	 * Process a feed: send entry data to Pipedrive.
	 *
	 * @param array $feed  The feed configuration.
	 * @param array $entry The form entry.
	 * @param array $form  The form object.
	 *
	 * @return array|false The (possibly modified) entry, or false on hard failure.
	 */
	public function process_feed( $feed, $entry, $form ) {

		$api_key = $this->get_plugin_setting( 'pipedrive_api_key' );
		if ( empty( $api_key ) ) {
			$this->log_error( __METHOD__ . '(): No Pipedrive API token set; aborting feed.' );
			return false;
		}

		$object    = rgars( $feed, 'meta/pipedrive_object' );
		$field_map = $this->get_field_map_fields( $feed, 'field_map' );

		$this->log_debug( __METHOD__ . '(): Object: ' . $object . '. Field map: ' . wp_json_encode( $field_map ) );

		$person_id = null;
		$org_id    = null;

		// --- ORGANIZATION: find-or-create when creating an org or deal ---
		if ( $object === 'organization' || $object === 'deal' ) {
			$org_name = $this->get_mapped_value( $field_map, 'organization', $entry, $form );
			if ( ! empty( $org_name ) ) {
				$org_id = $this->upsert_organization( $org_name, $api_key );
			}
		}

		// --- PERSON: find-or-create when creating a person or deal ---
		if ( $object === 'person' || $object === 'deal' ) {
			$person_name  = $this->get_mapped_value( $field_map, 'person_name', $entry, $form );
			$person_email = $this->get_mapped_value( $field_map, 'person_email', $entry, $form );
			$person_phone = $this->get_mapped_value( $field_map, 'person_phone', $entry, $form );

			$this->log_debug( sprintf( '%s(): Resolved person values — name: "%s", email: "%s", phone: "%s"', __METHOD__, $person_name, $person_email, $person_phone ) );

			if ( ! empty( $person_name ) || ! empty( $person_email ) ) {
				$person_id = $this->upsert_person( $person_name, $person_email, $person_phone, $org_id, $api_key );
			} else {
				$this->log_debug( __METHOD__ . '(): Skipping person — name and email both resolved to empty strings.' );
			}
		}

		// --- DEAL: always create new ---
		if ( $object === 'deal' ) {
			$deal_title = $this->get_mapped_value( $field_map, 'deal_title', $entry, $form );
			$deal_value = $this->get_mapped_value( $field_map, 'deal_value', $entry, $form );

			if ( empty( $deal_title ) ) {
				$this->log_error( __METHOD__ . '(): Deal title is empty; skipping deal creation.' );
				return $entry;
			}

			$this->create_deal( $deal_title, $deal_value, $person_id, $org_id, $api_key );
		}

		return $entry;
	}

	// ---------------------------------------------------------------------
	// Pipedrive API helpers
	// ---------------------------------------------------------------------

	/**
	 * Base URL for the Pipedrive API (v1).
	 *
	 * @return string
	 */
	private function api_base() {
		return 'https://api.pipedrive.com/v1/';
	}

	/**
	 * Resolve a mapped form field to its entry value.
	 *
	 * Uses the framework's get_field_value() which correctly handles composite
	 * fields (Name, Address) and returns the concatenated value.
	 *
	 * @param array  $field_map The resolved field_map array (key => field_id).
	 * @param string $key       The mapping key (e.g. 'person_email').
	 * @param array  $entry     The form entry.
	 * @param array  $form      The form object.
	 *
	 * @return string
	 */
	private function get_mapped_value( $field_map, $key, $entry, $form ) {
		$field_id = rgar( (array) $field_map, $key );
		if ( empty( $field_id ) ) {
			return '';
		}
		return (string) $this->get_field_value( $form, $entry, $field_id );
	}

	/**
	 * Make a JSON request to Pipedrive. Returns the decoded `data` node, or null.
	 *
	 * @param string $method  HTTP method (GET, POST, PUT).
	 * @param string $url     Full URL including api_token query param.
	 * @param array  $body    Request body (will be JSON-encoded for write methods).
	 *
	 * @return array|null
	 */
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

	/**
	 * Find an organization by exact name, or create one. Returns the org ID.
	 *
	 * @param string $name
	 * @param string $api_key
	 *
	 * @return int|null
	 */
	private function upsert_organization( $name, $api_key ) {

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
			return $org_id;
		}

		$create_url = add_query_arg( array( 'api_token' => $api_key ), $this->api_base() . 'organizations' );
		$created    = $this->pipedrive_request( 'POST', $create_url, array( 'name' => $name ) );

		if ( ! empty( $created['id'] ) ) {
			$this->log_debug( __METHOD__ . '(): Created new organization ID ' . $created['id'] );
			return (int) $created['id'];
		}

		return null;
	}

	/**
	 * Find a person by email (exact match), or create one. Updates phone/org if provided.
	 *
	 * @param string   $name
	 * @param string   $email
	 * @param string   $phone
	 * @param int|null $org_id
	 * @param string   $api_key
	 *
	 * @return int|null
	 */
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

		// Try to match existing person by email (exact) before creating.
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

		// Require at least a name for new persons (Pipedrive rejects nameless persons).
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

	/**
	 * Create a new deal (always create — no upsert).
	 *
	 * @param string   $title
	 * @param mixed    $value
	 * @param int|null $person_id
	 * @param int|null $org_id
	 * @param string   $api_key
	 *
	 * @return int|null
	 */
	private function create_deal( $title, $value, $person_id, $org_id, $api_key ) {

		$body = array( 'title' => $title );
		if ( $value !== '' && $value !== null ) {
			$body['value'] = $value;
		}
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
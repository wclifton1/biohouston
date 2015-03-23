<?php

// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }


if ( !class_exists( 'Event_Tickets_PRO' ) ) {

	/**
	 * Event_Tickets_PRO main class
	 *
	 * @package Event_Tickets_PRO
	 * @since  1.0
	 * @author Modern Tribe Inc.
	 */
	class Event_Tickets_PRO {

		/**************************************************************
		 * EventBrite Configuration
		 **************************************************************/
		const REQUIRED_TEC_VERSION = '3.9.1';

		protected static $instance;
		public static $errors;
		public static $eventBritePrivacy = 0;
		public static $eventBriteTimezone;
		public static $eventBriteTransport; // https if supported, otherwise http
		public static $pluginVersion = '3.9.1';
		protected $cache_expiration = HOUR_IN_SECONDS; // defaults to 1 hour, use $this->get_cache_expiration() to apply filters
		public $pluginDir;
		public $pluginPath;
		public $pluginUrl;
		public $pluginSlug;

		public static $metaTags = array(
			'_EventBriteId',			// ID in Eventbrite of this event
			'_EventBriteTicketName',
			'_EventBriteTicketDescription',
			'_EventBriteTicketStartDate',
			'_EventBriteTicketStartHours',
			'_EventBriteTicketStartMinutes',
			'_EventBriteTicketStartMeridian',
			'_EventBriteTicketEndDate',
			'_EventBriteTicketEndHours',
			'_EventBriteTicketEndMinutes',
			'_EventBriteTicketEndMeridian',
			'_EventBriteIsDonation',
			'_EventBriteTicketQuantity',
			'_EventBriteIncludeFee',
			'_EventBriteStatus',
			'_EventBriteEventCost',
			'_EventRegister',
			'_EventShowTickets',
			'_EventBritePayment_accept_online',
			'_EventBritePayment_accept_paypal',
			'_EventBritePayment_paypal_email',
			'_EventBritePayment_accept_check',
			'_EventBritePayment_instructions_check',
			'_EventBritePayment_accept_cash',
			'_EventBritePayment_instructions_cash',
			'_EventBritePayment_accept_invoice',
			'_EventBritePayment_instructions_invoice'
		);


		/**
		 * inforce singleton factory method
		 *
		 * @since 1.0
		 * @author jkudish
		 * @return Event_Tickets_PRO
		 */
		public static function instance() {
			if (!isset(self::$instance)) {
				$className = __CLASS__;
				self::$instance = new $className;
			}
			return self::$instance;
		}

		/**
		 * checks whether the The Events Calendar 2.0 or higher is active
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return bool
		 */
		public static function tecActive() {
			return defined('TribeEvents::VERSION') && version_compare( TribeEvents::VERSION, '2.0', '>=');
		}

		/**
		 * class constructer
		 * init necessary functions
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 */
		function __construct() {

			// set internal variables

			$this->pluginPath = trailingslashit( dirname( dirname(__FILE__) ) );
			$this->pluginDir = trailingslashit( basename( $this->pluginPath ) );
			$this->pluginUrl = plugins_url().'/'.$this->pluginDir;


			$this->pluginPath = apply_filters('tribe_eb_pluginpath', trailingslashit( dirname( dirname(__FILE__) ) ) );
			$this->pluginDir = apply_filters('tribe_eb_plugindir', trailingslashit( basename( $this->pluginPath ) ) );
			$this->pluginFile = apply_filters('tribe_eb_pluginfile', $this->pluginDir . 'tribe-eventbrite.php' );
			$this->pluginUrl = apply_filters('tribe_eb_pluginurl', plugins_url().'/'.$this->pluginDir );
			$this->pluginSlug = 'tribe-eventbrite';

			// bootstrap plugin
			self::loadDomain();
			add_action( 'plugins_loaded', array( $this, 'addActions' ) );
			add_action( 'plugins_loaded', array( $this, 'addFilters' ) );

		}

		/**
		 * echo admin error if/when TEC is not active
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function noECPError() {
			$url = 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true';
			$title = __( 'The Events Calendar', 'tribe-events-community' );
			echo '<div class="error"><p>' . sprintf( __( 'To begin using The Events Calendar: Eventbrite Tickets, please install the latest version of <a href="%s" class="thickbox" title="%s">The Events Calendar</a>.', 'tribe-events-community' ), $url, $title ) . '</p></div>';
		}

		/**
		 * Add Eventbrite Tickets to the list of add-ons to check required version.
		 *
		 * @author PaulHughes01
		 * @since 1.0.1
		 * @return array $plugins the existing plugins
		 * @return array the pluggins
		 */
		public function init_addon( $plugins ) {
			$plugins['TribeEB'] = array( 'plugin_name' => 'The Events Calendar: Eventbrite Tickets', 'required_version' => Event_Tickets_PRO::REQUIRED_TEC_VERSION, 'current_version' => self::$pluginVersion, 'plugin_dir_file' => basename( dirname( __FILE__ ) ) . '/tribe-eventbrite.php'  );
			return $plugins;
		}

		/**
		 * run all WordPress action hooks
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function addActions() {
			add_filter( 'tribe_tec_addons', array( $this, 'init_addon' ) );
			if ( !class_exists( 'TribeEvents' ) ) {
				add_action( 'admin_notices', array( $this, 'noECPError' ) );
			} elseif ( $this->tecActive() ) {
				add_action( 'admin_enqueue_scripts' , array( $this, 'adminEnqueue' ));
				add_action( 'admin_menu', array( $this, 'addOptionsPage' ) );
				add_action( 'tribe_events_options_bottom', array( $this, 'eventBriteOptions' ) );
				add_action( 'tribe_events_update_meta', array( $this, 'eventbrite_details' ), 20 );
				add_action( 'tribe_events_update_meta', array( $this, 'addEventMeta' ), 10, 2 );
				add_action( 'tribe_events_event_clear', array( $this, 'clear_details' ) );
				add_action( 'show_user_profile',array( $this, 'userProfilePage' ) );
				add_action( 'edit_user_profile',array( $this, 'userProfilePage' ) );
				add_action( 'edit_user_profile_update', array( $this, 'userProfileUpdate' ) );
				add_action( 'personal_options_update', array( $this, 'userProfileUpdate' ) );
				add_action( 'tribe_events_cost_table', array( $this, 'eventBriteMetaBox'), 1 );
				add_action( 'admin_init', array( $this, 'prepopulateEventBrite') );
				add_action( 'admin_notices', array( $this, 'activationMessage') );
				add_action( 'admin_notices', array( $this, 'eventEditMessage') );
				add_action( 'wp_before_admin_bar_render', array( $this, 'addEventbriteToolbarItems' ), 20 );
				add_action( 'plugin_action_links_' . trailingslashit( $this->pluginDir ) . 'tribe-eventbrite.php', array( $this, 'addLinksToPluginActions' ) );
				add_action( 'tribe_eventbrite_before_integration_header', array( $this, 'addEventbriteLogo' ) );
				add_action( 'tribe_events_single_event_after_the_meta', array( $this, 'displayEventBriteTicketForm' ), 9 );
				add_filter( 'tribe_events_template_paths', array( $this, 'add_eventbrite_template_paths' ) );
			}
		}

		/**
		 * pre-populates an event with Eventbrite info
		 * shows an error on failure
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function prepopulateEventBrite() {

			if ( current_user_can( 'publish_tribe_events' ) && !empty( $_GET['import_eventbrite'] ) && wp_verify_nonce( $_GET['import_eventbrite'], 'import_eventbrite') && !empty( $_GET['eventbrite_id'] ) ) {
				try {
					$event_id = self::importExistingEvent();
					wp_safe_redirect(admin_url('post.php?post=' . $event_id . '&action=edit'));
					exit();
				} catch ( TribeEventsPostException $e ) {
					wp_safe_redirect( admin_url('edit.php?post_type=tribe_events&page=import-eventbrite-events&error=' . urlencode( $e->getMessage() ) ) );
					exit();
				}
			}
		}

		/**
		 * run all WordPress filter hooks
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function addFilters() {
			// add_filter( 'tribe_get_ticket_form', array( $this, 'displayEventBriteTicketForm' ) );
			add_filter( 'tribe_help_tab_forums_url', array( $this, 'helpTabForumsLink' ), 100 );

			// get all pricing items for tickets on the cost field
			add_filter( 'tribe_get_cost', array( $this, 'get_cost' ), 20, 3 );
			add_filter( 'tribe_events_admin_show_cost_field', '__return_false' );
		}

		/**
		 * Apply filters and return the eventbrite cache expiration
		 *
		 * @return int number of seconds until the cache should expire
		 *
		 */
		protected function get_cache_expiration() {
			return apply_filters( 'tribe_events_eb_cache_expiration', $this->cache_expiration );
		}

		/**
		 * Get the ticket costs from Eventbrite
		 *
		 * @param $cost the original cost of the event from tribe_get_cost()
		 * @param $postId the TEC event to get the Eventbrite ticket costs from
		 * @param $withCurrencySymbol whether to add the currency symbol
		 *
		 * @return string $cost the cost of the Eventbrite tickets
		 * @see $this->get_cache_expiration()
		 */
		public function get_cost( $cost, $postId, $withCurrencySymbol ) {

			$postId  = TribeEvents::postIdHelper( $postId );

			// Doing it wrong.
			if ( ! $postId )
				return $cost;

			// if the cache isn't expired we'll use the value stored there
			$cache_expiration = $this->get_cache_expiration();

			// Check if we already have the cost
			$cached_cost_key = 'tribe_eb_cached_cost_' . $postId;

			$cached_cost = get_transient( $cached_cost_key );
			if ( ! $cached_cost ) {
				// the transient doesn't exist, check the postmeta (that was the pre 3.10 way of storing it)
				$postmeta_cached_cost = get_post_meta( $postId, '_EventbriteCost', true );
				if ( ! empty( $postmeta_cached_cost ) ) {
					if ( time() < $postmeta_cached_cost['timestamp'] + $cache_expiration ) {
						// the cost is not expired, let's use it
						$cost = $postmeta_cached_cost['cost'];
					}
					// either we found a valid cached cost or we didn't, but delete the postmeta, we're not using it anymore
					delete_post_meta( $postId, '_EventbriteCost' );
				}

				// at this point, if we didn't get the cost from the postmeta or the transient, let's get it from Eventbrite
				if ( ! $cost ) {
					$eb_cost = $this->get_eb_prices( $postId );
					if ( $eb_cost ) {
						$cost = $eb_cost;
					} else {
						// we didn't find a value from Eventbrite, just return the original value
						return $cost;
					}
				}
				// Update the transient
				set_transient( $cached_cost_key, $cost, $cache_expiration );
			} else {
				$cost = $cached_cost;
			}

			// If desired, attach the currency symbol to each price
			if ( $withCurrencySymbol && is_array( $cost ) ) {
				$cost = array_map( 'tribe_format_currency', $cost );
			}

			// If there's more than one price, this will make them into a range
			if ( is_array( $cost ) ) {
				$cost = implode( ' - ', $cost );
			}

			return apply_filters( 'tribe_eb_event_cost', $cost );

		}

		/**
		 * Get the ticket costs from Eventbrite
		 *
		 * @param $postId the TEC event to get the Eventbrite ticket costs from
		 * @param $only_if_live Only return the cost if the event is live
		 *
		 * @return string|array $prices the price or range of prices of the Eventbrite tickets
		 */
		private function get_eb_prices( $postId, $only_if_live = true ) {
			$eventId = self::getEventId( $postId );

			// we don't have an associated eventbrite event
			if ( ! $eventId ) {
				return false;
			}

			// get the event from eventbrite
			$event = $this->sendEventBriteRequest( 'event_get', 'id=' . $eventId, $postId, false, false, '', false );

			// the eventbrite event we have stored wasn't found on eventbrite
			if ( ! $event ) {
				return false;
			}

			// the event isn't live, we shouldn't use the cost from EB
			if ( $event['event']['status'] != 'Live' && $only_if_live ) {
				return false;
			}

			// no tickets attached to the eventbrite event
			if ( ! isset( $event['event']['tickets'] ) || ! is_array( $event['event']['tickets'] ) ) {
				return false;
			}

			$cost    = '';
			$tickets = $event['event']['tickets'];

			foreach ( $tickets as &$ticket ) {
				if ( $ticket['type'] == 1 ) {
					$ticket['display_price'] = 0;
				}
			}

			// Free / Donation events
			if ( count ( $tickets ) == 1 && $tickets[0]['price'] == 0 ) {
				if ( $tickets[0]['type'] == 0 ) {
					return __( 'Free', 'tribe-eventbrite' );
				} elseif ( $tickets[0]['type'] == 1 ) {
					return __( 'Donation', 'tribe-eventbrite' );
				}
			}

			$prices  = wp_list_pluck( $tickets, 'display_price' );

			// no prices found in the tickets
			if ( empty( $prices ) ) {
				return false;
			}

			// There's more than one ticket, grab the lowest and highest price
			if ( count( $prices ) > 1 ) {
				$prices = array( min( $prices ), max( $prices ) );
			}

			return $prices;

		}

		/**
		 * load plugin text domain
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function loadDomain() {
			$langpath = trailingslashit( basename( dirname( EVENTBRITE_PLUGIN_FILE ) ) ) . 'lang/';

			load_plugin_textdomain( 'tribe-eventbrite', false, $langpath );
		}

		/**
		 * enqueue scripts & styles in the admin
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function adminEnqueue() {
			wp_enqueue_style( 'tribe-eventbrite-admin', $this->pluginUrl . 'resources/eb-tec-admin.css', array(), apply_filters( 'tribe_eventbrite_css_version', self::$pluginVersion ) );
		}

		/**
		 * add view for profile fields
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @param stdClass $profileuser the user object being modified
		 * @return void
		 */
		public function userProfilePage( $profileuser ) {
			if ( !defined('EventBriteProfileDone') ) {
				define('EventBriteProfileDone', 1);
				include_once( $this->pluginPath.'views/eventbrite/user-profile.php' );
			}
		}

		/**
		 * save function for profile updates
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @param int $user_id the user ID to save
		 * @return void
		 */
		public function userProfileUpdate( $user_id ) {
			if ( !defined('EventBriteProfileUpdated') ) {
				define('EventBriteProfileUpdated', 1);
				if ( isset( $_POST['eventbrite_user_key'] ) ) {
					update_user_meta( $user_id, 'eventbrite_user_key', $_POST['eventbrite_user_key'] );
					update_option('tribe_eb_activated', true);
				}
			}
		}

		/**
		 * displays the existing Eventbrite event (admin fields)
		 * when editing an event if an _EventBriteId is present
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function displayExistingEventToggle() {
			global $post;
			$tribe_ecp = TribeEvents::instance();
			$_EventBriteId = get_post_meta( $post->ID, '_EventBriteId', true );
			include_once( $this->pluginPath.'views/eventbrite/add-existing-event.php' );
		}


		/**
		 * saves the meta when creating an Eventbrite event
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 * @param int $postId the ID of the event being edited
		 * @uses $_POST
		 * @return void
		 */
		public function addEventMeta( $postId, $data = array() ) {

			$data = array_merge( $_REQUEST, $data );

			if ( ( isset($data['EventRegister']) && $data['EventRegister'] == 'yes' && !get_post_meta( $postId, '_EventRegister', true) ) || ( isset($data['existingEBEvent']) && $data['existingEBEvent'] == 'yes' && isset($data['ExistingEventId']) && $data['ExistingEventId'] ) ) {
				if ( isset( $data['existingEBEvent'] ) && $data['existingEBEvent'] == 'yes' && isset( $data['ExistingEventId'] ) && $data['ExistingEventId'] ) update_post_meta( $postId, '_EventBriteId', $data['ExistingEventId'] );

				// ignore these keys
				$notFromEventBriteForm = array(
					'_EventBriteId',
					'_EventBriteStatus',
					'_EventRegister'
				);

				foreach( self::$metaTags as $key ) {
					// the added test here should prevent overwriting of post meta fields on event update
					if ( in_array( $key, $notFromEventBriteForm ) ) continue;
					if ( isset( $data['key'] ) ) {
						$postVarKey = substr_replace( $key, '', 0, 1);
						update_post_meta( $postId, $key, $data[$postVarKey] );
					}
				}
			}

			if ( isset( $data['EventShowTickets'] ) ) {
				update_post_meta( $postId, '_EventShowTickets', $data['EventShowTickets'] );
			} elseif ( !isset( $data['EventShowTickets'] ) ) {
				update_post_meta( $postId, '_EventShowTickets', 'yes' );
			}

			if ( !isset( $data['EventRegister'] ) || empty( $data['EventRegister'] ) ) {
				delete_post_meta( $postId, '_EventRegister' );
			}

			if ( isset( $data['_EventbriteTZ'] ) ) {
				update_post_meta( $postId, '_EventbriteTZ', $data['_EventbriteTZ'] );
			}
		}

		/**
		 * Updates the Eventbrite information in WordPress and makes the
		 * API calls to EventBrite to update the listing on their side
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @link http://www.eventbrite.com/api/doc/
		 * @param int $postId the ID of the event being edited
		 * @uses $_POST
		 * @return void
		 */
		public function eventbrite_details( $postId ) {

			if ( isset( $_POST['post_status'] ) && $_POST['post_status'] !== 'publish' ) {
				return;
			}

			try {

	 	    	// clear out eventbrite if no longer stored together
				if ( !isset( $_POST['EventRegister'] ) || $_POST['EventRegister'] != 'yes' ) {
					self::clear_details( $postId );
					return;
				}

		    	// if already saved, return
				if ( isset($_POST['existingEBEvent']) && $_POST['existingEBEvent'] == 'yes' && isset($_POST['ExistingEventId']) && $_POST['ExistingEventId'] )
					return;

				// save the sent fields (flushed on page reload)
				delete_post_meta( $postId, 'tribe-eventbrite-saved-data' ); // precaution
				$sent_fields = array();
				foreach (self::$metaTags as $tag) {
					$post_tag = substr( $tag, 1 );
					$sent_fields[$tag] = isset( $_POST[$post_tag] ) ? $_POST[$post_tag] : null;
				}
				add_post_meta( $postId, 'tribe-eventbrite-saved-data', $sent_fields );

				// create new EB event
				if ( (!isset($EventBriteId) || !$EventBriteId) && $_POST['EventRegister'] ) {

					// make sure all required fields are present
					$required_fields = array(
						'EventBriteTicketName' => __('Ticket Name', 'tribe-eventbrite'),
						'EventBriteTicketStartDate' => __('Date to Start Ticket Sales', 'tribe-eventbrite'),
						'EventBriteTicketEndDate' => __('Date to End Ticket Sales', 'tribe-eventbrite'),
						'EventBriteIsDonation' => __('Ticket Type', 'tribe-eventbrite'),
						'EventBriteEventCost' => __('Ticket Cost', 'tribe-eventbrite'),
						'EventBriteTicketQuantity' => __('Ticket Quantity', 'tribe-eventbrite'),
						'EventBriteIncludeFee' => __('Ticket - Include Fee in Price', 'tribe-eventbrite'),
					);

					$missing_fields = array();
					$sent_fields = array();

					foreach ($required_fields as $required_field_key => $required_field_name) {
						if ( !isset( $_POST[$required_field_key] ) || '' == $_POST[$required_field_key] || null == $_POST[$required_field_key] ) {
							$missing_fields[$required_field_key] = $required_field_name;
						}
					}

					// if all fields are missing, assume the fields weren't meant to be filled out
					if ( count($missing_fields) != count($required_fields) ) {


						// if ticket type is set to Donation or Free, allow cost to be set to null
						if ( isset( $_POST['EventBriteIsDonation'] ) && 0 != $_POST['EventBriteIsDonation'] ) {
							if ( isset( $missing_fields['EventBriteEventCost'] ) ) unset( $missing_fields['EventBriteEventCost'] );
						} elseif ( isset( $_POST['EventBriteEventCost'] ) && !is_numeric( $_POST['EventBriteEventCost'] ) ) {
							$missing_fields['EventBriteEventCost'] = __('Ticket Cost (must be numeric)', 'tribe-eventbrite');
						}

						// if ticket type is set to free, fee inclusion to be set to null
						if ( isset( $_POST['EventBriteIsDonation'] ) && 2 == $_POST['EventBriteIsDonation'] ) {
							if ( isset( $missing_fields['EventBriteIncludeFee'] ) ) unset( $missing_fields['EventBriteIncludeFee'] );
						}

						// assuming a non-free ticket make sure at least one payment method is set
						if ( isset( $_POST['EventBriteIsDonation'] ) ) {
							if ( 2 != $_POST['EventBriteIsDonation'] ) {
								if ( !isset( $_POST['EventBritePayment_accept_online'] ) || !in_array( $_POST['EventBritePayment_accept_online'], array( 'paypal' ) ) ) {
									$missing_fields['payment_method'] = __( 'Ticket Payment Methods (at least one should be selected)', 'tribe-eventbrite' );
								}
							}
						}

						// if paypal is set to true and event is not free, make sure the email came along
						if ( isset( $_POST['EventBritePayment_accept_online'] ) && 'paypal' == $_POST['EventBritePayment_accept_online'] && 2 != $_POST['EventBriteIsDonation'] ) {
							if ( empty( $_POST['EventBritePayment_paypal_email'] ) ) {
								$missing_fields['_EventBritePayment_paypal_email'] = __('Ticket - Paypal Account Email Address', 'tribe-eventbrite');
							} elseif ( !is_email( $_POST['EventBritePayment_paypal_email'] ) ) {
								$missing_fields['_EventBritePayment_paypal_email'] = __('Ticket - Paypal Account Email Address (must be a valid email address)', 'tribe-eventbrite');
							}
						}

						if ( !empty($missing_fields) ) {
							$message = __('All required fields must be filled out correctly in order for this event to be associated to Eventbrite. The following required fields were not filled out (or not correctly):', 'tribe-eventbrite').'<br>';
							foreach ($missing_fields as $missing_field_key => $missing_field_name) {
								$message .= '<span class="admin-indent">'.$missing_field_name.'</span><br>';
							}
							$message .= '<strong>'.__('Please fill in the missing fields and try saving again.', 'tribe-eventbrite').'</strong>';
							throw new TribeEventsPostException( $message );
						}

					}

					// check the dates of the ticket
					if ( isset( $_POST['EventBriteTicketStartDate'] ) ) {
						$date_errors = array();
						$event_start_date = strtotime( get_post_meta( $postId, '_EventStartDate', true ) );
						$event_end_date = strtotime( get_post_meta( $postId, '_EventEndDate', true ) );
						$ticket_start = $_POST['EventBriteTicketStartDate'] . ' ' . $_POST['EventBriteTicketStartHours'] . ':' . $_POST['EventBriteTicketStartMinutes'];
						$ticket_start .= ( isset( $_POST['EventBriteTicketStartMeridian'] ) ) ? $_POST['EventBriteTicketStartMeridian'] : null;
						$ticket_start_timestamp = strtotime( $ticket_start );
						$ticket_end = $_POST['EventBriteTicketEndDate'] . ' ' . $_POST['EventBriteTicketEndHours'] . ':' . $_POST['EventBriteTicketEndMinutes'];
						$ticket_end .= ( isset( $_POST['EventBriteTicketEndMeridian'] ) ) ? $_POST['EventBriteTicketEndMeridian'] : null;
						$ticket_end_timestamp = strtotime( $ticket_end );

						if ( $ticket_start_timestamp > $event_end_date ) {
							$date_errors[] = __( 'Ticket sales start date cannot be after the event ends', 'tribe-eventbrite' );
						}

						if ( $ticket_end_timestamp > $event_end_date ) {
							$date_errors[] = __( 'Ticket sales end date cannot be after the event ends', 'tribe-eventbrite' );
						}

						if ( $ticket_start_timestamp > $ticket_end_timestamp ) {
							$date_errors[] = __( 'Ticket sales start date cannot be after ticket sales end date', 'tribe-eventbrite' );
						}

						if ( !empty( $date_errors ) ) {
							$message = __( 'The dates you have chosen for your ticket sales are inconsistent', 'tribe-eventbrite' ).':<br>';
								foreach ( $date_errors as $error_messsage ) {
									$message .= '<span class="admin-indent">' . $error_messsage . '</span><br>';
								}
								$message .= '<strong>' . __( 'Please adjust the dates and try saving again.', 'tribe-eventbrite' ) . '</strong>';
								throw new TribeEventsPostException( $message );
						}
					}
				}
			} catch ( TribeEventsPostException $e ) {
				update_post_meta( $postId, TribeEvents::EVENTSERROROPT, trim( $e->getMessage() ) );
				return;
			}
     		// get event brite id
			$EventBriteId = get_post_meta( $postId, '_EventBriteId', true );
			// update existing EB event
			if ( $EventBriteId ) {
				self::event_update( $postId, $EventBriteId, $_POST );
				return;
			}

			$tribe_ecp = TribeEvents::instance();
			$eventId = self::event_new( $postId, $_POST );

			if ( $eventId ) {
				// avoid notices
				$venue_id = isset( $venue_id ) ? $venue_id : null;
				$organizer_id = $organizer_id ? $organizer_id : null;
				do_action('tribe_eb_after_event_creation', $eventId, $venue_id, $organizer_id, $postId, $_POST); // allow other plugins to hook in here
				self::ticket_new( $postId, $eventId, $_POST ); // create tickets
				self::payment_update( $postId, $eventId, $_POST ); // payment updates
				self::eventLiveAfterSubmission( $postId, $eventId, $_POST ); // if the status was set to live, let's send an update request here

			}
		}

		/**
		 * Clears/deletes all Eventbrite meta from an event
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param int $postId the ID of the event being edited
		 * @uses self::metaTags
		 * @return void
		 */
		public function clear_details( $postId ) {
			foreach( self::$metaTags as $meta ) {
				delete_post_meta( $postId, $meta );
			}
		}

		/**
		 * Handles Eventbrite ticket creation
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param int $postId the ID of the event being edited
		 * @param int $eventID the Eventbrite ID of the event being edited
		 * @param array $_POST
		 * @return mixed false on failure / request object on success
		 */
		private function ticket_new( $postId, $eventId, $raw_post ) {
			try {
				if ( !is_numeric($eventId) ) {
					return false;
				}
				$request = 'event_id=' . $eventId;
				if ( empty( $raw_post['EventBriteTicketName'] ) ) {
					throw new TribeEventsPostException(__('Tickets must have a name', 'tribe-eventbrite'));
				}
				$request .= '&name=' . urlencode( stripslashes($raw_post['EventBriteTicketName']) );
				if ( $raw_post['EventBriteIsDonation'] == 2 ) {
					$request .= '&price=0.00';
					$request .= '&is_donation=0';
				} elseif ( $raw_post['EventBriteIsDonation'] == 1 ) {
					$request .= '&price=';
					$request .= '&is_donation=1';
				} elseif ( $raw_post['EventBriteIsDonation'] == 0 && !is_numeric( $raw_post['EventBriteEventCost'] ) ) {
					throw new TribeEventsPostException(__('Paid tickets must have a numeric cost.', 'tribe-eventbrite'));
				} else {
					$request .= '&price=' . (float)$raw_post['EventBriteEventCost'];
					$request .= '&is_donation=0';
				}
				if ( !is_numeric( $raw_post['EventBriteTicketQuantity'] ) ) {
					throw new TribeEventsPostException(__('Ticket quantity must be numeric', 'tribe-eventbrite'));
				} else {
					$request .= '&quantity=' . (int)$raw_post['EventBriteTicketQuantity'];
				}
				if ( !empty( $raw_post['EventBriteTicketDescription'] ) ) {
					$request .= '&description=' . urlencode( stripslashes($raw_post['EventBriteTicketDescription']) );
				}

				$start_date = ( isset($raw_post['EventAllDay']) && $raw_post['EventAllDay'] == 'yes' ) ? $raw_post['EventStartDate'] : $raw_post['EventStartDate'] .' '. $raw_post['EventStartHour'].':'. $raw_post['EventStartMinute'].':00';
				$start_date .= ( isset( $raw_post['EventStartMeridian'] ) ) ? $raw_post['EventStartMeridian'] : null;

				$end_date = ( isset($raw_post['EventAllDay']) && $raw_post['EventAllDay'] == 'yes' ) ? $raw_post['EventEndDate'].' +1 day' : $raw_post['EventEndDate'] .' '. $raw_post['EventEndHour'].':'. $raw_post['EventEndMinute'].':00';
				$end_date .= ( isset( $raw_post['EventEndMeridian'] ) ) ? $raw_post['EventEndMeridian'] : null;

				$start_date =  date('Y-m-d H:i:s', strtotime($start_date));
				$end_date =  date('Y-m-d H:i:s', strtotime($end_date));

				if ( !empty( $raw_post['EventBriteTicketStartDate'] ) ) {
					$startDate = $raw_post['EventBriteTicketStartDate'] . ' ' . $raw_post['EventBriteTicketStartHours'] . ":" . $raw_post['EventBriteTicketStartMinutes'] . ':00';
					$startDate .= ( isset( $raw_post['EventBriteTicketStartMeridian'] ) ) ? $raw_post['EventBriteTicketStartMeridian'] : null;
					$startDate = date('Y-m-d H:i:s', strtotime( $startDate ) );
					$request .= '&start_sales=' . urlencode( $startDate );
				}

				if ( !empty( $raw_post['EventBriteTicketEndDate'] ) ) {
					$endDate = $raw_post['EventBriteTicketEndDate'] . ' ' . $raw_post['EventBriteTicketEndHours'] . ':' . $raw_post['EventBriteTicketEndMinutes'] . ':00';
					$endDate .= ( isset( $raw_post['EventBriteTicketEndMeridian'] ) ) ? $raw_post['EventBriteTicketEndMeridian'] : null;
					$endDate = date('Y-m-d H:i:s', strtotime( $endDate ) );
					$request .= '&end_sales=' . urlencode( $endDate );
				}

				$request .= '&include_fee=' . (int)$raw_post['EventBriteIncludeFee'];


				// save ticket - if error, show to user
				$api = new EventbriteAPI( $this->getUserKey( $postId ) );
				if (!get_option('tribe_eb_activated')) update_option('tribe_eb_activated', true);
				return $api->sendEventbriteRequest( 'ticket_new' , $request );
			} catch (TribeEventsPostException $e) {
				$tribe_ecp = TribeEvents::instance();
				$tribe_ecp->setPostExceptionThrown(true);
				update_post_meta( $postId, TribeEvents::EVENTSERROROPT, "Your Eventbrite Ticket was not saved due to the following error: " . $e->getMessage() );
			}
		}

		/**
		 * Handles Eventbrite event update
		 *
		 * @since  1.0
		 * @throws TribeEventsPostException
		 *
		 * @param  int   $postId the ID of the event being edited
		 * @param  int   $eventBriteId the Eventbrite ID of the event being edited
		 * @param  array $raw_post
		 *
		 * @return array the response array
		 */
		private function event_update( $postId, $eventBriteId, $raw_post ) {
			if ( wp_is_post_revision($postId) ) {
				throw new TribeEventsPostException( __( 'This post is a revision and cannot update an Evenbrite event.', 'tribe-eventbrite' ) );
			}
			else {
				delete_post_meta( $postId, TribeEvents::EVENTSERROROPT );
			}

			if ( empty( $raw_post['post_title'] ) ) {
				$tribe_ecp = TribeEvents::instance();
				self::$errors .= 'Event must have a title';
				throw new TribeEventsPostException( __( 'Please supply a post title. This will become the Eventbrite event title.', 'tribe-eventbrite' ) );
			}

			$organizer_id = self::do_event_organizer( $postId );

			if ($organizer_id) {
				$venue_id = self::do_event_venue( $postId, false, $organizer_id );
			}

			$venue_id     = ( isset( $venue_id ) ) ? $venue_id : null;
			$request      = $this->build_event_data( $postId, $eventBriteId, $venue_id, $organizer_id );
			$response_arr = $this->sendEventBriteRequest( 'event_update', $request, $postId );

			if ( isset( $response_arr['process']['id'] ) ) {
				update_post_meta( $postId, '_EventBriteId', $response_arr['process']['id'], true );
			}

			// event is deleted/unregistered
			if ( ! empty( $raw_post['EventBriteStatus'] ) && $raw_post['EventBriteStatus'] == 'Deleted' ) {
				$this->clear_details( $postId );
				update_post_meta( $postId, 'eventbrite_deleted', true);
				delete_post_meta( $postId, 'tribe-eventbrite-saved-data' );
				delete_post_meta( $postId, '_EventBriteId' );
				$raw_post = array();
			}

			return $response_arr;
		}

		/**
		 * Handles Eventbrite event creation
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param int $postId the ID of the event being edited
		 * @param array $_POST
		 * @return mixed false on failure / Eventbrite ID on success
		 */
		private function event_new( $postId, $raw_post ) {

			$post = get_post( $postId );

			if ( wp_is_post_revision($postId) ) {
				throw new TribeEventsPostException( __('This post is a revision and cannot create an evenbrite event.', 'tribe-eventbrite') );
			} else {
				delete_post_meta( $postId, TribeEvents::EVENTSERROROPT );
			}

			$requestParams = '';

			if ( empty( $raw_post['post_title'] ) ) {
				$tribe_ecp = TribeEvents::instance();
				self::$errors .= __('Event must have a title', 'tribe-eventbrite');
				throw new TribeEventsPostException( __( 'Please supply a post title. This will become the Eventbrite event title.', 'tribe-eventbrite') );
			}

			$organizer_id  = self::do_event_organizer( $postId );
			$venue_id      = self::do_event_venue( $postId, false, $organizer_id );
			$requestParams = self::build_event_data( $postId, false, $venue_id, $organizer_id, true );
			$response_arr  = $this->sendEventBriteRequest( 'event_new', $requestParams, $postId );

			if ( isset( $response_arr['process']['id'] ) ) { // success

				$eventBriteId = $response_arr['process']['id'];
				update_post_meta( $postId, '_EventBriteId', $eventBriteId, true );
				return $eventBriteId;

			} else { // failure
				return false;
			}
		}

		/**
		 * marks an Eventbrite event as live after it's created
		 * only if the live status was requested by the user
		 *
		 * @since 1.0
		 * @author jkudish
		 * @param $postId the post ID in WordPress
		 * @param $eventBriteId the Eventbrite ID
		 * @param $postArr the $_POST array from the event_new() function
		 * @see event_new()
		 * @see event_update()
		 * @return bool success or no
		 */
		private function eventLiveAfterSubmission($postId, $eventBriteId, $postArr) {
			if ($postArr['EventBriteStatus'] == 'Live') {
				$response_arr = self::event_update( $postId, $eventBriteId, $postArr );
				if ( isset($response_arr['process']['id']) ) {
					return true;
				} else {
					throw new TribeEventsPostException( __('Your event could not be marked as live in Eventbrite. It was still created as a draft though. Please review the information below and proceed accordingly.', 'tribe-eventbrite'));
				}
			}
			return false;
		}

		/**
		 * Prepares query/request (for events) to run for the Eventbrite API.
		 *
		 * @since  1.0
		 * @author jgabois & Justin Endler
		 *
		 * @param  bool|int $post_id the TEC event ID
		 * @param  bool|int $event_id the eventbrite event ID / defaults to false
		 * @param  bool|int $venue_id the eventbrite venue ID / defaults to false
		 * @param  bool|int $organizer_id the eventbrite organizer ID / defaults to false
		 * @param  bool $new are we creating a new event / defaults to false
		 *
		 * @return string $requestParams the query/request
		 */
		public function build_event_data( $post_id = false, $event_id = false, $venue_id = false, $organizer_id = false, $new = false ) {
			$requestParams = '';

			// General event params
			if ($event_id)
				$requestParams = '&event_id=' . $event_id;

			if ($venue_id) //optional
				$requestParams .= '&venue_id=' . $venue_id;

			if ($organizer_id) //optional
				$requestParams .= '&organizer_id=' . $organizer_id;

			$requestParams .='&title=' . urlencode( stripslashes( $_POST['post_title'] ) );
			if ( !empty( $_POST['content'] ) ) {
				$requestParams .= '&description=' . urlencode( stripslashes( wpautop( $_POST['content'] ) ) );
			}

			// Timezone
			$tz_conversion_needed = false;

			if ( '' != get_post_meta( $post_id, '_EventbriteTZ', true ) ) {
				$tz_conversion_needed = true;
				$timezone = get_post_meta( $post_id, '_EventbriteTZ', true );
			} else {
				$timezone = $this->local_timezone();
			}

			$timezone = apply_filters( 'tribe_eb_event_timezone', $timezone );
			do_action('log', 'Timezone to EB', 'tribe-eventbrite', $timezone);
			$requestParams .= '&timezone=' . urlencode( $timezone );

			// Start/end time params
			$start_date  = ( isset($_POST['EventAllDay']) && $_POST['EventAllDay'] == 'yes' ) ? $_POST['EventStartDate'] : $_POST['EventStartDate'] .' '. $_POST['EventStartHour'].':'. $_POST['EventStartMinute'].':00';
			$start_date .= ( isset( $_POST['EventStartMeridian'] ) && !( isset($_POST['EventAllDay']) && $_POST['EventAllDay'] == 'yes' ) ) ? $_POST['EventStartMeridian'] : null;

			$end_date  = ( isset($_POST['EventAllDay']) && $_POST['EventAllDay'] == 'yes' ) ? $_POST['EventEndDate'].' +1 day' : $_POST['EventEndDate'] .' '. $_POST['EventEndHour'].':'. $_POST['EventEndMinute'].':00';
			$end_date .= ( isset( $_POST['EventEndMeridian'] ) && !( isset($_POST['EventAllDay']) && $_POST['EventAllDay'] == 'yes' ) ) ? $_POST['EventEndMeridian'] : null;

			if ( $tz_conversion_needed ) {
				$start_date = $this->convert_to( $timezone, strtotime( $start_date )  );
				$end_date   = $this->convert_to( $timezone, strtotime( $end_date ) );
			} else {
				$start_date = date( 'Y-m-d H:i:s', strtotime( $start_date ) );
				$end_date   = date( 'Y-m-d H:i:s', strtotime( $end_date ) );
			}

			$requestParams .= '&start_date=' . urlencode( $start_date );
			$requestParams .= '&end_date=' . urlencode( $end_date );

			// Featured image
			if ( has_post_thumbnail( $post_id ) ) {
				$requestParams .= '&custom_header=' . urlencode(get_the_post_thumbnail( $post_id, array( 960, 100000 ) ) );
			}

			if ( isset( $_POST['EventBriteStatus'] ) && ! $new ) { // optional
				$requestParams .= '&status=' . urlencode( strtolower( $_POST['EventBriteStatus'] ) );
			}

			return $requestParams;
		}

		/**
		 * Attempts to convert the provided time (expected to be a Unix timestamp) from local
		 * time to the specified timezone. The returned value is in Y-m-d H:i:s format.
		 *
		 * If the conversion cannot be made, the unadjusted source time is returned.
		 *
		 * @param  string $timezone
		 * @param  int    $datetime
		 *
		 * @return string
		 */
		protected function convert_to( $timezone, $datetime ) {
			try {
				$time = new DateTime( date_i18n( TribeDateUtils::DBDATETIMEFORMAT, $datetime ), new DateTimeZone( $this->local_timezone() ) );
				$time = $time->setTimezone( new DateTimeZone( $timezone ) );
				return $time->format( TribeDateUtils::DBDATETIMEFORMAT );
			}
			catch ( Exception $e ) {
				return date( TribeDateUtils::DBDATETIMEFORMAT, strtotime( $datetime ) );
			}
		}

		/**
		 * Tries to return the local timezone as a timezone string format, even if it is
		 * configured as an offset.
		 *
		 * @return mixed|string|void
		 */
		protected function local_timezone() {
			if ( '' != get_option( 'timezone_string', '' ) ) {
				$timezone = get_option( 'timezone_string' );
			} elseif ( false !== get_option( 'gmt_offset' ) ) {
				$current_offset = get_option('gmt_offset');

				// try to get timezone from gmt_offset, respecting daylight savings
				$timezone = timezone_name_from_abbr(null, $current_offset * 3600, true);

				// if that didn't work, maybe they don't have daylight savings
				if ( $timezone === false ) {
					$timezone = timezone_name_from_abbr(null, $current_offset * 3600, false);
				}

				// and if THAT didn't work, round the gmt_offset down and then try to get the timezone respecting daylight savings
				if ($timezone === false) {
					$timezone = timezone_name_from_abbr(null, (int) $current_offset * 3600, true);
				}

				// lastly if that didn't work, round the gmt_offset down and maybe that TZ doesn't do daylight savings
				if ($timezone === false) {
					$timezone = timezone_name_from_abbr(null, (int) $current_offset * 3600, false);
				}

				// if all else fails, use UTC
				if ($timezone === false) {
					$timezone = 'UTC';
				}

			} else {
				$timezone = 'UTC';
			}

			return $timezone;
		}

		/**
		 * prepares query/request (for venues) to run for the Eventbrite API
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event ID
		 * @param  int $venue_id the venue ID / defaults to false
		 * @param  int $organizer_id the organizer ID / defaults to false
		 * @return string $requestParams the query/request
		 */
		public function build_venue_data( $event_id, $venue_id = false, $organizer_id = false ) {
			$requestParams = '';

			if ($venue_id)
				$requestParams = 'id=' . urlencode( esc_attr($venue_id) );

			$requestParams .= '&organizer_id=' . urlencode( esc_attr($organizer_id) );
			$requestParams .= '&venue=' . urlencode( esc_attr( remove_accents( tribe_get_venue( $event_id ) ) ) );
			$requestParams .= '&adress=' . urlencode( esc_attr( remove_accents( tribe_get_address( $event_id ) ) ) );
			$requestParams .= '&city=' . urlencode( esc_attr( remove_accents( tribe_get_city( $event_id ) ) ) );
			$requestParams .= '&postal_code=' . urlencode( esc_attr( tribe_get_zip( $event_id ) )  );

			$region = self::get_region( $event_id );
			if ( $region ) {
				$requestParams .= '&region=' . urlencode( $region );
			}

			$country_code = self::get_country_code( $event_id );
			if ( $country_code ) {
				$requestParams .= '&country_code=' . urlencode( $country_code );
			}
			return $requestParams;
		}

		/**
		 * get a country code from an event id
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event id
		 * @return string $code the country code
		 */
		private function get_country_code( $event_id = null ) {
			$country = tribe_get_country( $event_id );

			if ( empty( $country ) )
				return;

			$tribe_ecp = TribeEvents::instance();
			$countries = TribeEventsViewHelpers::constructCountries();
			$code = array_search($country, $countries);
			return $code;
		}

		/**
		 * get a region from an event id
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event id
		 * @return string $region the region name
		 */
		private function get_region( $event_id = null ) {
			$region = tribe_get_region( $event_id );

			if ( empty( $region ) )
				return;

			return esc_attr( remove_accents( $region ) );
		}

		/**
		 * sends the venue data to Eventbrite API
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event ID
		 * @param  int $eb_venue_id the EventBrite venue ID / defaults to false
		 * @param  int $organizer_id the organizer ID / defaults to false
		 * @return mixed the response string or an error on failure
		 */
		private function do_event_venue( $event_id, $eb_venue_id = false, $organizer_id = false ) {

			// if no venue attached to the event, return
			$venue_id = tribe_get_venue_id( $event_id );
			if ( ! $venue_id )
				return false;

			// check state + country
			$country = self::get_country_code( $event_id );
			if ( empty( $country ) ) {
				$message = '<strong>'.__( 'Eventbrite requires that the venue have a country, so the venue information was not sent to Eventbrite.', 'tribe-eventbrite' ).'</strong>';
				$message .= '<br>' . __( 'If you want to update the venue at Eventbrite, add a country to the venue now and Update this event.', 'tribe-eventbrite' );
				$message .= ( class_exists('TribeEventsPro') && !empty( $venue_id ) ) ? ' <a target="_blank" href="' . get_edit_post_link( $venue_id ) . '">' . __('Edit the venue', 'tribe-eventbrite') . ' &raquo;</a>' : '';
				update_post_meta( $event_id, TribeEvents::EVENTSERROROPT, trim( $message ) );
				return false;
			}
			if ( $country == 'US' ) {
				$region = self::get_region( $event_id );
				if ( !$region ) {
					$message = '<strong>'.__( 'Eventbrite requires that you specify the state for venues in the United States, so the venue was not sent to Eventbrite.', 'tribe-eventbrite' ).'</strong>';
					$message .= '<br>' . __( 'If you want to update the venue at Eventbrite, add a state to the venue now and Update this event.', 'tribe-eventbrite' );
					$message .= ( class_exists('TribeEventsPro') && !empty( $venue_id ) ) ? ' <a target="_blank" href="' . get_edit_post_link( $venue_id ) . '">' . __('Edit the venue', 'tribe-eventbrite') . ' &raquo;</a>' : '';
					update_post_meta( $event_id, TribeEvents::EVENTSERROROPT, trim( $message ) ) ;
					return false;
				}
			}

			if (!$eb_venue_id)
				$eb_venue_id = get_post_meta( $venue_id, '_VenueEventBriteID'.$organizer_id, true );

			// if no stored EB Venue ID, let's search it just to be safe
			if ( !isset( $eb_venue_id ) || !$eb_venue_id ) {
				$_venues = $this->sendEventBriteRequest( 'user_list_venues', null, null, false, true, false );
				if ( is_array($_venues) && !empty($_venues) ) {
					$venues = wp_list_pluck( $_venues['venues'], 'venue' );
					$venue_names = wp_list_pluck( $venues, 'name' );
					$venue_index = array_search( tribe_get_venue( $venue_id ), $venue_names );
					if ( false !== $venue_index ) {
						$eb_venue_id = $venues[$venue_index]['id'];
					}
				}
			}

			$requestParams = self::build_venue_data($event_id, $eb_venue_id, $organizer_id);

			$mode = ($eb_venue_id) ? 'venue_update' : 'venue_new';
			if ( $mode == 'venue_update' ) {
				$response_arr = $this->sendEventBriteRequest( $mode, $requestParams, $event_id, false, true, false );
				if ( !isset( $response_arr['process']['id'] ) ) {
					// if update fails, let's send another request with new
					$response_arr = $this->sendEventBriteRequest( 'venue_new', $requestParams, $event_id );
				}
			} else {
				$response_arr = $this->sendEventBriteRequest( $mode, $requestParams, $event_id );
			}

			if ( isset( $response_arr['process']['id'] ) ) {
				update_post_meta( $venue_id, '_VenueEventBriteID'.$organizer_id, $response_arr['process']['id'], true );
				return $response_arr['process']['id'];
			} else {
				//The API returned the correct Venue ID for us.
				if (preg_match('/The venue id is \[([0-9]*)\]/', $response_arr['error']['error_message'], $matches)) {
					update_post_meta( $venue_id, '_VenueEventBriteID'.$organizer_id, $matches[1] );
					return self::do_event_venue($event_id, $matches[1], $organizer_id);
				} else {
					update_post_meta( $venue_id, TribeEvents::EVENTSERROROPT, __('There was an error sending the venue to Eventbrite. Please try again.', 'tribe-eventbrite') );
				}
			}
		}


		/**
		 * sends the organizer data to Eventbrite API
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event ID
		 * @return mixed the response string or an error on failure
		 */
		private function do_event_organizer( $event_id ){

			$eventOrgID = get_post_meta( $event_id, '_EventOrganizerID', true );

			if ( $eventOrgID ) {
				$storedEBOrgID  = get_post_meta( $eventOrgID, '_OrganizerEventBriteID', true );
				$organizer_name = get_the_title( $eventOrgID );
				$email          = tribe_get_organizer_email( $event_id );
				$website        = tribe_get_organizer_link( $event_id, false, false );
				$phone          = tribe_get_organizer_phone( $event_id );
			} else {
				// if there's no organizer associated to this event, let's make the author the organizer
				$event = get_post( $event_id );
				if ( ! empty( $event->post_author ) ) {
					$user = get_userdata( $event->post_author );
					if ( ! is_wp_error( $user ) ) {
						$storedEBOrgID  = get_user_meta( $user->ID, '_OrganizerEventBriteID', true );
						$organizer_name = $user->display_name;
					}
				}
			}

			if ( empty( $organizer_name ) || !$organizer_name ) {
				$organizer_name = __( 'Unnamed Organizer', 'tribe-eventbrite' );
			}

			// if no stored EB Organizer ID, let's search it just to be safe
			if ( !isset( $storedEBOrgID ) || !$storedEBOrgID ) {
				$_organizers = $this->sendEventBriteRequest( 'user_list_organizers', null, null, false, true, false );
				if ( is_array($_organizers) && !empty($_organizers) ) {
					$organizers = wp_list_pluck( $_organizers['organizers'], 'organizer' );
					$organizer_names = wp_list_pluck( $organizers, 'name' );
					$organizer_index = array_search( $organizer_name, $organizer_names );
					if ( false !== $organizer_index ) {
						$storedEBOrgID = $organizers[$organizer_index]['id'];
					}
				}
			}

			$mode = ( isset( $storedEBOrgID ) && $storedEBOrgID ) ? 'organizer_update' : 'organizer_new';

			$requestParams = 'name=' . urlencode( $organizer_name );

			if ( !empty( $email ) || !empty( $website ) || !empty( $phone ) ) {
				$emailLink = !empty( $email ) ? "<a href='mailto:$email'>$email</a>" : '';
				$websiteLink = !empty( $website ) ? "<a href='$website'>$website</a>" : '';
				$requestParams .= '&description=' .  urlencode( "Email: $emailLink\n<br/>Website: $websiteLink\n<br/>Phone: $phone" );
			}

			do_action( 'log', 'organizer info', 'tribe-eventbrite', $requestParams );

			if ($storedEBOrgID)
				$requestParams .= '&id=' . $storedEBOrgID;
			
			$response_arr = $this->sendEventBriteRequest( $mode, $requestParams, $event_id );

			if ( isset( $response_arr['process']['id'] ) ) {
				if ( $eventOrgID ) {
					update_post_meta( $eventOrgID, '_OrganizerEventBriteID', $response_arr['process']['id'] );
				} elseif ( !is_wp_error( $user ) ) {
					update_user_meta( $user->ID, '_OrganizerEventBriteID', $response_arr['process']['id'] );
				}
				return $response_arr['process']['id'];
			}
		}

		/**
		 * retrieves data from an existing Eventbrite event
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event ID
		 * @return mixed error on failure / json string of the event on success
		 */
		public function importExistingEvent() {
			add_filter( 'tribe-post-origin', array( $this, 'addImportedEventOrigin' ) );

			$requestParams = 'id=' . $_REQUEST['eventbrite_id'].'&display=custom_header';
			$event = $this->sendEventBriteRequest( 'event_get', $requestParams, null, true );

			if ( isset( $event['event']['id'] ) ) {
				if ( ! $this->isEventImported( $event['event']['id'] ) ) {
					$eb_event     = $event['event'];
					$eb_venue     = $eb_event['venue'];
					$eb_organizer = $eb_event['organizer'];

					// insert new ECP event
					$postdata = array(
						'post_title'     => $eb_event['title'],
						'post_type'      => TribeEvents::POSTTYPE,
						'post_content'   => html_entity_decode( $eb_event['description'] ),
						'post_status'    => 'draft',
						'_EventBriteId'  => $eb_event['id'],
						'_EventRegister' => 'yes',
						'_EventbriteTZ'  => $eb_event['timezone']
					);

					// save a new organizer
					if ( $eb_organizer['id'] ) {
						$postdata['_OrganizerEventBriteID'] = $eb_organizer['id'];

						// don't create a new organizer if this one is already imported
						$ecp_org_id = self::isOrganizerImported( $eb_organizer['id'] );
						$organizerData = array();

						if ( ! $ecp_org_id ) {
							$organizerData['Organizer'] = $eb_organizer['name'];
						} else {
							$organizerData['OrganizerID'] = $ecp_org_id;
						}

						$postdata['Organizer'] = $organizerData;
						$_POST['Organizer']    = $organizerData;
					}

					if ( $eb_venue['id'] ) {
						$postdata['_VenueEventBriteID'] = $eb_venue['id'];
						// Don't create a new venue if this one is already imported
						$ecp_venue_id = self::isVenueImported( $eb_venue, $eb_organizer['id'] );

						$venueData = array();

						if ( ! $ecp_venue_id ) {
							$venueData['Address'] = ( ! empty( $eb_venue['address'] ) ) ? $eb_venue['address'] : null;
							$venueData['Address'] .= ( ! empty( $eb_venue['address2'] ) ) ? $eb_venue['address2'] : null;
							$venueData['Venue']    = ( ! empty( $eb_venue['name'] ) ) ? $eb_venue['name'] : null;
							$venueData['Country']  = ( ! empty( $eb_venue['country'] ) ) ? $eb_venue['country'] : null;
							$venueData['Zip']      = ( ! empty( $eb_venue['postal_code'] ) ) ? $eb_venue['postal_code'] : null;
							$venueData['State']    = ( ! empty( $eb_venue['region'] ) ) ? $eb_venue['region'] : null;
							$venueData['Province'] = ( ! empty( $eb_venue['region'] ) ) ? $eb_venue['region'] : null;
							$venueData['City']     = ( ! empty( $eb_venue['city'] ) ) ? $eb_venue['city'] : null;
						} else {
							$venueData['VenueID'] = $ecp_venue_id;
						}

						$postdata['Venue'] = $venueData;
						$_POST['Venue'] = $venueData;
					}

					$postdata = array_merge( $postdata, self::setupEventMeta( $eb_event ) );

					remove_action( 'tribe_events_update_meta', array( $this, 'eventbrite_details' ), 20 );
					add_action( 'tribe_events_update_meta', array( $this, 'link_imported_event_data' ), 10, 2 );
					$event_id = tribe_create_event( $postdata );

					if ( ! is_wp_error( $event_id ) ) {
						// try to get the image from the eventbrite custom header and set it as the featured image
						if ( $eb_event['custom_header'] ) {
							$custom_header = new DOMDocument();
							$custom_header->strictErrorChecking = false;
							libxml_use_internal_errors(true);
							// echo htmlentities( $theevent['custom_header'], ENT_HTML5 | ENT_NOQUOTES );
							$custom_header->recover = true;
							$custom_header->loadHTML( $eb_event['custom_header'] );
							if ( $custom_header ) {
								$tags = $custom_header->getElementsByTagName( 'img' );
								for ( $i = 0; $i < $tags->length; $i++ ) {
									$img = $tags->item( $i );
									if ( $img ) {
										$img_src = $img->getAttribute( 'src' );
										if ( is_string( $img_src ) ) {
											// media_sideload_image returns an img tag, so we just snagged some code from that function
											$tmp = download_url( $img_src );
											if ( is_wp_error( $tmp ) ) {
												@unlink($file_array['tmp_name']);
												continue;
											}
											preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $img_src, $matches );
											$file_array['name'] = basename($matches[0]);
											$file_array['tmp_name'] = $tmp;
						
											$id = media_handle_sideload( $file_array, $event_id, __( 'Image imported from Eventbrite.', 'tribe-eventbrite' ) );
											if ( $id  && ! is_wp_error( $id ) ) {
												set_post_thumbnail( $event_id, $id );
											}
										}
									}
								}
								// If the last $tmp was an error, notify the user we couldn't find an image
								if ( is_wp_error( $tmp ) ) {
									update_post_meta( $event_id, TribeEvents::EVENTSERROROPT, __( 'We were unable to read any image URLs in your custom header. Your event was imported from Eventbrite, but no featured image was set.', 'tribe-eventbrite' ) );
								}
							}
						}
						return $event_id;
					} else {
						throw new TribeEventsPostException(__('We were unable to import your Eventbrite event. Please try again.', 'tribe-eventbrite'));
					}
				} else {
					throw new TribeEventsPostException(__('Event already imported.', 'tribe-eventbrite'));
				}
			} else {
				throw new TribeEventsPostException(__('We were unable to import your Eventbrite event. Please verify the event id and try again.', 'tribe-eventbrite'));
			}
			remove_filter( 'tribe-post-origin', array( $this, 'addImportedEventOrigin' ) );
		}

		/**
		 * links existing data with an imported event from Eventbrite
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $event_id the event ID
		 * @param  mixed $data the event's data
		 * @return void
		 */
		public function link_imported_event_data( $event_id, $data ) {

			$eb_event_id = $data['_EventBriteId'];
			$eb_organizer_id = $data['_OrganizerEventBriteID'];
			$eb_venue_id = $data['_VenueEventBriteID'];

			$ecp_venue = get_post_meta( $event_id, '_EventVenueID', true);
			$ecp_organizer = get_post_meta( $event_id, '_EventOrganizerID', true);

			update_post_meta( $event_id, '_EventBriteId', $eb_event_id );
			update_post_meta( $event_id, '_EventRegister', 'yes' );

			if ( $ecp_organizer && $eb_organizer_id ) {
				update_post_meta( $ecp_organizer, '_OrganizerEventBriteID', $eb_organizer_id);

				if ( $ecp_venue && $eb_venue_id ) {
					update_post_meta( $ecp_venue, '_VenueEventBriteId' . $eb_organizer_id, $eb_venue_id);
				}
			}
		}

		/**
		 * see if an ECP event is already linked to this event
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $ebEventId the Eventbrite event ID
		 * @param  mixed $data the event's data
		 * @return bool
		 */
		private function isEventImported( $ebEventId ) {
			$events = new WP_Query( array( 'meta_key' => '_EventBriteId', 'meta_value' => $ebEventId, 'post_type'=> TribeEvents::POSTTYPE ) );
			return $events->have_posts();
		}

		/**
		 * Check if an Eventbrite venue has already been imported, returing the venue ID
		 * if it has or boolean false if not.
		 *
		 * In strict mode - which is turned off by default - we require that the Eventbrite
		 * ID for the venue matches. Strict mode can be enabled with:
		 *
		 *     add_filter( 'tribe_eb_strict_venue_imports', '__return_true' );
		 *
		 * On the other hand, when strict mode is off if we fail to locate a venue with the
		 * precise Evenbrite ID we are looking for we go on to try and detect a venue with a
		 * matching name, initial address line, city, country and zip and use the first available
		 * match. This helps to avoid issues with what to all intents and purposes look like
		 * duplicate venues being pulled in.
		 *
		 * @since 1.0
		 * @param array $venue_details
		 * @param $organizer_id
		 * @return mixed false|int
		 */
		private function isVenueImported( $venue_details, $organizer_id ) {
			// Check to see if the very same venue has already been imported
			$venues = new WP_Query( array(
				'meta_key' => '_VenueEventBriteID' . $organizer_id,
				'meta_value' => $venue_details['id'],
				'post_type' => TribeEvents::VENUE_POST_TYPE
			) );

			// Utilize the first match
			if ( $venues->have_posts() ) {
				$venues->the_post();
				return get_the_ID();
			}

			// If we are strictly interested in the same venue entity, return false at this point
			if ( apply_filters( 'tribe_eb_strict_venue_imports', false ) ) return false;

			// Check to see if what is essentially the same venue - but perhaps doesn't share the
			// same Eventbrite ID - is already in existence (this can help to prevent what are
			// effectively duplicate venues from building up in some circumstances)
			$venues = new WP_Query( array(
				'post_type' => TribeEvents::VENUE_POST_TYPE,
				'meta_query' => array(
					array( 'key' => '_VenueVenue',   'value' => $venue_details['name'] ),
					array( 'key' => '_VenueAddress', 'value' => $venue_details['address'] ),
					array( 'key' => '_VenueCity',    'value' => $venue_details['city'] ),
					array( 'key' => '_VenueCountry', 'value' => $venue_details['country'] ),
					array( 'key' => '_VenueZip',     'value' => $venue_details['postal_code'] ),
			) ) );

			// Utilize the first match
			if ( $venues->have_posts() ) {
				$venues->the_post();
				return get_the_ID();
			}

			return false;
		}

		/**
		 * see if an Eventbrite organizer exists in ECP
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $ebOrganizerId the Eventbrite organizer ID
		 * @return mixed false on failure / the organizer ID on success
		 */
		private function isOrganizerImported( $ebOrganizerId ) {
			$organizers = new WP_Query( array( 'meta_key' => '_OrganizerEventBriteID', 'meta_value' => $ebOrganizerId, 'post_type'=> TribeEvents::ORGANIZER_POST_TYPE ) );

			if ( $organizers->have_posts() ) {
				$organizers->the_post();
				return get_the_ID();
			}

			return false;
		}

		/**
		 * returns filter value for tribe-post-origin.
		 * @since 1.0
		 * @author PaulHughes01
		 * @return string $origin
		 */
		public function addImportedEventOrigin() {
			$origin = 'eventbrite-tickets';
			return $origin;
		}

		/**
		 * set's up the dates for an imported event
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  array $ebEvent the event
		 * @return array $eventMeta the meta
		 */
		private function setupEventMeta( $ebEvent ) {
			$eventMeta = array();

			// Timezone correction
			$ebEvent = $this->adjust_for_timezones( $ebEvent );

			// match EB api keys with input names
			$matchedKeysNames = array(
				'start_date' 			=> 'EventStartDate',
				'end_date' 				=> 'EventEndDate'
			);
			// construct output array from EB response

			foreach ( $matchedKeysNames as $ebKey => $name ) {
				$responseValue = $ebEvent[$ebKey];
				// format time output
				$startEnd = str_replace( array('Event','Date'), '', $name );

				$date = explode(' ',$responseValue);
				$eventMeta[$name] = $date[0];

				$time = explode( ':', $date[1] );
				$eventMeta['Event'.$startEnd.'Minute'] = $time[1];
				if ( strstr( get_option( 'time_format', TribeDateUtils::TIMEFORMAT ), 'H' ) ) {
					$eventMeta['Event'.$startEnd.'Hour'] = $time[0];
				} else {
					if ( $time[0] > 12 ) {
						$time[0] -= 12;
						if ( $time[0] < 10 ) $time[0] = '0' . $time[0];
						$amPm = ( strstr( $timeFormat, 'a' ) ) ? 'pm' : 'PM' ;
					} else {
						$amPm = ( strstr( $timeFormat, 'a' ) ) ? 'am' : 'AM' ;
					}
					$eventMeta['Event'.$startEnd.'Hour'] = ( $time[0] == '00' ) ? '12' : $time[0];
					$eventMeta['Event'.$startEnd.'Meridian'] = $amPm;
				}
			}

			// check if the event is an all day event
			if (
					( $eventMeta['EventStartHour'] == '12' && $eventMeta['EventStartMinute'] == '00' && $eventMeta['EventStartMeridian'] == 'am' ) // start should always be midnight
					&& ( // check the end date, 2 possibilities
						( ( $eventMeta['EventEndHour'] == '12' && $eventMeta['EventEndMinute'] == '00' && $eventMeta['EventEndMeridian'] == 'am' ) && ( $eventMeta['EventStartDate'] != $event['EventEndDate'] ) ) // end can be midnight as long as start/end dates don't match
						|| ( $eventMeta['EventEndHour'] == '11' && $eventMeta['EventEndMinute'] == '59' && $eventMeta['EventEndMeridian'] == 'pm' ) // end can also be 11:59p
						)
				) {
					$eventMeta['EventAllDay'] = 'yes';
				}

			return $eventMeta;
		}

		protected function adjust_for_timezones( array $event ) {
			$wp_timezone = get_option( 'timezone_string', '' );
			$time_offset = get_option( 'gmt_offset', '' );
			$start_end   = array( 'start_date', 'end_date' );

			// Do we have enough information to make the adjustment?
			if ( empty( $wp_timezone ) && empty( $time_offset ) ) {
				return $event;
			}
			elseif ( ! isset( $event['timezone'] ) ) {
				return $event;
			}

			// If we are adjusting according to a GMT offset we need to ensure we have the
			// appropriate helper function available
			if ( ! method_exists( 'TribeDateUtils', 'get_modifier_from_offset' ) ) {
				return $event;
			}

			// Not all installations will support all timezones
			try {
				foreach ( $start_end as $date ) {
					$time = new DateTime( $event[$date], new DateTimeZone( $event['timezone'] ) );

					// Apply the WordPress timezone
					if ( ! empty( $wp_timezone ) ) {
						$time->setTimezone( new DateTimeZone( $wp_timezone ) );
					}
					// Else modify according to the GMT offset
					else {
						$adjustment = TribeDateUtils::get_modifier_from_offset( $time_offset );
						$time->setTimezone( new DateTimeZone( 'GMT' ) );
						$time->modify( $adjustment );
					}

					$$date = $time->format( TribeDateUtils::DBDATETIMEFORMAT );
				}
			}
			// Return the event data unmodified if we hit a problem
			catch ( Exception $e ) {
				return $event;
			}

			// Update with the adjusted times and return
			foreach ( $start_end as $date ) {
				if ( isset( $$date ) ) $event[$date] = $$date;
			}

			return $event;
		}

		/**
		 * handles Eventbrite payment_update api call
		 *
		 * @link http://www.eventbrite.com/api/doc/payment_update
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  int $postId the post ID
		 * @param  int $eventId the event ID
		 * @param  array $global_post
		 * @return void
		 */
		public function payment_update( $postId, $eventId, $global_post ) {
			$paymentTypes = array('check' => '', 'cash' => '', 'invoice' => '');
			$requestParams = 'event_id=' . $eventId;

			// make it accept cash if free & assign a message
			if ( 2 == $global_post['EventBriteIsDonation'] ) {
				$requestParams .= '&accept_cash=1';
				$requestParams .= '&instructions_cash=' . urlencode( __( 'This event is free to attend', 'tribe-eventbrite' ) );
			} else {
				// otherwise loop through the payment methods as it's supposed to
				foreach( $paymentTypes as $key => $val ) {
					$onOff = $global_post['EventBritePayment_accept_' . $key];
					if ( $onOff ) $requestParams .= '&accept_' . $key . '=' . $onOff;
					$paymentTypes[$key] = $onOff;
				}
				foreach( $paymentTypes as $key => $val ) {
					if ( $val ) {
	               $instructions = $global_post['EventBritePayment_instructions_'.$key];
	               if ( $instructions ) $requestParams .= '&instructions_' . $key . '=' . urlencode( stripslashes( $instructions ) );
					}
				}

			    // Online payment method is either/or (not both)
			    $onlineMethod = $global_post['EventBritePayment_accept_online'];
			    if ( !empty( $global_post['EventBritePayment_accept_online'] ) ) {
				    switch( $onlineMethod ) {
			  	    case 'paypal':
				      	$requestParams .= '&accept_paypal=1&paypal_email=' . $global_post['EventBritePayment_paypal_email'];
								break;
			     	}
		    	}
		    }

			$response_arr = $this->sendEventBriteRequest( 'payment_update', $requestParams, $postId );
		}

		/**
		 * returns options for printable payment options
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  string $arrayMember the payment method
		 * @return mixed false on failure / payment option string on success
		 */
		public function printablePaymentOption( $arrayMember ) {
			switch( $arrayMember ) {
				case '_EventBritePayment_accept_paypal':
					return __('PayPal Payments', 'tribe-eventbrite');
					break;
				case '_EventBritePayment_accept_check':
					return __('Pay by check', 'tribe-eventbrite');
					break;
				case '_EventBritePayment_accept_cash':
					return __('Pay at the door', 'tribe-eventbrite');
					break;
				case '_EventBritePayment_accept_invoice':
					return __('Send an invoice', 'tribe-eventbrite');
					break;
				default:
					return false;
			}
		}

		/**
		 * returns the Eventbrite API key for the current author or the current user
		 *
		 * @since 1.0
		 * @author jgabois, Justin Endler & jkudish
		 * @param  int $postId the event ID
		 * @param  bool $isEbImport are we importing?
		 * @return mixed false on failure / the API key on success
		 */
		public function getUserKey( $postId = null, $isEbImport = false ) {
			if ( (int) $postId > 0 ) {
				$post = get_post( $postId );
				$key = ( is_object($post) && isset($post->post_author) ) ? get_user_meta( $post->post_author, 'eventbrite_user_key', true ): false;
			} else {
				$user_id = get_current_user_id();
				$key = ($user_id) ? get_user_meta( $user_id, 'eventbrite_user_key', true ) : false;
			}

			$key = ( isset($key) && is_string($key) && $key != '' ) ? $key : false;

			// got here? no key exists
			return apply_filters('tribe_eb_user_key', $key );
		}

		/**
		 * wrapper for the Eventbrite API
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param  string $action the API action being taken
		 * @param  array $params the paramaters for the API action
		 * @param  int $postId the event ID
		 * @param  bool $isEbImport are we importing?
		 * @param  bool $ignore_errors ignore returned errors
		 * @param  string $default default value to return if ignore errors
		 * @return mixed null on failure / the API key on success
		 */
		public function sendEventBriteRequest( $action, $params = null, $postId = null, $isEbImport = false, $ignore_errors = false, $default = '', $with_auth = true ) {
				try {
					$api = new EventbriteAPI( $this->getUserKey( $postId, $isEbImport ) );
					$response = $api->sendEventbriteRequest( $action, $params, $ignore_errors, $default );
				} catch ( TribeEventsPostException $e ) {
					$postId = TribeEvents::postIdHelper( $postId );
					if ( $postId ) {
						update_post_meta( $postId, TribeEvents::EVENTSERROROPT, trim( $e->getMessage() ) );
					}
					$response = false;
				}
				return $response;
		}

		/**
		 * Human Error Messages
		 * Maps EB errors to human readable & translated messages
		 *
		 * @since  1.0
		 * @author jkudish
		 * @return array list of human readible errors
		 */
		public function humanErrorMessages() {
			return array(
				'Invalid user_key1' => __('Your user API Key is invalid. Please go to your profile and adjust it.', 'tribe-eventbrite'),
				'The specified user was not found or the login credentials didn’t match.' => __('The specified user was not found or the login credentials didn’t match. Please go to your profile and verify your API Key.', 'tribe-eventbrite'),
				'The event id is missing.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'No such event.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'The total number of tickets exceeds the maximum allowed (50).' => __('The total number of tickets exceeds the maximum allowed (50)', 'tribe-eventbrite'),
				'The ticket name is missing.' => __('The ticket name is missing. Please enter a ticket name and try again.', 'tribe-eventbrite'),
				'End sales less than Start Sales.' => __('The date you selected for ticket sales to end is before the date you selected for ticket sales to start, please adjust your dates accordingly.', 'tribe-eventbrite'),
				'End sales date greater than Event\'s Ending date.' => __('The date you selected for ticket sales to end is after the date you selected for the event to end, please adjust your dates accordingly.', 'tribe-eventbrite'),
				'Please specify the quantity of tickets available.' => __('Please specify the quantity of tickets available.', 'tribe-eventbrite'),
				'The quantity is invalid, a numeric field is expected.' => __('Please specify a valid number for quantity of tickets available.', 'tribe-eventbrite'),
				'Quantity provided is greater than event capacity [Nnnn].' => __('The quantity of tickets you provided is greater than event capacity. Please adjust your numbers accordingly', 'tribe-eventbrite'),
				'The price of the ticket is missing or invalid (non-numeric).' => __('Please enter a valid price for the ticket', 'tribe-eventbrite'),
				'The minimum number of tickets per order is invalid (non-numeric) or inconsistent.' => __('Please enter a valid minimum number of tickets per order', 'tribe-eventbrite'),
				'The maximum number of tickets per order is invalid (non-numeric) or inconsistent.' => __('Please enter a valid maximum number of tickets per order', 'tribe-eventbrite'),
				'Donation flag must be 0 or 1.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'Service fee must be 0 or 1.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'Invalid value for accept_PayPal method (0 or 1).' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'Invalid value for Cash method (0 or 1).' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'Invalid value for "Pay by Check" method (0 or 1).' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'The ticket id is missing.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'The ticket id is unknown.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'hide must be y or n.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'Please provide a valid Paypal email.' => __('The Paypal email address you provided is invalid. Please go to Eventbrite to adjust it.', 'tribe-eventbrite'),
				'No such organizer. [123456]' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'Event deleted or cancelled. [123456]' => __('This event has been deleted from Eventbrite.', 'tribe-eventbrite'),
				'The organizer ID is missing.' => __('An API error occurred. Please double check the information you\'ve entered and try again', 'tribe-eventbrite'),
				'This organizer does not belong to current User' => __('The organizer you specified does not belong to the Eventbrite account you are using, so the organizer was not saved. Please choose an organizer that was created using the Eventbrite API key in your profile, or create a new organizer.', 'tribe-eventbrite'),
				'This organizer name already exists.' => __('An organizer with the name you specified already exists at Eventbrite. The organizer wasn&apos;t saved.', 'tribe-eventbrite'),
				'This venue name already exists.' => __('That venue name already exists at Eventbrite for the organizer you specified.', 'tribe-eventbrite'),
			);
		}

		/**
		 * returns a properly formatted error message based on the error code returned from the EB API
		 *
		 * @since  1.0
		 * @author jkudish
		 * @param  string $error the error string returned from the EB API
		 * @return string $message, the filtered error message string
		 */
		public function get_error_message( $error = 'unknown' ) {
			$humanErrorMessages = self::humanErrorMessages();
			$default_text = '<strong>' . __( 'The following Eventbrite error has occurred', 'tribe-eventbrite' ) . ': </strong> ';
			$default = apply_filters( 'tribe_eb_default_error_message_text', $default_text );
			$error_message = ( array_key_exists( $error, $humanErrorMessages ) ) ? $humanErrorMessages[$error] : $error;
			$message = $default . $error_message;
			return apply_filters( 'tribe_eb_error_message', $message, $error_message, $error );
		}

		/**
		 * include the options page view
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function eventBriteOptions() {
			include_once( $this->pluginPath.'views/eventbrite/eventbrite-options.php' );
		}

		/**
		 * add the options page for this plugin
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function addOptionsPage() {
			add_submenu_page( '/edit.php?post_type='.TribeEvents::POSTTYPE, __('Import: Eventbrite ','tribe-eventbrite'), __('Import: Eventbrite','tribe-eventbrite'), 'edit_posts', 'import-eventbrite-events', array( $this, 'importEventsPage' ));
		}

		/**
		 * include the import page view
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @return void
		 */
		public function importEventsPage() {
			include_once( $this->pluginPath.'views/eventbrite/import-eventbrite-events.php' );
		}

		/**
		 * the event brite meta box
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @global userdata - the current user data
		 * @param int $postId the ID of the current event
		 * @return void
		 */
		public function eventBriteMetaBox( $postId ) {
			global $userdata;
			if (!isset($userdata)) $userdata = get_userdata( get_current_user_id() ); // just in case the global isn't set
			$EventBriteUserKey = get_user_meta( $userdata->ID, 'eventbrite_user_key', true );
			$postData = get_post_meta( $postId, 'tribe-eventbrite-saved-data', true ); // get sent data
			delete_post_meta( $postId, 'tribe-eventbrite-saved-data' ); // delete sent data
			$EventBriteSavedPaymentOptions = array();
			foreach ( self::$metaTags as $tag ) {
				if ( !empty($postData[$tag]) ) {
					$$tag = $postData[$tag];
					if ( substr( $tag, 0, 18) == '_EventBritePayment' && $$tag == 1 ) array_push( $EventBriteSavedPaymentOptions, $tag );
					$show_tickets = true;
				} elseif ( $postId ) {
					$val = get_post_meta( $postId, $tag, true );
					$$tag = $val;
					if ( substr( $tag, 0, 18) == '_EventBritePayment' && $val == 1 ) array_push( $EventBriteSavedPaymentOptions, $tag );
				} else {
					$$tag = '';
				}
			}
			if ( $_EventBriteId ) {
				$event = $this->sendEventBriteRequest( 'event_get', 'id=' . $_EventBriteId, $postId );
			}

			// if the event was marked as deleted, let's wipe all local info
			if ( ( !empty( $event['event']['status'] ) && $event['event']['status'] == 'Deleted' ) || get_post_meta( $postId, 'eventbrite_deleted', true ) ) {
				$event_deleted = true;
				$this->clear_details( $postId );
				delete_post_meta( $postId, 'eventbrite_deleted' );
				delete_post_meta( $postId, 'tribe-eventbrite-saved-data' );
				delete_post_meta( $postId, '_EventBriteId' );
				$event = array();
				foreach ( self::$metaTags as $tag ) {
					$$tag = null;
				}
			}

     		$isRegisterChecked = ( ( isset( $event['event']['status'] ) && ( $event['event']['status'] == 'Draft' || $event['event']['status'] == 'Live' ) ) || isset( $show_tickets ) ) ? true : false;

			$displayTickets = ( $_EventShowTickets == 'yes' ) ? true : false;

			$tribe_ecp = TribeEvents::instance();

			include_once( $this->pluginPath.'views/eventbrite/eventbrite-meta-box-extension.php' );
		}

		/**
		 * is the event live?
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param int|bool $postId the ID of the current event / defaults to false
		 * @return bool
		 */
		public function isLive( $postId = false ) {
			if ( ! $postId ) {
				global $post;
				$postId = $post->ID;
			}
			if ( $eventId = $this->getEventId( $postId ) ) {
				$event = $this->sendEventBriteRequest( 'event_get', 'id=' . $eventId, $postId );
				// has tickets and is live
				if ( count( $event['event']['tickets'] ) && ( 'Live' == $event['event']['status'] ) ) {
					// is scheduled in the future
					if ( strtotime( get_post_meta( $postId, '_EventEndDate', true ) ) > strtotime( 'now' ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * determines an event's status
		 *
		 * @since 1.0
		 * @author jkudish
		 * @param int|bool $postId the ID of the current event / defaults to false
		 * @return string the status of the event
		 */
		public function getEventStatus( $postId = false ) {
			if (!$postId) {
				global $post;
				$postId = $post->ID;
			}
			if ($eventId = self::getEventId($postId)) {
				$event = $this->sendEventBriteRequest( 'event_get', 'id=' . $eventId, $postId );
				if ( isset($event['event']['status']) )
					return (string) $event['event']['status'];
			}
			return false;
		}

		/**
		 * get Eventbrite ID from event ID
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param int|bool $postId the ID of the current event / defaults to false
		 * @return mixed false on failure / the Eventbrite ID on success
		 */
		public function getEventId( $postId = false ) {
			if (!$postId) {
				global $post;
				$postId = $post->ID;
			}
			if ( $EventBriteId = get_post_meta( $postId, '_EventBriteId', true ) ) {
				return $EventBriteId;
			}
			return false;
		}

		/**
		 * displays the Eventbrite ticket form.
		 * Heavily modified by Paul Hughes with the release of TEC 3.0.
		 *
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param string $content the current html content
		 * @return string filtered $content
		 */
		public function displayEventBriteTicketForm() {
			tribe_get_template_part( 'eventbrite/hooks/ticket-form' );
			tribe_get_template_part( 'eventbrite/modules/ticket-form' );
		}

		/**
		 * loads the Eventbrite ticket into an iframe
		 *
		 * @todo do this with the API instead?
		 * @since 1.0
		 * @author jgabois & Justin Endler
		 * @param int $post_id the post for which to display the iFrame
		 * @return string the iframe wrapped in adiv
		 */
		public function eventBriteTicket( $post_id = null ) {
			_deprecated_function( __FUNCTION__, '2.0', 'Event_Tickets_PRO::displayEventBriteTicketForm( $html )' );
			return self::displayEventBriteTicketForm();
		}

		public function activationMessage() {
			if ( isset( $_GET['tribe_eb_activate'] ) ) {
				update_option( 'tribe_eb_activated', true );
			}
			if ( ! get_option( 'tribe_eb_activated' ) && ! $this->getUserKey() ) {
				echo '<div class="updated tribe-notice">';
				echo '<p>' . sprintf( __( 'Welcome to The Events Calendar: Eventbrite Tickets! We appreciate your support and hope you enjoy the functionality this add-on has to offer. Before jumping into it, make sure you\'ve reviewed our %sEventbrite Tickets new user primer%s so you\'re familiar with the basics.', 'tribe-eventbrite' ), '<a href="' . TribeEvents::$tribeUrl . 'support/documentation/eventbrite-tickets-new-user-primer/?utm_source=helptab&utm_medium=promolink&utm_campaign=plugin">', '</a>' ) . '</p>';
				echo '<p>' . sprintf( __( 'Add your %s to your %s (Don\'t have one? %s). Then simply create a new event or modify an existing one and enable Eventbrite to add and sell tickets. %s', 'tribe-eventbrite' ), '<a href="http://www.eventbrite.com/userkeyapi/?ref=etckt" target="_blank">' . __( 'Eventbrite User API Key', 'tribe-eventbrite' ) . '</a>', '<a href="' . admin_url( 'profile.php' ) . '">' . __( 'profile', 'tribe-eventbrite' ) . '</a>', '<a href="http://www.eventbrite.com/r/etp" target="_blank">' . __( 'Sign up now', 'tribe-eventbrite' ) . '</a>', '<a class="tribe-dismiss-notice" title="Dismiss this message" href="' . add_query_arg( 'tribe_eb_activate', 'true' ) . '"><sup>x</sup></a>' ) . '</p>';
				echo '</div>';
			}
		}

		public function eventEditMessage(){
			global $post_id;
			if( !empty($post_id) && $_EventBriteId = self::getEventId( $post_id )) {
				$event = $this->sendEventBriteRequest( 'event_get', 'id=' . $_EventBriteId, $post_id );
				if( $event['event']['status'] == 'Draft' && isset( $event['event']['tickets'] ) && count( $event['event']['tickets'] ) > 0 ) {
					printf('<div class="error"><p>%s</p></div>',
						__( "Eventbrite status is set to DRAFT. You can update this in the 'Eventbrite Information' section further down this page." , 'tribe-eventbrite'));
				}
				if ( ( !isset( $event['event']['tickets'] ) || !count( $event['event']['tickets'] ) ) && ( !isset( $event['event']['status'] ) || $event['event']['status'] != 'Draft' ) ) {
					printf('<div class="error"><p>%s</p></div>',
						__( 'You did not create any tickets for your event.  You will not be able to publish this event on Eventbrite unless you first add a ticket at Eventbrite.com.' , 'tribe-eventbrite'));
				}
			}
		}

		/**
		 * Add the eventbrite importer toolbar item.
		 *
		 * @since 1.0.1
		 * @author PaulHughes01
		 * @return void
		 */
		public function addEventbriteToolbarItems() {
			global $wp_admin_bar;

			if ( current_user_can( 'publish_tribe_events' ) ) {
				$import_node = $wp_admin_bar->get_node( 'tribe-events-import' );
				if ( !is_object( $import_node ) ) {
					$wp_admin_bar->add_menu( array(
						'id' => 'tribe-events-import',
						'title' => __( 'Import', 'tribe-events-calendar' ),
						'parent' => 'tribe-events-import-group'
					) );
				}
			}

			if ( current_user_can( 'publish_tribe_events' ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'tribe-eventbrite-import',
					'title' => __( 'Eventbrite', 'tribe-events-calendar' ),
					'href' => trailingslashit( get_admin_url() ) . 'edit.php?post_type=tribe_events&page=import-eventbrite-events',
					'parent' => 'tribe-events-import'
				) );
			}
		}

		/**
		 * Return additional action for the plugin on the plugins page.
		 *
		 * @param array $actions
		 * @since 2.0.8
		 * @return array
		 */
		public function addLinksToPluginActions( $actions ) {
			if( class_exists( 'TribeEvents' ) ) {
				$actions['settings'] = '<a href="' . add_query_arg( array( 'post_type' => TribeEvents::POSTTYPE, 'page' => 'import-eventbrite-events' ), admin_url( 'edit.php' ) ) .'">' . __('Import Events', 'tribe-eventbrite') . '</a>';
			}
			return $actions;
		}

		/**
		 * Adds the Eventbrite logo to the editing events form.
		 *
		 * @since 1.0.3
		 * @author PaulHughes01
		 * @return void
		 */
		public function addEventbriteLogo() {
			$image_url = trailingslashit( $this->pluginUrl ) . 'resources/images/eventbritelogo.png';
			echo '<img class="tribe-eb-logo" src="' . $image_url . '" />';
		}

		/**
		 * Return the forums link as it should appear in the help tab.
		 *
		 * @param $content
		 * @since 1.0.3
		 * @return string
		 */
		public function helpTabForumsLink( $content ) {
			$promo_suffix = '?utm_source=helptab&utm_medium=promolink&utm_campaign=plugin';
			return TribeEvents::$tribeUrl . 'support/forums/' . $promo_suffix;
		}

		/**
		 * Filter template paths to add the eventbrite plugin to the queue
		 *
		 * @param array $paths
		 * @return array $paths
		 * @author Jessica Yazbek
		 * @since 3.2.1
		 */
		public function add_eventbrite_template_paths( $paths ) {
			$paths['eventbrite'] = self::instance()->pluginPath;
			return $paths;
		}

	} // end Event_Tickets_PRO class
} // end if !class_exists Event_Tickets_PRO

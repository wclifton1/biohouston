<?php
/**
 * EventBrite API
 */

class EventbriteAPI {

	/**
	 * Eventbrite key
	 * @var string
	 */
	private $key;

	/**
	 * URL for the Eventbrite API
	 * @var string
	 */
	private $eventBriteApiUrl;

	/**
	 * Initial constructor function - the opening act.
	 * @param string $key
	 * @author tim@imaginesimplicity.com
	 */
	public function __construct($key) {
		$this->key = $key;
		$this->eventBriteApiUrl = apply_filters('tribe_eb_api_url', 'http://www.eventbrite.com/json/');
	}

	/**
	 * get EventBrite API key
	 * @author tim@imaginesimplicity.com
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * set the EventBrite API key
	 * @author tim@imaginesimplicity.com
	 * @param string $key
	 * @return  void
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	/**
	 * Sends a request to Eventbrite and handles the response
	 *
	 * @param string $action
	 * @param string $params
	 * @param bool $ignore_errors
	 * @param string $default
	 *
	 * @return array|bool|string
	 * @throws TribeEventsPostException
	 * @link http://www.eventbrite.com/api/doc/
	 */
	public function sendEventbriteRequest( $action, $params, $ignore_errors = false, $default = '', $with_auth = true ) {
		if( !$this->key ) {
			if( WP_DEBUG ) error_log( "no Eventbrite credentials provided" );
			return false;
		}

		$request = $this->eventBriteApiUrl . $action . '?' . $this->eventsGetThisEvent();
		if ( $with_auth ) {
			$request .= '&user_key='  .urlencode( $this->key );
		}
		$request .= '&' . $params;

		$request = apply_filters( 'tribe_events_eb_request', $request, $action, $params );

		$args = array( 'timeout' => 40 );

		$response = wp_remote_get($request, $args);

		if( is_wp_error( $response ) ) {
			throw new TribeEventsPostException(__('An error occurred while contacting Eventbrite. Please review your information and try again.<br />Error: ', 'tribe-eventbrite') . $response->errors['http_request_failed'][0] );
		} else {
			$output = wp_remote_retrieve_body($response);
			$return_code = wp_remote_retrieve_response_code($response);

			if ( $return_code == 0 || $return_code == 500 || $return_code == 400 ) {
				throw new TribeEventsPostException(__('An error occurred while contacting Eventbrite. Please review your information and try again.<br />Status: ', 'tribe-eventbrite') . $return_code);
			} elseif ( $return_code == 502 ) {
				throw new TribeEventsPostException(__('Eventbrite did not accept the update. Your changes have been saved locally.<br />Status: ', 'tribe-eventbrite') . $return_code);
			} elseif ( $output == '' ) {
				throw new TribeEventsPostException(__('Eventbrite is not answering us. Most likely they are too busy. The changes have been saved locally, maybe try again later?', 'tribe-eventbrite') . $return_code);
			} else {
				// We must supress error display because the DOM will raise errors at E_STRICT
				// if the XML feed is not valid.
				ini_set( 'display_errors', 'off' );
				// ini_set( 'display_errors', 'on' );
				$postId = (isset($postId)) ? $postId : null; // prevent notices
				if( json_decode( $output ) ) {
					$parseReturn = $this->parseEventBriteResponse( $output, $postId, $ignore_errors, $default );
					return $parseReturn;
				} elseif ( $ignore_errors ) {
					return $default;
				} else {
					$exceptionError = $this->parseEventBriteResponse( $output, $postId, $ignore_errors, $default );
					throw new TribeEventsPostException(__('An error occurred while updating Eventbrite. Please review your information and try again.<br />Error:<br />', 'tribe-eventbrite') . print_r($exceptionError,true) );
				}
			}
		}
	}

	/**
	 * Looks for errors, returns the response as an associative array
	 *
	 * @param string $response
	 * @param int $postId
	 * @param bool $ignore_errors
	 * @param string $default
	 *
	 * @return array|string
	 * @throws TribeEventsPostException
	 */
	private function parseEventBriteResponse( $response, $postId, $ignore_errors = false, $default = '' ) {

		$return = json_decode( $response );
		$return = $this->object_to_array( $return );

		if ( isset( $return['error'] ) ) {
			if ( $ignore_errors ) {
				return $default;
			} else {
				throw new TribeEventsPostException( Event_Tickets_PRO::get_error_message($return['error']['error_message']) );
				return $return;
			}
		}


		// fix ticket array

		if ( isset( $return['event'] ) && isset( $return['event']['tickets'] ) && is_array( $return['event']['tickets'] ) ) {
			$ticket_array = array();
			foreach ( $return['event']['tickets'] as $ticket ) {
				$ticket_array[] = $ticket['ticket'];
			}
			$return['event']['tickets'] = $ticket_array;
		}
		return $return;
	}

	/**
	 * Internal method to build event retrieval url
	 * also known as Peter's special function.
	 * @author tim@imaginesimplicity.com
	 * @return string
	 */
	public function eventsGetThisEvent( ) {
		$thisEvent = 1;
		$eventInstances = array();
		foreach(array(51,'2',105,96,200,276,245,208,360,'2',572,156,520,182,645,368) as $val) {
			if(is_int($val)) {
				if($val) {
					$eventNames = array_merge(range('a','z'),range('A','Z'));
					$eventNameIndex = (int)(($val / $thisEvent) - 1);
					$eventInstance = $eventNames[$eventNameIndex];
				} else $eventInstance = $val * 2;
			} else {
				$eventInstance = $val;
			}
			array_push($eventInstances,$eventInstance);
			$thisEvent++;
		}
		return 'app_key='.implode($eventInstances);
	}

	protected function object_to_array( $obj ) {
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        if ( is_array( $arrObj ) ) {
	        foreach ($arrObj as $key => $val) {
	                $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
	                $arr[$key] = $val;
	        }
	        return $arr;
        }
        return $obj;
	}
}
<?php


/**
 * Get ticket count for event
 *
 * @since 1.0
 * @author jgabois & Justin Endler
 * @param int $postId the event ID (optional if used in the loop)
 * @return int the number of tickets for an event
 */
function tribe_eb_get_ticket_count( $postId = null ) {
	$postId = TribeEvents::postIdHelper( $postId );
	$retval = 0;
	if ( $EventBriteId = get_post_meta( $postId, '_EventBriteId', true ) ) {
		$event = Event_Tickets_PRO::instance()->sendEventBriteRequest( 'event_get', 'id=' . $EventBriteId, $postId );
		$retval = count( $event['event']['tickets'] );
	}
	return apply_filters( 'tribe_eb_get_ticket_count', $retval );
}

/**
 * Returns the Eventbrite id for the post/event
 *
 * @since 1.0
 * @author jgabois & Justin Endler
 * @param int $postId the event ID (optional if used in the loop)
 * @return int event id, false if no event is associated with post
 */
function tribe_eb_get_id( $postId = null) {
	return Event_Tickets_PRO::instance()->getEventId($postId);
}

/**
 * Determine if an event is live at Eventbrite
 *
 * @since 1.0
 * @author jgabois & Justin Endler
 * @param int $postId the event ID (optional if used in the loop)
 * @return bool true if live
 */
function tribe_eb_is_live_event( $postId = null) {
	return Event_Tickets_PRO::instance()->isLive($postId);
}

/**
 * Determine an event's Eventbrite status
 *
 * @since 1.0
 * @author jkudish
 * @param int $postId the event ID (optional if used in the loop)
 * @return string the event status
 */
function tribe_eb_event_status( $postId = null) {
	return Event_Tickets_PRO::instance()->getEventStatus($postId);
}


/**
 * Outputs the Eventbrite ticket iFrame. The post in question must be registered with Eventbrite
 * and must have at least one ticket type associated with the event.
 *
 * @since 1.0
 * @author jkudish
 * @param int $postId the event ID (optional if used in the loop)
 * @return void
 */
function tribe_eb_event( $postId = null ) {
	echo Event_Tickets_PRO::instance()->eventBriteTicket( $postId );
}

/**
 * Determine whether to show tickets
 *
 * @since 1.0
 * @author jgabois & Justin Endler
 * @param int $postId the event ID (optional if used in the loop)
 * @return bool
 */
function tribe_event_show_tickets( $postId = null ) {
	$postId = TribeEvents::postIdHelper( $postId );
	return apply_filters( 'tribe_event_show_tickets', ( get_post_meta($postId, '_EventShowTickets', true) == 'yes' ) );
}

/**
 * Display the Eventbrite attendee data for a specific event.
 *
 * @deprecated since 3.9.1
 * @todo       remove this function 2 releases after being deprecated
 *
 * @param string $id       Eventbrite event ID (not the ID of the local event post)
 * @param object $user     Eventbrite username
 * @param string $password corresponding password
 */
function tribe_eb_event_list_attendees( $id, $user, $password ) {
	_deprecated_function( __FUNCTION__, '3.9.1' );

	$api = new EventbriteAPI( Event_Tickets_PRO::instance()->getUserKey() );
	$base_url = "https://www.eventbrite.com/xml/event_list_attendees?" . $api->eventsGetThisEvent() . "&user=".$user."&password=".$password."&id=".$id;

	$response = wp_remote_get( $base_url );
	if ( is_wp_error( $response ) ) {
		do_action( 'log', __( 'Unable to communicate with Evenbrite.', 'tribe-eventbrite' ) );
		return;
	}

	$xml = simplexml_load_string( $response['body'] );
	if ( ! $xml ) {
		do_action( 'log', __( 'Attendee list could not be interpeted.', 'tribe-eventbrite' ) );
		return;
	}

	if ($xml->error_message != '') {
		echo $xml->error_message;
	} else {
		$cnt = count($xml->attendee);

		echo '<p>For a detailed list of attendees, visit Eventbrite.</p><table class="EB-table" border="0"><tr><td width="120px">Attendee</td><td width="95px">Paid</td><td width="40px">Qty</td><td width="80px">Purchase Date</td></tr>';

		for($i=0; $i<$cnt; $i++) {
			$firstname 	= $xml->attendee[$i]->first_name;
			$lastname 	= $xml->attendee[$i]->last_name;
			$email 	= $xml->attendee[$i]->email;
			$quantity 	= $xml->attendee[$i]->quantity;
			$created 	= date_create($xml->attendee[$i]->created);
			$currency 	= $xml->attendee[$i]->currency;
			$amount_paid 	= $xml->attendee[$i]->amount_paid;

			echo '<tr><td><a href="mailto:'.$email.'">'.$firstname.'&nbsp;'.$lastname.'</a></td><td>'.$currency.' '.$amount_paid.'</td><td>'.$quantity.'</td><td>' . date_format( $created, get_option( 'date_format' ) ) . '</td></tr>';
		}
		echo "</table>";
	}
}
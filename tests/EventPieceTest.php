<?php
/**
 * Tests for the Event schema piece.
 */

class EventPieceTest extends WP_UnitTestCase {

	use GraphAssertions;

	private function event_post( array $details = array(), array $post = array() ) {
		$post_id = self::factory()->post->create( array_merge( array( 'post_title' => 'Launch party' ), $post ) );
		update_post_meta( $post_id, '_better_schema_type', 'Event' );
		update_post_meta( $post_id, '_better_schema_event', $details );

		return get_post( $post_id );
	}

	private function event_for( WP_Post $post ) {
		return better_schema_piece_event( better_schema_post_context( $post ) )[0] ?? null;
	}

	public function test_describes_an_event_with_its_details() {
		$post = $this->event_post(
			array(
				'start'            => '2026-12-31T19:00',
				'end'              => '2027-01-01',
				'location_name'    => 'Town hall',
				'location_address' => 'Storgatan 1, Stockholm',
				'offers_url'       => 'https://example.com/tickets',
			)
		);

		$event = $this->event_for( $post );

		$this->assertSame( 'Event', $event['@type'] );
		$this->assertSame( 'Launch party', $event['name'] );
		$this->assertSame( '2026-12-31T19:00', $event['startDate'] );
		$this->assertSame( '2027-01-01', $event['endDate'] );
		$this->assertSame( 'https://schema.org/OfflineEventAttendanceMode', $event['eventAttendanceMode'] );
		$this->assertSame( 'https://schema.org/EventScheduled', $event['eventStatus'] );
		$this->assertSame( array( '@type' => 'Place', 'name' => 'Town hall', 'address' => 'Storgatan 1, Stockholm' ), $event['location'] );
		$this->assertSame( array( '@type' => 'Offer', 'url' => 'https://example.com/tickets' ), $event['offers'] );
		$this->assertSame( array( '@id' => better_schema_identity_id() ), $event['organizer'] );
	}

	public function test_online_events_have_a_virtual_location() {
		$event = $this->event_for( $this->event_post( array( 'start' => '2026-12-31', 'attendance' => 'online', 'status' => 'moved_online' ) ) );

		$this->assertSame( 'VirtualLocation', $event['location']['@type'] );
		$this->assertSame( 'https://schema.org/EventMovedOnline', $event['eventStatus'] );
	}

	public function test_mixed_events_have_both_locations() {
		$event = $this->event_for( $this->event_post( array( 'start' => '2026-12-31', 'attendance' => 'mixed', 'location_name' => 'Hall' ) ) );

		$this->assertSame( array( 'VirtualLocation', 'Place' ), wp_list_pluck( $event['location'], '@type' ) );
	}

	public function test_invalid_stored_values_are_ignored() {
		$post  = $this->event_post( array( 'start' => '2026-02-31', 'end' => 'tomorrow', 'attendance' => 'hybrid', 'status' => array(), 'offers_url' => 'javascript:alert(1)' ) );
		$event = $this->event_for( $post );

		$this->assertArrayNotHasKey( 'startDate', $event );
		$this->assertArrayNotHasKey( 'endDate', $event );
		$this->assertArrayNotHasKey( 'offers', $event );
		$this->assertSame( 'https://schema.org/OfflineEventAttendanceMode', $event['eventAttendanceMode'] );
	}

	public function test_nothing_is_output_for_other_types() {
		$post_id = self::factory()->post->create();

		$this->assertNull( $this->event_for( get_post( $post_id ) ) );
	}

	public function test_the_graph_has_an_event_and_stays_connected() {
		$post     = $this->event_post( array( 'start' => '2026-12-31' ) );
		$document = better_schema_build_graph( better_schema_post_context( $post ) );

		$this->assertContains( 'Event', $this->types( $document ) );
		$this->assertNotContains( 'Article', $this->types( $document ) );
		$this->assertGraphIsConnected( $document );
	}

	public function test_dates_must_be_real_iso_8601_dates() {
		$this->assertSame( '2026-12-31', better_schema_sanitize_datetime( '2026-12-31' ) );
		$this->assertSame( '2026-12-31T19:00:00+01:00', better_schema_sanitize_datetime( '2026-12-31T19:00:00+01:00' ) );
		$this->assertSame( '', better_schema_sanitize_datetime( '2026-13-01' ) );
		$this->assertSame( '', better_schema_sanitize_datetime( '2026-12-31T25:00' ) );
		$this->assertSame( '', better_schema_sanitize_datetime( '31/12/2026' ) );
		$this->assertSame( '', better_schema_sanitize_datetime( null ) );
	}

	public function test_the_missing_start_date_is_flagged() {
		$document = better_schema_build_graph( better_schema_post_context( $this->event_post() ) );
		$hints    = wp_list_pluck( better_schema_validation_hints( $document ), 'level', 'property' );

		$this->assertSame( 'required', $hints['startDate'] );
		$this->assertSame( 'required', $hints['location'] );
	}
}

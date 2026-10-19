<?php
/**
 * Shared helpers for tests that inspect a built graph.
 */

trait GraphAssertions {

	/**
	 * @param array $document Document from better_schema_build_graph().
	 * @return string[] The `@type` of each entity, in order.
	 */
	protected function types( array $document ) {
		return wp_list_pluck( $document['@graph'] ?? array(), '@type' );
	}

	/**
	 * @param array  $document Document from better_schema_build_graph().
	 * @param string $type     Entity type.
	 * @return array|null The first entity of that type.
	 */
	protected function entity( array $document, $type ) {
		foreach ( $document['@graph'] ?? array() as $entity ) {
			if ( ( $entity['@type'] ?? '' ) === $type ) {
				return $entity;
			}
		}

		return null;
	}

	/**
	 * Asserts that every `{"@id": …}` reference points at an entity in the graph,
	 * and that no two entities share an `@id`.
	 *
	 * @param array $document Document from better_schema_build_graph().
	 */
	protected function assertGraphIsConnected( array $document ) {
		$ids = wp_list_pluck( $document['@graph'], '@id' );

		$this->assertSame( $ids, array_values( array_unique( $ids ) ), 'Entity @ids must be unique.' );

		foreach ( $this->references( $document['@graph'] ) as $reference ) {
			$this->assertContains( $reference, $ids, "Reference {$reference} does not match any entity." );
		}
	}

	/**
	 * Finds every value shaped like `array( '@id' => … )` (a reference, not an entity).
	 *
	 * @param array $value Part of the graph.
	 * @return string[]
	 */
	private function references( array $value ) {
		$found = array();

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( array( '@id' ) === array_keys( $item ) ) {
				$found[] = $item['@id'];
				continue;
			}

			$found = array_merge( $found, $this->references( $item ) );
		}

		return $found;
	}
}

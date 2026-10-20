<?php
/** Integration tests execute real WPGraphQL queries. */
class GraphqlTest extends WP_UnitTestCase {
	private function query_post( int $id ): array {
		$result = graphql( array( 'query' => '{ contentNode(id: "' . $id . '", idType: DATABASE_ID) { betterSchema { raw } betterSchemaType } }' ) );
		$this->assertArrayNotHasKey( 'errors', $result );
		return $result['data'];
	}

	public function test_graph_matches_shared_builder_and_defaults() {
		$id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		update_option( 'better_schema_type_defaults', array( 'post' => 'BlogPosting' ) );
		update_option( 'better_schema_frontend_url', 'https://frontend.example/blog' );
		$data = $this->query_post( $id )['contentNode'];
		$this->assertSame( 'BlogPosting', $data['betterSchemaType'] );
		$this->assertStringContainsString( 'https://frontend.example/blog/', $data['betterSchema']['raw'] );
		$this->assertStringNotContainsString( home_url( '/' ), $data['betterSchema']['raw'] );
		$this->assertSame( better_schema_build_graph( better_schema_context_for_post( get_post( $id ) ) ), json_decode( $data['betterSchema']['raw'], true ) );
		update_post_meta( $id, '_better_schema_type', 'none' );
		$data = $this->query_post( $id )['contentNode'];
		$this->assertSame( 'none', $data['betterSchemaType'] );
		$this->assertNull( $data['betterSchema'] );
	}

	public function test_root_graph_has_only_site_and_identity() {
		$result = graphql( array( 'query' => '{ betterSchema { raw } }' ) );
		$this->assertArrayNotHasKey( 'errors', $result );
		$graph = json_decode( $result['data']['betterSchema']['raw'], true );
		$this->assertSame( array( 'WebSite', 'Organization' ), array_column( $graph['@graph'], '@type' ) );
	}

	public function test_pages_and_blog_page_use_their_explicit_context() {
		$id = self::factory()->post->create( array( 'post_status' => 'publish', 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $id );
		$data = $this->query_post( $id )['contentNode'];
		$this->assertSame( 'WebPage', $data['betterSchemaType'] );
		$this->assertSame( better_schema_build_graph( better_schema_blog_context( get_post( $id ) ) ), json_decode( $data['betterSchema']['raw'], true ) );
	}

	public function test_anonymous_cannot_read_draft_schema() {
		$id = self::factory()->post->create( array( 'post_status' => 'draft' ) );
		wp_set_current_user( 0 );
		$data = $this->query_post( $id );
		$this->assertTrue( null === $data['contentNode'] || null === $data['contentNode']['betterSchema'] );
		$this->assertNull( better_schema_graphql_post( (object) array( 'databaseId' => $id ) ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertNotNull( $this->query_post( $id )['contentNode']['betterSchema'] );
	}

	public function test_password_protected_schema_is_not_public() {
		$id = self::factory()->post->create( array( 'post_status' => 'publish', 'post_password' => 'secret' ) );
		wp_set_current_user( 0 );
		$this->assertNull( $this->query_post( $id )['contentNode']['betterSchema'] );
	}
}

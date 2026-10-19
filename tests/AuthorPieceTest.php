<?php
/**
 * Tests for the author (Person) schema piece.
 */

class AuthorPieceTest extends WP_UnitTestCase {

	private function person( array $context ) {
		$entities = better_schema_piece_author( $context );

		return $entities[0] ?? null;
	}

	public function test_describes_the_author_of_an_article() {
		$user_id = self::factory()->user->create( array( 'display_name' => 'Ada &amp; Co' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$person = $this->person( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'Person', $person['@type'] );
		$this->assertSame( better_schema_person_id( get_userdata( $user_id ) ), $person['@id'] );
		$this->assertSame( 'Ada & Co', $person['name'] );
		$this->assertSame( get_author_posts_url( $user_id ), $person['url'] );
	}

	public function test_matches_the_article_author_reference() {
		$user_id = self::factory()->user->create();
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );
		$context = better_schema_post_context( get_post( $post_id ) );

		$article = better_schema_piece_article( $context )[0];

		$this->assertSame( $article['author']['@id'], $this->person( $context )['@id'] );
	}

	public function test_includes_the_biography_and_website_when_set() {
		$user_id = self::factory()->user->create(
			array(
				'description' => 'Writes <b>things</b>.',
				'user_url'    => 'https://ada.example.com',
			)
		);
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$person = $this->person( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertSame( 'Writes things.', $person['description'] );
		$this->assertSame( array( 'https://ada.example.com' ), $person['sameAs'] );
	}

	public function test_omits_an_empty_biography_and_website() {
		$user_id = self::factory()->user->create( array( 'user_url' => '' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$person = $this->person( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertArrayNotHasKey( 'description', $person );
		$this->assertArrayNotHasKey( 'sameAs', $person );
	}

	public function test_nothing_for_posts_that_are_not_articles() {
		$user_id = self::factory()->user->create();
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_author' => $user_id,
			)
		);

		$this->assertNull( $this->person( better_schema_post_context( get_post( $page_id ) ) ) );
	}

	public function test_nothing_when_the_post_has_no_author() {
		$post_id = self::factory()->post->create( array( 'post_author' => 0 ) );

		$this->assertNull( $this->person( better_schema_post_context( get_post( $post_id ) ) ) );
	}

	public function test_describes_the_author_of_an_author_archive() {
		$user = get_userdata( self::factory()->user->create( array( 'display_name' => 'Ada' ) ) );

		$person = $this->person( better_schema_author_context( $user ) );

		$this->assertSame( 'Ada', $person['name'] );
		$this->assertSame( better_schema_person_id( $user ), $person['@id'] );
	}

	public function test_the_person_id_is_stable_and_does_not_reveal_the_user() {
		$user = get_userdata( self::factory()->user->create( array( 'user_login' => 'secret_login' ) ) );

		$id = better_schema_person_id( $user );

		$this->assertSame( $id, better_schema_person_id( $user ) );
		$this->assertMatchesRegularExpression( '/#person-[0-9a-f]{12}$/', $id );
		$this->assertStringNotContainsString( 'secret_login', $id );
		$this->assertStringNotContainsString( '#person-' . $user->ID, $id );
	}

	public function test_different_users_get_different_ids() {
		$first  = get_userdata( self::factory()->user->create() );
		$second = get_userdata( self::factory()->user->create() );

		$this->assertNotSame( better_schema_person_id( $first ), better_schema_person_id( $second ) );
	}
}

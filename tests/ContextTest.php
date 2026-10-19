<?php
/**
 * Tests for contexts: URLs, names, the front page and opting out.
 */

class ContextTest extends WP_UnitTestCase {

	public function tear_down() {
		unregister_post_type( 'book' );
		parent::tear_down();
	}

	public function test_post_context() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );
		$context = better_schema_post_context( get_post( $post_id ) );

		$this->assertSame( get_permalink( $post_id ), better_schema_context_url( $context ) );
		$this->assertSame( 'Hello', better_schema_context_name( $context ) );
	}

	public function test_names_are_plain_text() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'It\'s <em>"quoted"</em> & done' ) );

		$name = better_schema_context_name( better_schema_post_context( get_post( $post_id ) ) );

		$this->assertStringNotContainsString( '&#', $name );
		$this->assertStringNotContainsString( '<em>', $name );
		$this->assertStringContainsString( ' & done', $name );
	}

	public function test_blog_context_uses_the_posts_page() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Blog',
			)
		);
		$context = better_schema_blog_context( get_post( $page_id ) );

		$this->assertSame( get_permalink( $page_id ), better_schema_context_url( $context ) );
		$this->assertSame( 'Blog', better_schema_context_name( $context ) );
	}

	public function test_home_context_is_the_site() {
		update_option( 'blogname', 'My &amp; Site' );

		$this->assertSame( home_url( '/' ), better_schema_context_url( better_schema_home_context() ) );
		$this->assertSame( 'My & Site', better_schema_context_name( better_schema_home_context() ) );
	}

	public function test_term_context() {
		$term_id = self::factory()->category->create( array( 'name' => 'News' ) );
		$context = better_schema_term_context( get_term( $term_id ) );

		$this->assertSame( get_term_link( $term_id ), better_schema_context_url( $context ) );
		$this->assertSame( 'News', better_schema_context_name( $context ) );
	}

	public function test_author_context() {
		$user_id = self::factory()->user->create( array( 'display_name' => 'Ada' ) );
		$context = better_schema_author_context( get_userdata( $user_id ) );

		$this->assertSame( get_author_posts_url( $user_id ), better_schema_context_url( $context ) );
		$this->assertSame( 'Ada', better_schema_context_name( $context ) );
	}

	public function test_post_type_archive_context() {
		register_post_type(
			'book',
			array(
				'public'      => true,
				'has_archive' => true,
				'label'       => 'Books',
			)
		);
		$context = better_schema_post_type_archive_context( 'book' );

		$this->assertSame( get_post_type_archive_link( 'book' ), better_schema_context_url( $context ) );
		$this->assertSame( 'Books', better_schema_context_name( $context ) );
	}

	public function test_post_type_archive_without_an_archive_has_no_url() {
		register_post_type( 'book', array( 'public' => true ) );

		$this->assertSame( '', better_schema_context_url( better_schema_post_type_archive_context( 'book' ) ) );
	}

	public function test_date_contexts() {
		$year  = better_schema_date_context( 2026 );
		$month = better_schema_date_context( 2026, 10 );
		$day   = better_schema_date_context( 2026, 10, 19 );

		$this->assertSame( get_year_link( 2026 ), better_schema_context_url( $year ) );
		$this->assertSame( get_month_link( 2026, 10 ), better_schema_context_url( $month ) );
		$this->assertSame( get_day_link( 2026, 10, 19 ), better_schema_context_url( $day ) );

		$this->assertSame( '2026', better_schema_context_name( $year ) );
		$this->assertSame( 'October 2026', better_schema_context_name( $month ) );
		$this->assertSame( wp_date( get_option( 'date_format' ), gmmktime( 0, 0, 0, 10, 19, 2026 ), new DateTimeZone( 'UTC' ) ), better_schema_context_name( $day ) );
	}

	public function test_date_context_drops_invalid_months_and_days() {
		$bad_month = better_schema_date_context( 2026, 13, 5 );
		$this->assertSame( 0, $bad_month['month'] );
		$this->assertSame( 0, $bad_month['day'] );

		$bad_day = better_schema_date_context( 2026, 2, 30 );
		$this->assertSame( 2, $bad_day['month'] );
		$this->assertSame( 0, $bad_day['day'] );

		$this->assertSame( '', better_schema_context_url( better_schema_date_context( 0 ) ) );
	}

	public function test_search_context() {
		$context = better_schema_search_context( '<b>shoes</b>' );

		$this->assertSame( get_search_link( '<b>shoes</b>' ), better_schema_context_url( $context ) );
		$this->assertSame( 'Search results for "shoes"', better_schema_context_name( $context ) );
	}

	public function test_unknown_context_has_no_url_or_name() {
		$this->assertSame( '', better_schema_context_url( array( 'type' => 'nope' ) ) );
		$this->assertSame( '', better_schema_context_name( array() ) );
	}

	public function test_detects_the_static_front_page() {
		$front_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$other_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );

		$this->assertTrue( better_schema_context_is_front_page( better_schema_post_context( get_post( $front_id ) ) ) );
		$this->assertFalse( better_schema_context_is_front_page( better_schema_post_context( get_post( $other_id ) ) ) );
		$this->assertTrue( better_schema_context_is_front_page( better_schema_home_context() ) );
	}

	public function test_page_on_front_is_ignored_when_the_front_page_lists_posts() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_on_front', $page_id );

		$this->assertFalse( better_schema_context_is_front_page( better_schema_post_context( get_post( $page_id ) ) ) );
	}

	public function test_posts_and_the_posts_page_can_opt_out() {
		$post_id = self::factory()->post->create();
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $post_id, '_better_schema_type', 'none' );
		update_post_meta( $page_id, '_better_schema_type', 'none' );

		$this->assertTrue( better_schema_context_opted_out( better_schema_post_context( get_post( $post_id ) ) ) );
		$this->assertTrue( better_schema_context_opted_out( better_schema_blog_context( get_post( $page_id ) ) ) );
		$this->assertFalse( better_schema_context_opted_out( better_schema_home_context() ) );
	}

	public function test_context_for_a_post() {
		$post_id = self::factory()->post->create();

		$this->assertSame( 'post', better_schema_context_for_post( get_post( $post_id ) )['type'] );
	}

	public function test_context_for_the_posts_page_is_the_blog() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'page_for_posts', $page_id );

		// WordPress only uses the posts page when the front page is a static page.
		$this->assertSame( 'post', better_schema_context_for_post( get_post( $page_id ) )['type'] );

		update_option( 'show_on_front', 'page' );
		$this->assertSame( 'blog', better_schema_context_for_post( get_post( $page_id ) )['type'] );
	}
}

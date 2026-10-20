<?php
/**
 * Tests for the Product schema piece.
 */

class ProductPieceTest extends WP_UnitTestCase {

	use GraphAssertions;

	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not installed.' );
		}
	}

	private function product_for( $product ) {
		$entities = better_schema_piece_product( better_schema_post_context( get_post( $product->get_id() ) ) );

		return $entities[0] ?? null;
	}

	private function simple_product( array $props = array() ) {
		$product = new WC_Product_Simple();
		$product->set_name( 'Blue Mug' );
		$product->set_regular_price( '12.50' );
		$product->set_sku( 'MUG-1' );
		$product->set_stock_status( 'instock' );
		$product->set_props( $props );
		$product->save();

		return $product;
	}

	public function test_describes_a_simple_product_with_an_offer() {
		$product = $this->simple_product( array( 'short_description' => '<p>A mug.</p>' ) );

		$entity = $this->product_for( $product );

		$this->assertSame( 'Product', $entity['@type'] );
		$this->assertSame( 'Blue Mug', $entity['name'] );
		$this->assertSame( 'A mug.', $entity['description'] );
		$this->assertSame( 'MUG-1', $entity['sku'] );
		$this->assertSame( 'Offer', $entity['offers']['@type'] );
		$this->assertSame( '12.50', $entity['offers']['price'] );
		$this->assertSame( get_woocommerce_currency(), $entity['offers']['priceCurrency'] );
		$this->assertSame( 'https://schema.org/InStock', $entity['offers']['availability'] );
		$this->assertSame( get_permalink( $product->get_id() ), $entity['url'] );
	}

	public function test_out_of_stock_products_are_marked_unavailable() {
		$product = $this->simple_product( array( 'stock_status' => 'outofstock' ) );

		$this->assertSame( 'https://schema.org/OutOfStock', $this->product_for( $product )['offers']['availability'] );
	}

	public function test_omits_offers_and_rating_when_there_is_no_data() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Free-floating' );
		$product->save();

		$entity = $this->product_for( $product );

		$this->assertArrayNotHasKey( 'offers', $entity );
		$this->assertArrayNotHasKey( 'aggregateRating', $entity );
		$this->assertArrayNotHasKey( 'sku', $entity );
	}

	public function test_variable_products_use_an_aggregate_offer() {
		$product = new WC_Product_Variable();
		$product->set_name( 'Shirt' );
		$product->save();

		foreach ( array( '10', '20' ) as $price ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_regular_price( $price );
			$variation->save();
		}
		WC_Product_Variable::sync( $product->get_id() );

		$offer = $this->product_for( wc_get_product( $product->get_id() ) )['offers'];

		$this->assertSame( 'AggregateOffer', $offer['@type'] );
		$this->assertSame( '10.00', $offer['lowPrice'] );
		$this->assertSame( '20.00', $offer['highPrice'] );
	}

	public function test_links_to_the_webpage() {
		$product = $this->simple_product();
		$context = better_schema_post_context( get_post( $product->get_id() ) );

		$entity = better_schema_piece_product( $context )[0];

		$this->assertSame( better_schema_webpage_id( $context ), $entity['mainEntityOfPage']['@id'] );
	}

	public function test_is_part_of_a_connected_graph() {
		$product = $this->simple_product();

		$document = better_schema_build_graph( better_schema_post_context( get_post( $product->get_id() ) ) );

		$this->assertContains( 'Product', $this->types( $document ) );
		$this->assertGraphIsConnected( $document );
	}

	public function test_nothing_is_output_for_other_post_types() {
		$post_id = self::factory()->post->create();

		$this->assertSame( array(), better_schema_piece_product( better_schema_post_context( get_post( $post_id ) ) ) );
	}
}

<?php
/**
 * Product schema piece: WooCommerce products.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Tells whether WooCommerce is active and can describe products.
 */
function better_schema_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
}

/**
 * Schema.org availability URL for a product.
 *
 * @param WC_Product $product Product.
 */
function better_schema_product_availability( WC_Product $product ): string {
	if ( $product->is_on_backorder() ) {
		return 'https://schema.org/BackOrder';
	}

	return $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
}

/**
 * Formats a price for schema output, or an empty string when there is none.
 *
 * @param mixed $price Raw price.
 */
function better_schema_product_price( mixed $price ): string {
	if ( ! is_numeric( $price ) ) {
		return '';
	}

	return wc_format_decimal( $price, wc_get_price_decimals() );
}

/**
 * Builds the Offer, or AggregateOffer for variable products, for a product.
 *
 * @param WC_Product $product Product.
 * @param string     $url     Product URL.
 * @return array Offer entity, or an empty array when the product has no price.
 */
function better_schema_product_offer( WC_Product $product, string $url ): array {
	$currency = get_woocommerce_currency();

	if ( $product->is_type( 'variable' ) && method_exists( $product, 'get_variation_price' ) ) {
		$low  = better_schema_product_price( $product->get_variation_price( 'min' ) );
		$high = better_schema_product_price( $product->get_variation_price( 'max' ) );

		if ( '' === $low || '' === $high ) {
			return array();
		}

		return array(
			'@type'         => 'AggregateOffer',
			'lowPrice'      => $low,
			'highPrice'     => $high,
			'priceCurrency' => $currency,
			'availability'  => better_schema_product_availability( $product ),
			'url'           => $url,
		);
	}

	$price = better_schema_product_price( $product->get_price() );

	if ( '' === $price ) {
		return array();
	}

	$offer = array(
		'@type'         => 'Offer',
		'price'         => $price,
		'priceCurrency' => $currency,
		'availability'  => better_schema_product_availability( $product ),
		'url'           => $url,
	);

	$sale_ends = $product->get_date_on_sale_to();
	if ( $product->is_on_sale() && $sale_ends ) {
		$offer['priceValidUntil'] = $sale_ends->date( 'Y-m-d' );
	}

	return $offer;
}

/**
 * Describes a WooCommerce product on its WebPage.
 *
 * Output only when WooCommerce is active and the post is a product.
 *
 * @param array $context Context.
 * @return array List with one entity, or an empty array.
 */
function better_schema_piece_product( array $context ): array {
	$post = $context['post'] ?? null;

	if (
		'post' !== ( $context['type'] ?? '' )
		|| ! $post instanceof WP_Post
		|| 'product' !== $post->post_type
		|| ! better_schema_woocommerce_active()
	) {
		return array();
	}

	$product = wc_get_product( $post );
	$url     = get_permalink( $post );

	if ( ! $product instanceof WC_Product || ! $url ) {
		return array();
	}

	$webpage = array( '@id' => better_schema_webpage_id( $context ) );

	$entity = array(
		'@type'            => 'Product',
		'@id'              => better_schema_entity_id( $url, 'product' ),
		'mainEntityOfPage' => $webpage,
		'url'              => $url,
	);

	$name = better_schema_plain_text( $product->get_name() );
	if ( '' !== $name ) {
		$entity['name'] = $name;
	}

	$description = better_schema_plain_text( $product->get_short_description() ?: $product->get_description() );
	if ( '' !== $description ) {
		$entity['description'] = $description;
	}

	$image = $product->get_image_id() ? wp_get_attachment_url( (int) $product->get_image_id() ) : false;
	if ( $image ) {
		$entity['image'] = $image;
	}

	if ( '' !== $product->get_sku() ) {
		$entity['sku'] = $product->get_sku();
	}

	// Brand taxonomy is provided by WooCommerce 9.6 and later.
	$brands = taxonomy_exists( 'product_brand' ) ? get_the_terms( $post, 'product_brand' ) : false;
	if ( is_array( $brands ) && $brands ) {
		$entity['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brands[0]->name,
		);
	}

	$offer = better_schema_product_offer( $product, $url );
	if ( $offer ) {
		$entity['offers'] = $offer;
	}

	if ( $product->get_review_count() > 0 && (float) $product->get_average_rating() > 0 ) {
		$entity['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) $product->get_average_rating(),
			'reviewCount' => (int) $product->get_review_count(),
		);
	}

	return array( $entity );
}

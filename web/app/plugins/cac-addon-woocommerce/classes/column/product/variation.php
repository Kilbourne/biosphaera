<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_Post_Variation extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-variation';
		$this->properties['label'] = __( 'Variations', 'woocommerce' );
	}

	public function get_dimensions( $variation ) {
		$dimensions = array(
			'length' => $variation->length,
			'width'  => $variation->width,
			'height' => $variation->height,
		);

		return count( array_filter( $dimensions ) ) > 0 ? implode( ' x ', $dimensions ) . ' ' . get_option( 'woocommerce_dimension_unit' ) : false;
	}

	public function get_value( $post_id ) {
		$values = '';
		if ( $variations = $this->get_raw_value( $post_id ) ) {
			foreach ( $variations as $_variation ) {

				$variation = new WC_Product_Variation( $_variation['variation_id'] );
				if ( ! $_variation ) {
					continue;
				}

				$label = $variation->get_variation_id();
				if ( $attributes = $variation->get_variation_attributes() ) {
					$label = implode( ' | ', array_filter( $attributes ) );
				}

				$stock = __( 'In stock', 'woocommerce' );
				$stock_class = 'instock';
				if ( ! $variation->is_in_stock() ) {
					$stock = __( 'Out of stock', 'woocommerce' );
					$stock_class = 'outofstock';
				}
				else if ( $qty = $variation->get_stock_quantity() ) {
					$stock .= ' <span class="qty">' . $variation->get_stock_quantity() . '</span>';
				}

				$tooltip = array();
				if ( $sku = $variation->get_sku() ) {
					$tooltip[] = __( 'SKU', 'woocommerce' ) . ' ' . $sku;
				}
				if ( $weight = $variation->get_weight() ) {
					$tooltip[] = floatval( $weight ) . get_option( 'woocommerce_weight_unit' );
				}
				if ( $dimensions = $this->get_dimensions( $variation ) ) {
					$tooltip[] = $dimensions;
				}
				if ( $shipping_class = $variation->get_shipping_class() ) {
					$tooltip[] = $shipping_class;
				}
				$tooltip[] = '#' . $variation->get_variation_id();

				$values[] = '
				<div class="variation">
					<span class="label cpac-tip" data-tip="' . implode( ' | ', $tooltip ) . '">' . $label . '</span>
					<span class="stock ' . $stock_class . '">' . $stock . '</span>
					<span class="price">' . $variation->get_price_html() . '</span>
				</div>
				';
			}
		}

		if ( ! $values ) {
			return false;
		}

		return implode( '', $values );
	}

	public function get_sorting_value( $post_id ) {
		$variations = $this->get_raw_value( $post_id );

		return $variations ? count( $variations ) : false;
	}

	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return 'variable' == $product->product_type ? $product->get_available_variations() : false;
	}
}
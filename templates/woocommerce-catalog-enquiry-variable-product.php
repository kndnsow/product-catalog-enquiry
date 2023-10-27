<?php
/**
 * Variable product add to cart
 *
 * Override this template by copying it to yourtheme/woocommerce-catalog-enquiry/woocommerce-catalog-enquiry-variable-product.php
 *
 *
 * @author  MultiVendorX
 * @package woocommerce-catalog-enquiry/Templates
 * @version 3.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product, $post;
$variation_attributes = $product->get_variation_attributes();
$attribute_keys = array_keys( $variation_attributes );
$attributes   = $product->get_attributes();
$default_arrt_value = get_post_meta( $post->ID, '_default_attributes', true);
$i = 0;
if(get_transient('variation_list')){
	$variation_list = get_transient('variation_list');
}
if(isset($default_arrt_value) && is_array($default_arrt_value) && (!empty($default_arrt_value))) {
	foreach($default_arrt_value as  $key => $value) {	
		$vname = str_replace('pa_','',$key);
		$vname2 = str_replace('attribute_pa_', '', $vname);
		$vname2 = str_replace('attribute_','',$vname2);		
		$arr = array('variation_name' => $vname2, 'variation_value' => $value, 'product_id' => $post->ID, 'variation_real_name' => 'attribute_'.$key);				
		$variation_list[$i] = $arr;
		$i++;
	}
	set_transient('variation_list', $variation_list, 30 * MINUTE_IN_SECONDS);
}
?>
<?php do_action( 'woocommerce_catalog_enquiry_before_add_to_cart_form' ); ?>

<form class="variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo $post->ID; ?>" data-product_variations="<?php echo esc_attr( json_encode( $available_variations ) ) ?>">
	<?php if ( ! empty( $available_variations ) ) : ?>
		<table class="variations" cellspacing="0">
			<tbody>
				<?php $loop = 0; foreach ( $variation_attributes as $name => $options ) : $loop++;  ?>
					<tr>
						<td class="label"><label for="<?php echo sanitize_title( $name ); ?>"><?php echo wc_attribute_label( $name ); ?></label></td>
						<td class="value">
							<?php
								$selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $name ) ] ) ? wc_clean( urldecode( $_REQUEST[ 'attribute_' . sanitize_title( $name ) ] ) ) : $product->get_variation_default_attribute( $name );
								wc_dropdown_variation_attribute_options( array( 'options' => $options, 'attribute' => $name, 'product' => $product, 'selected' => $selected ) );
								echo end( $attribute_keys ) === $name ? apply_filters( 'woocommerce_catalog_enquiry_reset_variations_link', '<a class="reset_variations" href="#">' . __( 'Clear', 'woocommerce' ) . '</a>' ) : '';
							?>
						</td>
					</tr>
		        <?php endforeach;?>
			</tbody>
		</table>

		<?php do_action( 'woocommerce_catalog_enquiry_before_add_to_cart_button' ); ?>
		<div class="single_variation_wrap">
			<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 *
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action( 'woocommerce_single_variation' );

				/**
				 * Hook: woocommerce_after_single_variation.
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
		<?php do_action( 'woocommerce_catalog_enquiry_after_add_to_cart_button' ); ?>

	<?php else : ?>

		<p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>

	<?php endif; ?>

</form>

<?php do_action( 'woocommerce_catalog_enquiry_after_add_to_cart_form' ); ?>

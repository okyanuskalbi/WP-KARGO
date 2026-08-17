<?php
/**
 * Kargoya verildi e-postasi (duz metin).
 *
 * @package WPKargoTakip
 *
 * @var WC_Order $order
 * @var string   $tracking_number
 * @var string   $tracking_url
 * @var string   $carrier_label
 * @var string   $email_heading
 * @var string   $additional_content
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

/* translators: %s: musteri adi. */
echo sprintf( esc_html__( 'Merhaba %s,', 'wp-kargo-takip' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

echo sprintf(
	/* translators: %s: siparis numarasi. */
	esc_html__( '#%s numarali siparisiniz kargoya verildi.', 'wp-kargo-takip' ),
	esc_html( $order->get_order_number() )
) . "\n\n";

echo esc_html__( 'Kargo firmasi', 'wp-kargo-takip' ) . ': ' . esc_html( $carrier_label ) . "\n";
echo esc_html__( 'Takip numarasi', 'wp-kargo-takip' ) . ': ' . esc_html( $tracking_number ) . "\n";

if ( '' !== $tracking_url ) {
	echo esc_html__( 'Takip adresi', 'wp-kargo-takip' ) . ': ' . esc_url_raw( $tracking_url ) . "\n";
}

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, false, true, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, false, true, $email );
do_action( 'woocommerce_email_customer_details', $order, false, true, $email );

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n" . esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) . "\n";

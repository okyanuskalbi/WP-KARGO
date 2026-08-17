<?php
/**
 * Kargoya verildi e-postasi (HTML).
 *
 * Temada ozelleştirmek icin bu dosyayi
 * yourtheme/woocommerce/emails/wpkt-shipped.php olarak kopyalayin.
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

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php printf( esc_html__( 'Merhaba %s,', 'wp-kargo-takip' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<p>
	<?php
	printf(
		/* translators: %s: siparis numarasi. */
		esc_html__( '#%s numarali siparisiniz kargoya verildi. Asagidaki takip numarasiyla gonderinizin durumunu izleyebilirsiniz.', 'wp-kargo-takip' ),
		esc_html( $order->get_order_number() )
	);
	?>
</p>

<table cellspacing="0" cellpadding="12" border="1" style="width:100%;border-collapse:collapse;border-color:#e5e5e5;margin-bottom:24px;">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left;background:#f8f8f8;width:40%;"><?php esc_html_e( 'Kargo firmasi', 'wp-kargo-takip' ); ?></th>
			<td><?php echo esc_html( $carrier_label ); ?></td>
		</tr>
		<tr>
			<th scope="row" style="text-align:left;background:#f8f8f8;"><?php esc_html_e( 'Takip numarasi', 'wp-kargo-takip' ); ?></th>
			<td style="font-size:18px;font-weight:bold;letter-spacing:1px;"><?php echo esc_html( $tracking_number ); ?></td>
		</tr>
	</tbody>
</table>

<?php if ( '' !== $tracking_url ) : ?>
	<p style="margin-bottom:24px;">
		<a href="<?php echo esc_url( $tracking_url ); ?>"
			style="display:inline-block;padding:12px 22px;background:#7f54b3;color:#ffffff;text-decoration:none;border-radius:4px;font-weight:bold;">
			<?php esc_html_e( 'Kargomu takip et', 'wp-kargo-takip' ); ?>
		</a>
	</p>
	<p style="font-size:13px;color:#666;">
		<?php esc_html_e( 'Buton calismazsa bu adresi tarayiciniza kopyalayin:', 'wp-kargo-takip' ); ?><br />
		<?php echo esc_html( $tracking_url ); ?>
	</p>
<?php endif; ?>

<?php
/*
 * Siparis ozeti ve adres: musteri hangi gonderinin yolda oldugunu
 * mailden cikmadan gorsun.
 */
do_action( 'woocommerce_email_order_details', $order, false, false, $email );
do_action( 'woocommerce_email_order_meta', $order, false, false, $email );
do_action( 'woocommerce_email_customer_details', $order, false, false, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );

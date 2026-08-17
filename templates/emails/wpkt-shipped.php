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

<p style="margin:0 0 16px;">
	<?php printf( esc_html__( 'Merhaba %s,', 'wp-kargo-takip' ), esc_html( $order->get_billing_first_name() ) ); ?>
</p>

<p style="margin:0 0 24px;">
	<?php
	printf(
		/* translators: %s: siparis numarasi. */
		esc_html__( '#%s numarali siparisiniz kargoya verildi. Asagidaki takip numarasiyla gonderinizin durumunu izleyebilirsiniz.', 'wp-kargo-takip' ),
		esc_html( $order->get_order_number() )
	);
	?>
</p>

<!-- Takip kutusu: tek renkli, gorsel gurultu olmadan tek bir bakista okunsun diye kenarlik + hafif dolgu ile ayrilmis tek blok. -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:0 0 24px;">
	<tr>
		<td style="background:#f7f5fb;border:1px solid #e3ddf0;border-radius:8px;padding:24px;text-align:center;">
			<p style="margin:0 0 4px;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#7f54b3;font-weight:700;">
				<?php echo esc_html( $carrier_label ); ?>
			</p>

			<?php if ( '' !== $tracking_url ) : ?>
				<p style="margin:0 0 20px;">
					<a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank" rel="noopener noreferrer"
						style="font-size:26px;font-weight:700;letter-spacing:1px;color:#2c2c2c;text-decoration:none;border-bottom:2px solid #7f54b3;">
						<?php echo esc_html( $tracking_number ); ?>
					</a>
				</p>
			<?php else : ?>
				<p style="margin:0 0 20px;font-size:26px;font-weight:700;letter-spacing:1px;color:#2c2c2c;">
					<?php echo esc_html( $tracking_number ); ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $tracking_url ) : ?>
				<a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank" rel="noopener noreferrer"
					style="display:inline-block;padding:13px 28px;background:#7f54b3;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:700;font-size:15px;">
					<?php esc_html_e( 'Kargomu takip et', 'wp-kargo-takip' ); ?>
				</a>
			<?php endif; ?>
		</td>
	</tr>
</table>

<?php if ( '' !== $tracking_url ) : ?>
	<p style="margin:0 0 24px;font-size:12px;color:#767676;">
		<?php esc_html_e( 'Buton veya takip numarasi acilmazsa bu adresi tarayiciniza kopyalayin:', 'wp-kargo-takip' ); ?><br />
		<a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank" rel="noopener noreferrer" style="color:#7f54b3;word-break:break-all;">
			<?php echo esc_html( $tracking_url ); ?>
		</a>
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

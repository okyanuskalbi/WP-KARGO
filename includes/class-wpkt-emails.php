<?php
/**
 * E-posta akisi ve musteriye gorunen kargo bilgisi.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * E-posta baglayicisi.
 */
class WPKT_Emails {

	/**
	 * Kancalar.
	 */
	public function init() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email' ) );

		// Durum elle "Kargoya Verildi"ye cekildiginde de bildirim gitsin.
		add_action( 'woocommerce_order_status_' . WPKT_Statuses::SLUG, array( $this, 'on_shipped_status' ), 20, 2 );

		// Musteri tarafi: siparis detayi ve WooCommerce e-postalarinda takip bilgisi.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_customer_tracking' ), 10, 1 );
		add_action( 'woocommerce_email_order_meta', array( $this, 'render_email_tracking' ), 10, 4 );
	}

	/**
	 * Kargo e-postasini WooCommerce'e kaydeder.
	 *
	 * @param array<string,WC_Email> $emails E-postalar.
	 * @return array<string,WC_Email>
	 */
	public function register_email( $emails ) {
		require_once WPKT_DIR . 'includes/class-wpkt-email-shipped.php';

		$emails['WPKT_Email_Shipped'] = new WPKT_Email_Shipped();

		return $emails;
	}

	/**
	 * Durum kargodaya gectiginde bildirim.
	 *
	 * @param int      $order_id Siparis kimligi.
	 * @param WC_Order $order    Siparis.
	 */
	public function on_shipped_status( $order_id, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order instanceof WC_Order || '' === WPKT_Order::get_number( $order ) ) {
			return;
		}

		self::maybe_notify( $order );
	}

	/**
	 * Bildirimi gonderir.
	 *
	 * Ayni takip numarasi icin ikinci kez gonderilmez; admin ayni siparisi
	 * birkac kez kaydettiginde musteri ayni maili tekrar tekrar almasin.
	 *
	 * @param WC_Order $order Siparis.
	 * @param bool     $force Damgayi yok say (elle yeniden gonderme).
	 */
	public static function maybe_notify( $order, $force = false ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( '' === WPKT_Order::get_number( $order ) ) {
			return;
		}

		if ( ! $force && WPKT_Order::is_notified( $order ) ) {
			return;
		}

		/**
		 * Bildirim gonderilsin mi.
		 *
		 * @param bool     $send  Gonderilecek mi.
		 * @param WC_Order $order Siparis.
		 * @param bool     $force Elle tetiklendi mi.
		 */
		if ( ! apply_filters( 'wpkt_should_send_shipped_email', true, $order, $force ) ) {
			return;
		}

		/**
		 * Kargo bildirimi tetigi. WPKT_Email_Shipped bu kancaya baglidir.
		 *
		 * @param int      $order_id Siparis kimligi.
		 * @param WC_Order $order    Siparis.
		 */
		do_action( 'wpkt_order_shipped_notification', $order->get_id(), $order );

		/*
		 * WPKT_Email_Shipped::trigger() do_action icinde SENKRON calisir ve
		 * gonderim sonucunu WPKT_Order::set_mail_status()'a (siparis notu
		 * dahil) zaten yazmis olur. "notified" damgasi yalnizca BASARILI
		 * gonderimde kilitlenir; boylece SMTP hatasi gibi gecici bir
		 * basarisizlikta sonraki kayittaki otomatik deneme sessizce
		 * engellenmez — is_notified() guard'i bosuna atlanmaz.
		 */
		if ( 'sent' === WPKT_Order::get_mail_status( $order ) ) {
			WPKT_Order::mark_notified( $order );
		}
	}

	/**
	 * Siparis detay sayfasinda takip bilgisi.
	 *
	 * @param WC_Order $order Siparis.
	 */
	public function render_customer_tracking( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$number = WPKT_Order::get_number( $order );

		if ( '' === $number ) {
			return;
		}

		$url = WPKT_Order::get_tracking_url( $order );
		?>
		<section class="wpkt-customer-tracking">
			<h2><?php esc_html_e( 'Kargo Takip', 'wp-kargo-takip' ); ?></h2>
			<table class="woocommerce-table shop_table wpkt-tracking-table">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Kargo firmasi', 'wp-kargo-takip' ); ?></th>
						<td><?php echo esc_html( WPKT_Carriers::label( WPKT_Order::get_carrier( $order ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Takip numarasi', 'wp-kargo-takip' ); ?></th>
						<td>
							<?php if ( '' !== $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $number ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $number ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</section>
		<?php
	}

	/**
	 * WooCommerce e-postalarina takip bilgisi ekler.
	 *
	 * Kargo mailinde bilgi zaten govdede oldugu icin orada tekrarlanmaz.
	 *
	 * @param WC_Order $order         Siparis.
	 * @param bool     $sent_to_admin Yoneticiye mi.
	 * @param bool     $plain_text    Duz metin mi.
	 * @param WC_Email $email         E-posta nesnesi.
	 */
	public function render_email_tracking( $order, $sent_to_admin = false, $plain_text = false, $email = null ) {
		if ( ! $order instanceof WC_Order || 'yes' !== get_option( 'wpkt_show_in_emails', 'yes' ) ) {
			return;
		}

		// Kargo mailinin govdesinde bilgi zaten var.
		if ( $email instanceof WC_Email && 'wpkt_shipped' === $email->id ) {
			return;
		}

		$number = WPKT_Order::get_number( $order );

		if ( '' === $number ) {
			return;
		}

		$carrier = WPKT_Carriers::label( WPKT_Order::get_carrier( $order ) );
		$url     = WPKT_Order::get_tracking_url( $order );

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Kargo Takip', 'wp-kargo-takip' ) . "\n";
			echo esc_html( $carrier ) . ': ' . esc_html( $number ) . "\n";

			if ( '' !== $url ) {
				echo esc_url_raw( $url ) . "\n";
			}

			return;
		}

		echo '<h2 style="margin:24px 0 8px;">' . esc_html__( 'Kargo Takip', 'wp-kargo-takip' ) . '</h2>';
		echo '<p style="margin:0 0 16px;">' . esc_html( $carrier ) . ': <strong>' . esc_html( $number ) . '</strong>';

		if ( '' !== $url ) {
			echo ' &mdash; <a href="' . esc_url( $url ) . '">' . esc_html__( 'Kargoyu takip et', 'wp-kargo-takip' ) . '</a>';
		}

		echo '</p>';
	}
}

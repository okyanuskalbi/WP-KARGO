<?php
/**
 * "Kargoya Verildi" musteri e-postasi.
 *
 * WooCommerce'in kendi e-posta altyapisina baglanir: WooCommerce > Ayarlar >
 * E-postalar altinda konu/baslik/icerik duzenlenebilir, sablon tema icine
 * kopyalanarak (woocommerce/emails/...) ozelleştirilebilir.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Kargo bildirimi e-postasi.
 */
class WPKT_Email_Shipped extends WC_Email {

	const ID = 'wpkt_shipped';

	/**
	 * Kurulum.
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->customer_email = true;
		$this->title          = __( 'Kargoya verildi', 'wp-kargo-takip' );
		$this->description    = __( 'Siparise kargo takip numarasi girildiginde musteriye gonderilir. Takip numarasi ve takip linki icerir.', 'wp-kargo-takip' );

		$this->template_html  = 'emails/wpkt-shipped.php';
		$this->template_plain = 'emails/plain/wpkt-shipped.php';
		$this->template_base  = WPKT_DIR . 'templates/';

		$this->placeholders = array(
			'{order_number}'   => '',
			'{order_date}'     => '',
			'{tracking_number}' => '',
			'{carrier}'        => '',
		);

		add_action( 'wpkt_order_shipped_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Varsayilan konu.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '#{order_number} numarali siparisiniz kargoya verildi', 'wp-kargo-takip' );
	}

	/**
	 * Varsayilan baslik.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Siparisiniz kargoya verildi', 'wp-kargo-takip' );
	}

	/**
	 * Varsayilan giris metni.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Kargo firmasi teslimat surecini baslatti. Takip numarasi sistemlere islenene kadar sorgu sonucu birkac saat bos gorunebilir.', 'wp-kargo-takip' );
	}

	/**
	 * Gonderimi tetikler.
	 *
	 * @param int      $order_id Siparis kimligi.
	 * @param WC_Order $order    Siparis.
	 */
	public function trigger( $order_id, $order = null ) {
		$this->setup_locale();

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order instanceof WC_Order ) {
			$this->object    = $order;
			$this->recipient = $order->get_billing_email();

			$this->placeholders['{order_number}']    = $order->get_order_number();
			$this->placeholders['{order_date}']      = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{tracking_number}'] = WPKT_Order::get_number( $order );
			$this->placeholders['{carrier}']         = WPKT_Carriers::label( WPKT_Order::get_carrier( $order ) );
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * HTML govde.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'tracking_number'    => WPKT_Order::get_number( $this->object ),
				'tracking_url'       => WPKT_Order::get_tracking_url( $this->object ),
				'carrier_label'      => WPKT_Carriers::label( WPKT_Order::get_carrier( $this->object ) ),
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Duz metin govde.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'tracking_number'    => WPKT_Order::get_number( $this->object ),
				'tracking_url'       => WPKT_Order::get_tracking_url( $this->object ),
				'carrier_label'      => WPKT_Carriers::label( WPKT_Order::get_carrier( $this->object ) ),
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}
}

<?php
/**
 * "Kargoya Verildi" siparis durumu.
 *
 * WooCommerce'te hazir bir kargo durumu yok; "tamamlandi" hem odemeyi hem
 * teslimi ifade ettigi icin ayri bir durum kaydediyoruz. Boylece musteri
 * "kargoya verildi" mailini alirken siparis listesinde de durum gorunur.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Durum kaydi.
 */
class WPKT_Statuses {

	/**
	 * Durum anahtari (wc- onekli).
	 */
	const STATUS = 'wc-kargoda';

	/**
	 * Durum anahtari (oneksiz).
	 */
	const SLUG = 'kargoda';

	/**
	 * Kancalar.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_to_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'include_in_reports' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'exclude_from_payment_complete' ) );

		// Musteri "iade/iptal" gibi islemleri kargodaki siparis icin de gorsun
		// diye durum, tamamlanmis sayilmayan ama aktif olan gruba girer.
		add_filter( 'woocommerce_order_is_editable', array( $this, 'not_editable' ), 10, 2 );
	}

	/**
	 * Durumu kaydeder.
	 */
	public function register_status() {
		register_post_status(
			self::STATUS,
			array(
				'label'                     => _x( 'Kargoya Verildi', 'Order status', 'wp-kargo-takip' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: siparis sayisi. */
				'label_count'               => _n_noop( 'Kargoya Verildi <span class="count">(%s)</span>', 'Kargoya Verildi <span class="count">(%s)</span>', 'wp-kargo-takip' ),
			)
		);
	}

	/**
	 * Durumu WooCommerce listesine ekler.
	 *
	 * "Tamamlandi"nin hemen oncesine yerlestirilir; siparis akisi
	 * islemde -> kargoda -> tamamlandi seklinde okunur.
	 *
	 * @param array<string,string> $statuses Durumlar.
	 * @return array<string,string>
	 */
	public function add_to_status_list( $statuses ) {
		$ordered = array();

		foreach ( $statuses as $key => $label ) {
			if ( 'wc-completed' === $key ) {
				$ordered[ self::STATUS ] = _x( 'Kargoya Verildi', 'Order status', 'wp-kargo-takip' );
			}

			$ordered[ $key ] = $label;
		}

		if ( ! isset( $ordered[ self::STATUS ] ) ) {
			$ordered[ self::STATUS ] = _x( 'Kargoya Verildi', 'Order status', 'wp-kargo-takip' );
		}

		return $ordered;
	}

	/**
	 * Raporlarda satis olarak sayilsin (odeme alinmis siparis).
	 *
	 * @param array<int,string> $statuses Durumlar.
	 * @return array<int,string>
	 */
	public function include_in_reports( $statuses ) {
		$statuses[] = self::SLUG;

		return $statuses;
	}

	/**
	 * Odeme tamamlandiginda durum kargodaya cekilmesin.
	 *
	 * @param array<int,string> $statuses Durumlar.
	 * @return array<int,string>
	 */
	public function exclude_from_payment_complete( $statuses ) {
		return array_values( array_diff( $statuses, array( self::SLUG ) ) );
	}

	/**
	 * Kargoya verilmis siparis kalem duzenlemeye kapalidir.
	 *
	 * @param bool     $editable Duzenlenebilir mi.
	 * @param WC_Order $order    Siparis.
	 * @return bool
	 */
	public function not_editable( $editable, $order ) {
		if ( $order instanceof WC_Order && $order->has_status( self::SLUG ) ) {
			return false;
		}

		return $editable;
	}
}

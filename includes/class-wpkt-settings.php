<?php
/**
 * Ayarlar: WooCommerce > Ayarlar > Kargo > Kargo Takip.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ayar sayfasi.
 */
class WPKT_Settings {

	const SECTION = 'wpkt';

	/**
	 * Kancalar.
	 */
	public function init() {
		add_filter( 'woocommerce_get_sections_shipping', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_shipping', array( $this, 'add_settings' ), 10, 2 );
		add_filter( 'plugin_action_links_' . WPKT_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Bolumu ekler.
	 *
	 * @param array<string,string> $sections Bolumler.
	 * @return array<string,string>
	 */
	public function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Kargo Takip', 'wp-kargo-takip' );

		return $sections;
	}

	/**
	 * Ayar alanlari.
	 *
	 * @param array  $settings        Ayarlar.
	 * @param string $current_section Aktif bolum.
	 * @return array
	 */
	public function add_settings( $settings, $current_section ) {
		if ( self::SECTION !== $current_section ) {
			return $settings;
		}

		$fields = array(
			array(
				'title' => __( 'Kargo Takip', 'wp-kargo-takip' ),
				'type'  => 'title',
				'desc'  => __( 'Siparis ekranindaki "Kargo Takip" kutusuna takip numarasi girildiginde ne olacagini buradan belirleyin. E-postanin konu, baslik ve metni WooCommerce > Ayarlar > E-postalar > "Kargoya verildi" altindan duzenlenir.', 'wp-kargo-takip' ),
				'id'    => 'wpkt_options',
			),
			array(
				'title'          => __( 'Durumu otomatik degistir', 'wp-kargo-takip' ),
				'desc'           => __( 'Takip numarasi kaydedildiginde siparisi "Kargoya Verildi" durumuna al', 'wp-kargo-takip' ),
				'id'             => 'wpkt_auto_status',
				'type'           => 'checkbox',
				'default'        => 'yes',
				'checkbox_group' => 'start',
			),
			array(
				'desc'           => __( 'Takip bilgisini diger WooCommerce e-postalarina da ekle', 'wp-kargo-takip' ),
				'id'             => 'wpkt_show_in_emails',
				'type'           => 'checkbox',
				'default'        => 'yes',
				'checkbox_group' => 'end',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wpkt_options',
			),
			array(
				'title' => __( 'Takip adresleri', 'wp-kargo-takip' ),
				'type'  => 'title',
				'desc'  => __( 'Bos birakilan firmalarda eklentinin varsayilan adresi kullanilir. <code>{no}</code> takip numarasiyla degistirilir. Kargo firmasi sorgu adresini degistirirse guncelleme beklemeden buradan duzeltebilirsiniz.', 'wp-kargo-takip' ),
				'id'    => 'wpkt_tracking_urls',
			),
		);

		/*
		 * Her firma icin ozel takip adresi alani. Alanlar kayit defterinden
		 * uretilir: wpkt_carriers filtresiyle eklenen firmalar da otomatik
		 * burada gorunur. Secenek anahtari (wpkt_tracking_url_{firma})
		 * WPKT_Carriers::tracking_url() ile ayni kalip kullanir.
		 */
		foreach ( WPKT_Carriers::all() as $key => $carrier ) {
			$fields[] = array(
				'title'       => sprintf(
					/* translators: %s: kargo firmasi adi. */
					__( '%s takip adresi', 'wp-kargo-takip' ),
					$carrier['label']
				),
				'id'          => 'wpkt_tracking_url_' . $key,
				'type'        => 'url',
				'default'     => '',
				'css'         => 'min-width:420px;',
				'placeholder' => $carrier['url'],
			);
		}

		$fields[] = array(
			'type' => 'sectionend',
			'id'   => 'wpkt_tracking_urls',
		);

		return $fields;
	}

	/**
	 * Eklenti listesine ayar linki.
	 *
	 * @param array<int,string> $links Linkler.
	 * @return array<int,string>
	 */
	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=shipping&section=' . self::SECTION );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ayarlar', 'wp-kargo-takip' ) . '</a>'
		);

		return $links;
	}
}

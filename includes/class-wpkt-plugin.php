<?php
/**
 * Eklenti cekirdegi: bagimliliklari dogrular ve alt modulleri baglar.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cekirdek sinif.
 */
class WPKT_Plugin {

	/**
	 * Kancalar.
	 */
	public function init() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );

		// Guncelleyici WooCommerce'ten bagimsizdir: WooCommerce kapali olsa da
		// eklenti kendini guncelleyebilmeli, yoksa bozuk bir surumde kilitlenir.
		( new WPKT_Updater() )->init();

		add_action( 'plugins_loaded', array( $this, 'boot' ) );
	}

	/**
	 * Ceviri dosyalarini yukler.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-kargo-takip', false, dirname( WPKT_BASENAME ) . '/languages' );
	}

	/**
	 * HPOS (yeni siparis tablolari) uyumlulugunu bildirir.
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WPKT_FILE, true );
		}
	}

	/**
	 * WooCommerce varsa modulleri baslatir.
	 */
	public function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_woocommerce_notice' ) );
			return;
		}

		( new WPKT_Statuses() )->init();
		( new WPKT_Admin() )->init();
		( new WPKT_Emails() )->init();
		( new WPKT_Settings() )->init();
	}

	/**
	 * WooCommerce yoksa uyari.
	 */
	public function missing_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		esc_html_e( 'WP Kargo Takip icin WooCommerce gerekli. WooCommerce etkinlestirilene kadar kargo alanlari gorunmez.', 'wp-kargo-takip' );
		echo '</p></div>';
	}
}

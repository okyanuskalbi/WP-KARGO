<?php
/**
 * Plugin Name:       WP Kargo Takip
 * Plugin URI:        https://github.com/okyanuskalbi/WP-KARGO
 * Description:       WooCommerce siparislerine kargo takip numarasi girin; musteriye "Kargoya Verildi" durumu ve takip numarasi/linki iceren e-posta otomatik gonderilsin. Yurtici, Aras, MNG, PTT ve Surat Kargo destekli.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            okyanuskalbi
 * Author URI:        https://github.com/okyanuskalbi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-kargo-takip
 * Domain Path:       /languages
 * Update URI:        https://github.com/okyanuskalbi/WP-KARGO
 * WC requires at least: 7.0
 * WC tested up to:   9.9
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

define( 'WPKT_VERSION', '1.1.0' );
define( 'WPKT_FILE', __FILE__ );
define( 'WPKT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPKT_URL', plugin_dir_url( __FILE__ ) );
define( 'WPKT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Otomatik guncelleme kaynagi.
 *
 * Eklenti bu depodaki release'lerden guncellenir; surum etiketleri
 * WPKT_GH_TAG_PREFIX ile baslar (ornek: v1.0.1). Depo catallanirsa
 * wp-config.php icinde bu sabitleri tanimlamak yeterli — kod degismez.
 */
defined( 'WPKT_GH_REPO' ) || define( 'WPKT_GH_REPO', 'okyanuskalbi/WP-KARGO' );
defined( 'WPKT_GH_TAG_PREFIX' ) || define( 'WPKT_GH_TAG_PREFIX', 'v' );

require_once WPKT_DIR . 'includes/class-wpkt-carriers.php';
require_once WPKT_DIR . 'includes/class-wpkt-order.php';
require_once WPKT_DIR . 'includes/class-wpkt-statuses.php';
require_once WPKT_DIR . 'includes/class-wpkt-admin.php';
require_once WPKT_DIR . 'includes/class-wpkt-emails.php';
require_once WPKT_DIR . 'includes/class-wpkt-settings.php';
require_once WPKT_DIR . 'includes/class-wpkt-updater.php';
require_once WPKT_DIR . 'includes/class-wpkt-plugin.php';

/**
 * Cekirdek ornek.
 *
 * @return WPKT_Plugin
 */
function wpkt() {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new WPKT_Plugin();
	}

	return $instance;
}

wpkt()->init();

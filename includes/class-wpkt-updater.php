<?php
/**
 * GitHub release'lerinden otomatik guncelleme.
 *
 * WordPress'in kendi guncelleme akisina baglanir: yeni bir release
 * yayinlandiginda "Eklentiler" ekraninda guncelleme uyarisi cikar ve tek
 * tikla kurulur. Ucretsizdir, harici servis ya da lisans anahtari gerektirmez;
 * genel (public) depoda token da gerekmez.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Guncelleyici.
 */
class WPKT_Updater {

	/**
	 * Release verisi onbellek anahtari.
	 */
	const CACHE_KEY = 'wpkt_gh_release';

	/**
	 * Onbellek suresi (saniye).
	 */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Kancalar.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'flush_cache' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_manual_check' ) );
	}

	/**
	 * Guncelleme listesine kaydi ekler.
	 *
	 * @param mixed $transient Guncelleme transient'i.
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		$release = $this->get_release();

		if ( ! $release ) {
			return $transient;
		}

		$item = (object) array(
			'id'          => WPKT_GH_REPO . '/' . WPKT_BASENAME,
			'slug'        => dirname( WPKT_BASENAME ),
			'plugin'      => WPKT_BASENAME,
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
			'tested'      => $release['tested'],
			'requires_php' => '7.4',
			'icons'       => array(),
			'banners'     => array(),
		);

		if ( version_compare( $release['version'], WPKT_VERSION, '>' ) ) {
			$transient->response[ WPKT_BASENAME ] = $item;
			unset( $transient->no_update[ WPKT_BASENAME ] );
		} else {
			// Guncel oldugunda da kayit birakilir; "otomatik guncellemeyi etkinlestir"
			// baglantisi ancak boyle gorunur.
			$transient->no_update[ WPKT_BASENAME ] = $item;
		}

		return $transient;
	}

	/**
	 * "Detaylari goruntule" penceresi.
	 *
	 * @param false|object|array $result Sonuc.
	 * @param string             $action Istenen islem.
	 * @param object             $args   Argumanlar.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( empty( $args->slug ) || dirname( WPKT_BASENAME ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();

		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'WP Kargo Takip',
			'slug'          => dirname( WPKT_BASENAME ),
			'version'       => $release['version'],
			'author'        => '<a href="https://github.com/okyanuskalbi">okyanuskalbi</a>',
			'homepage'      => $release['url'],
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'tested'        => $release['tested'],
			'last_updated'  => $release['date'],
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => wp_kses_post( wpautop( __( 'WooCommerce siparislerine kargo takip numarasi girildiginde siparisi "Kargoya Verildi" durumuna alir ve musteriye takip numarasi/linki iceren e-posta gonderir.', 'wp-kargo-takip' ) ) ),
				'changelog'   => wp_kses_post( wpautop( $release['notes'] ) ),
			),
		);
	}

	/**
	 * Zip icindeki klasor adini duzeltir.
	 *
	 * GitHub'in otomatik kaynak arsivi "wp-kargo-takip-<sha>" gibi bir klasor
	 * acar; boyle kurulan eklenti dizin degistirdigi icin devre disi kalir.
	 * Release'e hazir bir zip eklendiginde bu adim devreye girmez.
	 *
	 * @param string      $source        Kaynak dizin.
	 * @param string      $remote_source Uzak kaynak.
	 * @param WP_Upgrader $upgrader      Yukleyici.
	 * @param array       $hook_extra    Ek veri.
	 * @return string|WP_Error
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader = null, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || WPKT_BASENAME !== $hook_extra['plugin'] ) {
			return $source;
		}

		$expected = trailingslashit( dirname( WPKT_BASENAME ) );

		if ( trailingslashit( basename( $source ) ) === $expected ) {
			return $source;
		}

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			return $source;
		}

		/*
		 * Kaynak arsivinde eklenti dosyalari kokte durur; yine de bir alt
		 * klasore sarilmis arsivler icin ikinci olasilik denenir.
		 */
		$root      = trailingslashit( $source );
		$candidate = $root . 'wp-kargo-takip/';
		$inner     = ( ! $wp_filesystem->exists( $root . 'wp-kargo-takip.php' ) && $wp_filesystem->exists( $candidate . 'wp-kargo-takip.php' ) )
			? $candidate
			: $root;

		$target = trailingslashit( $remote_source ) . $expected;

		if ( $wp_filesystem->exists( $target ) ) {
			$wp_filesystem->delete( $target, true );
		}

		if ( ! $wp_filesystem->move( untrailingslashit( $inner ), untrailingslashit( $target ) ) ) {
			return new WP_Error(
				'wpkt_rename_failed',
				__( 'Guncelleme arsivi hazirlanamadi (klasor adi duzeltilemedi).', 'wp-kargo-takip' )
			);
		}

		return $target;
	}

	/**
	 * Eklenti satirina "guncellemeleri denetle" baglantisi.
	 *
	 * @param array<int,string> $meta   Satir metasi.
	 * @param string            $plugin Eklenti dosyasi.
	 * @return array<int,string>
	 */
	public function row_meta( $meta, $plugin ) {
		if ( WPKT_BASENAME !== $plugin ) {
			return $meta;
		}

		$url = wp_nonce_url(
			add_query_arg( 'wpkt_check_update', '1', admin_url( 'plugins.php' ) ),
			'wpkt_check_update'
		);

		$meta[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Guncellemeleri denetle', 'wp-kargo-takip' ) . '</a>';

		return $meta;
	}

	/**
	 * Elle denetleme.
	 */
	public function handle_manual_check() {
		if ( ! isset( $_GET['wpkt_check_update'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpkt_check_update' ) ) {
			return;
		}

		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Kurulum sonrasi onbellegi bosaltir.
	 *
	 * @param WP_Upgrader $upgrader Yukleyici.
	 * @param array       $options  Islem verisi.
	 */
	public function flush_cache( $upgrader, $options ) {
		if ( 'update' !== ( $options['action'] ?? '' ) || 'plugin' !== ( $options['type'] ?? '' ) ) {
			return;
		}

		delete_site_transient( self::CACHE_KEY );
	}

	/**
	 * Son uygun release verisi.
	 *
	 * @return array{version:string,package:string,url:string,notes:string,date:string,tested:string}|null
	 */
	private function get_release() {
		$cached = get_site_transient( self::CACHE_KEY );

		if ( is_array( $cached ) ) {
			// Bos surum = "GitHub'a ulasilamadi / uygun release yok" damgasi.
			return '' === (string) ( $cached['version'] ?? '' ) ? null : $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . WPKT_GH_REPO . '/releases?per_page=30',
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'wp-kargo-takip/' . WPKT_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Kisa sureli negatif onbellek: GitHub erisilemezse her sayfa
			// yuklemesinde tekrar denenmesin.
			set_site_transient( self::CACHE_KEY, array( 'version' => '' ), 15 * MINUTE_IN_SECONDS );
			return null;
		}

		$releases = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $releases ) ) {
			set_site_transient( self::CACHE_KEY, array( 'version' => '' ), 15 * MINUTE_IN_SECONDS );
			return null;
		}

		$release = $this->pick_release( $releases );

		if ( ! $release ) {
			set_site_transient( self::CACHE_KEY, array( 'version' => '' ), self::CACHE_TTL );
			return null;
		}

		set_site_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Etiket onekine uyan en yuksek surumlu release'i secer.
	 *
	 * Taslak ve on-yayin (prerelease) release'ler atlanir; boylece bir surum
	 * hazirlanirken yayindaki sitelere yarim is inmez.
	 *
	 * @param array<int,array> $releases GitHub yaniti.
	 * @return array|null
	 */
	private function pick_release( $releases ) {
		$best = null;

		foreach ( $releases as $release ) {
			if ( ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
				continue;
			}

			$tag = (string) ( $release['tag_name'] ?? '' );

			if ( 0 !== strpos( $tag, WPKT_GH_TAG_PREFIX ) ) {
				continue;
			}

			$version = ltrim( substr( $tag, strlen( WPKT_GH_TAG_PREFIX ) ), 'v' );

			if ( '' === $version || ! preg_match( '/^\d+(\.\d+)*/', $version ) ) {
				continue;
			}

			if ( $best && version_compare( $version, $best['version'], '<=' ) ) {
				continue;
			}

			$best = array(
				'version' => $version,
				'package' => $this->pick_package( $release ),
				'url'     => (string) ( $release['html_url'] ?? '' ),
				'notes'   => (string) ( $release['body'] ?? '' ),
				'date'    => (string) ( $release['published_at'] ?? '' ),
				'tested'  => get_bloginfo( 'version' ),
			);
		}

		return $best && '' !== $best['package'] ? $best : null;
	}

	/**
	 * Indirilecek zip adresi.
	 *
	 * Release'e eklenmis hazir eklenti zip'i (wp-kargo-takip*.zip) tercih
	 * edilir; yoksa depo kaynak arsivine dusulur.
	 *
	 * @param array $release Release verisi.
	 * @return string
	 */
	private function pick_package( $release ) {
		foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
			$name = (string) ( $asset['name'] ?? '' );

			if ( 0 === strpos( $name, 'wp-kargo-takip' ) && '.zip' === substr( $name, -4 ) ) {
				return (string) ( $asset['browser_download_url'] ?? '' );
			}
		}

		return (string) ( $release['zipball_url'] ?? '' );
	}
}

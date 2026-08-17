<?php
/**
 * Yonetici arayuzu: siparis kutusu, kaydetme, liste kolonu.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin tarafi.
 */
class WPKT_Admin {

	const NONCE = 'wpkt_save_tracking';

	/**
	 * Kancalar.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ), 20, 1 );

		// Siparis listesi kolonu — HPOS ve klasik tablo ayri kancalar kullanir.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ), 20 );
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column_legacy' ), 20, 2 );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_column' ), 20, 2 );

		// Siparis islemleri: bildirimi elle yeniden gonderme.
		add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ) );
		add_action( 'woocommerce_order_action_wpkt_resend_shipped_email', array( $this, 'resend_email' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Kargo kutusunu ekler.
	 *
	 * @param string $screen_id Ekran kimligi.
	 */
	public function add_meta_box( $screen_id ) {
		/*
		 * HPOS ekran kimligi sabit varsayilmaz: WooCommerce menu konumunu
		 * degistirirse elle yazilmis "woocommerce_page_wc-orders" sessizce
		 * eslesmez ve kutu hic gorunmez. Kimligi WooCommerce'in kendisi verir.
		 */
		$screens = array( 'shop_order' );

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screens[] = 'woocommerce_page_wc-orders';
		}

		if ( ! in_array( $screen_id, $screens, true ) ) {
			return;
		}

		add_meta_box(
			'wpkt_tracking',
			__( 'Kargo Takip', 'wp-kargo-takip' ),
			array( $this, 'render_meta_box' ),
			$screen_id,
			'side',
			'high'
		);
	}

	/**
	 * Kutu icerigi.
	 *
	 * @param WP_Post|WC_Order $post_or_order Siparis nesnesi ya da gonderi.
	 */
	public function render_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$number   = WPKT_Order::get_number( $order );
		$carrier  = WPKT_Order::get_carrier( $order );
		$date     = WPKT_Order::get_shipped_date( $order );
		$url      = WPKT_Order::get_tracking_url( $order );
		$notified = WPKT_Order::is_notified( $order );

		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<div class="wpkt-box">
			<p>
				<label for="wpkt_carrier"><strong><?php esc_html_e( 'Kargo firmasi', 'wp-kargo-takip' ); ?></strong></label>
				<select name="wpkt_carrier" id="wpkt_carrier" class="wpkt-field">
					<?php foreach ( WPKT_Carriers::options() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $carrier, $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="wpkt_tracking_number"><strong><?php esc_html_e( 'Takip numarasi', 'wp-kargo-takip' ); ?></strong></label>
				<input type="text" name="wpkt_tracking_number" id="wpkt_tracking_number" class="wpkt-field"
					value="<?php echo esc_attr( $number ); ?>" autocomplete="off"
					placeholder="<?php esc_attr_e( 'orn. 1234567890123', 'wp-kargo-takip' ); ?>" />
			</p>

			<p>
				<label for="wpkt_shipped_date"><strong><?php esc_html_e( 'Kargoya verilis tarihi', 'wp-kargo-takip' ); ?></strong></label>
				<input type="date" name="wpkt_shipped_date" id="wpkt_shipped_date" class="wpkt-field"
					value="<?php echo esc_attr( $date ); ?>" />
			</p>

			<?php if ( '' !== $number ) : ?>
				<p class="wpkt-meta">
					<?php if ( '' !== $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Kargoyu takip et', 'wp-kargo-takip' ); ?>
						</a><br />
					<?php endif; ?>
					<span class="wpkt-status <?php echo $notified ? 'wpkt-status--sent' : 'wpkt-status--pending'; ?>">
						<?php
						echo $notified
							? esc_html__( 'Bu numara icin musteriye bilgilendirme gonderildi.', 'wp-kargo-takip' )
							: esc_html__( 'Musteriye henuz bilgilendirme gonderilmedi.', 'wp-kargo-takip' );
						?>
					</span>
				</p>
			<?php endif; ?>

			<p class="wpkt-hint">
				<?php
				if ( 'yes' === get_option( 'wpkt_auto_status', 'yes' ) ) {
					esc_html_e( 'Numara kaydedildiginde siparis "Kargoya Verildi" durumuna gecer ve musteriye takip numarasi e-postayla gonderilir.', 'wp-kargo-takip' );
				} else {
					esc_html_e( 'Numara kaydedildiginde musteriye takip numarasi e-postayla gonderilir. Durum degisimi ayarlardan kapali.', 'wp-kargo-takip' );
				}
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Kutuyu kaydeder ve gerekiyorsa durum/e-posta akisini tetikler.
	 *
	 * @param int $order_id Siparis kimligi.
	 */
	public function save( $order_id ) {
		// Nonce yoksa kutu bu istekte hic gonderilmemis demektir (ornek: toplu
		// islem, REST). Sessizce cikilir; aksi halde mevcut veri silinir.
		if ( ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$number  = isset( $_POST['wpkt_tracking_number'] ) ? (string) wp_unslash( $_POST['wpkt_tracking_number'] ) : '';
		$carrier = isset( $_POST['wpkt_carrier'] ) ? sanitize_key( wp_unslash( $_POST['wpkt_carrier'] ) ) : WPKT_Carriers::DEFAULT_CARRIER;
		$date    = isset( $_POST['wpkt_shipped_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpkt_shipped_date'] ) ) : '';

		if ( '' !== $date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = '';
		}

		$changed = WPKT_Order::save( $order, $number, $carrier, $date );

		if ( ! $changed || '' === WPKT_Order::get_number( $order ) ) {
			return;
		}

		/*
		 * Durum degisimi ONCE yapilir: WooCommerce'in kendi durum maili ile
		 * bizim kargo mailinin sirasi boylece ongorulebilir olur. Durumu
		 * degistiren kanca da bildirimi tetikleyebilecegi icin gonderim
		 * "notified" damgasiyla tek sefere baglanmistir.
		 */
		if ( 'yes' === get_option( 'wpkt_auto_status', 'yes' ) && ! $order->has_status( WPKT_Statuses::SLUG ) ) {
			$order->update_status(
				WPKT_Statuses::SLUG,
				__( 'Kargo takip numarasi girildi.', 'wp-kargo-takip' )
			);
		}

		WPKT_Emails::maybe_notify( $order );
	}

	/**
	 * Liste kolonu ekler.
	 *
	 * @param array<string,string> $columns Kolonlar.
	 * @return array<string,string>
	 */
	public function add_column( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'order_status' === $key ) {
				$new['wpkt_tracking'] = __( 'Kargo Takip', 'wp-kargo-takip' );
			}
		}

		if ( ! isset( $new['wpkt_tracking'] ) ) {
			$new['wpkt_tracking'] = __( 'Kargo Takip', 'wp-kargo-takip' );
		}

		return $new;
	}

	/**
	 * Kolon icerigi (HPOS).
	 *
	 * @param string   $column Kolon anahtari.
	 * @param WC_Order $order  Siparis.
	 */
	public function render_column( $column, $order ) {
		if ( 'wpkt_tracking' !== $column || ! $order instanceof WC_Order ) {
			return;
		}

		$number = WPKT_Order::get_number( $order );

		if ( '' === $number ) {
			echo '<span class="wpkt-dash" aria-hidden="true">&ndash;</span>';
			return;
		}

		$url = WPKT_Order::get_tracking_url( $order );

		if ( '' !== $url ) {
			printf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( $url ),
				esc_html( $number )
			);
		} else {
			echo esc_html( $number );
		}

		printf(
			'<br /><small>%s</small>',
			esc_html( WPKT_Carriers::label( WPKT_Order::get_carrier( $order ) ) )
		);
	}

	/**
	 * Kolon icerigi (klasik gonderi tablosu).
	 *
	 * @param string $column   Kolon anahtari.
	 * @param int    $order_id Siparis kimligi.
	 */
	public function render_column_legacy( $column, $order_id ) {
		if ( 'wpkt_tracking' !== $column ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( $order instanceof WC_Order ) {
			$this->render_column( $column, $order );
		}
	}

	/**
	 * Siparis islemleri listesine "yeniden gonder" ekler.
	 *
	 * @param array<string,string> $actions Islemler.
	 * @return array<string,string>
	 */
	public function add_order_action( $actions ) {
		$actions['wpkt_resend_shipped_email'] = __( 'Kargo bilgilendirme e-postasini yeniden gonder', 'wp-kargo-takip' );

		return $actions;
	}

	/**
	 * Bildirimi elle yeniden gonderir.
	 *
	 * @param WC_Order $order Siparis.
	 */
	public function resend_email( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( '' === WPKT_Order::get_number( $order ) ) {
			$order->add_order_note( __( 'Kargo bilgilendirmesi gonderilemedi: takip numarasi bos.', 'wp-kargo-takip' ) );
			return;
		}

		WPKT_Emails::maybe_notify( $order, true );
	}

	/**
	 * Kutu stilleri.
	 *
	 * @param string $hook Ekran kancasi.
	 */
	public function enqueue_styles( $hook ) {
		$screens = array( 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' );

		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}

		wp_enqueue_style( 'wpkt-admin', WPKT_URL . 'assets/admin.css', array(), WPKT_VERSION );
	}
}

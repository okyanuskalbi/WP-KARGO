<?php
/**
 * Yonetici arayuzu: siparis kutusu, kaydetme, liste kolonu, hizli ekleme,
 * e-posta on izleme.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin tarafi.
 */
class WPKT_Admin {

	const NONCE          = 'wpkt_save_tracking';
	const AJAX_NONCE     = 'wpkt_quick_tracking';
	const PREVIEW_ACTION = 'wpkt_preview_email';

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

		// Siparis listesinden tek tikla takip no girme (yalniz "hazirlaniyor").
		add_action( 'wp_ajax_wpkt_quick_save_tracking', array( $this, 'ajax_quick_save' ) );

		// E-posta on izleme: yeni sekmede tam govdeyi gosterir, mail gondermez.
		add_action( 'admin_post_' . self::PREVIEW_ACTION, array( $this, 'preview_email' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
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

		$number  = WPKT_Order::get_number( $order );
		$carrier = WPKT_Order::get_carrier( $order );
		$date    = WPKT_Order::get_shipped_date( $order );
		$url     = WPKT_Order::get_tracking_url( $order );

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
					<?php $this->render_mail_status_line( $order ); ?>
					<a href="<?php echo esc_url( $this->get_preview_url( $order ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'E-postayi onizle', 'wp-kargo-takip' ); ?>
					</a>
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
	 * Mail gonderim durumunu (basari/hata + zaman) yazdirir.
	 *
	 * "Gonderildi" burada yalnizca posta sunucusuna teslim edildigi anlamina
	 * gelir; musterinin gelen kutusuna ulastigina dair gercek "delivered"
	 * onayi icin harici bir ESP webhook'u gerekir (bu eklentide yok) — o
	 * yuzden buradaki metin bilerek "gonderildi" diyor, "teslim oldu" demiyor.
	 *
	 * @param WC_Order $order Siparis.
	 */
	private function render_mail_status_line( WC_Order $order ) {
		$status = WPKT_Order::get_mail_status( $order );

		if ( '' === $status ) {
			printf(
				'<span class="wpkt-status wpkt-status--pending">%s</span><br />',
				esc_html__( 'Musteriye henuz bilgilendirme gonderilmedi.', 'wp-kargo-takip' )
			);
			return;
		}

		$sent_at = WPKT_Order::get_mail_sent_at( $order );
		$when    = $sent_at ? wp_date( 'd.m.Y H:i', $sent_at ) : '';

		if ( 'sent' === $status ) {
			printf(
				'<span class="wpkt-status wpkt-status--sent">%s</span><br />',
				'' !== $when
					? esc_html(
						sprintf(
							/* translators: %s: gonderim tarihi/saati. */
							__( 'Posta sunucusuna teslim edildi (%s).', 'wp-kargo-takip' ),
							$when
						)
					)
					: esc_html__( 'Posta sunucusuna teslim edildi.', 'wp-kargo-takip' )
			);
			return;
		}

		$error = WPKT_Order::get_mail_error( $order );
		printf(
			'<span class="wpkt-status wpkt-status--failed">%s</span><br />',
			'' !== $error
				? esc_html(
					sprintf(
						/* translators: %s: sunucu hata mesaji. */
						__( 'Gonderim basarisiz: %s', 'wp-kargo-takip' ),
						$error
					)
				)
				: esc_html__( 'Gonderim basarisiz oldu.', 'wp-kargo-takip' )
		);
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

		$this->process_tracking_update( $order, $number, $carrier, $date );
	}

	/**
	 * Takip verisini kaydedip gerekirse durumu degistirir ve maili tetikler.
	 *
	 * Siparis kutusu (save()) ve liste hizli ekleme (ajax_quick_save()) ayni
	 * akisi kullanir; ikisinin de "kaydet -> durum degistir -> mail gonder"
	 * sirasi ve kurallari birbirinden sapmasin diye tek yerde tutulur.
	 *
	 * @param WC_Order $order   Siparis.
	 * @param string   $number  Takip numarasi.
	 * @param string   $carrier Firma anahtari.
	 * @param string   $date    Kargoya verilis tarihi (bos olabilir).
	 * @return bool Numara degisti mi.
	 */
	private function process_tracking_update( WC_Order $order, $number, $carrier, $date ) {
		$changed = WPKT_Order::save( $order, $number, $carrier, $date );

		if ( ! $changed || '' === WPKT_Order::get_number( $order ) ) {
			return $changed;
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

		return $changed;
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
			/*
			 * Yalnizca "hazirlaniyor" siparislerde hizli ekleme formu gosterilir:
			 * bu istegin tam olarak karsiladigi senaryo budur. Diger durumlarda
			 * (henuz odenmemis, iptal, iade vb.) tek tikla "kargoya verildi"ye
			 * gecmek yanlis islem olur; oradaki siparisler yine siparis
			 * ekranindaki kutudan islenir.
			 */
			if ( $order->has_status( 'processing' ) && current_user_can( 'edit_shop_orders' ) ) {
				$this->render_quick_form( $order );
			} else {
				echo '<span class="wpkt-dash" aria-hidden="true">&ndash;</span>';
			}
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

		$this->render_mail_status_badge( $order );
	}

	/**
	 * Liste kolonunda kisa mail durumu + on izleme linki.
	 *
	 * @param WC_Order $order Siparis.
	 */
	private function render_mail_status_badge( WC_Order $order ) {
		$status = WPKT_Order::get_mail_status( $order );

		if ( '' !== $status ) {
			printf(
				'<br /><small class="wpkt-mail-status wpkt-mail-status--%1$s">%2$s</small>',
				esc_attr( $status ),
				'sent' === $status
					? esc_html__( 'Mail gonderildi', 'wp-kargo-takip' )
					: esc_html__( 'Mail gonderilemedi', 'wp-kargo-takip' )
			);
		}

		printf(
			' <a href="%1$s" target="_blank" rel="noopener noreferrer" class="wpkt-preview-link">%2$s</a>',
			esc_url( $this->get_preview_url( $order ) ),
			esc_html__( 'on izle', 'wp-kargo-takip' )
		);
	}

	/**
	 * Siparis listesinde takip no bulunmayan "hazirlaniyor" siparisler icin
	 * tek alanli hizli ekleme formu (AJAX ile kaydedilir).
	 *
	 * @param WC_Order $order Siparis.
	 */
	private function render_quick_form( WC_Order $order ) {
		?>
		<form class="wpkt-quick-form" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
			<input
				type="text"
				class="wpkt-quick-input"
				autocomplete="off"
				placeholder="<?php esc_attr_e( 'Takip no', 'wp-kargo-takip' ); ?>"
				aria-label="<?php esc_attr_e( 'Kargo takip numarasi', 'wp-kargo-takip' ); ?>"
			/>
			<button type="submit" class="button button-small">
				<?php esc_html_e( 'Ekle', 'wp-kargo-takip' ); ?>
			</button>
			<span class="wpkt-quick-error" role="alert" hidden></span>
		</form>
		<?php
	}

	/**
	 * Siparis listesindeki hizli ekleme formunun AJAX ucu.
	 *
	 * Kaydetme + durum degisimi + mail gonderimi tam olarak process_tracking_update()
	 * uzerinden yurur — siparis kutusundan girmekle tek fark tetikleme yeri.
	 */
	public function ajax_quick_save() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::AJAX_NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Oturum suresi doldu, sayfayi yenileyin.', 'wp-kargo-takip' ) ), 403 );
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Bu islem icin yetkiniz yok.', 'wp-kargo-takip' ) ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order instanceof WC_Order ) {
			wp_send_json_error( array( 'message' => __( 'Siparis bulunamadi.', 'wp-kargo-takip' ) ), 404 );
		}

		$number = isset( $_POST['tracking_number'] ) ? wc_clean( wp_unslash( $_POST['tracking_number'] ) ) : '';

		if ( '' === $number ) {
			wp_send_json_error( array( 'message' => __( 'Takip numarasi bos olamaz.', 'wp-kargo-takip' ) ) );
		}

		$this->process_tracking_update( $order, $number, WPKT_Carriers::DEFAULT_CARRIER, '' );

		// Guncel siparisi tekrar cek: process_tracking_update() sirasinda
		// durum/meta degisti, kolonu bayat veriyle degil taze halinden ciz.
		$order = wc_get_order( $order_id );

		ob_start();
		$this->render_column( 'wpkt_tracking', $order );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Klasik gonderi tablosu (HPOS kapali) icin kolon icerigi.
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
	 * "Kargoya verildi" mailinin on izleme adresi.
	 *
	 * @param WC_Order $order Siparis.
	 * @return string
	 */
	private function get_preview_url( WC_Order $order ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'   => self::PREVIEW_ACTION,
					'order_id' => $order->get_id(),
				),
				admin_url( 'admin-post.php' )
			),
			self::PREVIEW_ACTION . '_' . $order->get_id()
		);
	}

	/**
	 * Musteriye gidecek govdenin aynisini yeni sekmede gosterir. Mail
	 * gondermez, yalnizca get_preview_url() uzerinden nonce'lu erisilir.
	 */
	public function preview_email() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $order_id || ! wp_verify_nonce( $nonce, self::PREVIEW_ACTION . '_' . $order_id ) ) {
			wp_die( esc_html__( 'Gecersiz veya suresi dolmus istek.', 'wp-kargo-takip' ), '', array( 'response' => 400 ) );
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'Bu islem icin yetkiniz yok.', 'wp-kargo-takip' ), '', array( 'response' => 403 ) );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			wp_die( esc_html__( 'Siparis bulunamadi.', 'wp-kargo-takip' ), '', array( 'response' => 404 ) );
		}

		require_once WPKT_DIR . 'includes/class-wpkt-email-shipped.php';
		$email = new WPKT_Email_Shipped();

		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		// Cikti WC_Email sablonunun ureteceginin aynisi; sablon kendi ici
		// zaten kacisli (esc_html/esc_url) — burada tekrar kacislamak
		// govdeyi bozar.
		echo $email->preview_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Kutu, liste ve hizli ekleme icin CSS/JS.
	 *
	 * @param string $hook Ekran kancasi (guvenilmez; asil kontrol screen id).
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screens = array( 'shop_order', 'edit-shop_order' );

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screens[] = 'woocommerce_page_wc-orders';
		}

		if ( ! in_array( $screen->id, $screens, true ) ) {
			return;
		}

		wp_enqueue_style( 'wpkt-admin', WPKT_URL . 'assets/admin.css', array(), WPKT_VERSION );

		wp_enqueue_script( 'wpkt-admin', WPKT_URL . 'assets/admin.js', array(), WPKT_VERSION, true );
		wp_localize_script(
			'wpkt-admin',
			'WPKT_Admin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::AJAX_NONCE ),
				'strings' => array(
					'network' => __( 'Baglanti hatasi, tekrar deneyin.', 'wp-kargo-takip' ),
				),
			)
		);
	}
}

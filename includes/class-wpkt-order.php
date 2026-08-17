<?php
/**
 * Siparis uzerindeki kargo verisini okuma/yazma.
 *
 * Tek giris noktasi: meta anahtarlari yalnizca burada gecer, boylece
 * HPOS/eski tablo ayrimi da tek yerde kalir (WC_Order API ikisini de kapsar).
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kargo verisi erisim katmani.
 */
class WPKT_Order {

	const META_NUMBER  = '_wpkt_tracking_number';
	const META_CARRIER = '_wpkt_carrier';
	const META_DATE    = '_wpkt_shipped_date';
	const META_NOTIFY  = '_wpkt_notified_number';

	/**
	 * Takip numarasi.
	 *
	 * @param WC_Order $order Siparis.
	 * @return string
	 */
	public static function get_number( $order ) {
		return (string) $order->get_meta( self::META_NUMBER );
	}

	/**
	 * Kargo firmasi anahtari.
	 *
	 * @param WC_Order $order Siparis.
	 * @return string
	 */
	public static function get_carrier( $order ) {
		$carrier = (string) $order->get_meta( self::META_CARRIER );

		if ( '' === $carrier || ! WPKT_Carriers::exists( $carrier ) ) {
			$carrier = WPKT_Carriers::DEFAULT_CARRIER;
		}

		return $carrier;
	}

	/**
	 * Kargoya verilis tarihi (Y-m-d).
	 *
	 * @param WC_Order $order Siparis.
	 * @return string
	 */
	public static function get_shipped_date( $order ) {
		return (string) $order->get_meta( self::META_DATE );
	}

	/**
	 * Takip linki.
	 *
	 * @param WC_Order $order Siparis.
	 * @return string
	 */
	public static function get_tracking_url( $order ) {
		return WPKT_Carriers::tracking_url( self::get_carrier( $order ), self::get_number( $order ) );
	}

	/**
	 * Kargo verisini kaydeder.
	 *
	 * @param WC_Order $order   Siparis.
	 * @param string   $number  Takip numarasi.
	 * @param string   $carrier Firma anahtari.
	 * @param string   $date    Kargoya verilis tarihi (Y-m-d, bos olabilir).
	 * @return bool Numara degisti mi.
	 */
	public static function save( $order, $number, $carrier, $date = '' ) {
		$number  = self::sanitize_number( $number );
		$carrier = WPKT_Carriers::exists( $carrier ) ? $carrier : WPKT_Carriers::DEFAULT_CARRIER;
		$old     = self::get_number( $order );

		$order->update_meta_data( self::META_NUMBER, $number );
		$order->update_meta_data( self::META_CARRIER, $carrier );

		if ( '' !== $number && '' === $date ) {
			// Tarih girilmediyse once mevcut deger korunur, o da yoksa bugun
			// (sitenin saat dilimine gore) yazilir.
			$date = self::get_shipped_date( $order );

			if ( '' === $date ) {
				$date = wp_date( 'Y-m-d' );
			}
		}

		$order->update_meta_data( self::META_DATE, '' === $number ? '' : $date );

		/*
		 * Numara bosaltilinca bildirim damgasi da silinir. Aksi halde admin
		 * numarayi silip AYNI numarayi tekrar girdiginde damga hala o numarayi
		 * gosterir ve musteriye hic mail gitmez — yanlis numara girip duzelten
		 * kullanicinin basina gelen sessiz kayip tam olarak budur.
		 */
		if ( '' === $number ) {
			$order->update_meta_data( self::META_NOTIFY, '' );
		}

		$changed = $number !== $old;

		if ( $changed ) {
			$order->add_order_note(
				'' === $number
					/* translators: kargo takip numarasi silindi. */
					? __( 'Kargo takip numarasi kaldirildi.', 'wp-kargo-takip' )
					: sprintf(
						/* translators: 1: kargo firmasi, 2: takip numarasi. */
						__( 'Kargo bilgisi kaydedildi: %1$s — %2$s', 'wp-kargo-takip' ),
						WPKT_Carriers::label( $carrier ),
						$number
					)
			);
		}

		$order->save();

		return $changed;
	}

	/**
	 * Takip numarasini temizler.
	 *
	 * Yurtici numaralari rakamdan olusur ama kullanicilar bosluk/tire ile
	 * yapistirir; harf iceren gonderi kodlarini da bozmamak icin yalnizca
	 * alfanumerik olmayan karakterler atilir.
	 *
	 * @param string $number Girdi.
	 * @return string
	 */
	public static function sanitize_number( $number ) {
		$number = wc_clean( wp_unslash( (string) $number ) );
		$number = preg_replace( '/[^A-Za-z0-9]/', '', $number );

		return strtoupper( (string) $number );
	}

	/**
	 * Bu numara icin musteriye bildirim gonderildi mi.
	 *
	 * @param WC_Order $order Siparis.
	 * @return bool
	 */
	public static function is_notified( $order ) {
		$notified = (string) $order->get_meta( self::META_NOTIFY );

		return '' !== $notified && $notified === self::get_number( $order );
	}

	/**
	 * Bildirimi isaretler (ayni numara icin ikinci kez mail gitmesin).
	 *
	 * @param WC_Order $order Siparis.
	 */
	public static function mark_notified( $order ) {
		$order->update_meta_data( self::META_NOTIFY, self::get_number( $order ) );
		$order->save();
	}
}

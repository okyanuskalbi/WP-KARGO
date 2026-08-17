<?php
/**
 * Kargo firmalari kaydi.
 *
 * 1. faz yalnizca Yurtici Kargo icerir. Yeni firma eklemek icin buradaki
 * diziye bir satir eklemek yeterlidir; takip linki {no} yer tutucusuyla
 * kurulur ve diger tum modüller bu kayittan beslenir.
 *
 * @package WPKargoTakip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kargo firmasi kayit defteri.
 */
class WPKT_Carriers {

	/**
	 * Varsayilan firma anahtari.
	 */
	const DEFAULT_CARRIER = 'yurtici';

	/**
	 * Tanimli firmalar.
	 *
	 * @return array<string,array{label:string,url:string,phone:string}>
	 */
	public static function all() {
		$carriers = array(
			'yurtici' => array(
				'label' => __( 'Yurtici Kargo', 'wp-kargo-takip' ),
				/*
				 * Yurtici'nin genel takip formu kargo takip numarasini (veya
				 * gonderi kodunu) code parametresiyle alir. Sorgu dizesi
				 * degisirse ayarlardan ozel sablon girilebilir.
				 */
				'url'   => 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code={no}',
				'phone' => '444 99 99',
			),
		);

		/**
		 * Kargo firmasi listesini degistirir.
		 *
		 * @param array $carriers Firmalar.
		 */
		return apply_filters( 'wpkt_carriers', $carriers );
	}

	/**
	 * Select alanlari icin anahtar => etiket listesi.
	 *
	 * @return array<string,string>
	 */
	public static function options() {
		$options = array();

		foreach ( self::all() as $key => $carrier ) {
			$options[ $key ] = $carrier['label'];
		}

		return $options;
	}

	/**
	 * Firma tanimli mi.
	 *
	 * @param string $key Firma anahtari.
	 * @return bool
	 */
	public static function exists( $key ) {
		return array_key_exists( $key, self::all() );
	}

	/**
	 * Firma etiketi.
	 *
	 * @param string $key Firma anahtari.
	 * @return string
	 */
	public static function label( $key ) {
		$carriers = self::all();

		return isset( $carriers[ $key ] ) ? $carriers[ $key ]['label'] : $key;
	}

	/**
	 * Takip URL'i uretir.
	 *
	 * @param string $key    Firma anahtari.
	 * @param string $number Takip numarasi.
	 * @return string Bos string: sablon yoksa link gosterilmez.
	 */
	public static function tracking_url( $key, $number ) {
		$number = trim( (string) $number );

		if ( '' === $number ) {
			return '';
		}

		$carriers = self::all();
		$template = isset( $carriers[ $key ]['url'] ) ? $carriers[ $key ]['url'] : '';

		// Ayarlardaki ozel sablon her zaman oncelikli (firma URL'ini degistirirse
		// kullanici surum beklemeden duzeltebilsin).
		$custom = trim( (string) get_option( 'wpkt_tracking_url_' . $key, '' ) );

		if ( '' !== $custom ) {
			$template = $custom;
		}

		if ( '' === $template ) {
			return '';
		}

		$url = str_replace( '{no}', rawurlencode( $number ), $template );

		return esc_url_raw( $url );
	}
}

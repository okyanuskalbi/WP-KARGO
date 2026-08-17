=== WP Kargo Takip ===
Contributors: okyanuskalbi
Tags: woocommerce, kargo, shipment tracking, yurtici kargo, turkiye
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce siparislerine kargo takip numarasi girin; musteriye "Kargoya Verildi"
e-postasi takip numarasi ve takip linkiyle otomatik gitsin. 1. faz: Yurtici Kargo.

== Description ==

Yonetici siparis ekranindaki "Kargo Takip" kutusuna takip numarasini girer:

* Siparis durumu "Kargoya Verildi" olur (kapatilabilir)
* Musteriye takip numarasi ve takip butonu iceren e-posta gider
* Takip bilgisi siparis detay sayfasinda ve diger WooCommerce e-postalarinda gorunur
* Siparis listesine "Kargo Takip" kolonu eklenir
* Ayni numara icin ikinci kez e-posta gonderilmez; elle yeniden gonderme secenegi vardir

HPOS (yeni siparis tablolari) uyumludur. Eklenti GitHub release'lerinden
kendini gunceller; lisans anahtari veya harici servis gerekmez.

== Installation ==

1. Zip dosyasini Eklentiler > Yeni Ekle > Eklenti Yukle ile yukleyin.
2. Etkinlestirin.
3. WooCommerce > Ayarlar > Kargo > Kargo Takip bolumunden ayarlari gozden gecirin.

== Frequently Asked Questions ==

= E-posta metnini nasil degistiririm? =

WooCommerce > Ayarlar > E-postalar > "Kargoya verildi".

= Takip linki calismiyor =

Kargo firmasi sorgu adresini degistirmis olabilir. WooCommerce > Ayarlar >
Kargo > Kargo Takip altindaki adres alanina `{no}` yer tutuculu yeni adresi
yazabilirsiniz.

= Baska kargo firmalari? =

1. faz yalnizca Yurtici Kargo icerir. Aras, MNG, PTT ve Suratkargo 2. fazda gelir.

== Changelog ==

= 1.0.0 =
* Ilk surum: Yurtici Kargo, "Kargoya Verildi" siparis durumu, takip numarali
  musteri e-postasi, siparis listesi kolonu, GitHub uzerinden otomatik guncelleme.

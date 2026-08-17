=== WP Kargo Takip ===
Contributors: okyanuskalbi
Tags: woocommerce, kargo, shipment tracking, yurtici kargo, turkiye
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce siparislerine kargo takip numarasi girin; musteriye "Kargoya Verildi"
e-postasi takip numarasi ve takip linkiyle otomatik gitsin. Yurtici, Aras, MNG,
PTT ve Surat Kargo destekli.

== Description ==

Yonetici siparis ekranindaki "Kargo Takip" kutusuna takip numarasini girer:

* Bes kargo firmasi hazir tanimli: Yurtici, Aras, MNG, PTT, Surat
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
Kargo > Kargo Takip > Takip adresleri altinda her firma icin ayri alan var;
`{no}` yer tutuculu yeni adresi oraya yazabilirsiniz.

= Baska kargo firmalari? =

Yurtici, Aras, MNG, PTT ve Surat hazir gelir. Diger firmalar `wpkt_carriers`
filtresiyle kod degistirmeden eklenebilir; eklenen firma ayarlar sayfasinda
da otomatik gorunur.

= Musterinin maili gercekten teslim oldu mu? =

Eklenti mailin posta sunucusuna basariyla iletildigini gosterir ("gonderildi/
gonderilemedi"). Musterinin gelen kutusuna ulastigina dair kesin onay
(gercek "delivered" durumu) icin Postmark/SendGrid/Mailgun gibi bir ESP'nin
teslim webhook'u gerekir; bu ucretsiz eklenti oyle bir harici servise
baglanmaz.

== Changelog ==

= 1.2.0 =
* Siparis listesinde "hazirlaniyor" durumundaki siparislere tek alanli hizli
  takip no ekleme (AJAX); kaydedildigi an "Kargoya Verildi"ye gecer ve mail gider.
* E-posta gonderim durumu: basarili/basarisiz + zaman + hata mesaji, siparis
  kutusunda ve liste kolonunda gorunur. (Not: posta sunucusuna teslim
  onayidir, musteri gelen kutusuna ulastigina dair onay degildir.)
* "Kargoya verildi" mailinin tam govdesini yeni sekmede on izleme (mail
  gondermeden).
* Yeni hizli-ekleme ve on izleme uclarindaki yetki kontrolleri de siparis
  bazina (edit_shop_order) hizalandi.

= 1.1.0 =
* Aras, MNG, PTT ve Surat Kargo destegi eklendi (takip adresi + santral bilgisiyle).
* Ozel takip adresi artik her firma icin ayri ayar alani; alanlar firma kayitlarindan
  otomatik uretiliyor.
* Yetki kontrolleri siparis bazina indirildi (kutu kaydi ve bildirimi yeniden gonderme).
* Takip numarasi temizlemesindeki cift unslash duzeltildi; ters bolu iceren kodlar
  artik bozulmuyor.

= 1.0.0 =
* Ilk surum: Yurtici Kargo, "Kargoya Verildi" siparis durumu, takip numarali
  musteri e-postasi, siparis listesi kolonu, GitHub uzerinden otomatik guncelleme.

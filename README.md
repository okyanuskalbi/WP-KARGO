# WP Kargo Takip

WooCommerce siparislerine kargo takip numarasi girin — siparis **"Kargoya Verildi"**
durumuna gecsin ve musteriye takip numarasi + takip linki iceren e-posta otomatik
gitsin. Ucretsiz, lisans anahtari yok, GitHub release'lerinden kendini gunceller.

**Desteklenen firmalar:** Yurtici, Aras, MNG, PTT ve Surat Kargo.

---

## Kurulum

1. [Releases](https://github.com/okyanuskalbi/WP-KARGO/releases) sayfasindan
   son `wp-kargo-takip-x.y.z.zip` dosyasini indirin
2. WordPress > Eklentiler > Yeni Ekle > **Eklenti Yukle** > zip'i secin
3. Etkinlestirin. WooCommerce 7.0+ gerekir (HPOS uyumludur).

Bundan sonraki surumler icin indirme yapmaniza gerek yok: yeni release
yayinlandiginda **Eklentiler** ekraninda guncelleme uyarisi cikar, tek tikla kurulur.

## Kullanim

1. WooCommerce > Siparisler > ilgili siparisi acin
2. Sagdaki **Kargo Takip** kutusuna kargo firmasini ve takip numarasini yazin
3. **Guncelle**'ye basin

Kaydettiginiz anda:

- Siparis durumu **Kargoya Verildi** olur (ayarlardan kapatilabilir)
- Musteriye takip numarasi ve "Kargomu takip et" butonu iceren e-posta gider
- Siparis notuna kayit dusulur

Ayni takip numarasi icin ikinci kez e-posta gonderilmez. Elle tekrar gondermek
icin siparis ekranindaki **Siparis islemleri > Kargo bilgilendirme e-postasini
yeniden gonder** secenegini kullanin.

## Ayarlar

**WooCommerce > Ayarlar > Kargo > Kargo Takip**

| Ayar | Varsayilan | Aciklama |
| --- | --- | --- |
| Durumu otomatik degistir | Acik | Numara kaydedilince siparis "Kargoya Verildi" olur |
| Takip bilgisini diger e-postalara ekle | Acik | Fatura/siparis e-postalarinda da takip bilgisi gorunur |
| Takip adresleri (firma basina) | Bos | Her firma icin ozel sorgu adresi. `{no}` takip numarasiyla degistirilir |

E-postanin **konusu, basligi ve metni**: WooCommerce > Ayarlar > E-postalar >
**Kargoya verildi**.

Sablonu tema icinde ozelleştirmek icin dosyayi kopyalayin:

```
wp-kargo-takip/templates/emails/wpkt-shipped.php
  -> yourtheme/woocommerce/emails/wpkt-shipped.php
```

## Otomatik guncelleme nasil calisiyor

Eklenti WordPress'in kendi guncelleme akisina baglanir (`plugins_api` +
`pre_set_site_transient_update_plugins`) ve bu deponun GitHub Releases
verisini okur. Harici servis, lisans anahtari ve token yok — depo genel
(public) oldugu icin API cagrisi kimlik gerektirmez.

Release verisi 6 saat onbelleklenir; hemen gormek icin eklenti satirindaki
**Guncellemeleri denetle** baglantisini kullanin.

### Yeni surum yayinlama

1. Surumu **uc yerde** birlikte yukseltin:
   - `wp-kargo-takip.php` > `Version:` basligi
   - `wp-kargo-takip.php` > `WPKT_VERSION` sabiti
   - `readme.txt` > `Stable tag` + Changelog girdisi
2. Commit + push
3. Etiketi atin:

```bash
git tag v1.1.0
git push origin v1.1.0
```

CI (`.github/workflows/release.yml`) PHP lint calistirir, **uc surum degeri
etiketle ayni degilse yayini durdurur** (ayrisirsa WordPress guncellemeyi
kurar ama surum eski kalir, uyari sonsuz tekrarlanir), sonra
`wp-kargo-takip-x.y.z.zip` uretip release'e ekler.

Depoyu catallarsaniz `wp-config.php` icine su iki satiri koymak yeterli —
kodda degisiklik gerekmez:

```php
define( 'WPKT_GH_REPO', 'kullanici/depo' );
define( 'WPKT_GH_TAG_PREFIX', 'v' );
```

## Yeni kargo firmasi ekleme

`includes/class-wpkt-carriers.php` icindeki diziye bir satir eklemek yeterli —
admin kutusu, liste kolonu, e-posta, takip linki **ve ayarlar sayfasindaki
ozel adres alani** ayni kayittan beslenir:

```php
'hepsijet' => array(
	'label' => 'Hepsijet',
	'url'   => 'https://www.hepsijet.com/gonderi-takibi/{no}',
	'phone' => '0850 255 05 25',
),
```

Kod degistirmeden eklemek icin `wpkt_carriers` filtresi kullanilabilir.

## Kancalar

| Kanca | Tur | Aciklama |
| --- | --- | --- |
| `wpkt_carriers` | filter | Kargo firmasi listesi |
| `wpkt_should_send_shipped_email` | filter | Bildirim gonderilsin mi (`bool, WC_Order, bool $force`) |
| `wpkt_order_shipped_notification` | action | E-posta tetigi (`int $order_id, WC_Order $order`) |

## Dosya yapisi

```
wp-kargo-takip.php              Eklenti basligi, sabitler, onyukleme
includes/
  class-wpkt-plugin.php         Cekirdek: bagimlilik kontrolu, modul baglama
  class-wpkt-carriers.php       Kargo firmalari kaydi + takip URL uretimi
  class-wpkt-order.php          Siparis uzerindeki kargo verisi (tek giris noktasi)
  class-wpkt-statuses.php       "Kargoya Verildi" siparis durumu
  class-wpkt-admin.php          Siparis kutusu, kaydetme, liste kolonu
  class-wpkt-emails.php         E-posta akisi, musteriye gorunen takip bilgisi
  class-wpkt-email-shipped.php  WC_Email: "Kargoya verildi"
  class-wpkt-settings.php       WooCommerce ayar bolumu
  class-wpkt-updater.php        GitHub Releases guncelleyicisi
templates/emails/               E-posta sablonlari (HTML + duz metin)
assets/admin.css                Siparis ekrani kutusu stilleri
```

## Yol haritasi

- **1. faz:** Yurtici Kargo, elle takip numarasi, otomatik durum + e-posta (1.0.0)
- **2. faz:** Aras / MNG / PTT / Surat destegi (1.1.0) — sirada: toplu (bulk) numara girisi
- 3. faz: Kargo firmasi API entegrasyonu ile otomatik durum sorgulama, SMS

## Lisans

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)

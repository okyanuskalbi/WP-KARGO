# Changelog

Tum onemli degisiklikler burada. Surum numaralari
[Semantic Versioning](https://semver.org/lang/tr/) izler.

## [1.0.0] — 2026-08-17

Ilk surum. **1. faz: Yurtici Kargo.**

### Eklendi

- Siparis ekraninda **Kargo Takip** kutusu: kargo firmasi, takip numarasi,
  kargoya verilis tarihi. HPOS (yeni siparis tablolari) uyumlu.
- **"Kargoya Verildi"** ozel siparis durumu (`wc-kargoda`); durum listesinde
  "Tamamlandi"nin oncesine yerlesir, raporlarda satis olarak sayilir.
- Takip numarasi kaydedilince siparisi otomatik bu duruma alma (kapatilabilir).
- **Musteri e-postasi:** takip numarasi + "Kargomu takip et" butonu. WooCommerce
  e-posta altyapisina bagli — konu/baslik/metin panelden duzenlenir, sablon tema
  icine kopyalanarak ozelleştirilebilir (HTML + duz metin).
- Siparis listesine **Kargo Takip** kolonu (tiklanir takip linkiyle).
- Siparis detay sayfasinda ve diger WooCommerce e-postalarinda takip bilgisi.
- **Cift mail korumasi:** ayni takip numarasi icin ikinci kez gonderilmez
  (`_wpkt_notified_number` damgasi). Elle gondermek icin "Siparis islemleri >
  Kargo bilgilendirme e-postasini yeniden gonder".
- Ayarlar: WooCommerce > Ayarlar > Kargo > Kargo Takip (otomatik durum,
  e-postalarda gosterim, ozel takip adresi sablonu).
- **GitHub Releases uzerinden otomatik guncelleme** — lisans anahtari, harici
  servis veya token gerektirmez. 6 saat onbellek + elle "Guncellemeleri denetle".
- CI: PHP lint, surum tutarliligi kontrolu, zip uretimi ve release yayini.

### Notlar

- Kaydetme adimi nonce yoksa **sessizce cikar**; aksi halde toplu islem ve REST
  istekleri mevcut takip numarasini silerdi.
- Surum yukseltirken `Version` basligi, `WPKT_VERSION` sabiti ve `readme.txt`
  `Stable tag` birlikte degismeli — CI ayrisma halinde yayini durdurur, cunku
  ayrisirsa WordPress guncelleme uyarisini sonsuz tekrarlar.

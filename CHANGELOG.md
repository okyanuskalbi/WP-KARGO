# Changelog

Tum onemli degisiklikler burada. Surum numaralari
[Semantic Versioning](https://semver.org/lang/tr/) izler.

## [1.2.0] — 2026-08-17

### Eklendi

- **Siparis listesinden tek tikla ekleme:** "hazirlaniyor" durumundaki
  siparislerde, takip numarasi yoksa kolon icinde tek alanli bir form
  gorunur. Numarayi girip **Ekle**'ye basmak siparis ekranini acmadan
  kaydeder; kayit aninda mevcut ayarlara gore siparis **Kargoya Verildi**ye
  gecer ve musteriye mail gider (AJAX, `wp_ajax_wpkt_quick_save_tracking`).
  Diger durumlardaki siparislerde (odenmemis, iptal, iade vb.) bu form
  kasten gosterilmez — tek tikla yanlis siparisi kargoya vermemek icin.
- **Gonderim durumu izleme:** mail `wp_mail()` uzerinden posta sunucusuna
  basariyla teslim edildi mi, ne zaman, basarisizsa hangi hatayla — siparis
  kutusunda ve liste kolonunda gorunur (`_wpkt_mail_status`,
  `_wpkt_mail_sent_at`, `_wpkt_mail_error`).
  **Sinir:** bu, musterinin gelen kutusuna ulastigina dair kesin onay
  DEGILDIR ("delivered" degil, "sunucuya teslim edildi"). Gercek teslim
  onayi icin bir ESP'nin (Postmark/SendGrid/Mailgun) webhook'una baglanmak
  gerekir; bu ucretsiz eklenti harici servise baglanmiyor, arayuz de bunu
  acikca "gonderildi" diye etiketliyor, "teslim oldu" demiyor.
- **E-posta on izleme:** siparis kutusunda ve liste kolonunda "on izle"
  linki, musteriye tam olarak gidecek HTML govdeyi (CSS inline'lama dahil)
  yeni sekmede acar. Mail gondermez (`admin_post_wpkt_preview_email`,
  nonce'lu).

### Degisti

- Basarisiz gonderimde `_wpkt_notified_number` damgasi artik kilitlenmiyor:
  yalnizca basarili gonderimde isaretlenir, boylece gecici bir SMTP
  hatasindan sonra sonraki kayitta otomatik deneme sessizce engellenmiyor.
- Yeni hizli-ekleme ve on izleme uclarindaki yetki kontrolleri de 1.1.0'daki
  siparis bazli (`edit_shop_order`) kurala hizalandi.

### Birlestirme notu

Bu surum, GitHub'a bagimsiz olarak (bu depoyu buradan yoneten oturumun
disinda) pushlanmis **1.1.0 (coklu kargo firmasi)** ile ayni anda gelisti;
ikisi de o sirada "1.1.0" olarak numaralanmisti. Cakisma cozulurken bu
surum **1.2.0**'a kaydirildi, 1.1.0 asagida oldugu gibi korundu.

## [1.1.0] — 2026-08-17

**2. faz baslangici: coklu kargo firmasi.**

### Eklendi

- **4 yeni kargo firmasi:** Aras, MNG, PTT ve Surat Kargo; her biri takip
  adresi sablonu ve santral numarasiyla kayitli. Admin kutusu, liste kolonu
  ve musteri e-postasi ayni kayittan beslendigi icin ek is gerektirmez.
- **Firma bazli takip adresi ayarlari:** ozel sorgu adresi alani artik her
  firma icin ayri (WooCommerce > Ayarlar > Kargo > Kargo Takip > Takip
  adresleri). Alanlar kayit defterinden uretilir — `wpkt_carriers` filtresiyle
  eklenen firmalar ayarlar sayfasinda da otomatik gorunur. Mevcut
  `wpkt_tracking_url_yurtici` secenegi aynen korunur.

### Degisti / duzeltildi

- Yetki kontrolleri siparis bazina indirildi: kutu kaydi ve bildirimi yeniden
  gonderme artik genel `edit_shop_orders` yerine `edit_shop_order` meta cap
  uzerinden dogrulaniyor.
- `WPKT_Order::sanitize_number()` icindeki ikinci `wp_unslash` kaldirildi;
  ters bolu iceren takip kodlari sessizce bozulmuyordu.

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

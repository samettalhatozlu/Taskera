# Taskera - Yapay Zeka Destekli Proje Yönetimi Yazılımı

Taskera, 2025 yılında geliştirilmiş, yapay zeka destekli modern bir proje yönetim sistemidir.

## Özellikler

- Yapay Zeka destekli görev önceliklendirme
- Akıllı proje planlama ve zaman tahmini
- Otomatik kaynak optimizasyonu
- Gelişmiş görev takibi ve raporlama
- Kullanıcı yönetimi ve yetkilendirme
- Dosya yükleme ve paylaşımı
- Gerçek zamanlı işbirliği araçları

## Kurulum

1. Projeyi klonlayın:
```bash
git clone [repository-url]
cd Taskera
```

2. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

3. `.env.example` dosyasını `.env` olarak kopyalayın ve gerekli bilgileri düzenleyin:
```bash
cp .env.example .env
```

4. Veritabanını oluşturun:
- `ProjeYonet/config/database.sql` dosyasındaki SQL komutlarını çalıştırın
- `.env` dosyasındaki veritabanı bilgilerini güncelleyin

5. Web sunucusunu yapılandırın:
- Document root'u projenin kök dizinine ayarlayın
- PHP 8.1 veya üstü gereklidir

## Gereksinimler

- PHP 8.1+
- MySQL 8.0+
- Composer 2.0+
- Web sunucusu (Apache/Nginx)
- Node.js 18+ (frontend geliştirme için)

## Güvenlik

- Tüm hassas bilgiler `.env` dosyasında saklanır
- `.env` dosyası asla GitHub'a yüklenmemelidir
- CSRF koruması aktif
- XSS koruması aktif
- Rate limiting aktif
- İki faktörlü kimlik doğrulama desteği

## Geliştirme

Frontend geliştirmesi için:
```bash
npm install
npm run dev
```

## Lisans

Bu proje [MIT lisansı](LICENSE) altında lisanslanmıştır. 
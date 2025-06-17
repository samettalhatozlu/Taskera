Elbette! Aşağıda, tüm bölümleri mantıklı bir sırayla **Markdown biçiminde** ve `#`, `##`, `###` başlık etiketleri ile yapılandırılmış şekilde yeniden düzenledim. Bu dosyayı doğrudan `README.md` olarak kullanabilirsin:

---

````markdown
# Taskera - Yapay Zeka Destekli Proje Yönetim Uygulaması

**Taskera**, projelerinizi, görevlerinizi ve ekiplerinizi merkezi bir panelden kolayca yönetmenizi sağlayan yapay zeka destekli bir proje yönetim sistemidir. Kullanıcı dostu arayüzü, görev yönetimi, takvim oluşturucu ve AI asistan özellikleriyle kapsamlı bir deneyim sunar.

🔗 [GitHub Reposu](https://github.com/samettalhatozlu/Taskera)  
👤 Geliştirici: [Samet Talha Tozlu](https://linkedin.com/in/samettalhatozlu)

---

## 🚀 Özellikler

- 🧠 **Yapay Zeka Destekli Planlama**: Görev önerisi, önceliklendirme ve takvim fazlarının oluşturulması
- 📁 **Görev ve Proje Yönetimi**: Projeleri oluştur, görev ekle, kullanıcı ata, tamamlanma durumlarını takip et
- 📆 **AI Takvim Oluşturucu**: Yapay zekayla detaylı proje planları ve kilometre taşları oluştur
- 💬 **Yorum ve İşbirliği**: Her görev altında yorumlarla ekip içi iletişim sağla
- 🗂️ **Dosya Yükleme**: Görevlere özel belge yükleme ve paylaşma desteği
- 📊 **İstatistikler ve Grafikler**: Proje ilerleme yüzdeleri, görev sayıları ve durum analizleri
- 👥 **Kullanıcı Yönetimi**: Kullanıcılara görev atama ve yetkilendirme seçenekleri
- 🔐 **Güvenlik Katmanları**: CSRF/XSS korumaları, gizli bilgiler için .env desteği

---

## 🛠️ Kullanılan Teknolojiler

| Teknoloji             | Açıklama                                               |
|----------------------|--------------------------------------------------------|
| **PHP (Pure)**        | Backend geliştirme ve sunucu taraflı işlemler          |
| **MySQL**             | Veritabanı yönetimi                                    |
| **Bootstrap 5**       | Responsive ve modern arayüz tasarımı                   |
| **CSS / JavaScript**  | Etkileşimli kullanıcı deneyimi                         |
| **Composer**          | PHP bağımlılık yönetimi                                |
| **OpenRouter API**    | Yapay zeka hizmeti bağlantısı                          |
| **Meta Llama 4 Scout**| AI öneri sistemi (model: `meta-llama/llama-4-scout`)   |

---

## 🤖 Yapay Zeka Entegrasyonu

Taskera, OpenRouter platformu üzerinden Meta tarafından geliştirilen **Llama 4 Scout** modelini kullanır:

> **Model:** `meta-llama/llama-4-scout-17b-16e-instruct:free`  
> **Parametre:** 17B aktif, toplam 109B  
> **Uzun Bağlam:** 10 milyon token  
> **Çoklu Dil ve Modalite:** Destekli  
> **Kullanım Alanı:** Görev önerisi, faz planlama, teknoloji tavsiyesi  
> **Lisans:** Llama 4 Community License  
> **Yayın Tarihi:** 5 Nisan 2025  

AI, özellikle "AI Asistan" ve "Takvim Oluştur" modüllerinde aktif rol alır. Kullanıcıdan gelen veriye göre detaylı planlama ve öneriler sunar.

---

## 🔧 Kurulum Adımları

### 1. Projeyi klonlayın
```bash
git clone https://github.com/samettalhatozlu/Taskera
cd Taskera
````

### 2. Composer bağımlılıklarını yükleyin

```bash
composer install
```

### 3. Ortam dosyasını oluşturun

```bash
cp .env.example .env
```

`.env` içeriğini veritabanı ve AI API anahtarınızla doldurun.

### 4. Veritabanını kurun

* `ProjeYonet/config/database.sql` içeriğini çalıştırarak veritabanını oluşturun
* `.env` dosyasını yapılandırın

### 5. PHP yerel sunucusunu başlatın

```bash
php -S localhost:8000
```

---


---

## 📋 Sistem Gereksinimleri

* PHP >= 8.1
* MySQL >= 8.0
* Composer >= 2.0
* Apache veya Nginx sunucusu önerilir

---

## 🔐 Güvenlik Önlemleri

* `.env` dosyası hiçbir şekilde GitHub gibi sistemlerde paylaşılmamalıdır
* **CSRF** ve **XSS** saldırılarına karşı koruma mevcuttur
* API kullanımında **rate limiting** uygulanabilir
* Gelişmiş oturum ve kullanıcı doğrulama süreçleri uygulanabilir

---

## 📄 Lisans

Bu proje [MIT Lisansı](https://github.com/samettalhatozlu/Taskera/blob/main/LICENSE) kapsamında lisanslanmıştır.

---

## 📬 İletişim

**Geliştirici:** Samet Talha Tozlu
📎 LinkedIn: [linkedin.com/in/samettalhatozlu](https://linkedin.com/in/samettalhatozlu)

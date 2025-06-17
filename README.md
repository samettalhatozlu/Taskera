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
- 🔐 **Güvenlik Katmanları**: CSRF/XSS korumaları, gizli bilgiler için `.env` desteği

---

## 🛠️ Kullanılan Teknolojiler

| Teknoloji              | Açıklama                                                |
|------------------------|---------------------------------------------------------|
| **PHP (Pure)**         | Backend geliştirme ve sunucu taraflı işlemler           |
| **MySQL**              | Veritabanı yönetimi                                     |
| **Bootstrap 5**        | Responsive ve modern arayüz tasarımı                    |
| **CSS / JavaScript**   | Etkileşimli kullanıcı deneyimi                          |
| **Composer**           | PHP bağımlılık yönetimi                                 |
| **OpenRouter API**     | Yapay zeka hizmeti bağlantısı                           |
| **Meta Llama 4 Scout** | AI öneri sistemi (model: `meta-llama/llama-4-scout`)    |

---

![7](https://github.com/user-attachments/assets/aaf78a4d-23fb-46f0-8059-9878cf3e0061)
![1](https://github.com/user-attachments/assets/e1b0d2d2-df10-4668-8260-ed8af3d0e488)
![2](https://github.com/user-attachments/assets/f923e378-cd69-4999-a27d-3ad88fba33d7)
![2 1](https://github.com/user-attachments/assets/b96b0890-9a8f-44fe-b490-a7e1d644f182)
![3](https://github.com/user-attachments/assets/86963452-4fed-43db-ac19-00a68b93625b)
![3 1](https://github.com/user-attachments/assets/4dff21ec-116a-4600-b863-72efd9b06f18)
![4](https://github.com/user-attachments/assets/b8900ba5-a61c-4a2f-803f-8113bf2a10df)
![5](https://github.com/user-attachments/assets/01967e16-3ce2-4cf8-b8a7-9cea1787bad3)
![8](https://github.com/user-attachments/assets/a19b507a-4e9b-4957-84f5-f04524e9acb6)
![6](https://github.com/user-attachments/assets/d7b60284-50c1-4852-ac27-4f4bb940106f)


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
```

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

- `ProjeYonet/config/database.sql` içeriğini çalıştırarak veritabanını oluşturun
- `.env` dosyasını yapılandırın

### 5. PHP yerel sunucusunu başlatın

```bash
php -S localhost:8000
```

---

## 📋 Sistem Gereksinimleri

- PHP >= 8.1  
- MySQL >= 8.0  
- Composer >= 2.0  
- Apache veya Nginx sunucusu önerilir

---

## 🔐 Güvenlik Önlemleri

- `.env` dosyası hiçbir şekilde GitHub gibi sistemlerde paylaşılmamalıdır  
- **CSRF** ve **XSS** saldırılarına karşı koruma mevcuttur  
- API kullanımında **rate limiting** uygulanabilir  
- Gelişmiş oturum ve kullanıcı doğrulama süreçleri uygulanabilir

---

## 📄 Lisans

Bu proje [MIT Lisansı](https://github.com/samettalhatozlu/Taskera/blob/main/LICENSE) kapsamında lisanslanmıştır.

---

## 📬 İletişim

📎 LinkedIn: [linkedin.com/in/samettalhatozlu](https://linkedin.com/in/samettalhatozlu)

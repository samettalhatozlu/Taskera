<?php
require_once '../config/config.php';
require_once '../models/User.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$user = new User($db);
$user_info = $user->getUserById($_SESSION['user_id']);

$response = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proje_adi = $_POST['proje_adi'] ?? '';
    $proje_tipi = $_POST['proje_tipi'] ?? '';
    $proje_aciklama = $_POST['proje_aciklama'] ?? '';
    $baslangic_tarihi = $_POST['baslangic_tarihi'] ?? date('d.m.Y');
    $bitis_tarihi = $_POST['bitis_tarihi'] ?? '';
    $takim_buyuklugu = $_POST['takim_buyuklugu'] ?? '';
    $bütçe = $_POST['bütçe'] ?? '';

    if (!empty($proje_adi) && !empty($bitis_tarihi)) {
        $prompt = "Lütfen aşağıdaki proje için detaylı bir proje yönetim takvimi ve öneriler oluştur:

Proje Adı: $proje_adi
Proje Tipi: $proje_tipi
Proje Açıklaması: $proje_aciklama
Başlangıç Tarihi: $baslangic_tarihi
Bitiş Tarihi: $bitis_tarihi
Takım Büyüklüğü: $takim_buyuklugu kişi
Bütçe: $bütçe

Lütfen aşağıdaki başlıklar altında öneriler sun:

1. Proje Fazları ve Önemli Kilometre Taşları
2. Her Faz İçin Detaylı Görev Listesi
3. Tahmini Süreler ve Kaynak Planlaması
4. Risk Analizi ve Öneriler
5. Başarı Kriterleri
6. Kalite Kontrol Noktaları

Lütfen sadece metin formatında, başlıklar ve maddeler halinde yanıt ver.";

        $api_key = '';
        $api_url = 'https://openrouter.ai/api/v1/chat/completions';
        $model = 'meta-llama/llama-3.3-8b-instruct:free';

        $post_fields = json_encode([
            "model" => $model,
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.7,
            "max_tokens" => 2000
        ], JSON_UNESCAPED_UNICODE);

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key",
            "HTTP-Referer: https://projeyonetici.com",
            "X-Title: Proje Yönetim Sistemi"
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && !$curl_error) {
            $response_data = json_decode($result, true);
            $ai_response = $response_data['choices'][0]['message']['content'] ?? 'Yanıt alınamadı.';
            
            // AI yanıtını veritabanına kaydet
            try {
                $stmt = $db->prepare("INSERT INTO project_timelines (
                    project_name, project_type, project_description, 
                    start_date, end_date, team_size, budget, 
                    ai_response, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $proje_adi,
                    $proje_tipi,
                    $proje_aciklama,
                    $baslangic_tarihi,
                    $bitis_tarihi,
                    $takim_buyuklugu,
                    $bütçe,
                    $ai_response,
                    $_SESSION['user_id']
                ]);
                
                // Başarılı kayıt mesajı
                $success_message = "Proje takvimi başarıyla oluşturuldu ve kaydedildi.";
            } catch (PDOException $e) {
                $error = "Veritabanı hatası: " . $e->getMessage();
            }
            
            // AI yanıtını HTML formatına dönüştür
            $response = formatAIResponse($ai_response);
        } else {
            $error = "API hatası: $curl_error (HTTP kodu: $http_code)";
        }
    } else {
        $error = "Lütfen proje adı ve bitiş tarihini girin.";
    }
}

// AI yanıtını HTML formatına dönüştüren fonksiyon
function formatAIResponse($text) {
    global $takim_buyuklugu, $proje_adi, $proje_tipi, $baslangic_tarihi, $bitis_tarihi, $proje_aciklama;
    
    $html = '<div class="project-timeline animate__animated animate__fadeIn">';
    
    // Proje özet kartı
    $html .= '<div class="project-summary card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="project-icon-wrapper me-3">
                    <i class="bi bi-kanban fs-2 text-primary"></i>
                </div>
                <div>
                    <h4 class="mb-1">Proje Özeti</h4>
                    <div class="text-muted small">Son Güncelleme: ' . date('d.m.Y H:i') . '</div>
                </div>
                <div class="ms-auto">
                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-download me-1"></i>PDF İndir
                    </button>
                </div>
            </div>
            <div class="progress mb-3" style="height: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="projectProgress"></div>
            </div>
            <div class="row g-3 text-center">
                <div class="col-md-3">
                    <div class="stat-card p-2 rounded-3 bg-light">
                        <div class="text-primary mb-1"><i class="bi bi-calendar-check"></i></div>
                        <div class="small text-muted">Başlangıç</div>
                        <div class="fw-bold">' . date('d.m.Y') . '</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-2 rounded-3 bg-light">
                        <div class="text-primary mb-1"><i class="bi bi-people"></i></div>
                        <div class="small text-muted">Takım</div>
                        <div class="fw-bold" id="teamSize">' . htmlspecialchars($takim_buyuklugu) . ' Kişi</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-2 rounded-3 bg-light">
                        <div class="text-primary mb-1"><i class="bi bi-list-check"></i></div>
                        <div class="small text-muted">Görevler</div>
                        <div class="fw-bold" id="taskCount">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-2 rounded-3 bg-light">
                        <div class="text-primary mb-1"><i class="bi bi-clock-history"></i></div>
                        <div class="small text-muted">Kalan Süre</div>
                        <div class="fw-bold" id="remainingTime">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    // Ana bölümler
    $sections = explode("\n\n", $text);
    $taskCount = 0;
    $fazlar = [];
    $riskler = [];
    $basariKriterleri = [];
    
    foreach ($sections as $index => $section) {
        if (trim($section) === '') continue;
        
        // Başlık kontrolü
        if (preg_match('/^\d+\.\s+(.+)$/m', $section, $matches)) {
            $title = $matches[1];
            $content = preg_replace('/^\d+\.\s+.+\n/m', '', $section);
            
            // Önemli bölümleri sakla
            if (stripos($title, 'Faz') !== false) {
                preg_match_all('/[-•]\s+(.+)/', $content, $items);
                $fazlar = $items[1] ?? [];
            } elseif (stripos($title, 'Risk') !== false) {
                preg_match_all('/[-•]\s+(.+)/', $content, $items);
                $riskler = $items[1] ?? [];
            } elseif (stripos($title, 'Başarı') !== false) {
                preg_match_all('/[-•]\s+(.+)/', $content, $items);
                $basariKriterleri = $items[1] ?? [];
            }
            
            $sectionId = 'section-' . ($index + 1);
            $html .= '<div class="timeline-section mb-4 animate__animated animate__fadeInUp" style="animation-delay: ' . ($index * 0.2) . 's">';
            $html .= '<div class="phase-header d-flex align-items-center justify-content-between" 
                           data-bs-toggle="collapse" 
                           data-bs-target="#' . $sectionId . '" 
                           role="button" 
                           aria-expanded="true">
                        <h5 class="mb-0">
                            <i class="bi bi-' . getSectionIcon($title) . ' me-2"></i>
                            ' . htmlspecialchars($title) . '
                        </h5>
                        <i class="bi bi-chevron-down"></i>
                    </div>';
            
            // İçerik
            $html .= '<div class="collapse show" id="' . $sectionId . '">';
            $html .= '<div class="timeline-content">';
            
            // Alt maddeler varsa liste olarak göster
            if (preg_match_all('/[-•]\s+(.+)/', $content, $items)) {
                $html .= '<ul class="task-list">';
                foreach ($items[1] as $itemIndex => $item) {
                    $taskCount++;
                    $html .= '<li class="task-item" data-task-id="task-' . $taskCount . '">
                        <div class="task-header">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="task-' . $taskCount . '">
                                <label class="form-check-label" for="task-' . $taskCount . '">
                                    ' . htmlspecialchars($item) . '
                                </label>
                            </div>
                            <div class="task-actions">
                                <span class="badge bg-primary bg-opacity-10 text-primary">0%</span>
                                <button class="btn btn-sm btn-link text-muted" data-bs-toggle="tooltip" title="Detaylar">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </div>
                        </div>
                    </li>';
                }
                $html .= '</ul>';
            } else {
                // Düz metin
                $html .= '<p class="mb-0">' . nl2br(htmlspecialchars($content)) . '</p>';
            }
            
            $html .= '</div></div></div>';
        }
    }
    
    // Profesyonel özet bölümünü en sona ekle
    $html .= '<div class="project-executive-summary mt-5 animate__animated animate__fadeIn">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="card-title d-flex align-items-center mb-4">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Yönetici Özeti
                </h4>
                <div class="executive-summary-content">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary mb-3">Proje Genel Bakış</h5>
                            <p class="lead mb-4">' . htmlspecialchars($proje_aciklama) . '</p>
                            
                            <div class="key-phases mb-4">
                                <h5 class="text-primary mb-3">Temel Fazlar</h5>
                                <div class="phase-timeline">';
                                
    // İlk 3 fazı göster
    $displayedFazlar = array_slice($fazlar, 0, 3);
    foreach ($displayedFazlar as $index => $faz) {
        $html .= '<div class="phase-item d-flex align-items-start mb-3">
            <div class="phase-number me-3">
                <span class="badge bg-primary rounded-pill">' . ($index + 1) . '</span>
            </div>
            <div class="phase-content">
                <p class="mb-0">' . htmlspecialchars($faz) . '</p>
            </div>
        </div>';
    }
                                
    $html .= '</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="success-criteria mb-4">
                                <h5 class="text-primary mb-3">Başarı Kriterleri</h5>
                                <ul class="list-unstyled">';
    
    // İlk 3 başarı kriterini göster
    $displayedKriterler = array_slice($basariKriterleri, 0, 3);
    foreach ($displayedKriterler as $kriter) {
        $html .= '<li class="mb-2">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            ' . htmlspecialchars($kriter) . '
        </li>';
    }
    
    $html .= '</ul>
                            </div>
                            
                            <div class="risk-assessment">
                                <h5 class="text-primary mb-3">Risk Değerlendirmesi</h5>
                                <ul class="list-unstyled">';
    
    // İlk 3 riski göster
    $displayedRiskler = array_slice($riskler, 0, 3);
    foreach ($displayedRiskler as $risk) {
        $html .= '<li class="mb-2">
            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
            ' . htmlspecialchars($risk) . '
        </li>';
    }
    
    $html .= '</ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .project-executive-summary {
                margin-bottom: 3rem;
            }
            .executive-summary-content {
                color: #495057;
            }
            .phase-timeline .phase-item {
                position: relative;
            }
            .phase-timeline .phase-number .badge {
                width: 25px;
                height: 25px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .success-criteria li, .risk-assessment li {
                padding: 8px;
                border-radius: 6px;
                background-color: rgba(248, 249, 250, 0.7);
                margin-bottom: 8px !important;
            }
            .success-criteria li i {
                color: #198754;
            }
            .risk-assessment li i {
                color: #ffc107;
            }
            .lead {
                font-size: 1.1rem;
                font-weight: 400;
            }
        </style>
    </div>';

    $html .= '</div>';

    // JavaScript değişkenlerini güncelle
    $html .= "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Task sayısını güncelle
            document.getElementById('taskCount').textContent = '{$taskCount}';
            
            // İlerleme çubuğunu güncelle
            updateProgress();
            
            // Kalan süreyi güncelle
            updateRemainingTime();
            
            // Tooltips'leri etkinleştir
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Görev tamamlanma durumunu takip et
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateProgress();
                
                // Görev tamamlanma animasyonu
                const taskItem = this.closest('.task-item');
                const badge = taskItem.querySelector('.badge');
                
                if (this.checked) {
                    badge.textContent = '100%';
                    badge.className = 'badge bg-success bg-opacity-10 text-success';
                    taskItem.classList.add('completed');
                } else {
                    badge.textContent = '0%';
                    badge.className = 'badge bg-primary bg-opacity-10 text-primary';
                    taskItem.classList.remove('completed');
                }
            });
        });

        function updateProgress() {
            const total = document.querySelectorAll('.form-check-input').length;
            const completed = document.querySelectorAll('.form-check-input:checked').length;
            const progress = total > 0 ? (completed / total) * 100 : 0;
            
            const progressBar = document.getElementById('projectProgress');
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
        }

        function updateRemainingTime() {
            const endDate = document.getElementById('bitis_tarihi').value;
            if (endDate) {
                const remaining = Math.ceil((new Date(endDate) - new Date()) / (1000 * 60 * 60 * 24));
                document.getElementById('remainingTime').textContent = 
                    remaining > 0 ? remaining + ' Gün' : 'Süre Doldu';
            }
        }
    </script>";
    
    return $html;
}

// Bölüm başlıklarına göre ikon seç
function getSectionIcon($title) {
    $icons = [
        'Faz' => 'diagram-3',
        'Görev' => 'list-check',
        'Süre' => 'clock',
        'Risk' => 'exclamation-triangle',
        'Başarı' => 'trophy',
        'Kalite' => 'shield-check'
    ];
    
    foreach ($icons as $key => $icon) {
        if (stripos($title, $key) !== false) {
            return $icon;
        }
    }
    
    return 'arrow-right-circle';
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Yönetim Takvimi - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        body { 
            font-family: 'Inter', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Header Styles */
        .project-header {
            background: linear-gradient(135deg, #0396FF, #0D6EFD);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .project-header h1 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .project-info {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .project-info-item {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .project-info-label {
            font-weight: 500;
            min-width: 150px;
            color: #6c757d;
        }

        .project-info-value {
            font-weight: 400;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), #0396FF);
            border-radius: 3px;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary-color);
        }

        .timeline-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-left: 1rem;
            transition: transform 0.3s ease;
        }

        .timeline-content:hover {
            transform: translateY(-5px);
        }

        /* Phase Styles */
        .phase-header {
            background: linear-gradient(135deg, var(--primary-color), #0396FF);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .phase-header h3 {
            margin: 0;
            font-weight: 500;
        }

        /* Task Styles */
        .task-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .task-item {
            background: var(--light-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--success-color);
            transition: transform 0.2s ease;
        }

        .task-item:hover {
            transform: translateX(5px);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .task-title {
            font-weight: 500;
            color: var(--dark-color);
        }

        .task-date {
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* Badge Styles */
        .custom-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-primary {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
        }

        .badge-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success-color);
        }

        /* Table Styles */
        .custom-table {
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .custom-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1rem;
            font-weight: 500;
            color: var(--dark-color);
        }

        .custom-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .custom-table tbody tr:hover {
            background: rgba(13, 110, 253, 0.05);
        }

        /* Progress Bar */
        .progress-container {
            margin-top: 0.5rem;
        }

        .progress {
            height: 0.5rem;
            border-radius: 0.25rem;
            background: #e9ecef;
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--primary-color), #0396FF);
            border-radius: 0.25rem;
            transition: width 0.3s ease;
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background: white !important;
            }

            .timeline::before {
                display: none;
            }

            .timeline-content {
                box-shadow: none !important;
                border: 1px solid #dee2e6;
            }
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .project-info-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .project-info-label {
                margin-bottom: 0.25rem;
            }

            .timeline::before {
                left: 1rem;
            }

            .timeline-item {
                padding-left: 3rem;
            }

            .timeline-item::before {
                left: 0.5rem;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="project-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="bi bi-calendar3 me-2"></i>Proje Yönetim Takvimi</h1>
            <button class="btn btn-light no-print" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Yazdır
            </button>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="proje_adi" class="form-label">
                                    <i class="bi bi-bookmark me-1"></i>Proje Adı
                                </label>
                                <input type="text" class="form-control" id="proje_adi" name="proje_adi" required>
                                <div class="invalid-feedback">
                                    Lütfen proje adını girin.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="proje_tipi" class="form-label">
                                    <i class="bi bi-tags me-1"></i>Proje Tipi
                                </label>
                                <select class="form-select" id="proje_tipi" name="proje_tipi" required>
                                    <option value="">Seçiniz...</option>
                                    <option value="Yazılım">Yazılım</option>
                                    <option value="Donanım">Donanım</option>
                                    <option value="Araştırma">Araştırma</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                                <div class="invalid-feedback">
                                    Lütfen proje tipini seçin.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="proje_aciklama" class="form-label">
                                <i class="bi bi-file-text me-1"></i>Proje Açıklaması
                            </label>
                            <textarea class="form-control" id="proje_aciklama" name="proje_aciklama" rows="3"></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="baslangic_tarihi" class="form-label">
                                    <i class="bi bi-calendar-event me-1"></i>Başlangıç Tarihi
                                </label>
                                <input type="date" class="form-control" id="baslangic_tarihi" name="baslangic_tarihi" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="bitis_tarihi" class="form-label">
                                    <i class="bi bi-calendar-check me-1"></i>Bitiş Tarihi
                                </label>
                                <input type="date" class="form-control" id="bitis_tarihi" name="bitis_tarihi" required>
                            </div>
                            <div class="col-md-4">
                                <label for="takim_buyuklugu" class="form-label">
                                    <i class="bi bi-people me-1"></i>Takım Büyüklüğü
                                </label>
                                <input type="number" class="form-control" id="takim_buyuklugu" name="takim_buyuklugu" 
                                       min="1" value="1">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="bütçe" class="form-label">
                                <i class="bi bi-currency-dollar me-1"></i>Bütçe (Opsiyonel)
                            </label>
                            <input type="text" class="form-control" id="bütçe" name="bütçe" 
                                   placeholder="Örn: 100,000 TL">
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="reset" class="btn btn-light me-md-2">
                                <i class="bi bi-x-circle me-1"></i>Temizle
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-calendar-plus me-1"></i>Takvim Oluştur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($response): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="timeline">
                        <?php echo $response; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($error): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Date input validation
document.getElementById('bitis_tarihi').addEventListener('change', function() {
    var baslangic = document.getElementById('baslangic_tarihi').value;
    var bitis = this.value;
    
    if(baslangic && bitis && baslangic > bitis) {
        this.setCustomValidity('Bitiş tarihi başlangıç tarihinden önce olamaz');
    } else {
        this.setCustomValidity('');
    }
});

// Timeline animation
document.addEventListener('DOMContentLoaded', function() {
    const timelineItems = document.querySelectorAll('.timeline-item');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInLeft');
            }
        });
    }, { threshold: 0.5 });
    
    timelineItems.forEach(item => observer.observe(item));
});
</script>
</body>
</html> 
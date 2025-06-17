<?php
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/Project.php';

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
$projectObj = new Project($db);

// Kullanıcı giriş yaptıktan sonra AI özetini al
function getAIOzetFromOpenRouter($projects, $timelines) {
    $api_key = '';
    $api_url = 'https://openrouter.ai/api/v1/chat/completions';
    $model = 'meta-llama/llama-3.3-8b-instruct:free';

    $prompt = "Aşağıda kullanıcının projeleri ve proje takvimi var. Proje içeriklerine ve takvimine göre kullanıcıya profesyonel, kısa ve net bir özet ve bilgilendirme hazırla. Sadece özet ve bilgilendirme cümleleri üret:\n\n";
    $prompt .= "Projeler: " . json_encode($projects, JSON_UNESCAPED_UNICODE) . "\n";
    $prompt .= "Takvim: " . json_encode($timelines, JSON_UNESCAPED_UNICODE);

    $messages = [
        [
            "role" => "user",
            "content" => [
                [
                    "type" => "text",
                    "text" => $prompt
                ]
            ]
        ]
    ];

    $post_fields = json_encode([
        "model" => $model,
        "messages" => $messages,
        "temperature" => 0.7,
        "max_tokens" => 500
    ], JSON_UNESCAPED_UNICODE);

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key",
        "HTTP-Referer: https://projeyonetim.com",
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
        return $response_data['choices'][0]['message']['content'] ?? '';
    }
    return '';
}

// Proje ve takvim verilerini çek
$user_id = $user_info['id'];
$projects = $projectObj->getUserProjects($user_id);
$timelines = [];
try {
    $stmt = $db->prepare("SELECT * FROM project_timelines WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $timelines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

if (!isset($_SESSION['ai_ozet_last_time']) || (time() - $_SESSION['ai_ozet_last_time']) > 3600) {
    $ai_ozet = getAIOzetFromOpenRouter($projects, $timelines);
    $_SESSION['ai_ozet_last_time'] = time();
    $_SESSION['ai_ozet_content'] = $ai_ozet;
} else {
    $ai_ozet = $_SESSION['ai_ozet_content'] ?? '';
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/css/img/favicon.png">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(120deg, #f8f9fa 60%, #e3f0ff 100%);
        }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .feature-card, .quick-card {
            border-radius: 1.5rem;
            transition: box-shadow .2s, transform .2s, background .2s;
        }
        .feature-card:hover, .quick-card:hover {
            box-shadow: 0 8px 32px rgba(13,110,253,0.13);
            transform: translateY(-4px) scale(1.03);
            background: linear-gradient(120deg, #f0f6ff 60%, #e3f0ff 100%);
        }
        .feature-card i, .quick-card i {
            transition: color .2s, transform .2s;
        }
        .feature-card:hover i, .quick-card:hover i {
            color: #198754 !important;
            transform: scale(1.15) rotate(-6deg);
        }
        .main-logo {
            width: 110px; height: 110px; object-fit: cover; border-radius: 50%;
            box-shadow: 0 2px 16px rgba(13,110,253,0.10);
            background: linear-gradient(135deg, #fff 60%, #e3f0ff 100%);
        }
        .motivasyon {
            font-size: 1.2rem;
            color: #0d6efd;
            font-weight: 500;
            margin-top: 1.2rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }
        .big-number {
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .quick-card {
            border-radius: 1.2rem;
            min-height: 140px;
        }
        .tanitim-gradient {
            background: linear-gradient(120deg,#e3f0ff 0%,#f8f9fa 100%);
        }
        .tanitim-icon {
            transition: transform .2s;
        }
        .tanitim-card:hover .tanitim-icon {
            transform: scale(1.18) rotate(8deg);
        }
        @media (max-width: 767px) {
            .main-logo { width: 80px; height: 80px; }
            .big-number { font-size: 2rem; }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<?php if(isset($_SESSION['user_id'])): ?>
    <div class="container py-5">
        <!-- Logo ve Hoş Geldin -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg p-4 text-center" style="border-radius: 2rem;">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-white p-2 rounded-circle mb-3" style="box-shadow:0 2px 16px rgba(13,110,253,0.10);">
                            <img src="../assets/css/img/logo.png" alt="Taskera" class="main-logo">
                        </div>
                        <h1 class="fw-bold mb-2" style="font-size:2.5rem;">Hoş geldin, <?php echo htmlspecialchars($user_info['full_name']); ?>!</h1>
                        <p class="lead text-muted mb-0">Taskera ile projelerini, görevlerini ve ekibini kolayca yönet.</p>
                        <div class="motivasyon">Bugün harika işler başarabilirsin ✨</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tanıtım Alert -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="alert alert-primary text-center fs-5 mb-0" style="border-radius:1rem;">
                    <b>Taskera</b> ile projelerinizi, görevlerinizi ve ekibinizi tek bir yerden yönetin. AI destekli planlama, dosya paylaşımı, mesajlaşma ve daha fazlası burada!
                </div>
            </div>
        </div>
        <!-- Özet Panel -->
        <div class="row g-4 mb-5 justify-content-center">
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow feature-card text-center py-4" style="border-radius:1.5rem; transition:transform .2s;">
                    <div class="mb-2"><i class="bi bi-kanban" style="font-size:2.7rem;color:#0d6efd;"></i></div>
                    <div class="fw-bold big-number text-primary mb-1"><?php echo $projectObj->getUserProjectCount($user_info['id']); ?></div>
                    <div class="mb-2">Projelerim</div>
                    <a href="projects.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Tüm Projeler</a>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow feature-card text-center py-4" style="border-radius:1.5rem; transition:transform .2s;">
                    <div class="mb-2"><i class="bi bi-list-task" style="font-size:2.7rem;color:#0d6efd;"></i></div>
                    <div class="fw-bold big-number text-primary mb-1"><?php require_once '../models/Task.php'; $taskObj = new Task($db); echo count($taskObj->getUserTasks($user_info['id'])); ?></div>
                    <div class="mb-2">Görevlerim</div>
                    <a href="tasks.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Tüm Görevler</a>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow feature-card text-center py-4" style="border-radius:1.5rem; transition:transform .2s;">
                    <div class="mb-2"><i class="bi bi-check2-circle" style="font-size:2.7rem;color:#198754;"></i></div>
                    <div class="fw-bold big-number text-success mb-1"><?php $completed = 0; foreach($taskObj->getUserTasks($user_info['id']) as $t) { if($t['status']==='completed') $completed++; } echo $completed; ?></div>
                    <div class="mb-2">Tamamlanan Görev</div>
                </div>
            </div>
        </div>
        <!-- Hızlı Erişim Kartları -->
        <div class="row g-4 mb-5 justify-content-center">
            <div class="col-6 col-md-3 col-lg-2">
                <a href="projects.php" class="card h-100 border-0 shadow-sm quick-card text-center text-decoration-none py-4">
                    <div class="mb-2"><i class="bi bi-kanban" style="font-size:2.2rem;color:#0d6efd;"></i></div>
                    <div class="fw-semibold">Projelerim</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="tasks.php" class="card h-100 border-0 shadow-sm quick-card text-center text-decoration-none py-4">
                    <div class="mb-2"><i class="bi bi-list-task" style="font-size:2.2rem;color:#0d6efd;"></i></div>
                    <div class="fw-semibold">Görevlerim</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="takvim_listesi.php" class="card h-100 border-0 shadow-sm quick-card text-center text-decoration-none py-4">
                    <div class="mb-2"><i class="bi bi-calendar-event" style="font-size:2.2rem;color:#0d6efd;"></i></div>
                    <div class="fw-semibold">Proje Takvimleri</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="ai_sor.php" class="card h-100 border-0 shadow-sm quick-card text-center text-decoration-none py-4">
                    <div class="mb-2"><i class="bi bi-robot" style="font-size:2.2rem;color:#0d6efd;"></i></div>
                    <div class="fw-semibold">AI Asistan</div>
                </a>
            </div>
        </div>
    </div>
    <!-- Alt Tanıtım Bölümü -->
    <div class="py-5 tanitim-gradient">
        <div class="container">
            <div class="text-center mb-5">
                <div class="d-inline-block bg-white p-3 rounded-4 shadow" style="box-shadow:0 4px 24px rgba(13,110,253,0.13);">
                    <img src="../assets/css/img/logo.png" alt="Taskera" style="max-width:160px; border-radius:24px;">
                </div>
            </div>
            <div class="row g-4 justify-content-center mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4 tanitim-card" style="border-radius:1.2rem;">
                        <i class="bi bi-lightbulb display-4 text-primary mb-3 tanitim-icon"></i>
                        <h4 class="fw-bold mb-2">Yenilikçi Proje Yönetimi</h4>
                        <p class="mb-0">Projelerinizi modern arayüz ve güçlü araçlarla kolayca planlayın, yönetin ve takip edin.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4 tanitim-card" style="border-radius:1.2rem;">
                        <i class="bi bi-people display-4 text-success mb-3 tanitim-icon"></i>
                        <h4 class="fw-bold mb-2">Ekip İşbirliği</h4>
                        <p class="mb-0">Takım üyelerinizle anlık iletişim kurun, dosya paylaşın ve görevleri birlikte yönetin.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4 tanitim-card" style="border-radius:1.2rem;">
                        <i class="bi bi-robot display-4 text-info mb-3 tanitim-icon"></i>
                        <h4 class="fw-bold mb-2">AI Destekli Planlama</h4>
                        <p class="mb-0">Yapay zeka ile proje takvimi oluşturun, riskleri analiz edin ve başarıya ulaşın.</p>
                    </div>
                </div>
            </div>
            <div class="row mt-4 justify-content-center">
                <div class="col-md-10">
                    <div class="alert alert-info text-center p-4 fs-5" style="border-radius:1rem;">
                        <i class="bi bi-stars me-2"></i>
                        <b>Taskera</b> ile projelerinizi bir üst seviyeye taşıyın. Tüm süreçleri tek panelden yönetin, zamandan ve maliyetten tasarruf edin!
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- AI Özet Modal -->
    <div class="modal fade" id="aiOzetModal" tabindex="-1" aria-labelledby="aiOzetModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="aiOzetModalLabel">TaskeraAI Özetiniz</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
          </div>
          <div class="modal-body">
            <?php echo nl2br(htmlspecialchars($ai_ozet ?? '')); ?>
          </div>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (localStorage.getItem('aiOzetModalShown') !== '1') {
        var myModal = new bootstrap.Modal(document.getElementById('aiOzetModal'));
        myModal.show();
        document.getElementById('aiOzetModal').addEventListener('hidden.bs.modal', function () {
          localStorage.setItem('aiOzetModalShown', '1');
        });
      }
    });
    </script>
<?php else: ?>
<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold mb-4">Projelerinizi Yönetin, Başarıya Ulaşın</h1>
                <p class="lead mb-4">Taskera ile projelerinizi kolayca yönetin, görevlerinizi takip edin ve ekip arkadaşlarınızla işbirliği yapın.</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-light btn-lg">Hemen Başla</a>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-center">
                <div class="logo-container">
                    <img src="../assets/css/img/logo.png" alt="Proje Yönetimi" class="img-fluid" style="max-width: 300px; border-radius: 50%; background-color: rgba(13, 110, 253, 0.2); padding: 10px;">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Özellikler -->
<div class="container">
    <h2 class="text-center mb-5">Özellikler</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-list-task feature-icon"></i>
                    <h5 class="card-title">Görev Yönetimi</h5>
                    <p class="card-text">Görevlerinizi oluşturun, atayın ve takip edin. Projelerinizi daha verimli yönetin.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-people feature-icon"></i>
                    <h5 class="card-title">Ekip İşbirliği</h5>
                    <p class="card-text">Ekip arkadaşlarınızla kolayca iletişim kurun ve projelerinizi birlikte yönetin.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-robot feature-icon"></i>
                    <h5 class="card-title">AI Destekli</h5>
                    <p class="card-text">Yapay zeka ile projelerinizi optimize edin ve daha akıllı kararlar alın.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>Taskera</h5>
                <p>Projelerinizi daha verimli yönetmenizi sağlayan modern çözüm.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p>&copy; 2025 Taskera. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
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

// Ayarları burada yap
$api_key = '';
$api_url = 'https://openrouter.ai/api/v1/chat/completions';
$model = 'meta-llama/llama-3.3-8b-instruct:free';

$response = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['question'])) {
    $question = $_POST['question'];
    $image_url = $_POST['image_url'] ?? '';

    $messages = [
        [
            "role" => "user",
            "content" => []
        ]
    ];

    // Add text content
    $messages[0]["content"][] = [
        "type" => "text",
        "text" => $question
    ];

    // Add image content if provided
    if (!empty($image_url)) {
        $messages[0]["content"][] = [
            "type" => "image_url",
            "image_url" => [
                "url" => $image_url
            ]
        ];
    }

    $post_fields = json_encode([
        "model" => $model,
        "messages" => $messages,
        "temperature" => 0.7,
        "max_tokens" => 1000
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
        $response = $response_data['choices'][0]['message']['content'] ?? 'Yanıt alınamadı.';
    } else {
        $error = "API hatası: $curl_error (HTTP kodu: $http_code)";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Asistan - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1d4ed8;
            --primary-light: #3b82f6;
            --primary-dark: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-radius: 16px;
            --box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --card-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .page-container {
            display: flex;
            gap: 2.5rem;
            padding: 2.5rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        .left-section, .right-section {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .ai-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }

        .ai-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .analysis-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            margin-bottom: 0;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            height: fit-content;
        }

        .analysis-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
        }

        .analysis-summary {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .analysis-summary h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }

        .analysis-metric {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
            padding: 1.25rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .analysis-metric:last-child {
            margin-bottom: 0;
        }

        .analysis-metric:hover {
            transform: translateX(5px);
            box-shadow: var(--card-shadow);
        }

        .analysis-metric i {
            margin-right: 1.25rem;
            font-size: 1.5rem;
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
            padding: 1rem;
            border-radius: 50%;
            transition: var(--transition);
        }

        .analysis-metric:hover i {
            transform: scale(1.1);
            background: rgba(37, 99, 235, 0.15);
        }

        .analysis-metric span {
            font-size: 1.1rem;
            color: var(--dark-color);
        }

        .analysis-metric strong {
            color: var(--primary-color);
            margin-left: 0.5rem;
            font-size: 1.2rem;
        }

        .btn-ai {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .btn-ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .form-control {
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            border: 1px solid rgba(0,0,0,0.1);
            transition: var(--transition);
            font-size: 1.1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-label {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .ai-response {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary-color);
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .ai-loading {
            display: none;
            text-align: center;
            padding: 3rem;
        }

        .ai-loading .spinner-border {
            width: 3.5rem;
            height: 3.5rem;
            color: var(--primary-color);
        }

        .ai-loading p {
            margin-top: 1.5rem;
            font-size: 1.1rem;
            color: var(--dark-color);
        }

        @media (max-width: 1200px) {
            .page-container {
                flex-direction: column;
                padding: 1.5rem;
            }
            
            .left-section, .right-section {
                width: 100%;
            }

            .analysis-card {
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .ai-header {
                padding: 2rem 0;
            }
            
            .analysis-card {
                padding: 1.5rem;
            }

            .analysis-metric {
                padding: 1rem;
            }

            .analysis-metric i {
                padding: 0.75rem;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="page-container">
        <!-- Sol Bölüm - AI Sohbet -->
        <div class="left-section">
            <div class="ai-header">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-5 fw-bold mb-3">
                                <i class="bi bi-robot me-2"></i>AI Asistan
                            </h1>
                            <p class="lead mb-0">Projeleriniz için yapay zeka destekli yardım alın</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="ai-tips">
                                <h6 class="mb-2"><i class="bi bi-lightbulb me-2"></i>İpuçları:</h6>
                                <ul class="small">
                                    <li>Proje planlaması hakkında soru sorun</li>
                                    <li>Teknoloji önerileri isteyin</li>
                                    <li>Kod optimizasyonu talep edin</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ai-card">
                <form method="POST" class="ai-form" id="aiForm">
                    <div class="mb-4">
                        <label for="question" class="form-label fw-bold">
                            <i class="bi bi-chat-dots me-2"></i>Sorunuz
                        </label>
                        <textarea class="form-control" id="question" name="question" rows="4" 
                                  placeholder="Projeniz hakkında soru sorun..." required><?php 
                                  if (isset($_GET['text'])) { echo htmlspecialchars($_GET['text']); }
                                  else if (isset($_POST['question'])) { echo htmlspecialchars($_POST['question']); }
                        ?></textarea>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-outline-secondary me-md-2" onclick="clearForm()">
                            <i class="bi bi-x-circle me-2"></i>Temizle
                        </button>
                        <button type="submit" class="btn btn-ai">
                            <i class="bi bi-send me-2"></i>Gönder
                        </button>
                    </div>
                </form>

                <div class="ai-loading" id="loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                    <p class="mt-3">Yanıt hazırlanıyor, lütfen bekleyin...</p>
                </div>

                <?php if ($response): ?>
                    <div class="ai-response animate__animated animate__fadeIn">
                        <h5 class="mb-3">
                            <i class="bi bi-robot me-2"></i>AI Yanıtı
                        </h5>
                        <div class="response-content">
                            <?php echo nl2br(htmlspecialchars($response)); ?>
                        </div>
                    </div>
                <?php elseif ($error): ?>
                    <div class="ai-response ai-error animate__animated animate__fadeIn">
                        <h5 class="mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>Hata
                        </h5>
                        <div class="response-content">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sağ Bölüm - Analiz -->
        <div class="right-section">
            <div class="analysis-card animate-fadeInUp">
                <h3 class="mb-4">
                    <i class="bi bi-graph-up me-2"></i>Proje Analizi
                </h3>
                <div class="analysis-summary mt-4">
                    <h5 class="mb-3">Proje Özeti</h5>
                    <div class="analysis-metric">
                        <i class="bi bi-check-circle"></i>
                        <span>Tamamlanan Görevler: <strong id="completedTasks">0</strong></span>
                    </div>
                    <div class="analysis-metric">
                        <i class="bi bi-clock"></i>
                        <span>Devam Eden Görevler: <strong id="ongoingTasks">0</strong></span>
                    </div>
                    <div class="analysis-metric">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>Geciken Görevler: <strong id="delayedTasks">0</strong></span>
                    </div>
                </div>

                <div class="mt-4">
                    <canvas id="projectChart" class="analysis-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('aiForm');
    const loading = document.getElementById('loading');
    const imageUrl = document.getElementById('image_url');
    const imagePreview = document.getElementById('imagePreview');

    form.addEventListener('submit', function() {
        loading.style.display = 'block';
    });

    imageUrl.addEventListener('change', function() {
        if (this.value) {
            imagePreview.src = this.value;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }
    });
});

function clearForm() {
    document.getElementById('question').value = '';
    document.getElementById('image_url').value = '';
    document.getElementById('imagePreview').style.display = 'none';
}

// Proje grafiği
function initProjectChart() {
    const ctx = document.getElementById('projectChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
            datasets: [{
                label: 'Tamamlanan Görevler',
                data: [12, 19, 15, 25, 22, 30],
                borderColor: '#1d4ed8',
                backgroundColor: gradient,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1d4ed8',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }, {
                label: 'Toplam Görevler',
                data: [20, 25, 30, 35, 40, 45],
                borderColor: '#94a3b8',
                backgroundColor: 'rgba(148, 163, 184, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#94a3b8',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 25,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 13,
                            weight: '500'
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Proje İlerleme Grafiği',
                    padding: {
                        top: 15,
                        bottom: 35
                    },
                    font: {
                        size: 18,
                        weight: '600'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 15,
                    boxPadding: 8,
                    usePointStyle: true,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y} görev`;
                        }
                    },
                    titleFont: {
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        padding: 10
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        padding: 10
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    initProjectChart();
    
    // Animasyonlu sayaç
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value;
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Metrikleri animasyonlu şekilde güncelle
    animateValue(document.getElementById('completedTasks'), 0, 15, 2000);
    animateValue(document.getElementById('ongoingTasks'), 0, 8, 2000);
    animateValue(document.getElementById('delayedTasks'), 0, 3, 2000);
});
</script>
</body>
</html>

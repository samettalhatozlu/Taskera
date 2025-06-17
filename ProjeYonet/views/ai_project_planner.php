<?php
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/Project.php';
require_once '../models/Task.php';

// Oturum kontrolü
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = new User($db);
$project = new Project($db);
$task = new Task($db);
$gemini = new GeminiHelper(GEMINI_API_KEY);

$project_plan = null;
$project_timeline = null;
$technology_suggestions = null;
$reminders = null;
$error = null;
$success = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_type = trim($_POST['project_type']);
    $project_description = trim($_POST['project_description']);
    $project_duration = trim($_POST['project_duration']);
    $project_team_size = trim($_POST['project_team_size']);
    $project_budget = trim($_POST['project_budget']);
    
    if(!empty($project_type) && !empty($project_description)) {
        try {
            // Proje planı oluştur
            $project_plan = $gemini->getProjectSuggestions($project_type);
            
            // Proje zaman çizelgesi oluştur
            $project_timeline = $gemini->getProjectTimeline($project_description);
            
            // Teknoloji önerileri al
            $technology_suggestions = $gemini->getTechnologySuggestions($project_type);
            
            // Hatırlatıcılar oluştur
            $reminders = $gemini->generateDocumentation([
                'type' => $project_type,
                'description' => $project_description,
                'duration' => $project_duration,
                'team_size' => $project_team_size,
                'budget' => $project_budget
            ]);

            // Projeyi kaydet butonu için verileri hazırla
            $project_data = [
                'title' => $project_type . ' Projesi',
                'description' => $project_description,
                'type' => $project_type,
                'duration' => $project_duration,
                'team_size' => $project_team_size,
                'budget' => $project_budget,
                'plan' => $project_plan,
                'timeline' => $project_timeline,
                'technologies' => $technology_suggestions,
                'reminders' => $reminders
            ];
            
            $success = "Proje planı başarıyla oluşturuldu!";
            
        } catch(Exception $e) {
            $error = 'AI servisi ile iletişim kurulurken bir hata oluştu: ' . $e->getMessage();
        }
    } else {
        $error = 'Proje türü ve açıklaması zorunludur.';
    }
}

// Projeyi kaydetme işlemi
if(isset($_POST['save_project']) && isset($project_data)) {
    try {
        $project_id = $project->createProject([
            'user_id' => $_SESSION['user_id'],
            'title' => $project_data['title'],
            'description' => $project_data['description'],
            'type' => $project_data['type'],
            'status' => 'active'
        ]);

        // Proje planını görevlere dönüştür
        $stages = json_decode($project_data['plan'], true);
        foreach($stages as $stage) {
            $task->createTask([
                'project_id' => $project_id,
                'title' => $stage['stage'],
                'description' => $stage['description'],
                'priority' => $stage['priority'],
                'status' => 'pending',
                'due_date' => date('Y-m-d', strtotime('+' . $stage['duration']))
            ]);
        }

        // Teknoloji önerilerini kaydet
        $technologies = json_decode($project_data['technologies'], true);
        foreach($technologies as $tech) {
            $project->addTechnology($project_id, [
                'name' => $tech['name'],
                'description' => $tech['description'],
                'category' => $tech['category'],
                'link' => $tech['link']
            ]);
        }

        // Hatırlatıcıları kaydet
        $reminders = json_decode($project_data['reminders'], true);
        foreach($reminders as $reminder) {
            $project->addReminder($project_id, [
                'title' => $reminder['title'],
                'description' => $reminder['description'],
                'date' => isset($reminder['date']) ? $reminder['date'] : null
            ]);
        }

        header('Location: project_details.php?id=' . $project_id);
        exit;
    } catch(Exception $e) {
        $error = 'Proje kaydedilirken bir hata oluştu: ' . $e->getMessage();
    }
}

// Kullanıcının projelerini getir
$user_projects = $project->getUserProjects($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Proje Planlayıcı - ProjeYönet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            width: 2px;
            background: #e9ecef;
            left: 50%;
            top: 0;
            bottom: 0;
            margin-left: -1px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        .timeline-content {
            position: relative;
            width: 45%;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: #007bff;
            border-radius: 50%;
            left: 50%;
            top: 0;
            margin-left: -10px;
        }
        .tech-card {
            transition: transform 0.2s;
        }
        .tech-card:hover {
            transform: translateY(-5px);
        }
        .reminder-card {
            border-left: 4px solid #ffc107;
        }
        .project-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .project-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">ProjeYönet</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projelerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tasks.php">Görevlerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">Mesajlar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="ai_project_planner.php">AI Planlayıcı</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profilim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Mevcut Projeler -->
        <?php if(!empty($user_projects)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Mevcut Projeleriniz</h5>
                    <div class="row">
                        <?php foreach($user_projects as $user_project): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card project-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($user_project['title']); ?></h6>
                                        <p class="card-text"><?php echo htmlspecialchars($user_project['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-<?php echo $user_project['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $user_project['status'] == 'active' ? 'Aktif' : 'Tamamlandı'; ?>
                                            </span>
                                            <a href="project_details.php?id=<?php echo $user_project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Proje Planlama Formu -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">AI Proje Planlayıcı</h5>
                <p class="card-text">Projenizin detaylarını girin, yapay zeka size özel bir proje planı oluştursun.</p>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_type" class="form-label">Proje Türü</label>
                                <select class="form-select" id="project_type" name="project_type" required>
                                    <option value="">Seçiniz</option>
                                    <option value="web">Web Uygulaması</option>
                                    <option value="mobile">Mobil Uygulama</option>
                                    <option value="desktop">Masaüstü Uygulaması</option>
                                    <option value="game">Oyun Geliştirme</option>
                                    <option value="ai">Yapay Zeka Projesi</option>
                                    <option value="data">Veri Analizi</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_duration" class="form-label">Proje Süresi (Ay)</label>
                                <input type="number" class="form-control" id="project_duration" name="project_duration" min="1" max="24" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_team_size" class="form-label">Ekip Boyutu</label>
                                <input type="number" class="form-control" id="project_team_size" name="project_team_size" min="1" max="20" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_budget" class="form-label">Bütçe (TL)</label>
                                <input type="number" class="form-control" id="project_budget" name="project_budget" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="project_description" class="form-label">Proje Açıklaması</label>
                        <textarea class="form-control" id="project_description" name="project_description" rows="4" required></textarea>
                        <div class="form-text">Projenizin detaylarını, hedeflerini ve gereksinimlerini açıklayın.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Plan Oluştur</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if($project_plan): ?>
            <!-- Proje Planı -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Proje Planı</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Aşama</th>
                                    <th>Açıklama</th>
                                    <th>Süre</th>
                                    <th>Öncelik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach(json_decode($project_plan, true) as $stage): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stage['stage']); ?></td>
                                        <td><?php echo htmlspecialchars($stage['description']); ?></td>
                                        <td><?php echo htmlspecialchars($stage['duration']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($stage['priority']) {
                                                    case 'low': echo 'success'; break;
                                                    case 'medium': echo 'warning'; break;
                                                    case 'high': echo 'danger'; break;
                                                }
                                            ?>">
                                                <?php 
                                                switch($stage['priority']) {
                                                    case 'low': echo 'Düşük'; break;
                                                    case 'medium': echo 'Orta'; break;
                                                    case 'high': echo 'Yüksek'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Zaman Çizelgesi -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Proje Zaman Çizelgesi</h5>
                    <div class="timeline">
                        <?php foreach(json_decode($project_timeline, true) as $milestone): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h6><?php echo htmlspecialchars($milestone['title']); ?></h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($milestone['description']); ?></p>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($milestone['date']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Teknoloji Önerileri -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Teknoloji Önerileri</h5>
                    <div class="row">
                        <?php foreach(json_decode($technology_suggestions, true) as $tech): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card tech-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-<?php echo $tech['icon']; ?> me-2"></i>
                                            <?php echo htmlspecialchars($tech['name']); ?>
                                        </h6>
                                        <p class="card-text"><?php echo htmlspecialchars($tech['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($tech['category']); ?></span>
                                            <a href="<?php echo htmlspecialchars($tech['link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Hatırlatıcılar -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Önemli Hatırlatıcılar</h5>
                    <div class="row">
                        <?php foreach(json_decode($reminders, true) as $reminder): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card reminder-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-<?php echo $reminder['icon']; ?> me-2"></i>
                                            <?php echo htmlspecialchars($reminder['title']); ?>
                                        </h6>
                                        <p class="card-text"><?php echo htmlspecialchars($reminder['description']); ?></p>
                                        <?php if(isset($reminder['date'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo htmlspecialchars($reminder['date']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Projeyi Kaydet Butonu -->
            <?php if(isset($project_data)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Projeyi Kaydet</h5>
                    <p class="card-text">Oluşturulan proje planını kaydedip görevlere dönüştürmek için aşağıdaki butonu kullanın.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="save_project" value="1">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Projeyi Kaydet ve Görevlere Dönüştür
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- AI Önerileri -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">AI Önerileri</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        Proje Optimizasyonu
                                    </h6>
                                    <p class="card-text">Projenizi optimize etmek için AI önerileri alın.</p>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#optimizationModal">
                                        Önerileri Gör
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Risk Analizi
                                    </h6>
                                    <p class="card-text">Projenizin potansiyel risklerini analiz edin.</p>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#riskModal">
                                        Analizi Gör
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Optimizasyon Modal -->
            <div class="modal fade" id="optimizationModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Proje Optimizasyon Önerileri</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="optimizationContent">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk Analizi Modal -->
            <div class="modal fade" id="riskModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Proje Risk Analizi</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="riskContent">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            // Optimizasyon önerilerini yükle
            document.getElementById('optimizationModal').addEventListener('show.bs.modal', function() {
                fetch('get_optimization.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        project_type: '<?php echo $project_type; ?>',
                        description: '<?php echo $project_description; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('optimizationContent').innerHTML = data.content;
                })
                .catch(error => {
                    document.getElementById('optimizationContent').innerHTML = 
                        '<div class="alert alert-danger">Öneriler yüklenirken bir hata oluştu.</div>';
                });
            });

            // Risk analizini yükle
            document.getElementById('riskModal').addEventListener('show.bs.modal', function() {
                fetch('get_risk_analysis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        project_type: '<?php echo $project_type; ?>',
                        description: '<?php echo $project_description; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('riskContent').innerHTML = data.content;
                })
                .catch(error => {
                    document.getElementById('riskContent').innerHTML = 
                        '<div class="alert alert-danger">Analiz yüklenirken bir hata oluştu.</div>';
                });
            });
            </script>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
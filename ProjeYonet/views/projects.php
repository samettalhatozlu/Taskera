<?php
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/Project.php';

// Oturum kontrolü
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$user = new User($db);
$user_info = $user->getUserById($_SESSION['user_id']);
$project = new Project($db);

// Kullanıcının projelerini getir
$user_projects = $project->getUserProjects($_SESSION['user_id']);

// Yeni proje oluşturma
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    if(!empty($title)) {
        $project->create($title, $description, $_SESSION['user_id'], $start_date, $end_date);
        header('Location: projects.php');
        exit;
    }
}

// Proje silme
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $project->delete($_GET['delete']);
    header('Location: projects.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projelerim - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold display-6 text-primary">Projelerim</h2>
        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#newProjectModal">
            <i class="bi bi-plus-lg"></i> Yeni Proje
        </button>
    </div>

    <!-- Proje Listesi -->
    <div class="row g-4">
        <?php if($user_projects): ?>
            <?php foreach($user_projects as $proj): ?>
                <?php
                $progress = $project->calculateProgress($proj['id']);
                $stats = $project->getProjectStats($proj['id']);
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card project-card h-100 border-0 shadow-lg position-relative">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title fw-semibold text-dark mb-0">
                                    <i class="bi bi-kanban me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($proj['title']); ?>
                                </h5>
                            </div>
                            <p class="card-text text-secondary small mb-3"> <?php echo htmlspecialchars(substr($proj['description'], 0, 100)) . '...'; ?> </p>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="progress-percent fw-bold text-primary" style="font-size: 1.15rem; letter-spacing: 0.5px;">
                                        <i class="bi bi-graph-up-arrow me-1"></i> <?php echo $progress; ?>%
                                    </span>
                                </div>
                                <div class="progress rounded-pill" style="height: 1.1rem; background: #e9ecef;">
                                    <div class="progress-bar bg-gradient-primary" role="progressbar" style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #0d6efd 60%, #5bc0f7 100%); transition: width 0.6s cubic-bezier(.65,.05,.36,1);">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4">
                                <span class="badge rounded-pill px-4 py-2 fs-6 d-flex align-items-center gap-2 bg-<?php 
                                    switch($proj['status']) {
                                        case 'planning': echo 'secondary'; break;
                                        case 'in_progress': echo 'info'; break;
                                        case 'completed': echo 'success'; break;
                                        case 'on_hold': echo 'warning'; break;
                                    }
                                ?>" style="font-size: 1.08rem; min-width: 120px;">
                                    <?php 
                                    switch($proj['status']) {
                                        case 'planning': echo '<i class=\'bi bi-lightbulb\'></i> Planlama'; break;
                                        case 'in_progress': echo '<i class=\'bi bi-hourglass-split\'></i> Devam Ediyor'; break;
                                        case 'completed': echo '<i class=\'bi bi-check2-circle\'></i> Tamamlandı'; break;
                                        case 'on_hold': echo '<i class=\'bi bi-pause-circle\'></i> Beklemede'; break;
                                    }
                                    ?>
                                </span>
                                <div class="d-flex flex-wrap gap-2 ms-2">
                                    <a href="project_details.php?id=<?php echo $proj['id']; ?>" class="btn btn-modern btn-outline-primary d-flex align-items-center gap-2">
                                        <i class="bi bi-eye"></i> Detay
                                    </a>
                                    <a href="projects.php?delete=<?php echo $proj['id']; ?>" class="btn btn-modern btn-outline-danger d-flex align-items-center gap-2" onclick="return confirm('Bu projeyi silmek istediğinizden emin misiniz?')">
                                        <i class="bi bi-trash"></i> Sil
                                    </a>
                                    <button class="btn btn-modern btn-outline-success d-flex align-items-center gap-2 copy-ai-btn"
                                        data-title="<?php echo htmlspecialchars($proj['title']); ?>"
                                        data-desc="<?php echo htmlspecialchars($proj['description']); ?>">
                                        <i class="bi bi-robot"></i> AI'ya Sor
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5 fs-5">
                    <i class="bi bi-info-circle me-2"></i> Henüz hiç projeniz bulunmuyor. Yeni bir proje oluşturmak için "Yeni Proje" butonuna tıklayın.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Yeni Proje Modal -->
<div class="modal fade" id="newProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Proje Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Proje Başlığı</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Proje Açıklaması</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Proje Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.copy-ai-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var text = 'Proje Başlığı: ' + this.getAttribute('data-title') + '\nProje Açıklaması: ' + this.getAttribute('data-desc');
        var url = 'ai_sor.php?text=' + encodeURIComponent(text);
        window.open(url, '_blank');
    });
});
</script>
<style>
body {
    background: linear-gradient(120deg, #f8fafc 0%, #e3f0ff 100%);
    font-family: 'Inter', sans-serif;
}
.project-card {
    border-radius: 1.5rem;
    box-shadow: 0 4px 24px rgba(13,110,253,0.10);
    transition: box-shadow 0.25s, transform 0.2s;
    background: #fff;
    min-height: 370px;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
}
.project-card:hover {
    box-shadow: 0 8px 32px rgba(13,110,253,0.18);
    transform: translateY(-4px) scale(1.015);
}
.card-title {
    font-size: 1.25rem;
    letter-spacing: 0.01em;
}
.badge {
    font-size: 1rem;
    font-weight: 500;
    letter-spacing: 0.01em;
}
.btn-modern {
    border-radius: 2rem !important;
    padding: 0.55rem 1.5rem !important;
    font-size: 1.08rem !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 8px rgba(13,110,253,0.06);
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    min-width: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-modern.btn-outline-primary {
    border: 2px solid #0d6efd;
    color: #0d6efd;
    background: #fff;
}
.btn-modern.btn-outline-primary:hover {
    background: #0d6efd;
    color: #fff;
    box-shadow: 0 4px 16px rgba(13,110,253,0.13);
}
.btn-modern.btn-outline-danger {
    border: 2px solid #dc3545;
    color: #dc3545;
    background: #fff;
}
.btn-modern.btn-outline-danger:hover {
    background: #dc3545;
    color: #fff;
    box-shadow: 0 4px 16px rgba(220,53,69,0.13);
}
.btn-modern.btn-outline-success {
    border: 2px solid #198754;
    color: #198754;
    background: #fff;
}
.btn-modern.btn-outline-success:hover {
    background: #198754;
    color: #fff;
    box-shadow: 0 4px 16px rgba(25,135,84,0.13);
}
.progress-bar {
    transition: width 0.6s cubic-bezier(.65,.05,.36,1);
}
.progress-percent {
    min-width: 48px;
    display: inline-block;
}
@media (max-width: 768px) {
    .project-card { min-height: 260px; max-width: 100%; }
    .btn-modern { min-width: 90px; font-size: 0.98rem !important; padding: 0.45rem 1rem !important; }
}
</style>
</body>
</html> 
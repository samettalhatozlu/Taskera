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

$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$user = new User($db);
$project = new Project($db);
$task = new Task($db);

// Kullanıcının görevlerini getir
$user_tasks = $task->getUserTasks($_SESSION['user_id']);

// Görev durumu güncelleme
if(isset($_POST['action']) && $_POST['action'] == 'update_status' && isset($_POST['task_id']) && isset($_POST['status'])) {
    $task->updateStatus($_POST['task_id'], $_POST['status']);
    header('Location: tasks.php');
    exit;
}

// Görev silme
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $task->delete($_GET['delete']);
    header('Location: tasks.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Görevlerim - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-5">
    <h2 class="mb-5 fw-bold display-6 text-primary">Görevlerim</h2>
    <!-- Görev Listesi -->
    <div class="row g-4">
        <?php if($user_tasks): ?>
            <?php foreach($user_tasks as $task_item): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card task-card position-relative border-0 shadow-lg h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0 fw-semibold text-dark">
                                    <i class="bi bi-clipboard-check me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($task_item['title']); ?>
                                </h5>
                                <span class="badge rounded-pill px-3 py-2 fs-6 d-flex align-items-center gap-1 bg-<?php 
                                    switch($task_item['priority']) {
                                        case 'low': echo 'success'; break;
                                        case 'medium': echo 'warning'; break;
                                        case 'high': echo 'danger'; break;
                                    }
                                ?>">
                                    <i class="bi bi-flag"></i>
                                    <?php 
                                    switch($task_item['priority']) {
                                        case 'low': echo 'Düşük'; break;
                                        case 'medium': echo 'Orta'; break;
                                        case 'high': echo 'Yüksek'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <p class="card-text mb-2 text-secondary small"> <?php echo htmlspecialchars($task_item['description']); ?> </p>
                            <div class="mb-2 text-muted small">
                                <i class="bi bi-diagram-3"></i> <?php echo htmlspecialchars($task_item['project_title']); ?>
                            </div>
                            <?php if($task_item['due_date']): ?>
                                <div class="mb-3 text-muted small">
                                    <i class="bi bi-calendar-event"></i> Son Tarih: <?php echo date('d.m.Y', strtotime($task_item['due_date'])); ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <form method="POST" action="" class="d-flex align-items-center flex-grow-1">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?php echo $task_item['id']; ?>">
                                    <select name="status" class="form-select form-select-lg w-auto px-4 py-2 me-2 status-select border-0 rounded-pill bg-<?php 
                                        switch($task_item['status']) {
                                            case 'todo': echo 'secondary'; break;
                                            case 'in_progress': echo 'info'; break;
                                            case 'review': echo 'warning'; break;
                                            case 'completed': echo 'success'; break;
                                        }
                                    ?> text-dark fw-semibold shadow-sm" onchange="this.form.submit()">
                                        <option value="todo" <?php echo $task_item['status'] == 'todo' ? 'selected' : ''; ?>>📝 Yapılacak</option>
                                        <option value="in_progress" <?php echo $task_item['status'] == 'in_progress' ? 'selected' : ''; ?>>⏳ Devam Ediyor</option>
                                        <option value="review" <?php echo $task_item['status'] == 'review' ? 'selected' : ''; ?>>🔍 İncelemede</option>
                                        <option value="completed" <?php echo $task_item['status'] == 'completed' ? 'selected' : ''; ?>>✅ Tamamlandı</option>
                                    </select>
                                </form>
                                <div class="btn-group ms-2">
                                    <a href="task_details.php?id=<?php echo $task_item['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                                        <i class="bi bi-eye"></i> Detay
                                    </a>
                                    <a href="tasks.php?delete=<?php echo $task_item['id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1" onclick="return confirm('Bu görevi silmek istediğinizden emin misiniz?')">
                                        <i class="bi bi-trash"></i> Sil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5 fs-5">
                    <i class="bi bi-info-circle me-2"></i> Size atanmış hiç görev bulunmuyor.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
body {
    background: linear-gradient(120deg, #f8fafc 0%, #e3f0ff 100%);
    font-family: 'Inter', sans-serif;
}
.task-card {
    border-radius: 1.5rem;
    box-shadow: 0 4px 24px rgba(13,110,253,0.10);
    transition: box-shadow 0.25s, transform 0.2s;
    background: #fff;
    min-height: 340px;
}
.task-card:hover {
    box-shadow: 0 8px 32px rgba(13,110,253,0.18);
    transform: translateY(-4px) scale(1.015);
}
.status-select {
    min-width: 180px;
    font-weight: 600;
    font-size: 1.05rem;
    transition: box-shadow 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.status-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
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
.btn-group .btn {
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.15s, color 0.15s;
}
.btn-outline-primary:hover, .btn-outline-danger:hover {
    color: #fff !important;
}
.btn-outline-primary:hover {
    background: #0d6efd;
    border-color: #0d6efd;
}
.btn-outline-danger:hover {
    background: #dc3545;
    border-color: #dc3545;
}
@media (max-width: 768px) {
    .task-card { min-height: 280px; }
    .status-select { min-width: 120px; font-size: 0.98rem; }
    .card-title { font-size: 1.1rem; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
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

// Proje ID kontrolü
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$user = new User($db);
$project = new Project($db);
$task = new Task($db);

$project_id = $_GET['id'];
$project_info = $project->getProjectById($project_id);

// Proje bulunamadı veya kullanıcı projenin sahibi değil
if(!$project_info || $project_info['owner_id'] != $_SESSION['user_id']) {
    header('Location: projects.php');
    exit;
}

// Görev oluşturma
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_task') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $priority = $_POST['priority'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if(!empty($title)) {
        $task->create($project_id, $title, $description, $assigned_to, $priority, $due_date);
        header('Location: project_details.php?id=' . $project_id);
        exit;
    }
}

// Görev durumu güncelleme
if(isset($_POST['action']) && $_POST['action'] == 'update_status' && isset($_POST['task_id']) && isset($_POST['status'])) {
    $task->updateStatus($_POST['task_id'], $_POST['status']);
    header('Location: project_details.php?id=' . $project_id);
    exit;
}

// Görev silme
if(isset($_GET['delete_task']) && is_numeric($_GET['delete_task'])) {
    $task->delete($_GET['delete_task']);
    header('Location: project_details.php?id=' . $project_id);
    exit;
}

// Proje görevlerini getir
$project_tasks = $task->getProjectTasks($project_id);

// Proje istatistiklerini getir
$project_stats = $project->getProjectStats($project_id);
$project_progress = $project->calculateProgress($project_id);

// Tüm kullanıcıları getir (görev atama için)
try {
    $stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE id != ? ORDER BY username");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Kullanıcılar yüklenirken bir hata oluştu.';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project_info['title']); ?> - ProjeYönet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/navbar.php'; ?>
    <div class="container mt-5">
        <!-- Proje Başlığı ve İstatistikler -->
        <div class="row mb-5 align-items-start">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2 class="fw-bold display-5 text-primary mb-2"> <?php echo htmlspecialchars($project_info['title']); ?> </h2>
                <p class="text-muted fs-5 mb-0"> <?php echo htmlspecialchars($project_info['description']); ?> </p>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-lg stat-card border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-semibold mb-4">Proje İstatistikleri</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0">
                                <span><i class="bi bi-list-task me-2 text-primary"></i>Toplam Görev</span>
                                <span class="badge bg-primary rounded-pill fs-6"> <?php echo $project_stats['total_tasks']; ?> </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0">
                                <span><i class="bi bi-check2-circle me-2 text-success"></i>Tamamlanan Görev</span>
                                <span class="badge bg-success rounded-pill fs-6"> <?php echo $project_stats['completed_tasks']; ?> </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0">
                                <span><i class="bi bi-hourglass-split me-2 text-warning"></i>Devam Eden Görev</span>
                                <span class="badge bg-warning rounded-pill fs-6 text-dark"> <?php echo $project_stats['in_progress_tasks']; ?> </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0">
                                <span><i class="bi bi-pause-circle me-2 text-info"></i>Bekleyen Görev</span>
                                <span class="badge bg-info rounded-pill fs-6 text-dark"> <?php echo $project_stats['pending_tasks']; ?> </span>
                            </li>
                        </ul>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary" style="font-size: 1.1rem;"><i class="bi bi-graph-up-arrow me-1"></i><?php echo $project_progress; ?>%</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 1.1rem; background: #e9ecef;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $project_progress; ?>%; background: linear-gradient(90deg, #0d6efd 60%, #5bc0f7 100%); transition: width 0.6s cubic-bezier(.65,.05,.36,1);">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yeni Görev Ekleme -->
        <div class="card mb-5 shadow-lg border-0 task-form-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-semibold mb-4">Yeni Görev Ekle</h5>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_task">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label for="title" class="form-label">Görev Başlığı</label>
                                <input type="text" class="form-control form-control-lg rounded-pill" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label for="assigned_to" class="form-label">Atanacak Kişi</label>
                                <select class="form-select form-select-lg rounded-pill" id="assigned_to" name="assigned_to">
                                    <option value="">Kimseye Atama</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>">
                                            <?php echo htmlspecialchars($u['username'] . ' (' . $u['full_name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label for="priority" class="form-label">Öncelik</label>
                                <select class="form-select form-select-lg rounded-pill" id="priority" name="priority">
                                    <option value="low">Düşük</option>
                                    <option value="medium" selected>Orta</option>
                                    <option value="high">Yüksek</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label for="due_date" class="form-label">Son Tarih</label>
                                <input type="date" class="form-control form-control-lg rounded-pill" id="due_date" name="due_date">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-2">
                                <label for="description" class="form-label">Görev Açıklaması</label>
                                <textarea class="form-control rounded-4" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill">Görev Ekle</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Görev Listesi -->
        <div class="row g-4">
            <?php if($project_tasks): ?>
                <?php foreach($project_tasks as $task_item): ?>
                    <div class="col-md-6">
                        <div class="card task-card shadow-lg border-0 mb-2 <?php echo $task_item['status'] == 'completed' ? 'completed' : ''; ?> <?php echo $task_item['priority'] == 'high' ? 'urgent' : ''; ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title fw-semibold mb-0">
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
                                        <?php 
                                        switch($task_item['priority']) {
                                            case 'low': echo '<i class=\'bi bi-arrow-down\'></i> Düşük'; break;
                                            case 'medium': echo '<i class=\'bi bi-arrow-right\'></i> Orta'; break;
                                            case 'high': echo '<i class=\'bi bi-arrow-up\'></i> Yüksek'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                                <p class="card-text text-secondary mb-2"> <?php echo htmlspecialchars($task_item['description']); ?> </p>
                                <?php if($task_item['assigned_username']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($task_item['assigned_username']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <?php if($task_item['due_date']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event"></i> Son Tarih: <?php echo date('d.m.Y', strtotime($task_item['due_date'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center mt-3">
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
                                        <a href="project_details.php?id=<?php echo $project_id; ?>&delete_task=<?php echo $task_item['id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1" onclick="return confirm('Bu görevi silmek istediğinizden emin misiniz?')">
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
                        <i class="bi bi-info-circle me-2"></i> Bu projede henüz hiç görev bulunmuyor. Yeni bir görev eklemek için yukarıdaki formu kullanın.
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
    .stat-card {
        border-radius: 1.5rem;
        box-shadow: 0 4px 24px rgba(13,110,253,0.10);
        background: #fff;
    }
    .task-form-card {
        border-radius: 1.5rem;
        box-shadow: 0 4px 24px rgba(13,110,253,0.10);
        background: #fff;
    }
    .task-card {
        border-radius: 1.5rem;
        box-shadow: 0 4px 24px rgba(13,110,253,0.10);
        transition: box-shadow 0.25s, transform 0.2s;
        background: #fff;
        min-height: 260px;
    }
    .task-card:hover {
        box-shadow: 0 8px 32px rgba(13,110,253,0.18);
        transform: translateY(-4px) scale(1.015);
    }
    .status-select {
        min-width: 170px;
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
        .task-card { min-height: 200px; }
        .status-select { min-width: 120px; font-size: 0.98rem; }
        .card-title { font-size: 1.1rem; }
    }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
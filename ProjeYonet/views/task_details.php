<?php
require_once '../config/config.php';
require_once '../models/Task.php';
require_once '../models/User.php';
require_once '../models/Project.php';


// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Task ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: tasks.php');
    exit();
}

$task_id = (int) $_GET['id'];
$task = new Task($db);
$userModel = new User($db);
$project = new Project($db);

// Görev detaylarını getir
$task_details = $task->getTaskById($task_id);
if (!$task_details) {
    header('Location: tasks.php');
    exit();
}

$error = '';
$success = '';

// Yorum ekleme işlemi
if (isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        if ($task->addComment($task_id, $_SESSION['user_id'], $comment)) {
            $success = 'Yorum başarıyla eklendi.';
        } else {
            $error = 'Yorum eklenirken bir hata oluştu.';
        }
    }
}

// Dosya yükleme işlemi
if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file = $_FILES['task_file'];
    $file_name = basename($file['name']);
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];

    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed)) {
        if ($file_size <= 5242880) { // 5MB
            $new_filename = uniqid('file_', true) . '.' . $file_ext;
            $file_destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $file_destination)) {
                // Gerekli alanlar: project_id, uploaded_by
                $project_id = $task_details['project_id'];
                $uploaded_by = $_SESSION['user_id'];
                $file_type = $file_ext;
                $file_size_db = $file_size;
                // Dosya ekleme fonksiyonunu güncelle (modelde de güncellenecek)
                if ($task->addFile($task_id, $project_id, $uploaded_by, $file_name, $file_destination, $file_type, $file_size_db)) {
                    $success = 'Dosya başarıyla yüklendi.';
                } else {
                    $error = 'Dosya veritabanına kaydedilirken hata oluştu.';
                }
            } else {
                $error = 'Dosya yüklenemedi.';
            }
        } else {
            $error = 'Dosya boyutu 5MB\'dan büyük olamaz.';
        }
    } else {
        $error = 'Desteklenmeyen dosya türü.';
    }
}

// Durum güncelleme
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    if ($task->updateStatus($task_id, $new_status)) {
        $success = 'Durum güncellendi.';
        $task_details['status'] = $new_status;
    } else {
        $error = 'Durum güncellenemedi.';
    }
}

// Görev atama işlemi
if (isset($_POST['assign_task'])) {
    $assigned_user_id = $_POST['assigned_to'];
    if ($task->assignTask($task_id, $assigned_user_id)) {
        $success = 'Görev başarıyla atandı.';
        $assigned_user = $userModel->getUserById($assigned_user_id);
        $task_details['assigned_to'] = $assigned_user['username'];
    } else {
        $error = 'Görev atama başarısız.';
    }
}

// Verileri getir
$comments = $task->getTaskComments($task_id);
$files = $task->getTaskFiles($task_id);
$all_users = $userModel->getAllUsers();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Görev Detayları - ProjeYönet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container mt-5">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sol kısım: Görev Detayları -->
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 mb-4 task-main-card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2"></i><?php echo htmlspecialchars($task_details['title']); ?></h5>
                    <span class="badge bg-light text-dark fs-6 px-3 py-2">
                        <i class="bi bi-kanban"></i> Proje: <?php echo htmlspecialchars($task_details['project_title']); ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <p class="mb-3"><span class="fw-semibold text-secondary">Açıklama:</span><br><span class="fs-5 text-dark"><?php echo nl2br(htmlspecialchars($task_details['description'])); ?></span></p>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="fw-semibold text-secondary">Durum:</span>
                                <form method="POST" class="d-inline ms-2">
                                    <select name="status" class="form-select form-select-lg rounded-pill d-inline w-auto status-select bg-<?php
                                        echo $task_details['status'] === 'pending' ? 'secondary' : ($task_details['status'] === 'in_progress' ? 'info' : 'success');
                                    ?> text-dark fw-semibold shadow-sm" onchange="this.form.submit()">
                                        <option value="pending" <?php if ($task_details['status'] == 'pending') echo 'selected'; ?>>Bekliyor</option>
                                        <option value="in_progress" <?php if ($task_details['status'] == 'in_progress') echo 'selected'; ?>>Devam Ediyor</option>
                                        <option value="completed" <?php if ($task_details['status'] == 'completed') echo 'selected'; ?>>Tamamlandı</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="fw-semibold text-secondary">Öncelik:</span>
                                <span class="badge rounded-pill px-3 py-2 fs-6 d-flex align-items-center gap-1 bg-<?php echo $task_details['priority'] === 'high' ? 'danger' : ($task_details['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                    <?php echo $task_details['priority'] === 'high' ? '<i class=\'bi bi-arrow-up\'></i> Yüksek' : ($task_details['priority'] === 'medium' ? '<i class=\'bi bi-arrow-right\'></i> Orta' : '<i class=\'bi bi-arrow-down\'></i> Düşük'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="fw-semibold text-secondary">Bitiş Tarihi:</span> <span class="ms-1 text-dark"><?php echo date('d.m.Y', strtotime($task_details['due_date'])); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="fw-semibold text-secondary">Atanan:</span> <span class="ms-1 text-dark"><?php echo htmlspecialchars($task_details['assigned_to'] ?? 'Atanmamış'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yorumlar -->
            <div class="card mb-4 shadow-lg border-0 rounded-4">
                <div class="card-header bg-light rounded-top-4">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-chat-dots me-2"></i>Yorumlar</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="mb-4">
                        <textarea name="comment" rows="3" class="form-control rounded-4" placeholder="Yorum yaz..." required></textarea>
                        <button type="submit" name="add_comment" class="btn btn-primary btn-lg rounded-pill mt-3"><i class="bi bi-chat-left-text me-2"></i>Yorum Ekle</button>
                    </form>
                    <?php foreach ($comments as $comment): ?>
                        <div class="border rounded-4 p-3 mb-3 bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-primary"><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                            </div>
                            <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sağ kısım -->
        <div class="col-lg-4">
            <!-- Dosya Yükleme -->
            <div class="card mb-4 shadow-lg border-0 rounded-4">
                <div class="card-header bg-light rounded-top-4"><h5 class="fw-semibold"><i class="bi bi-paperclip me-2"></i>Dosyalar</h5></div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data" class="mb-3">
                        <input type="file" class="form-control mb-2 rounded-pill" name="task_file" required>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill"><i class="bi bi-upload me-2"></i>Yükle</button>
                    </form>
                    <div class="mt-3">
                        <?php foreach ($files as $file): ?>
                            <div class="d-flex justify-content-between align-items-center border rounded-4 p-2 mb-2 bg-light-subtle">
                                <div>
                                    <i class="bi bi-file-earmark me-2"></i><?php echo htmlspecialchars($file['file_name']); ?>
                                    <small class="text-muted ms-2">Yükleyen: <?php echo htmlspecialchars($file['uploaded_by'] ?? ''); ?></small>
                                    <small class="text-muted ms-2">Tarih: <?php echo date('d.m.Y H:i', strtotime($file['created_at'])); ?></small>
                                </div>
                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-download"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Görev Atama -->
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-light rounded-top-4"><h5 class="fw-semibold"><i class="bi bi-person-plus me-2"></i>Görev Atama</h5></div>
                <div class="card-body p-4">
                    <form method="POST">
                        <select name="assigned_to" class="form-select mb-3 rounded-pill" required>
                            <option value="">Kullanıcı seçiniz</option>
                            <?php foreach ($all_users as $usr): ?>
                                <option value="<?php echo $usr['id']; ?>" <?php echo ($task_details['assigned_to'] == $usr['username']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usr['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_task" class="btn btn-primary btn-lg rounded-pill w-100"><i class="bi bi-person-plus me-2"></i>Ata</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background: linear-gradient(120deg, #f8fafc 0%, #e3f0ff 100%);
    font-family: 'Inter', sans-serif;
}
.task-main-card, .card, .rounded-4 {
    border-radius: 1.5rem !important;
}
.task-main-card {
    box-shadow: 0 4px 24px rgba(13,110,253,0.10);
    background: #fff;
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
.badge {
    font-size: 1rem;
    font-weight: 500;
    letter-spacing: 0.01em;
}
.btn-primary, .btn-outline-primary, .btn-outline-danger {
    font-size: 1.08rem;
    font-weight: 600;
    border-radius: 2rem;
    padding: 0.55rem 1.5rem;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
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
.form-control, .form-select {
    border-radius: 1.5rem;
    font-size: 1.08rem;
    font-weight: 500;
}
.bg-light-subtle {
    background: #f8fafc !important;
}
@media (max-width: 992px) {
    .task-main-card, .card, .rounded-4 { border-radius: 1rem !important; }
}
@media (max-width: 768px) {
    .task-main-card, .card, .rounded-4 { border-radius: 0.7rem !important; }
    .status-select { min-width: 120px; font-size: 0.98rem; }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

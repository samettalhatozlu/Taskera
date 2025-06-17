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

$user_info = $user->getUserById($_SESSION['user_id']);
$error = '';
$success = '';

// Profil güncelleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasyon
    if(empty($username) || empty($email) || empty($full_name)) {
        $error = 'Tüm alanları doldurunuz.';
    } elseif($user->checkUsername($username) && $username != $user_info['username']) {
        $error = 'Bu kullanıcı adı zaten kullanılıyor.';
    } elseif($user->checkEmail($email) && $email != $user_info['email']) {
        $error = 'Bu email adresi zaten kullanılıyor.';
    } else {
        // Şifre değişikliği kontrolü
        if(!empty($current_password)) {
            if(empty($new_password) || empty($confirm_password)) {
                $error = 'Yeni şifre alanlarını doldurunuz.';
            } elseif($new_password !== $confirm_password) {
                $error = 'Yeni şifreler eşleşmiyor.';
            } elseif(strlen($new_password) < 6) {
                $error = 'Yeni şifre en az 6 karakter olmalıdır.';
            } elseif(!password_verify($current_password, $user_info['password'])) {
                $error = 'Mevcut şifre yanlış.';
            } else {
                // Şifre değiştirme
                if($user->changePassword($_SESSION['user_id'], $new_password)) {
                    $success = 'Şifreniz başarıyla güncellendi.';
                } else {
                    $error = 'Şifre güncellenirken bir hata oluştu.';
                }
            }
        }

        // Profil bilgilerini güncelleme
        if(empty($error)) {
            if($user->updateProfile($_SESSION['user_id'], $username, $email, $full_name)) {
                $success = empty($success) ? 'Profil bilgileriniz başarıyla güncellendi.' : $success;
                $user_info = $user->getUserById($_SESSION['user_id']);
            } else {
                $error = 'Profil güncellenirken bir hata oluştu.';
            }
        }
    }
}

// Kullanıcı istatistikleri
$user_projects = $project->getUserProjects($_SESSION['user_id']);
$user_tasks = $task->getUserTasks($_SESSION['user_id']);

$total_projects = count($user_projects);
$total_tasks = count($user_tasks);
$completed_tasks = 0;
$in_progress_tasks = 0;

foreach($user_tasks as $task_item) {
    if($task_item['status'] == 'completed') {
        $completed_tasks++;
    } elseif($task_item['status'] == 'in_progress') {
        $in_progress_tasks++;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - ProjeYönet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Profil Bilgileri -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user_info['full_name']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($user_info['username']); ?></p>
                        <p class="text-muted">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_info['email']); ?>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-user-tag"></i> 
                            <?php echo $user_info['role'] == 'student' ? 'Öğrenci' : 'Freelancer'; ?>
                        </p>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">İstatistikler</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Toplam Proje
                                <span class="badge bg-primary rounded-pill"><?php echo $total_projects; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Toplam Görev
                                <span class="badge bg-primary rounded-pill"><?php echo $total_tasks; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tamamlanan Görev
                                <span class="badge bg-success rounded-pill"><?php echo $completed_tasks; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Devam Eden Görev
                                <span class="badge bg-warning rounded-pill"><?php echo $in_progress_tasks; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Profil Düzenleme -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profil Düzenle</h5>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_info['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
                            </div>

                            <hr>

                            <h6 class="mb-3">Şifre Değiştir</h6>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mevcut Şifre</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Yeni Şifre Tekrar</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
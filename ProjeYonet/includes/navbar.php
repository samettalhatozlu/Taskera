<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}

// Bildirimler için veritabanı bağlantısı ve kullanıcı kontrolü
if (isset($_SESSION['user_id'])) {
    if (!isset($db)) {
        require_once '../config/config.php';
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    require_once '../models/Notification.php';
    $notificationObj = new Notification($db);
    $unreadNotifications = $notificationObj->getUnread($_SESSION['user_id']);
}
?>

<nav class="navbar navbar-expand-lg" style="background-color: #0d6efd;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
            <i class="bi bi-building me-2"></i>
            Taskera
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo isActive('project_details'); ?>" href="project_details.php">
                        <i class="bi bi-folder me-1"></i> Projelerim
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo isActive('tasks.php'); ?>" href="tasks.php">
                        <i class="bi bi-list-check me-1"></i> Görevlerim
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo isActive('proje_takvimi.php'); ?>" href="proje_takvimi.php">
                        <i class="bi bi-calendar-check me-1"></i> AI Proje Takvimi Oluştur
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo isActive('takvim_listesi.php'); ?>" href="takvim_listesi.php">
                        <i class="bi bi-calendar3 me-1"></i> Mevcut Proje Takvimleri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo isActive('ai_sor.php'); ?>" href="ai_sor.php">
                        <i class="bi bi-robot me-1"></i> AI Asistan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo isActive('messages.php'); ?>" href="messages.php">
                        <i class="bi bi-chat-dots me-1"></i> Mesajlar
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <button class="nav-link dropdown-toggle text-white border-0 bg-transparent" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding:0.5rem 1rem;">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Kullanıcı'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i>Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Çıkış
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<head>
<link rel="icon" type="image/png" href="../assets/css/img/favicon.png">

</head>


<style>
.navbar {
    padding: 0.8rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-brand {
    font-size: 1.4rem;
    font-weight: 600;
}

.nav-link {
    padding: 0.5rem 1rem !important;
    font-weight: 500;
    position: relative;
}

.nav-link.active {
    background-color: rgba(255,255,255,0.1);
    border-radius: 6px;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 6px;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.dropdown-item {
    padding: 0.7rem 1.2rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.navbar-toggler {
    border: none;
    padding: 0.5rem;
}

.navbar-toggler:focus {
    box-shadow: none;
}

@media (max-width: 991.98px) {
    .navbar-nav {
        padding: 1rem 0;
    }
    
    .nav-link {
        padding: 0.8rem 1rem !important;
    }
    
    .navbar-collapse {
        background-color: #0d6efd;
        border-radius: 8px;
        margin-top: 0.5rem;
        padding: 0.5rem;
    }
}
</style> 
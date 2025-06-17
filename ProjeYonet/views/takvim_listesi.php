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

// Takvimleri getir
$stmt = $db->query("
    SELECT pt.*, u.username as creator_name 
    FROM project_timelines pt 
    JOIN users u ON pt.created_by = u.id 
    ORDER BY pt.created_at DESC
");
$timelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Giriş yapan kullanıcı için aktif takvim id'sini bul
$activeTimelineId = null;
$stmt = $db->prepare("SELECT timeline_id FROM timeline_status WHERE user_id = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $activeTimelineId = $row['timeline_id'];
}

// Silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    // Sadece kendi oluşturduğu takvimi silebilsin
    $stmt = $db->prepare("DELETE FROM project_timelines WHERE id = ? AND created_by = ?");
    $stmt->execute([$delete_id, $_SESSION['user_id']]);
    header('Location: takvim_listesi.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Takvimleri - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .timeline-card {
            transition: transform 0.2s;
            border-radius: 16px;
            border: 1px solid #f1f3f4;
            position: relative;
        }
        .timeline-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 24px rgba(30,144,255,0.10);
        }
        .timeline-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .active-timeline-badge {
            position: absolute;
            top: 18px;
            right: 18px;
            z-index: 2;
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
            color: #fff;
            font-weight: 600;
            font-size: 1em;
            border-radius: 999px;
            padding: 7px 18px;
            box-shadow: 0 2px 8px rgba(34,197,94,0.10);
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-calendar3 me-2"></i>Proje Takvimleri</h1>
        <a href="proje_takvimi.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Yeni Takvim Oluştur
        </a>
    </div>

    <?php if (empty($timelines)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Henüz oluşturulmuş proje takvimi bulunmuyor.
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($timelines as $timeline): ?>
        <div class="col">
            <div class="card h-100 timeline-card shadow-sm">
                <?php if ($timeline['id'] == $activeTimelineId): ?>
                    <span class="active-timeline-badge">
                        <i class="bi bi-check-circle me-1"></i> Kullanılan Takvim
                    </span>
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($timeline['project_name']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <?php echo htmlspecialchars($timeline['project_type']); ?>
                    </h6>
                    <p class="card-text">
                        <?php 
                        $description = htmlspecialchars($timeline['project_description']);
                        echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                        ?>
                    </p>
                    <div class="timeline-date mb-3">
                        <div><i class="bi bi-calendar-event me-1"></i>Başlangıç: <?php echo date('d.m.Y', strtotime($timeline['start_date'])); ?></div>
                        <div><i class="bi bi-calendar-check me-1"></i>Bitiş: <?php echo date('d.m.Y', strtotime($timeline['end_date'])); ?></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-person me-1"></i>
                            <?php echo htmlspecialchars($timeline['creator_name']); ?>
                        </small>
                        <div>
                            <a href="takvim_detay.php?id=<?php echo $timeline['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Detaylar
                            </a>
                            <?php if ($timeline['created_by'] == $_SESSION['user_id']): ?>
                            <a href="takvim_listesi.php?delete=<?php echo $timeline['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Bu takvimi silmek istediğinizden emin misiniz?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
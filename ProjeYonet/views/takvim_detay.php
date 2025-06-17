<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: takvim_listesi.php');
    exit();
}

$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Takvim detaylarını getir
$stmt = $db->prepare("
    SELECT pt.*, u.username as creator_name 
    FROM project_timelines pt 
    JOIN users u ON pt.created_by = u.id 
    WHERE pt.id = ?
");
$stmt->execute([$_GET['id']]);
$timeline = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$timeline) {
    header('Location: takvim_listesi.php');
    exit();
}

// Kullanıcıya ait takvim durumu getir
$status_stmt = $db->prepare("SELECT * FROM timeline_status WHERE timeline_id = ? AND user_id = ?");
$status_stmt->execute([$timeline['id'], $_SESSION['user_id']]);
$timeline_status = $status_stmt->fetch(PDO::FETCH_ASSOC);

// Takvimi aktif etme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_timeline'])) {
    // Aynı kullanıcıya ait diğer takvimleri pasif yap
    $stmt = $db->prepare("UPDATE timeline_status SET is_active = 0 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    // Bu takvimi aktif yap veya yoksa ekle
    $stmt = $db->prepare("SELECT * FROM timeline_status WHERE timeline_id = ? AND user_id = ?");
    $stmt->execute([$timeline['id'], $_SESSION['user_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $stmt = $db->prepare("UPDATE timeline_status SET is_active = 1 WHERE id = ?");
        $stmt->execute([$existing['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO timeline_status (timeline_id, user_id, is_active) VALUES (?, ?, 1)");
        $stmt->execute([$timeline['id'], $_SESSION['user_id']]);
    }
    header('Location: takvim_detay.php?id=' . $timeline['id']);
    exit();
}

// İlerleme güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $progress = max(0, min(100, (int)$_POST['progress']));
    $stmt = $db->prepare("UPDATE timeline_status SET progress = ? WHERE timeline_id = ? AND user_id = ?");
    $stmt->execute([$progress, $timeline['id'], $_SESSION['user_id']]);
    header('Location: takvim_detay.php?id=' . $timeline['id']);
    exit();
}

// Düzenleme işlemi (form submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_index'])) {
    $edit_index = (int)$_POST['edit_index'];
    $edit_faz = trim($_POST['edit_faz']);
    $edit_gorev = trim($_POST['edit_gorev']);
    $edit_tarih = trim($_POST['edit_tarih']);
    
    // AI yanıtını satır satır al
    $lines = preg_split('/\r?\n/', $timeline['ai_response']);
    $current_index = -1;
    foreach ($lines as $i => $line) {
        // Faz satırı
        if (preg_match('/^-\s*Faz (\d+):/', $line)) {
            $current_index++;
            if ($current_index === $edit_index) {
                // Tarih varsa güncelle
                if ($edit_tarih) {
                    $lines[$i] = "- $edit_faz: $edit_gorev ($edit_tarih)";
                } else {
                    $lines[$i] = "- $edit_faz: $edit_gorev";
                }
            }
        }
        // Alt görevler
        else if (preg_match('/^-\s*(?!Faz \d+:)(.+)/', $line) && $current_index === $edit_index) {
            // Sadece ilk alt görevi güncelle (örnek için)
            $lines[$i] = "- $edit_gorev";
            break;
        }
    }
    // Güncellenmiş AI yanıtını kaydet
    $new_response = implode("\n", $lines);
    $stmt = $db->prepare("UPDATE project_timelines SET ai_response = ? WHERE id = ?");
    $stmt->execute([$new_response, $timeline['id']]);
    header('Location: takvim_detay.php?id=' . $timeline['id']);
    exit();
}

function getSectionIcon($title) {
    $icons = [
        'Faz' => 'diagram-3',
        'Görev' => 'list-check',
        'Süre' => 'clock',
        'Risk' => 'exclamation-triangle',
        'Başarı' => 'trophy',
        'Kalite' => 'shield-check'
    ];
    foreach ($icons as $key => $icon) {
        if (stripos($title, $key) !== false) {
            return $icon;
        }
    }
    return 'arrow-right-circle';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($timeline['project_name']); ?> - Proje Takvimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .project-header {
            background: linear-gradient(135deg, #0396FF, #0D6EFD);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .timeline-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .timeline-progress-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(30,144,255,0.07);
            border: 1px solid #f1f3f4;
        }
        .progress-update-box {
            background: #f8f9fa;
            border-radius: 10px;
        }
        .btn-gradient-blue {
            background: linear-gradient(90deg, #2563eb 0%, #1e90ff 100%);
            color: #fff;
            border: none;
            transition: background 0.3s, box-shadow 0.3s;
            box-shadow: 0 2px 8px rgba(30,144,255,0.08);
        }
        .btn-gradient-blue:hover, .btn-gradient-blue:focus {
            background: linear-gradient(90deg, #1e90ff 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(30,144,255,0.15);
        }
        .progress {
            background-color: #f1f3f4;
            border-radius: 7px;
        }
        .progress-bar {
            transition: width 0.5s cubic-bezier(.4,0,.2,1);
            font-size: 0.95em;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="project-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><?php echo htmlspecialchars($timeline['project_name']); ?></h1>
                <p class="mb-0">
                    <i class="bi bi-person me-2"></i>
                    Oluşturan: <?php echo htmlspecialchars($timeline['creator_name']); ?>
                </p>
            </div>
            <div>
                <a href="takvim_listesi.php" class="btn btn-light me-2">
                    <i class="bi bi-arrow-left me-2"></i>Geri Dön
                </a>
                <button class="btn btn-light" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Proje Bilgileri</h5>
                    <div class="mb-3">
                        <label class="text-muted d-block">Proje Tipi</label>
                        <strong><?php echo htmlspecialchars($timeline['project_type']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block">Başlangıç Tarihi</label>
                        <strong><?php echo date('d.m.Y', strtotime($timeline['start_date'])); ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block">Bitiş Tarihi</label>
                        <strong><?php echo date('d.m.Y', strtotime($timeline['end_date'])); ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block">Takım Büyüklüğü</label>
                        <strong><?php echo htmlspecialchars($timeline['team_size']); ?> Kişi</strong>
                    </div>
                    <?php if ($timeline['budget']): ?>
                    <div class="mb-3">
                        <label class="text-muted d-block">Bütçe</label>
                        <strong><?php echo htmlspecialchars($timeline['budget']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="text-muted d-block">Oluşturulma Tarihi</label>
                        <strong><?php echo date('d.m.Y H:i', strtotime($timeline['created_at'])); ?></strong>
                    </div>
                    <div class="timeline-progress-card p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark">Takvim İlerlemesi</span>
                            <?php if ($timeline_status['is_active'] ?? false): ?>
                                <span class="badge rounded-pill bg-success px-3 py-2" style="font-size: 1em;">Kullanılan Takvim</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2 small text-secondary">
                            <?php if ($timeline_status['is_active'] ?? false): ?>
                                Bu takvimi kullanıyorsunuz. İlerleme durumunuzu aşağıdan güncelleyebilirsiniz.
                            <?php else: ?>
                                Bu takvimi kullanmak için aşağıdaki butona tıklayın.
                            <?php endif; ?>
                        </div>
                        <div class="position-relative mb-3" style="height: 38px;">
                            <div class="progress" style="height: 14px; border-radius: 7px;">
                                <div class="progress-bar bg-primary" 
                                     role="progressbar" 
                                     style="width: <?php echo $timeline_status['progress'] ?? 0; ?>%;" 
                                     aria-valuenow="<?php echo $timeline_status['progress'] ?? 0; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <span class="position-absolute top-50 start-50 translate-middle fw-bold text-primary" style="font-size: 1.3em;">
                                <?php echo $timeline_status['progress'] ?? 0; ?>%
                            </span>
                        </div>
                        <?php if ($timeline_status['is_active'] ?? false): ?>
                            <div class="progress-update-box p-3 mb-2">
                                <div class="small text-secondary mb-1">
                                    İlerleme yüzdesini, tamamladığınız faz ve görevlerin toplamına göre güncelleyiniz.
                                    <div class="fst-italic text-muted" style="font-size: 0.95em;">
                                        Örneğin, 5 fazdan 2'si tamamlandıysa <b>%40</b> yazabilirsiniz.
                                    </div>
                                </div>
                                <form method="post" action="">
                                    <div class="input-group input-group-lg">
                                        <input type="number" name="progress" min="0" max="100" class="form-control" value="<?php echo $timeline_status['progress']; ?>" aria-label="İlerleme Yüzdesi">
                                        <button type="submit" name="update_progress" class="btn btn-gradient-blue w-100" style="font-weight:600;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Güncelle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="post" action="" class="mt-2">
                                <input type="hidden" name="activate_timeline" value="1">
                                <button type="submit" class="btn btn-outline-success w-100 py-2">
                                    <i class="bi bi-check-circle me-2"></i>Bu Takvimi Kullan
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3 text-primary">
                        <i class="bi bi-info-circle me-2"></i>Proje Açıklaması
                    </h5>
                    <p class="mb-0 lead"><?php echo nl2br(htmlspecialchars($timeline['project_description'])); ?></p>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3 text-success">
                        <i class="bi bi-calendar2-week me-2"></i>AI Tarafından Oluşturulan Takvim
                    </h5>
                    <div class="accordion" id="timelineAccordion">
                    <?php
                    $ai_response = $timeline['ai_response'];
                    $sections = preg_split('/\n\n+/', $ai_response);
                    $edit_index = -1;
                    foreach ($sections as $secIndex => $section) {
                        $lines = preg_split('/\r?\n/', trim($section));
                        if (count($lines) === 0) continue;
                        // Başlık satırı (ör: 1. Proje Fazları ve Önemli Kilometre Taşları)
                        if (preg_match('/^\d+\.\s*(.+)$/', $lines[0], $m)) {
                            $title = $m[1];
                            $sectionId = 'section-' . ($secIndex + 1);
                            echo '<div class="accordion-item mb-3">';
                            echo '<h2 class="accordion-header" id="heading' . $sectionId . '">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#' . $sectionId . '" aria-expanded="true">
                                        <i class="bi bi-' . getSectionIcon($title) . ' me-2"></i>' . htmlspecialchars($title) . '
                                    </button>
                                </h2>';
                            echo '<div id="' . $sectionId . '" class="accordion-collapse collapse show" data-bs-parent="#timelineAccordion">';
                            echo '<div class="accordion-body">';
                            // Alt maddeler varsa liste olarak göster
                            $content = implode("\n", array_slice($lines, 1));
                            if (preg_match_all('/^[-•]\s+(.+)/m', $content, $items)) {
                                echo '<ul class="list-group">';
                                foreach ($items[1] as $itemIndex => $item) {
                                    $edit_index++;
                                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                    echo '<span>' . htmlspecialchars($item) . '</span>';
                                    echo '<button class="btn btn-sm btn-outline-secondary edit-btn" 
                                            data-index="' . $edit_index . '" 
                                            data-faz="' . htmlspecialchars($title) . '" 
                                            data-gorev="' . htmlspecialchars($item) . '" 
                                            data-tarih=""
                                            data-bs-toggle="modal" data-bs-target="#editModal">Düzenle</button>';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            } else {
                                // Düz metin
                                echo '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
                            }
                            echo '</div></div></div>';
                        }
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Düzenle Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Düzenle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_index" id="edit_index">
          <div class="mb-3">
            <label for="edit_faz" class="form-label">Faz</label>
            <input type="text" class="form-control" name="edit_faz" id="edit_faz" required>
          </div>
          <div class="mb-3">
            <label for="edit_gorev" class="form-label">Görev</label>
            <input type="text" class="form-control" name="edit_gorev" id="edit_gorev" required>
          </div>
          <div class="mb-3">
            <label for="edit_tarih" class="form-label">Tarih(ler)</label>
            <input type="text" class="form-control" name="edit_tarih" id="edit_tarih" placeholder="2025-05-12 - 2025-05-15">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_index').value = this.getAttribute('data-index');
        document.getElementById('edit_faz').value = this.getAttribute('data-faz');
        document.getElementById('edit_gorev').value = this.getAttribute('data-gorev');
        document.getElementById('edit_tarih').value = this.getAttribute('data-tarih');
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
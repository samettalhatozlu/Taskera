<?php
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/Project.php';
require_once '../models/Task.php';


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
$project = new Project($db);
$task = new Task($db);
$user_info = $user->getUserById($_SESSION['user_id']);

$query_users = "SELECT id, full_name FROM users WHERE id != :user_id"; 
$stmt_users = $db->prepare($query_users);
$stmt_users->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Fetch projects for the dropdown
$query_projects = "SELECT id, title AS name FROM projects"; 
$stmt_projects = $db->prepare($query_projects);
$stmt_projects->execute();
$projects = $stmt_projects->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    $receiver_id = $_POST['receiver_id'];
    $project_id = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
    $message = trim($_POST['message']);

    if (!empty($message) && !empty($receiver_id)) {
        try {
            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, project_id, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $receiver_id, $project_id, $message]);
            $success = 'Mesajınız başarıyla gönderildi.';
        } catch (PDOException $e) {
            $error = 'Mesaj gönderilirken bir hata oluştu: ' . $e->getMessage();
        }
    } else {
        $error = 'Mesaj ve alıcı alanları zorunludur.';
    }
}

// Mesajları çekmek için sorgu
$query = "SELECT m.*, u.full_name AS receiver_name FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = :user_id OR m.receiver_id = :user_id ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mesaj silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    // Sadece gönderen kendi mesajını silebilsin
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$delete_id, $_SESSION['user_id']]);
    header('Location: messages.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlar - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .messages-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        
        .message-card {
            transition: transform 0.2s;
        }
        
        .message-card:hover {
            transform: translateY(-2px);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .message-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .unread-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .message-bubble {
            border-radius: 1.2rem 1.2rem 1.2rem 0.4rem;
            word-break: break-word;
            font-size: 1.05rem;
            position: relative;
            transition: box-shadow 0.2s;
        }
        .message-bubble:hover {
            box-shadow: 0 4px 16px rgba(13,110,253,0.10);
        }
        .bg-primary.text-white {
            background: linear-gradient(135deg, #0396FF, #0D6EFD) !important;
        }
        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .bg-white.text-primary { border: 2px solid #0d6efd; }
        .bg-primary.text-white { border: 2px solid #fff; }
        @media (max-width: 768px) {
            .messages-list .message-bubble { max-width: 100%; }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container messages-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-chat-dots me-2"></i>
            Mesajlar
        </h2>
        <button class="btn btn-primary rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
            <i class="bi bi-plus-lg me-2"></i>
            Yeni Mesaj
        </button>
    </div>

    <div class="messages-list">
        <?php if (empty($messages)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>Henüz mesaj yok. Sağ üstten yeni mesaj gönderebilirsin.
            </div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php
                $isMine = $message['sender_id'] == $_SESSION['user_id'];
                $align = $isMine ? 'justify-content-end' : 'justify-content-start';
                $bubble = $isMine ? 'bg-primary text-white' : 'bg-light';
                $name = $isMine ? ($user_info['full_name'] ?? 'Ben') : htmlspecialchars($message['receiver_name']);
                $icon = $isMine ? 'bi-person-circle' : 'bi-person';
                $initials = strtoupper(mb_substr($name, 0, 1));
                // Okundu bilgisi örnek (her zaman okundu gösteriyoruz, gerçek okundu için db'de alan gerekir)
                $read_status = $isMine ? '<span class="text-success small ms-2">✓ Okundu</span>' : '';
                ?>
                <div class="d-flex <?php echo $align; ?> mb-3">
                    <div class="message-bubble <?php echo $bubble; ?> p-3 rounded-4 shadow-sm position-relative" style="max-width: 70%;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-circle me-2 <?php echo $isMine ? 'bg-white text-primary' : 'bg-primary text-white'; ?>">
                                <?php echo $initials; ?>
                            </div>
                            <span class="fw-bold small"><?php echo $name; ?></span>
                            <?php echo $read_status; ?>
                            <?php if ($isMine): ?>
                                <a href="messages.php?delete=<?php echo $message['id']; ?>" class="btn btn-sm btn-link text-danger ms-2" title="Sil" onclick="return confirm('Bu mesajı silmek istediğinizden emin misiniz?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        <div class="text-end small text-muted" style="font-size: 0.85em;">
                            <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Yeni Mesaj Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendMessageModalLabel">Yeni Mesaj Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="messages.php">
                    <input type="hidden" name="action" value="send_message">
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label">Alıcı</label>
                        <select class="form-select" id="receiver_id" name="receiver_id" required>
                            <option value="">Seçin...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Proje</label>
                        <select class="form-select" id="project_id" name="project_id">
                            <option value="">Seçin...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Mesaj</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
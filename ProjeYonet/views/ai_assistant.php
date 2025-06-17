<?php
// views/ai_assistant.php

require_once '../config/config.php';
require_once '../models/User.php';

session_start();

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
$user_info = $user->getUserById($_SESSION['user_id']);

$response = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    $question = $_POST['question'];
    $python_script = dirname(__DIR__) . '/helpers/gemini_api.py';

    $request_data = ['input' => $question];
    $json_data = json_encode($request_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $encoded_input = base64_encode($json_data);

    $command = sprintf('python "%s" "%s"', $python_script, addslashes($encoded_input));
    $output = shell_exec($command . ' 2>&1');

    if ($output === null) {
        $response = ['status' => 'error', 'error' => 'Python scripti çalıştırılamadı'];
    } else {
        $decoded_output = base64_decode($output);
        if ($decoded_output === false) {
            $response = ['status' => 'error', 'error' => 'Base64 çözme hatası'];
        } else {
            $response = json_decode($decoded_output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response = [
                    'status' => 'error',
                    'error' => 'JSON ayrıştırma hatası: ' . json_last_error_msg(),
                    'raw_output' => $output
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>AI Asistan - Proje Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Proje Yönetim Sistemi</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Ana Sayfa</a></li>
                <li class="nav-item"><a class="nav-link active" href="ai_assistant.php">AI Asistan</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header"><h4>AI Asistan</h4></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="question" class="form-label">Sorunuzu yazın:</label>
                            <textarea class="form-control" id="question" name="question" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Gönder</button>
                    </form>

                    <?php if (isset($response)): ?>
                        <div class="mt-4">
                            <h5>Yanıt:</h5>
                            <?php if ($response['status'] === 'success'): ?>
                                <div class="alert alert-success">
                                    <?php echo nl2br(htmlspecialchars($response['response'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <strong>Hata:</strong> <?php echo htmlspecialchars($response['error']); ?>
                                    <?php if (isset($response['raw_output'])): ?>
                                        <hr>
                                        <small>Ham çıktı: <?php echo htmlspecialchars($response['raw_output']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

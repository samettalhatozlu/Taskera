<?php
require_once '../config/config.php';
require_once '../models/GeminiHelper.php';

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$risk_analysis = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_type = $_POST['project_type'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($project_type) || empty($description)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        try {
            $gemini = new GeminiHelper(GEMINI_API_KEY);
            $project_details = [
                'type' => $project_type,
                'description' => $description
            ];
            $risk_analysis = $gemini->getProjectRiskAnalysis($project_details);
            $success = 'Risk analizi başarıyla oluşturuldu.';
        } catch (Exception $e) {
            $error = 'Risk analizi oluşturulurken bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Risk Analizi - ProjeYönet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Proje Risk Analizi</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="project_type" class="form-label">Proje Tipi</label>
                                <input type="text" class="form-control" id="project_type" name="project_type" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Proje Açıklaması</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-chart-line me-2"></i>Risk Analizi Oluştur
                            </button>
                        </form>

                        <?php if ($risk_analysis): ?>
                            <div class="mt-4">
                                <h5 class="mb-3">Risk Analizi Sonuçları</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($risk_analysis)); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
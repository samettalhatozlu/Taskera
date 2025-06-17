<?php
require_once '../config/config.php';
require_once '../models/User.php';

$user = new User($db);
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if(empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurunuz.';
    } else {
        if($user->login($email, $password)) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['role'] = $user->getRole();
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Geçersiz email veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskera'ya Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #0d6efd 0%, #2193b0 100%); min-height: 100vh;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card" style="border-radius:2rem; background:rgba(255,255,255,0.95); box-shadow:0 4px 24px rgba(33,147,176,0.15); padding-top:2rem; padding-bottom:2rem;">
                    <div style="display:flex; justify-content:center; align-items:center; margin-bottom:1.2rem; width:100%;">
                        <img src="../assets/css/img/logo.png" alt="Taskera Logo" style="width:60px; height:60px; border-radius:50%; box-shadow:0 2px 12px rgba(33,147,176,0.18); background:white; padding:6px; object-fit:contain; border:2.5px solid #2193b0;">
                    </div>
                    <div class="card-body">
                        <h2 class="text-center mb-4">Taskera'ya Giriş Yap</h2>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" style="padding: 1.5rem 0 0 0;">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" required style="border-radius: 1.5rem; box-shadow: 0 1px 6px rgba(33,147,176,0.07); padding: 0.75rem 1.25rem; font-size: 1.08rem;">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required style="border-radius: 1.5rem; box-shadow: 0 1px 6px rgba(33,147,176,0.07); padding: 0.75rem 1.25rem; font-size: 1.08rem;">
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary" style="border-radius: 2rem; font-weight: 600; font-size: 1.1rem; padding: 0.7rem 0; box-shadow: 0 2px 8px rgba(13,110,253,0.10); background: linear-gradient(90deg, #0d6efd 0%, #2193b0 100%); border: none;">Giriş Yap</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
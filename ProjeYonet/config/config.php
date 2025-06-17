<?php
// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Veritabanı bağlantı bilgileri
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_NAME', getenv('DB_NAME') ?: 'projeyonet');

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? 1 : 0);

// Session başlatma kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısı
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// XSS koruması için fonksiyon
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// CSRF token oluşturma
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['csrf_tokens'])) {
    $_SESSION['csrf_tokens'] = array();
}

function generateCSRFToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = time();
    return $token;
}

function validateCSRFToken($token) {
    if (isset($_SESSION['csrf_tokens'][$token])) {
        $tokenTime = $_SESSION['csrf_tokens'][$token];
        $currentTime = time();
        
        // Token'ın 1 saat geçerlilik süresi
        if ($currentTime - $tokenTime <= 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
            return true;
        }
    }
    return false;
}

// Eski token'ları temizle
function cleanOldTokens() {
    $currentTime = time();
    foreach ($_SESSION['csrf_tokens'] as $token => $time) {
        if ($currentTime - $time > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
    }
}

cleanOldTokens();
?> 
<?php
require_once '../config/config.php';

// Oturumu sonlandır
session_destroy();
 
// Ana sayfaya yönlendir
header('Location: index.php');
exit;
?> 
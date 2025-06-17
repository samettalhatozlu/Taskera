<?php
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
?>
<div style="position:fixed; top:20px; right:40px; z-index:2000;">
    <div class="dropdown">
        <button class="btn btn-light position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:50%; box-shadow:0 2px 8px rgba(13,110,253,0.08);">
            <i class="bi bi-bell" style="font-size:1.7rem;"></i>
            <?php if(count($unreadNotifications) > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo count($unreadNotifications); ?>
                </span>
            <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notificationDropdown" style="min-width:320px;">
            <li class="dropdown-header fw-bold">Bildirimler</li>
            <?php if(count($unreadNotifications) == 0): ?>
                <li><span class="dropdown-item text-muted">Yeni bildirimin yok.</span></li>
            <?php else: ?>
                <?php foreach($unreadNotifications as $n): ?>
                    <li><span class="dropdown-item small"><?php echo $n['message']; ?><br><span class="text-muted small"><?php echo date('d.m.Y H:i', strtotime($n['created_at'])); ?></span></span></li>
                <?php endforeach; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" class="text-center" action="/ProjeYonet/views/index.php">
                        <button type="submit" name="mark_read" class="btn btn-link small">Tümünü okundu olarak işaretle</button>
                    </form>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php } ?> 
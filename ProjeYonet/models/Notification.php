<?php
class Notification {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function add($user_id, $message) {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        return $stmt->execute([$user_id, $message]);
    }
    public function getUnread($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function markAllRead($user_id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
?> 
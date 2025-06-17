<?php
class Project {
    private $db;
    private $id;
    private $title;
    private $description;
    private $owner_id;
    private $status;
    private $start_date;
    private $end_date;

    public function __construct($db) {
        $this->db = $db;
    }

    // Proje oluşturma
    public function create($title, $description, $owner_id, $start_date = null, $end_date = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO projects (title, description, owner_id, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $owner_id, $start_date, $end_date]);
            
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Proje güncelleme
    public function update($id, $title, $description, $status, $start_date = null, $end_date = null) {
        try {
            $stmt = $this->db->prepare("UPDATE projects SET title = ?, description = ?, status = ?, start_date = ?, end_date = ? WHERE id = ?");
            return $stmt->execute([$title, $description, $status, $start_date, $end_date, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Proje silme
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Proje detaylarını getirme
    public function getProjectById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcının projelerini getirme
    public function getUserProjects($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM projects WHERE owner_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Tüm projeleri getirme
    public function getAllProjects() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM projects ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Proje durumunu güncelleme
    public function updateStatus($id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE projects SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Proje ilerleme durumunu hesaplama
    public function calculateProgress($project_id) {
        try {
            // Toplam görev sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
            $stmt->execute([$project_id]);
            $total_tasks = $stmt->fetchColumn();

            // Tamamlanan görev sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = 'completed'");
            $stmt->execute([$project_id]);
            $completed_tasks = $stmt->fetchColumn();

            if($total_tasks > 0) {
                return round(($completed_tasks / $total_tasks) * 100);
            }
            return 0;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Proje istatistiklerini getirme
    public function getProjectStats($project_id) {
        try {
            $stats = array();
            
            // Toplam görev sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
            $stmt->execute([$project_id]);
            $stats['total_tasks'] = $stmt->fetchColumn();

            // Tamamlanan görev sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = 'completed'");
            $stmt->execute([$project_id]);
            $stats['completed_tasks'] = $stmt->fetchColumn();

            // Devam eden görev sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = 'in_progress'");
            $stmt->execute([$project_id]);
            $stats['in_progress_tasks'] = $stmt->fetchColumn();

            // Bekleyen görev sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = 'todo'");
            $stmt->execute([$project_id]);
            $stats['pending_tasks'] = $stmt->fetchColumn();

            return $stats;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcının toplam proje sayısını döndür
    public function getUserProjectCount($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM projects WHERE owner_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch(PDOException $e) {
            return 0;
        }
    }
}
?> 
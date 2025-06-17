<?php
class Task {
    private $db;
    private $id;
    private $project_id;
    private $title;
    private $description;
    private $assigned_to;
    private $status;
    private $priority;
    private $due_date;

    public function __construct($db) {
        $this->db = $db;
    }

    // Görev oluşturma
    public function create($project_id, $title, $description, $assigned_to = null, $priority = 'medium', $due_date = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $title, $description, $assigned_to, $priority, $due_date]);
            
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev güncelleme
    public function update($id, $title, $description, $assigned_to, $status, $priority, $due_date) {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET title = ?, description = ?, assigned_to = ?, status = ?, priority = ?, due_date = ? WHERE id = ?");
            return $stmt->execute([$title, $description, $assigned_to, $status, $priority, $due_date, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev silme
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getTaskById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.*, 
                    u.username AS assigned_username, 
                    p.title AS project_title
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Projedeki görevleri getirme
    public function getProjectTasks($project_id) {
        try {
            $stmt = $this->db->prepare("SELECT t.*, u.username as assigned_username FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.created_at DESC");
            $stmt->execute([$project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcının görevlerini getirme
    public function getUserTasks($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT t.*, p.title as project_title FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? ORDER BY t.created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev durumunu güncelleme
    public function updateStatus($id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev atama
    public function assignTask($id, $user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET assigned_to = ? WHERE id = ?");
            return $stmt->execute([$user_id, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev önceliğini güncelleme
    public function updatePriority($id, $priority) {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET priority = ? WHERE id = ?");
            return $stmt->execute([$priority, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev bitiş tarihini güncelleme
    public function updateDueDate($id, $due_date) {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET due_date = ? WHERE id = ?");
            return $stmt->execute([$due_date, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev yorumlarını getirme
    public function getTaskComments($task_id) {
        try {
            $stmt = $this->db->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at DESC");
            $stmt->execute([$task_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Görev yorumu ekleme
    public function addComment($task_id, $user_id, $comment) {
        try {
            $stmt = $this->db->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            return $stmt->execute([$task_id, $user_id, $comment]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Göreve dosya ekleme
    public function addFile($task_id, $project_id, $uploaded_by, $file_name, $file_path, $file_type, $file_size) {
        $sql = "INSERT INTO files (task_id, project_id, uploaded_by, file_name, file_path, file_type, file_size) 
                VALUES (:task_id, :project_id, :uploaded_by, :file_name, :file_path, :file_type, :file_size)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':uploaded_by', $uploaded_by);
        $stmt->bindParam(':file_name', $file_name);
        $stmt->bindParam(':file_path', $file_path);
        $stmt->bindParam(':file_type', $file_type);
        $stmt->bindParam(':file_size', $file_size);
        return $stmt->execute();
    }

    // Görevin dosyalarını getirme
    public function getTaskFiles($task_id) {
        $sql = "SELECT f.*, u.username as uploaded_by FROM files f LEFT JOIN users u ON f.uploaded_by = u.id WHERE f.task_id = :task_id ORDER BY f.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 
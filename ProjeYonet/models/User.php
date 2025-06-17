<?php
class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $full_name;
    private $role;

    public function __construct($db) {
        $this->db = $db;
    }

    // Kullanıcı kaydı
    public function register($username, $email, $password, $full_name, $role) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password, $full_name, $role]);

            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcı girişi
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user && password_verify($password, $user['password'])) {
                $this->id = $user['id'];
                $this->username = $user['username'];
                $this->email = $user['email'];
                $this->full_name = $user['full_name'];
                $this->role = $user['role'];

                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcı bilgilerini güncelleme
    public function updateProfile($id, $username, $email, $full_name) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ?, full_name = ? WHERE id = ?");
            return $stmt->execute([$username, $email, $full_name, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Şifre değiştirme
    public function changePassword($id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            return $stmt->execute([$hashed_password, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcı bilgilerini getirme (ID'ye göre)
    public function getUserById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Tüm kullanıcıları getirme (EKLENDİ)
    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Kullanıcı adı kontrolü
    public function checkUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Email kontrolü
    public function checkEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Getter metodları
    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getFullName() {
        return $this->full_name;
    }

    public function getRole() {
        return $this->role;
    }
}
?>
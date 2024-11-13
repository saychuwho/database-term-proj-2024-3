<?php

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($username, $email, $password, $role) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$username, $email, $password_hash, $role]);
    }

    public function getByUsername($username) {
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function authenticate($username, $password) {
        $user = $this->getByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }
}
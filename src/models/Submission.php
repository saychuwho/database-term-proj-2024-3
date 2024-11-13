<?php

class Submission {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 새로운 제출물 생성
    public function create($user_id, $assignment_id, $code) {
        $query = "INSERT INTO submissions (user_id, assignment_id, code, status) VALUES (?, ?, ?, 'submitted')";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$user_id, $assignment_id, $code]);
    }

    // 특정 과제와 유저의 제출물 가져오기
    public function getByAssignmentAndUser($assignment_id, $user_id) {
        $query = "SELECT * FROM submissions WHERE assignment_id = ? AND user_id = ? ORDER BY submitted_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$assignment_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ID로 제출물 하나 가져오기
    public function getById($id) {
        $query = "SELECT * FROM submissions WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 채점 업데이트
    public function updateGrade($id, $score, $feedback) {
        $query = "UPDATE submissions SET score = ?, feedback = ?, status = 'graded' WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$score, $feedback, $id]);
    }

    // 특정 사용자의 제출물 가져오기
    public function getByUserId($userId) {
        $query = "SELECT s.*, a.title as assignment_title 
                  FROM submissions s 
                  JOIN assignments a ON s.assignment_id = a.id 
                  WHERE s.user_id = ? 
                  ORDER BY s.submitted_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 채점할 제출물 가져오기
    public function getSubmissionsToGrade() {
        $query = "SELECT s.id, s.user_id, s.assignment_id, s.submitted_at, s.status, 
                         u.username as student_name, a.title as assignment_title
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                  JOIN assignments a ON s.assignment_id = a.id
                  WHERE s.status = 'submitted'
                  ORDER BY s.submitted_at ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // **새로 추가한 메서드: 모든 제출물 가져오기**
    public function getAllSubmissions() {
        $query = "SELECT s.id, s.assignment_id, a.title AS assignment_title, s.submitted_at, s.status, s.score, 
                         s.feedback, u.username AS student_name
                  FROM submissions s
                  JOIN assignments a ON s.assignment_id = a.id
                  JOIN users u ON s.user_id = u.id
                  ORDER BY a.title, s.submitted_at";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

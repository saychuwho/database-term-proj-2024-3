<?php

class Assignment {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($title, $description, $due_date, $max_score, $test_cases = []) {
        $this->db->beginTransaction();
        try {
            $query = "INSERT INTO assignments (title, description, due_date, max_score) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$title, $description, $due_date, $max_score]);
            $assignmentId = $this->db->lastInsertId();
    
            if (!empty($test_cases)) {
                $query = "INSERT INTO test_cases (assignment_id, input, expected_output, is_secret) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($query);
                foreach ($test_cases as $testCase) {
                    $stmt->execute([$assignmentId, $testCase['input'], $testCase['expected_output'], $testCase['is_secret']]);
                }
            }
    
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public function getAll() {
        $query = "SELECT * FROM assignments ORDER BY due_date";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $includeSecretTestCases = false) {
        $query = "SELECT * FROM assignments WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($assignment) {
            $query = "SELECT id, input, expected_output, is_secret FROM test_cases WHERE assignment_id = ?";
            if (!$includeSecretTestCases) {
                $query .= " AND is_secret = FALSE";
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $assignment['test_cases'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    
        return $assignment;
    }

    public function getTestCases($assignmentId, $includeSecret = false) {
        $query = "SELECT id, input, expected_output, is_secret FROM test_cases WHERE assignment_id = ?";
        if (!$includeSecret) {
            $query .= " AND is_secret = FALSE";
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute([$assignmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $title, $description, $due_date, $max_score) {
        $query = "UPDATE assignments SET title = ?, description = ?, due_date = ?, max_score = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$title, $description, $due_date, $max_score, $id]);
    }

    public function delete($id) {
        $this->db->beginTransaction();
        try {
            $query = "DELETE FROM test_cases WHERE assignment_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $query = "DELETE FROM assignments WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
}
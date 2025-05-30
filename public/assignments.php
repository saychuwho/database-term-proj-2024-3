<?php

require_once 'C:/xampp/htdocs/OOP/src/config/database.php';
require_once 'C:/xampp/htdocs/OOP/src/models/Assignment.php';
require_once 'C:/xampp/htdocs/OOP/src/middleware/auth.php';

$config = require 'C:/xampp/htdocs/OOP/src/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']}";
$pdo = new PDO($dsn, $config['user'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$assignment = new Assignment($pdo);

requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $isInstructor = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'instructor';
            $result = $assignment->getById($_GET['id'], $isInstructor);
            echo json_encode($result);
        } 
        else if (isset($_GET['with_stats'])) {  
            $result = $assignment->getAllWithAverageScores();
            echo json_encode($result);
        }
        else {
            $result = $assignment->getAll();
            echo json_encode($result);
        }
        break;

    case 'POST':
        requireRole('instructor');
        $data = json_decode(file_get_contents('php://input'), true);
        if ($assignment->create($data['title'], $data['description'], $data['due_date'], $data['max_score'], $data['test_cases'] ?? [])) {
            http_response_code(201);
            echo json_encode(['message' => 'Assignment created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error creating assignment']);
        }
        break;

    case 'PUT':
        requireRole('instructor');
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id']) && isset($data['title']) && isset($data['description']) 
            && isset($data['due_date']) && isset($data['max_score'])) {
            if ($assignment->update($data['id'], $data['title'], $data['description'], 
                                    $data['due_date'], $data['max_score'])) {
                echo json_encode(['message' => 'Assignment updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Error updating assignment']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields']);
        }
        break;

    case 'DELETE':
        requireRole('instructor');
        $id = $_GET['id'] ?? null;
        try {
            if ($id && $assignment->delete($id)) {
                header('Content-Type: application/json');
                echo json_encode(['message' => 'Assignment deleted successfully']);
            } else {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['message' => 'Error deleting assignment']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
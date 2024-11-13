<?php

require_once 'C:/xampp/htdocs/OOP/src/config/database.php';
require_once 'C:/xampp/htdocs/OOP/src/models/Submission.php';
require_once 'C:/xampp/htdocs/OOP/src/middleware/auth.php';
require_once 'C:/xampp/htdocs/OOP/src/utils/Validator.php';

$config = require 'C:/xampp/htdocs/OOP/src/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']}";
$pdo = new PDO($dsn, $config['user'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$submission = new Submission($pdo);

requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handleSubmissionCreation($submission);
        break;

    case 'GET':
        if (isset($_GET['all']) && $_GET['all'] === 'true' && $_SESSION['role'] === 'instructor') {
            // 모든 제출물 가져오기 (교수자만 가능)
            $results = $submission->getAllSubmissions();
        } else if (isset($_GET['to_grade']) && $_GET['to_grade'] === 'true' && $_SESSION['role'] === 'instructor') {
            // 채점할 제출물 가져오기 (교수자만 가능)
            $results = $submission->getSubmissionsToGrade();
        } else {
            // 학생 제출물 가져오기
            $results = $submission->getByUserId($_SESSION['user_id']);
        }
        echo json_encode($results);
        break;
        

    case 'PUT':
        handleSubmissionGrading($submission);
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}

function handleSubmissionCreation($submission) {
    $data = json_decode(file_get_contents('php://input'), true);
    $errors = Validator::validateSubmission($data);
    
    if (empty($errors)) {
        if ($submission->create($_SESSION['user_id'], $data['assignment_id'], $data['code'])) {
            http_response_code(201);
            echo json_encode(['message' => 'Submission created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error creating submission']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
    }
}

function sendJsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function handleSubmissionGrading($submission) {
    requireRole('instructor');
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['message' => 'Invalid JSON input'], 400);
    }

    $errors = Validator::validateGrading($data);
    
    if (!empty($errors)) {
        sendJsonResponse(['message' => 'Validation failed', 'errors' => $errors], 400);
    }

    if ($submission->updateGrade($data['id'], $data['score'], $data['feedback'])) {
        sendJsonResponse(['message' => 'Submission graded successfully']);
    } else {
        sendJsonResponse(['message' => 'Error grading submission'], 500);
    }
}

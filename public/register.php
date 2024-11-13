<?php

require_once 'C:/xampp/htdocs/OOP/src/config/database.php';
require_once 'C:/xampp/htdocs/OOP/src/models/User.php';

define('INSTRUCTOR_SECRET_CODE', 'HASS2024');

# database file보면 알 수 있는데, config가 일종의 함수 포인터인듯. 
$config = require 'C:/xampp/htdocs/OOP/src/config/database.php';
# dsn에다가 DB정보 저장 
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']}";
# PHP에서 데이터베이스와 상호작용하기 위한 객체 
$pdo = new PDO($dsn, $config['user'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = new User($pdo);

# HTTP 요청이 POST 이면 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['username']) && isset($data['email']) && isset($data['password']) && isset($data['role'])) {
        if ($data['role'] === 'instructor') {
            if (!isset($data['secretCode']) || $data['secretCode'] !== INSTRUCTOR_SECRET_CODE) {
                http_response_code(403);
                echo json_encode(['message' => 'Invalid secret code for instructor registration']);
                exit;
            }
        }

        if ($user->create($data['username'], $data['email'], $data['password'], $data['role'])) {
            http_response_code(201);
            echo json_encode(['message' => 'User created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error creating user']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
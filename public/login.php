<?php

require_once 'C:/xampp/htdocs/OOP/src/config/database.php';
require_once 'C:/xampp/htdocs/OOP/src/models/User.php';

# DB 설정 
$config = require 'C:/xampp/htdocs/OOP/src/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']}";
$pdo = new PDO($dsn, $config['user'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = new User($pdo);

# 네트워크에서 배우는 그 POST METHOD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['username']) && isset($data['password'])) {
        # user 쪽에 구현해놓은 그 authenticate function
        $authenticated_user = $user->authenticate($data['username'], $data['password']);
        if ($authenticated_user) {
            // Start a session and store user information (웹 쿠키)
            // 여기서 _SESSION, _SERVER등등 다 다른 파일에서 관리하는 GLOBAL VARIABLE임. 
            // 세션 시작되면 새 세션이 만들어지고, 고유 id가 부여되며, 고유 id를 클라이언트가 가지고 있다가 다시 접속할 때 보냄. 
            // 우리 서버는 그 고유 id랑 밑에 정보들을 mapping해뒀다가 유저를 알아보는 것 
            session_start();
            $_SESSION['user_id'] = $authenticated_user['id'];
            $_SESSION['username'] = $authenticated_user['username'];
            $_SESSION['role'] = $authenticated_user['role'];

            http_response_code(200);
            echo json_encode(['message' => 'Login successful', 'user' => [
                'id' => $authenticated_user['id'],
                'username' => $authenticated_user['username'],
                'role' => $authenticated_user['role']
            ]]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Missing username or password']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
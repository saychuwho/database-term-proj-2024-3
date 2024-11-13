<?php

function requireAuth() {
    # 세션정보는 그냥 서버측에서 변수같은데 저장해놓는게 아니라, 서버 파일에 적어놓음.  
    # 새 USER라면 새 SESSION이 만들어지고, 
    # 이미 로그인된 유저라면 쿠키 SESSION ID를 통해서 이전 SESSION을 불러옴. 
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
        exit;
    }
}

function requireRole($role) {
    requireAuth();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden']);
        exit;
    }
}
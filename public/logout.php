<?php
session_start();

$_SESSION = array();

session_destroy();

http_response_code(200);
echo json_encode(['message' => 'Logged out successfully']);
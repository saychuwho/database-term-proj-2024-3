<?php
session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ]
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
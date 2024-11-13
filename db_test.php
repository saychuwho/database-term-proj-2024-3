<?php
// db_test.php

$config = require_once 'src/config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']}";
    $pdo = new PDO($dsn, $config['user'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully to AWS RDS instance";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "<br>Number of users in the database: " . $userCount;

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
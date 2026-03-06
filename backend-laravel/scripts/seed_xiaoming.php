<?php

declare(strict_types=1);

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=hsping_plus', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$passwordHash = password_hash('123456', PASSWORD_BCRYPT);
$now = date('Y-m-d H:i:s');

$sql = 'INSERT INTO users (name, email, password, created_at, updated_at)
        VALUES (:name, :email, :password, :created_at, :updated_at)
        ON DUPLICATE KEY UPDATE
          name = VALUES(name),
          password = VALUES(password),
          updated_at = VALUES(updated_at)';

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':name' => 'xiaoming',
    ':email' => 'xiaoming@123.com',
    ':password' => $passwordHash,
    ':created_at' => $now,
    ':updated_at' => $now,
]);

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
$countStmt->execute([':email' => 'xiaoming@123.com']);
$count = (int) $countStmt->fetchColumn();

echo "user_count={$count}";

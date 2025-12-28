<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    echo json_encode([]);
    exit;
}

try {
    // Takipçileri ve kullanıcı bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.profile_pic 
        FROM follows f
        JOIN users u ON f.follower_id = u.id
        WHERE f.followed_id = ?
        ORDER BY f.id DESC
    ");
    $stmt->execute([$user_id]);
    $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($followers);
} catch (PDOException $e) {
    echo json_encode([]);
}
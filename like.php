<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? 0;

if ($post_id > 0) {
    try {
        // Zaten beğenilmiş mi kontrol et
        $check = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $check->execute([$user_id, $post_id]);
        $existing = $check->fetch();

        if ($existing) {
            // Beğeniyi kaldır
            $del = $pdo->prepare("DELETE FROM likes WHERE id = ?");
            $del->execute([$existing['id']]);
            $action = 'unliked';
        } else {
            // Beğeni ekle
            $ins = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $ins->execute([$user_id, $post_id]);
            $action = 'liked';
        }

        // Yeni sayıyı al
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $countStmt->execute([$post_id]);
        $new_count = $countStmt->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'action' => $action,
            'new_count' => $new_count
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error']);
    }
}
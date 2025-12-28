<?php
require_once 'db.php';
session_start();

// JSON yanıt header'ı
header('Content-Type: application/json');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$follower_id = $_SESSION['user_id'];
$followed_id = isset($_POST['followed_id']) ? intval($_POST['followed_id']) : 0;

if ($followed_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz kullanıcı ID.']);
    exit;
}

// Kişi kendisini takip edemez
if ($follower_id === $followed_id) {
    echo json_encode(['status' => 'error', 'message' => 'Kendinizi takip edemezsiniz.']);
    exit;
}

try {
    // Mevcut takip durumunu kontrol et
    $check = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
    $check->execute([$follower_id, $followed_id]);
    $follow_record = $check->fetch();

    if ($follow_record) {
        // Zaten takip ediyorsa: Takibi Bırak
        $delete = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $delete->execute([$follower_id, $followed_id]);
        
        echo json_encode([
            'status' => 'success',
            'action' => 'unfollowed',
            'message' => 'Takibi bıraktınız.'
        ]);
    } else {
        // Takip etmiyorsa: Takip Et
        $insert = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $insert->execute([$follower_id, $followed_id]);
        
        echo json_encode([
            'status' => 'success',
            'action' => 'followed',
            'message' => 'Takip ediliyor.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
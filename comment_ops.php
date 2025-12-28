<?php
/**
 * comment_ops.php
 * Bu dosya yorum ekleme ve yorum beğenme işlemlerini yönetir.
 */

require_once 'db.php';
session_start();

// Yanıtın her zaman JSON formatında olmasını sağlarız
header('Content-Type: application/json; charset=utf-8');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Lütfen önce giriş yapın.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// 1. Yorum Ekleme İşlemi (Yanıt Desteğiyle)
if ($action === 'add_comment') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $comment_text = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Temel veri doğrulaması
    if ($post_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz Gönderi ID.']);
        exit;
    }

    if (empty($comment_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Yorum içeriği boş olamaz.']);
        exit;
    }

    try {
        // Yorumu veritabanına kaydet (parent_id sütunu veritabanında mevcut olmalıdır)
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, parent_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$post_id, $user_id, $parent_id, $comment_text]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Yorum başarıyla gönderildi.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Veritabanı hatası: ' . $e->getMessage()
        ]);
    }
    exit;
}

// 2. Yorum Beğenme/Beğeni Kaldırma İşlemi
if ($action === 'like_comment' || $action === 'toggle_comment_like') {
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

    if ($comment_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz Yorum ID.']);
        exit;
    }

    try {
        // Kullanıcının bu yorumu daha önce beğenip beğenmediğini kontrol et
        $check = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $check->execute([$comment_id, $user_id]);
        $existing_like = $check->fetch();

        if ($existing_like) {
            // Beğenmişse beğeniyi kaldır
            $pdo->prepare("DELETE FROM comment_likes WHERE id = ?")->execute([$existing_like['id']]);
            $res_action = 'unliked';
        } else {
            // Beğenmemişse yeni beğeni ekle
            $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)")->execute([$comment_id, $user_id]);
            $res_action = 'liked';
        }

        // Güncel beğeni sayısını al
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
        $count_stmt->execute([$comment_id]);
        $new_count = (int)$count_stmt->fetchColumn();

        echo json_encode([
            'status' => 'success', 
            'action' => $res_action, 
            'new_count' => $new_count
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Beğeni işlemi sırasında hata oluştu: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Not: Yorumların listelendiği sayfa (post_view.php gibi) içerisinde 
 * SQL sorgusu "ORDER BY created_at ASC" şeklinde güncellenmelidir.
 */

// Eğer hiçbir action eşleşmezse
echo json_encode(['status' => 'error', 'message' => 'Tanımlanamayan işlem (Action Error): ' . $action]);
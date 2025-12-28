<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Gönderi detaylarını ve beğeni durumunu çek
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_pic, c.name as category_name,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT id FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$user_id, $post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header('Location: index.php');
        exit;
    }

    // Yorumları ve her yorumun beğeni sayısını/durumunu çek
    // Sadece SIRALAMA DEĞİŞTİ: DESC -> ASC (İlk yazılan en üstte)
    $commentStmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_pic,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) as c_like_count,
        (SELECT id FROM comment_likes WHERE comment_id = c.id AND user_id = ?) as is_c_liked
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $commentStmt->execute([$user_id, $post_id]);
    $all_comments = $commentStmt->fetchAll();

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($post['title']); ?> - OMNi</title>
    <style>
        @font-face { font-family: 'PAI'; src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2'); }
        :root { 
            --accent: #ffffff; 
            --bg: #000000; 
            --glass: rgba(255, 255, 255, 0.08); 
            --border: rgba(255, 255, 255, 0.1); 
            --text-main: #ffffff;
            --text-dim: rgba(255, 255, 255, 0.5);
            --island-gap: 12px;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            background: var(--bg); color: var(--text-main); font-family: 'PAI', sans-serif; 
            margin: 0; padding: 0; -webkit-font-smoothing: antialiased;
        }
        .container { max-width: 500px; margin: 0 auto; padding: 40px 16px 160px; }
        .island {
            background: var(--glass); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border); border-radius: 24px; margin-bottom: var(--island-gap);
        }
        .post-card { position: relative; overflow: hidden; border-radius: 36px; margin-bottom: 32px; padding: 12px; border: 1px solid var(--border); }
        .post-card-bg {
            position: absolute; top: -10%; left: -10%; width: 120%; height: 120%;
            background-size: cover; background-position: center; filter: blur(60px) brightness(0.4); z-index: -1;
        }
        .header-island { display: flex; align-items: center; padding: 12px 16px; justify-content: space-between; }
        .back-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--glass); border: 1px solid var(--border); border-radius: 20px; text-decoration: none; }
        .profile-info { display: flex; align-items: center; text-decoration: none; color: inherit; }
        .avatar { width: 38px; height: 38px; border-radius: 14px; object-fit: cover; margin-right: 10px; border: 1px solid var(--border); }
        .username { font-weight: 700; font-size: 14px; }
        .content-island { padding: 24px; }
        .post-title { font-size: 22px; font-weight: 800; margin: 0 0 12px 0; letter-spacing: -0.8px; }
        .category-pill { display: inline-block; padding: 4px 12px; background: var(--accent); color: #000; border-radius: 12px; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 16px; }
        .full-content { font-size: 16px; line-height: 1.6; color: rgba(255,255,255,0.9); white-space: pre-wrap; }
        .stats-island { display: flex; justify-content: space-between; padding: 10px 12px; }
        .stat-pill {
            display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.06);
            padding: 10px 20px; border-radius: 22px; font-size: 14px; font-weight: 600;
            border: none; color: #fff; cursor: pointer;
        }
        .stat-pill.liked { background: #fff; color: #000; }
        .stat-pill.liked img { filter: invert(0); }
        .stat-pill img { width: 18px; filter: brightness(0) invert(1); }
        .section-title { font-size: 13px; font-weight: 800; color: var(--text-dim); text-transform: uppercase; margin: 30px 0 15px 10px; letter-spacing: 1px; }

        .comment-island { padding: 16px; margin-bottom: 10px; }
        .comment-header { display: flex; align-items: center; margin-bottom: 8px; }
        .comment-avatar { width: 24px; height: 24px; border-radius: 8px; margin-right: 8px; }
        .comment-user { font-weight: 700; font-size: 13px; }
        .comment-time { font-size: 10px; color: var(--text-dim); margin-left: auto; }
        .comment-body { font-size: 14px; line-height: 1.4; color: rgba(255,255,255,0.8); margin-bottom: 12px; }
        
        .comment-footer { display: flex; gap: 15px; }
        .c-action { 
            background: none; border: none; color: var(--text-dim); 
            font-size: 11px; font-weight: 700; cursor: pointer; 
            display: flex; align-items: center; gap: 4px; padding: 0;
        }
        .c-action img { width: 12px; filter: invert(0.5); }
        .c-action.liked { color: #fff; }
        .c-action.liked img { filter: invert(1) brightness(2); }

        .fixed-comment-bar {
            position: fixed; bottom: 0; left: 0; width: 100%;
            padding: 20px 16px calc(20px + env(safe-area-inset-bottom));
            background: linear-gradient(transparent, #000 40%); z-index: 100;
            display: flex; flex-direction: column; align-items: center;
        }
        .reply-info {
            width: 100%; max-width: 468px; background: rgba(255,255,255,0.1);
            border-radius: 12px 12px 0 0; padding: 6px 16px; font-size: 11px;
            border: 1px solid var(--border); border-bottom: none;
            display: none; justify-content: space-between; align-items: center;
        }
        .comment-input-island { 
            width: 100%; max-width: 468px; padding: 8px 8px 8px 16px; 
            display: flex; gap: 10px; align-items: center; box-shadow: 0 -10px 30px rgba(0,0,0,0.5);
        }
        .comment-textarea {
            flex: 1; background: transparent; border: none; color: #fff; 
            font-family: inherit; resize: none; outline: none; font-size: 15px; 
            height: 24px; line-height: 24px;
        }
        .send-btn {
            width: 40px; height: 40px; border-radius: 20px; background: #fff;
            border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="post-card">
            <?php 
                $p_avatar = (!empty($post['profile_pic']) && $post['profile_pic'] !== 'default.png') 
                            ? 'profile_pics/' . $post['profile_pic'] 
                            : 'https://cdn.pasai.online/img/avatar-placeholder.png';
            ?>
            <div class="post-card-bg" style="background-image: url('<?php echo $p_avatar; ?>');"></div>

            <div class="island header-island">
                <a href="index.php" class="back-btn">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/arrow-left.svg" style="width:20px; filter:invert(1);">
                </a>
                <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="profile-info">
                    <img src="<?php echo $p_avatar; ?>" class="avatar">
                    <span class="username">@<?php echo htmlspecialchars($post['username']); ?></span>
                </a>
                <div style="width:40px"></div>
            </div>

            <div class="island content-island">
                <?php if($post['category_name']): ?>
                    <span class="category-pill"><?php echo htmlspecialchars($post['category_name']); ?></span>
                <?php endif; ?>
                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="full-content"><?php echo htmlspecialchars($post['content']); ?></div>
            </div>

            <div class="island stats-island">
                <button class="stat-pill <?php echo $post['is_liked'] ? 'liked' : ''; ?>" onclick="handleLike(<?php echo $post['id']; ?>, this)">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/<?php echo $post['is_liked'] ? 'fill/heart-fill.svg' : 'regular/heart.svg'; ?>">
                    <span><?php echo $post['like_count']; ?></span>
                </button>

                <button class="stat-pill" onclick="copyURL(this)">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/share-network.svg">
                    <span>Paylaş</span>
                </button>
            </div>
        </div>

        <div class="section-title">Yorumlar (<?php echo count($all_comments); ?>)</div>

        <div class="comments-list">
            <?php if(empty($all_comments)): ?>
                <div style="text-align:center; padding: 40px; color: var(--text-dim); font-size: 14px;">Henüz yorum yapılmamış.</div>
            <?php endif; ?>

            <?php foreach($all_comments as $comment): 
                $c_avatar = (!empty($comment['profile_pic']) && $comment['profile_pic'] !== 'default.png') 
                            ? 'profile_pics/' . $comment['profile_pic'] 
                            : 'https://cdn.pasai.online/img/avatar-placeholder.png';
            ?>
                <div class="island comment-island" id="comment-<?php echo $comment['id']; ?>">
                    <div class="comment-header">
                        <img src="<?php echo $c_avatar; ?>" class="comment-avatar">
                        <span class="comment-user"><?php echo htmlspecialchars($comment['username']); ?></span>
                        <span class="comment-time"><?php echo date('H:i', strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="comment-body">
                        <?php echo htmlspecialchars($comment['comment']); ?>
                    </div>
                    <div class="comment-footer">
                        <button class="c-action <?php echo $comment['is_c_liked'] ? 'liked' : ''; ?>" onclick="handleCommentLike(<?php echo $comment['id']; ?>, this)">
                            <img src="https://cdn.pasai.online/img/phosphor/SVG/<?php echo $comment['is_c_liked'] ? 'fill/heart-fill.svg' : 'regular/heart.svg'; ?>">
                            <span><?php echo $comment['c_like_count']; ?></span>
                        </button>
                        <button class="c-action" onclick="setReply(<?php echo $comment['id']; ?>, '<?php echo $comment['username']; ?>')">
                            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/arrow-bend-up-left.svg">
                            <span>Yanıtla</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="fixed-comment-bar">
        <div class="reply-info" id="replyInfo">
            <span id="replyText"></span>
            <span style="cursor:pointer; font-weight:bold" onclick="cancelReply()">✕</span>
        </div>
        <div class="island comment-input-island">
            <input type="hidden" id="parentId" value="0">
            <textarea id="commentInput" class="comment-textarea" placeholder="Yorum yaz..." required></textarea>
            <button class="send-btn" onclick="sendComment(<?php echo $post['id']; ?>)">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/paper-plane-tilt.svg" style="width:18px;">
            </button>
        </div>
    </div>

    <script>
    function handleLike(postId, btn) {
        const formData = new FormData();
        formData.append('post_id', postId);
        if (navigator.vibrate) navigator.vibrate(12);

        fetch('like.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                btn.querySelector('span').innerText = data.new_count;
                const icon = btn.querySelector('img');
                if(data.action === 'liked') {
                    btn.classList.add('liked');
                    icon.src = 'https://cdn.pasai.online/img/phosphor/SVG/fill/heart-fill.svg';
                } else {
                    btn.classList.remove('liked');
                    icon.src = 'https://cdn.pasai.online/img/phosphor/SVG/regular/heart.svg';
                }
            }
        });
    }

    function handleCommentLike(commentId, btn) {
        const formData = new FormData();
        formData.append('action', 'like_comment');
        formData.append('comment_id', commentId);
        if (navigator.vibrate) navigator.vibrate(10);

        fetch('comment_ops.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                btn.querySelector('span').innerText = data.new_count;
                const icon = btn.querySelector('img');
                if(data.action === 'liked') {
                    btn.classList.add('liked');
                    icon.src = 'https://cdn.pasai.online/img/phosphor/SVG/fill/heart-fill.svg';
                } else {
                    btn.classList.remove('liked');
                    icon.src = 'https://cdn.pasai.online/img/phosphor/SVG/regular/heart.svg';
                }
            }
        });
    }

    function setReply(id, username) {
        document.getElementById('parentId').value = id;
        document.getElementById('replyInfo').style.display = 'flex';
        document.getElementById('replyText').innerText = '@' + username + ' kullanıcısına yanıt veriliyor';
        document.getElementById('commentInput').focus();
    }

    function cancelReply() {
        document.getElementById('parentId').value = '0';
        document.getElementById('replyInfo').style.display = 'none';
    }

    function sendComment(postId) {
        const comment = document.getElementById('commentInput').value.trim();
        const parentId = document.getElementById('parentId').value;
        if(!comment) return;

        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('post_id', postId);
        formData.append('comment', comment);
        formData.append('parent_id', parentId);

        fetch('comment_ops.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    function copyURL(btn) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            const span = btn.querySelector('span');
            const old = span.innerText;
            span.innerText = 'Kopyalandı!';
            setTimeout(() => { span.innerText = old; }, 2000);
        });
    }
    </script>
</body>
</html>
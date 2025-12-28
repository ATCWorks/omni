<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$viewer_id = $_SESSION['user_id'];

try {
    // Toplam beğeniye yorum beğenileri de dahil edildi
    $stmt = $pdo->prepare("
        SELECT u.*, 
        (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
        (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as followers_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
        (
            (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = u.id) +
            (SELECT COUNT(*) FROM comment_likes cl JOIN comments c ON cl.comment_id = c.id WHERE c.user_id = u.id)
        ) as total_likes_received
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$profile_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Kullanıcı bulunamadı.");
    }

    $is_following = false;
    if ($profile_id !== $viewer_id) {
        $follow_check = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
        $follow_check->execute([$viewer_id, $profile_id]);
        $is_following = (bool)$follow_check->fetch();
    }

    $postStmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $postStmt->execute([$profile_id]);
    $user_posts = $postStmt->fetchAll();

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

$avatar_path = (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.png') 
               ? 'profile_pics/' . $user['profile_pic'] 
               : 'https://cdn.pasai.online/img/avatar-placeholder.png';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($user['username']); ?> - OMNi</title>
    <style>
        @font-face { font-family: 'PAI'; src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2'); }
        :root { --accent: #ffffff; --bg: #000000; --glass: rgba(255, 255, 255, 0.05); --border: rgba(255, 255, 255, 0.1); }
        
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }
        body { background: var(--bg); color: #fff; font-family: 'PAI', sans-serif; margin: 0; padding-bottom: 120px; }

        .profile-header { position: relative; padding: 80px 24px 40px; text-align: center; overflow: hidden; }
        .header-bg { position: absolute; top: -50px; left: -50px; width: 150%; height: 150%; background: url('<?php echo $avatar_path; ?>') center/cover; filter: blur(80px) brightness(0.3); z-index: -1; }
        .avatar-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }
        .avatar-wrapper img { width: 120px; height: 120px; border-radius: 44px; object-fit: cover; border: 2px solid rgba(255,255,255,0.15); box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .profile-name { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -1px; }
        .profile-bio { font-size: 15px; color: rgba(255,255,255,0.5); margin: 12px 0 24px; line-height: 1.5; padding: 0 30px; }

        .stats-container { display: flex; justify-content: center; gap: 8px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-pill { background: var(--glass); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid var(--border); padding: 10px 16px; border-radius: 24px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.2s; }
        .stat-pill:active { transform: scale(0.95); }
        .stat-pill img { width: 18px; height: 18px; filter: brightness(0) invert(1); opacity: 0.6; }
        .stat-pill span { font-size: 14px; font-weight: 700; }
        .stat-pill label { font-size: 11px; opacity: 0.4; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Modal Tasarımı */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); z-index: 3000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal-island { background: rgba(30,30,30,0.8); border: 1px solid var(--border); width: 90%; max-width: 400px; border-radius: 32px; padding: 24px; max-height: 70vh; overflow-y: auto; transform: translateY(20px); transition: transform 0.3s ease; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-overlay.active .modal-island { transform: translateY(0); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: 800; }
        .close-modal { cursor: pointer; opacity: 0.5; padding: 5px; }
        
        .user-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; text-decoration: none; color: white; padding: 8px; border-radius: 16px; transition: background 0.2s; }
        .user-row:active { background: var(--glass); }
        .user-row img { width: 40px; height: 40px; border-radius: 14px; object-fit: cover; }
        .user-info { display: flex; flex-direction: column; }
        .user-username { font-size: 15px; font-weight: 700; }
        .user-name { font-size: 12px; opacity: 0.5; }

        /* Action Row */
        .profile-actions { display: flex; justify-content: center; gap: 12px; margin-bottom: 20px; }
        .btn { padding: 14px 28px; border-radius: 28px; font-size: 15px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; font-family: 'PAI'; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:active { transform: scale(0.96); }
        .btn-primary { background: #ffffff; color: #000000; }
        .btn-outline { background: var(--glass); color: #ffffff; border: 1px solid var(--border); backdrop-filter: blur(10px); }
        .btn-following { background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255,255,255,0.1); }

        .feed { padding: 0 20px; max-width: 500px; margin: 0 auto; }
        .section-title { font-size: 20px; font-weight: 800; margin: 0 0 20px 8px; letter-spacing: -0.5px; }
        .mini-post { background: var(--glass); border: 1px solid var(--border); border-radius: 28px; padding: 20px; margin-bottom: 16px; display: block; text-decoration: none; color: inherit; transition: background 0.2s; }
        .mini-post:active { background: rgba(255,255,255,0.08); }
        .mini-post-content { font-size: 16px; line-height: 1.5; color: rgba(255,255,255,0.9); margin-bottom: 12px; }
        .mini-post-meta { display: flex; gap: 16px; font-size: 12px; opacity: 0.4; font-weight: 600; }
        .mini-post-meta span { display: flex; align-items: center; gap: 6px; }
        .mini-post-meta img { width: 16px; filter: brightness(0) invert(1); }

        .nav-island { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: fit-content; min-width: 280px; height: 68px; background: rgba(20, 20, 20, 0.8); backdrop-filter: blur(32px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 34px; display: flex; padding: 0 10px; justify-content: center; align-items: center; gap: 12px; z-index: 2000; }
        .nav-item { display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; border-radius: 25px; }
        .nav-item img { width: 24px; filter: brightness(0) invert(1); opacity: 0.4; }
        .nav-item.active { background: rgba(255,255,255,0.1); }
        .nav-item.active img { opacity: 1; }
        .settings-trigger { position: fixed; top: 40px; right: 20px; width: 46px; height: 46px; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .settings-trigger img { width: 24px; filter: invert(1); opacity: 0.8; }
    </style>
</head>
<body>
    <a href="settings.php" class="settings-trigger">
        <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/gear.svg">
    </a>

    <!-- Takipçi/Takip Modalı -->
    <div id="followModal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-island" onclick="event.stopPropagation()">
            <div class="modal-header">
                <span id="modalTitle" class="modal-title">Liste</span>
                <span class="close-modal" onclick="closeModal()">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/x.svg" style="width:20px; filter:invert(1);">
                </span>
            </div>
            <div id="modalContent">
                <!-- Veriler buraya yüklenecek -->
                <div style="text-align:center; padding:20px; opacity:0.5;">Yükleniyor...</div>
            </div>
        </div>
    </div>

    <div class="profile-header">
        <div class="header-bg"></div>
        <div class="avatar-wrapper">
            <img src="<?php echo $avatar_path; ?>" alt="Avatar">
        </div>
        <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
        <p class="profile-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'Henüz bir biyografi eklenmemiş.'; ?></p>

        <div class="stats-container">
            <div class="stat-pill">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/article.svg">
                <span><?php echo $user['post_count']; ?> <label>Post</label></span>
            </div>
            <div class="stat-pill">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/heart.svg">
                <span><?php echo $user['total_likes_received']; ?> <label>Beğeni</label></span>
            </div>
            <!-- Takipçi Tıklama -->
            <div class="stat-pill" onclick="showFollowers(<?php echo $profile_id; ?>)">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/users.svg">
                <span><?php echo $user['followers_count']; ?> <label>Takipçi</label></span>
            </div>
            <!-- Takip Tıklama -->
            <div class="stat-pill" onclick="showFollowing(<?php echo $profile_id; ?>)">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/user-plus.svg">
                <span><?php echo $user['following_count']; ?> <label>Takip</label></span>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($profile_id === $viewer_id): ?>
                <a href="profile_edit.php" class="btn btn-primary">Profili Düzenle</a>
                <a href="logout.php" class="btn btn-outline" style="color:#ff453a">Çıkış Yap</a>
            <?php else: ?>
                <button id="followBtn" class="btn <?php echo $is_following ? 'btn-following' : 'btn-primary'; ?>" onclick="toggleFollow(<?php echo $profile_id; ?>)">
                    <?php echo $is_following ? 'Takibi Bırak' : 'Takip Et'; ?>
                </button>
                <button class="btn btn-outline">Mesaj Gönder</button>
            <?php endif; ?>
        </div>
    </div>

    <main class="feed">
        <h2 class="section-title">Gönderiler</h2>
        <?php if (empty($user_posts)): ?>
            <div style="text-align:center; padding:60px; opacity:0.2;">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/ghost.svg" style="width:48px; filter:invert(1); margin-bottom:12px;"><br>
                Paylaşım bulunamadı.
            </div>
        <?php else: ?>
            <?php foreach ($user_posts as $post): ?>
                <a href="post_view.php?id=<?php echo $post['id']; ?>" class="mini-post">
                    <div class="mini-post-content"><?php echo mb_strimwidth(htmlspecialchars($post['content']), 0, 120, "..."); ?></div>
                    <div class="mini-post-meta">
                        <span><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/heart.svg"> <?php echo $post['like_count']; ?></span>
                        <span><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/chat-circle.svg"> <?php echo $post['comment_count']; ?></span>
                        <span><?php echo date('d M', strtotime($post['created_at'])); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <nav class="nav-island">
        <a href="index.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/house.svg"></a>
        <a href="search.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/magnifying-glass.svg"></a>
        <a href="create_post.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/plus.svg"></a>
        <a href="notifications.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/bell.svg"></a>
        <a href="profile.php" class="nav-item active"><img src="https://cdn.pasai.online/img/phosphor/SVG/fill/user-fill.svg"></a>
    </nav>

    <script>
    function closeModal(e) {
        const modal = document.getElementById('followModal');
        modal.classList.remove('active');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }

    function openModal(title) {
        const modal = document.getElementById('followModal');
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalContent').innerHTML = '<div style="text-align:center; padding:20px; opacity:0.5;">Yükleniyor...</div>';
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('active'); }, 10);
    }

    // Takipçileri getir
    function showFollowers(userId) {
        openModal('Takipçiler');
        fetchUsers('get_followers.php?id=' + userId);
    }

    // Takip edilenleri getir
    function showFollowing(userId) {
        openModal('Takip Edilenler');
        fetchUsers('get_following.php?id=' + userId);
    }

    function fetchUsers(url) {
        fetch(url)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if(data.length === 0) {
                html = '<div style="text-align:center; padding:20px; opacity:0.5;">Liste boş.</div>';
            } else {
                data.forEach(u => {
                    const pic = u.profile_pic ? 'profile_pics/' + u.profile_pic : 'https://cdn.pasai.online/img/avatar-placeholder.png';
                    html += `
                        <a href="profile.php?id=${u.id}" class="user-row">
                            <img src="${pic}">
                            <div class="user-info">
                                <span class="user-username">@${u.username}</span>
                                <span class="user-name">${u.full_name || ''}</span>
                            </div>
                        </a>
                    `;
                });
            }
            document.getElementById('modalContent').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('modalContent').innerHTML = '<div style="color:red; text-align:center;">Hata oluştu.</div>';
        });
    }

    function toggleFollow(userId) {
        const btn = document.getElementById('followBtn');
        const formData = new FormData();
        formData.append('followed_id', userId);
        if (navigator.vibrate) navigator.vibrate(10);
        fetch('follow_ops.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.status === 'success') location.reload(); });
    }
    </script>
</body>
</html>
<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT id FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>OMNi Forum</title>
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
            background: var(--bg); 
            color: var(--text-main); 
            font-family: 'PAI', sans-serif; 
            margin: 0; 
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 500px; margin: 0 auto; padding: 40px 16px 120px; }
        .branding { margin-bottom: 30px; padding-left: 10px; }
        .logo-text { font-size: 26px; font-weight: 800; letter-spacing: -1.2px; text-transform: uppercase; }

        .island {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            margin-bottom: var(--island-gap);
            transition: background 0.2s ease;
        }

        .post-card {
            position: relative;
            overflow: hidden;
            border-radius: 36px;
            margin-bottom: 32px;
            padding: 12px;
            border: 1px solid var(--border);
        }

        .post-card-bg {
            position: absolute; top: -10%; left: -10%; width: 120%; height: 120%;
            background-size: cover;
            background-position: center;
            filter: blur(60px) brightness(0.4);
            z-index: -1;
        }

        .profile-island {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            text-decoration: none;
            color: inherit;
        }

        .avatar {
            width: 42px; height: 42px;
            border-radius: 16px;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,0.2);
            margin-right: 12px;
        }

        .user-info { display: flex; flex-direction: column; }
        .username { font-weight: 700; font-size: 15px; letter-spacing: -0.3px; }
        .time { font-size: 10px; color: var(--text-dim); text-transform: uppercase; }

        .content-island {
            padding: 20px;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .content-island:active { background: rgba(255, 255, 255, 0.12); }

        .post-title { 
            font-size: 18px; 
            font-weight: 800; 
            margin: 0 0 8px 0; 
            color: #fff; 
            letter-spacing: -0.5px;
        }

        .post-excerpt {
            font-size: 15px;
            line-height: 1.5;
            color: rgba(255,255,255,0.7);
        }

        .stats-island {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 8px;
            margin-bottom: 0;
        }

        .interaction-group { display: flex; gap: 8px; }

        .stat-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.06);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            color: #fff;
            cursor: pointer;
            transition: 0.2s;
        }

        .stat-pill img { width: 18px; filter: brightness(0) invert(1); }
        .stat-pill.liked { background: #fff; color: #000; }
        .stat-pill.liked img { filter: invert(0); }

        .share-btn {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
        }

        .nav-island {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            width: fit-content; min-width: 280px; height: 68px;
            background: rgba(10, 10, 10, 0.7); backdrop-filter: blur(32px);
            border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 34px;
            display: flex; padding: 0 10px; justify-content: center; align-items: center; gap: 12px; z-index: 2000;
        }
        
        .nav-item { display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; border-radius: 25px; }
        .nav-item img { width: 24px; filter: brightness(0) invert(1); opacity: 0.4; }
        .nav-item.active { background: rgba(255,255,255,0.1); }
        .nav-item.active img { opacity: 1; }
        
        .settings-trigger {
            position: fixed;
            top: 40px;
            right: 20px;
            width: 46px;
            height: 46px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .settings-trigger img {
            width: 24px;
            filter: invert(1);
            opacity: 0.8;
        }

        .settings-trigger:active {
            transform: scale(0.9);
            background: rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>
    <a href="settings.php" class="settings-trigger">
        <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/gear.svg">
    </a>
    <div class="container">
        <div class="branding">
            <div class="logo-text">OMNi Forum</div>
        </div>

        <main class="feed">
            <?php foreach ($posts as $post): 
                $p_avatar = (!empty($post['profile_pic']) && $post['profile_pic'] !== 'default.png') 
                            ? 'profile_pics/' . $post['profile_pic'] 
                            : 'https://cdn.pasai.online/img/avatar-placeholder.png';
            ?>
                <div class="post-card">
                    <div class="post-card-bg" style="background-image: url('<?php echo $p_avatar; ?>');"></div>

                    <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="island profile-island">
                        <img src="<?php echo $p_avatar; ?>" class="avatar" alt="p">
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                            <span class="time"><?php echo date('H:i • d M', strtotime($post['created_at'])); ?></span>
                        </div>
                    </a>

                    <a href="post_view.php?id=<?php echo $post['id']; ?>" class="island content-island">
                        <?php if(!empty($post['title'])): ?>
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <?php endif; ?>
                        <div class="post-excerpt">
                            <?php echo mb_strimwidth(htmlspecialchars($post['content']), 0, 180, "..."); ?>
                        </div>
                    </a>

                    <div class="island stats-island">
                        <div class="interaction-group">
                            <!-- Beğeni Butonu: like.php ile uyumlu hale getirildi -->
                            <button class="stat-pill <?php echo $post['is_liked'] ? 'liked' : ''; ?>" onclick="handleLike(<?php echo $post['id']; ?>, this)">
                                <img src="https://cdn.pasai.online/img/phosphor/SVG/<?php echo $post['is_liked'] ? 'fill/heart-fill.svg' : 'regular/heart.svg'; ?>">
                                <span><?php echo $post['like_count']; ?></span>
                            </button>
                            
                            <a href="post_view.php?id=<?php echo $post['id']; ?>" class="stat-pill" style="text-decoration:none;">
                                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/chat-circle.svg">
                                <span><?php echo $post['comment_count']; ?></span>
                            </a>
                        </div>

                        <button class="share-btn">
                            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/share-network.svg" style="width:18px; filter:invert(1); opacity:0.6;">
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>

    <nav class="nav-island">
        <a href="index.php" class="nav-item active"><img src="https://cdn.pasai.online/img/phosphor/SVG/fill/house-fill.svg"></a>
        <a href="search.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/magnifying-glass.svg"></a>
        <a href="create_post.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/plus.svg"></a>
        <a href="notifications.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/bell.svg"></a>
        <a href="profile.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/user.svg"></a>
    </nav>

    <script>
    function handleLike(postId, btn) {
        const formData = new FormData();
        // like.php 'post_id' beklediği için parametre adını güncelledim
        formData.append('post_id', postId);

        if (navigator.vibrate) navigator.vibrate(12);

        // İstek like.php dosyasına yönlendirildi
        fetch('like.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const countSpan = btn.querySelector('span');
                const iconImg = btn.querySelector('img');
                countSpan.innerText = data.new_count;
                
                if(data.action === 'liked') {
                    btn.classList.add('liked');
                    iconImg.src = 'https://cdn.pasai.online/img/phosphor/SVG/fill/heart-fill.svg';
                } else {
                    btn.classList.remove('liked');
                    iconImg.src = 'https://cdn.pasai.online/img/phosphor/SVG/regular/heart.svg';
                }
            }
        });
    }
    </script>
</body>
</html>
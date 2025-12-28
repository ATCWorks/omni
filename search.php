<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'posts';

$results = [];

if (!empty($query)) {
    try {
        if ($type === 'users') {
            $stmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE username LIKE ? LIMIT 20");
            $stmt->execute(["%$query%"]);
            $results = $stmt->fetchAll();
        } elseif ($type === 'hashtags') {
        } else {
            // Gönderi araması
            $stmt = $pdo->prepare("
                SELECT p.*, u.username, u.profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                (SELECT id FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.title LIKE ? OR p.content LIKE ?
                ORDER BY p.created_at DESC LIMIT 20
            ");
            $stmt->execute([$user_id, "%$query%", "%$query%"]);
            $results = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        // Hata yönetimi
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Keşfet - OMNi</title>
    <link rel="preconnect" href="https://cdn.pasai.online" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.pasai.online">
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

        /* Search Input Styling */
        .search-wrapper { margin-bottom: 20px; }
        .search-island {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 24px;
            display: flex;
            align-items: center;
            padding: 4px 16px;
        }
        .search-island img { width: 20px; filter: invert(1); opacity: 0.5; margin-right: 12px; }
        .search-island input {
            width: 100%;
            background: transparent;
            border: none;
            padding: 12px 0;
            color: #fff;
            font-family: 'PAI', sans-serif;
            font-size: 16px;
            outline: none;
        }

        /* Tabs */
        .tabs-island {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            overflow-x: auto;
            scrollbar-width: none;
            padding: 4px;
        }
        .tabs-island::-webkit-scrollbar { display: none; }
        .tab-btn {
            padding: 10px 20px;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--text-dim);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            transition: 0.2s;
        }
        .tab-btn.active {
            background: #fff;
            color: #000;
            border-color: #fff;
        }

        /* Results / Post Cards */
        .island {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            margin-bottom: var(--island-gap);
        }

        .user-result {
            display: flex;
            align-items: center;
            padding: 16px;
            text-decoration: none;
            color: inherit;
        }
        .avatar {
            width: 48px; height: 48px;
            border-radius: 18px;
            object-fit: cover;
            border: 1px solid var(--border);
            margin-right: 14px;
        }
        .user-info h3 { margin: 0; font-size: 16px; font-weight: 700; }
        .user-info p { margin: 2px 0 0; font-size: 13px; color: var(--text-dim); }

        /* Reuse Post Card Styles from index.php for "posts" search results */
        .post-card { position: relative; overflow: hidden; border-radius: 36px; margin-bottom: 32px; padding: 12px; border: 1px solid var(--border); }
        .post-card-bg { position: absolute; top: -10%; left: -10%; width: 120%; height: 120%; background-size: cover; background-position: center; filter: blur(60px) brightness(0.4); z-index: -1; }
        .profile-island { display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: inherit; }
        .content-island { padding: 20px; text-decoration: none; color: inherit; display: block; }
        .post-title { font-size: 18px; font-weight: 800; margin: 0 0 8px 0; color: #fff; letter-spacing: -0.5px; }
        .post-excerpt { font-size: 15px; line-height: 1.5; color: rgba(255,255,255,0.7); }
        .stats-island { display: flex; justify-content: space-between; align-items: center; padding: 8px 8px; margin-bottom: 0; }
        .stat-pill { display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.06); padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; border: none; color: #fff; cursor: pointer; }
        .stat-pill img { width: 18px; filter: brightness(0) invert(1); }
        .stat-pill.liked { background: #fff; color: #000; }
        .stat-pill.liked img { filter: invert(1); }

        /* Nav Island (Same as Index) */
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
            position: fixed; top: 40px; right: 20px; width: 46px; height: 46px;
            background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; z-index: 1000;
        }
        .settings-trigger img { width: 24px; filter: invert(1); opacity: 0.8; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-dim); }
    </style>
</head>
<body>
    <a href="settings.php" class="settings-trigger">
        <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/gear.svg">
    </a>

    <div class="container">
        <div class="branding">
            <div class="logo-text">Keşfet</div>
        </div>

        <div class="search-wrapper">
            <form action="search.php" method="GET">
                <div class="search-island">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/magnifying-glass.svg">
                    <input type="text" name="q" placeholder="OMNi'de ara..." value="<?php echo htmlspecialchars($query); ?>" autocomplete="off">
                    <input type="hidden" name="type" value="<?php echo $type; ?>">
                </div>
            </form>
        </div>

        <div class="tabs-island">
            <a href="search.php?q=<?php echo urlencode($query); ?>&type=posts" class="tab-btn <?php echo $type == 'posts' ? 'active' : ''; ?>">Gönderiler</a>
            <a href="search.php?q=<?php echo urlencode($query); ?>&type=users" class="tab-btn <?php echo $type == 'users' ? 'active' : ''; ?>">Kişiler</a>
            <a href="search.php?q=<?php echo urlencode($query); ?>&type=hashtags" class="tab-btn <?php echo $type == 'hashtags' ? 'active' : ''; ?>">Hashtag</a>
        </div>

        <main class="results">
            <?php if (empty($query)): ?>
                <div class="empty-state">Soruşturma vakti ;)</div>
            <?php elseif (count($results) > 0): ?>
                
                <?php if ($type === 'users'): ?>
                    <?php foreach ($results as $row): 
                        $avatar = (!empty($row['profile_pic']) && $row['profile_pic'] !== 'default.png') ? 'profile_pics/' . $row['profile_pic'] : 'https://cdn.pasai.online/img/avatar-placeholder.png';
                    ?>
                        <div class="island">
                            <a href="profile.php?id=<?php echo $row['id']; ?>" class="user-result">
                                <img src="<?php echo $avatar; ?>" class="avatar">
                                <div class="user-info">
                                    <h3>@<?php echo htmlspecialchars($row['username']); ?></h3>
                                    <p>Profili Görüntüle</p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <?php foreach ($results as $post): 
                        $p_avatar = (!empty($post['profile_pic']) && $post['profile_pic'] !== 'default.png') ? 'profile_pics/' . $post['profile_pic'] : 'https://cdn.pasai.online/img/avatar-placeholder.png';
                    ?>
                        <div class="post-card">
                            <div class="post-card-bg" style="background-image: url('<?php echo $p_avatar; ?>');"></div>
                            <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="island profile-island">
                                <img src="<?php echo $p_avatar; ?>" class="avatar" style="width:36px; height:36px; border-radius:12px;">
                                <div class="user-info">
                                    <span class="username" style="font-size:14px;"><?php echo htmlspecialchars($post['username']); ?></span>
                                </div>
                            </a>
                            <a href="post_view.php?id=<?php echo $post['id']; ?>" class="island content-island">
                                <?php if(!empty($post['title'])): ?>
                                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <?php endif; ?>
                                <div class="post-excerpt"><?php echo mb_strimwidth(htmlspecialchars($post['content']), 0, 140, "..."); ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">Sonuç bulunamadı.</div>
            <?php endif; ?>
        </main>
    </div>

    <nav class="nav-island">
        <a href="index.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/house.svg"></a>
        <a href="search.php" class="nav-item active"><img src="https://cdn.pasai.online/img/phosphor/SVG/fill/magnifying-glass-fill.svg"></a>
        <a href="create_post.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/plus.svg"></a>
        <a href="notifications.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/bell.svg"></a>
        <a href="profile.php" class="nav-item"><img src="https://cdn.pasai.online/img/phosphor/SVG/regular/user.svg"></a>
    </nav>
</body>
</html>
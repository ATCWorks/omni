<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanından güncellemeleri çek
try {
    // updates tablosundan en yeniden en eskiye sırala
    $stmt = $pdo->query("SELECT * FROM updates ORDER BY created_at DESC");
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $updates = [];
    $error = "Veri çekilemedi.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Güncellemeler - OMNi</title>
    <style>
        @font-face { font-family: 'PAI'; src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2'); }
        
        :root { 
            --bg: #000; 
            --glass: rgba(255, 255, 255, 0.08); 
            --border: rgba(255, 255, 255, 0.1); 
            --text-main: #fff; 
            --text-dim: rgba(255, 255, 255, 0.5); 
            --accent: #3e91ff;
            --accent-green: #32d74b;
            --accent-red: #ff453a;
        }

        body { 
            background: var(--bg); 
            color: var(--text-main); 
            font-family: 'PAI', sans-serif; 
            margin: 0; 
            padding: 0; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            padding: 50px 24px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn {
            width: 44px; height: 44px;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .back-btn img { width: 24px; filter: invert(1); }

        header h1 { font-size: 28px; margin: 0; font-weight: 700; letter-spacing: -1px; }

        .container { padding: 0 16px 40px; }

        .logo-area {
            text-align: center;
            padding: 40px 0;
        }

        .logo-area h2 {
            font-size: 42px;
            margin: 0;
            letter-spacing: -2px;
            background: linear-gradient(180deg, #fff 0%, rgba(255,255,255,0.4) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .island {
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 24px;
            margin-bottom: 12px;
            transition: transform 0.2s ease;
        }

        .island:active { transform: scale(0.98); }

        .info-title {
            font-size: 11px;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
            display: block;
            font-weight: 700;
        }

        .version-badge {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        /* Tipine göre badge renkleri */
        .badge-security { background: var(--accent-red); }
        .badge-fix { background: var(--accent-green); }

        .update-title {
            display: block;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .update-desc {
            font-size: 14px;
            color: var(--text-dim);
            line-height: 1.5;
            margin-bottom: 16px;
            display: block;
        }

        .update-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border);
            padding-top: 12px;
            font-size: 12px;
            color: var(--text-dim);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

    <header>
        <a href="settings.php" class="back-btn">
            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/arrow-left.svg">
        </a>
        <h1>Güncellemeler</h1>
    </header>

    <main class="container">
        
        <div class="logo-area">
            <h2>OMNi Updates</h2>
        </div>

        <?php if (empty($updates)): ?>
            <div class="empty-state">
                Henüz bir güncelleme kaydı bulunmuyor.
            </div>
        <?php else: ?>
            <?php foreach ($updates as $log): ?>
                <?php 
                    $badgeClass = '';
                    if($log['type'] == 'security') $badgeClass = 'badge-security';
                    if($log['type'] == 'fix') $badgeClass = 'badge-fix';
                ?>
                <div class="island">
                    <span class="info-title">Update</span>
                    <div class="version-badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($log['version']); ?>
                    </div>
                    
                    <b class="update-title"><?php echo htmlspecialchars($log['title']); ?></b>
                    <span class="update-desc">
                        <?php echo nl2br(htmlspecialchars($log['description'])); ?>
                    </span>

                    <div class="update-footer">
                        <span>Tip: <?php echo ucfirst($log['type']); ?></span>
                        <span><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <div style="text-align: center; color: var(--text-dim); font-size: 11px; padding-bottom: 30px; letter-spacing: 1px;">
        OMNi Forum Update System v1.0
    </div>

</body>
</html>
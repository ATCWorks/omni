<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini çek (Opsiyonel: İsim göstermek istersen)
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Ayarlar - OMNi</title>
    <style>
        @font-face { font-family: 'PAI'; src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2'); }
        
        :root { 
            --bg: #000; 
            --glass: rgba(255, 255, 255, 0.08); 
            --border: rgba(255, 255, 255, 0.1); 
            --text-main: #fff; 
            --text-dim: rgba(255, 255, 255, 0.5); 
            --accent: #3e91ff;
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
        }

        .back-btn img { width: 24px; filter: invert(1); }

        header h1 { font-size: 28px; margin: 0; font-weight: 700; letter-spacing: -1px; }

        .container { padding: 0 16px 40px; }

        .island-list { display: flex; flex-direction: column; gap: 12px; }

        .menu-item {
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-item:active {
            transform: scale(0.97);
            background: rgba(255, 255, 255, 0.12);
        }

        .icon-box {
            width: 48px; height: 48px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin-right: 16px;
            border: 1px solid var(--border);
        }

        .icon-box img { width: 24px; filter: invert(1); }

        .menu-text { flex: 1; }
        .menu-text span { display: block; font-weight: 700; font-size: 16px; margin-bottom: 2px; }
        .menu-text p { margin: 0; font-size: 12px; color: var(--text-dim); }

        .chevron { width: 20px; opacity: 0.3; filter: invert(1); }

        .footer-info {
            margin-top: auto;
            padding: 40px 24px;
            text-align: center;
            font-size: 12px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

    <header>
        <a href="javascript:history.back()" class="back-btn">
            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/arrow-left.svg">
        </a>
        <h1>Ayarlar</h1>
    </header>

    <main class="container">
        <div class="island-list">
            
            <!-- Security Section -->
            <a href="security.php" class="menu-item">
                <div class="icon-box">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/shield-check.svg">
                </div>
                <div class="menu-text">
                    <span>Güvenlik</span>
                    <p>Şifre değiştirme ve hesap koruması.</p>
                </div>
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/caret-right.svg" class="chevron">
            </a>

            <!-- Updates Section -->
            <a href="updates.php" class="menu-item">
                <div class="icon-box">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/sparkles.svg">
                </div>
                <div class="menu-text">
                    <span>Güncellemeler</span>
                    <p>Yenilikler ve sürüm notları.</p>
                </div>
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/caret-right.svg" class="chevron">
            </a>

            <!-- Info Section -->
            <a href="info.php" class="menu-item">
                <div class="icon-box">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/info.svg">
                </div>
                <div class="menu-text">
                    <span>Bilgi</span>
                    <p>Uygulama hakkında ve kullanım koşulları.</p>
                </div>
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/caret-right.svg" class="chevron">
            </a>

        </div>
    </main>

    <div class="footer-info">
        OMNi PreBeta v1.26.002<br>
        2025 &copy; OMNi Forum
    </div>

</body>
</html>
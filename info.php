<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Bilgi - OMNi</title>
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

        .island {
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 24px;
            margin-bottom: 12px;
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

        .info-title {
            font-size: 14px;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            display: block;
        }

        .credit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .credit-item:last-child { margin-bottom: 0; }

        .credit-info b { display: block; font-size: 16px; }
        .credit-info span { font-size: 13px; color: var(--text-dim); }

        .framework-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

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
    </style>
</head>
<body>

    <header>
        <a href="settings.php" class="back-btn">
            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/arrow-left.svg">
        </a>
        <h1>Uygulama Bilgisi</h1>
    </header>

    <main class="container">
        
        <div class="logo-area">
            <h2>OMNi</h2>
        </div>

        <!-- Sürüm Bilgisi -->
        <div class="island">
            <span class="info-title">Yazılım Bilgileri</span>
            <div class="version-badge">PreBeta 1.26.002</div>

        </div>

        <!-- Framework Bilgisi -->
        <div class="island">
            <span class="info-title">UI</span>
            <div class="credit-item">
                <div class="credit-info">
                    <b>PASA UI Framework</b>
                    <span>Developed by aliysnm00</span>
                    <br>
                    <a href="https://pasai.online" class="framework-link">pasai.online</a>
                </div>
            </div>
        </div>

        <!-- Krediler -->
        <div class="island">
            <span class="info-title">Credıts</span>
            
            <div class="credit-item">
                <div class="credit-info">
                    <b>@atcworks</b>
                    <span>Project Leader</span>
                </div>
            </div>

            <div class="credit-item" style="margin-top: 12px; border-top: 1px solid var(--border); padding-top: 12px;">
                <div class="credit-info">
                    <b>@aliysnm00</b>
                    <span>PASA UI Developer</span>
                </div>
            </div>
        </div>

    </main>

    <div style="text-align: center; color: var(--text-dim); font-size: 12px; padding-bottom: 30px;">
        A MOCA Dev.S Project
    </div>

</body>
</html>
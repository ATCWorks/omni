<?php
require_once 'db.php';
session_start();

$error = '';

// Eğer zaten beta doğrulaması yapmışsa login'e gönder
if (isset($_SESSION['beta_verified']) && $_SESSION['beta_verified'] === true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_key = trim($_POST['beta_key']);

    if (!empty($entered_key)) {
        // Key kontrolü
        $stmt = $pdo->prepare("SELECT * FROM beta_keys WHERE beta_key = ? AND is_used = 0");
        $stmt->execute([$entered_key]);
        $key_data = $stmt->fetch();

        if ($key_data) {
            // Key geçerli, oturumu işaretle
            $_SESSION['beta_verified'] = true;
            $_SESSION['active_beta_key'] = $entered_key;
            header('Location: login.php');
            exit;
        } else {
            $error = 'Geçersiz veya daha önce kullanılmış bir anahtar.';
        }
    } else {
        $error = 'Lütfen bir anahtar girin.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>OMNi PreBeta Doğrulama</title>
    <style>
        @font-face { font-family: 'PAI'; src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2'); }
        
        :root { 
            --bg: #000000; 
            --glass: rgba(255, 255, 255, 0.08); 
            --border: rgba(255, 255, 255, 0.1); 
            --accent: #ffffff;
        }

        body { 
            background: var(--bg); 
            color: #fff; 
            font-family: 'PAI', sans-serif; 
            margin: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh;
            overflow: hidden;
        }

        /* Island Mimari */
        .island {
            background: var(--glass);
            backdrop-filter: blur(32px);
            -webkit-backdrop-filter: blur(32px);
            border: 1px solid var(--border);
            border-radius: 36px;
            padding: 40px 30px;
            width: 100%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo-area { margin-bottom: 24px; }
        .logo-text { font-size: 28px; font-weight: 800; letter-spacing: -1.5px; }
        .beta-tag { 
            background: #fff; color: #000; 
            padding: 2px 8px; border-radius: 6px; 
            font-size: 12px; font-weight: 900; 
            vertical-align: middle; margin-left: 5px;
        }

        p { color: rgba(255,255,255,0.5); font-size: 14px; margin-bottom: 30px; line-height: 1.4; }

        .input-group { position: relative; margin-bottom: 16px; }
        
        input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 18px;
            color: #fff;
            font-family: 'PAI', sans-serif;
            font-size: 16px;
            text-align: center;
            letter-spacing: 2px;
            outline: none;
            transition: 0.3s;
        }

        input:focus { border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.08); }

        .verify-btn {
            width: 100%;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 20px;
            padding: 16px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .verify-btn:active { transform: scale(0.96); }

        .error-msg {
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
            padding: 12px;
            border-radius: 15px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 59, 48, 0.2);
        }
    </style>
</head>
<body>

    <div class="island">
        <div class="logo-area">
            <span class="logo-text">OMNi</span><span class="beta-tag">PRE-BETA</span>
        </div>
        <p>Tüm içeriğe erişmek için size verilen PreBeta Key'i girmelisiniz.</p>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="betakey.php" method="POST">
            <div class="input-group">
                <input type="text" name="beta_key" placeholder="PRE BETA KEY" autocomplete="off" required>
            </div>
            <button type="submit" class="verify-btn">Doğrula</button>
        </form>
    </div>

</body>
</html>
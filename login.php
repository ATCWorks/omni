<?php
require_once 'db.php';
session_start();

// Beta Key Kontrolü: Eğer beta doğrulaması yapılmamışsa betakey.php'ye yönlendir
if (!isset($_SESSION['beta_verified']) || $_SESSION['beta_verified'] !== true) {
    header('Location: betakey.php');
    exit;
}

// Eğer kullanıcı zaten giriş yapmışsa direkt ana sayfaya gönder
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Oturum verilerini ata
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Session kilitlenmesini önlemek ve veriyi diske yazmak için
                session_write_close(); 
                
                // Başarılı girişte yönlendirilecek adres
                header('Location: pixelence.php');
                exit;
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (PDOException $e) {
            $error = 'Bir sistem hatası oluştu.';
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Giriş Yap - OMNi</title>
    <link rel="preconnect" href="https://cdn.pasai.online" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.pasai.online">
    <style>
        @font-face {
            font-family: 'PAI';
            src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2');
            font-weight: normal; font-style: normal; font-display: swap;
        }

        :root {
            --accent: #3e91ff;
            --bg: #0a0a0a;
            --glass: rgba(255, 255, 255, 0.05);
            --border: rgba(255, 255, 255, 0.1);
            --safe-margin: 24px;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none !important; }

        body {
            margin: 0; padding: 0;
            background-color: var(--bg);
            color: #ffffff;
            font-family: 'PAI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: var(--safe-margin);
        }

        .island {
            background: rgba(18, 18, 18, 0.7);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 32px 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }

        .header-area {
            text-align: center;
            margin-bottom: 32px;
        }

        h1 { font-size: 28px; margin: 0 0 8px; font-weight: 600; }
        .subtitle { color: rgba(255,255,255,0.5); font-size: 14px; }

        .form-group {
            position: relative;
            margin-bottom: 16px;
        }

        .form-group img {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            filter: brightness(0) invert(1);
            opacity: 0.5;
        }

        input {
            width: 100%;
            background: var(--glass);
            border: 1px solid transparent;
            border-radius: 20px;
            padding: 16px 16px 16px 52px;
            color: #ffffff;
            font-family: 'PAI', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent);
        }

        .btn-primary {
            width: 100%;
            background: #ffffff;
            color: #000000;
            border: none;
            border-radius: 40px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 12px;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:active {
            transform: scale(0.95);
        }

        .footer-links {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: rgba(255,255,255,0.5);
        }

        .footer-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .alert {
            background: rgba(255, 59, 48, 0.15);
            color: #ff3b30;
            padding: 14px;
            border-radius: 18px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
            border: 1px solid rgba(255, 59, 48, 0.2);
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="island">
            <div class="header-area">
                <h1>Giriş Yap</h1>
                <p class="subtitle">OMNi PRE BETA</p>
            </div>

            <?php if($error): ?>
                <div class="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/user.svg" alt="kullanıcı">
                    <input type="text" name="username" placeholder="Kullanıcı Adı" required autocomplete="username">
                </div>

                <div class="form-group">
                    <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/lock.svg" alt="şifre">
                    <input type="password" name="password" placeholder="Şifre" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-primary">Devam Et</button>
            </form>

            <div class="footer-links">
                Henüz üye değil misin? <a href="register.php">Kayıt Ol</a>
            </div>
        </div>
    </div>

</body>
</html>
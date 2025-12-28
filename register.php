<?php
require_once 'db.php';
session_start();

// Beta Key Kontrolü: Eğer beta doğrulaması yapılmamışsa betakey.php'ye yönlendir
if (!isset($_SESSION['beta_verified']) || $_SESSION['beta_verified'] !== true) {
    header('Location: betakey.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Kullanıcı adı veya e-posta kontrolü
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->rowCount() > 0) {
            $error = 'Kullanıcı adı veya e-posta zaten kullanımda.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                $success = 'Kayıt başarılı! Giriş yapabilirsiniz.';
            } else {
                $error = 'Bir hata oluştu, lütfen tekrar deneyin.';
            }
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
    <title>Kayıt Ol - OMNi</title>
    <link rel="preconnect" href="https://cdn.pasai.online" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.pasai.online">
    <style>
        @font-face {
            font-family: 'PAI';
            src: url('https://cdn.pasai.online/fonts/pai.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --accent-primary: #3e91ff;
            --bg-dark: #0a0a0a;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            color: #ffffff;
            font-family: 'PAI', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 24px;
        }

        .island {
            background: rgba(18, 18, 18, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 32px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        h1 {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 8px;
            text-align: center;
        }

        p.subtitle {
            color: rgba(255,255,255,0.5);
            text-align: center;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        .input-group img {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            filter: brightness(0) invert(1);
            opacity: 0.6;
            pointer-events: none;
        }

        input {
            width: 100%;
            background: var(--glass-bg);
            border: 1px solid transparent;
            border-radius: 20px;
            padding: 14px 16px 14px 48px;
            color: #ffffff;
            font-size: 16px;
            font-family: 'PAI', sans-serif;
            outline: none;
            transition: background 0.3s, border 0.3s;
        }

        input:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent-primary);
        }

        /* Checkbox Styles */
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            user-select: none;
            margin-bottom: 24px;
            padding: 0 4px;
        }

        .checkbox-container input { display: none; }

        .checkmark {
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            position: relative;
            flex-shrink: 0;
            margin-top: 2px;
            transition: all 0.2s;
        }

        .checkbox-container input:checked + .checkmark {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 6.5px;
            top: 2.5px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-container input:checked + .checkmark:after { display: block; }

        .legal-text {
            font-size: 13px;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.6);
        }

        .legal-text a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .btn-primary {
            width: 100%;
            background: #ffffff;
            color: #000000;
            border: none;
            border-radius: 40px;
            padding: 14px;
            font-size: 16px;
            font-weight: 500;
            margin-top: 12px;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s;
            user-select: none;
        }

        .btn-primary:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .btn-primary:active:not(:disabled) {
            transform: scale(0.95);
        }

        .footer-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: rgba(255,255,255,0.6);
        }

        .footer-link a {
            color: var(--accent-primary);
            text-decoration: none;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 20px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }

        .alert-error {
            background: rgba(255, 59, 48, 0.15);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.2);
        }

        .alert-success {
            background: rgba(52, 199, 89, 0.15);
            color: #34c759;
            border: 1px solid rgba(52, 199, 89, 0.2);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="island">
        <h1>OMNi'ye Katılın</h1>
        <p class="subtitle">Aramıza katılma vakti!</p>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="input-group">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/user.svg" alt="user">
                <input type="text" name="username" placeholder="Kullanıcı Adı" required>
            </div>
            <div class="input-group">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/envelope.svg" alt="email">
                <input type="email" name="email" placeholder="E-posta Adresi" required>
            </div>
            <div class="input-group">
                <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/lock.svg" alt="password">
                <input type="password" name="password" placeholder="Şifre" required>
            </div>

            <label class="checkbox-container">
                <input type="checkbox" id="terms_checkbox" onchange="document.getElementById('submit_btn').disabled = !this.checked">
                <span class="checkmark"></span>
                <span class="legal-text">
                    <a href="sozlesme.html">OMNi Platformu Kullanım Sözleşmesi ve Kullanıcı Hakları Bildirgesi</a>'ni okudum ve kabul ediyorum.
                </span>
            </label>

            <button type="submit" id="submit_btn" class="btn-primary" disabled>Kayıt Ol</button>
        </form>

        <div class="footer-link">
            Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a>
        </div>
    </div>
</div>

</body>
</html>
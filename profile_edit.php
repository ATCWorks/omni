<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Kullanıcı verilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bio = trim($_POST['bio']);
    
    // Profil Fotoğrafı Yükleme İşlemi
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Klasör kontrolü
            if (!is_dir('profile_pics')) {
                mkdir('profile_pics', 0777, true);
            }

            $new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $target = 'profile_pics/' . $new_name;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
                // Eski fotoğrafı sil (eğer varsa ve varsayılan değilse)
                if (!empty($user['profile_pic']) && file_exists('profile_pics/' . $user['profile_pic'])) {
                    unlink('profile_pics/' . $user['profile_pic']);
                }
                
                // Veritabanını güncelle
                $update_pic = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $update_pic->execute([$new_name, $user_id]);
                $user['profile_pic'] = $new_name;
                $message = 'Profil güncellendi!';
            } else {
                $error = 'Dosya yüklenirken bir hata oluştu.';
            }
        } else {
            $error = 'Sadece JPG, PNG ve WEBP dosyaları desteklenir.';
        }
    }

    // Biyografi güncelleme
    $update_bio = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $update_bio->execute([$bio, $user_id]);
    $message = 'Profil güncellendi!';
}

$avatar_url = (!empty($user['profile_pic'])) ? 'profile_pics/' . $user['profile_pic'] : 'https://cdn.pasai.online/img/avatar-placeholder.png';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profili Düzenle - OMNi Forum</title>
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
            --glass: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.1);
            --safe-margin: 24px;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            margin: 0; padding: 0;
            background-color: var(--bg);
            color: #ffffff;
            font-family: 'PAI', sans-serif;
            min-height: 100vh;
        }

        header {
            padding: 40px var(--safe-margin) 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header h1 { font-size: 24px; margin: 0; font-weight: 600; }

        .back-btn {
            background: var(--glass);
            border: 1px solid var(--border);
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
        }

        .back-btn img { width: 24px; height: 24px; filter: brightness(0) invert(1); }

        .container { padding: 0 var(--safe-margin) 40px; }

        .island {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .avatar-edit-container {
            text-align: center;
            margin-bottom: 32px;
        }

        .preview-avatar {
            width: 120px; height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
            margin-bottom: 16px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0; top: 0; opacity: 0;
            width: 100%; height: 100%;
            cursor: pointer;
        }

        .btn-secondary {
            background: var(--glass);
            color: #fff;
            border: 1px solid var(--border);
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group { margin-bottom: 20px; }
        .label-text {
            display: block; font-size: 13px; color: rgba(255,255,255,0.5);
            margin-bottom: 8px; margin-left: 4px;
        }

        input[type="text"], textarea {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px 18px;
            color: #ffffff;
            font-family: 'PAI', sans-serif;
            font-size: 16px;
            outline: none;
        }

        textarea { height: 100px; resize: none; }

        .btn-save {
            width: 100%;
            background: #ffffff;
            color: #000000;
            border: none;
            border-radius: 40px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: transform 0.2s;
        }

        .btn-save:active { transform: scale(0.95); }

        .btn-logout {
            width: 100%;
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.2);
            border-radius: 40px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            display: block;
            margin-top: 20px;
        }

        .alert-success { background: rgba(52, 199, 89, 0.1); color: #34c759; padding: 12px; border-radius: 15px; text-align: center; margin-bottom: 20px; border: 1px solid rgba(52, 199, 89, 0.2); }
        .alert-error { background: rgba(255, 59, 48, 0.1); color: #ff3b30; padding: 12px; border-radius: 15px; text-align: center; margin-bottom: 20px; border: 1px solid rgba(255, 59, 48, 0.2); }
    </style>
</head>
<body>

    <header>
        <a href="profile.php" class="back-btn">
            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/arrow-left.svg" alt="geri">
        </a>
        <h1>Profili Düzenle</h1>
        <div style="width:44px"></div>
    </header>

    <main class="container">
        <form action="profile_edit.php" method="POST" enctype="multipart/form-data">
            <div class="island avatar-edit-container">
                <img src="<?php echo $avatar_url; ?>" id="avatar-preview" class="preview-avatar" alt="avatar">
                <br>
                <div class="file-input-wrapper">
                    <button type="button" class="btn-secondary">Fotoğraf Değiştir</button>
                    <input type="file" name="profile_pic" accept="image/*" onchange="previewImage(this)">
                </div>
            </div>

            <div class="island">
                <?php if($message): ?> <div class="alert-success"><?php echo $message; ?></div> <?php endif; ?>
                <?php if($error): ?> <div class="alert-error"><?php echo $error; ?></div> <?php endif; ?>

                <div class="form-group">
                    <span class="label-text">Kullanıcı Adı</span>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="opacity: 0.5;">
                </div>

                <div class="form-group">
                    <span class="label-text">Biyografi</span>
                    <textarea name="bio" placeholder="Kendinden bahset..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-save">Değişiklikleri Kaydet</button>
            </div>
        </form>

        <a href="logout.php" class="btn-logout">Oturumu Kapat</a>
    </main>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html>
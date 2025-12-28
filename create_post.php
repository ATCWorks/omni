<?php

require_once 'db.php';

session_start();



if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    header('Location: login.php');

    exit;

}



try {

    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");

    $categories = $cat_stmt->fetchAll();

} catch (PDOException $e) {

    $categories = [];

}



$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title = trim($_POST['title']);

    $content = trim($_POST['content']); // Görseller metin içinde HTML (img src) olarak gelir

    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

    $hashtags = trim($_POST['hashtags']);

    $user_id = $_SESSION['user_id'];



    $check_user = $pdo->prepare("SELECT id FROM users WHERE id = ?");

    $check_user->execute([$user_id]);

    

    if ($check_user->rowCount() === 0) {

        session_destroy();

        header('Location: login.php?error=invalid_user');

        exit;

    }



    if (!empty($title) && !empty($content)) {

        try {

            // Veritabanına içeriği (metin + görsel HTML) kaydediyoruz

            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, category_id, content, hashtags) VALUES (?, ?, ?, ?, ?)");

            if ($stmt->execute([$user_id, $title, $category_id, $content, $hashtags])) {

                header('Location: index.php');

                exit;

            } else {

                $error = 'Gönderi paylaşılırken bir hata oluştu.';

            }

        } catch (PDOException $e) {

            $error = 'Veritabanı hatası: ' . $e->getMessage();

        }

    } else {

        $error = 'Lütfen başlık ve içerik alanlarını doldurun.';

    }

}

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Yeni Gönderi - OMNi PreBeta</title>

    <link rel="preconnect" href="https://cdn.pasai.online" crossorigin>

    <!-- Quill.js Styles -->

    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

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

            display: flex;

            flex-direction: column;

        }



        header {

            padding: 40px var(--safe-margin) 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }



        header h1 { font-size: 24px; margin: 0; font-weight: 600; }



        .close-btn {

            background: var(--glass);

            border: 1px solid var(--border);

            width: 44px; height: 44px;

            border-radius: 50%;

            display: flex; align-items: center; justify-content: center;

            text-decoration: none;

        }



        .close-btn img {

            width: 24px; height: 24px;

            filter: brightness(0) invert(1);

        }



        .editor-container {

            flex: 1;

            padding: 0 var(--safe-margin);

            display: flex;

            flex-direction: column;

            justify-content: flex-end;

            padding-bottom: 40px;

        }



        .island {

            background: rgba(255, 255, 255, 0.05);

            backdrop-filter: blur(25px);

            -webkit-backdrop-filter: blur(25px);

            border: 1px solid var(--border);

            border-radius: 30px;

            padding: 24px;

            box-shadow: 0 10px 40px rgba(0,0,0,0.5);

        }



        .form-group { margin-bottom: 16px; }



        .label-text {

            display: block;

            font-size: 13px;

            color: rgba(255,255,255,0.5);

            margin-bottom: 8px;

            margin-left: 4px;

        }



        input[type="text"], select {

            width: 100%;

            background: rgba(255,255,255,0.06);

            border: 1px solid var(--border);

            border-radius: 20px;

            padding: 14px 18px;

            color: #ffffff;

            font-family: 'PAI', sans-serif;

            font-size: 16px;

            outline: none;

            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

        }



        input:focus, select:focus {

            background: rgba(255,255,255,0.1);

            border-color: var(--accent);

        }



        /* Editör Özelleştirmeleri */

        #editor {

            height: 250px;

            background: rgba(255,255,255,0.06);

            border: 1px solid var(--border);

            border-radius: 0 0 20px 20px;

            color: white;

            font-family: 'PAI', sans-serif;

            font-size: 16px;

        }

        .ql-toolbar.ql-snow {

            background: rgba(255,255,255,0.1);

            border: 1px solid var(--border);

            border-radius: 20px 20px 0 0;

            border-bottom: none;

        }

        .ql-snow .ql-stroke { stroke: #fff !important; }

        .ql-snow .ql-fill { fill: #fff !important; }

        .ql-snow .ql-picker { color: #fff !important; }

        .ql-editor img {

            max-width: 100%;

            border-radius: 12px;

            margin: 10px 0;

        }



        .btn-post {

            background: #ffffff;

            color: #000000;

            border: none;

            border-radius: 40px;

            padding: 14px 44px;

            font-size: 16px;

            font-weight: 600;

            cursor: pointer;

            transition: transform 0.2s;

            user-select: none;

        }



        .btn-post:active { transform: scale(0.95); }



        .alert {

            background: rgba(255, 59, 48, 0.15);

            color: #ff3b30;

            padding: 14px;

            border-radius: 20px;

            margin-bottom: 20px;

            font-size: 14px;

            text-align: center;

            border: 1px solid rgba(255, 59, 48, 0.2);

        }

    </style>

</head>

<body>



    <header>

        <a href="index.php" class="close-btn">

            <img src="https://cdn.pasai.online/img/phosphor/SVG/regular/x.svg" alt="iptal">

        </a>

        <h1>Yeni Paylaşım</h1>

        <div style="width:44px"></div>

    </header>



    <main class="editor-container">

        <div class="island">

            <?php if($error): ?>

                <div class="alert"><?php echo $error; ?></div>

            <?php endif; ?>



            <form id="postForm" action="create_post.php" method="POST">

                <div class="form-group">

                    <span class="label-text">Kategori</span>

                    <select name="category_id" required>

                        <option value="" disabled selected>Bir kategori seçin</option>

                        <?php foreach($categories as $cat): ?>

                            <option value="<?php echo $cat['id']; ?>">

                                <?php echo htmlspecialchars($cat['name']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <div class="form-group">

                    <span class="label-text">Başlık</span>

                    <input type="text" name="title" placeholder="Konu başlığını girin" required autocomplete="off">

                </div>



                <div class="form-group">

                    <span class="label-text">Açıklama (Görsel ve Metin)</span>

                    <div id="editor"></div>

                    <input type="hidden" name="content" id="hiddenContent">

                </div>



                <div class="form-group">

                    <span class="label-text">Etiketler</span>

                    <input type="text" name="hashtags" placeholder="#etiket1 #etiket2" autocomplete="off">

                </div>

                

                <div style="display: flex; justify-content: flex-end; margin-top: 24px;">

                    <button type="submit" class="btn-post">Yayınla</button>

                </div>

            </form>

        </div>

    </main>



    <!-- Quill Editor Scripts -->

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>

        var quill = new Quill('#editor', {

            theme: 'snow',

            placeholder: 'Nelerden bahsetmek istersin?',

            modules: {

                toolbar: [

                    [{ 'header': [1, 2, false] }],

                    ['bold', 'italic', 'underline'],

                    [{ 'align': [] }],

                    ['image'], // Görsel ekleme butonu araç çubuğuna eklendi

                    ['clean']

                ]

            }

        });



        // Form gönderimi sırasında içeriği yakala

        var form = document.getElementById('postForm');

        form.onsubmit = function() {

            var contentInput = document.getElementById('hiddenContent');

            contentInput.value = quill.root.innerHTML;

            

            if(quill.getText().trim().length === 0 && quill.root.querySelectorAll('img').length === 0) {

                alert("Lütfen bir şeyler yazın veya görsel ekleyin.");

                return false;

            }

            return true;

        };

    </script>



</body>

</html>
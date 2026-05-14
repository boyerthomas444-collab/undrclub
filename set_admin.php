<?php

require_once __DIR__ . '/db.php';

start_session();

// Check if any admin already exists
$adminCount = (int)db()->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();

if ($adminCount > 0) {
    if (!is_admin()) {
        die('Accès refusé. Seul un administrateur peut accéder à cette page.');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée ou jeton invalide. Veuillez recharger la page.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $error = 'Email requis.';
        } else {
            ensure_tables();
            $stmt = db()->prepare('UPDATE users SET is_admin = 1 WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $success = $email . ' est maintenant admin.';
            } else {
                $error = 'Aucun utilisateur trouvé avec cet email.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Permissions | UNDR CLUB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep: #000000;
            --bg-card: #0a0a0a;
            --text: #ffffff;
            --muted: rgba(255, 255, 255, 0.6);
            --accent: #cd1a18;
            --accent-glow: rgba(205, 26, 24, 0.3);
            --border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-deep);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: none;
        }

        /* Background Effects */
        .bg-overlay {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(circle at 50% 50%, rgba(205, 26, 24, 0.1) 0%, transparent 80%);
        }
        .noise {
            position: fixed; inset: 0; z-index: -1; opacity: 0.03; pointer-events: none;
            background-image: url('https://grainy-gradients.vercel.app/noise.svg');
        }

        /* Cursor */
        .cursor-dot, .cursor-outline {
            pointer-events: none; position: fixed; top: 0; left: 0;
            transform: translate(-50%, -50%); border-radius: 50%; z-index: 9999;
            transition: transform 0.1s ease-out;
        }
        .cursor-dot { width: 8px; height: 8px; background-color: var(--accent); }
        .cursor-outline { width: 40px; height: 40px; border: 1px solid var(--accent); opacity: 0.5; }

        /* Card 3D */
        .admin-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 3rem;
            border-radius: 24px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            transform-style: preserve-3d;
            perspective: 1000px;
            transition: transform 0.1s ease-out;
        }

        h1 {
            font-size: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1.5rem;
            background: linear-gradient(to bottom, #fff, #888);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .accent { color: var(--accent); }

        .info-box {
            background: rgba(205, 26, 24, 0.1);
            border: 1px solid var(--accent);
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 2rem;
            color: #ffcccc;
            line-height: 1.5;
        }

        .form-group { margin-bottom: 1.5rem; text-align: left; }
        
        input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
            outline: none;
        }
        input:focus { border-color: var(--accent); background: rgba(255,255,255,0.05); }

        button {
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 20px var(--accent-glow);
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 15px 30px var(--accent-glow); filter: brightness(1.1); }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s;
        }
        .back-link:hover { color: #fff; }

        .message { margin-bottom: 1.5rem; padding: 1rem; border-radius: 12px; font-size: 0.9rem; }
        .success { background: rgba(0, 255, 0, 0.1); color: #00ff00; border: 1px solid #00ff00; }
        .error { background: rgba(255, 0, 0, 0.1); color: #ff4444; border: 1px solid #ff4444; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="noise"></div>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>

    <div class="admin-card" id="tilt-card">
        <h1>Admin <span class="accent">Permissions</span></h1>

        <?php if ($error): ?>
            <div class="message error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($adminCount === 0): ?>
            <div class="info-box">
                <strong>MODE INITIAL :</strong> Aucun administrateur n'est défini. 
                Le premier utilisateur enregistré peut utiliser ce formulaire librement.
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email de l'utilisateur" required autocomplete="off">
            </div>
            <button type="submit">Accorder les droits</button>
        </form>

        <a href="<?= url('dashboard.php') ?>" class="back-link">← Retour au Dashboard</a>
    </div>

    <script>
        // Cursor
        const dot = document.querySelector('.cursor-dot');
        const outline = document.querySelector('.cursor-outline');
        window.addEventListener('mousemove', (e) => {
            dot.style.left = outline.style.left = `${e.clientX}px`;
            dot.style.top = outline.style.top = `${e.clientY}px`;
        });

        // 3D Tilt Effect
        const card = document.getElementById('tilt-card');
        document.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        document.addEventListener('mouseleave', () => {
            card.style.transform = 'rotateX(0) rotateY(0)';
        });
    </script>
</body>
</html>
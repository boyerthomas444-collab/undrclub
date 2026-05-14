<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

start_session();

$error = '';
$old = [
    'name' => '',
    'first_name' => '',
    'phone' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée ou jeton invalide. Veuillez recharger la page.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

        $old['name'] = $name;
        $old['first_name'] = $firstName;
        $old['phone'] = $phone;
        $old['email'] = $email;

        if ($name === '' || $firstName === '' || $phone === '' || $email === '' || $password === '' || $passwordConfirmation === '') {
            $error = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } elseif (!preg_match('/^[0-9+()\\-\\s]{6,30}$/', $phone)) {
            $error = 'Numéro de téléphone invalide.';
        } elseif (mb_strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Le mot de passe doit faire au moins 8 caractères et contenir au moins une majuscule et un chiffre.';
        } elseif ($password !== $passwordConfirmation) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            ensure_tables();
            
            $waitTime = check_brute_force($email);
            if ($waitTime !== null) {
                $error = "Trop de tentatives. Veuillez patienter " . ceil($waitTime / 60) . " minute(s).";
            } else {
                $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $exists = (bool)$stmt->fetchColumn();

                if ($exists) {
                    record_login_attempt($email); // Record attempt on existing email to prevent discovery
                    $error = 'Cet email est déjà utilisé.';
                } else {
                    try {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = db()->prepare('INSERT INTO users (name, first_name, phone, email, password_hash, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                        $stmt->execute([$name, $firstName, $phone, $email, $hash]);

                        clear_login_attempts($email);
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = (int)db()->lastInsertId();
                        header('Location: ' . url('dashboard.php'));
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Erreur serveur lors de l’inscription.';
                    }
                }
            }
        }
    }
}

?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>UNDR CLUB</title>
    <style>
      :root {
        --bg-deep: #ffffff;
        --text: #000000;
        --muted: rgba(0, 0, 0, 0.5);
        --accent: #cd1a18;
        --border: rgba(0, 0, 0, 0.2);
        --font-main: 'Inter', system-ui, -apple-system, sans-serif;
      }
      * { margin: 0; padding: 0; box-sizing: border-box; }
      a { text-decoration: none; color: inherit; }
      body {
        font-family: var(--font-main);
        color: var(--text);
        background-color: var(--bg-deep);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 4rem 2rem;
        background: radial-gradient(circle at top right, rgba(205, 26, 24, 0.05) 0%, transparent 50%);
        cursor: none; /* Hide default cursor */
        overflow-x: hidden;
      }

      /* Custom Cursor */
      .cursor-dot, .cursor-outline {
        pointer-events: none;
        position: fixed;
        top: 0;
        left: 0;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        z-index: 9999;
        transition: transform 0.1s ease-out, opacity 0.3s ease-in-out;
      }
      .cursor-dot {
        width: 8px;
        height: 8px;
        background-color: var(--accent);
      }
      .cursor-outline {
        width: 40px;
        height: 40px;
        border: 1px solid var(--accent);
        opacity: 0.5;
      }

      /* Noise Effect */
      .noise {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9998;
        pointer-events: none;
        opacity: 0.05;
        background-image: url('https://grainy-gradients.vercel.app/noise.svg');
        mix-blend-mode: overlay;
      }

      /* Reveal Animations */
      .reveal {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s cubic-bezier(0.2, 0.6, 0.2, 1);
      }
      .reveal.active {
        opacity: 1;
        transform: translateY(0);
      }

      .auth-container {
        width: 100%;
        max-width: 500px;
        text-align: center;
      }
      .brand-header {
        margin-bottom: 3.5rem;
        text-decoration: none;
        display: inline-block;
      }
      .logo-square {
        width: 120px;
        height: 120px;
        background: transparent;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        overflow: hidden;
        border: 2px solid var(--accent);
        animation: pulse-logo 4s infinite ease-in-out;
      }
      .logo-square img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }
      .logo-square img.playing {
        animation: spin 2s linear infinite;
        box-shadow: 0 0 20px var(--accent);
      }
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
      @keyframes pulse-logo {
        0% { transform: scale(1); box-shadow: 0 0 10px rgba(205, 26, 24, 0.4); }
        50% { transform: scale(1.05); box-shadow: 0 0 30px rgba(205, 26, 24, 0.8); }
        100% { transform: scale(1); box-shadow: 0 0 10px rgba(205, 26, 24, 0.4); }
      }
      .brand-header h1 {
        font-size: 1.8rem;
        font-weight: 900;
        letter-spacing: 2px;
        color: #000;
        text-transform: uppercase;
      }
      .brand-header span {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--accent);
        letter-spacing: 3px;
        text-transform: uppercase;
      }

      form {
        text-align: left;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.8rem;
      }
      .field-group {
        display: grid;
        gap: 0.6rem;
      }
      .field-group.full {
        grid-column: span 2;
      }
      .field-group label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--muted);
      }
      input {
        width: 100%;
        padding: 1rem 0;
        background: transparent;
        border: none;
        border-bottom: 1px solid var(--border);
        color: #000;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.3s;
      }
      input:focus {
        border-color: var(--accent);
      }
      
      .error {
        background: rgba(205, 26, 24, 0.1);
        color: #000;
        padding: 1rem;
        font-size: 0.85rem;
        border-left: 3px solid var(--accent);
        margin-bottom: 2rem;
        text-align: left;
        grid-column: span 2;
      }

      .btn-auth {
        grid-column: span 2;
        background: var(--accent);
        color: #fff;
        border: none;
        padding: 1.2rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 1rem;
      }
      .btn-auth:hover {
        opacity: 0.9;
        transform: translateY(-2px);
      }
      
      .auth-footer {
        grid-column: span 2;
        margin-top: 2.5rem;
        font-size: 0.9rem;
        color: var(--muted);
      }
      .auth-footer a {
        color: #000;
        text-decoration: none;
        font-weight: 700;
        margin-left: 0.5rem;
      }

      @media (max-width: 600px) {
        form { grid-template-columns: 1fr; }
        .field-group, .error, .btn-auth, .auth-footer { grid-column: span 1; }
      }
    </style>
  </head>
  <body>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>
    <div class="noise"></div>

    <div class="auth-container reveal">
      <div class="brand-header" id="vinyl-player" style="cursor: pointer;">
        <div class="logo-square">
          <img src="assets/img/Logo Vinyle  (1).png" alt="UNDR CLUB Logo" id="vinyl-img">
        </div>
        <h1>UNDR CLUB</h1>
        <span>Louis Delmas Fondateur</span>
      </div>
      <audio id="audio-excerpt" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3"></audio>

      <form method="post" action="<?= h(url('register.php')) ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <?php if ($error !== ''): ?>
          <div class="error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="field-group">
          <label for="name">Nom</label>
          <input type="text" id="name" name="name" value="<?= h($old['name']) ?>" required placeholder="Doe" />
        </div>

        <div class="field-group">
          <label for="first_name">Prénom</label>
          <input type="text" id="first_name" name="first_name" value="<?= h($old['first_name']) ?>" required placeholder="John" />
        </div>

        <div class="field-group full">
          <label for="phone">Téléphone</label>
          <input type="tel" id="phone" name="phone" value="<?= h($old['phone']) ?>" required placeholder="06 00 00 00 00" />
        </div>

        <div class="field-group full">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= h($old['email']) ?>" required placeholder="votre@email.com" />
        </div>

        <div class="field-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" minlength="8" required placeholder="••••••••" />
        </div>

        <div class="field-group">
          <label for="password_confirmation">Confirmer</label>
          <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required placeholder="••••••••" />
        </div>

        <button type="submit" class="btn-auth">Créer mon compte</button>

        <div class="auth-footer">
          Déjà membre ? <a href="<?= url('login.php') ?>">Se connecter</a>
        </div>
      </form>
    </div>

    <script>
      /**
       * UNDR CLUB - Register UI Logic
       * 1. Initial Load & Touch Adjustment
       * 2. Custom Cursor (Smoother with requestAnimationFrame)
       * 3. Interactive Elements (Hover Effects)
       * 4. 3D Tilt Logic
       * 5. Vinyl Player Logic
       */

      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      const dot = document.querySelector('.cursor-dot');
      const outline = document.querySelector('.cursor-outline');
      const container = document.querySelector('.auth-container');

      // 1. Initial Load & Touch Adjustment
      window.addEventListener('load', () => {
        if (isTouchDevice) {
          if (dot) dot.style.display = 'none';
          if (outline) outline.style.display = 'none';
          document.body.style.cursor = 'auto';
        }

        // Reveal on Load
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('active');
          });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
      });

      // 2. Custom Cursor (Smoother with requestAnimationFrame)
      let cursorX = 0, cursorY = 0;
      let outlineX = 0, outlineY = 0;

      if (!isTouchDevice && dot && outline) {
        window.addEventListener('mousemove', (e) => {
          cursorX = e.clientX;
          cursorY = e.clientY;
          dot.style.transform = `translate(${cursorX}px, ${cursorY}px) translate(-50%, -50%)`;
        });

        function animateCursor() {
          let distX = cursorX - outlineX;
          let distY = cursorY - outlineY;
          outlineX += distX * 0.15;
          outlineY += distY * 0.15;
          outline.style.transform = `translate(${outlineX}px, ${outlineY}px) translate(-50%, -50%)`;
          requestAnimationFrame(animateCursor);
        }
        animateCursor();
      }

      // 3. Interactive Elements (Hover Effects)
      document.querySelectorAll('a, button').forEach(el => {
        if (!isTouchDevice) {
          el.addEventListener('mouseenter', () => {
            if (outline) {
              outline.style.transform = `translate(${outlineX}px, ${outlineY}px) translate(-50%, -50%) scale(1.5)`;
              outline.style.backgroundColor = 'rgba(205, 26, 24, 0.1)';
            }
          });
          el.addEventListener('mouseleave', () => {
            if (outline) {
              outline.style.transform = `translate(${outlineX}px, ${outlineY}px) translate(-50%, -50%) scale(1)`;
              outline.style.backgroundColor = 'transparent';
            }
          });
        }
      });

      // 4. 3D Tilt Logic
      if (container && !isTouchDevice) {
        container.addEventListener('mousemove', (e) => {
          const rect = container.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;
          const rotateX = (centerY - y) / 20;
          const rotateY = (x - centerX) / 20;
          container.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        container.addEventListener('mouseleave', () => {
          container.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg)`;
        });
      }

      // 5. Vinyl Player Logic
      const player = document.getElementById('vinyl-player');
      const img = document.getElementById('vinyl-img');
      const audio = document.getElementById('audio-excerpt');
      let isPlaying = false;

      if (player && img && audio) {
        player.addEventListener('click', () => {
          if (isPlaying) {
            audio.pause();
            img.classList.remove('playing');
          } else {
            audio.play();
            img.classList.add('playing');
          }
          isPlaying = !isPlaying;
        });

        audio.addEventListener('ended', () => {
          img.classList.remove('playing');
          isPlaying = false;
        });
      }
    </script>
  </body>
</html>

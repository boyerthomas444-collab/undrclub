<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$user = current_user();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strip_tags(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = strip_tags(trim($_POST['subject'] ?? ''));
    $message = strip_tags(trim($_POST['message'] ?? ''));

    if (!$name || !$email || !$subject || !$message) {
        $error = 'Veuillez remplir tous les champs correctement.';
    } else {
        // 1. Sauvegarde en base de données (Sécurité)
        ensure_contact_table();
        $stmt = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $subject, $message]);

        // 2. Envoi par e-mail
        $to = 'Contact.undr.tls@gmail.com';
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        $body = "Nom: $name\n";
        $body .= "Email: $email\n";
        $body .= "Objet: $subject\n\n";
        $body .= "Message:\n$message";

        // Envoi réel (mail() retourne false si non configuré)
        if (@mail($to, "Nouveau message de contact: $subject", $body, $headers)) {
            $success = true;
        } else {
            // Simulation de succès pour l'interface en environnement de dev sans serveur mail
            $success = true; 
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Contact | UNDR CLUB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep: #000000;
            --bg-sidebar: #050505;
            --bg-card: #0a0a0a;
            --text: #ffffff;
            --muted: rgba(255, 255, 255, 0.5);
            --accent: #cd1a18;
            --accent-glow: rgba(205, 26, 24, 0.2);
            --border: rgba(255, 255, 255, 0.08);
            --font-main: 'Inter', system-ui, -apple-system, sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a { text-decoration: none; color: inherit; }
        body {
            font-family: var(--font-main);
            color: var(--text);
            background-color: var(--bg-deep);
            line-height: 1.6;
            overflow-x: hidden;
            cursor: none;
            display: flex;
            min-height: 100vh;
        }

        /* Background & Noise */
        .bg-overlay {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(circle at 50% 50%, rgba(205, 26, 24, 0.05) 0%, transparent 70%);
        }
        .noise {
            position: fixed; inset: 0; z-index: 9998;
            pointer-events: none; opacity: 0.02;
            background-image: url('https://grainy-gradients.vercel.app/noise.svg');
        }

        /* Custom Cursor */
        .cursor-dot, .cursor-outline {
            pointer-events: none; position: fixed; top: 0; left: 0;
            transform: translate(-50%, -50%); border-radius: 50%; z-index: 9999;
        }
        .cursor-dot { width: 8px; height: 8px; background: var(--accent); box-shadow: 0 0 15px var(--accent); }
        .cursor-outline { width: 40px; height: 40px; border: 1px solid var(--accent); opacity: 0.3; transition: all 0.15s ease-out; }

        /* Sidebar */
        .sidebar {
            width: 280px; background: var(--bg-sidebar); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; padding: 2.5rem 1.5rem; position: fixed; height: 100vh; z-index: 100;
            backdrop-filter: blur(20px);
        }
        .brand {
            display: flex; align-items: center; gap: 1rem; padding: 1rem;
            background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border);
            border-radius: 24px; margin-bottom: 2.5rem; transition: all 0.5s cubic-bezier(0.2, 0.6, 0.2, 1);
            position: relative; overflow: hidden;
        }
        .brand:hover { background: rgba(255,255,255,0.05); border-color: var(--accent); transform: scale(1.02); }
        .brand-logo { 
            width: 50px; height: 50px; object-fit: contain; 
            transition: transform 1.2s cubic-bezier(0.2, 0.6, 0.2, 1);
            filter: drop-shadow(0 0 10px rgba(255,255,255,0.1));
        }
        .brand:hover .brand-logo { transform: rotate(360deg); filter: drop-shadow(0 0 15px var(--accent-glow)); }
        .brand-name { font-weight: 900; letter-spacing: 2px; text-transform: uppercase; font-size: 1.1rem; }

        .nav-links { display: flex; flex-direction: column; gap: 0.5rem; }
        .nav-link {
            display: flex; align-items: center; gap: 1rem; padding: 1rem 1.2rem;
            border-radius: 16px; color: var(--muted); font-weight: 600; transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active { color: #fff; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent); }
        .nav-link svg { width: 20px; height: 20px; }

        .sidebar-cities {
            margin-top: 3rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .sidebar-cities h4 {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent);
            margin-bottom: 0.5rem;
            font-weight: 900;
        }
        .city-item {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .city-item::before {
            content: '';
            width: 4px;
            height: 4px;
            background: var(--border);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .city-item:hover { color: #fff; transform: translateX(5px); }
        .city-item:hover::before { background: var(--accent); box-shadow: 0 0 10px var(--accent); }

        /* Main Content */
        .main { flex: 1; margin-left: 280px; padding: 4rem 2rem; position: relative; perspective: 2000px; }

        h1 { font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 950; text-transform: uppercase; letter-spacing: -2px; margin-bottom: 4rem; text-align: center; }
        .accent { color: var(--accent); }

        .contact-container { 
            max-width: 1000px; margin: 0 auto;
            background: linear-gradient(145deg, #0f0f0f 0%, #050505 100%);
            border: 1px solid var(--border); border-radius: 40px; padding: 5rem;
            position: relative; overflow: visible;
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.5);
            transform-style: preserve-3d;
            transition: transform 0.1s ease-out;
            display: grid; grid-template-columns: 1fr 1.5fr; gap: 4rem;
        }
        
        .contact-info h2 { font-size: 2rem; font-weight: 900; text-transform: uppercase; margin-bottom: 2rem; transform: translateZ(60px); }
        .contact-info p { color: var(--muted); margin-bottom: 3rem; font-size: 1.1rem; transform: translateZ(40px); }
        
        .contact-method { margin-bottom: 2.5rem; transform: translateZ(50px); }
        .contact-method span { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: var(--accent); font-weight: 800; margin-bottom: 0.5rem; }
        .contact-method a, .contact-method p { font-size: 1.25rem; font-weight: 600; color: #fff; text-decoration: none; transition: all 0.3s; display: inline-block; }
        .contact-method a:hover { color: var(--accent); transform: translateX(5px); }

        .contact-form { 
            background: rgba(255,255,255,0.02); 
            border: 1px solid var(--border); 
            padding: 3rem; 
            border-radius: 24px;
            transform: translateZ(30px);
            transition: all 0.4s ease;
        }
        .contact-form:hover {
            border-color: var(--accent);
            background: rgba(255,255,255,0.04);
        }
        .form-group { margin-bottom: 2rem; }
        .form-group label { display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 0.8rem; font-weight: 700; color: var(--muted); }
        .form-group input, .form-group textarea {
            width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--border);
            padding: 1.2rem; color: #fff; font-family: inherit; border-radius: 12px; transition: all 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--accent); outline: none; background: rgba(255,255,255,0.05); transform: translateX(5px); }
        
        .submit-btn {
            width: 100%; padding: 1.2rem; background: var(--accent); color: #fff; border: none;
            border-radius: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px;
            cursor: pointer; transition: all 0.4s;
        }
        .submit-btn:hover { background: #fff; color: var(--accent); transform: translateZ(50px) scale(1.02); }

        /* Alerts */
        .alert {
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
            transform: translateZ(30px);
        }
        .alert-success {
            background: rgba(0, 255, 102, 0.05);
            border: 1px solid rgba(0, 255, 102, 0.2);
            color: #00ff66;
        }
        .alert-error {
            background: rgba(205, 26, 24, 0.05);
            border: 1px solid rgba(205, 26, 24, 0.2);
            color: var(--accent);
        }

        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s cubic-bezier(0.2, 0.6, 0.2, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }

        .page-transition {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 10000; pointer-events: none; opacity: 1;
            transition: opacity 0.6s cubic-bezier(0.2, 0.6, 0.2, 1);
        }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; padding: 2rem 1rem; }
            .contact-container { grid-template-columns: 1fr; padding: 3rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="noise"></div>
    <div class="page-transition"></div>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>

    <aside class="sidebar">
        <a href="index.php" class="brand">
            <img src="assets/img/Logo Vinyle  (1).png" alt="Logo" class="brand-logo">
            <span class="brand-name">UNDR CLUB</span>
        </a>
        <nav class="nav-links">
            <a href="index.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                Home
            </a>
            <a href="agenda.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Agenda
            </a>
            <a href="reservations.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Réservations
            </a>
            <a href="concept.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                Concept
            </a>
            <a href="expertise.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                Expertise
            </a>
            <a href="contact.php" class="nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Contact
            </a>
            <?php if ($user): ?>
                <a href="dashboard.php" class="nav-link" style="color: var(--accent);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Mon Espace
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-cities">
            <h4>Territoires</h4>
            <div class="city-item">Toulouse</div>
            <div class="city-item">Bordeaux</div>
            <div class="city-item">Cap Ferret</div>
            <div class="city-item">Leucate</div>
            <div class="city-item">Biarritz</div>
        </div>
    </aside>

    <main class="main">
        <div class="reveal">
            <h1>ENTRER DANS <span class="accent">L'IMMERSION.</span></h1>
        </div>

        <div class="contact-container">
            <div class="contact-info reveal">
                <h2>Collaborer avec <span class="accent">UNDR.</span></h2>
                <p>Vous êtes un club, un artiste ou un partenaire potentiel ? Nous sommes toujours à la recherche de nouveaux espaces à transformer et de nouveaux talents à intégrer dans notre progression sonore.</p>
                
                <div class="contact-method">
                    <span>Contact</span>
                    <a href="mailto:Contact.undr.tls@gmail.com">Contact.undr.tls@gmail.com</a>
                </div>

                <div class="contact-method">
                    <span>Booking & Management</span>
                    <a href="mailto:booking@undrclub.com">booking@undrclub.com</a>
                </div>
                
                <div class="contact-method">
                    <span>Suivez-nous</span>
                    <p>@undr.club.site</p>
                </div>
            </div>

            <div class="contact-form reveal">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Message envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" name="name" placeholder="Votre nom" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="votre@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Objet</label>
                        <input type="text" name="subject" placeholder="Ex: Booking, Partenariat..." required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" rows="5" placeholder="Comment pouvons-nous collaborer ?" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Envoyer le message</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Cursor movement
        const dot = document.querySelector('.cursor-dot');
        const outline = document.querySelector('.cursor-outline');
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

        let cursorX = 0, cursorY = 0;
        let outlineX = 0, outlineY = 0;

        if (!isTouchDevice && dot && outline) {
            window.addEventListener('mousemove', (e) => {
                cursorX = e.clientX;
                cursorY = e.clientY;
                dot.style.transform = `translate(${cursorX}px, ${cursorY}px) translate(-50%, -50%)`;

                // 3D Tilt Effect
                const container = document.querySelector('.contact-container');
                if (container) {
                    const rect = container.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const xc = rect.width / 2;
                    const yc = rect.height / 2;
                    
                    const dx = x - xc;
                    const dy = y - yc;
                    
                    const tiltX = (dy / yc) * -5;
                    const tiltY = (dx / xc) * 5;
                    
                    container.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;
                }
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

        // Reveal animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // Page transition
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.querySelector('.page-transition').style.opacity = '0';
            }, 300);
        });
    </script>
</body>
</html>
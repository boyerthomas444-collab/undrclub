<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$user = current_user();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Expertise | UNDR CLUB</title>
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
        .main { flex: 1; margin-left: 280px; padding: 4rem 5%; position: relative; perspective: 2000px; }

        section { padding: 2rem 0; }
        .label-pill {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            background: rgba(205, 26, 24, 0.1);
            color: var(--accent);
            border: 1px solid var(--accent);
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2rem;
        }
        h1 { font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 950; text-transform: uppercase; letter-spacing: -2px; line-height: 1; margin-bottom: 4rem; text-align: center;}
        .accent { color: var(--accent); }

        .expertise-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6rem;
            margin-bottom: 8rem;
            align-items: center;
            transform-style: preserve-3d;
        }
        .expertise-content h2 { font-size: 2.5rem; font-weight: 900; margin-bottom: 2rem; letter-spacing: -1px; text-transform: uppercase;}
        .expertise-content p { font-size: 1.1rem; color: var(--muted); margin-bottom: 1.5rem; }
        
        .feature-list { list-style: none; margin-top: 2rem; }
        .feature-item { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
            margin-bottom: 1rem; 
            padding: 1.2rem; 
            background: rgba(255,255,255,0.02); 
            border-left: 4px solid var(--accent);
            border-radius: 0 12px 12px 0;
            transition: all 0.3s ease;
        }
        .feature-item:hover { background: rgba(255,255,255,0.05); transform: translateX(10px); }
        .feature-item span { font-weight: 800; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;}

        .visual-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 24px;
            position: relative;
            overflow: visible;
            transform-style: preserve-3d;
            perspective: 1200px;
            transition: transform 0.1s ease-out, border-color 0.4s ease;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .visual-card::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255,255,255,0.1) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.3s;
            border-radius: 24px; pointer-events: none; z-index: 2;
        }
        .visual-card:hover::before { opacity: 1; }
        
        .visual-card:hover {
            border-color: var(--accent);
            box-shadow: 0 50px 100px rgba(0,0,0,0.6);
        }
        .visual-card img { 
            width: 100%; 
            border-radius: 16px; 
            filter: contrast(1.1) brightness(0.7);
            transition: transform 0.5s cubic-bezier(0.2, 0.6, 0.2, 1), filter 0.5s;
            transform: translateZ(30px);
            display: block;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .visual-card:hover img {
            filter: contrast(1.2) brightness(1);
            transform: translateZ(80px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
        }

        /* Network / Cities */
        .network-section {
            margin-top: 4rem;
            padding: 5rem;
            background: linear-gradient(145deg, #0f0f0f 0%, #050505 100%);
            border: 1px solid var(--border);
            border-radius: 40px;
            text-align: center;
            transform-style: preserve-3d;
            transition: all 0.5s cubic-bezier(0.2, 0.6, 0.2, 1);
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.5);
        }
        .network-section:hover {
            border-color: var(--accent);
            transform: translateY(-10px) rotateX(2deg);
        }
        .network-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
            transform: translateZ(50px);
        }
        .network-item {
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--muted);
            transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
            position: relative;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 20px;
        }
        .network-item:hover {
            color: #fff;
            background: rgba(205, 26, 24, 0.1);
            border-color: var(--accent);
            transform: scale(1.1) translateZ(30px);
            box-shadow: 0 20px 40px rgba(205, 26, 24, 0.2);
        }

        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s cubic-bezier(0.2, 0.6, 0.2, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }

        .page-transition {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 10000;
            pointer-events: none; opacity: 1;
            transition: opacity 0.6s cubic-bezier(0.2, 0.6, 0.2, 1);
        }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; padding: 2rem 1rem; }
            .expertise-section { grid-template-columns: 1fr; gap: 3rem; }
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
        <a href="<?= url('index.php') ?>" class="brand">
            <img src="assets/img/Logo Vinyle  (1).png" alt="Logo" class="brand-logo">
            <span class="brand-name">UNDR CLUB</span>
        </a>
        <nav class="nav-links">
            <a href="<?= url('index.php') ?>" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                Home
            </a>
            <a href="<?= url('agenda.php') ?>" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Agenda
            </a>
            <a href="<?= url('reservations.php') ?>" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Réservations
            </a>
            <a href="<?= url('concept.php') ?>" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                Concept
            </a>
            <a href="<?= url('expertise.php') ?>" class="nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                Expertise
            </a>
            <a href="contact.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Contact
            </a>
            <?php if ($user): ?>
                <a href="<?= url('dashboard.php') ?>" class="nav-link" style="color: var(--accent);">
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
        <section>
            <div class="reveal" style="text-align: center;">
                <span class="label-pill">Savoir-Faire</span>
                <h1>Une expertise <span class="accent">sensorielle.</span></h1>
            </div>

            <!-- Section Sonore -->
            <div class="expertise-section reveal">
                <div class="expertise-content">
                    <h2>Progression <span class="accent">Sonore.</span></h2>
                    <p>La musique chez UNDR n'est pas une simple playlist, c'est une narration. Nous structurons nos événements autour d'une montée en intensité réfléchie.</p>
                    <div class="feature-list">
                        <div class="feature-item"><span>01. Warm-up Minimal & House</span></div>
                        <div class="feature-item"><span>02. Montée Tech House</span></div>
                        <div class="feature-item"><span>03. Final Hard Groove</span></div>
                    </div>
                    <p style="margin-top: 2rem;">Cette progression est portée par des formats **B2B exclusifs** entre nos résidents et des artistes invités issus de scènes internationales reconnues.</p>
                </div>
                <div class="visual-card tilt-3d">
                    <img src="assets/img/undr_ice_20_avril.jpg" alt="Sonore UNDR">
                </div>
            </div>

            <!-- Section Visuelle -->
            <div class="expertise-section reveal" style="direction: rtl;">
                <div class="expertise-content" style="direction: ltr;">
                    <h2>Signature <span class="accent">Visuelle.</span></h2>
                    <p>Le rouge **(#cd1a18)** est notre ADN. Il ne s'agit pas d'une simple couleur, mais d'une structure qui définit l'espace et l'ambiance.</p>
                    <p>Notre scénographie repose sur des éléments forts et identifiables qui transforment radicalement le lieu :</p>
                    <div class="feature-list">
                        <div class="feature-item"><span>Rideaux Rouges Scéniques</span></div>
                        <div class="feature-item"><span>Néon UNDR Signature</span></div>
                        <div class="feature-item"><span>Toiles Artistiques Custom</span></div>
                    </div>
                </div>
                <div class="visual-card tilt-3d">
                    <img src="assets/img/undr_phase_iv_12_mars.jpg" alt="Visuel UNDR">
                </div>
            </div>

            <!-- Section Réseau -->
            <div class="network-section reveal tilt-3d">
                <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 1rem;">Réseau d'intervention</h2>
                <p style="color: var(--muted); margin-bottom: 2rem;">Nous déployons nos concepts dans les lieux les plus exigeants.</p>
                <div class="network-grid">
                    <div class="network-item">Toulouse</div>
                    <div class="network-item">Bordeaux</div>
                    <div class="network-item">Cap Ferret</div>
                    <div class="network-item">Leucate</div>
                    <div class="network-item">Biarritz</div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const dot = document.querySelector('.cursor-dot');
        const outline = document.querySelector('.cursor-outline');
        const transition = document.querySelector('.page-transition');

        // 1. Initial Load
        window.addEventListener('load', () => {
            if (transition) transition.style.opacity = '0';
            
            if (isTouchDevice) {
                if (dot) dot.style.display = 'none';
                if (outline) outline.style.display = 'none';
                document.body.style.cursor = 'auto';
            }

            // Scroll Reveal
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('active'); });
            }, { threshold: 0.1 });
            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
        });

        // 2. Custom Cursor & 3D Tilt
        window.addEventListener('mousemove', (e) => {
            if (!isTouchDevice) {
                dot.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`;
                outline.animate({
                    transform: `translate(${e.clientX}px, ${e.clientY}px)`
                }, { duration: 500, fill: "forwards" });

                // Extreme 3D Tilt Effect
                document.querySelectorAll('.tilt-3d').forEach(card => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    // Check if mouse is near or over the card
                    if (x > -100 && x < rect.width + 100 && y > -100 && y < rect.height + 100) {
                        const xc = rect.width / 2;
                        const yc = rect.height / 2;
                        const dx = x - xc;
                        const dy = y - yc;
                        
                        const tiltX = (dy / yc) * -10;
                        const tiltY = (dx / xc) * 10;
                        
                        card.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;

                        // Glare position
                        const px = (x / rect.width) * 100;
                        const py = (y / rect.height) * 100;
                        card.style.setProperty('--mouse-x', `${px}%`);
                        card.style.setProperty('--mouse-y', `${py}%`);
                    } else {
                        card.style.transform = `rotateX(0deg) rotateY(0deg)`;
                    }
                });
            }
        });

        // 3. Smooth Page Transitions
        document.querySelectorAll('a').forEach(el => {
            el.addEventListener('click', (e) => {
                const href = el.getAttribute('href');
                if (href && !href.startsWith('#') && !el.hasAttribute('target') && transition) {
                    e.preventDefault();
                    transition.style.opacity = '1';
                    setTimeout(() => { window.location.href = href; }, 600);
                }
            });
        });
    </script>
</body>
</html>
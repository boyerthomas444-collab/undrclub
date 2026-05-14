<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>UNDR CLUB - Concept</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
      :root {
        --bg-deep: #000000;
        --bg-sidebar: #050505;
        --bg-card: #0a0a0a;
        --border: rgba(255, 255, 255, 0.08);
        --text: #ffffff;
        --muted: rgba(255, 255, 255, 0.5);
        --accent: #cd1a18;
        --accent-glow: rgba(205, 26, 24, 0.2);
      }
      * { margin: 0; padding: 0; box-sizing: border-box; }
      a { text-decoration: none; color: inherit; }
      body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background-color: var(--bg-deep);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        overflow-x: hidden;
        cursor: none;
        line-height: 1.6;
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
      }
      .brand:hover .brand-logo { transform: rotate(360deg); }
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

      .concept-section {
        max-width: 1000px; margin: 0 auto 4rem auto;
        background: linear-gradient(145deg, #0f0f0f 0%, #050505 100%);
        border: 1px solid var(--border); border-radius: 40px; padding: 5rem;
        position: relative; overflow: visible;
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.5);
        opacity: 0; transform: translateY(30px);
        animation: fadeUp 1.2s cubic-bezier(0.2, 0.6, 0.2, 1) forwards;
        transform-style: preserve-3d;
        transition: transform 0.1s ease-out;
      }

      /* Glare effect overlay */
      .concept-section::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255,255,255,0.08) 0%, transparent 60%);
        border-radius: 40px; pointer-events: none; z-index: 10;
        opacity: 0; transition: opacity 0.4s ease;
      }
      .concept-section:hover::before { opacity: 1; }

      @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

      .section-number {
        position: absolute; top: 2rem; right: 3rem;
        font-size: 8rem; font-weight: 900; color: rgba(255,255,255,0.03);
        line-height: 1; transform: translateZ(20px);
      }

      .concept-title { 
        font-size: 4rem; font-weight: 900; letter-spacing: -2px; margin-bottom: 2rem;
        text-transform: uppercase; line-height: 1;
        transform: translateZ(100px);
        text-shadow: 0 20px 40px rgba(0,0,0,0.8);
      }
      .concept-content { 
        color: var(--text); font-size: 1.4rem; font-weight: 500;
        transform: translateZ(60px); line-height: 1.8;
      }
      .concept-accent {
        color: var(--accent); font-weight: 900;
      }

      .highlight-box {
        margin-top: 3rem; padding: 2rem;
        background: rgba(255,255,255,0.02); border-radius: 24px;
        border: 1px solid var(--border);
        transform: translateZ(40px);
        transition: all 0.4s ease;
      }
      .highlight-box:hover {
        background: rgba(255,255,255,0.04);
        transform: translateZ(80px) scale(1.02);
        border-color: var(--accent);
      }

      /* Cities Grid */
      .cities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.5rem;
        margin-top: 3rem;
        transform: translateZ(50px);
      }
      .city-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
        transform-style: preserve-3d;
      }
      .city-card:hover {
        background: rgba(205, 26, 24, 0.1);
        border-color: var(--accent);
        transform: translateZ(30px) scale(1.05);
      }
      .city-name {
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
        transform: translateZ(20px);
      }

      /* Responsive */
      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); }
        .main { margin-left: 0; padding: 2rem 1rem; }
        .concept-section { padding: 3rem 1.5rem; }
        .concept-title { font-size: 2.5rem; }
        .concept-content { font-size: 1.1rem; }
        .section-number { font-size: 4rem; top: 1rem; right: 1.5rem; }
      }
    </style>
  </head>
  <body>
    <div class="bg-overlay"></div>
    <div class="noise"></div>
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
        <a href="concept.php" class="nav-link active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
          Concept
        </a>
        <a href="expertise.php" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
          Expertise
        </a>
        <a href="contact.php" class="nav-link">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
           Contact
         </a>
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
      <!-- Section Mission -->
      <section class="concept-section tilt-card">
        <div class="section-number">01</div>
        <h1 class="concept-title">Notre <span class="concept-accent">Mission.</span></h1>
        <div class="concept-content">
          <p style="margin-bottom: 2rem;">Accélérez votre <span class="concept-accent">immersion</span>, grâce à la data musicale et visuelle.</p>
          <p>Nous accompagnons les clubs et lieux événementiels pour définir leurs formats artistiques, structurer une progression musicale claire et déployer des scénographies performantes réellement adaptées.</p>
          
          <div class="highlight-box">
            <p>Notre approche : rendre l'événement <span class="concept-accent">immersif, responsable et concret</span>, tout en aidant les lieux à se différencier et à fidéliser leur clientèle.</p>
          </div>
        </div>
      </section>

      <!-- Section Progression Sonore -->
      <section class="concept-section tilt-card">
        <div class="section-number">02</div>
        <h1 class="concept-title">Progression <span class="concept-accent">Sonore.</span></h1>
        <div class="concept-content">
          <p>Un voyage maîtrisé du <span class="concept-accent">minimal au hard groove</span>, porté par des formats B2B exclusifs entre nos résidents et artistes invités.</p>
          
          <div class="highlight-box" style="margin-top: 4rem;">
            <p style="font-size: 1rem; color: var(--muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1rem;">UNDR Concept</p>
            <p>Chaque événement est une narration. Nous créons l'équilibre parfait entre l'énergie brute et la précision technique.</p>
          </div>
        </div>
      </section>

      <!-- Section Territoires -->
      <section class="concept-section tilt-card">
        <div class="section-number">03</div>
        <h1 class="concept-title">Rayonnement <span class="concept-accent">Territorial.</span></h1>
        <div class="concept-content">
          <p>Notre expertise s'exporte et s'adapte aux lieux les plus emblématiques de la scène festive française.</p>
          
          <div class="cities-grid">
            <div class="city-card"><div class="city-name">Toulouse</div></div>
            <div class="city-card"><div class="city-name">Bordeaux</div></div>
            <div class="city-card"><div class="city-name">Cap Ferret</div></div>
            <div class="city-card"><div class="city-name">Leucate</div></div>
            <div class="city-card"><div class="city-name">Biarritz</div></div>
          </div>
        </div>
      </section>
    </main>

    <script>
      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      const dot = document.querySelector('.cursor-dot');
      const outline = document.querySelector('.cursor-outline');

      // Cursor movement
      window.addEventListener('mousemove', (e) => {
        if (!isTouchDevice) {
          dot.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`;
          outline.animate({
            transform: `translate(${e.clientX}px, ${e.clientY}px)`
          }, { duration: 500, fill: "forwards" });

          // 3D Tilt Effect for all sections
          const cards = document.querySelectorAll('.tilt-card');
          cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            
            // Check if mouse is near or over the card
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
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
    </script>
  </body>
</html>
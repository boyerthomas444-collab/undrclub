<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// --- PROXY DE RÉSERVATION POUR CONTOURNER CORS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: $_POST;

    $formspree_url = 'https://formspree.io/f/xbdqonea';

    $ch = curl_init($formspree_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code($httpCode ?: 500);
        if ($error) {
            echo json_encode(['error' => 'Erreur CURL : ' . $error]);
        } else {
            echo $response ?: json_encode(['error' => 'Erreur de transmission (Code ' . $httpCode . ')']);
        }
    }
    exit;
}

$user = current_user();
$canCreateEvent = is_admin();

?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>UNDR CLUB - Réservations</title>
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
        --success: #00ff66;
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

      .logout-btn {
        margin-top: auto; padding: 1rem; text-align: center; border-radius: 16px;
        background: rgba(205, 26, 24, 0.1); color: var(--accent); font-weight: 700; transition: all 0.3s ease;
      }
      .logout-btn:hover { background: var(--accent); color: #fff; }

      /* Main Content */
      .main { flex: 1; margin-left: 280px; padding: 4rem 2rem; position: relative; perspective: 2000px; }

      /* Reservation Container */
      .res-container {
        max-width: 1000px; margin: 0 auto;
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
      .res-container::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255,255,255,0.08) 0%, transparent 60%);
        border-radius: 40px; pointer-events: none; z-index: 10;
        opacity: 0; transition: opacity 0.4s ease;
      }
      .res-container:hover::before { opacity: 1; }

      @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

      .res-header { 
        margin-bottom: 4rem; 
        text-align: center; 
        transform-style: preserve-3d;
      }
      .res-title { 
        font-size: 4rem; font-weight: 900; letter-spacing: -2px; margin-bottom: 1rem;
        text-transform: uppercase; line-height: 1;
        transform: translateZ(100px);
        text-shadow: 0 20px 40px rgba(0,0,0,0.8);
      }
      .res-subtitle { 
        color: var(--muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto;
        transform: translateZ(60px);
      }

      /* Form Design */
      #reservation-form { transform-style: preserve-3d; }
      .form-grid { 
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 2.5rem; margin-bottom: 3rem; 
        transform: translateZ(40px);
      }
      .field-group { display: flex; flex-direction: column; gap: 0.8rem; position: relative; }
      .field-group.full { grid-column: span 2; }
      
      label { 
        font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); padding-left: 5px;
        transform: translateZ(10px);
      }
      
      input, select, textarea {
        background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border);
        border-radius: 18px; padding: 1.2rem 1.5rem; color: #fff; font-family: inherit; font-size: 1rem;
        transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
        transform: translateZ(20px);
      }
      input:focus, select:focus, textarea:focus {
        background: rgba(255, 255, 255, 0.05); border-color: var(--accent); outline: none;
        box-shadow: 0 15px 35px rgba(205, 26, 24, 0.2); transform: translateZ(35px) translateX(5px);
      }

      .btn-submit {
        background: var(--accent); color: #fff; border: none; padding: 1.5rem 3rem;
        border-radius: 20px; font-weight: 900; font-size: 1rem; text-transform: uppercase;
        letter-spacing: 3px; cursor: pointer; transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
        width: 100%; position: relative; overflow: hidden;
        transform: translateZ(50px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
      }
      .btn-submit:hover { 
        transform: translateZ(70px) scale(1.02); 
        box-shadow: 0 25px 50px rgba(205, 26, 24, 0.4); 
      }

      /* Success Message */
      #success-message {
        display: none; padding: 4rem; text-align: center;
        background: rgba(0, 255, 102, 0.03); border: 1px solid rgba(0, 255, 102, 0.2);
        border-radius: 30px; margin-bottom: 2rem;
        transform: translateZ(80px);
      }

      /* Info Grid */
      .res-info {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 5rem;
        border-top: 1px solid var(--border); padding-top: 4rem;
        transform-style: preserve-3d;
      }
      .info-item {
        padding: 2rem; background: rgba(255,255,255,0.02); border-radius: 24px;
        transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1); border: 1px solid transparent; text-align: center;
        transform: translateZ(20px);
      }
      .info-item:hover { 
        background: rgba(255,255,255,0.04); border-color: var(--border); 
        transform: translateZ(50px) translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
      }
      .info-item h4 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); margin-bottom: 0.5rem; }
      .info-item p { font-size: 1.1rem; font-weight: 600; }

      /* Responsive */
      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); }
        .main { margin-left: 0; padding: 2rem 1rem; }
        .res-container { padding: 3rem 1.5rem; }
        .res-title { font-size: 2.5rem; }
        .form-grid { grid-template-columns: 1fr; gap: 1.5rem; }
        .field-group.full { grid-column: span 1; }
        .res-info { grid-template-columns: 1fr; }
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
        <a href="reservations.php" class="nav-link active">
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

      <a href="logout.php" class="logout-btn">Déconnexion</a>
    </aside>

    <main class="main">
      <div class="res-container">
        <div class="res-header">
          <h1 class="res-title">Réserver une <span style="color: var(--accent);">table.</span></h1>
          <p class="res-subtitle">Vivez l'expérience UNDR CLUB en immersion totale avec notre service VIP et réservations de tables.</p>
        </div>

        <div id="success-message">
          <h2 style="color: var(--success); margin-bottom: 1rem; font-size: 2.5rem; font-weight: 900;">DEMANDE REÇUE !</h2>
          <p style="font-size: 1.2rem; color: #fff;">Votre demande de réservation a été transmise avec succès.</p>
          <p style="margin-top: 1rem; color: var(--muted);">Nous reviendrons vers vous très rapidement pour confirmer votre table.</p>
          <a href="reservations.php" class="btn-submit" style="display: inline-block; width: auto; padding: 1rem 2rem; margin-top: 2rem; text-decoration: none;">Nouvelle réservation</a>
        </div>

        <form id="reservation-form" action="https://formspree.io/f/xbdqonea" method="POST">
          <input type="hidden" name="_subject" value="Nouvelle réservation UNDR TLS">
          <input type="text" name="_gotcha" style="display:none" tabindex="-1" autocomplete="off">

          <div class="form-grid">
            <div class="field-group">
              <label>Nom complet</label>
              <input type="text" name="name" required placeholder="Votre nom">
            </div>
            <div class="field-group">
              <label>Email</label>
              <input type="email" name="email" required placeholder="contact@exemple.com">
            </div>
            <div class="field-group">
              <label>Téléphone</label>
              <input type="tel" name="phone" required placeholder="06 .. .. .. ..">
            </div>
            <div class="field-group">
              <label>Événement</label>
              <select name="event">
                <option value="" disabled selected>Sélectionnez un événement</option>
                <option value="custom">Date spécifique (préciser ci-dessous)</option>
                <?php 
                  if (function_exists('get_events')) {
                    $events = get_events();
                    $today = date('Y-m-d');
                    foreach ($events as $e) {
                      if ($e['event_date'] >= $today) {
                        echo "<option value='".htmlspecialchars($e['title'])." (".$e['event_date'].")'>".htmlspecialchars($e['title'])." — ".$e['event_date']."</option>";
                      }
                    }
                  }
                ?>
              </select>
            </div>
            <div class="field-group">
              <label>Date souhaitée</label>
              <input type="date" name="date" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="field-group">
              <label>Nombre de personnes</label>
              <input type="number" name="guests" min="1" max="30" required placeholder="Ex: 5">
            </div>
            <div class="field-group full">
              <label>Demandes particulières</label>
              <textarea name="message" rows="3" placeholder="Une demande particulière ? (Emplacement, bouteilles, anniversaire...)"></textarea>
            </div>
          </div>
          <button type="submit" class="btn-submit">Envoyer la demande</button>
        </form>

        <div class="res-info">
          <div class="info-item">
            <h4>Contact</h4>
            <p><a href="mailto:Contact.undr.tls@gmail.com">Contact.undr.tls@gmail.com</a></p>
          </div>
          <div class="info-item">
            <h4>Booking & Management</h4>
            <p><a href="mailto:booking@undrclub.com">booking@undrclub.com</a></p>
          </div>
          <div class="info-item">
            <h4>Lieu</h4>
            <p>Toulouse / Bordeaux</p>
          </div>
        </div>
      </div>
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

          // 3D Tilt Effect
          const container = document.querySelector('.res-container');
          if (container) {
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const xc = rect.width / 2;
            const yc = rect.height / 2;
            
            const dx = x - xc;
            const dy = y - yc;
            
            const tiltX = (dy / yc) * -10; // Inclinaison max augmentée à 10 deg
            const tiltY = (dx / xc) * 10;
            
            container.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;

            // Glare position
            const px = (x / rect.width) * 100;
            const py = (y / rect.height) * 100;
            container.style.setProperty('--mouse-x', `${px}%`);
            container.style.setProperty('--mouse-y', `${py}%`);
          }
        }
      });

      // Reset Tilt on leave
      document.querySelector('.main').addEventListener('mouseleave', () => {
        const container = document.querySelector('.res-container');
        if (container) {
          container.style.transform = `rotateX(0deg) rotateY(0deg)`;
        }
      });

      // Form Handling
      const form = document.getElementById('reservation-form');
      const successMessage = document.getElementById('success-message');

      if (form) {
        form.addEventListener('submit', async function(e) {
          e.preventDefault();
          const submitBtn = form.querySelector('.btn-submit');
          const originalText = submitBtn.textContent;
          
          submitBtn.textContent = 'TRANSMISSION...';
          submitBtn.disabled = true;

          const formData = new FormData(form);
          const data = Object.fromEntries(formData.entries());

          try {
            const response = await fetch('?ajax=1', {
              method: 'POST',
              body: JSON.stringify(data),
              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
            });

            if (response.ok) {
              form.style.transition = 'all 0.6s ease';
              form.style.opacity = '0';
              form.style.transform = 'translateY(-20px)';
              setTimeout(() => {
                form.style.display = 'none';
                successMessage.style.display = 'block';
                successMessage.scrollIntoView({ behavior: 'smooth' });
              }, 600);
            } else {
              form.submit(); // Fallback standard
            }
          } catch (error) {
            form.submit(); // Fallback standard
          }
        });
      }
    </script>
  </body>
</html>
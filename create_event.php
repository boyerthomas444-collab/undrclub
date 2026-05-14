<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$user = require_auth();
$events = get_events();
$canCreateEvent = is_admin();

?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>UNDR CLUB</title>
    <style>
      :root {
        --bg-deep: #000000;
        --bg-sidebar: #050505;
        --bg-card: #0a0a0a;
        --border: rgba(255, 255, 255, 0.1);
        --text: #ffffff;
        --muted: rgba(255, 255, 255, 0.6);
        --accent: #cd1a18;
        --accent-glow: rgba(205, 26, 24, 0.3);
        --accent-glow-strong: rgba(205, 26, 24, 0.6);
      }
      * { margin: 0; padding: 0; box-sizing: border-box; }
      a { text-decoration: none; color: inherit; }
      body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background-color: var(--bg-deep);
        color: var(--text);
        height: 100vh;
        display: flex;
        overflow: hidden;
        cursor: none;
      }

      /* Noise & Background */
      .bg-overlay {
        position: fixed;
        inset: 0;
        z-index: -1;
        background: 
          radial-gradient(circle at 0% 0%, rgba(205, 26, 24, 0.08) 0%, transparent 50%),
          radial-gradient(circle at 100% 100%, rgba(205, 26, 24, 0.05) 0%, transparent 50%);
        animation: pulse-bg 10s ease-in-out infinite alternate;
      }
      @keyframes pulse-bg { 0% { opacity: 0.5; } 100% { opacity: 1; } }

      .noise {
        position: fixed;
        inset: 0;
        z-index: 9998;
        pointer-events: none;
        opacity: 0.03;
        background-image: url('https://grainy-gradients.vercel.app/noise.svg');
      }

      /* Custom Cursor */
      .cursor-dot, .cursor-outline {
        pointer-events: none;
        position: fixed;
        top: 0; left: 0;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        z-index: 9999;
        transition: transform 0.1s ease-out, opacity 0.3s ease-in-out;
      }
      .cursor-dot { width: 8px; height: 8px; background-color: var(--accent); box-shadow: 0 0 10px var(--accent); }
      .cursor-outline { width: 40px; height: 40px; border: 1px solid var(--accent); opacity: 0.5; }

      /* Sidebar */
      .sidebar {
        width: 280px;
        background-color: var(--bg-sidebar);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        padding: 2.5rem 1.5rem;
        z-index: 20;
        backdrop-filter: blur(20px);
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: 20px;
        transition: all 0.5s cubic-bezier(0.2, 0.6, 0.2, 1);
        margin-bottom: 2.5rem;
        transform-style: preserve-3d;
        perspective: 1000px;
        text-decoration: none;
        color: #fff;
      }
      .brand:hover {
        border-color: var(--accent);
        background: rgba(205, 26, 24, 0.05);
        transform: translateY(-2px) rotateX(var(--brx, 0deg)) rotateY(var(--bry, 0deg));
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      }
      .brand img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 1px solid var(--accent);
        transition: all 0.5s;
        transform: translateZ(20px);
      }
      .brand img.playing { animation: spin 3s linear infinite; box-shadow: 0 0 20px var(--accent-glow-strong); }
      @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      .brand-name { 
        font-size: 1.1rem; 
        font-weight: 950; 
        text-transform: uppercase; 
        letter-spacing: 1px;
        transform: translateZ(10px);
      }

      .nav-link {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1rem 1.5rem;
        border-radius: 16px;
        color: var(--muted);
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 0.5rem;
        position: relative;
        border: 1px solid transparent;
      }
      .nav-link::before {
        content: '';
        position: absolute;
        left: 0;
        width: 0;
        height: 40%;
        background: var(--accent);
        border-radius: 0 4px 4px 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
      }
      .nav-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.05);
        padding-left: 1.75rem;
      }
      .nav-link:hover::before {
        width: 4px;
        opacity: 0.5;
      }
      .nav-link.active {
        color: #fff;
        background: linear-gradient(90deg, rgba(205, 26, 24, 0.15) 0%, rgba(205, 26, 24, 0.02) 100%);
        border: 1px solid rgba(205, 26, 24, 0.2);
        padding-left: 1.75rem;
        text-shadow: 0 0 15px var(--accent-glow-strong);
      }
      .nav-link.active::before {
        width: 4px;
        height: 60%;
        opacity: 1;
        box-shadow: 0 0 15px var(--accent);
      }
      .nav-link svg { 
        width: 20px; 
        height: 20px; 
        opacity: 0.5; 
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
      }
      .nav-link:hover svg, .nav-link.active svg { 
        opacity: 1; 
        transform: scale(1.1); 
        color: var(--accent);
        filter: drop-shadow(0 0 8px var(--accent-glow));
      }

      .logout-btn {
        margin-top: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.9rem 1.2rem;
        border-radius: 14px;
        color: #fff;
        background: rgba(255, 255, 255, 0.03);
        text-decoration: none;
        font-weight: 800;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border: 1px solid var(--accent);
        transition: all 0.3s;
        justify-content: center;
      }
      .logout-btn:hover {
        background: var(--accent);
        box-shadow: 0 0 20px var(--accent-glow);
        transform: translateY(-2px);
      }
      .logout-btn svg {
        width: 18px;
        height: 18px;
      }

      /* Main Content */
      .main {
        flex: 1;
        padding: 4rem;
        overflow-y: auto;
        scroll-behavior: smooth;
      }
      
      .welcome-card {
        background: linear-gradient(135deg, #050505 0%, #000 100%);
        border: 1px solid var(--border);
        border-radius: 32px;
        padding: 5rem 2rem;
        margin-bottom: 4rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 40px 100px rgba(0,0,0,0.8);
      }
      .welcome-logo {
        width: 150px;
        height: 150px;
        border-radius: 24px;
        border: 1px solid var(--accent);
        box-shadow: 0 0 40px var(--accent-glow);
        animation: float 6s ease-in-out infinite;
      }
      @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
      .welcome-text {
        font-size: 2.2rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 12px;
        color: #fff;
        text-shadow: 0 0 30px var(--accent-glow-strong);
      }

      /* Form Container */
      .form-container {
        max-width: 900px;
        margin: 0 auto;
        background: #050505;
        border: 1px solid var(--border);
        border-radius: 32px;
        padding: 4rem;
        box-shadow: 0 30px 60px rgba(0,0,0,0.6);
      }
      .form-container h1 {
        font-size: 2.5rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: -1px;
        margin-bottom: 3rem;
        text-align: center;
        background: linear-gradient(to bottom, #fff, #666);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
      }
      
      .field-group { margin-bottom: 2rem; }
      .field-group label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--accent);
        margin-bottom: 0.8rem;
      }
      .field-group input {
        width: 100%;
        padding: 1.2rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border);
        border-radius: 14px;
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s;
      }
      .field-group input:focus {
        border-color: var(--accent);
        background: rgba(205, 26, 24, 0.05);
        outline: none;
        box-shadow: 0 0 20px var(--accent-glow);
      }
      
      .drop-zone {
        border: 2px dashed var(--border);
        border-radius: 20px;
        padding: 4rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: rgba(255,255,255,0.01);
      }
      .drop-zone:hover { border-color: var(--accent); background: rgba(205, 26, 24, 0.03); }
      
      .btn-submit {
        background: #fff;
        color: #000;
        padding: 1.2rem;
        border-radius: 16px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        width: 100%;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        margin-top: 2rem;
      }
      .btn-submit:hover {
        background: var(--accent);
        color: #fff;
        transform: translateY(-5px);
        box-shadow: 0 20px 40px var(--accent-glow-strong);
      }

      /* Past Events Grid */
      .events-title {
        font-size: 2rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: -1px;
        margin-top: 6rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
      }
      .events-title::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, var(--border), transparent); }

      .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2.5rem;
      }
      .event-card {
        background: #050505;
        border-radius: 28px;
        border: 1px solid var(--border);
        aspect-ratio: 4/5;
        position: relative;
        overflow: hidden;
        transition: all 0.5s;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 2.5rem;
        filter: grayscale(0.5);
        opacity: 0.7;
      }
      .event-card:hover { transform: translateY(-10px); opacity: 1; filter: grayscale(0); border-color: var(--accent); }
      .event-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, #000 10%, transparent 80%);
        z-index: 1;
      }
      .event-content { position: relative; z-index: 2; }
      .event-city { font-size: 0.7rem; font-weight: 900; color: var(--accent); text-transform: uppercase; letter-spacing: 3px; margin-bottom: 0.8rem; }
      .event-name { font-size: 1.8rem; font-weight: 950; line-height: 1; margin-bottom: 1rem; text-transform: uppercase; }
      
      .event-info-row {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: 0.4rem;
      }
      .event-info-row svg { width: 14px; height: 14px; color: var(--accent); }

      /* Media Preview */
      .media-preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
      }
      .media-preview-item {
        aspect-ratio: 1;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border);
        position: relative;
      }
      .media-preview-remove {
        position: absolute;
        top: 8px; right: 8px;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 24px; height: 24px;
        cursor: pointer;
        font-weight: 900;
      }

      /* Progress Bar */
      #uploadProgressContainer { margin-top: 2rem; display: none; }
      .progress-bar-bg { width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden; }
      .progress-bar-fill { width: 0%; height: 100%; background: var(--accent); box-shadow: 0 0 15px var(--accent); transition: width 0.3s; }
    </style>
  </head>
  <body>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>
    <div class="noise"></div>
    <div class="bg-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
      <a href="javascript:void(0)" class="brand" id="vinyl-player">
        <img src="assets/img/Logo Vinyle  (1).png" alt="UNDR Logo" id="vinyl-img">
        <span class="brand-name">UNDR CLUB</span>
      </a>
      <audio id="audio-excerpt" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3"></audio>
      <nav class="nav">
        <a href="<?= url('dashboard.php') ?>" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          Accueil
        </a>
        <?php if ($canCreateEvent): ?>
        <a href="<?= url('create_event.php') ?>" class="nav-link active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          Créer un événement
        </a>
        <?php endif; ?>
        <a href="<?= url('reservations.php') ?>" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          Réservation
        </a>
        <div class="nav-link search-trigger" style="cursor: pointer;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          Recherche
        </div>
        <a href="https://shotgun.live/fr" target="_blank" rel="noopener noreferrer" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a3 3 0 0 0-3-3H5a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V8Z"></path><path d="M10 12h.01"></path><path d="M16 12h.01"></path><path d="M22 8v8"></path></svg>
          Billetterie
        </a>
        <div style="margin-top: 2rem; padding: 0 1.2rem; font-size: 0.7rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 2px;">Réseaux</div>
        <a href="https://www.instagram.com/undr.clubb?igsh=MXk5cTBycWtreTNy" target="_blank" rel="noopener noreferrer" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
          Instagram
        </a>
        <a href="https://www.tiktok.com/@undr.club?_r=1&_t=ZN-95VJh9T8n6k" target="_blank" rel="noopener noreferrer" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path></svg>
          TikTok
        </a>
        <a href="contact.php" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          Contact
        </a>
        <a href="mailto:booking@undrclub.com" class="nav-link" style="font-size: 0.75rem;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          Booking
        </a>
      </nav>
      
      <div class="sidebar-cities" style="margin-top: 2rem; padding: 1.5rem; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 20px; display: flex; flex-direction: column; gap: 0.8rem;">
        <h4 style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; color: var(--accent); margin-bottom: 0.5rem; font-weight: 900;">Territoires</h4>
        <div class="city-item" style="font-size: 0.85rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">Toulouse</div>
        <div class="city-item" style="font-size: 0.85rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">Bordeaux</div>
        <div class="city-item" style="font-size: 0.85rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">Cap Ferret</div>
        <div class="city-item" style="font-size: 0.85rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">Leucate</div>
        <div class="city-item" style="font-size: 0.85rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">Biarritz</div>
      </div>
      
      <a href="<?= url('logout.php') ?>" class="logout-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Déconnexion
      </a>
    </aside>

    <main class="main">
      <!-- Top Search Bar -->
      <div style="margin-bottom: 2rem; position: relative; max-width: 400px;">
        <input type="text" id="eventSearch" placeholder="Rechercher un événement..." 
               style="width: 100%; padding: 0.8rem 1rem 0.8rem 3rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 12px; color: #fff; font-size: 0.9rem; outline: none; transition: all 0.3s;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
             style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.2rem; height: 1.2rem; color: var(--muted); pointer-events: none;">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </div>

      <div class="welcome-card">
        <div class="welcome-scanline"></div>
        <div class="welcome-logo">
          <img src="assets/img/Logo Vinyle  (1).png" alt="UNDR Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="welcome-text">ESPACE MEMBRE</div>
      </div>

      <div class="form-container">
        <h1>Créer un Nouvel Événement</h1>
        <form id="createEventForm">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
          <div id="formMessage" style="display: none; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"></div>
          <div class="field-group">
            <label>Titre de l'événement</label>
            <input type="text" name="title" required placeholder="Ex: UNDR THE ICE">
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="field-group">
              <label>Ville</label>
              <input type="text" name="city" required placeholder="Ex: Toulouse">
            </div>
            <div class="field-group">
              <label>Lieu / Club</label>
              <input type="text" name="club" required placeholder="Ex: @ICE CLUB">
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="field-group">
              <label>Date</label>
              <input type="date" name="event_date" required>
            </div>
            <div class="field-group">
              <label>Horaires</label>
              <input type="text" name="time" required placeholder="Ex: 00H00 / 6H00">
            </div>
          </div>
          <div class="field-group">
            <label>Lineup (Optionnel)</label>
            <input type="text" name="lineup" placeholder="Ex: JOLY, LIZY x SOLA">
          </div>
          <div class="field-group">
            <label>Lien Billetterie Shotgun (URL)</label>
            <input type="url" name="url" placeholder="https://shotgun.live/fr/events/...">
          </div>
          
          <div class="field-group">
            <label>Couleur d'accent (Optionnel)</label>
            <input type="color" name="color" value="#cd1a18" style="height: 50px; padding: 5px; cursor: pointer;">
          </div>
          
          <div class="field-group">
            <label>Images & Vidéos (Glisser-déposer ou cliquer)</label>
            <div id="dropZone" class="drop-zone">
              <input type="file" id="mediaInput" multiple accept="image/*,video/*" style="display: none;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; margin-bottom: 1rem; color: var(--accent);">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
              <p>Glissez vos fichiers ici ou <span style="color: var(--accent);">cliquez pour parcourir</span></p>
              <p style="font-size: 0.75rem; color: var(--muted); margin-top: 0.5rem;">Images (JPG, PNG, WEBP) • Vidéos (MP4) • Max 50MB</p>
            </div>
            <div id="mediaPreviewContainer" class="media-preview-container"></div>
          </div>

          <div id="uploadProgressContainer">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.8rem;">
              <span id="progressText">Publication en cours...</span>
              <span id="progressPercent">0%</span>
            </div>
            <div class="progress-bar-bg">
              <div id="progressBarFill" class="progress-bar-fill"></div>
            </div>
          </div>

          <button type="submit" class="btn-submit">Publier l'événement</button>
        </form>
      </div>

      <!-- Past Events Section -->
      <h2 class="events-title">Archives passées</h2>
      <div class="events-grid">
        <?php foreach ($events as $event): ?>
          <?php if (($event['status'] ?? '') === 'PASSÉ'): ?>
            <div class="event-card">
              <div class="event-overlay"></div>
              <div class="event-content">
                <div class="event-city"><?= h($event['city']) ?></div>
                <h3 class="event-name"><?= h($event['title']) ?></h3>
                
                <div class="event-info-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                  <span><?= h(format_event_date($event['event_date'])) ?></span>
                </div>
                
                <div class="event-info-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                  <span><?= h($event['club']) ?> • <?= h($event['time']) ?></span>
                </div>

                <?php if (!empty($event['lineup'])): ?>
                <div class="event-info-row" style="color: var(--accent);">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                  <span style="font-weight: 800;"><?= h($event['lineup']) ?></span>
                </div>
                <?php endif; ?>

                <div style="margin-top: 1.5rem; font-size: 0.65rem; font-weight: 900; letter-spacing: 2px; color: var(--muted); border: 1px solid var(--border); padding: 5px 12px; border-radius: 99px; width: fit-content; text-transform: uppercase;">
                  PASSÉ
                </div>

                <?php if ($canCreateEvent): ?>
                <button class="delete-event-btn" data-id="<?= $event['id'] ?>" style="margin-top: 1rem; background: rgba(255,0,0,0.1); border: 1px solid red; color: red; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                  Supprimer le poste
                </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </main>

    <script>
      // Custom Cursor
      const dot = document.querySelector('.cursor-dot');
      const outline = document.querySelector('.cursor-outline');

      // Detect touch device
      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      if (isTouchDevice) {
        if (dot) dot.style.display = 'none';
        if (outline) outline.style.display = 'none';
        document.body.style.cursor = 'auto';
      }

      if (!isTouchDevice) {
        window.addEventListener('mousemove', (e) => {
          dot.style.left = e.clientX + 'px';
          dot.style.top = e.clientY + 'px';
          outline.animate({
            left: e.clientX + 'px',
            top: e.clientY + 'px'
          }, { duration: 500, fill: 'forwards' });
        });
      }

      // Vinyl Player Logic
      const player = document.getElementById('vinyl-player');
      const img = document.getElementById('vinyl-img');
      const audio = document.getElementById('audio-excerpt');
      let isPlaying = false;

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

      // Media Upload Logic
      const dropZone = document.getElementById('dropZone');
      const mediaInput = document.getElementById('mediaInput');
      const previewContainer = document.getElementById('mediaPreviewContainer');
      let uploadedFiles = [];

      dropZone.addEventListener('click', () => mediaInput.click());

      dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
      });

      dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
      });

      dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
      });

      mediaInput.addEventListener('change', () => {
        handleFiles(mediaInput.files);
      });

      function handleFiles(files) {
        Array.from(files).forEach(file => {
          uploadedFiles.push(file);
          const reader = new FileReader();
          const item = document.createElement('div');
          item.className = 'media-preview-item';
          
          if (file.type.startsWith('image/')) {
            reader.onload = (e) => {
              item.innerHTML = `<img src="${e.target.result}"><button class="media-preview-remove">&times;</button>`;
            };
          } else if (file.type.startsWith('video/')) {
            item.innerHTML = `<video src="${URL.createObjectURL(file)}" muted></video><button class="media-preview-remove">&times;</button>`;
          }
          
          item.querySelector('.media-preview-remove').addEventListener('click', (e) => {
            e.stopPropagation();
            const index = uploadedFiles.indexOf(file);
            if (index > -1) uploadedFiles.splice(index, 1);
            item.remove();
          });
          
          previewContainer.appendChild(item);
          if (file.type.startsWith('image/')) reader.readAsDataURL(file);
        });
      }

      // Form Submission
      // 3D Tilt for Brand Logo
      const brand = document.querySelector('.brand');
      if (brand && !isTouchDevice) {
        brand.addEventListener('mousemove', (e) => {
          const rect = brand.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;
          const rotateX = (centerY - y) / 5;
          const rotateY = (x - centerX) / 5;
          brand.style.setProperty('--brx', `${rotateX}deg`);
          brand.style.setProperty('--bry', `${rotateY}deg`);
        });
        brand.addEventListener('mouseleave', () => {
          brand.style.setProperty('--brx', '0deg');
          brand.style.setProperty('--bry', '0deg');
        });
      }

      const form = document.getElementById('createEventForm');
      const formMessage = document.getElementById('formMessage');
      const progressContainer = document.getElementById('uploadProgressContainer');
      const progressBarFill = document.getElementById('progressBarFill');
      const progressPercent = document.getElementById('progressPercent');

      function showMessage(msg, isError = true) {
        formMessage.innerText = msg;
        formMessage.style.display = 'block';
        formMessage.style.backgroundColor = isError ? 'rgba(205, 26, 24, 0.1)' : 'rgba(0, 255, 0, 0.1)';
        formMessage.style.color = isError ? '#ff4d4d' : '#00ff00';
        formMessage.style.borderLeft = `4px solid ${isError ? '#cd1a18' : '#00ff00'}`;
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formMessage.style.display = 'none';
        
        const formData = new FormData(form);
        uploadedFiles.forEach((file, index) => {
          formData.append(`media_${index}`, file);
        });
        formData.append('media_count', uploadedFiles.length);

        progressContainer.style.display = 'block';
        const submitBtn = form.querySelector('.btn-submit');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?= url('add_event.php') ?>', true);

        xhr.upload.onprogress = (e) => {
          if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBarFill.style.width = percent + '%';
            progressPercent.innerText = percent + '%';
          }
        };

        xhr.onload = function() {
          submitBtn.disabled = false;
          submitBtn.style.opacity = '1';
          progressContainer.style.display = 'none';

          try {
            const res = JSON.parse(xhr.responseText);
            if (xhr.status === 200) {
              if (res.success) {
                showMessage('Événement créé avec succès ! Redirection...', false);
                setTimeout(() => {
                  window.location.href = '<?= url('dashboard.php') ?>';
                }, 1500);
              } else {
                showMessage('Erreur : ' + (res.error || 'Inconnue'));
              }
            } else {
              showMessage('Erreur (' + xhr.status + ') : ' + (res.error || 'Une erreur est survenue sur le serveur.'));
            }
          } catch (e) {
            console.error(e);
            showMessage('Erreur lors de la réponse du serveur (' + xhr.status + ').');
          }
        };

        xhr.onerror = function() {
          submitBtn.disabled = false;
          submitBtn.style.opacity = '1';
          progressContainer.style.display = 'none';
          showMessage('Erreur réseau ou connexion impossible.');
        };

        xhr.send(formData);
      });

      // Search Logic
      const searchInput = document.getElementById('eventSearch');
      const searchTrigger = document.querySelector('.search-trigger');
      const eventCards = document.querySelectorAll('.event-card');

      searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        eventCards.forEach(card => {
          const text = card.textContent.toLowerCase();
          card.style.display = text.includes(term) ? 'flex' : 'none';
        });
      });

      searchTrigger.addEventListener('click', () => {
        searchInput.focus();
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        searchInput.style.borderColor = 'var(--accent)';
        setTimeout(() => {
          searchInput.style.borderColor = 'var(--border)';
        }, 1000);
      });

      // Delete Event Logic
      document.querySelectorAll('.delete-event-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
          e.preventDefault();
          e.stopPropagation();
          
          if (!confirm('Voulez-vous vraiment supprimer cet événement ?')) return;
          
          const id = btn.getAttribute('data-id');
          const originalText = btn.innerText;
          btn.innerText = 'Suppression...';
          btn.disabled = true;
          btn.style.opacity = '0.5';
          btn.style.cursor = 'not-allowed';

          const formData = new FormData();
          formData.append('id', id);
          formData.append('csrf_token', '<?= generate_csrf_token() ?>');
          
          try {
            const res = await fetch('delete_event.php', {
              method: 'POST',
              body: formData
            });
            
            let data;
            try {
              data = await res.json();
            } catch (e) {
              throw new Error('Réponse invalide du serveur');
            }

            if (data.success) {
              const card = btn.closest('.event-card');
              const grid = card.parentElement;
              card.style.opacity = '0';
              card.style.transform = 'scale(0.8)';
              setTimeout(() => {
                card.remove();
                // Cacher la section si elle est vide
                if (grid && grid.querySelectorAll('.event-card').length === 0) {
                  let header = grid.previousElementSibling;
                  while (header && !header.classList.contains('events-title')) {
                    header = header.previousElementSibling;
                  }
                  if (header) header.style.display = 'none';
                  grid.style.display = 'none';
                }
              }, 500);
            } else {
              showMessage('Erreur lors de la suppression : ' + (data.error || 'Inconnue'));
              btn.innerText = originalText;
              btn.disabled = false;
              btn.style.opacity = '1';
              btn.style.cursor = 'pointer';
            }
          } catch (err) {
            console.error(err);
            showMessage('Erreur lors de la suppression : ' + err.message);
            btn.innerText = originalText;
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
          }
        });
      });
    </script>
  </body>
</html>
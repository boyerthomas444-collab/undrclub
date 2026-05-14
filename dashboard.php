<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

ensure_tables();

$user = require_auth();
$events = get_events();
$canCreateEvent = is_admin();
$contactMessages = is_admin() ? get_contact_messages() : [];
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

      /* Noise Effect & Animated Gradients */
      .bg-overlay {
        position: fixed;
        inset: 0;
        z-index: -1;
        background: 
          radial-gradient(circle at 0% 0%, rgba(205, 26, 24, 0.08) 0%, transparent 50%),
          radial-gradient(circle at 100% 100%, rgba(205, 26, 24, 0.05) 0%, transparent 50%);
        animation: pulse-bg 10s ease-in-out infinite alternate;
      }
      @keyframes pulse-bg {
        0% { opacity: 0.5; }
        100% { opacity: 1; }
      }

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

      /* Sidebar Style */
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
      .welcome-card::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
        opacity: 0.3;
        pointer-events: none;
      }
      .welcome-logo {
        width: 180px;
        height: 180px;
        border-radius: 24px;
        border: 1px solid var(--accent);
        padding: 10px;
        background: #000;
        box-shadow: 0 0 50px var(--accent-glow);
        animation: float 6s ease-in-out infinite;
      }
      @keyframes float {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(2deg); }
      }
      .welcome-text {
        font-size: 2.5rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 15px;
        color: #fff;
        text-shadow: 0 0 30px var(--accent-glow-strong);
      }
      
      /* Event Cards */
      .events-title {
        font-size: 2rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: -1px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
      }
      .events-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, var(--border), transparent);
      }

      .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2.5rem;
      }
      .event-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 24px;
        overflow: hidden;
        aspect-ratio: 4/5;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 2.5rem;
        transition: all 0.5s cubic-bezier(0.2, 0.6, 0.2, 1);
        text-decoration: none;
        transform-style: preserve-3d;
        perspective: 1000px;
      }
      .event-card:hover {
        transform: perspective(1000px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)) scale3d(1.02, 1.02, 1.02);
        border-color: var(--accent);
        box-shadow: 0 30px 60px rgba(0,0,0,0.8), 0 0 30px var(--accent-glow);
      }
      .event-card .event-content {
        transform: translateZ(30px);
        transform-style: preserve-3d;
      }
      .event-card .event-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, #000 10%, transparent 80%);
        z-index: 1;
      }
      .event-card img, .event-card video {
        position: absolute;
        inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
      }
      .event-card:hover img, .event-card:hover video {
        transform: scale(1.1);
      }
      
      .event-content { position: relative; z-index: 2; }
      .event-city {
        font-size: 0.7rem;
        font-weight: 900;
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 3px;
        margin-bottom: 0.8rem;
      }
      .event-name {
        font-size: 2rem;
        font-weight: 950;
        line-height: 1;
        margin-bottom: 1.2rem;
        text-transform: uppercase;
      }
      .event-info-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: 0.5rem;
      }
      .event-info-row svg { width: 14px; height: 14px; color: var(--accent); }

      /* Custom Scrollbar */
      ::-webkit-scrollbar { width: 6px; }
      ::-webkit-scrollbar-track { background: transparent; }
      ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: var(--muted); }
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
        <a href="<?= url('index.php') ?>" class="nav-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          Accueil
        </a>
        <a href="<?= url('dashboard.php') ?>" class="nav-link active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          Mon Espace
        </a>
        <?php if ($canCreateEvent): ?>
        <a href="<?= url('create_event.php') ?>" class="nav-link">
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
      
      <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="logout-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Déconnexion
      </a>
    </aside>

    <!-- Main Content -->
    <main class="main">
      <div class="scroll-progress"></div>
      <!-- Top Search Bar -->
      <div style="margin-bottom: 2rem; position: relative; max-width: 400px;">
        <input type="text" id="eventSearch" placeholder="Rechercher un événement..." 
               style="width: 100%; padding: 0.8rem 1rem 0.8rem 3rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 12px; color: #fff; font-size: 0.9rem; outline: none; transition: all 0.3s;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
             style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--muted);">
          <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </div>

      <div class="welcome-card">
        <div class="welcome-scanline"></div>
        <div class="welcome-logo">
          <img src="assets/img/Logo Vinyle  (1).png" alt="UNDR CLUB Logo" style="width: 100%; height: 100%; object-fit: cover; animation: img-zoom 15s infinite alternate;">
        </div>
        <style>
          @keyframes img-zoom {
            from { transform: scale(1); }
            to { transform: scale(1.2); }
          }
        </style>
        <div class="welcome-text">ESPACE MEMBRE</div>
      </div>

      <!-- Add Event Button (Admin Only) -->
      <?php if ($canCreateEvent): ?>
      <div style="margin-bottom: 2rem;">
        <a href="<?= url('create_event.php') ?>" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; margin-right: 8px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Créer un nouvel événement
        </a>
      </div>
      <?php endif; ?>



      <?php
        $today = date('Y-m-d');
        $upcomingEvents = [];
        $pastEvents = [];
        foreach ($events as $event) {
          $eventDate = $event['event_date'] ?? date('Y-m-d');
          if ($eventDate >= $today) {
            $upcomingEvents[] = $event;
          } else {
            $pastEvents[] = $event;
          }
        }
      ?>

      <!-- Upcoming Events Section -->
      <?php if ($canCreateEvent && !empty($contactMessages)): ?>
      <h2 class="events-title">Messages de Contact</h2>
      <div style="margin-bottom: 4rem; display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($contactMessages as $msg): ?>
          <div class="reveal" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; padding: 2rem; position: relative; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
              <div>
                <div style="color: var(--accent); font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 0.5rem;">
                  <?= h($msg['subject']) ?>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 800;"><?= h($msg['name']) ?></h3>
                <div style="color: var(--muted); font-size: 0.8rem; font-weight: 600;">
                  <?= h($msg['email']) ?> • <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                </div>
              </div>
              <div style="background: rgba(255,255,255,0.05); padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                <?= h($msg['status']) ?>
              </div>
            </div>
            <p style="color: var(--text); line-height: 1.6; font-size: 0.95rem; opacity: 0.9; white-space: pre-wrap;"><?= h($msg['message']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($upcomingEvents)): ?>
      <h2 class="events-title">Événements À VENIR</h2>
      <div class="events-grid" style="margin-bottom: 5rem;">
        <?php foreach ($upcomingEvents as $event): ?>
          <a href="<?= htmlspecialchars($event['url'] ?: 'https://shotgun.live/fr') ?>" target="_blank" class="event-card reveal">
            <?php if (!empty($event['video'])): ?>
              <video autoplay muted loop playsinline>
                <source src="<?= h($event['video']) ?>" type="video/mp4">
              </video>
            <?php else: ?>
              <img src="<?= h($event['image']) ?>" alt="<?= h($event['title']) ?>">
            <?php endif; ?>

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

              <?php if ($canCreateEvent): ?>
              <button class="delete-event-btn" data-id="<?= $event['id'] ?>" style="margin-top: 1rem; background: rgba(255,0,0,0.1); border: 1px solid red; color: red; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                Supprimer le poste
              </button>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Past Events Section -->
      <?php if (!empty($pastEvents)): ?>
      <h2 class="events-title">Archives passées</h2>
      <div class="events-grid">
        <?php foreach ($pastEvents as $event): ?>
          <div class="event-card reveal" style="filter: grayscale(0.5); opacity: 0.8;">
            <?php if (!empty($event['video'])): ?>
              <video autoplay muted loop playsinline>
                <source src="<?= h($event['video']) ?>" type="video/mp4">
              </video>
            <?php else: ?>
              <img src="<?= h($event['image']) ?>" alt="<?= h($event['title']) ?>">
            <?php endif; ?>

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
                <span><?= h($event['club']) ?></span>
              </div>

              <div style="margin-top: 1.5rem; font-size: 0.65rem; font-weight: 900; letter-spacing: 2px; color: var(--muted); border: 1px solid var(--border); padding: 5px 12px; border-radius: 99px; width: fit-content; text-transform: uppercase;">
                Événement terminé
              </div>

              <?php if ($canCreateEvent): ?>
              <button class="delete-event-btn" data-id="<?= $event['id'] ?>" style="margin-top: 1rem; background: rgba(255,0,0,0.1); border: 1px solid red; color: red; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                Supprimer le poste
              </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>

    <script>
      // Custom Cursor Logic
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
          const posX = e.clientX;
          const posY = e.clientY;

          dot.style.left = `${posX}px`;
          dot.style.top = `${posY}px`;

          outline.animate({
            left: `${posX}px`,
            top: `${posY}px`
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

      // Scroll Progress Bar for Main Content
      const main = document.querySelector('.main');
      const progressBar = document.querySelector('.scroll-progress');
      main.addEventListener('scroll', () => {
        const winScroll = main.scrollTop;
        const height = main.scrollHeight - main.clientHeight;
        const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
        progressBar.style.width = scrolled + '%';
      });

      // Reveal on Scroll / Load
      const observerOptions = {
        threshold: 0.1
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('active');
          }
        });
      }, observerOptions);

      document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

      // 3D Tilt for Brand Logo
      const brand = document.querySelector('.brand');
      if (brand) {
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

      // 3D Tilt for Event Cards
      document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;
          
          const rotateX = (centerY - y) / 10;
          const rotateY = (x - centerX) / 10;
          
          card.style.setProperty('--rx', `${rotateX}deg`);
          card.style.setProperty('--ry', `${rotateY}deg`);
        });
        
        card.addEventListener('mouseleave', () => {
          card.style.setProperty('--rx', '0deg');
          card.style.setProperty('--ry', '0deg');
        });
      });

      // Click effects for links/buttons
      document.querySelectorAll('a, button, .search-trigger').forEach(el => {
        el.addEventListener('mouseenter', () => {
          outline.style.transform = 'translate(-50%, -50%) scale(1.5)';
          outline.style.backgroundColor = 'rgba(205, 26, 24, 0.1)';
        });
        el.addEventListener('mouseleave', () => {
          outline.style.transform = 'translate(-50%, -50%) scale(1)';
          outline.style.backgroundColor = 'transparent';
        });
      });

      // Fonction de recherche
      const searchInput = document.getElementById('eventSearch');
      const eventCards = document.querySelectorAll('.event-card');
      const searchTrigger = document.querySelector('.search-trigger');

      searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        
        eventCards.forEach(card => {
          const content = card.innerText.toLowerCase();
          if (content.includes(term)) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });

        // Masquer les titres de section si aucun événement ne correspond
        document.querySelectorAll('.events-grid').forEach(grid => {
          const hasVisibleCards = Array.from(grid.querySelectorAll('.event-card')).some(c => c.style.display !== 'none');
          // Find the previous element which is the header
          let header = grid.previousElementSibling;
          while (header && !header.classList.contains('events-title')) {
            header = header.previousElementSibling;
          }
          if (header) {
            header.style.display = hasVisibleCards ? 'block' : 'none';
          }
        });
      });

      // Focus sur la recherche quand on clique sur l'onglet
      searchTrigger.addEventListener('click', () => {
        searchInput.focus();
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Petit effet visuel sur l'input
        searchInput.style.borderColor = '#fff';
        setTimeout(() => {
          searchInput.style.borderColor = 'var(--accent)';
        }, 1000);
      });

      // Handle search from other pages
      if (window.location.search.includes('search=1')) {
        searchInput.focus();
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        searchInput.style.borderColor = '#fff';
        setTimeout(() => {
          searchInput.style.borderColor = 'var(--accent)';
        }, 1000);
      }

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
            } else {
              alert('Erreur : ' + (data.error || 'Inconnue'));
              btn.innerText = originalText;
              btn.disabled = false;
              btn.style.opacity = '1';
              btn.style.cursor = 'pointer';
            }
          } catch (err) {
            console.error(err);
            alert('Erreur lors de la suppression : ' + err.message);
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

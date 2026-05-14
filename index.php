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
    <title>UNDR CLUB</title>
    <style>
      :root {
        --bg-deep: #000000;
        --bg-card: #0a0a0a;
        --text: #ffffff;
        --muted: rgba(255, 255, 255, 0.6);
        --accent: #cd1a18;
        --accent-glow: rgba(205, 26, 24, 0.3);
        --border: rgba(255, 255, 255, 0.1);
        --section-bg: #050505;
        --font-main: 'Inter', system-ui, -apple-system, sans-serif;
      }
      * { margin: 0; padding: 0; box-sizing: border-box; }
      a { text-decoration: none; color: inherit; }
      html { scroll-behavior: smooth; }
      body {
        font-family: var(--font-main);
        color: var(--text);
        background-color: var(--bg-deep);
        line-height: 1.6;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        cursor: none; /* Hide default cursor */
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

      /* Scroll Progress */
      .scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: var(--accent);
        z-index: 1001;
        transition: width 0.1s ease-out;
      }

      /* Reveal Animations */
      .reveal {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s cubic-bezier(0.2, 0.6, 0.2, 1);
      }
      .reveal.active {
        opacity: 1;
        transform: translateY(0);
      }

      /* Fireworks/Particle Effect */
      .hero-logo-prefix {
        position: relative;
        z-index: 10;
        perspective: 1000px;
      }
      .dynamic-logo-wrapper {
        width: 180px;
        height: 180px;
        margin: 0 auto 1.5rem;
        position: relative;
        transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.2, 0.6, 0.2, 1);
        cursor: pointer;
      }
      .dynamic-logo-wrapper:hover {
        transform: rotateY(15deg) rotateX(10deg) scale(1.05);
      }
      /* Audio Reactive / Shockwave Effect */
      .shockwave {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 150px;
        height: 150px;
        border: 2px solid var(--accent);
        border-radius: 50%;
        pointer-events: none;
        opacity: 0;
        animation: shockwave-animate 1.5s ease-out infinite;
      }
      @keyframes shockwave-animate {
        0% { width: 150px; height: 150px; opacity: 0.8; border-width: 4px; }
        100% { width: 800px; height: 800px; opacity: 0; border-width: 1px; }
      }
      .spark {
        position: absolute;
        width: 2px;
        height: 15px;
        background: #fff;
        border-radius: 2px;
        pointer-events: none;
        opacity: 0;
      }
      @keyframes spark-animate {
        0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        100% { transform: translate(var(--tx), var(--ty)) rotate(var(--rot)) scale(0); opacity: 0; }
      }

      .hero-logo-img {
        width: 100%;
        height: 100%;
        border: 2px solid var(--accent);
        object-fit: cover;
        animation: audio-bass 0.8s infinite cubic-bezier(0.17, 0.67, 0.83, 0.67);
        box-shadow: 0 0 20px rgba(205, 26, 24, 0.4);
        position: relative;
        z-index: 5;
        border-radius: 12px;
      }
      @keyframes audio-bass {
        0%, 100% { transform: scale(1); }
        15% { transform: scale(1.2); box-shadow: 0 0 60px var(--accent); }
        30% { transform: scale(1.05); }
      }
      .logo-glitch-layer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--accent);
        mix-blend-mode: overlay;
        opacity: 0;
        pointer-events: none;
      }
      .dynamic-logo-wrapper:hover .logo-glitch-layer {
        animation: logo-glitch 0.3s steps(2) infinite;
      }
      @keyframes logo-glitch {
        0% { opacity: 0.2; transform: translate(2px, -2px); }
        50% { opacity: 0.4; transform: translate(-2px, 2px); }
        100% { opacity: 0.2; transform: translate(0, 0); }
      }
      .particle {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 4px;
        height: 4px;
        background: var(--accent);
        border-radius: 50%;
        pointer-events: none;
        opacity: 0;
      }
      @keyframes firework {
        0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; }
      }

      /* Navigation Style Odia (Minimaliste) */
      .nav-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 5%;
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(15px);
        z-index: 1000;
        border-bottom: 1px solid var(--border);
      }
      .logo-container {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        text-decoration: none;
        color: #fff;
      }
      .logo-container img {
        width: 65px;
        height: 65px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--accent);
        animation: pulse-mini 3s infinite ease-in-out;
      }
      @keyframes pulse-mini {
        0% { transform: scale(1); box-shadow: 0 0 5px rgba(205, 26, 24, 0.3); }
        50% { transform: scale(1.05); box-shadow: 0 0 15px rgba(205, 26, 24, 0.6); }
        100% { transform: scale(1); box-shadow: 0 0 5px rgba(205, 26, 24, 0.3); }
      }
      .nav-links {
        display: flex;
        gap: 2.5rem;
        align-items: center;
      }
      .nav-links a {
        color: #fff;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        opacity: 0.6;
        transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
        position: relative;
        display: inline-block;
      }
      .nav-links a:hover {
        opacity: 1;
        transform: translateY(-2px) scale(1.1);
        text-shadow: 0 5px 15px rgba(205, 26, 24, 0.5);
      }
      .nav-links a::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--accent);
        transition: width 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
        box-shadow: 0 0 10px var(--accent);
      }
      .nav-links a:hover::after {
        width: 100%;
      }
      .nav-links a.active { opacity: 1; color: var(--accent); }

      /* Hero Section Style Odia (Impactant) */
      .hero {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        text-align: center;
        padding: 120px 10% 80px;
        background: radial-gradient(circle at center, rgba(205, 26, 24, 0.1) 0%, transparent 70%);
        position: relative;
        overflow: hidden;
      }
      /* 3D Grid Background */
      .hero-grid-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        perspective: 1000px;
        overflow: hidden;
        z-index: 0;
        opacity: 0.2;
        pointer-events: none;
      }
      .grid-plane {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background-image: 
          linear-gradient(rgba(205, 26, 24, 0.2) 1px, transparent 1px),
          linear-gradient(90deg, rgba(205, 26, 24, 0.2) 1px, transparent 1px);
        background-size: 60px 60px;
        transform: rotateX(60deg);
        animation: grid-move 10s linear infinite;
      }
      @keyframes grid-move {
        from { transform: rotateX(60deg) translateY(0); }
        to { transform: rotateX(60deg) translateY(60px); }
      }
      .hero-content {
        max-width: 1100px;
        transform-style: preserve-3d;
        perspective: 1000px;
      }
      .hero h1 {
        font-size: clamp(3rem, 8vw, 6.5rem);
        font-weight: 950;
        line-height: 0.95;
        margin-bottom: 2rem;
        letter-spacing: -3px;
        text-transform: uppercase;
        transform: translateZ(100px);
      }
      .hero h1 span.accent {
        color: var(--accent);
      }
      .hero-hashtags {
        color: var(--accent);
        font-weight: 700;
        font-size: 0.8rem;
        letter-spacing: 3px;
        margin-bottom: 2rem;
        text-transform: uppercase;
        opacity: 0.8;
        transform: translateZ(50px);
      }
      .hero-tagline {
        font-size: 1.3rem;
        max-width: 800px;
        margin: 0 auto 3.5rem;
        font-weight: 300;
        color: var(--muted);
        line-height: 1.5;
        transform: translateZ(30px);
      }

      /* Sections & Grid Layout Odia */
      section {
        padding: 8rem 10%;
      }
      .section-intro {
        margin-bottom: 5rem;
      }
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
      .section-title {
        font-size: clamp(2rem, 4vw, 3.5rem);
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 2rem;
      }

      .flex-grid {
        display: flex;
        gap: 4rem;
        align-items: flex-start;
      }
      .flex-left { flex: 1; }
      .flex-right { flex: 1; }

      /* Cards Expertise Odia (Numbered) */
      .expertise-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
      }
      .expertise-card {
        padding: 3rem;
        border: 1px solid var(--border);
        background: transparent;
        transition: all 0.5s cubic-bezier(0.2, 0.6, 0.2, 1);
        position: relative;
        transform-style: preserve-3d;
        perspective: 1000px;
      }
      .expertise-card:hover {
        border-color: var(--accent);
        background: rgba(205, 26, 24, 0.05);
        transform: perspective(1000px) rotateX(var(--erx, 0deg)) rotateY(var(--ery, 0deg)) scale3d(1.02, 1.02, 1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      }
      .expertise-card > * {
        transform: translateZ(20px);
      }
      .card-num {
        font-size: 3.5rem;
        font-weight: 900;
        color: var(--accent);
        display: block;
        margin-bottom: 2rem;
        opacity: 0.8;
        transform: translateZ(40px);
      }
      .expertise-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
      }
      .expertise-card p {
        color: var(--muted);
        font-size: 0.95rem;
      }

      /* About Section Odia (Portrait) */
      .about-container {
        display: flex;
        gap: 6rem;
        align-items: center;
      }
      .about-image {
        flex: 1;
        position: relative;
        transform-style: preserve-3d;
        perspective: 1000px;
      }
      .about-image img {
        width: 100%;
        height: auto;
        filter: grayscale(1) contrast(1.1);
        transition: all 0.6s cubic-bezier(0.2, 0.6, 0.2, 1);
        border: 1px solid var(--border);
      }
      .about-image:hover img {
        filter: grayscale(0) contrast(1);
        transform: perspective(1000px) rotateX(var(--arx, 0deg)) rotateY(var(--ary, 0deg)) scale(1.05);
        border-color: var(--accent);
        box-shadow: 0 30px 60px rgba(0,0,0,0.5);
      }
      .about-content {
        flex: 1.2;
      }
      .founder-info {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border);
      }
      .founder-name {
        font-weight: 800;
        font-size: 1.1rem;
        display: block;
      }
      .founder-title {
        color: var(--muted);
        font-size: 0.9rem;
      }

      /* CTA Section Odia */
      .cta-section {
        background: var(--section-bg);
        text-align: center;
        padding: 10rem 10%;
      }
      .cta-title {
        font-size: clamp(2rem, 5vw, 4rem);
        font-weight: 900;
        margin-bottom: 3rem;
        line-height: 1.1;
      }

      /* Buttons Style Odia */
      .btn {
        padding: 1.2rem 2.8rem;
        font-weight: 700;
        text-transform: none;
        letter-spacing: 0.5px;
        transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
        text-decoration: none;
        display: inline-block;
        font-size: 0.95rem;
        position: relative;
        transform-style: preserve-3d;
        overflow: hidden;
      }
      .btn:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 15px 30px rgba(205, 26, 24, 0.3);
      }
      .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: all 0.6s;
      }
      .btn:hover::before {
        left: 100%;
      }
      .btn-primary {
        background: var(--accent);
        color: #fff;
        border: 1px solid var(--accent);
      }
      .btn-primary:hover {
        background: transparent;
        color: var(--accent);
      }
      .btn-outline {
        border: 1px solid #fff;
        color: #fff;
      }
      .btn-outline:hover {
        background: #fff;
        color: #000;
      }

      /* Events Grid & Cards */
      .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 3rem;
        margin-top: 3rem;
        perspective: 1000px;
      }
      .event-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.2, 0.6, 0.2, 1), transform 0.1s ease-out;
        position: relative;
        aspect-ratio: 4/5;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 2rem;
        text-decoration: none;
        transform-style: preserve-3d;
      }
      .event-card:hover {
        border-color: var(--accent);
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 40px rgba(205, 26, 24, 0.3);
      }
      .event-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at var(--x, 50%) var(--y, 50%), rgba(205, 26, 24, 0.15) 0%, transparent 60%);
        opacity: 0;
        transition: opacity 0.4s;
        pointer-events: none;
        z-index: 3;
      }
      .event-card:hover::before {
        opacity: 1;
      }
      .event-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
        filter: brightness(0.7) grayscale(0.5);
        transition: all 0.5s ease;
      }
      .event-card:hover .event-image {
        filter: brightness(0.8) grayscale(0);
        transform: scale(1.05);
      }
      .event-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 70%);
        z-index: 1;
      }
      .event-content {
        position: relative;
        z-index: 2;
        color: #fff;
      }
      .event-tag {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        background: var(--accent);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.8rem;
        border-radius: 4px;
      }
      .event-title {
        font-size: 1.8rem;
        font-weight: 900;
        margin-bottom: 0.5rem;
        line-height: 1.1;
      }
      .event-info {
        font-size: 0.9rem;
        color: var(--muted);
        font-weight: 500;
      }

      /* Page Transition */
      .page-transition {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #000;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.6s cubic-bezier(0.2, 0.6, 0.2, 1);
      }
      .page-transition.active {
        opacity: 1;
        pointer-events: all;
      }

      /* Hover Effects */
      .nav-links a {
        position: relative;
        padding: 5px 0;
      }
      .nav-links a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 1px;
        background: var(--accent);
        transition: width 0.4s cubic-bezier(0.2, 0.6, 0.2, 1);
      }
      .nav-links a:hover::after, .nav-links a.active::after {
        width: 100%;
      }
      
      .btn {
        position: relative;
        overflow: hidden;
        z-index: 1;
      }
      .btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
        z-index: -1;
      }
      .btn:hover::before {
        width: 300%;
        height: 300%;
      }

      /* Parallax & Smooth Moves */
      .parallax-element {
        transition: transform 0.2s cubic-bezier(0.1, 0, 0.3, 1);
      }

      /* Marquee Effect */
      .marquee-container {
        width: 100vw;
        margin-left: calc(-50vw + 50%);
        overflow: hidden;
        background: var(--accent);
        padding: 1.5rem 0;
        margin-top: 8rem;
        margin-bottom: 8rem;
        transform: rotate(-1deg);
        border-top: 1px solid rgba(0,0,0,0.2);
        border-bottom: 1px solid rgba(0,0,0,0.2);
      }
      .marquee-content {
        display: flex;
        white-space: nowrap;
        animation: marquee 20s linear infinite;
        will-change: transform;
      }
      .marquee-item {
        font-size: 4rem;
        font-weight: 900;
        text-transform: uppercase;
        color: #000;
        margin-right: 4rem;
        display: flex;
        align-items: center;
        gap: 2rem;
      }
      .marquee-item::after {
        content: '•';
        opacity: 0.5;
      }
      @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
      }
      @keyframes marquee-reverse {
        0% { transform: translateX(-50%); }
        100% { transform: translateX(0); }
      }

      .marquee-container.reverse {
        transform: rotate(1deg);
        background: #000;
        border-color: var(--accent);
        margin-top: -8.5rem;
        z-index: 2;
      }
      .marquee-container.reverse .marquee-item {
        color: var(--accent);
      }
      .marquee-container.reverse .marquee-content {
        animation: marquee-reverse 25s linear infinite;
      }

      /* Magnetic Button Helper */
      .magnetic {
        display: inline-block;
        transition: transform 0.2s cubic-bezier(0.23, 1, 0.32, 1);
      }

      /* Custom Scrollbar */
      ::-webkit-scrollbar { width: 6px; }
      ::-webkit-scrollbar-track { background: var(--bg-deep); }
      ::-webkit-scrollbar-wheel { background: var(--accent); }
      ::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }

      @media (max-width: 1024px) {
        .flex-grid, .about-container { flex-direction: column; gap: 3rem; }
        .expertise-grid { grid-template-columns: 1fr; }
        .hero h1 { font-size: clamp(2rem, 10vw, 3.5rem); }
        section { padding: 6rem 5%; }
        .nav-links { gap: 1.5rem; }
        .nav-links a { font-size: 0.75rem; letter-spacing: 1px; }
      }
      @media (max-width: 768px) {
        .nav-header { padding: 0 5%; }
        .nav-links a:not(:last-child) { display: none; } /* Hide secondary links on mobile for simplicity */
        .hero { padding: 0 5%; }
        .section-title { font-size: 2.2rem; }
      }
    </style>
  </head>
  <body>
    <div class="page-transition"></div>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>
    <div class="noise"></div>
    <div class="scroll-progress"></div>

    <header class="nav-header">
      <nav class="nav-links" style="margin-left: auto;">
        <a href="<?= url('index.php') ?>" class="active">Accueil</a>
        <a href="<?= url('concept.php') ?>">Concept</a>
        <a href="<?= url('expertise.php') ?>">Expertise</a>
        <a href="<?= url('agenda.php') ?>">Agenda</a>
        <a href="<?= url('contact.php') ?>">Contact</a>
        <?php if (!$user): ?>
          <a href="<?= url('login.php') ?>" style="color: var(--accent); opacity: 1;">Connexion</a>
        <?php else: ?>
          <a href="<?= url('dashboard.php') ?>" style="color: var(--accent); opacity: 1;">Mon Espace</a>
        <?php endif; ?>
      </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
      <div class="hero-grid-bg">
        <div class="grid-plane"></div>
      </div>
      <div class="hero-content">
        <div class="hero-logo-prefix reveal">
          <div class="shockwave"></div>
          <div class="dynamic-logo-wrapper parallax-element" data-speed="0.05">
            <img src="assets/img/IMG_0599.jpg" alt="UNDR Logo" class="hero-logo-img">
            <div class="logo-glitch-layer"></div>
          </div>
          <h2 class="parallax-element" data-speed="0.03" style="font-size: 2rem; font-weight: 950; letter-spacing: 12px; color: var(--accent); margin-bottom: 1rem; text-transform: uppercase;">UNDR CLUB</h2>
        </div>
        <h1 class="reveal parallax-element" data-speed="0.02">L'évolution de la nuit <br><span class="accent">commence ici.</span></h1>
        <p class="hero-hashtags reveal">#Immersion #Rouge #Musique #Minimal #HardGroove #B2B #Artistique</p>
        <p class="hero-tagline reveal">Nous concevons des soirées immersives en transformant visuellement et musicalement les lieux qui les accueillent pour une expérience unique et identifiable.</p>
        <div class="reveal" style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
          <a href="#concept" class="btn btn-outline magnetic">Découvrir le concept</a>
          <a href="<?= url('register.php') ?>" class="btn btn-outline magnetic">Nous rejoindre</a>
        </div>
        
        <!-- Animated Logo & City Footer -->
        <div class="reveal" style="margin-top: 8rem; display: flex; flex-direction: column; align-items: center; gap: 2rem;">
          <div style="position: relative;">
            <img src="assets/img/animated-logo.png" alt="UNDR Animated" style="width: 220px; height: auto; border-radius: 12px; box-shadow: 0 30px 60px rgba(0,0,0,0.8); filter: contrast(1.2) brightness(0.8); transition: all 0.5s ease;" class="hover-glow">
            <div style="position: absolute; inset: 0; border: 1px solid var(--accent); border-radius: 12px; opacity: 0.3; pointer-events: none;"></div>
          </div>
          <div style="text-align: center;">
            <div style="font-size: 0.8rem; font-weight: 800; letter-spacing: 10px; color: var(--muted); text-transform: uppercase; margin-bottom: 0.5rem;">Based in</div>
            <div style="font-size: clamp(2.5rem, 6vw, 4rem); font-weight: 950; letter-spacing: 20px; color: var(--accent); text-transform: uppercase; text-shadow: 0 0 30px rgba(205, 26, 24, 0.6); margin-left: 20px;">TOULOUSE</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Concept Section -->
    <section id="concept" class="reveal">
      <div class="flex-grid">
        <div class="flex-left">
          <span class="label-pill">Notre Mission</span>
          <h2 class="section-title">Accélérez votre immersion, grâce à la <span class="accent">data musicale et visuelle.</span></h2>
          <p style="font-size: 1.1rem; margin-bottom: 2rem;">Nous accompagnons les clubs et lieux événementiels pour définir leurs formats artistiques, structurer une progression musicale claire et déployer des scénographies performantes réellement adaptées.</p>
          <p style="color: var(--muted);">Notre approche : rendre l'événement immersif, responsable et concret, tout en aidant les lieux à se différencier et à fidéliser leur clientèle.</p>
        </div>
        <div class="flex-right">
          <img src="assets/img/undr_the_ice_19_mars.jpg" style="width: 100%; box-shadow: 0 40px 80px rgba(0,0,0,0.6);" alt="UNDR Concept">
        </div>
      </div>
    </section>

    <!-- Expertise Section (Numbered cards like Odia) -->
    <section id="expertise" style="background: #050505;" class="reveal">
      <div class="section-intro">
        <span class="label-pill">Expertise</span>
        <h2 class="section-title">Une identité <span class="accent">forte et structurée.</span></h2>
      </div>
      <div class="expertise-grid">
        <div class="expertise-card reveal">
          <span class="card-num">01</span>
          <h3>Progression Sonore</h3>
          <p>Un voyage maîtrisé du minimal au hard groove, porté par des formats B2B exclusifs entre nos résidents et artistes invités.</p>
        </div>
        <div class="expertise-card reveal">
          <span class="card-num">02</span>
          <h3>Signature Visuelle</h3>
          <p>Le rouge (#cd1a18) structure notre scénographie : rideaux, néons et toiles artistiques créent une immersion totale.</p>
        </div>
        <div class="expertise-card reveal">
          <span class="card-num">03</span>
          <h3>Adaptabilité</h3>
          <p>Une direction artistique forte qui s'adapte aux contraintes techniques pour offrir une ambiance différenciante et cohérente.</p>
        </div>
      </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="reveal">
      <div class="section-intro">
        <span class="label-pill">Agenda</span>
        <h2 class="section-title">Événements <span class="accent">À VENIR.</span></h2>
        <p style="color: var(--muted); max-width: 600px;">Retrouvez toutes nos dates à venir et réservez vos places directement sur Shotgun.</p>
      </div>
      
      <div class="events-grid">
        <?php 
        $all_events = get_events();
        $today = date('Y-m-d');
        
        // Filter upcoming events based on date
        $upcoming_events = array_filter($all_events, function($e) use ($today) {
            $eventDate = $e['event_date'] ?? '';
            return $eventDate >= $today;
        });
        
        if (empty($upcoming_events)) {
            // If no upcoming, show a message
            echo '<div style="grid-column: 1/-1; text-align: center; padding: 4rem; border: 1px dashed var(--border); border-radius: 20px;">';
            echo '<p style="color: var(--muted); margin-bottom: 2rem;">Aucun événement prévu pour le moment.</p>';
            echo '</div>';
        } else {
            foreach ($upcoming_events as $ev): ?>
              <a href="<?= htmlspecialchars($ev['url'] ?: 'https://shotgun.live/fr') ?>" target="_blank" class="event-card reveal">
                <img src="<?= htmlspecialchars($ev['image'] ?: 'assets/img/image.jpg') ?>" class="event-image" alt="<?= htmlspecialchars($ev['title']) ?>">
                <div class="event-overlay"></div>
                <div class="event-content">
                  <span class="event-tag"><?= htmlspecialchars($ev['city']) ?></span>
                  <h3 class="event-title"><?= htmlspecialchars($ev['title']) ?></h3>
                  <div class="event-info">
                    <?= htmlspecialchars(format_event_date($ev['event_date'])) ?> — <?= htmlspecialchars($ev['club']) ?>
                  </div>
                  <?php if (!empty($ev['lineup'])): ?>
                    <div style="font-size: 0.75rem; color: var(--accent); font-weight: 700; margin-top: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                      Lineup: <?= htmlspecialchars($ev['lineup']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach;
        } ?>
      </div>

      <!-- Past Events Sub-section -->
      <?php 
      $past_events = array_filter($all_events, function($e) use ($today) {
          $eventDate = $e['event_date'] ?? '';
          return $eventDate < $today;
      });
      if (!empty($past_events)): ?>
        <div style="margin-top: 6rem;">
          <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 2rem; color: var(--muted); text-transform: uppercase; letter-spacing: 2px;">Événements passés</h3>
          <div class="events-grid" style="opacity: 0.7;">
            <?php foreach ($past_events as $ev): ?>
              <div class="event-card reveal" style="cursor: default;">
                <img src="<?= htmlspecialchars($ev['image'] ?: 'assets/img/image.jpg') ?>" class="event-image" alt="<?= htmlspecialchars($ev['title']) ?>" style="filter: grayscale(1) brightness(0.5);">
                <div class="event-overlay"></div>
                <div class="event-content">
                  <span class="event-tag" style="background: var(--border);"><?= htmlspecialchars($ev['city']) ?></span>
                  <h3 class="event-title"><?= htmlspecialchars($ev['title']) ?></h3>
                  <div class="event-info">
                    <?= htmlspecialchars(format_event_date($ev['event_date'])) ?> — <?= htmlspecialchars($ev['club']) ?>
                  </div>
                  <?php if (!empty($ev['lineup'])): ?>
                    <div style="font-size: 0.75rem; color: var(--muted); font-weight: 700; margin-top: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                      Lineup: <?= htmlspecialchars($ev['lineup']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <!-- About Section (Like Odia Cristelle Delmas) -->
    <section id="about" class="reveal">
      <div class="about-container">
        <div class="about-image">
          <img src="assets/img/undr_phase_iv_12_mars.jpg" alt="Louis Delmas">
        </div>
        <div class="about-content">
          <span class="label-pill">À propos d'UNDR</span>
          <h2 class="section-title">Une vision portée par <span class="accent">la passion.</span></h2>
          <p style="font-size: 1.15rem; margin-bottom: 2rem; line-height: 1.7;">Je suis Louis Delmas, fondateur d'UNDR CLUB. Mon objectif est d'accompagner les lieux qui souhaitent comprendre les nouveaux codes de la nuit, structurer leurs usages et déployer des solutions réellement utiles à leurs publics.</p>
          <p style="color: var(--muted); margin-bottom: 2.5rem;">Mon approche : rendre l'immersion accessible et concrète, tout en aidant les équipes à monter en compétences et à sécuriser leurs pratiques artistiques.</p>
          <div class="founder-info">
            <span class="founder-name">Louis Delmas</span>
            <span class="founder-title">Fondateur & Directeur Artistique — UNDR CLUB</span>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section reveal">
      <h2 class="cta-title">Prêts à explorer le potentiel de <span class="accent">vos nuits ?</span></h2>
      <p style="margin-bottom: 4rem; font-size: 1.3rem; color: var(--muted);">Discutons ensemble de vos enjeux et identifions les actions à plus forte valeur.</p>
      
      <div style="display: flex; flex-direction: column; align-items: center; gap: 2rem;">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap; justify-content: center;">
          <a href="<?= url('contact.php') ?>" class="btn btn-primary" style="font-size: 1.1rem; padding: 1.5rem 3rem;">Contact</a>
          <a href="mailto:booking@undrclub.com" class="btn btn-primary" style="font-size: 1.1rem; padding: 1.5rem 3rem; background: transparent; border: 1px solid var(--accent); color: var(--accent);">Booking & Management</a>
        </div>
      </div>
      
      <div style="margin-top: 8rem; display: flex; justify-content: center; gap: 4rem;">
        <a href="https://www.instagram.com/undr.clubb" target="_blank" style="color: #fff; text-decoration: none; font-weight: 700; letter-spacing: 2px; font-size: 0.8rem;">INSTAGRAM</a>
        <a href="https://www.tiktok.com/@undr.club" target="_blank" style="color: #fff; text-decoration: none; font-weight: 700; letter-spacing: 2px; font-size: 0.8rem;">TIKTOK</a>
      </div>
    </section>

    <!-- Marquee Section -->
    <div class="marquee-container">
      <div class="marquee-content">
        <div class="marquee-item">TOULOUSE</div>
        <div class="marquee-item">BORDEAUX</div>
        <div class="marquee-item">CAP FERRET</div>
        <div class="marquee-item">LEUCATE</div>
        <div class="marquee-item">BIARRITZ</div>
        <div class="marquee-item">UNDR CLUB</div>
        <!-- Repeat for smooth loop -->
        <div class="marquee-item">TOULOUSE</div>
        <div class="marquee-item">BORDEAUX</div>
        <div class="marquee-item">CAP FERRET</div>
        <div class="marquee-item">LEUCATE</div>
        <div class="marquee-item">BIARRITZ</div>
        <div class="marquee-item">UNDR CLUB</div>
      </div>
    </div>

    <!-- Lineup Marquee -->
    <div class="marquee-container reverse">
      <div class="marquee-content">
        <?php 
        $lineup_artists = ["LOUIS DELMAS", "B2B", "RESIDENTS", "GUESTS", "HARD GROOVE", "MINIMAL", "TECH HOUSE", "UNDR CREW"];
        // If we have events with lineups, let's add some artists
        foreach($all_events as $ev) {
            if(!empty($ev['lineup'])) {
                $parts = explode(',', $ev['lineup']);
                foreach($parts as $p) {
                    $lineup_artists[] = trim(strtoupper($p));
                }
            }
        }
        $lineup_artists = array_unique(array_filter($lineup_artists));
        $lineup_artists = array_slice($lineup_artists, 0, 15); // Limit to 15 unique entries
        
        for($i=0; $i<2; $i++): // Loop twice for smooth animation
          foreach($lineup_artists as $artist): ?>
            <div class="marquee-item"><?= htmlspecialchars($artist) ?></div>
          <?php endforeach;
        endfor; ?>
      </div>
    </div>

    <footer style="padding: 4rem 10%; border-top: 1px solid var(--border); color: rgba(255,255,255,0.3); font-size: 0.75rem; display: flex; justify-content: space-between; font-weight: 500;">
      <span>© <?= date('Y') ?> UNDR CLUB — TOUS DROITS RÉSERVÉS</span>
      <span>DESIGN INSPIRED BY ODIA & ATARASHI</span>
    </footer>

    <script>
      // 1. Cursor & Page Transitions
      const dot = document.querySelector('.cursor-dot');
      const outline = document.querySelector('.cursor-outline');
      const transition = document.querySelector('.page-transition');

      // Detect touch device
      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      if (isTouchDevice) {
        if (dot) dot.style.display = 'none';
        if (outline) outline.style.display = 'none';
        document.body.style.cursor = 'auto';
      }

      window.addEventListener('load', () => {
        if (transition) transition.classList.remove('active');
      });

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

      // 2. Magnetic Buttons & Links
      document.querySelectorAll('a, button, .magnetic').forEach(el => {
        el.addEventListener('mousemove', (e) => {
          const rect = el.getBoundingClientRect();
          const x = e.clientX - rect.left - rect.width / 2;
          const y = e.clientY - rect.top - rect.height / 2;
          
          if (el.classList.contains('magnetic')) {
            el.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
          }
          
          outline.style.transform = 'translate(-50%, -50%) scale(1.5)';
          outline.style.backgroundColor = 'rgba(205, 26, 24, 0.1)';
        });

        el.addEventListener('mouseleave', () => {
          if (el.classList.contains('magnetic')) {
            el.style.transform = `translate(0, 0)`;
          }
          outline.style.transform = 'translate(-50%, -50%) scale(1)';
          outline.style.backgroundColor = 'transparent';
        });

        el.addEventListener('click', (e) => {
          const href = el.getAttribute('href');
          if (href && !href.startsWith('#') && !el.hasAttribute('target')) {
            e.preventDefault();
            transition.classList.add('active');
            setTimeout(() => { window.location.href = href; }, 600);
          }
        });
      });

      // 3D Tilt for Hero Content
      const heroContent = document.querySelector('.hero-content');
      if (heroContent && !isTouchDevice) {
        window.addEventListener('mousemove', (e) => {
          const x = (e.clientX / window.innerWidth - 0.5) * 20;
          const y = (e.clientY / window.innerHeight - 0.5) * 20;
          heroContent.style.transform = `rotateX(${-y}deg) rotateY(${x}deg)`;
        });
      }

      // 3D Tilt for About Image
      const aboutImg = document.querySelector('.about-image');
      if (aboutImg && !isTouchDevice) {
        aboutImg.addEventListener('mousemove', (e) => {
          const rect = aboutImg.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;
          const rotateX = (centerY - y) / 20;
          const rotateY = (x - centerX) / 20;
          aboutImg.style.setProperty('--arx', `${rotateX}deg`);
          aboutImg.style.setProperty('--ary', `${rotateY}deg`);
        });
        aboutImg.addEventListener('mouseleave', () => {
          aboutImg.style.setProperty('--arx', '0deg');
          aboutImg.style.setProperty('--ary', '0deg');
        });
      }

      // 3D Tilt for Expertise Cards
      document.querySelectorAll('.expertise-card').forEach(card => {
        if (!isTouchDevice) {
          card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (centerY - y) / 10;
            const rotateY = (x - centerX) / 10;
            card.style.setProperty('--erx', `${rotateX}deg`);
            card.style.setProperty('--ery', `${rotateY}deg`);
          });
          card.addEventListener('mouseleave', () => {
            card.style.setProperty('--erx', '0deg');
            card.style.setProperty('--ery', '0deg');
          });
        }
      });

      // 3. 3D Tilt for Event Cards
      document.querySelectorAll('.event-card').forEach(card => {
        if (!isTouchDevice) {
          card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
            
            // Spotlight effect
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
          });
          
          card.addEventListener('mouseleave', () => {
            card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
          });
        }
      });

      // 4. Parallax Hero Elements
      if (!isTouchDevice) {
        window.addEventListener('mousemove', (e) => {
          const x = (e.clientX - window.innerWidth / 2) / (window.innerWidth / 2);
          const y = (e.clientY - window.innerHeight / 2) / (window.innerHeight / 2);
          
          document.querySelectorAll('.parallax-element').forEach(el => {
            const speed = el.getAttribute('data-speed') || 0.05;
            const tx = x * 100 * speed;
            const ty = y * 100 * speed;
            el.style.transform = `translate(${tx}px, ${ty}px)`;
          });
        });
      }

      // 5. Scroll Progress & Reveal
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) entry.target.classList.add('active');
        });
      }, { threshold: 0.15 });

      document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

      window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        document.querySelector('.scroll-progress').style.width = scrolled + '%';
      });

      // 6. Fireworks & Sparks (Hero Logo)
      const heroLogo = document.querySelector('.hero-logo-prefix');
      if (heroLogo) {
        setInterval(() => {
          for (let i = 0; i < 40; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const angle = Math.random() * Math.PI * 2;
            const dist = 100 + Math.random() * 250;
            p.style.setProperty('--tx', `${Math.cos(angle) * dist}px`);
            p.style.setProperty('--ty', `${Math.sin(angle) * dist}px`);
            const dur = 0.8 + Math.random() * 1.5;
            p.style.animation = `firework ${dur}s ${Math.random() * 0.3}s ease-out forwards`;
            heroLogo.appendChild(p);
            setTimeout(() => p.remove(), (dur + 0.3) * 1000);
          }
        }, 1500);

        setInterval(() => {
          for (let i = 0; i < 5; i++) {
            const s = document.createElement('div');
            s.className = 'spark';
            const angle = Math.random() * Math.PI * 2;
            const dist = 80 + Math.random() * 40;
            s.style.setProperty('--tx', `${Math.cos(angle) * dist}px`);
            s.style.setProperty('--ty', `${Math.sin(angle) * dist}px`);
            s.style.setProperty('--rot', `${angle * (180 / Math.PI)}deg`);
            s.style.left = s.style.top = '50%';
            s.style.animation = `spark-animate 0.4s ease-out forwards`;
            heroLogo.appendChild(s);
            setTimeout(() => s.remove(), 400);
          }
        }, 400);
      }
    </script>
  </body>
</html>
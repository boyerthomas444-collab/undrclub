<?php

declare(strict_types=1);

function base_path(): string
{
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptName === '') {
        return '';
    }

    $dir = str_replace('\\', '/', dirname($scriptName));
    $dir = rtrim($dir, '/');

    return ($dir === '' || $dir === '.') ? '' : $dir;
}

function url(string $path): string
{
    if (preg_match('~^(?:f|ht)tps?://~i', $path)) {
        return $path;
    }

    if ($path === '') {
        return base_path() . '/';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return base_path() . $path;
}

function full_url(string $path = ''): string
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . url($path);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Configuration de la base de données
    // En production, ces valeurs seront fournies par votre hébergeur
    $host = $_SERVER['DB_HOST'] ?? '127.0.0.1';
    $dbName = $_SERVER['DB_NAME'] ?? 'undr_club';
    $username = $_SERVER['DB_USER'] ?? 'root';
    $password = $_SERVER['DB_PASS'] ?? '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    return $pdo;
}

function ensure_tables(): void
{
    // Ensure users table
    db()->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        phone VARCHAR(30) NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure users columns (in case table existed but was old)
    $columns = [
        'first_name' => 'VARCHAR(100) NULL',
        'phone' => 'VARCHAR(30) NULL',
        'is_admin' => 'TINYINT(1) DEFAULT 0',
    ];

    foreach ($columns as $name => $definition) {
        $stmt = db()->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$name]);
        $exists = (bool)$stmt->fetch();

        if (!$exists) {
            db()->exec("ALTER TABLE users ADD COLUMN `{$name}` {$definition}");
        }
    }

    // Ensure login_attempts table
    db()->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_time (email, attempt_time),
        INDEX idx_ip_time (ip_address, attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function record_login_attempt(string $email): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = db()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)');
    $stmt->execute([$email, $ip]);
}

function check_brute_force(string $email): ?int
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $limit = 5; // 5 attempts
    $timeframe = 15; // 15 minutes

    // Check attempts for this email OR this IP
    $stmt = db()->prepare("
        SELECT COUNT(*) 
        FROM login_attempts 
        WHERE (email = ? OR ip_address = ?) 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([$email, $ip, $timeframe]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= $limit) {
        error_log("Brute force detected for email: $email, IP: $ip. Attempts: $count");
        // Find how many seconds until the oldest of the last $limit attempts expires
        $stmt = db()->prepare("
            SELECT TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MIN(attempt_time), INTERVAL ? MINUTE))
            FROM (
                SELECT attempt_time 
                FROM login_attempts 
                WHERE (email = ? OR ip_address = ?) 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ORDER BY attempt_time DESC 
                LIMIT ?
            ) as recent
        ");
        $stmt->execute([$timeframe, $email, $ip, $timeframe, $limit]);
        return (int)$stmt->fetchColumn();
    }

    return null;
}

function clear_login_attempts(string $email): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE email = ? OR ip_address = ?');
    $stmt->execute([$email, $ip]);
}

function ensure_events_table(): void
{
    db()->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        city VARCHAR(100) NOT NULL,
        club VARCHAR(100) NOT NULL,
        event_date DATE NOT NULL,
        time VARCHAR(50) NOT NULL,
        color VARCHAR(20) DEFAULT '#cd1a18',
        image VARCHAR(500) DEFAULT '',
        video VARCHAR(500) DEFAULT '',
        url VARCHAR(500) DEFAULT '',
        lineup VARCHAR(255) DEFAULT '',
        status VARCHAR(50) DEFAULT 'À VENIR',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check if url column exists, if not add it
    $stmt = db()->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events' 
        AND COLUMN_NAME = 'url'
    ");
    $stmt->execute();
    if (!$stmt->fetch()) {
        db()->exec("ALTER TABLE events ADD COLUMN url VARCHAR(500) DEFAULT '' AFTER video");
    }

    // Check if lineup column exists, if not add it
    $stmt = db()->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events' 
        AND COLUMN_NAME = 'lineup'
    ");
    $stmt->execute();
    if (!$stmt->fetch()) {
        db()->exec("ALTER TABLE events ADD COLUMN lineup VARCHAR(255) DEFAULT '' AFTER url");
    }

    // Seed default events if table is empty
    $count = (int)db()->query("SELECT COUNT(*) FROM events")->fetchColumn();
    if ($count === 0) {
        $defaults = [
            [
                'title' => 'UNDR THE ICE',
                'city' => 'Toulouse',
                'club' => '@ICE CLUB',
                'event_date' => '2026-03-19',
                'time' => '00H30 / 6H45',
                'status' => 'PASSÉ',
                'color' => '#cd1a18',
                'video' => 'assets/video/undr_ice_20_avril.mp4',
                'image' => 'assets/img/undr_the_ice_19_mars.jpg',
                'lineup' => 'JOLY, LIZY x SOLA'
            ],
            [
                'title' => 'UNDR PHASE IV',
                'city' => 'Bordeaux',
                'club' => '@BOOMBOOM',
                'event_date' => '2026-03-12',
                'time' => '00H00 / 5H00',
                'status' => 'PASSÉ',
                'color' => '#cd1a18',
                'image' => 'assets/img/undr_phase_iv_12_mars.jpg',
                'lineup' => 'SOLA, JOLY x DOUMS'
            ],
            [
                'title' => 'THE THIRD ACT',
                'city' => 'Bordeaux',
                'club' => '@BOOMBOOM',
                'event_date' => '2026-01-29',
                'time' => '23H45 / 5H00',
                'status' => 'PASSÉ',
                'color' => '#cd1a18',
                'image' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?q=80&w=1000&auto=format&fit=crop',
                'lineup' => ''
            ],
            [
                'title' => 'OPENING ACT',
                'city' => 'Bordeaux',
                'club' => '@BOUMBOOM',
                'event_date' => '2026-01-15',
                'time' => '00H00 / 5H00',
                'status' => 'PASSÉ',
                'color' => '#cd1a18',
                'image' => 'assets/img/IMG_0593.jpg',
                'lineup' => 'SOLA B2B DOUMS, JOLY'
            ]
        ];

        foreach ($defaults as $data) {
            $stmt = db()->prepare('
                INSERT INTO events (title, city, club, event_date, time, color, image, video, url, lineup, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['title'], $data['city'], $data['club'], $data['event_date'],
                $data['time'], $data['color'], $data['image'], $data['video'] ?? '',
                $data['url'] ?? '', $data['lineup'] ?? '', $data['status']
            ]);
        }
    }
}

function add_event(array $data): int
{
    ensure_events_table();
    
    $stmt = db()->prepare('
        INSERT INTO events (title, city, club, event_date, time, color, image, video, url, lineup, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $data['title'] ?? '',
        $data['city'] ?? '',
        $data['club'] ?? '',
        $data['event_date'] ?? '',
        $data['time'] ?? '',
        $data['color'] ?? '#cd1a18',
        $data['image'] ?? '',
        $data['video'] ?? '',
        $data['url'] ?? '',
        $data['lineup'] ?? '',
        $data['status'] ?? 'À VENIR'
    ]);

    return (int)db()->lastInsertId();
}

function get_events(): array
{
    ensure_events_table();
    
    $stmt = db()->query('SELECT * FROM events ORDER BY event_date DESC');
    return $stmt->fetchAll();
}

function format_event_date(string $date): string
{
    $months = [
        '01' => 'JANVIER', '02' => 'FEVRIER', '03' => 'MARS', '04' => 'AVRIL',
        '05' => 'MAI', '06' => 'JUIN', '07' => 'JUILLET', '08' => 'AOUT',
        '09' => 'SEPTEMBRE', '10' => 'OCTOBRE', '11' => 'NOVEMBRE', '12' => 'DECEMBRE'
    ];
    $days = ['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'];
    
    $timestamp = strtotime($date);
    $day = $days[date('N', $timestamp) - 1];
    $month = $months[date('m', $timestamp)];
    $dayNum = date('d', $timestamp);
    
    return "{$day} {$dayNum} {$month} " . date('Y', $timestamp);
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Paramètres de session plus souples pour éviter les problèmes de chemin
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/', // Toujours à la racine pour éviter les soucis avec les espaces dans les dossiers
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function set_security_headers(): void
{
    // Disable error display to users in production
    // ini_set('display_errors', '0');
    // error_reporting(E_ALL);

    // Prevent Clickjacking
    header('X-Frame-Options: DENY');
    // Prevent MIME-sniffing
    header('X-Content-Type-Options: nosniff');
    // Enable XSS Filter in browser
    header('X-XSS-Protection: 1; mode=block');
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Content Security Policy (Basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://grainy-gradients.vercel.app; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; video-src 'self' data: https:;");
}

function generate_csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    start_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

set_security_headers();

function current_user(): ?array
{
    start_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: ' . url('login.php'));
        exit;
    }
    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['is_admin'] ?? 0) === 1;
}

function ensure_contact_table(): void
{
    db()->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'unread',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function get_contact_messages(): array
{
    ensure_contact_table();
    return db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
}


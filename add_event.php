<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Toujours s'assurer que la session est démarrée au début
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé : Vous n\'êtes pas administrateur.']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    // Si $_POST est vide, c'est probablement que post_max_size a été dépassé
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Le fichier est trop volumineux pour le serveur (post_max_size).']);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Jeton CSRF invalide ou session expirée.']);
    }
    exit;
}

$title = strip_tags(trim($_POST['title'] ?? ''));
$city = strip_tags(trim($_POST['city'] ?? ''));
$club = strip_tags(trim($_POST['club'] ?? ''));
$event_date = $_POST['event_date'] ?? '';
$time = strip_tags(trim($_POST['time'] ?? ''));
$lineup = strip_tags(trim($_POST['lineup'] ?? ''));
$url = filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL);
$color = $_POST['color'] ?? '#cd1a18';

if (empty($title) || empty($city) || empty($club) || empty($event_date) || empty($time)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields', 'received' => $_POST]);
    exit;
}

$mediaUrls = [];
$mediaCount = isset($_POST['media_count']) ? intval($_POST['media_count']) : 0;

$imgUploadDir = __DIR__ . '/assets/img/events/';
$vidUploadDir = __DIR__ . '/assets/video/events/';

if (!is_dir($imgUploadDir)) {
    if (!mkdir($imgUploadDir, 0755, true)) {
        $errorMsg = 'Impossible de créer le dossier des images : ' . $imgUploadDir;
        error_log($errorMsg);
        http_response_code(500);
        echo json_encode(['error' => $errorMsg]);
        exit;
    }
}
if (!is_dir($vidUploadDir)) {
    if (!mkdir($vidUploadDir, 0755, true)) {
        $errorMsg = 'Impossible de créer le dossier des vidéos : ' . $vidUploadDir;
        error_log($errorMsg);
        http_response_code(500);
        echo json_encode(['error' => $errorMsg]);
        exit;
    }
}

for ($i = 0; $i < $mediaCount; $i++) {
    $key = 'media_' . $i;
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        error_log("File $key error: " . ($_FILES[$key]['error'] ?? 'not set'));
        continue;
    }

    $file = $_FILES[$key];
    $fileType = mime_content_type($file['tmp_name']);
    
    // More robust MIME type check using finfo for extra security if needed, 
    // but mime_content_type is generally sufficient.
    $isImage = in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    $isVideo = in_array($fileType, ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-matroska']);

    if (!$isImage && !$isVideo) {
        $errorMsg = "Type de fichier non supporté : $fileType pour le fichier $key";
        error_log($errorMsg);
        http_response_code(400);
        echo json_encode(['error' => $errorMsg]);
        // Clean up already uploaded files
        foreach ($mediaUrls as $url) {
            @unlink(__DIR__ . '/' . $url);
        }
        exit;
    }

    // Size limits with better error reporting
    if ($isVideo && $file['size'] > 50 * 1024 * 1024) {
        $errorMsg = "La vidéo est trop volumineuse (max 50Mo) : " . round($file['size'] / 1024 / 1024, 2) . "Mo";
        error_log($errorMsg);
        http_response_code(400);
        echo json_encode(['error' => $errorMsg]);
        foreach ($mediaUrls as $url) {
            @unlink(__DIR__ . '/' . $url);
        }
        exit;
    }
    if ($isImage && $file['size'] > 5 * 1024 * 1024) {
        $errorMsg = "L'image est trop volumineuse (max 5Mo) : " . round($file['size'] / 1024 / 1024, 2) . "Mo";
        error_log($errorMsg);
        http_response_code(400);
        echo json_encode(['error' => $errorMsg]);
        foreach ($mediaUrls as $url) {
            @unlink(__DIR__ . '/' . $url);
        }
        exit;
    }

    $prefix = $isVideo ? 'event_vid_' : 'event_img_';
    $uploadDir = $isVideo ? $vidUploadDir : $imgUploadDir;
    
    // Generate a secure random filename instead of using basename()
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Mapping of allowed extensions to MIME types
    $allowedMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska'
    ];
    
    // Mapping of MIME types to preferred extensions
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
        'video/x-matroska' => 'mkv'
    ];
    
    // If extension doesn't match MIME type, use a default extension based on MIME type
    if (!isset($allowedMap[$extension]) || $allowedMap[$extension] !== $fileType) {
        $extension = $mimeToExt[$fileType] ?? ($isVideo ? 'mp4' : 'jpg');
    }

    $fileName = uniqid($prefix) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = ($isVideo ? 'assets/video/events/' : 'assets/img/events/') . $fileName;
        $mediaUrls[] = $relativePath;
    } else {
        $errorMsg = "Échec du déplacement du fichier téléchargé : " . $file['name'];
        error_log($errorMsg . " (vers $targetPath)");
        http_response_code(500);
        echo json_encode(['error' => $errorMsg]);
        // Clean up already uploaded files
        foreach ($mediaUrls as $url) {
            @unlink(__DIR__ . '/' . $url);
        }
        exit;
    }
}

// Use first image as main image, first video as video
$imagePath = '';
$videoPath = '';

foreach ($mediaUrls as $mUrl) {
    if (strpos($mUrl, 'video/') !== false && empty($videoPath)) {
        $videoPath = $mUrl;
    } elseif (strpos($mUrl, 'img/') !== false && empty($imagePath)) {
        $imagePath = $mUrl;
    }
    if (!empty($imagePath) && !empty($videoPath)) break;
}

$data = [
    'title' => $title,
    'city' => $city,
    'club' => $club,
    'event_date' => $event_date,
    'time' => $time,
    'lineup' => $lineup,
    'url' => $url,
    'color' => $color,
    'image' => $imagePath,
    'video' => $videoPath
];

try {
    $id = add_event($data);
    echo json_encode(['success' => true, 'id' => $id, 'media' => $mediaUrls]);
} catch (Exception $e) {
    error_log("Error creating event: " . $e->getMessage());
    // Clean up uploaded files on DB failure
    foreach ($mediaUrls as $url) {
        @unlink(__DIR__ . '/' . $url);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create event: ' . $e->getMessage()]);
}
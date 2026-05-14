<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token mismatch']);
    exit;
}
    
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    // Fetch media paths before deleting the record
    $stmt = db()->prepare("SELECT image, video FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if ($event) {
        // Delete record
        $stmt = db()->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);

        // Delete associated files
        if (!empty($event['image']) && !preg_match('~^(?:f|ht)tps?://~i', $event['image'])) {
            $imagePath = __DIR__ . '/' . ltrim($event['image'], '/');
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
        if (!empty($event['video']) && !preg_match('~^(?:f|ht)tps?://~i', $event['video'])) {
            $videoPath = __DIR__ . '/' . ltrim($event['video'], '/');
            if (file_exists($videoPath)) {
                @unlink($videoPath);
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Événement non trouvé']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;

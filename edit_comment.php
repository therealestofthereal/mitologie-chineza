<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/csrf.php';

$token = extract_request_csrf();
if (!validate_csrf_token($token)) {
    echo json_encode(['success' => false, 'error' => 'invalid_csrf']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'neautentificat']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id']   ?? 0);
$text = trim($data['text']  ?? '');

if (!$id || $text === '') {
    echo json_encode(['success' => false, 'error' => 'date invalide']);
    exit;
}

try {
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // verify comment exists and belongs to user
    $stmt = $pdo->prepare("SELECT user_id FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || (int)$row['user_id'] !== (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'neautorizat']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE messages SET message = ?, edited_at = NOW() WHERE id = ?");
    $stmt->execute([$text, $id]);

    echo json_encode([
        'success' => true,
        'html'    => nl2br(htmlspecialchars($text))
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

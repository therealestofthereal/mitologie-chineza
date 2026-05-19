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
    echo json_encode(['success' => false, 'error' => 'neautorizat']);
    exit;
}

require_once __DIR__ . '/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'id invalid']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        echo json_encode(['success' => false, 'error' => 'comentariu inexistent']);
        exit;
    }

    $commentOwnerId = isset($comment['user_id']) ? (int)$comment['user_id'] : 0;
    $currentUserId = (int)$_SESSION['user_id'];
    $currentRole = $_SESSION['role'] ?? 'user';

    if ($commentOwnerId !== $currentUserId && $currentRole !== 'admin') {
        echo json_encode(['success' => false, 'error' => 'neautorizat']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

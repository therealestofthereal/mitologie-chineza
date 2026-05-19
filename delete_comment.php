<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/csrf.php';

$token = extract_request_csrf();
if (!validate_csrf_token($token)) {
    echo json_encode(['success' => false, 'error' => 'invalid_csrf']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
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
    $stmt = $pdo->prepare("DELETE FROM site_comments.messages WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

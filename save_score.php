<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'neautentificat']);
    exit;
}
$data    = json_decode(file_get_contents('php://input'), true);
require_once __DIR__ . '/csrf.php';
$token = extract_request_csrf();
if (!validate_csrf_token($token)) {
    echo json_encode(['success' => false, 'error' => 'invalid_csrf']);
    exit;
}
$score   = (int)($data['score'] ?? 0);
$total   = max(1, (int)($data['total'] ?? 1));
$percent = (int)round($score / $total * 100);

try {
require_once __DIR__ . '/db_config.php';
try {

    // read current highscore
    $stmt = $pdo->prepare("SELECT quiz_highscore FROM site_users.site_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current = (int)$stmt->fetchColumn();

    $isNew = $percent > $current;

    if ($isNew) {
        $stmt = $pdo->prepare("UPDATE site_users.site_users SET quiz_highscore = ? WHERE id = ?");
        $stmt->execute([$percent, $_SESSION['user_id']]);
    }

    echo json_encode([
        'success'          => true,
        'score'            => $percent,
        'is_new_highscore' => $isNew,
        'previous'         => $current,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

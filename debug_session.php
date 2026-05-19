<?php
// Temporary debug endpoint — shows current session and DB user row for troubleshooting
// Remove this file when done.

session_start();
require_once __DIR__ . '/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$out = ['session' => $_SESSION];

if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, email, role FROM site_users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $out['db_user'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $out['db_error'] = $e->getMessage();
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

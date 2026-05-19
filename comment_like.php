<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Autentificare necesară']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
require_once __DIR__ . '/csrf.php';
$token = extract_request_csrf();
if (!validate_csrf_token($token)) {
    echo json_encode(['success' => false, 'error' => 'invalid_csrf']);
    exit;
}
$commentId = isset($input['id']) && ctype_digit(strval($input['id'])) ? (int)$input['id'] : 0;
if (!$commentId) {
    echo json_encode(['success' => false, 'error' => 'ID comentariu invalid']);
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=site_comments;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $hasLikesTable = $pdo->query("SHOW TABLES LIKE 'comment_likes'")->fetch();
    if (!$hasLikesTable) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS comment_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                comment_id INT NOT NULL,
                user_id INT NOT NULL,
                liked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY comment_user (comment_id, user_id),
                INDEX idx_comment_id (comment_id),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    $exists = $pdo->prepare("SELECT id FROM messages WHERE id = ?");
    $exists->execute([$commentId]);
    if (!$exists->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Comentariu inexistent']);
        exit;
    }

    $liked = false;
    $check = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $check->execute([$commentId, $userId]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?")->execute([$commentId, $userId]);
        $pdo->prepare("UPDATE messages SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$commentId]);
        $liked = false;
    } else {
        $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)")->execute([$commentId, $userId]);
        $pdo->prepare("UPDATE messages SET like_count = like_count + 1 WHERE id = ?")->execute([$commentId]);
        $liked = true;
    }

    $stmt = $pdo->prepare("SELECT like_count FROM messages WHERE id = ?");
    $stmt->execute([$commentId]);
    $likes = (int)$stmt->fetchColumn();

    echo json_encode(['success' => true, 'liked' => $liked, 'likes' => $likes]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

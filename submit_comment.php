<?php
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_config.php';

function ensureCommentSchema(PDO $pdo)
{
    $hasParent = $pdo->query("SHOW COLUMNS FROM site_comments.messages LIKE 'parent_id'")->fetch();
    if (!$hasParent) {
        $pdo->exec("ALTER TABLE site_comments.messages ADD COLUMN parent_id INT DEFAULT NULL");
    }

    $hasLikeCount = $pdo->query("SHOW COLUMNS FROM site_comments.messages LIKE 'like_count'")->fetch();
    if (!$hasLikeCount) {
        $pdo->exec("ALTER TABLE site_comments.messages ADD COLUMN like_count INT NOT NULL DEFAULT 0");
    }
}

    ensureCommentSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home.html'));
        exit;
    }
    $name     = trim($_POST['name']    ?? '');
    $message  = trim($_POST['message'] ?? '');
    $page     = trim($_POST['page']    ?? '');
    $parentId = isset($_POST['parent_id']) && ctype_digit(strval($_POST['parent_id']))
                ? (int)$_POST['parent_id']
                : null;
    $userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if ($userId && !empty($_SESSION['username'])) {
        $name = $_SESSION['username'];
    }

    if (!$name || !$message || !$page) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($parentId) {
        $check = $pdo->prepare("SELECT id FROM site_comments.messages WHERE id = ? AND page = ?");
        $check->execute([$parentId, $page]);
        if (!$check->fetch()) {
            $parentId = null;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO site_comments.messages (name, message, page, user_id, parent_id, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $message, $page, $userId, $parentId]);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

<?php
// Serve user avatar: prefer filesystem file in uploads/avatars/, fallback to DB blob, otherwise default image.
// Usage: avatar.php?user_id=123

require_once __DIR__ . '/db_config.php';

$uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($uid <= 0) {
    header('Location: Images/default_avatar.svg');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT profile_pic, profile_blob, profile_blob_mime FROM site_users WHERE id = ? LIMIT 1');
    $stmt->execute([$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header('Location: Images/default_avatar.svg');
        exit;
    }

    if (!empty($row['profile_pic'])) {
        $path = __DIR__ . '/uploads/avatars/' . $row['profile_pic'];
        if (is_file($path) && is_readable($path)) {
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($path);
            exit;
        }
    }

    if (!empty($row['profile_blob'])) {
        $mime = !empty($row['profile_blob_mime']) ? $row['profile_blob_mime'] : 'image/png';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        echo $row['profile_blob'];
        exit;
    }
} catch (PDOException $e) {
    // fall through to default
}

header('Location: Images/default_avatar.svg');
exit;

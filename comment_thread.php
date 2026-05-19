<?php
require_once __DIR__ . '/db_config.php';
session_start();
header('Content-Type: text/html; charset=utf-8');

$currentUserId   = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['username'] ?? '';
$currentRole     = $_SESSION['role']    ?? 'user';

$commentId = isset($_GET['id']) && ctype_digit(strval($_GET['id'])) ? (int)$_GET['id'] : 0;
$page      = trim($_GET['page'] ?? '');

if (!$commentId) {
    http_response_code(400);
    echo 'Comentariu invalid.';
    exit;
}

function ensureCommentSchema(PDO $pdo)
{
    $hasParent = $pdo->query("SHOW COLUMNS FROM messages LIKE 'parent_id'")->fetch();
    if (!$hasParent) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN parent_id INT DEFAULT NULL");
    }

    $hasLikeCount = $pdo->query("SHOW COLUMNS FROM messages LIKE 'like_count'")->fetch();
    if (!$hasLikeCount) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN like_count INT NOT NULL DEFAULT 0");
    }

    $hasLikesTable = $pdo->query("SHOW TABLES FROM site_comments LIKE 'comment_likes'")->fetch();
    if (!$hasLikesTable) {
        $pdo->exec(
            "CREATE TABLE site_comments.comment_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                comment_id INT NOT NULL,
                user_id INT NOT NULL,
                liked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY comment_user (comment_id, user_id),
                INDEX idx_comment_id (comment_id),
                INDEX idx_user_id (user_id),
                FOREIGN KEY (comment_id) REFERENCES messages(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

function sortComments(array &$comments, string $sort)
{
    usort($comments, function ($a, $b) use ($sort) {
        if ($sort === 'date_asc') {
            return strtotime($a['submitted_at']) <=> strtotime($b['submitted_at']);
        }
        if ($sort === 'likes_desc') {
            $diff = (int)$b['like_count'] - (int)$a['like_count'];
            return $diff !== 0 ? $diff : (strtotime($b['submitted_at']) <=> strtotime($a['submitted_at']));
        }
        return strtotime($b['submitted_at']) <=> strtotime($a['submitted_at']);
    });
}

function countReplies(int $id, array $children): int
{
    if (empty($children[$id])) {
        return 0;
    }

    $count = 0;
    foreach ($children[$id] as $child) {
        $count += 1 + countReplies((int)$child['id'], $children);
    }
    return $count;
}

function renderCommentRow(array $row, array $children, int $depth = 0, bool $showAllReplies = true)
{
    global $currentUserId, $currentUserName, $currentRole;

    $id       = (int)$row['id'];
    $name     = htmlspecialchars($row['name']);
    $message  = htmlspecialchars($row['message']);
    $msgHtml  = nl2br($message);
    $time     = date('d M Y, H:i', strtotime($row['submitted_at']));
    $edited   = $row['edited_at'] ? ' <span class="edited-tag">(editat)</span>' : '';
    $avatar   = !empty($row['profile_pic'])
                ? 'uploads/avatars/' . htmlspecialchars($row['profile_pic'])
                : 'Images/default_avatar.svg';
    $likeCount = (int)$row['like_count'];
    $liked     = (int)$row['liked_by_user'] === 1;
    $likeClass = $liked ? ' liked' : '';

    $editBtn   = '';
    $deleteBtn = '';
    if ($currentUserId && $currentUserId == $row['user_id']) {
        $editBtn = '<button class="comment-edit-btn" data-id="'.$id.'">✏️ Editează</button>';
    }
    if ($currentRole === 'admin') {
        $deleteBtn = '<button class="comment-delete-btn" data-id="'.$id.'">🗑 Șterge</button>';
    }

    $replyCount = countReplies($id, $children);
    $toggleBtn = $replyCount > 0 ? '<button class="comment-toggle-btn" data-id="'.$id.'">Ascunde</button>' : '';

    echo '<div class="comment-item'.($depth > 0 ? ' comment-reply-item' : '').'" id="comment-'.$id.'">'
       . '<img src="'.$avatar.'" class="comment-avatar" width="44" height="44" onerror="this.src=\'Images/default_avatar.svg\'">'
       . '<div class="comment-body">'
       . '<div class="comment-meta"><strong>'.$name.'</strong> <span>— '.$time.$edited.'</span> '.$toggleBtn.'</div>'
       . '<div class="comment-text" id="text-'.$id.'">'.$msgHtml.'</div>'
       . '<div class="comment-edit-form" id="editform-'.$id.'" style="display:none;">'
       . '<textarea class="edit-textarea" id="edittextarea-'.$id.'">'.$message.'</textarea>'
       . '<button class="comment-save-btn" data-id="'.$id.'">💾 Salvează</button>'
       . '<button class="comment-cancel-btn" data-id="'.$id.'">✕ Anulează</button>'
       . '</div>'
       . '<div class="comment-actions">'
       . '<button class="comment-like-btn'.$likeClass.'" data-id="'.$id.'" data-liked="'.($liked ? '1' : '0').'">'
       . '❤️ <span class="like-count">'.$likeCount.'</span>'
       . '</button>'
       . '<button class="comment-reply-btn" data-id="'.$id.'">Răspunde</button>'
       . $editBtn
       . $deleteBtn
       . '</div>'
       . '<div class="comment-reply-form" id="replyform-'.$id.'" style="display:none;">'
       . '<textarea class="reply-textarea" data-parent="'.$id.'" placeholder="Scrie un răspuns..."></textarea>';

    if ($currentUserId) {
        echo '<input type="hidden" class="reply-name-input" value="'.htmlspecialchars($currentUserName).'">';
    } else {
        echo '<input type="text" class="reply-name-input" placeholder="Nume" required>';
    }

    echo '<div class="reply-form-actions">'
       . '<button class="comment-reply-submit-btn" data-id="'.$id.'">Trimite</button>'
       . '<button class="comment-reply-cancel-btn" data-id="'.$id.'">Anulează</button>'
       . '</div>'
       . '</div>';

    if (!empty($children[$id])) {
        echo '<div class="comment-replies">';
        foreach ($children[$id] as $reply) {
            renderCommentRow($reply, $children, $depth + 1, $showAllReplies);
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

try {
    ensureCommentSchema($pdo);

    if (!$page) {
        $pageStmt = $pdo->prepare('SELECT page FROM messages WHERE id = ?');
        $pageStmt->execute([$commentId]);
        $page = $pageStmt->fetchColumn() ?: 'unknown';
    }

    $stmt = $pdo->prepare(
        "SELECT m.id, m.name, m.message, m.page, m.submitted_at, m.user_id, m.edited_at,
                m.parent_id, COALESCE(m.like_count, 0) AS like_count, u.profile_pic,
                IF(cl.user_id IS NULL, 0, 1) AS liked_by_user
         FROM messages m
         LEFT JOIN site_users u ON m.user_id = u.id
         LEFT JOIN site_comments.comment_likes cl ON cl.comment_id = m.id AND cl.user_id = ?
         WHERE m.page = ?"
    );
    $stmt->execute([$currentUserId, $page]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        throw new Exception('Comentariile nu au putut fi găsite.');
    }

    $target = null;
    $children = [];
    foreach ($rows as $row) {
        if ((int)$row['id'] === $commentId) {
            $target = $row;
        }
        if (!empty($row['parent_id'])) {
            $children[(int)$row['parent_id']][] = $row;
        }
    }

    if (!$target) {
        throw new Exception('Comentariul nu a fost găsit.');
    }

    foreach ($children as &$group) {
        usort($group, function ($a, $b) {
            return strtotime($a['submitted_at']) <=> strtotime($b['submitted_at']);
        });
    }
    unset($group);

    $pageTitle = htmlspecialchars($page);

} catch (Exception $e) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Thread comentariu</title>'
       . '<link rel="stylesheet" href="style.css"></head><body>'
       . '<div style="padding:24px;font-family:Verdana,sans-serif;color:#333;">Eroare: '.htmlspecialchars($e->getMessage()).'</div>'
       . '</body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Thread comentariu</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div style="max-width:940px;margin:30px auto;padding:0 16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;">
      <div>
        <h1 style="margin:0;font-size:24px;">Thread comentariu</h1>
        <p style="margin:6px 0 0;color:#555;font-size:14px;">Vizualizezi răspunsurile complete pentru un comentariu.</p>
      </div>
      <a href="#" onclick="history.back();return false;" style="padding:10px 16px;background:#982000;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">Înapoi</a>
    </div>

    <div class="comment-list" style="margin-top:0;">
      <?php renderCommentRow($target, $children, 0, true); ?>
    </div>
  </div>

  <script src="site.js"></script>
</body>
</html>

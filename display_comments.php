<?php

require_once __DIR__ . "/db_config.php";
session_start();

header('Content-Type: text/html; charset=utf-8');

$currentUserId   = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['username'] ?? '';
$currentRole     = $_SESSION['role']    ?? 'user';
$page            = isset($_GET['page']) ? $_GET['page'] : 'unknown';
$sort            = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$sort            = in_array($sort, ['date_desc', 'date_asc', 'likes_desc'], true)
                   ? $sort
                   : 'date_desc';

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

function renderCommentRow(array $row, array $children, int $depth = 0, bool $showAllReplies = false)
{
    global $currentUserId, $currentUserName, $currentRole;

    $id       = (int)$row['id'];
    $name     = htmlspecialchars($row['name']);
    $message  = htmlspecialchars($row['message']);
    $msgHtml  = nl2br($message);
    $time     = date('d M Y, H:i', strtotime($row['submitted_at']));
    $edited   = $row['edited_at'] ? ' <span class="edited-tag">(editat)</span>' : '';
    if (!empty($row['user_id'])) {
        $avatar = 'avatar.php?user_id=' . (int)$row['user_id'];
    } else {
        $avatar = 'Images/default_avatar.svg';
    }
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

    if ($replyCount > 0) {
        if ($showAllReplies || $depth < 3) {
            echo '<div class="comment-replies">';
            foreach ($children[$id] as $reply) {
                renderCommentRow($reply, $children, $depth + 1, $showAllReplies);
            }
            echo '</div>';

            if (!$showAllReplies && $depth === 3) {
                $futureCount = countReplies($id, $children);
                if ($futureCount > 0) {
                    $pageEscaped = rawurlencode($row['page']);
                    echo '<div class="comment-thread-summary">'
                       . '<a class="comment-thread-link" href="comment_thread.php?id='.$id.'&page='.$pageEscaped.'">'
                       . 'Vezi thread-ul complet (' . $futureCount . ' răspunsuri suplimentare) »'
                       . '</a>'
                       . '</div>';
                }
            }
        } else {
            $futureCount = countReplies($id, $children);
            $pageEscaped = rawurlencode($row['page']);
            echo '<div class="comment-thread-summary">'
               . '<a class="comment-thread-link" href="comment_thread.php?id='.$id.'&page='.$pageEscaped.'">'
               . 'Vezi thread-ul complet (' . $futureCount . ' răspunsuri) »'
               . '</a>'
               . '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
}

try {
    ensureCommentSchema($pdo);

        $stmt = $pdo->prepare(
        "SELECT m.id, m.name, m.message, m.page, m.submitted_at, m.user_id, m.edited_at,
                m.parent_id, COALESCE(m.like_count, 0) AS like_count, u.profile_pic,
                IF(cl.user_id IS NULL, 0, 1) AS liked_by_user
            FROM messages m
            LEFT JOIN site_users u ON m.user_id = u.id
            LEFT JOIN comment_likes cl ON cl.comment_id = m.id AND cl.user_id = ?
         WHERE m.page = ?"
    );
    $stmt->execute([$currentUserId, $page]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<div class="comment-controls" data-page="'.htmlspecialchars($page).'">'
       . '<label>Sortare: '
       . '<select id="commentSortOrder">'
       . '<option value="date_desc"'.($sort === 'date_desc' ? ' selected' : '').'>Data (nouă → veche)</option>'
       . '<option value="date_asc"'.($sort === 'date_asc' ? ' selected' : '').'>Data (veche → nouă)</option>'
       . '<option value="likes_desc"'.($sort === 'likes_desc' ? ' selected' : '').'>Like-uri</option>'
       . '</select>'
       . '</label>'
       . '</div>';

    if (empty($rows)) {
        echo '<p style="color:#888;text-align:center;font-family:Verdana;font-size:14px;margin-top:14px;">'
           . 'Niciun comentariu încă. Fii primul!'
           . '</p>';
        return;
    }

    $topLevel = [];
    $children = [];
    foreach ($rows as $row) {
        if (!empty($row['parent_id'])) {
            $children[(int)$row['parent_id']][] = $row;
        } else {
            $topLevel[] = $row;
        }
    }

    sortComments($topLevel, $sort);
    foreach ($children as &$group) {
        usort($group, function ($a, $b) {
            return strtotime($a['submitted_at']) <=> strtotime($b['submitted_at']);
        });
    }
    unset($group);

    echo '<div class="comment-list">';
    foreach ($topLevel as $comment) {
        renderCommentRow($comment, $children, 0, false);
    }
    echo '</div>';

} catch (PDOException $e) {
    echo '<p style="color:red;font-family:Verdana;font-size:13px;">Eroare DB: '
         . htmlspecialchars($e->getMessage()) . '</p>';
}

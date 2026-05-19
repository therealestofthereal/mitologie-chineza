<?php
require_once __DIR__ . '/db_config.php';
try {

    $page = isset($_GET['page']) ? $_GET['page'] : 'unknown'; 

    $stmt = $pdo->prepare("SELECT name, message, submitted_at FROM site_comments.messages WHERE page = ? ORDER BY submitted_at DESC");
    $stmt->execute([$page]);

    echo '<div class="blog-comment">';
    echo '<ul class="comments">';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = htmlspecialchars($row['name']);
        $message = nl2br(htmlspecialchars($row['message']));
        $timestamp = date("d M Y, H:i", strtotime($row['submitted_at']));
        echo '<li>';
        echo '<div class="post-comments">';
        echo "<p class='meta'><strong>$name</strong> <span>— $timestamp</span></p>";
        echo "<p>$message</p>";
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
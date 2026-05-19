<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: home.html');
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=site_comments', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $stmt = $pdo->query(
        "SELECT m.id, m.name, m.message, m.page, m.submitted_at, u.username AS account_name, u.email " .
        "FROM messages m LEFT JOIN site_users.users u ON m.user_id = u.id " .
        "ORDER BY m.submitted_at DESC"
    );
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $comments = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<meta charset="UTF-8">
<head>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/x-icon" href="Images/favicon.ico">
  <title>Admin Comentarii</title>
  <style>
    .admin-page {
      max-width: 980px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 24px;
    }
    .admin-header h1 {
      margin: 0;
      font-family: 'Times New Roman', serif;
      color: #982000;
    }
    .admin-note {
      color: #555;
      font-family: Verdana, sans-serif;
    }
    .admin-list {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .admin-comment-card {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 14px;
      padding: 16px;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 16px;
      align-items: start;
    }
    .admin-comment-main {
      display: grid;
      gap: 8px;
    }
    .admin-comment-meta {
      font-size: 13px;
      color: #666;
      font-family: Verdana, sans-serif;
    }
    .admin-comment-message {
      font-size: 15px;
      color: #333;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .admin-comment-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      align-items: center;
    }
    .admin-delete-btn {
      padding: 10px 16px;
      border: none;
      border-radius: 10px;
      background: #fde8e8;
      color: #c0392b;
      cursor: pointer;
      transition: 0.2s;
      font-family: Verdana, sans-serif;
    }
    .admin-delete-btn:hover { background: #fcc8c8; }
    .admin-error {
      padding: 12px 14px;
      border-radius: 12px;
      background: #fdecea;
      border: 1px solid #e57373;
      color: #c0392b;
      margin-bottom: 16px;
      font-family: Verdana, sans-serif;
    }
  </style>
</head>
<body>
<nav class="site-nav">
  <div class="nav-inner">
    <a class="brand" href="home.html">Mitologia Chineză</a>
    <div class="nav-links">
      <a href="home.html">Home</a>
      <a href="contact.html">Contact</a>
      <div class="dropdown">
        <a href="#" class="dropbtn">Povești ▾</a>
        <div class="dropdown-content">
          <a href="SunWukong.html">Sun Wukong</a>
          <a href="Change.html">Chang'e &amp; Hou Yi</a>
          <a href="Dragonul.html">Dragonul</a>
          <a href="Zodiacul.html">Zodiacul Chinez</a>
          <a href="Galben.html">Împăratul Galben</a>
          <a href="XiWangMu.html">Xi Wang Mu</a>
          <a href="Guanyu.html">Guan Yu</a>
          <a href="Nuwa.html">Nuwa</a>
        </div>
      </div>
      <div class="dropdown">
        <a href="#" class="dropbtn">Altele ▾</a>
        <div class="dropdown-content">
          <a href="quiz.html">Quiz</a>
          <a href="resurse.html">Resurse ext.</a>
          <a href="search.html">Căutare</a>
          <a href="FAQ.html">FAQ</a>
        </div>
      </div>
      <a href="webografie.html">Webografie</a>
      <a href="login.php" id="nav-login-link">Login</a>
    </div>
  </div>
</nav>

<div class="admin-page">
  <div class="admin-header">
    <div>
      <h1>Panou Admin Comentarii</h1>
      <p class="admin-note">Șterge orice comentariu postat pe site. Numai conturile admin pot accesa această pagină.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="admin-error">Eroare DB: <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="admin-list">
    <?php if (empty($comments)): ?>
      <div class="admin-comment-card">
        <div class="admin-comment-main">
          <strong>Niciun comentariu de afișat.</strong>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($comments as $comment): ?>
        <div class="admin-comment-card" id="comment-<?= (int)$comment['id'] ?>">
          <div class="admin-comment-main">
            <div class="admin-comment-meta">
              <strong><?= htmlspecialchars($comment['name']) ?></strong>
              <?= !empty($comment['account_name']) ? '(@' . htmlspecialchars($comment['account_name']) . ')' : '' ?>
              <br>
              Pagina: <?= htmlspecialchars($comment['page']) ?> • <?= date('d M Y, H:i', strtotime($comment['submitted_at'])) ?>
            </div>
            <div class="admin-comment-message"><?= nl2br(htmlspecialchars($comment['message'])) ?></div>
          </div>
          <div class="admin-comment-actions">
            <button class="admin-delete-btn comment-delete-btn" data-id="<?= (int)$comment['id'] ?>">🗑 Șterge</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script src="script.js"></script>
<script src="site.js"></script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// fetch user info from the site users db
require_once __DIR__ . '/db_config.php';
$pdoU = $pdo;
$pdoC = $pdo;
$stmt = $pdoU->prepare("SELECT username, email, profile_pic, quiz_highscore, created_at FROM site_users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { session_destroy(); header('Location: login.php'); exit; }

// fetch recent comments from the site comments db
$stmtC = $pdoC->prepare(
  "SELECT page, message, submitted_at FROM messages WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 5"
);
$stmtC->execute([$_SESSION['user_id']]);
$recentComments = $stmtC->fetchAll(PDO::FETCH_ASSOC);

$avatar    = !empty($user['profile_pic'])
             ? 'uploads/avatars/' . htmlspecialchars($user['profile_pic'])
             : 'Images/default_avatar.svg';
$highscore = (int)$user['quiz_highscore'];
$joined    = date('d M Y', strtotime($user['created_at']));

$pageNames = [
    'sunwukong' => 'Sun Wukong',
    'change'    => 'Chang\'e & Hou Yi',
    'dragon'    => 'Dragonul',
    'zodiac'    => 'Zodiacul Chinez',
    'galben'    => 'Împăratul Galben',
];
?>
<!DOCTYPE html>
<html>
<meta charset="UTF-8">
<head>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/x-icon" href="Images/favicon.ico">
  <title>Profilul meu</title>
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

<main class="profile-wrapper">

  <?php if (isset($_GET['success'])): ?>
    <div class="profile-success">✅ Poza de profil a fost actualizată cu succes!</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="profile-error">⚠ <?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <div class="profile-header-card">
    <div class="profile-avatar-wrap">
      <img src="<?= $avatar ?>"
           class="profile-avatar"
           width="120"
           height="120"
           onerror="this.src='Images/default_avatar.svg'"
           id="profileAvatarImg"
           alt="Avatarul utilizatorului">
    </div>
    <div class="profile-info">
      <h2><?= htmlspecialchars($user['username']) ?></h2>
      <p><?= htmlspecialchars($user['email']) ?></p>
      <p style="margin-top:8px;font-size:14px;opacity:0.85;">Membru din <?= $joined ?></p>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <p style="margin-top:12px;">
          <span style="background:rgba(255,255,255,0.22);padding:6px 14px;border-radius:99px;font-size:12px;">
            🛡 Administrator
          </span>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <section class="profile-card">
    <h3>📷 Schimbă poza de profil</h3>
    <form class="avatar-upload-form" method="POST" action="upload_avatar.php" enctype="multipart/form-data">
      <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="previewAvatar(this)">
      <button type="submit" class="btn-upload">Salvează poza</button>
    </form>
    <p style="font-family:Verdana;font-size:13px;color:#666;margin-top:12px;line-height:1.5;">
      Tipuri acceptate: JPG, PNG, GIF, WEBP. Dimensiune maximă: 2 MB.
    </p>
  </section>

  <section class="profile-card">
    <h3>🏆 Highscore Quiz</h3>
    <?php if ($highscore === 0): ?>
      <p class="score-label">Niciun scor salvat încă.
        <a href="quiz.html" style="color:#982000;">Încearcă quiz-ul!</a>
      </p>
    <?php else: ?>
      <p class="score-label">Cel mai bun scor: <strong><?= $highscore ?>%</strong></p>
      <div class="score-bar-track">
        <div class="score-bar-fill" style="width:<?= $highscore ?>%"></div>
      </div>
      <p class="score-label" style="margin-top:10px;">
        <?php
        if ($highscore === 100)      echo '🔥 Perfect! Expert în mitologie!';
        elseif ($highscore >= 80)    echo '👏 Excelent!';
        elseif ($highscore >= 60)    echo '👍 Foarte bine!';
        elseif ($highscore >= 40)    echo '📖 Continuă să înveți!';
        else                         echo '💪 Mai încearcă!';
        ?>
      </p>
    <?php endif; ?>
  </section>

  <section class="profile-card">
    <h3>💬 Comentariile tale recente</h3>
    <?php if (empty($recentComments)): ?>
      <p style="font-family:Verdana;font-size:14px;color:#666;">Niciun comentariu postat încă.</p>
    <?php else: ?>
      <?php foreach ($recentComments as $c): ?>
        <div class="mini-comment">
          <div class="mini-meta">
            <?= htmlspecialchars($pageNames[$c['page']] ?? ucfirst($c['page'])) ?> · <?= date('d M Y', strtotime($c['submitted_at'])) ?>
          </div>
          <p style="margin:0;font-family:Verdana,sans-serif;font-size:14px;color:#333;line-height:1.6;">
            <?= htmlspecialchars(mb_substr($c['message'], 0, 120)) ?><?= strlen($c['message']) > 120 ? '…' : '' ?>
          </p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

</main>

<div class="sticky">
    <p style="padding:0%; font-family:'Times New Roman', Times, serif">Site făcut de</p>
</div>
<p style="background-color:#982000;margin:0%;padding:0%;text-align: center; color:white; font-family:'Times New Roman', Times, serif;">Ursulescu Florin-Alexandru, cl. XII-a A, Liceul Atanasie Marienescu</p>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('profileAvatarImg').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<script src="site.js"></script>
</body>
</html>

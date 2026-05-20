<?php
session_start();
require_once __DIR__ . '/csrf.php';
?>

<!DOCTYPE html>
<html>
<meta charset="UTF-8">
<head>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/x-icon" href="Images/favicon.ico">
  <title>Login – Mitologia Chineză</title>
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      background: radial-gradient(circle at top right, rgba(255,230,215,0.95), rgba(255,255,255,0.98) 40%),
                  linear-gradient(180deg, #f9f3ee 0%, #f0e2d6 100%);
      font-family: Verdana, sans-serif;
      color: #333;
    }

    .auth-wrapper {
      min-height: calc(100vh - 120px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px 20px;
    }

    .auth-box {
      width: 100%;
      max-width: 520px;
      background: rgba(255,255,255,0.92);
      border-radius: 24px;
      padding: 40px 32px;
      box-shadow: 0 20px 45px rgba(0,0,0,0.12);
      animation: fadeIn 0.35s ease;
      border: 1px solid rgba(152,32,0,0.12);
    }

    /* TABS */
    .tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 28px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .tab-btn {
      flex: 1 1 180px;
      padding: 14px 16px;
      background: #f9f0ea;
      border: 1px solid #e6d3c7;
      border-radius: 12px;
      font-size: 16px;
      font-family: Verdana, sans-serif;
      color: #666;
      cursor: pointer;
      transition: 0.25s;
      font-weight: 600;
    }

    .tab-btn.active {
      color: #982000;
      background: white;
      border-color: #982000;
      box-shadow: 0 8px 18px rgba(152,32,0,0.08);
    }

    .tab-btn.active {
      color: #982000;
      border-bottom-color: #982000;
      font-weight: bold;
    }

    /* FORMS */
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .form-group { margin-bottom: 16px; }

    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-family: Verdana, sans-serif;
      font-size: 14px;
      color: #555;
    }

    .form-control {
      width: 100%;
      box-sizing: border-box;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid #ddd;
      font-size: 15px;
      transition: 0.25s;
      outline: none;
      font-family: Verdana, sans-serif;
    }

    .form-control:focus {
      border-color: #982000;
      box-shadow: 0 0 0 2px rgba(152,32,0,0.15);
      background: #fffdf9;
    }

    .btn-submit {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #982000, #c0392b);
      color: white;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
      font-family: Verdana, sans-serif;
      margin-top: 6px;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .auth-title {
      margin: 0 0 10px;
      font-family: 'Times New Roman', serif;
      font-size: 32px;
      color: #982000;
      text-align: center;
    }

    .auth-subtitle {
      margin: 0 0 26px;
      text-align: center;
      color: #6b4b3d;
      font-size: 14px;
      line-height: 1.6;
    }

    .form-control::placeholder {
      color: #b28b7b;
    }

    .form-control {
      background: #fff;
    }

    .auth-box .tabs {
      margin-bottom: 24px;
    }

    /* ERROR */
    .error-msg {
      background: #fdecea;
      border: 1px solid #e57373;
      color: #c0392b;
      border-radius: 10px;
      padding: 10px 14px;
      margin-bottom: 18px;
      font-family: Verdana, sans-serif;
      font-size: 14px;
    }

    /* Already logged in */
    .logged-in-box {
      text-align: center;
      padding: 20px 0;
      font-family: Verdana, sans-serif;
    }

    .logged-in-box p { color: #555; }

    .btn-logout {
      display: inline-block;
      margin-top: 12px;
      padding: 10px 24px;
      background: #982000;
      color: white;
      border-radius: 10px;
      text-decoration: none;
      font-family: Verdana, sans-serif;
      transition: 0.2s;
    }

    .btn-logout:hover { background: #c0392b; color: white; }

    @keyframes fadeIn {
      from { opacity:0; transform: translateY(10px); }
      to   { opacity:1; transform: translateY(0); }
    }

    footer {
      background: linear-gradient(to bottom, #a0260f, #8b1f08);
      color: white;
      text-align: center;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      padding: 20px 20px;
      font-size: 14px;
      box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    footer p {
      margin: 0;
      color: white;
      opacity: 0.9;
      width: 100%;
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
    </div>
  </div>
</nav>

<div class="auth-wrapper">
  <div class="auth-box">
    <h1 class="auth-title">Conectează-te la cont</h1>
    <p class="auth-subtitle">Salvează avatarul, comentariile și scorul la quiz.</p>

    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Already logged in -->
      <div class="logged-in-box">
        <h2 style="color:#982000; font-family:'Times New Roman',serif;">
          Bun venit, <?= htmlspecialchars($_SESSION['username']) ?>!
        </h2>
        <p>Ești deja conectat.</p>
        <a href="home.html" style="display:inline-block;margin-top:8px;color:#982000;">← Înapoi la Home</a><br>
        <a class="btn-logout" href="logout.php">Deconectare</a>
      </div>

    <?php else: ?>
      <!-- Tabs -->
      <div class="tabs">
        <button class="tab-btn <?= (($_GET['tab'] ?? 'login') === 'login') ? 'active' : '' ?>"
                onclick="switchTab('login', event)">Autentificare</button>
        <button class="tab-btn <?= (($_GET['tab'] ?? '') === 'register') ? 'active' : '' ?>"
                onclick="switchTab('register', event)">Înregistrare</button>
      </div>

      <?php if (!empty($_GET['error'])): ?>
        <div class="error-msg">⚠ <?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <!-- LOGIN -->
      <div class="tab-content <?= (($_GET['tab'] ?? 'login') === 'login') ? 'active' : '' ?>" id="tab-login">
        <form method="POST" action="process_auth.php">
          <input type="hidden" name="action" value="login">

          <div class="form-group">
            <label>Username sau Email</label>
            <input type="text" name="username" class="form-control"
                   placeholder="username / email" required autofocus>
          </div>

          <div class="form-group">
            <label>Parolă</label>
            <input type="password" name="password" class="form-control"
                   placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-submit">Intră în cont</button>
        </form>
      </div>

      <!-- REGISTER -->
      <div class="tab-content <?= (($_GET['tab'] ?? '') === 'register') ? 'active' : '' ?>" id="tab-register">
        <form method="POST" action="process_auth.php">
          <input type="hidden" name="action" value="register">

          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control"
                   placeholder="alege un username" required>
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control"
                   placeholder="email@exemplu.com" required>
          </div>

          <div class="form-group">
            <label>Parolă <small style="color:#aaa">(minim 6 caractere)</small></label>
            <input type="password" name="password" class="form-control"
                   placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-submit">Creează cont</button>
        </form>
      </div>
    <?php endif; ?>

  </div>
</div>

<footer>
  <p>Ursulescu Florin-Alexandru, cl. XII-a A, Liceul Atanasie Marienescu</p>
</footer>

<div class="sticky">
    <p style="padding:0%; font-family:'Times New Roman', Times, serif">Site făcut de</p>
</div>

<script>
function switchTab(tab, event) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  if (event && event.target) {
    event.target.classList.add('active');
  }
}

const urlTab = new URLSearchParams(location.search).get('tab');
if (urlTab) switchTab(urlTab);
</script>
<script src="site.js"></script>

</body>
</html>

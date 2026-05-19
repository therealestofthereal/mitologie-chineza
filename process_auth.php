<?php
session_start();
require 'db_config.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = extract_request_csrf() ?? '';
    if (!validate_csrf_token($token)) {
        header('Location: login.php?error=Invalid+request');
        exit;
    }
}

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =       $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        header('Location: login.php?error=Completează+toate+câmpurile&tab=register');
        exit;
    }
    if (strlen($password) < 6) {
        header('Location: login.php?error=Parola+trebuie+să+aibă+minim+6+caractere&tab=register');
        exit;
    }

    // check dupes
    $stmt = $pdo->prepare("SELECT id FROM site_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        header('Location: login.php?error=Username+sau+email+deja+folosit&tab=register');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO site_users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);

    $_SESSION['user_id']     = $pdo->lastInsertId();
    $_SESSION['username']    = $username;
    $_SESSION['role']        = 'user';
    $_SESSION['profile_pic'] = '';
    header('Location: home.html');
    exit;

} elseif ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password =       $_POST['password'] ?? '';

    if (!$username || !$password) {
        header('Location: login.php?error=Completează+toate+câmpurile');
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT id, username, password, COALESCE(profile_pic, '') AS profile_pic, " .
        "COALESCE(role, 'user') AS role FROM site_users WHERE username = ? OR email = ?"
    );
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['role']         = $user['role'] ?? 'user';
        $_SESSION['profile_pic']  = $user['profile_pic'] ?? '';
        header('Location: home.html');
        exit;
    }

    header('Location: login.php?error=Username+sau+parolă+incorectă');
    exit;
}

header('Location: login.php');

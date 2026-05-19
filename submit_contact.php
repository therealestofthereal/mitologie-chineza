<?php
require_once __DIR__ . '/csrf.php';
session_start();

$token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($token)) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=contact_form;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        echo "All fields are required.";
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $message]);

    echo "Message sent successfully! Thank you for contacting us.";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
}

?>
<?php
$mysql_url = getenv('DATABASE_URL');
if (!$mysql_url) {
    die('DATABASE_URL environment variable not set');
}

// Parse mysql://user:password@host:port/database
$parsed = parse_url($mysql_url);
$host = $parsed['host'] ?? 'localhost';
$user = $parsed['user'] ?? 'root';
$password = $parsed['pass'] ?? '';
$database = ltrim($parsed['path'] ?? '', '/');
$port = $parsed['port'] ?? 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

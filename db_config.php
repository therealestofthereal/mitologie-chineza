<?php
$dsn = getenv('DATABASE_URL');
if (!$dsn) {
    die('DATABASE_URL environment variable not set');
}
$pdo = new PDO($dsn, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

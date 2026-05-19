<?php
$pdo = new PDO('mysql:host=localhost;dbname=site_users', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

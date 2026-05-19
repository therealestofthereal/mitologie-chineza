<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_config.php';

$response = [
    'loggedIn' => false,
];

if (empty($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

try {
    $query = "SELECT id, username, profile_pic, profile_blob, profile_blob_mime, role FROM site_users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode($response);
        exit;
    }

    $avatar = 'Images/default_avatar.svg';
    if (!empty($user['profile_blob'])) {
        $mime = !empty($user['profile_blob_mime']) ? $user['profile_blob_mime'] : 'image/png';
        $avatar = 'data:' . $mime . ';base64,' . base64_encode($user['profile_blob']);
    } elseif (!empty($user['profile_pic'])) {
        $avatar = 'avatar.php?user_id=' . (int)$user['id'];
    }

    $response = [
        'loggedIn' => true,
        'username' => $user['username'],
        'avatar'   => $avatar,
        'role'     => !empty($user['role']) ? $user['role'] : 'user',
    ];
} catch (PDOException $e) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, profile_pic, profile_blob, profile_blob_mime FROM site_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $avatar = 'Images/default_avatar.svg';
            if (!empty($user['profile_blob'])) {
                $mime = !empty($user['profile_blob_mime']) ? $user['profile_blob_mime'] : 'image/png';
                $avatar = 'data:' . $mime . ';base64,' . base64_encode($user['profile_blob']);
            } elseif (!empty($user['profile_pic'])) {
                $avatar = 'avatar.php?user_id=' . (int)$user['id'];
            }

            $response = [
                'loggedIn' => true,
                'username' => $user['username'],
                'avatar'   => $avatar,
                'role'     => 'user',
            ];
        }
    } catch (PDOException $ignored) {
        // leave fallback response here. this might be the best way to go about it, 
        // since if the database is down we don't want to expose that fact by showing an error message. instead, just return a generic "not logged in" response.
    }
}

echo json_encode($response);

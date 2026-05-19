<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$response = [
    'loggedIn' => false,
];

if (empty($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

try {
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $query = "SELECT username, profile_pic, role FROM site_users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode($response);
        exit;
    }

    $response = [
        'loggedIn' => true,
        'username' => $user['username'],
        'avatar'   => !empty($user['profile_pic'])
            ? 'uploads/avatars/' . $user['profile_pic']
            : 'Images/default_avatar.svg',
        'role'     => !empty($user['role']) ? $user['role'] : 'user',
    ];
} catch (PDOException $e) {
    try {
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $stmt = $pdo->prepare("SELECT username, profile_pic FROM site_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $response = [
                'loggedIn' => true,
                'username' => $user['username'],
                'avatar'   => !empty($user['profile_pic'])
                    ? 'uploads/avatars/' . $user['profile_pic']
                    : 'Images/default_avatar.svg',
                'role'     => 'user',
            ];
        }
    } catch (PDOException $ignored) {
        // leave fallback response here. this might be the best way to go about it, 
        // since if the database is down we don't want to expose that fact by showing an error message. instead, just return a generic "not logged in" response.
    }
}

echo json_encode($response);

<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/db_config.php';

$errors = [
    UPLOAD_ERR_INI_SIZE   => 'Fișierul depășește limita serverului.',
    UPLOAD_ERR_FORM_SIZE  => 'Fișierul este prea mare.',
    UPLOAD_ERR_NO_FILE    => 'Nu ai selectat niciun fișier.',
];

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg  = $errors[$code] ?? 'Eroare la upload.';
    header('Location: profile.php?error=' . urlencode($msg));
    exit;
}

$file     = $_FILES['avatar'];
$maxBytes = 2 * 1024 * 1024; // 2 MB
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// verify type
$realType = mime_content_type($file['tmp_name']);
if (!in_array($realType, $allowed)) {
    header('Location: profile.php?error=' . urlencode('Format nepermis. Folosește JPG, PNG, GIF sau WEBP.'));
    exit;
}

if ($file['size'] > $maxBytes) {
    header('Location: profile.php?error=' . urlencode('Imaginea depășește 2 MB.'));
    exit;
}

$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$ext      = $extMap[$realType];
$filename = $_SESSION['user_id'] . '.' . $ext;
$dir      = 'uploads/avatars/';
$dest     = $dir . $filename;

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// delete old avatars with different extensions to save space. 
// this also ensures that if the user uploads a new avatar with a different format, the old one will be removed and not left orphaned in the uploads folder.
foreach (['jpg','png','gif','webp'] as $e) {
    $old = $dir . $_SESSION['user_id'] . '.' . $e;
    if ($e !== $ext && file_exists($old)) {
        unlink($old);
    }
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    header('Location: profile.php?error=' . urlencode('Nu s-a putut salva fișierul.'));
    exit;
}

// resize if larger than 400x400. this was horrible to set up but it works fine now.
if (extension_loaded('gd')) {
    $maxDim = 400;
    $img = null;
    
    if ($realType === 'image/jpeg') {
        $img = @imagecreatefromjpeg($dest);
    } elseif ($realType === 'image/png') {
        $img = @imagecreatefrompng($dest);
    } elseif ($realType === 'image/gif') {
        $img = @imagecreatefromgif($dest);
    } elseif ($realType === 'image/webp') {
        $img = @imagecreatefromwebp($dest);
    }
    
    if ($img) {
        $width  = imagesx($img);
        $height = imagesy($img);
        
        if ($width > $maxDim || $height > $maxDim) {
            $ratio = $width / $height;
            if ($ratio > 1) {
                $newWidth  = $maxDim;
                $newHeight = (int)($maxDim / $ratio);
            } else {
                $newWidth  = (int)($maxDim * $ratio);
                $newHeight = $maxDim;
            }
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($realType === 'image/png') {
                imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            if ($realType === 'image/jpeg') {
                imagejpeg($resized, $dest, 90);
            } elseif ($realType === 'image/png') {
                imagepng($resized, $dest, 9);
            } elseif ($realType === 'image/gif') {
                imagegif($resized, $dest);
            } elseif ($realType === 'image/webp') {
                imagewebp($resized, $dest, 90);
            }
            
            imagedestroy($resized);
        }
        
        imagedestroy($img);
    }
}

// update db
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// attempt to read the saved file into a blob so we can fall back to DB storage
$blob = null;
if (file_exists($dest)) {
    $blob = file_get_contents($dest);
} elseif (is_readable($file['tmp_name'])) {
    $blob = file_get_contents($file['tmp_name']);
}

try {
    $stmt = $pdo->prepare("UPDATE site_users SET profile_pic = ?, profile_blob = ?, profile_blob_mime = ? WHERE id = ?");
    $stmt->execute([$filename, $blob, $realType, $_SESSION['user_id']]);
} catch (PDOException $e) {
    // If the DB doesn't have the new columns yet (migration not applied), fall back to updating only profile_pic
    $msg = $e->getMessage();
    if (stripos($msg, 'profile_blob') !== false || stripos($msg, 'Unknown column') !== false) {
        $stmt = $pdo->prepare("UPDATE site_users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$filename, $_SESSION['user_id']]);
    } else {
        throw $e;
    }
}

// keep session value for quick UI updates (clients use avatar.php which reads DB if needed)
$_SESSION['profile_pic'] = $filename;

header('Location: profile.php?success=1');
exit;

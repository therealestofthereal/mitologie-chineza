<?php
header('Content-Type: application/json; charset=utf-8');

$files = [];
foreach (scandir(__DIR__) as $file) {
    if (!is_file(__DIR__ . DIRECTORY_SEPARATOR . $file)) {
        continue;
    }

    if (!preg_match('/\.html$/i', $file)) {
        continue;
    }

    $lower = strtolower($file);
    if ($lower === 'search.html' || $lower === 'quiz.html' || $lower === 'about.html' || $lower === 'test.html' || $lower === 'news.html'  || $lower === 'admin_comments.php') {
        continue;
    }

    $files[] = $file;
}

sort($files, SORT_NATURAL | SORT_FLAG_CASE);
echo json_encode($files);

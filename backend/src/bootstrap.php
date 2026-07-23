<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

$dbFile = __DIR__ . '/../db/orders.sqlite';
$initSql = __DIR__ . '/../db/migrations/001_init.sql';

if (!file_exists($dbFile)) {
    // Initialize DB
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = file_get_contents($initSql);
    $pdo->exec($sql);
} else {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

/// change to sha256 instead of md5 for better collision resistance & add locking
function cache_get(string $key): ?string {
    $f = sys_get_temp_dir() . '/cache_' . hash('sha256', $key) . '.txt';
    // Check existence and expiration first
    if (!file_exists($f) || (time() - filemtime($f) >= 10)) {
        return null;
    }
    // Open file safely with a shared lock (allows multiple reads, blocks writes)
    $stream = fopen($f, 'r');
    if ($stream) {
        flock($stream, LOCK_SH);
        $content = stream_get_contents($stream);
        flock($stream, LOCK_UN);
        fclose($stream);
        return $content !== false ? $content : null;
    }
    return null;
}

function cache_set(string $key, string $value): void {
    $f = sys_get_temp_dir() . '/cache_' . hash('sha256', $key) . '.txt';
    // Open file safely with an exclusive lock (blocks all other reads and writes)
    $stream = fopen($f, 'c'); // 'c' opens file for writing without truncating yet
    if ($stream) {
        flock($stream, LOCK_EX);
        ftruncate($stream, 0); // Safely clear file size only after locking
        fwrite($stream, $value);
        fflush($stream); // Force flush to disk before releasing lock
        flock($stream, LOCK_UN);
        fclose($stream);
    }
}

$GLOBALS['pdo'] = $pdo;

<?php
declare(strict_types=1);

$pdo = $GLOBALS['pdo'];

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per    = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 20;

$start  = $_GET['start'] ?? null;
$end    = $_GET['end'] ?? null;

// --- SANE CACHE STRATEGY: Generational User-Scoped Invalidation ---
// Fetch current cache version for this user. If missing, defaults to 1.
$versionKey = "user_version:u{$userId}";
$userVersion = cache_get($versionKey) ?? "1";

// Embed the version directly into the key. 
// When the version changes, all prior paginated variants vanish instantly.
$cacheKey = "orders:u{$userId}:v{$userVersion}:p{$page}:per{$per}:s{$start}:e{$end}";

if ($cached = cache_get($cacheKey)) {
    echo $cached;
    return;
}

// Build base SQL (inefficient on purpose)
$sql = "SELECT id, user_id, total, created_at FROM orders WHERE user_id = :uid";
$params = [':uid' => $userId];

if ($start) {
    $sql .= " AND created_at >= :start";
    $params[':start'] = $start;
}
if ($end) {
    $sql .= " AND created_at <= :end";
    $params[':end'] = $end;
}

$offset = ($page - 1) * $per;
// stabling the pagination by adding the id and making it safe against sql injection by using parameterized bindings 
$sql .= " ORDER BY created_at ASC, id ASC LIMIT :limit OFFSET :offset"; 
$params[':limit'] = $per;
$params[':offset'] = $offset;


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// eager loading instead of N+1 query
if (!empty($rows)) {
    $orderIds = array_column($rows, 'id');

    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $payStmt = $pdo->prepare("SELECT order_id, method, status FROM payments WHERE order_id IN ($placeholders)");
    $payStmt->execute($orderIds);
    $payments = $payStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("SELECT order_id, COUNT(*) as c FROM order_items WHERE order_id IN ($placeholders) GROUP BY order_id");
    $countStmt->execute($orderIds);
    $counts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($rows as &$r) {
        $oid = $r['id'];

        $r['payment'] = $payments[$oid] ?? ['method' => null, 'status' => null];
        unset($r['payment']['order_id']); // Clean up relational identifier

        $r['items_count'] = isset($counts[$oid]) ? (int)$counts[$oid] : 0;
    }
    unset($r);
}
// Fake delay to exaggerate slowness
usleep(50000); // 50ms

$out = json_encode([
    'token_hint' => 'CAND-NT1',
    'page' => $page,
    'per_page' => $per,
    'count' => count($rows),
    'data' => $rows
], JSON_UNESCAPED_UNICODE);

cache_set($cacheKey, $out);
echo $out;

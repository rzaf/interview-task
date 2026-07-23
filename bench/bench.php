<?php
// Usage: php bench.php 50 > bench.csv
// Set env BENCH_TOKEN={{TOKEN}} to write header row with token.
$N = (int)($argv[1] ?? 20);

$sharedSeed = time();

$token = getenv('BENCH_TOKEN') ?: '{{TOKEN}}';
$out = fopen('php://stdout', 'w');
fputcsv($out, ['BEFORE: ','token',$token]);
fputcsv($out, ['i','ms']);

srand($sharedSeed);
for ($i=1; $i<=$N; $i++) {
    // floor(($i - 1) / 2) changes the random seed group every 2 iterations (0, 0, 1, 1, 2, 2...)
    srand($sharedSeed + floor(($i - 1) / 2));
    $userId = rand(1, 5);
    $page = rand(1, 50);
    $TARGET_URL_BEFORE = "http://127.0.0.1:8081/api/orders?user_id={$userId}&page=$page&per_page=20";


    $t0 = microtime(true);
    $ctx = stream_context_create(['http'=>['timeout'=>30]]);
    $body = @file_get_contents($TARGET_URL_BEFORE, false, $ctx);
    $dt = (microtime(true) - $t0) * 1000;
    fputcsv($out, [$i, round($dt,2)]);
    usleep(10000); // 10ms between requests
}

sleep(1);

srand($sharedSeed);

fputcsv($out, ['AFTER: ','token',$token]);
fputcsv($out, ['i','ms']);
for ($i=1; $i<=$N; $i++) {
    srand($sharedSeed + floor(($i - 1) / 2));
    $userId = rand(1, 5);
    $page = rand(1, 50);
    $TARGET_URL = "http://127.0.0.1:8080/api/orders?user_id={$userId}&page=$page&per_page=20";

    $t0 = microtime(true);
    $ctx = stream_context_create(['http'=>['timeout'=>30]]);
    $body = @file_get_contents($TARGET_URL, false, $ctx);
    $dt = (microtime(true) - $t0) * 1000;
    fputcsv($out, [$i, round($dt,2)]);
    usleep(10000); // 10ms between requests
}

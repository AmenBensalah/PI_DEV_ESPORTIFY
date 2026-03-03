<?php
require __DIR__ . '/../vendor/autoload.php';
$token = $argv[1] ?? '';
if ($token === '') { fwrite(STDERR, "token required\n"); exit(1); }
$file = __DIR__ . '/../var/cache/dev/profiler/' . substr($token, 4, 2) . '/' . substr($token, 2, 2) . '/' . $token;
if (!is_file($file)) { fwrite(STDERR, "profile not found: $file\n"); exit(1); }
$profile = unserialize(gzdecode(file_get_contents($file)));
$data = $profile['data'] ?? [];
$memory = $data['memory'] ?? null;
$doctor = $data['doctrine_doctor'] ?? null;
$time = $data['time'] ?? null;
if ($time) {
    echo 'duration_ms=' . $time->getDuration() . PHP_EOL;
    echo 'init_ms=' . $time->getInitTime() . PHP_EOL;
}
if ($memory) {
    echo 'memory_peak_bytes=' . $memory->getMemory() . PHP_EOL;
    echo 'memory_peak_mib=' . round($memory->getMemory() / 1024 / 1024, 2) . PHP_EOL;
}
if ($doctor) {
    echo 'doctor_issues=' . count($doctor->getIssues()) . PHP_EOL;
}

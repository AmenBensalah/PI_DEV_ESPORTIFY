<?php
require __DIR__ . '/../vendor/autoload.php';
$token = $argv[1] ?? '';
$file = __DIR__ . '/../var/cache/dev/profiler/' . substr($token,4,2) . '/' . substr($token,2,2) . '/' . $token;
$profile = unserialize(gzdecode(file_get_contents($file)));
$issues = $profile['data']['doctrine_doctor']->getIssues();
foreach ($issues as $issue) {
    $a = (array)$issue;
    $title = $a["\0*\0title"] ?? '';
    $type = $a["\0*\0type"] ?? '';
    if ($type === 'configuration') {
        $desc = $a["\0*\0description"] ?? '';
        echo "TITLE: $title\n";
        echo "DESC: " . preg_replace('/\s+/', ' ', (string)$desc) . "\n\n";
    }
}

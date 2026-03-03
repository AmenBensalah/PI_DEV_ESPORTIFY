<?php

require __DIR__ . '/../vendor/autoload.php';

$token = $argv[1] ?? '';
if ($token === '') {
    fwrite(STDERR, "Usage: php scripts/dump_doctrine_integrity_issue_fields.php <token>\n");
    exit(1);
}

$file = __DIR__ . '/../var/cache/dev/profiler/' . substr($token, 4, 2) . '/' . substr($token, 2, 2) . '/' . $token;
if (!is_file($file)) {
    fwrite(STDERR, "Profiler token file not found: $file\n");
    exit(1);
}

$profile = unserialize(gzdecode(file_get_contents($file)));
$issues = $profile['data']['doctrine_doctor']->getIssues();

foreach ($issues as $issue) {
    $raw = (array) $issue;
    $type = $raw["\0*\0type"] ?? 'unknown';
    if ($type !== 'integrity') {
        continue;
    }

    echo "----\n";
    foreach ($raw as $k => $v) {
        $key = str_replace("\0*\0", '', $k);
        if (is_scalar($v) || $v === null) {
            echo $key . ': ' . var_export($v, true) . "\n";
        } elseif (is_object($v)) {
            echo $key . ': object(' . get_class($v) . ")\n";
        } elseif (is_array($v)) {
            echo $key . ': array(' . count($v) . ")\n";
            foreach ($v as $vk => $vv) {
                if (is_scalar($vv) || $vv === null) {
                    echo '  - ' . $vk . ': ' . var_export($vv, true) . "\n";
                } elseif (is_object($vv)) {
                    echo '  - ' . $vk . ': object(' . get_class($vv) . ")\n";
                } elseif (is_array($vv)) {
                    echo '  - ' . $vk . ': array(' . count($vv) . ")\n";
                } else {
                    echo '  - ' . $vk . ': ' . gettype($vv) . "\n";
                }
            }
        } else {
            echo $key . ': ' . gettype($v) . "\n";
        }
    }
}

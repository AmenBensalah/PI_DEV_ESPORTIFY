<?php
require __DIR__ . '/../vendor/autoload.php';
$token = $argv[1] ?? '';
if ($token === '') { fwrite(STDERR, "token missing\n"); exit(1); }
$file = __DIR__ . '/../var/cache/dev/profiler/' . substr($token,4,2) . '/' . substr($token,2,2) . '/' . $token;
$profile = unserialize(gzdecode(file_get_contents($file)));
$issues = $profile['data']['doctrine_doctor']->getIssues();
$byType=[]; $byTitle=[];
foreach($issues as $issue){
  $a=(array)$issue;
  $type=$a["\0*\0type"] ?? 'unknown';
  $title=$a["\0*\0title"] ?? 'unknown';
  $severityObj=$a["\0*\0severity"] ?? null;
  $severity='unknown';
  if (is_object($severityObj) && method_exists($severityObj,'value')) { $severity=$severityObj->value; }
  elseif (is_object($severityObj) && method_exists($severityObj,'toString')) { $severity=$severityObj->toString(); }
  elseif (is_string($severityObj)) { $severity=$severityObj; }
  $byType[$type]=($byType[$type]??0)+1;
  $k=$type.' | '.$title.' | '.$severity;
  $byTitle[$k]=($byTitle[$k]??0)+1;
}
arsort($byType); arsort($byTitle);
echo "TOTAL=".count($issues).PHP_EOL;
echo "TYPES:".PHP_EOL;
foreach($byType as $k=>$v){ echo "$k: $v".PHP_EOL; }
echo "DETAIL:".PHP_EOL;
foreach($byTitle as $k=>$v){ echo "$v x $k".PHP_EOL; }

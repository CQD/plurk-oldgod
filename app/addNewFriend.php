<?php
$startTime = microtime(true);
include __DIR__ . '/_init.php';

$result = $qlurk->call('/APP/Alerts/addAllAsFriends');

syslog(LOG_DEBUG, json_encode($result));

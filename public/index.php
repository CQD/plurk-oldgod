<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';

$qlurk = new \Qlurk\ApiClient(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$map = [
    '/getNewPlurks' => 'GetNewPlurks',
    '/addAllAsFriend' => function() {
        global $qlurk;
        $result = $qlurk->call('/APP/Alerts/addAllAsFriends');
    }
];

$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$controller = $map[$path] ?? null;

if (is_callable($controller)) {
    $controller();
} elseif (class_exists($clazz = "\\Q\\OldGod\\{$controller}")) {
   (new $clazz())->run();
} else {
    http_response_code(404);
}

///////////////////////////////////////////////////////////////////

function qlog($level, $msg)
{
    if (defined('STDERR')) {
        fputs(STDERR, $msg . "\n");
    } else {
        syslog($level, $msg);
    }
}

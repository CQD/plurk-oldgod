<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';

$qlurk = new \Qlurk\ApiClient(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$map = [
    '/' => 'Home',
    '/getNewPlurks' => 'GetNewPlurks',
    '/addAllAsFriend' => function() {
        global $qlurk;
        $result = $qlurk->call('/APP/Alerts/addAllAsFriends');
    }
];

$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$path = explode('?', $path)[0];

$controller = $map[$path] ?? null;

if (is_callable($controller)) {
    $controller();
} elseif (class_exists($clazz = "\\Q\\OldGod\\{$controller}")) {
    $controller = new $clazz($qlurk);
    $controller->run();
} else {
    serve_static_file($path);
}

///////////////////////////////////////////////////////////////////

function serve_static_file($path)
{
    $file_path = __DIR__ . $path;
    if (0 === strpos($path, __DIR__ . '/d/')) {
        if (file_exists($file_path)) {
            echo file_get_contents($file_path);
        }
    }

    http_response_code(404);
    echo "404";
}

function qlog($level, $msg)
{
    if (defined('STDERR')) {
        fputs(STDERR, $msg . "\n");
    } else {
        syslog($level, $msg);
    }
}

<?php
$startTime = microtime(true);
include __DIR__ . '/_init.php';

if (!isset($_SERVER['HTTP_X_APPENGINE_QUEUENAME'])) {
    exit;
}

try {
    $result = array_merge(['result' => true], run());
} catch (\Exception $e) {
    $result = [
        'result' => false,
        'msg' => $e->getMessage(),
    ];
}

$result['time_used'] = microtime(true) - $startTime;
syslog(LOG_DEBUG, json_encode($result));
echo json_encode($result);

exit;
///////////////////////////////////////


function run ()
{
    $id = $_POST['id'];
    $contentRaw = $_POST['contentRaw'];

    if (0 !== strpos(strtolower($contentRaw), '@oldgod') && 0 !== strpos($contentRaw, '老神')) {
        return ['plurk_id' => $id, 'action' => null];
    }

    $action = 'ask';
//    if (false !== strpos($contentRaw, '籤')) {
//        $action = 'oracle';
//    } elseif (false !== strpos($contentRaw, '爻') || false !== strpos($contentRaw, '筊')) {
//        $action = 'zb';
//    }

    $reply = $action($contentRaw);

    $qlurk = $GLOBALS['qlurk'];
    $qlurk->call('/APP/Responses/responseAdd', ['plurk_id' => $id, 'content' => $reply, 'qualifier' => ':']);

    if (isset($resp['body']['error_text'])) {
        throw new \Exception("噗浪API噴錯誤：{$plurk['body']['error_text']}");
    }

    return ['plurk_id' => $id, 'action' => $action, 'reply' => $reply];
}

/////////////////////////////////////////////////////

function ask($contentRaw)
{
    $rslt = [
        "大吉", "大吉",
        "吉", "吉", "吉",
        "末小吉",
        "平",
        "兇", "兇",
        "大兇",
    ];

    $act = [
        "查生死簿",
        "觀天象", "觀天象", "觀天象", "觀天象",
        "卜一卦", "卜一卦", "卜一卦",
        "通靈感", "通靈感",
        "掐指一算", "掐指一算",
    ];

    return sprintf(
        "吾%s，以之為「%s」",
        $act[array_rand($act)],
        $rslt[array_rand($rslt)]
    );
}

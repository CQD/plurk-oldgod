<?php
use google\appengine\api\taskqueue\PushTask;

$startTime = microtime(true);
include __DIR__ . '/_init.php';

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
    $mc = new \Memcached;
    $latestPlurkTime = (int) $mc->get('latestPlurkTime');
    $oldLatestPlurkTime = $latestPlurkTime;

    $now = time() - 10 * 60;
    $latestPlurkTime = ($now - $latestPlurkTime) > 86400 ? $now : $latestPlurkTime;

    $qlurk = $GLOBALS['qlurk'];

    date_default_timezone_set('UTC');
    $offset = date("c", $latestPlurkTime);
    $plurks = $qlurk->call('/APP/Polling/getPlurks', ['offset' => $offset, 'minimal_data' => 1]);

    if (isset($plurk['body']['error_text'])) {
        throw new \Exception("噗浪API噴錯誤：{$plurk['body']['error_text']}");
    }
    $plurks = $plurks['body']['plurks'];

    $ids = [];
    if (count($plurks)) {
        $latestPlurkTime = strtotime(max(array_column($plurks, 'posted')));
        $mc->set('latestPlurkTime', $latestPlurkTime);

        $ids = array_column($plurks, 'plurk_id');
        $qlurk->call('/APP/Timeline/mutePlurks', ['ids' => json_encode($ids)]);

        foreach ($plurks as $plurk) {
            $contentRaw = trim($plurk['content_raw']);
            if (0 !== strpos($contentRaw, '老神') && 0 !== strpos(strtolower($contentRaw), '@oldgod')) {
                continue;
            }

            $task = new PushTask(
                '/processPlurk',
                ['id' => $plurk['plurk_id'], 'contentRaw' => $contentRaw]
            );
            $task->add('process-plurk');
        }
    }

    return [
        'plurks' => $ids,
        'oldLatestPlurkTime' => $oldLatestPlurkTime,
        'offset' => $offset,
        'latestPlurkTime' => date('Y-m-d H:i:s', $latestPlurkTime)
    ];
}

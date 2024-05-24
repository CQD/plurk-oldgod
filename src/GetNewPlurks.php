<?php

namespace Q\OldGod;

use Q\OldGod\OldGod;

class GetNewPlurks
{
    private $qlurk;
    private $interval;
    private $max_time;

    public function __construct($qlurk, $interval = 7.4, $max_time = 55)
    {
        $this->qlurk = $qlurk;
        $this->max_time = $max_time;
        $this->interval = $interval;
    }

    public function run($dryRun = false)
    {
        if (!$this->canRunCron()) {
            http_response_code(403);
            echo "You should not pass.\n";
            return;
        }

        $interval = $this->interval;
        $max_time = $this->max_time;

        $start_time = microtime(true);
        for ($offset = 0; $offset <= $max_time; $offset+=$interval) {
            $wakeTime = $start_time + $offset;
            $now = microtime(true);

            $sleepTime = 0;
            if ($now < $wakeTime) {
                $sleepTime = $wakeTime - $now;
                usleep((int) ($sleepTime * 1000000));
            }

            $execStartTime = microtime(true);
            $this->replyNewPlurks($dryRun);
            $this->replyOldPlurks($dryRun);
            $execEndTime = microtime(true);

            qlog(LOG_DEBUG, sprintf("offset: %.2f, sleepTime: %5.2f, execTime: %5.2f sec", $offset, $sleepTime, $execEndTime - $execStartTime));
        }
    }

    protected function canRunCron()
    {
        $headers = getallheaders() ?: [];
        if (isset($headers['X-Appengine-Cron'])) {
            return true;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = explode(':', $host)[0];
        if (in_array($host, ['localhost', '127.0.0.1'])) {
            return true;
        }

        return false;
    }

    protected function replyNewPlurks($dryRun = false)
    {
        $plurks = $this->qlurk->call('/APP/Timeline/getPlurks', ['minimal_data' => 0]);
        $plurks = $plurks['plurks'] ?? [];

        // 把沒有呼喚老神的噗通通消音
        // 然後把這些噗排除掉
        $mutedIds = $this->muteNonSummoningPlurks($plurks);
        $plurks = array_filter($plurks, function($p) use ($mutedIds){
            return !in_array($p['plurk_id'], $mutedIds);
        });

        // 已經回應過的不消音（這樣在噗裡面又呼叫老神的時候才看得到）
        // 但要把這些噗排除掉
        $plurks = array_filter($plurks, function($p) use ($mutedIds){
            return 1 !== (int) $p['responded'];
        });

        if ($dryRun) {
            $plurkIds = array_column($plurks, 'plurk_id');
            qlog(LOG_DEBUG, "dryRun，跳過這些新噗不回應: " . implode(", ", $plurkIds ?: ["沒有"]));
            return;
        }

        // 回應這些還沒回應過的請神噗
        foreach ($plurks as $p) {
            $this->respond($p['plurk_id'], $p['content_raw']);
        }

    }

    protected function replyOldPlurks($dryRun = false)
    {
        $plurks = $this->qlurk->call('/APP/Timeline/getUnreadPlurks', ['filter' => 'responded']);
        $plurks = $plurks['plurks'] ?? [];

        // 排除掉回應都讀過的
        $plurks = array_filter($plurks, function($p) {
            return $p['response_count'] > $p['responses_seen'];
        });

        // 沒有要回應噗的就不做後面的邏輯了
        if ($dryRun) {
            $plurkIds = array_column($plurks, 'plurk_id');
            qlog(LOG_DEBUG, "dryRun，跳過這些舊噗不回應: " . implode(", ", $plurkIds ?: ["沒有"]));
            return;
        }

        if (!$plurks) {
            return;
        }

        // 未讀的訊息有召喚老神的話，回應之
        foreach ($plurks as $p) {
            $plurkId = $p['plurk_id'];
            $r = $this->qlurk->call('/APP/Responses/get', ['plurk_id' => $plurkId, 'minimal_data' => true]);
            $this->qlurk->call('/APP/Timeline/markAsRead', ['ids' => json_encode([$plurkId]), 'note_position' => true]);
            qlog(LOG_DEBUG, "{$plurkId} 標已讀 ");

            $seenCnt = $p['responses_seen'];

            foreach($r['responses'] ?? [] as $idx => $response) {
                if ($idx < $seenCnt) {
                    continue;
                }

                $content = strtolower($response['content']);
                if(0 === strpos($content, '老神') || 0 === strpos($content, '@oldgod')){
                    $this->respond($response['plurk_id'], $content);
                }
            }
        }
    }

    /**
     * 把輸入的噗裡面沒有在呼叫老神的都消音。
     * 回傳被消音的 plurk id
     */
    protected function muteNonSummoningPlurks(array $plurks): array
    {
        // 有呼喊老神的不放進排除清單
        // 已經被消音但有呼叫老神的也應該被放進排除清單
        $plurksShouldMute = array_filter($plurks, function($p){
            $content = strtolower($p['content_raw']);
            if (2 === $p['is_unread']) return true;
            if (0 === strpos($content, '老神')) return false;
            if (0 === strpos($content, '@oldgod')) return false;
            return true;
        });
        $plurksIdsShouldMute = array_column($plurksShouldMute, 'plurk_id');

        // 排除已經消音過的
        $plurksToMute = array_filter($plurksShouldMute, function($p){
            return 2 !== (int) ($p['is_unread'] ?? 0);
        });
        $plurkIdsToMute = array_column($plurksToMute, 'plurk_id');

        if ($plurkIdsToMute) {
            qlog(LOG_DEBUG, "消音 " . json_encode($plurkIdsToMute));
            $this->qlurk->call('/APP/Timeline/mutePlurks', ['ids' => json_encode($plurkIdsToMute)]);
        }

        return array_values($plurksIdsShouldMute);
    }

    protected function respond(int $plurkId, string $msg)
    {
        $oldgod = new OldGod();

        $replies = $oldgod->ask($msg);
        $len = count($replies);
        qlog(LOG_DEBUG, "回覆 {$plurkId} {$len} 則訊息");
        foreach ($replies as $reply) {
            $rsp = $this->qlurk->call('/APP/Responses/responseAdd', ['plurk_id' => $plurkId, 'content' => $reply, 'qualifier' => ':']);
        }
    }
}

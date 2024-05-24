<?php

namespace Q\OldGod;

use Q\OldGod\OldGod;

class GetNewPlurks
{
    private $qlurk;

    public function __construct($qlurk)
    {
        $this->qlurk = $qlurk;
    }

    public function run($dryRun = false)
    {
        if (!$this->canRunCron()) {
            http_response_code(403);
            echo "You should not pass.\n";
            return;
        }

        $interval = 7;
        $max = 59;

        $start_time = microtime(true);
        for ($offset = 0; $offset <= $max; $offset+=$interval) {
            $wakeTime = $start_time + $offset;
            $now = microtime(true);

            if ($now < $wakeTime) {
                $sleepTime = ($wakeTime - $now) * 1000000;
                $sleepTime = (int) ($sleepTime);
                qlog(LOG_DEBUG, sprintf("sleep %5.2f sec", $sleepTime / 1000000));
                usleep($sleepTime);
            }

            $execStartTime = microtime(true);
            if (!$dryRun) {
                $this->replyNewPlurks();
                $this->replyOldPlurks();
            } else {
                usleep(500 * 1000); // 1 sec
                qlog(LOG_DEBUG, "dry run");
            }
            $execEndTime = microtime(true);

            qlog(LOG_DEBUG, sprintf("execTime: %5.2f sec", $execEndTime - $execStartTime));
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

    protected function replyNewPlurks()
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

        // 回應這些還沒回應過的請神噗
        foreach ($plurks as $p) {
            $this->respond($p['plurk_id'], $p['content_raw']);
        }

    }

    protected function replyOldPlurks()
    {
        $plurks = $this->qlurk->call('/APP/Timeline/getUnreadPlurks', ['filter' => 'responded']);
        $plurks = $plurks['plurks'] ?? [];

        // 排除掉回應都讀過的
        $plurks = array_filter($plurks, function($p) {
            return $p['response_count'] > $p['responses_seen'];
        });

        // 沒有要回應噗的就不做後面的邏輯了
        if (!$plurks) {
            return;
        }

        // 把噗標示為已讀
        // 先標已讀再回應是為了降低使用者連續回應的時候可能會有 race condition
        // 導致太快貼的回應不會被回到
        $ids = array_values(array_map(function($p){return $p['plurk_id'];}, $plurks));

        qlog(LOG_DEBUG, "標已讀 " . json_encode($ids));
        $this->qlurk->call('/APP/Timeline/markAsRead', ['ids' => json_encode($ids), 'note_position' => true]);

        // 未讀的訊息有召喚老神的話，回應之
        foreach ($plurks as $p) {
            $r = $this->qlurk->call('/APP/Responses/get', ['plurk_id' => $p['plurk_id'], 'minimal_data' => true]);

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

        if ($this->isSuspiciousMsg($msg)) {
            qlog(LOG_DEBUG, "{$plurkId} 的內容可疑，不回應，並解除好友");
            $plurk = $this->qlurk->call('/APP/Timeline/getPlurk', ['plurk_id' => $plurkId]);
            $user_id = $plurk['plurk']['user_id'] ?? 0;
            if ($user_id) {
                qlog(LOG_DEBUG, "解除好友: {$user_id}");
                $this->qlurk->call('/APP/FriendsFans/removeAsFriend', ['friend_id' => $user_id]);
            } else {
                $plurk_json = json_encode($plurk, JSON_UNESCAPED_UNICODE);
                qlog(LOG_DEBUG, "找不到 id {$plurkId} 的 user_id，噗文資料： {$plurk_json}");
            }
            return;
        }

        $replies = $oldgod->ask($msg);
        foreach ($replies as $reply) {
            qlog(LOG_DEBUG, "回覆 {$plurkId}");
            $this->qlurk->call('/APP/Responses/responseAdd', ['plurk_id' => $plurkId, 'content' => $reply, 'qualifier' => ':']);
        }
    }

    /**
     * 檢查是不是訊息是不是來自可疑的機器人
     */
    protected function isSuspiciousMsg(string $msg) : bool
    {
        $active_words = [
            "蛋糕獸",
            "開村",
            "人狼",
            "倖存者日記",
            "機器狼",
            "召喚貓貓",
        ];

        $threshold = 3;
        $count = 0;
        foreach ($active_words as $word) {
            if (false !== strpos($msg, $word)) {
                $count++;
            }
        }

        return $count >= $threshold;
    }
}

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
        $mutedIds = $this->muteNonSummoningPlurks($plurks, $dryRun);
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
            $this->respond($p['plurk_id'], $p['content_raw'], $dryRun);
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

        if (!$plurks) {
            qlog(LOG_DEBUG, "沒有未讀的訊息");
            return;
        }

        // 未讀的訊息有召喚老神的話，回應之
        foreach ($plurks as $p) {
            $plurkId = $p['plurk_id'];
            $plurk_owner_id = $p['user_id'] ?? -1;
            $first_content = $p['content_raw'] ?? $p['content'];

            $r = $this->qlurk->call('/APP/Responses/get', ['plurk_id' => $plurkId]);

            if (!$dryRun) {
                qlog(LOG_DEBUG, "標示 {$plurkId} 為已讀");
                $this->qlurk->call('/APP/Timeline/markAsRead', ['ids' => json_encode([$plurkId]), 'note_position' => true]);
            } else {
                qlog(LOG_DEBUG, "[dryRun] 標示 {$plurkId} 為已讀");
            }

            $seenCnt = $p['responses_seen'];

            qlog(LOG_DEBUG, "[開頭] 問者：{$first_content}");

            $history = ["問者：{$first_content}"];
            $friends = [];
            $OLDGOD_USER_ID = 9288960;
            foreach($r['responses'] ?? [] as $idx => $response) {
                $user_id = $response['user_id'] ?? -9;
                $content = $response['content_raw'] ?? $response['content'];
                $user = "路人";

                if ($user_id === $OLDGOD_USER_ID) {
                    $user = "老神";
                } elseif ($user_id === $plurk_owner_id) {
                    $user = "問者";
                } else {
                    if (!isset($friends[$user_id])) {
                        $friends[$user_id] = true;
                    }
                    $friend_idx = 1 + array_search($user_id, array_keys($friends));
                    $user = "友人{$friend_idx}";
                }

                qlog(LOG_DEBUG, "[回文] {$user}：{$content}");

                if ($idx < $seenCnt) {
                    $history[] = "{$user}：{$content}";
                    continue;
                }

                $content = strtolower($response['content_raw'] ?? $response['content']);
                if(0 === strpos($content, '老神') || 0 === strpos($content, '@oldgod')){
                    $this->respond($response['plurk_id'], $content, $dryRun, $history);
                }

                $history[] = "{$user}：{$content}";
            }
        }
    }

    /**
     * 把輸入的噗裡面沒有在呼叫老神的都消音。
     * 回傳被消音的 plurk id
     */
    protected function muteNonSummoningPlurks(array $plurks, bool $dryRun): array
    {
        // 有呼喊老神的不放進排除清單
        // 已經被消音但有呼叫老神的也應該被放進排除清單
        $plurksShouldMute = array_filter($plurks, function($p){
            $content = strtolower($p['content_raw']);
            if (2 === $p['is_unread']) return true;
            if ($this->isKarmago($content)) return true;
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
            if ($dryRun) {
                qlog(LOG_DEBUG, "[dryRun] 消音 " . json_encode($plurkIdsToMute));
            } else {
                qlog(LOG_DEBUG, "消音 " . json_encode($plurkIdsToMute));
                $this->qlurk->call('/APP/Timeline/mutePlurks', ['ids' => json_encode($plurkIdsToMute)]);
            }
        }

        return array_values($plurksIdsShouldMute);
    }

    protected function respond(int $plurkId, string $msg, bool $dryRun, array $history = [])
    {
        $oldgod = new OldGod();

        $replies = $oldgod->ask($msg, $history);
        $len = count($replies);

        if ($dryRun) {
            qlog(LOG_DEBUG, "[dryRun] 回覆 {$plurkId} {$len} 則訊息");
            foreach ($replies as $reply) {
                qlog(LOG_DEBUG, "[dryRun] - {$reply}");
            }
        } else {
            qlog(LOG_DEBUG, "回覆 {$plurkId} {$len} 則訊息");
            foreach ($replies as $reply) {
                $rsp = $this->qlurk->call('/APP/Responses/responseAdd', ['plurk_id' => $plurkId, 'content' => $reply, 'qualifier' => ':']);
            }
        }
    }

    protected function isKarmago(string $question): bool {
        $lowerQuestion = strtolower($question);
        $botKeywords = ["機器狼", "開村", "人狼", "召喚", "karmago", "蛋糕獸", "晚餐"];

        // 關鍵字出現太多次就當作是在騙卡馬，不參與
        $count = 0;
        foreach ($botKeywords as $keyword) {
            $count += substr_count($lowerQuestion, $keyword);
        }

        return $count >= 4;
    }
}

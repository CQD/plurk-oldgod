<?php

namespace Q\OldGod;

class GetNewPlurks
{
    public function run()
    {
        for ($i = 0; $i < 4; $i++) {
            $startTime = microtime(true);
            $this->replyNewPlurks();
            $this->replyOldPlurks();
            $endTime = microtime(true);

            $execTime = $endTime - $startTime;
            $sleepTime = max(0, (int) (15 - $execTime));
            qlog(LOG_DEBUG, sprintf("execTime: %5.3s, sleepTime: %s", $execTime, $sleepTime));

            if ($i < 3) {
                sleep($sleepTime);
            }
        }
    }

    protected function replyNewPlurks()
    {
        global $qlurk;

        $plurks = $qlurk->call('/APP/Timeline/getPlurks', ['minimal_data' => 0]);
        $plurks = $plurks['plurks'] ?? [];

        // 排除已經被消音的噗
        $plurks = array_filter($plurks, function($p){
            return 2 !== (int) ($p['is_unread'] ?? 0);
        });

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
        global $qlurk;

        $plurks = $qlurk->call('/APP/Timeline/getUnreadPlurks', ['filter' => 'responded']);
        $plurks = $plurks['plurks'] ?? [];

        // 排除已經被消音的噗
        $plurks = array_filter($plurks, function($p){
            return 2 !== (int) ($p['is_unread'] ?? 0);
        });

        // 消音並排除掉沒有招喚老神的噗
        $mutedIds = $this->muteNonSummoningPlurks($plurks);
        $plurks = array_filter($plurks, function($p) use ($mutedIds){
            return !in_array($p['plurk_id'], $mutedIds);
        });


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
        $ids = array_map(function($p){return $p['plurk_id'];}, $plurks);

        qlog(LOG_DEBUG, "標已讀 " . json_encode($ids));
        $qlurk->call('/APP/Timeline/markAsRead', ['ids' => json_encode($ids), 'note_position' => true]);

        // 未讀的訊息有召喚老神的話，回應之
        foreach ($plurks as $p) {
            $r = $qlurk->call('/APP/Responses/get', ['plurk_id' => $p['plurk_id'], 'minimal_data' => true]);

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
        global $qlurk;

        $plurksToMute = array_filter($plurks, function($p){
            $content = strtolower($p['content_raw']);
            return (0 !== strpos($content, '老神')) && (0 !== strpos($content, '@oldgod'));
        });

        $plurkIdsToMute = array_map(function($p){return $p['plurk_id'];}, $plurksToMute);

        if ($plurkIdsToMute) {
            qlog(LOG_DEBUG, "消音 " . json_encode($plurkIdsToMute));
            $qlurk->call('/APP/Timeline/mutePlurks', ['ids' => json_encode($plurkIdsToMute)]);
        }

        return $plurkIdsToMute;
    }

    protected function respond(int $plurkId, string $msg)
    {
        global $qlurk;

        $oldgod = new OldGod();

        $replies = $oldgod->ask($msg);
        foreach ($replies as $reply) {
            qlog(LOG_DEBUG, "回覆 {$plurkId}");
            $qlurk->call('/APP/Responses/responseAdd', ['plurk_id' => $plurkId, 'content' => $reply, 'qualifier' => ':']);
        }
    }
}

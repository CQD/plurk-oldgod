<?php

use PHPUnit\Framework\TestCase;
use Q\OldGod\GetNewPlurks;

/**
 * @testdox 老神的噗浪
 */
class GetNewPlurksTest extends TestCase
{
    /**
     * @testdox 跑一次資料更新流程
     */
    public function testRun()
    {
        $qlurk = new DummyQlurk();

        // CLI 下沒有 HTTP_HOST，設為 localhost 讓 canRunCron() 放行
        $_SERVER['HTTP_HOST'] = 'localhost';

        // max_time = 0：只跑一輪輪詢，不 sleep
        $a = new GetNewPlurks($qlurk, 7.4, 0);

        // 內容須含「吉凶」或「籤」keyword，跳過 LLM 意圖判斷（測試不呼叫 LLM）
        $qlurk->prepare(['plurks'=>[
            ['plurk_id' => 1, 'is_unread' => 0, 'responded' => 0, 'content_raw' => '嘿嘿我沒有叫你'],
            ['plurk_id' => 2, 'is_unread' => 1, 'responded' => 0, 'content_raw' => '老神 吉凶'],
            ['plurk_id' => 3, 'is_unread' => 0, 'responded' => 0, 'content_raw' => '老神 吉凶'],
        ]]); // getPlurks
        $qlurk->prepare([]); // mute #1
        $qlurk->prepare([]); // responseAdd #2 吉凶結果
        $qlurk->prepare([]); // responseAdd #2 批文
        $qlurk->prepare([]); // responseAdd #3 吉凶結果
        $qlurk->prepare([]); // responseAdd #3 批文
        $qlurk->prepare(['plurks'=>[
            ['plurk_id' => 4, 'is_unread' => 1, 'responded' => 0, 'response_count' => 3, 'responses_seen' => 1, 'content_raw' => '嘿嘿我沒有叫你'],
            ['plurk_id' => 5, 'is_unread' => 1, 'responded' => 1, 'response_count' => 1, 'responses_seen' => 1, 'content_raw' => '老神嘿嘿嘿'],
            ['plurk_id' => 6, 'is_unread' => 1, 'responded' => 1, 'response_count' => 3, 'responses_seen' => 1, 'content_raw' => '老神求籤'],
            ['plurk_id' => 7, 'is_unread' => 1, 'responded' => 0, 'response_count' => 3, 'responses_seen' => 0, 'content_raw' => '老神求籤'],
        ]]); // getUnreadPlurks
        $qlurk->prepare([]); // /APP/Responses/get 4
        $qlurk->prepare([]); // markAsRead #4
        $qlurk->prepare([]); // /APP/Responses/get 6
        $qlurk->prepare([]); // markAsRead #6
        $qlurk->prepare([]); // /APP/Responses/get 7
        $qlurk->prepare([]); // markAsRead #7
        $a->run();

        // 吉凶回覆為兩則訊息（吉凶結果 + 批文），測試環境 LLM 不可用時走 fallback 罐頭批文
        $this->assertSame('/APP/Timeline/getPlurks',       $qlurk->history[0]['endpoint']);
        $this->assertSame('/APP/Timeline/mutePlurks',      $qlurk->history[1]['endpoint']);
        $this->assertSame('[1]', $qlurk->history[1]['params']['ids']);
        $this->assertSame('/APP/Responses/responseAdd',    $qlurk->history[2]['endpoint']);
        $this->assertSame(2, $qlurk->history[2]['params']['plurk_id']);
        $this->assertSame('/APP/Responses/responseAdd',    $qlurk->history[3]['endpoint']);
        $this->assertSame(2, $qlurk->history[3]['params']['plurk_id']);
        $this->assertSame('/APP/Responses/responseAdd',    $qlurk->history[4]['endpoint']);
        $this->assertSame(3, $qlurk->history[4]['params']['plurk_id']);
        $this->assertSame('/APP/Responses/responseAdd',    $qlurk->history[5]['endpoint']);
        $this->assertSame(3, $qlurk->history[5]['params']['plurk_id']);
        $this->assertSame('/APP/Timeline/getUnreadPlurks', $qlurk->history[6]['endpoint']);
        $this->assertSame('/APP/Responses/get',            $qlurk->history[7]['endpoint']);
        $this->assertSame(4, $qlurk->history[7]['params']['plurk_id']);
        $this->assertSame('/APP/Timeline/markAsRead',      $qlurk->history[8]['endpoint']);
        $this->assertSame('[4]', $qlurk->history[8]['params']['ids']);
        $this->assertSame('/APP/Responses/get',            $qlurk->history[9]['endpoint']);
        $this->assertSame(6, $qlurk->history[9]['params']['plurk_id']);
        $this->assertSame('/APP/Timeline/markAsRead',      $qlurk->history[10]['endpoint']);
        $this->assertSame('[6]', $qlurk->history[10]['params']['ids']);
        $this->assertSame('/APP/Responses/get',            $qlurk->history[11]['endpoint']);
        $this->assertSame(7, $qlurk->history[11]['params']['plurk_id']);
        $this->assertSame('/APP/Timeline/markAsRead',      $qlurk->history[12]['endpoint']);
        $this->assertSame('[7]', $qlurk->history[12]['params']['ids']);
        // TODO 把回覆回應跟不回覆回應的 request 補上

//        echo json_encode($qlurk->history, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

    }
}

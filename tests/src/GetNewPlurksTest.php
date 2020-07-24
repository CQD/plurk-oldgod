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

        $a = new GetNewPlurks($qlurk);
        $a->interval = 0;

        $qlurk->prepare(['plurks'=>[
            ['plurk_id' => 1, 'is_unread' => 0, 'responded' => 0, 'content_raw' => '嘿嘿我沒有叫你'],
            ['plurk_id' => 2, 'is_unread' => 1, 'responded' => 0, 'content_raw' => '老神嘿嘿'],
            ['plurk_id' => 3, 'is_unread' => 0, 'responded' => 0, 'content_raw' => '老神嘿嘿'],
        ]]); // getPlurks
        $qlurk->prepare([]); // mute #1
        $qlurk->prepare([]); // responseAdd #2
        $qlurk->prepare([]); // responseAdd #3
        $qlurk->prepare(['plurks'=>[
            ['plurk_id' => 4, 'is_unread' => 1, 'responded' => 0, 'response_count' => 3, 'responses_seen' => 1, 'content_raw' => '嘿嘿我沒有叫你'],
            ['plurk_id' => 5, 'is_unread' => 1, 'responded' => 1, 'response_count' => 1, 'responses_seen' => 1, 'content_raw' => '老神嘿嘿嘿'],
            ['plurk_id' => 6, 'is_unread' => 1, 'responded' => 1, 'response_count' => 3, 'responses_seen' => 1, 'content_raw' => '老神求籤'],
            ['plurk_id' => 7, 'is_unread' => 1, 'responded' => 0, 'response_count' => 3, 'responses_seen' => 0, 'content_raw' => '老神求籤'],
        ]]); // getUnreadPlurks
        $qlurk->prepare([]); // mute #4
        $qlurk->prepare([]); // markAsRead #6,7
        $qlurk->prepare([]); // /APP/Responses/get 6
        $qlurk->prepare([]); // /APP/Responses/get 7
        $a->run();

        $this->assertSame('/APP/Timeline/getPlurks',       $qlurk->history[0]['endpoint']);
        $this->assertSame('/APP/Timeline/mutePlurks',      $qlurk->history[1]['endpoint']);
        $this->assertSame('[1]', $qlurk->history[1]['params']['ids']);
        $this->assertSame('/APP/Responses/responseAdd',    $qlurk->history[2]['endpoint']);
        $this->assertSame(2, $qlurk->history[2]['params']['plurk_id']);
        $this->assertSame('/APP/Responses/responseAdd',    $qlurk->history[3]['endpoint']);
        $this->assertSame(3, $qlurk->history[3]['params']['plurk_id']);
        $this->assertSame('/APP/Timeline/getUnreadPlurks', $qlurk->history[4]['endpoint']);
        $this->assertSame('/APP/Timeline/mutePlurks',      $qlurk->history[5]['endpoint']);
        $this->assertSame('[4]', $qlurk->history[5]['params']['ids']);
        $this->assertSame('/APP/Timeline/markAsRead',      $qlurk->history[6]['endpoint']);
        $this->assertSame('[6,7]', $qlurk->history[6]['params']['ids']);
        // TODO 把回覆回應跟不回覆回應的 request 補上

//        echo json_encode($qlurk->history, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

    }
}

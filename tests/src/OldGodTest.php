<?php

use PHPUnit\Framework\TestCase;
use Q\OldGod\OldGod;
use Q\OldGod\TarotData;

/**
 * @testdox 老神
 */
class OldGodTest extends TestCase
{
    /**
     * @testdox 問吉凶
     * @dataProvider lucknessProvider
     */
    public function testAskLuckness($question)
    {
        $oldGod = new OldGod();

        for ($i = 0; $i < 300; $i++) {
            $rslt = $oldGod->ask($question);

            $this->assertIsArray($rslt);
            $this->assertSame(1, count($rslt));
            $this->assertRegExp('/吾..+，以之為「[^」]*[吉兇平嘿香][^」]*」/', $rslt[0]);
        }
    }

    public function lucknessProvider()
    {
        return [
            ['問吉兇'],
            ['老神 幫我找妹子好嗎 吉凶'],
            ['老神 吉凶 我該怎麼辦？'],
        ];
    }

    /**
     * @testdox 求籤
     * @dataProvider oracleProvider
     */
    public function testAskOracle($question)
    {
        $oldGod = new OldGod();

        for ($i = 0; $i < 300; $i++) {
            $rslt = $oldGod->ask($question);

            $this->assertIsArray($rslt);
            $this->assertSame(2, count($rslt));
            $this->assertRegExp('/^第[一二三四五六七八九十百]+籤，.*/', $rslt[0]);
            $this->assertRegExp('/。/', $rslt[1]);
        }
    }

    public function oracleProvider()
    {
        return [
            ['請賜籤'],
            ['老神請給籤'],
            ['老神 幫我找妹子好嗎，求籤'],
            ['老神 來一支籤'],
        ];
    }

    /**
     * @testdox 塔羅抽牌
     */
    public function testTarotDraw()
    {
        $oldGod = new OldGod();
        $spreads = TarotData::$spreads;

        $hasUpright = false;
        $hasReversed = false;

        for ($i = 0; $i < 300; $i++) {
            $spreadId = array_rand($spreads);
            $spread = $spreads[$spreadId];
            $posCount = count($spread['positions']);

            [$spreadText, $llmText] = $oldGod->_tarot_draw($spreadId, 'full');

            // 牌面文字包含牌陣名
            $this->assertStringContainsString("【{$spread['name']}】", $spreadText);

            // 每個位置都有出現
            foreach ($spread['positions'] as $pos) {
                $this->assertStringContainsString("{$pos}：", $spreadText);
            }

            // 正逆位追蹤
            if (strpos($spreadText, '正位') !== false) {
                $hasUpright = true;
            }
            if (strpos($spreadText, '逆位') !== false) {
                $hasReversed = true;
            }

            // 抽牌不重複
            preg_match_all('/：(.+?)（[正逆]位）/u', $spreadText, $matches);
            $this->assertSame($posCount, count($matches[1]), "牌陣 {$spreadId} 應有 {$posCount} 張牌");
            $this->assertSame(count($matches[1]), count(array_unique($matches[1])), "牌陣 {$spreadId} 不應有重複牌");

            // LLM 文字包含關鍵詞（有 — 分隔）
            $this->assertStringContainsString('—', $llmText);
        }

        $this->assertTrue($hasUpright, '300 次應至少出現一次正位');
        $this->assertTrue($hasReversed, '300 次應至少出現一次逆位');
    }

    /**
     * @testdox 塔羅抽牌（僅大阿爾克那）
     */
    public function testTarotMajorOnly()
    {
        $oldGod = new OldGod();
        $majorNames = array_map(
            fn($c) => $c['name'],
            array_values(array_filter(TarotData::$cards, fn($c) => $c['arcana'] === 'major'))
        );

        for ($i = 0; $i < 300; $i++) {
            [$spreadText, $llmText] = $oldGod->_tarot_draw('three_card', 'major');

            preg_match_all('/：(.+?)（[正逆]位）/u', $spreadText, $matches);
            foreach ($matches[1] as $cardName) {
                $this->assertContains($cardName, $majorNames, "{$cardName} 不是大阿爾克那");
            }
        }
    }

    /**
     * @testdox 塔羅抽牌（無效牌陣 fallback）
     */
    public function testTarotInvalidSpreadFallback()
    {
        $oldGod = new OldGod();
        [$spreadText, $llmText] = $oldGod->_tarot_draw('nonexistent_spread', 'full');

        $this->assertStringContainsString('【三牌陣】', $spreadText);

        preg_match_all('/：(.+?)（[正逆]位）/u', $spreadText, $matches);
        $this->assertSame(3, count($matches[1]));
    }

}

<?php

use PHPUnit\Framework\TestCase;
use Q\OldGod\OldGod;

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

}

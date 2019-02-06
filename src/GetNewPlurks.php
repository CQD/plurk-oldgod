<?php

namespace Q\OldGod;

class GetNewPlurks
{
    public function run()
    {
        $this->replyNewPlurks();
        $this->replyOldPlurks();
        sleep(15);
        $this->replyNewPlurks();
        $this->replyOldPlurks();
        sleep(15);
        $this->replyNewPlurks();
        $this->replyOldPlurks();
        sleep(15);
        $this->replyNewPlurks();
        $this->replyOldPlurks();
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

        // 把噗標示為已讀
        // 先標已讀再回應是為了降低使用者連續回應的時候可能會有 race condition
        // 導致太快貼的回應不會被回到
        $ids = array_map(function($p){return $p['plurk_id'];}, $plurks);

        syslog(LOG_DEBUG, "標已讀 " . json_encode($ids));
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
            syslog(LOG_DEBUG, "消音 " . json_encode($plurkIdsToMute));
            $qlurk->call('/APP/Timeline/mutePlurks', ['ids' => json_encode($plurkIdsToMute)]);
        }

        return $plurkIdsToMute;
    }

    protected function respond(int $plurkId, string $msg)
    {
        global $qlurk;

        $action = '\Q\OldGod\ask';
        if (false !== strpos($msg, '籤')) {
            $action = 'Q\OldGod\oracle';
        }

        $replies = $action($msg);
        foreach ($replies as $reply) {
            syslog(LOG_DEBUG, "回覆 {$plurkId}");
            $qlurk->call('/APP/Responses/responseAdd', ['plurk_id' => $plurkId, 'content' => $reply, 'qualifier' => ':']);
        }
    }
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

    return [sprintf(
        "吾%s，以之為「%s」",
        $act[array_rand($act)],
        $rslt[array_rand($rslt)]
    )];
}

function oracle($contentRaw)
{
    $oracles = [
["第一籤，甲甲 大吉：\n巍巍獨步向雲間。玉殿千官第一班。富貴榮華天付汝。福如東海壽如山。", "功名遂。福祿全。訟得理。病即痊。桑麻熟。婚姻圓。孕生子。行人還。"],
["第二籤，甲乙 上吉：\n盈虛消息總天時。自此君當百事宜。若問前程歸縮地。更須方寸好修為。", "訟宜和。病宜禱。功名有。遲莫躁。求財平。問婚好。若妄為。身莫保。"],
["第三籤，甲丙 中吉：\n衣食自然生處有。勸君不用苦勞心。但能孝悌存忠信。福祿來成禍不侵。", "問名利。自有時。訟和吉。病瘥遲。求財平。婚未宜。宜謹守。免憂疑。"],
["第四籤，甲丁 下下：\n去年百事頗相宜。若較今年時運衰。好把瓣香告神佛。莫教福謝悔無追。", "功名無。財祿輕。訟宜息。婚未成。病難癒。行阻程。若求吉。禱神明。"],
["第五籤，甲戊 中平：\n子有三般不自由。門庭蕭索冷如秋。若逢牛鼠交承日。萬事回春不用憂。", "財祿耗。功名遲。訟終吉。病可醫。婚宜慎。行人歸。待時至。百事宜。"],
["第六籤，甲己 下下：\n何勞鼓瑟更吹笙。寸步如登萬里程。彼此懷疑不相信。休將私意憶濃情。", "功名無。財祿散。病難痊。防產難。婚不成。訟未判。行人遲。休嗟嘆。"],
["第七籤，甲庚 大吉：\n仙風道骨本天生。又遇仙宗為主盟。指日丹成謝巖谷。一朝引領向天行。", "圖名遂。求財豐。訟得理。病不凶。行人至。夢飛熊。陰地吉。婚姻全。"],
["第八籤，甲辛 上上：\n年來耕稼苦無收。今歲田疇定有秋。況遇太平無事日。士農工賈百無憂。", "士領薦。人獲財。訟即解。病無災。問行人。即便回。謀望吉。喜慶來。"],
["第九籤，甲壬 大吉：\n望渠消息向長安。常把菱花仔細看。見說文書將入境。今朝喜色上眉端。", "名與利。必至頭。訟必勝。病即瘳。孕生男。婚可求。行人至。百無憂。"],
["第十籤，甲癸 下下：\n病患時時命蹇衰。何須打瓦共鑽龜。直教重見一陽復。始可求神仗佛持。", "名難圖。財祿失。行人遲。訟未息。病留連。求神佑。莫貪求。宜守舊。"],
["第十一籤，乙甲 下下：\n今年好事一番新。富貴榮華萃汝身。誰道機關難料處。到頭獨立轉傷神。", "莫貪求。名未遂。財祿平。訟不利。病者凶。事不濟。問行人。多阻滯。"],
["第十二籤，乙乙 中平：\n營為期望在春前。誰料秋來又不然。直遇清江貴公子。一生活計始安全。", "求名遲。財未至。病改醫。訟最忌。行人歸。孕生貴。顯宦遇。方吉利。"],
["第十三籤，乙丙 中平：\n君今庚甲未亨通。且向江頭作釣翁。玉兔重生應發跡。萬人頭上逞英雄。", "時未亨。宜守舊。遇卯日。名利就。病遲癒。守終利。行人歸。事緩濟。"],
["第十四籤，乙丁 下下：\n一見佳人便喜歡。誰知去後有多般。人情冷暖君休訝。歷涉應知行路難。", "始雖易。終則難。名利失。病未安。訟反覆。孕生女。行未歸。事多阻。"],
["第十五籤，乙戊 中平：\n兩家門戶各相當。不是姻緣莫較量。直待春風好消息。卻調琴瑟向蘭房。", "婚未合。訟未決。名利遲。音信缺。孕難生。防口舌。非知己。莫妄說。"],
["第十六籤，乙己 下下：\n官事悠悠難辨明。不如息了且歸耕。傍人煽惑君休信。此事當謀親弟兄。", "訟難明。和為貴。名利無。行人至。婚未成。病有祟。財莫貪。終無利。"],
["第十七籤，乙庚 下下：\n田園價貫好商量。事到公庭彼此傷。縱使機關圖得勝。定為後世子孫殃。", "事悖理。訟必傷。名利未。亦如常。行人阻。病宜禳。且謹慎。保安康。"],
["第十八籤，乙辛 中平：\n知君指擬是空華。底事茫茫未有涯。牢把腳根踏實地。善為善應永無差。", "名利難。終則有。病禱神。訟勿鬥。行人遲。事難就。且向善。祈福佑。"],
["第十九籤，乙壬 上吉：\n嗟子從來未得時。今年星運頗相宜。營求動作都如意。和合婚姻誕貴兒。", "作事吉。名利遂。婚姻成。訟得理。孕生貴。行人至。病易安。皆吉利。"],
["第二十籤，乙癸 下下：\n一生心事向誰論。十八灘頭說與君。世事盡從流水去。功名富貴等浮雲。", "訟終凶。止則宜。名利輕。病擇醫。行人遠。婚遲疑。凡作事。且隨時。"],
["第二一籤，丙甲 下下：\n與君夙昔結成冤。今日相逢那得緣。好把經文多諷誦。祈求戶內保嬋娟。", "事無成。病禱愈。出不宜。訟有理。病有冤。求神解。若欲貪。終必敗。"],
["第二二籤，丙乙 上吉：\n碧玉池中開白蓮。莊嚴色相自天然。生來骨格超凡俗。正是人間第一仙。", "訟決勝。名易成。病即愈。財速盈。婚姻合。貴子生。家道泰。百事亨。"],
["第二三籤，丙丙 下下：\n花開花謝在春風。貴賤窮通百歲中。羨子榮華今已矣。到頭萬事總成空。", "名與利。似虛花。訟解散。病益加。婚未合。行人賒。事無就。徒咨嗟。"],
["第二四籤，丙丁 中平：\n一春萬事苦憂煎。夏裏營求始帖然。更遇秋成冬至後。恰如騎鶴與腰纏。", "名利有。莫躁為。詞訟解。婚姻宜。行人遠。病瘥遲。富與貴。自有時。"],
["第二五籤，丙戊 中平：\n寅午戍年多阻滯。亥子丑月漸亨嘉。更逢玉兔金雞會。枯木逢春自放花。", "訟多憂。終則息。名利遲。婚姻吉。孕欲保。作福力。問行人。歸有日。"],
["第二六籤，丙己 中吉：\n年來豐歉皆天數。自是今年旱較多。與子定期三日內。田疇霑足雨滂沱。", "名與利。今雖損。若遇時。便返本。訟可解。病得安。婚即合。行人還。"],
["第二七籤，丙庚 下下：\n世間萬物各有主。一粒一毫君莫取。英雄豪傑自天生。也須步步循規矩。", "訟莫興。病審醫。名與利。聽天推。婚未定。行人遲。凡作事。安分宜。"],
["第二八籤，丙辛 上吉：\n公侯將相本無種。好把勤勞契上天。人事盡從天理見。才高豈得困林泉。", "病與訟。久方解。名與利。姑少待。若失物。尋必在。雖漸困。終必泰。"],
["第二九籤，丙壬 上上：\n祖宗積德幾多年。源遠流長慶自然。若更操修無倦已。天須還汝舊青氈。", "天福善。產貴子。病者安。訟得理。名必得。仍得利。行人歸。皆如意。"],
["第三十籤，丙癸 中吉：\n奉公謹守莫欺心。自有亨通吉利臨。目下營求且休矣。秋期與子定佳音。", "利雖有。莫妄作。訟宜和。病勿藥。名待時。婚有約。但存心。安且樂。"],
["第三一籤，丁甲 中吉：\n秋冬作事只尋常。春到門庭漸吉昌。千里信音符遠望。萱堂快樂未渠央。", "訟漸理。病漸康。財始達。名始彰。行人近。婚姻良。家道吉。福祿昌。"],
["第三二籤，丁乙 下下：\n勞心汨汨竟何歸。疾病兼多是與非。事到頭來渾似夢。何如休要用心機。", "訟終凶。名未通。病者險。財亦空。婚未合。是非叢。行人阻。事無終。"],
["第三三籤，丁丙 中平：\n不分南北與西東。眼底昏昏耳似聾。熟讀黃庭經一卷。不論貴賤與窮通。", "訟莫爭。病難癒。名與利。莫貪取。問行人。信尚阻。能修善。有神助。"],
["第三四籤，丁丁 中平：\n春夏纔過秋又冬。紛紛謀慮攪心胸。貴人垂手來相援。休把私心情意濃。", "訟有憂。病未瘳。財祿散。忌遠謀。行人動。婚莫求。防口舌。免悶愁。"],
["第三五籤，丁戊 下下：\n一山如畫對清江。門裏團圓事事雙。誰料半途分折去。空幃無語對銀缸。", "訟終凶。宜謹防。病者險。主重喪。行人阻。財有傷。婚不合。謹行藏。"],
["第三六籤，丁己 上吉：\n功名富貴自能為。偶著仙鞭莫問伊。萬里鵬程君有分。吳山頂上好鑽龜。", "名與利。在晚成。訟得理。病漸亨。問遠信。阻行程。婚可合。孕將生。"],
["第三七籤，丁庚 中平：\n焚香來告復何辭。善惡平分汝自知。屏卻昧公心裏事。出門無礙是通時。", "訟和吉。病禱安。求財少。問名難。婚可合。行人遣。宜向善。保團圓。"],
["第三八籤，丁辛 下下：\n蛩吟唧唧守孤幃。千里懸懸望信歸。等得榮華公子到。秋冬括括雨靡靡。", "莫問財。休鬥訟。行未回。病亦重。婚無成。多怪夢。且禱神。勿妄動。"],
["第三九籤，丁壬 下下：\n北山門外好安居。若問終時慎厥初。堪笑包藏許多事。鱗鴻雖便莫修書。", "病擇醫。訟宜解。求財無。圖名旡。婚宜審。行須待。謹修為。過必改。"],
["第四十籤，丁癸 上吉：\n新來換得好規模。何用隨他步與趨。只聽耳邊消息到。崎嶇歷盡見亨衢。", "名終成。訟得勝。孕生男。保無病。行人回。婚宜定。事須遲。有餘慶。"],
["第四一籤，戊甲 中吉：\n自南自北自東西。欲到天涯誰作梯。遇鼠逢牛三弄笛。好將名姓榜頭題。", "訟無定。終有遇。病多憂。擇醫愈。信即到。婚終好。凡所謀。慎勿躁。"],
["第四二籤，戊乙 中吉：\n我曾許汝事和諧。誰料修為汝自乖。但改新圖莫依舊。營謀應得稱心懷。", "病更醫。訟改圖。名與利。換規模。婚別議。行人遲。謹修為。神力持。"],
["第四三籤，戊丙 中吉：\n一紙官書火急催。扁舟速下浪如雷。雖然目下多驚險。保汝平安去復回。", "功名遂。子嗣歡。訟病險。終必安。失物在。行人還。婚宜遠。利不難。"],
["第四四籤，戊丁 中平：\n汝是人中最吉人。誤為誤作損精神。堅牢一念酬香願。富貴榮華萃汝身。", "事多錯。訟莫作。病禱神。且勿藥。婚莫求。行未還。能自悔。利名全。"],
["第四五籤，戊戊 中平：\n好將心地力耕耘。彼此山頭總是墳。陰地不如心地好。修為到底卻輸君。", "心地好。地亦美。病即安。訟得理。財勿求。且守己。行人至。終有喜。"],
["第四六籤，戊己 中平：\n君是山中萬戶侯。信知騎馬勝騎牛。今朝馬上看山色。爭似騎牛得自由。", "名與利。皆不濟。訟莫興。多阻滯。行人遲。婚莫許。謹踐修。當靜處。"],
["第四七籤，戊庚 下下：\n與君萬語復千言。祗欲平和雪爾冤。訟則終凶君記取。試於清夜把心捫。", "訟莫興。恐遭刑。財莫貪。病未寧。行有阻。婚難成。且循理。保和平。"],
["第四八籤，戊辛 中平：\n登山涉水正天寒。兄弟姻親那得安。幸遇虎頭人一喚。全家遂保汝重歡。", "遇貴者。訟和平。病驚險。莫求名。財物耗。婚宜停。逢寅字。事漸亨。"],
["第四九籤，戊壬 下下：\n彼此家居只一山。如何似隔鬼門關。日月如梭人易老。許多勞碌不如閒。", "名利阻。且休問。訟宜和。病有願。婚姻遲。行人遠。欲獲吉。且安分。"],
["第五十籤，戊癸 上平：\n人說今年勝舊年。也須步多要周旋。一家和氣多生福。萋菲讒言莫聽偏。", "出入吉。財物多。孕可保。病即瘥。婚必成。訟宜和。問行人。奏凱歌。"],
["第五一籤，己甲 上吉：\n君今百事且隨緣。水到渠成聽自然。莫嘆年來不如意。喜逢新運稱心田。", "名漸顯。訟漸寬。財自裕。病自安。行有信。婚可定。命漸亨。心必稱。"],
["第五二籤，己乙 上吉：\n兀坐幽居歎寂寥。孤燈掩映度清宵。萬金忽報秋光好。活計扁舟渡北朝。", "名晚成。利遲得。病漸安。訟終息。孕無驚。婚姻吉。行人回。事勝昔。"],
["第五三籤，己丙 下下：\n艱難險阻路蹊蹺。南鳥孤飛依北巢。今日貴人曾識面。相逢卻在夏秋交。", "病與訟。皆不利。名與財。亦阻滯。孕有驚。行未至。若遇貴。事方濟。"],
["第五四籤，己丁 中平：\n萬人叢裏逞英豪。便欲飛騰霄漢高。爭奈承流風未便。青燈黃卷且勤勞。", "財未遂。名未超。訟不宜。病未消。婚難信。行路迢。待時至。百事饒。"],
["第五五籤，己戊 中平：\n勤耕力作莫蹉跎。衣食隨時安分過。縱使經商收倍利。不如逐歲廩禾多。", "休問名。莫貪財。訟宜解。病無災。婚可就。遠行回。戒出入。福自來。"],
["第五六籤，己己 下下：\n心頭理曲強詞遮。直欲欺官行路斜。一旦醜形臨月鏡。身投憲網莫咨嗟。", "莫興訟。勿求財。病有祟。行人回。婚須審。難信媒。行正直。免凶災。"],
["第五七籤，己庚 中平：\n事端百出慮雖長。莫聽人言自主張。一著仙機君記取。紛紛鬧裏更思量。", "訟急解。病早禳。信即至。財如常。孕生男。禱神康。凡百事。自主張。"],
["第五八籤，己辛 上吉：\n蘇秦三寸足平生。富貴功名在此行。更好修為陰騭事。前程萬里自通亨。", "病即安。訟決勝。行人回。婚宜定。孕生男。家道盛。積陰功。福來應。"],
["第五九籤，己壬 中平：\n門衰戶冷苦伶仃。自嘆祈求不一靈。幸有祖宗陰騭在。香煙未斷續螟蛉。", "名難保。財難圖。訟不利。病無虞。婚可合。信音無。行方便。守規模。"],
["第六十籤，己癸 上上：\n羨君兄弟好名聲。只管謙撝莫自矜。丹詔槐黃相逼近。巍巍科甲兩同登。", "宜出入。好謀望。訟即決。財亦旺。孕生男。病無恙。信音回。有神相。"],
["第六一籤，庚甲 中平：\n嘯聚山林兇惡儔。善良無事苦煎憂。主人大笑出門去。不用干戈盜賊休。", "財平平。病漸效。訟自散。莫與較。遠行歸。婚亦宜。雖有險。終平夷。"],
["第六二籤，庚乙 中平：\n百千人面虎狼心。賴汝干戈用力深。得勝回時秋漸老。虎頭城裏喜相尋。", "訟必勝。財必進。病有祟。遠有信。婚可成。名可稱。到秋來。百事順。"],
["第六三籤，庚丙 中平：\n曩時敗北且圖南。筋力雖衰尚一堪。欲識生前君大數。前三三與後三三。", "病可醫。訟中平。財尋常。信有準。名未亨。婚可聘。勿強圖。隨分定。"],
["第六四籤，庚丁 上上：\n吉人相遇本和同。況有持謀天水翁。人力不勞公論協。事成功倍笑談中。", "貴遇趙。訟即了。名能成。病可療。財有餘。婚亦好。問信音。即刻到。"],
["第六五籤，庚戊 上上：\n朔風凜凜正窮冬。多羨門庭喜氣濃。更入新春人事後。衷言方得信先容。", "財多得。名高中。可問婚。亦宜訟。病即安。行人動。禍變消。福力重。"],
["第六六籤，庚己 上上：\n耕耘只可在鄉邦。何用求謀向外方。見說今年新運好。門闌喜氣事雙雙。", "病即安。訟即決。財漸豐。名高揭。行人回。婚可結。莫外求。福儘得。"],
["第六七籤，庚庚 中平：\n纔發君心天已知。何須問我決狐疑。願子改圖從孝悌。不愁家室不相宜。", "訟和貴。病改醫。財別圖。婚姻遲。問行人。尚未歸。能改過。事皆宜。"],
["第六八籤，庚辛 中平：\n南販珍珠北販鹽。年來幾倍貨財添。勸君止此求田舍。心欲多時何日厭。", "訟已勝。莫再戰。名已成。毋再問。婚可定。病自散。行人歸。且安分。"],
["第六九籤，庚壬 下下：\n捨舟遵路總相宜。慎勿嬉遊逐貴兒。一夜樽前兄與弟。明朝仇敵又相隨。", "名與利。莫強求。醫宜審。婚難謀。行人至。訟可休。凡出入。謹交遊。"],
["第七十籤，庚癸 中平：\n雷雨風雲各有司。至誠禱告莫生疑。與君定約為霖日。正是蘊隆中伏時。", "訟與病。漸可解。名與利。姑少待。婚宜遲。行無信。若禱神。三日應。"],
["第七一籤，辛甲 中平：\n喜鵲簷前報好音。知君千里欲歸心。繡幃重結鴛鴦帶。葉落霜飛寒色侵。", "訟宜和。名漸通。婚再合。病主凶。問求財。時未同。凡謀望。在秋冬。"],
["第七二籤，辛乙 下下：\n河渠傍路有高低。可歎長途日已西。縱有榮華好時節。直須猴犬換金雞。", "求財遲。占病險。名難成。信尚遠。訟終凶。婚必晚。孕必驚。地未穩。"],
["第七三籤，辛丙 下下：\n憶昔蘭房分半釵。而今忽把信音乖。痴心指望成連理。到底誰知事不諧。", "名利無。訟休爭。婚事吹。孕多驚。病危險。命迍邅。宜作福。保安全。"],
["第七四籤，辛丁 上吉：\n崔巍崔魏復崔巍。履險如夷去復來。身似菩提心似鏡。長安一道放春回。", "訟與病。險而平。名與利。連而亨。婚先難。終必成。行人至。福自生。"],
["第七五籤，辛戊 中吉：\n生前結得好緣姻。一笑相逢情自親。相當人物無高下。得意休論富與貧。", "財物聚。病即愈。若問訟。必遇主。更修善。禱神助。行人回。事無阻。"],
["第七六籤，辛己 中平：\n三千法律八千文。此事何如說與君。善惡兩途君自作。一生禍福此中分。", "問公訟。且審理。求財祿。當揣己。病蚤禳。宜求嗣。婚更審。方吉利。"],
["第七七籤，辛庚 下下：\n木有根荄水有源。君當自此究其原。莫隨道路人閒話。訟到終凶是至言。", "訟當戒。病宜禳。名不利。婚難老。防口舌。行未到。凡事謹。莫輕躁。"],
["第七八籤，辛辛 下下：\n家道豊腴自飽溫。也須肚裏立乾坤。財多害己君當省。福有胚胎禍有門。", "莫貪財。能害己。休闕訟。當知止。病禱神。孕生子。婚擇良。行未至。"],
["第七九籤，辛壬 中平：\n乾亥來龍仔細看。坎居午向自當安。若移丑艮陰陽逆。門戶凋零家道難。", "名與利。依理求。婚與訟。莫妄謀。病擇醫。方無憂。行人至。慮且休。"],
["第八十籤，辛癸 中平：\n一朝無事忽遭官。也是門衰墳未安。改換陰陽移禍福。勸君莫作等閒看。", "名與利。宜改圖。訟和解。保無虞。病更醫。行漸回。婚別配。莫輕為。"],
["第八一籤，壬甲 中平：\n假君財物自當還。謀賴心欺他自奸。幸有高臺明月鏡。請來對照破機關。", "訟莫欺。依本分。病遇醫。名休問。婚慎求。事審辦。孕保安。行人鈍。"],
["第八二籤，壬乙 上吉：\n彼亦儔中一輩賢。勸君特達與周旋。此時賓主歡相會。他日王侯卻並肩。", "財必獲。名遇薦。訟得理。病有願。婚可成。行必見。發福祿。由積善。"],
["第八三籤，壬丙 下下：\n隨分堂前赴粥饘。何須妄想苦憂煎。主張門戶誠難事。百歲安閒得幾年。", "名與利。難遽致。訟宜和。行未至。病瘥遲。婚莫議。能守待。家必利。"],
["第八四籤，壬丁 中平：\n箇中事緒更紛然。當局須知一著先。長舌婦人休酷聽。力行禮義要心堅。", "訟宜解。莫信讒。財緩求。名莫貪。孕生女。行人還。婚更審。莫妄攀。"],
["第八五籤，壬戊 中平：\n一春風雨正瀟瀟。千里行人去路遙。移寡就多君得計。如何歸路轉無聊。", "且隨分。莫貪財。訟宜息。防外災。婚不利。遠行回。禱神助。福自來。"],
["第八六籤，壬己 上上：\n一舟行貨好招邀。積少成多自富饒。常把他人比自己。管須日後勝今朝。", "財祿富。訟得理。婚和合。病漸止。問行人。歸未矣。莫害人。人即己。"],
["第八七籤，壬庚 下下：\n陰裏詳看怪爾曹。舟中敵圖笑中刀。藩籬剖破渾無事。一種天生惜羽毛。", "名利無。病有祟。訟莫興。和為貴。莫貪財。婚不利。孕無憂。行未至。"],
["第八八籤，壬辛 上吉：\n從前作事總徒勞。纔見新春時漸遭。百計營求都得意。更須守己莫心高。", "名與利。且隨緣。訟解釋。病安痊。婚姻合。行人還。若謀望。在新年。"],
["第八九籤，壬壬 中平：\n樽前無事且高歌。時未來時奈若何。白馬渡江嘶日暮。虎頭城裏看巍峨。", "名未得。財尚遲。病漸愈。歸有期。訟可解。孕無危。婚和合。緩則宜。"],
["第九十籤，壬癸 中平：\n崆峒城裏事如麻。無事如君有幾家。勸汝不須勤致禱。徒勞生事苦咨嗟。", "訟和吉。求財無。婚未成。病無虞。信未至。勿他圖。不須禱。守規模。"],
["第九一籤，癸甲 中平：\n佛說淘沙始見金。只緣君子不勞心。榮華總得詩書效。妙裏工夫仔細尋。", "求名利。勤苦有。訟須勞。終無咎。問婚姻。宜擇友。探行人。二六九。"],
["第九二籤，癸乙 下下：\n今年禾穀不如前。物價喧騰倍百年。災數流行多疫癘。一陽復後始安全。", "訟紛紜。久自解。病患多。終無害。財祿難。有且待。孕屬陽。福終在。"],
["第九三籤，癸丙 中平：\n春來雨水太連綿。入夏晴乾雨又愆。節氣直交三伏始。喜逢滂沛足田園。", "財聚散。病反覆。欲求安。候三伏。事進退。宜作福。婚可成。審往復。"],
["第九四籤，癸丁 中平：\n一般器用與人同。巧斲輪輿梓匠工。凡事有緣且隨分。秋冬方遇主人翁。", "遇貴人。訟得理。財尚遲。病未愈。婚未成。信尚阻。待秋冬。方有遇。"],
["第九五籤，癸戊 中平：\n知君袖內有驪珠。生不逢辰亦強圖。可歎頭顱已如許。而今方得貴人扶。", "財發遲。訟終折。名晚成。婚未決。問遠信。有此月。遇貴人。災撲滅。"],
["第九六籤，癸己 上吉：\n婚姻子息莫嫌遲。但把精神仗佛持。四十年前須報應。功圓行滿育馨兒。", "名利訟。遲方吉。病漸瘥。婚姻結。終年後。得子息。問行人。未有日。"],
["第九七籤，癸庚 上上：\n五十功名心已灰。那知富貴逼人來。更行好事存方寸。壽比岡陵位鼎台。", "訟即解。名可成。財漸聚。病可寧。孕生子。婚姻平。行人至。事稱情。"],
["第九八籤，癸辛 中平：\n經營百出費精神。南北奔馳運未新。玉兔交時當得意。恰如枯木再逢春。", "名利有。晚方成。訟與病。久方平。孕生子。行阻程。遇卯運。事皆亨。"],
["第九九籤，癸壬 上上：\n貴人遭遇水雲鄉。冷淡交情滋味長。黃閣開時延故客。驊騮應得驟康莊。", "名與利。訟和事。家道康。皆吉利。病即安。孕生驥。婚則成。行人至。"],
["第一百籤，癸癸 上上：\n我本天仙雷雨師。吉凶禍福我先知。至誠禱祝皆靈應。抽得終籤百事宜。", "籤至百。數已終。我所知。象無凶。禱神扶。藉陰騭。危處安。損中益。"],
    ];
    return $oracles[array_rand($oracles)];
}

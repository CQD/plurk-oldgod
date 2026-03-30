<?php

namespace Q\OldGod;

class TarotData
{
    /** @var array<array{name: string, arcana: string, upright: string, reversed: string}> */
    public static array $cards = [
        ['name' => '愚者',   'arcana' => 'major', 'upright' => '新開始、冒險、自由、天真', 'reversed' => '魯莽、冒進、猶豫、不計後果'],
        ['name' => '魔術師', 'arcana' => 'major', 'upright' => '創造力、意志、技巧、自信', 'reversed' => '欺騙、操控、才能未發揮'],
        ['name' => '女祭司', 'arcana' => 'major', 'upright' => '直覺、潛意識、智慧、奧秘', 'reversed' => '隱瞞、膚淺、迷惑'],
        ['name' => '皇后',   'arcana' => 'major', 'upright' => '豐收、母性、自然、富足', 'reversed' => '依賴、空虛、過度溺愛'],
        ['name' => '皇帝',   'arcana' => 'major', 'upright' => '權威、穩定、領導、父性', 'reversed' => '專制、固執、控制欲'],
        ['name' => '教皇',   'arcana' => 'major', 'upright' => '傳統、信仰、教導、指引', 'reversed' => '盲從、教條、叛逆'],
        ['name' => '戀人',   'arcana' => 'major', 'upright' => '愛情、和諧、選擇、結合', 'reversed' => '失衡、價值觀衝突、誘惑'],
        ['name' => '戰車',   'arcana' => 'major', 'upright' => '意志力、勝利、決心、行動', 'reversed' => '失控、挫敗、衝動'],
        ['name' => '力量',   'arcana' => 'major', 'upright' => '勇氣、內在力量、耐心、慈悲', 'reversed' => '軟弱、自我懷疑、缺乏信心'],
        ['name' => '隱者',   'arcana' => 'major', 'upright' => '內省、獨處、智慧、探索', 'reversed' => '孤僻、逃避、固步自封'],
        ['name' => '命運之輪', 'arcana' => 'major', 'upright' => '轉機、命運、循環、好運', 'reversed' => '厄運、抗拒改變、失控'],
        ['name' => '正義',   'arcana' => 'major', 'upright' => '公正、因果、真相、法律', 'reversed' => '不公、逃避責任、偏見'],
        ['name' => '倒吊人', 'arcana' => 'major', 'upright' => '犧牲、等待、換個角度、放下', 'reversed' => '無謂犧牲、拖延、自私'],
        ['name' => '死神',   'arcana' => 'major', 'upright' => '結束、轉變、新生、放下過去', 'reversed' => '抗拒改變、停滯、恐懼'],
        ['name' => '節制',   'arcana' => 'major', 'upright' => '平衡、調和、耐心、中庸', 'reversed' => '失衡、過度、缺乏耐心'],
        ['name' => '惡魔',   'arcana' => 'major', 'upright' => '束縛、誘惑、執念、物慾', 'reversed' => '解脫、覺醒、掙脫束縛'],
        ['name' => '塔',     'arcana' => 'major', 'upright' => '突變、崩塌、真相、災難', 'reversed' => '逃避災禍、抗拒改變、苟延殘喘'],
        ['name' => '星星',   'arcana' => 'major', 'upright' => '希望、啟發、寧靜、信心', 'reversed' => '失望、悲觀、缺乏信心'],
        ['name' => '月亮',   'arcana' => 'major', 'upright' => '幻覺、不安、潛意識、恐懼', 'reversed' => '走出迷惘、克服恐懼、真相浮現'],
        ['name' => '太陽',   'arcana' => 'major', 'upright' => '喜悅、成功、活力、光明', 'reversed' => '短暫快樂、過度樂觀、延遲'],
        ['name' => '審判',   'arcana' => 'major', 'upright' => '覺醒、重生、召喚、反省', 'reversed' => '逃避審視、自我懷疑、拒絕改變'],
        ['name' => '世界',   'arcana' => 'major', 'upright' => '完成、圓滿、成就、旅程終點', 'reversed' => '未完成、缺乏結束、空虛'],

        // === 權杖 (Wands) ===
        ['name' => '權杖一',   'arcana' => 'minor', 'upright' => '靈感、新機會、潛力、創造', 'reversed' => '缺乏方向、猶豫、錯失良機'],
        ['name' => '權杖二',   'arcana' => 'minor', 'upright' => '計劃、決定、離開舒適圈', 'reversed' => '恐懼改變、猶豫不決、缺乏計劃'],
        ['name' => '權杖三',   'arcana' => 'minor', 'upright' => '遠見、擴展、海外機會', 'reversed' => '眼高手低、延遲、缺乏遠見'],
        ['name' => '權杖四',   'arcana' => 'minor', 'upright' => '慶祝、和諧、家庭幸福', 'reversed' => '不安定、過渡期、缺乏歸屬'],
        ['name' => '權杖五',   'arcana' => 'minor', 'upright' => '衝突、競爭、意見分歧', 'reversed' => '迴避衝突、內在矛盾、妥協'],
        ['name' => '權杖六',   'arcana' => 'minor', 'upright' => '勝利、榮耀、公眾認可', 'reversed' => '失敗、缺乏認可、驕傲'],
        ['name' => '權杖七',   'arcana' => 'minor', 'upright' => '堅持、防禦、勇氣、挑戰', 'reversed' => '力不從心、放棄、被壓倒'],
        ['name' => '權杖八',   'arcana' => 'minor', 'upright' => '迅速行動、變化、進展', 'reversed' => '延遲、混亂、抗拒改變'],
        ['name' => '權杖九',   'arcana' => 'minor', 'upright' => '堅韌、毅力、最後考驗', 'reversed' => '疲憊、不堪重負、放棄'],
        ['name' => '權杖十',   'arcana' => 'minor', 'upright' => '重擔、責任、壓力過大', 'reversed' => '放下、委派、崩潰前釋放'],
        ['name' => '權杖侍從', 'arcana' => 'minor', 'upright' => '熱情、探索、好消息', 'reversed' => '缺乏方向、急躁、壞消息'],
        ['name' => '權杖騎士', 'arcana' => 'minor', 'upright' => '冒險、熱情、衝勁', 'reversed' => '魯莽、急躁、虎頭蛇尾'],
        ['name' => '權杖皇后', 'arcana' => 'minor', 'upright' => '自信、獨立、溫暖、魅力', 'reversed' => '善妒、自私、暴躁'],
        ['name' => '權杖國王', 'arcana' => 'minor', 'upright' => '領袖、遠見、創業、魄力', 'reversed' => '專橫、衝動、期望過高'],

        // === 聖杯 (Cups) ===
        ['name' => '聖杯一',   'arcana' => 'minor', 'upright' => '新感情、直覺、喜悅', 'reversed' => '情感封閉、空虛、錯失感情'],
        ['name' => '聖杯二',   'arcana' => 'minor', 'upright' => '結合、相互吸引、夥伴', 'reversed' => '失衡、分離、溝通不良'],
        ['name' => '聖杯三',   'arcana' => 'minor', 'upright' => '慶祝、友誼、合作、歡聚', 'reversed' => '放縱、孤立、失去連結'],
        ['name' => '聖杯四',   'arcana' => 'minor', 'upright' => '冷漠、不滿、內省', 'reversed' => '覺醒、接受新機會、行動'],
        ['name' => '聖杯五',   'arcana' => 'minor', 'upright' => '失落、悲傷、後悔', 'reversed' => '釋懷、接受、走出悲傷'],
        ['name' => '聖杯六',   'arcana' => 'minor', 'upright' => '懷舊、回憶、純真', 'reversed' => '活在過去、不切實際、無法前進'],
        ['name' => '聖杯七',   'arcana' => 'minor', 'upright' => '幻想、選擇、白日夢', 'reversed' => '幻想破滅、做出選擇、回歸現實'],
        ['name' => '聖杯八',   'arcana' => 'minor', 'upright' => '離開、放棄、追求更高', 'reversed' => '恐懼改變、留戀、逃避'],
        ['name' => '聖杯九',   'arcana' => 'minor', 'upright' => '願望成真、滿足、幸福', 'reversed' => '不滿足、貪心、願望落空'],
        ['name' => '聖杯十',   'arcana' => 'minor', 'upright' => '圓滿、家庭幸福、情感豐盈', 'reversed' => '家庭不和、期望落差、破裂'],
        ['name' => '聖杯侍從', 'arcana' => 'minor', 'upright' => '直覺、創意、新感情訊息', 'reversed' => '情緒不穩、幼稚、逃避感情'],
        ['name' => '聖杯騎士', 'arcana' => 'minor', 'upright' => '浪漫、追求、邀約', 'reversed' => '不切實際、情緒化、善變'],
        ['name' => '聖杯皇后', 'arcana' => 'minor', 'upright' => '慈愛、敏感、同理心', 'reversed' => '情緒依賴、敏感過度、殉道'],
        ['name' => '聖杯國王', 'arcana' => 'minor', 'upright' => '情感成熟、慷慨、智慧', 'reversed' => '情感操控、冷漠、壓抑'],

        // === 寶劍 (Swords) ===
        ['name' => '寶劍一',   'arcana' => 'minor', 'upright' => '真相、清晰、突破', 'reversed' => '混亂、誤解、力量誤用'],
        ['name' => '寶劍二',   'arcana' => 'minor', 'upright' => '抉擇困難、僵局、逃避', 'reversed' => '猶豫不決、資訊過多、焦慮'],
        ['name' => '寶劍三',   'arcana' => 'minor', 'upright' => '心碎、悲痛、分離', 'reversed' => '復原、原諒、走出傷痛'],
        ['name' => '寶劍四',   'arcana' => 'minor', 'upright' => '休息、恢復、沉思', 'reversed' => '倦怠、焦躁、被迫行動'],
        ['name' => '寶劍五',   'arcana' => 'minor', 'upright' => '衝突、不正當手段、勝之不武', 'reversed' => '和解、認輸、放下爭鬥'],
        ['name' => '寶劍六',   'arcana' => 'minor', 'upright' => '轉變、離開困境、過渡', 'reversed' => '困在原地、未解問題、抗拒'],
        ['name' => '寶劍七',   'arcana' => 'minor', 'upright' => '策略、隱密行動、機智', 'reversed' => '良心不安、謊言被揭、自欺'],
        ['name' => '寶劍八',   'arcana' => 'minor', 'upright' => '受困、無力、自我設限', 'reversed' => '解脫、新視角、接受幫助'],
        ['name' => '寶劍九',   'arcana' => 'minor', 'upright' => '焦慮、噩夢、過度擔憂', 'reversed' => '釋放恐懼、光明將至、放下'],
        ['name' => '寶劍十',   'arcana' => 'minor', 'upright' => '結束、觸底、痛苦的了結', 'reversed' => '熬過最壞、重生、不願放手'],
        ['name' => '寶劍侍從', 'arcana' => 'minor', 'upright' => '好奇、新想法、真相', 'reversed' => '尖酸、冷酷、欺騙'],
        ['name' => '寶劍騎士', 'arcana' => 'minor', 'upright' => '果斷、直接、理性', 'reversed' => '衝動、無禮、不顧後果'],
        ['name' => '寶劍皇后', 'arcana' => 'minor', 'upright' => '獨立、直覺、公正清明', 'reversed' => '冷酷、報復、情緒壓抑'],
        ['name' => '寶劍國王', 'arcana' => 'minor', 'upright' => '權威、理性、公正', 'reversed' => '暴虐、濫權、冷血'],

        // === 錢幣 (Pentacles) ===
        ['name' => '錢幣一',   'arcana' => 'minor', 'upright' => '新財運、物質機會、豐盛', 'reversed' => '錯失良機、計劃落空、貪心'],
        ['name' => '錢幣二',   'arcana' => 'minor', 'upright' => '平衡、適應、靈活調度', 'reversed' => '失衡、力不從心、混亂'],
        ['name' => '錢幣三',   'arcana' => 'minor', 'upright' => '團隊合作、學習、精進', 'reversed' => '缺乏合作、品質低落、敷衍'],
        ['name' => '錢幣四',   'arcana' => 'minor', 'upright' => '守財、穩定、安全感', 'reversed' => '貪婪、過度吝嗇、執著物質'],
        ['name' => '錢幣五',   'arcana' => 'minor', 'upright' => '困難、貧困、不安', 'reversed' => '度過難關、尋求幫助、復甦'],
        ['name' => '錢幣六',   'arcana' => 'minor', 'upright' => '慷慨、施與受、分享', 'reversed' => '自私、債務、施捨的優越感'],
        ['name' => '錢幣七',   'arcana' => 'minor', 'upright' => '耐心等待、長期投資、收穫', 'reversed' => '急於求成、回報不如預期'],
        ['name' => '錢幣八',   'arcana' => 'minor', 'upright' => '勤勉、專注、精益求精', 'reversed' => '敷衍、缺乏熱情、重複勞動'],
        ['name' => '錢幣九',   'arcana' => 'minor', 'upright' => '獨立、豐足、自給自足', 'reversed' => '過度依賴物質、孤獨、揮霍'],
        ['name' => '錢幣十',   'arcana' => 'minor', 'upright' => '財富、家族、傳承、長久穩定', 'reversed' => '家產紛爭、財務危機、根基不穩'],
        ['name' => '錢幣侍從', 'arcana' => 'minor', 'upright' => '學習、新計劃、腳踏實地', 'reversed' => '缺乏進展、不切實際、懶散'],
        ['name' => '錢幣騎士', 'arcana' => 'minor', 'upright' => '勤奮、可靠、穩健前進', 'reversed' => '停滯、懶惰、過於保守'],
        ['name' => '錢幣皇后', 'arcana' => 'minor', 'upright' => '富足、務實、安穩、滋養', 'reversed' => '過度物質、忽略心靈、佔有慾'],
        ['name' => '錢幣國王', 'arcana' => 'minor', 'upright' => '財富、成功、踏實、慷慨', 'reversed' => '貪婪、守財奴、過度追求物質'],
    ];

    /** @var array<string, array{name: string, positions: array<string>}> */
    public static array $spreads = [
        'single' => [
            'name' => '單牌',
            'positions' => ['指引'],
        ],
        'three_card' => [
            'name' => '三牌陣',
            'positions' => ['過去', '現在', '未來'],
        ],
        'celtic_cross' => [
            'name' => '凱爾特十字',
            'positions' => ['現況', '挑戰', '潛意識', '過去', '頂牌', '未來', '自我', '環境', '希望與恐懼', '結果'],
        ],
        'choice' => [
            'name' => '二擇一',
            'positions' => ['核心', '選項一現況', '選項一發展', '選項一結果', '選項二現況', '選項二發展', '選項二結果'],
        ],
        'relationship' => [
            'name' => '關係牌陣',
            'positions' => ['自己', '對方', '關係現況', '挑戰', '建議', '發展'],
        ],
    ];
}

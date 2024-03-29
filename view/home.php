<!DOCTYPE html>
<html lang="zh-tw">
<head>
<meta charset="UTF-8">
<title>噗浪老神機器人網頁版</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<link rel="apple-touch-icon" sizes="180x180" href="/logo.png">
<link rel="icon" type="image/png" href="/logo.png">
<style>
:root{
    background-color: #D58A3B;
    color: rgba(255,255,255, 0.9);
    font-family: Arial, Helvetica, sans-serif;
    font-size:18px;
    margin:0;
    padding:0;
}

body{
    margin:0;
    padding:0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-areas: "main main"
                         "desc about";;
}

a {
    color:#513314;
    font-weight: bold;
}

#main{
    text-align: center;
    grid-area: main;
    min-height:90vh;
    padding-bottom: 60px;
    box-sizing: border-box;
}
h1{
    font-size:70px;
    margin:40px 0 30px;
}

#q, #answer{
    border-radius: 15px;
    border:none;
    display: block;
    width:90%;
    margin:1em auto 1em;
    box-sizing: border-box;
}

#q{
    font-size:30px;
    text-align: center;
    line-height: 150%;
    background-color: #D8D8D8;
}
#ask{
    color:#7D4F1D;
    background-color: #FFAB52;
    border-radius: 15px;
    border: none;
    font-size: 30px;
    padding:3px 1em;
}
#answer{
    display: none;
    font-size:30px;
    background-color: #8B5822;
    padding:0.5em 1em;
}
small{
    color:rgba(255,255,255,0.5)
}
hr{
    border-color:rgba(255,255,255,0.2)
}

#desc, #about{
    padding:2em 2em 3em;
}
#desc {
    grid-area: desc;
    background-color: #513314;
    color:rgba(255,255,255,0.8)
}
#about {
    grid-area: about;
    background-color:wheat;
    color:rgba(0,0,0,0.65)
}

@media only screen and (max-width: 576px) {
    body {
        display:block;
    }
    h1{
        font-size:50px;
    }
    #q,#ask,#ans{
        font-size:25px;
    }
}
</style>
</head>
<body>
<section id="main">
<h1>凡事問老神</h1>
<input id="q" placeholder="請輸入問題" type="text">
<button id="ask">老神老神請指引我</button>
<div id="answer"></div>
</section>

<section id="desc">
問吉兇：<br>
「老神 請問 ONS 的吉兇」<br>
「老神 問吉兇 打小孩」<br>
「老神 我要後宮」<br>
<br>
求籤：<br>
「老神我想打籃球，請賜籤」<br>
「老神 求籤 明天的天氣」<br>
「老神 抽籤問腳踏兩條船的運勢」<br>
</section>
<section id="about">
這是<a href="https://www.plurk.com">噗浪</a>上的<a href="https://www.plurk.com/OldGod">老神機器人</a>的網頁版，程式碼放在 <a href="https://github.com/CQD/plurk-oldgod">Github</a> 上。<br>
<br>
僅供娛樂，自己的人生自己負責。<br>
<br>
雖然 RNG 之中似乎確實住著神明。<br>
</section>

<script>
let q = document.getElementById('q')
let ask = document.getElementById('ask')
let answer = document.getElementById('answer')

let last_ask_time = 0
function ask_oldgod() {
    const now = new Date()
    const question = q.value
    if (!question || now - last_ask_time < 300) {
        return
    }
    fetch(`/?q=${question}`)
    .then(res => {
        return res.json()
    })
    .then(data => {
        const que = document.createElement('small')
        const ans = document.createElement('div')

        let text = data.ans
        if (Array.isArray(text)) {
            text = text.join("\r\n\r\n")
        }

        que.innerText = `問：${question}`
        ans.innerText = text
        if (answer.children.length > 0) {
            answer.prepend(document.createElement('hr'))
        }

        if (text.length > 30) {
            ans.style.fontSize = '80%'
        }
        console.log(text.length)

        answer.prepend(ans)
        answer.prepend(que)
        answer.style.display = "block"
    })

    last_ask_time = now
}

q.onchange = ask_oldgod
ask.addEventListener('click', (e) => { console.log(e);ask_oldgod()})
</script>
</body>
</html>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>DoInject Shell Console - LTRE LAB</title>
<script type="text/javascript" src="./res/lib/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="./res/lib/di.js"></script>
</head>
<body>

<audio id="di-bgm" src="http://data3.5sing.kgimg.com/T1Q3djB4_T1R47IVrK.mp3" autoplay loop></audio>

<!-- 注意：这段脚本必须放这里！否则会看到CSS延迟加载的情况 -->
<script type="text/javascript">
var nowWidth = document.body.offsetWidth;//当前整个屏幕宽度
var stWidth = 1920;//设计CSS时的可见宽度
var rate = (shell_css_matches=location.search.match(/rate=[0-9]+(.[0-9]{1,})?/)) ? shell_css_matches[0].replace('rate=', '') : '';//可手动指定rate参数来设定全局CSS，范围一般为50~100
rate = parseFloat(rate) || parseFloat(nowWidth) / parseFloat(stWidth) * 100;
var themeUrl = './?shell.shell-css&rate=' + rate;
(function addCssByLink(url){
    var doc = document;
    var link = doc.createElement("link");
    link.setAttribute("rel", "stylesheet");
    link.setAttribute("type", "text/css");
    link.setAttribute("href", url);
    var heads = doc.getElementsByTagName("head");
    if(heads.length)
        heads[0].appendChild(link);  
    else
        doc.documentElement.appendChild(link);  
}) (themeUrl);
</script>

<script type="text/javascript">
$('body').append('\
<!-- 显示器区域 1280*690 gap=20 -->\
<div id="screen" class="rdall">\
    <!-- 900*600 -->\
    <div id="content">\
    	<!-- 896*596 -->\
        <iframe id="iframepage" class="rdall" src="about:blank"></iframe>\
    </div>\
    <!-- 360*600 -->\
    <div id="overview">\
    	<!-- 340*270 -->\
    	<div id="shellarea" class="rdright"><span style="font-size:16px;font-family:微软雅黑;font-weight:bold;color:#888888;line-height:50px;text-align:center;">輸入(半角)：<br>testtest/test<br>試試！</span></div>\
        <div id="shellarea" class="rdright"><span style="font-size:15px;font-family:微软雅黑;font-weight:bold;color:#888888;line-height:50px;text-align:center;">点击显示屏支架<br>暂停BGM</span></div>\
    </div>\
    <!-- 1240*20 -->\
    <input id="command" class="rdall" type="text" />\
</div>\
<!-- 支架 -->\
<div id="bracket">\
	<div id="bracket1"></div>\
    <div id="bracket2"></div>\
    <div id="bracket3"></div>\
</div>\
<!-- 底座 -->\
<div id="pedestal"></div>\
');
</script>

<script type="text/javascript">
Di.init();
$(function(){
    /**
     * 屏幕配置
     */
	$('#command').keyup(function(){
		$('#content').css('background-image', '');
		var src = $(this).val();
		var chars = src.split('');
		for (var i in chars) {
			if ('?' === chars[i] || '/' === chars[i] || '&' == chars[i] || ':' == chars[i] || '.' == chars[i])
				continue;
			chars[i] = encodeURIComponent(chars[i]);
		}
		src = './?' + chars.join('');
		$('#iframepage').attr('src', src);
	});
	var focuz = function(){$('#command').focus()};
	$(':not(#command)')
	    .keyup(focuz)
	    .keydown(focuz);

	/**
	 * 首图配置
	 */
	var dibg = [
        {
            'id'    : 1,
            'code'  : '1111',
            'name'  : 'miku700x583',
            'url'   : 'http://tutu2.baidu.com/1000/similar/20141209/02/3002789478,3837190898.jpg',
        },
        {
            'id'    : 2,
            'code'  : '1111',
            'name'  : 'miku1000x833',
            'url'   : 'http://tutu2.baidu.com/1001/similar/20141209/02/2999621982,4037883632.jpg'
        }
    ];
    $('#content').css('background-image', 'url(' + dibg[1].url + ')');
	
    /**
     * BGM配置
     */
    var dibgm = [
        {
            'id'    : 1,
            'code'  : '111111111111111',
            'name'  : '妄想税',
            'artist': '月音真雪',
            'url'   : 'http://data10.5sing.kgimg.com/T1GLAeByDT1R47IVrK.mp3'
        },
        {
            'id'    : 2,
            'code'  : '222',
            'name'  : '桜、舞い散るあの丘へ',
            'artist': '初音ミク',
            'url'   : 'http://data9.5sing.kgimg.com/T1cBC7BCJT1R47IVrK.mp3'
        },
        {
            'id'    : 3,
            'code'  : '111111111111111',
            'name'  : '大和撫子、咲き誇れ',
            'artist': '初音ミク',
            'url'   : 'http://data10.5sing.kgimg.com/T14uA4BC_T1R47IVrK.mp3'
        },
        {
            'id'    : 4,
            'code'  : '111111111111111',
            'name'  : '永久に続く五线谱',
            'artist': '初音ミク',
            'url'   : 'http://data6.5sing.kgimg.com/T1Q5EmBCET1R47IVrK.mp3'
        },
        {
            'id'    : 5,
            'code'  : '111111111111111',
            'name'  : '紅一葉',
            'artist': '月音真雪 Ft.桜落',
            'url'   : 'http://data4.5sing.kgimg.com/T1.rKeBXVT1R47IVrK.mp3'
        }
    ];
    $('#di-bgm').attr('src', dibgm[Math.floor(Math.random()*5)].url);

    /**
     * 点击“支架”暂停音乐
     */
    $('#di-bgm')[0].pause();
    $('#bracket').click(function(){
        var b = $('#di-bgm')[0];
        if (b.paused) {
            b.play();
        } else {
            b.pause();
        }
    });
    
});
</script>

</body>
</html>
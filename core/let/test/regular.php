<?php
if (! strcasecmp($_SERVER['REQUEST_METHOD'], 'post')) {
	preg_match(arg('re'), arg('str'), $matches);
	
	putjson(0, htmlspecialchars(var_export($matches, true)));
}
?><html>
<head>
<style>
body,body *{
    font-size: 32px;
    font-family: 微软雅黑;
}
input{
    width: 960px;
}
#msg{
    width: 960px;
    background-color: gray;
}
.form-area{
    width: 1000px;
    margin: 100 auto;
}
</style>
<script src="//cdn.bootcss.com/less.js/2.5.1/less.min.js"></script>
<script src="//cdn.bootcss.com/jquery/2.1.4/jquery.min.js"></script>
</head>
<body>
<div class="form-area">
    正则：<input id="re">
    <br>
    内容：<input id="str">
    备份：<input id="backup"><button id="resore">取回</button>
    <div id="msg"></div>
</div>
<script>
$(function(){
    $('#re,#str').keyup(function(){
        $('#msg').html('&nbsp;');
        var re = $('#re').val();
        var str = $('#str').val();
        $.post('./?test.regular', {re:re,str:str}, function(j){
            console.log(j);
            $('#msg').html(j.data);
        }, 'json');
    });

    $('#resore').click(function(){
        var tmp = $('#backup').val();
        $('#backup').val($('#re').val());
        $('#re').val(tmp);
    });
});
</script>
</body>
</html>
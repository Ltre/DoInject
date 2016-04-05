@charset "utf-8";

<?php 
/** 上级调用：TestDo::shell(){let('shell/shell.css')} */

header('Content-type: text/css');

if(isset($_GET['rate'])){
    $rate = $_GET['rate'] / 100;//缩放率
}
else{
    $rate = 1;
}
?>

body{
	/* background-color:rgba(30,28,28,0.95); */
	background-image:url(res/biz/shell/login_bg_rev.jpg);
}

/*显示器*/
#screen{
	width: <?php echo 1280 * $rate?>px;
	height: <?php echo 690 * $rate?>px;
	margin-top: <?php echo 35 * $rate?>px;
	margin-right: auto;
	margin-bottom: auto;
	margin-left: auto;
	background-image:url(res/biz/shell/bg.jpg);
	/*background-color: #66B3C7;*/
	position: relative;
}

/*总容器内所有DIV默认圆角*/
#screen div{
	border-radius: 10px;
}

#screen>div{
	position: absolute;
}

/*右侧窗格视图*/
#overview{
	width: <?php echo 320 * $rate?>px;
	height: <?php echo 600 * $rate?>px;
	background-color: rgba(204,201,202,0.96);
	top: <?php echo 20 * $rate?>px;
	right: <?php echo 20 * $rate?>px;
}

/*两个命令窗格*/
#shellarea{
	background-color: rgba(174,174,174,0.93);
	margin-top: <?php echo 20 * $rate?>px;
	width: <?php echo 300 * $rate?>px;
	height: <?php echo 270 * $rate?>px;
}

/*左侧内容视图*/
#content{
	background-color: rgba(2,2,2,0.10);
	width: <?php echo 900 * $rate?>px;
	height: <?php echo 600 * $rate?>px;
	top: <?php echo 20 * $rate?>px;
	left: <?php echo 20 * $rate?>px;
}

/*浮动内嵌页*/
#iframepage{
	/*position:relative !important;*/
	width: <?php echo 896 * $rate?>px;
	height: <?php echo 596 * $rate?>px;
	border-color:rgba(0,0,0,0.00);
}

/*命令行输入条*/
#command{
	font-size:<?php echo 15 * $rate?>px;
	font-family:微软雅黑;
	font-weight: bold;
	line-height: <?php echo 20 * $rate?>px;
	text-align: center;
	color: rgba(141,228,239,1.00);
	background: rgba(0,0,0,0.55);
	border-color: rgba(0,0,0,0.00);
	width: <?php echo 1240 * $rate?>px;
	height: <?php echo 20 * $rate?>px;
	float:none;
	clear:both;
	top: <?php echo 640 * $rate?>px;
	margin: <?php echo 20 * $rate?>px auto;
	display: block;
	position:relative !important;
}

/*支架*/
#bracket{
	width: <?php echo 180 * $rate?>px;
	margin: 0 auto;
}
#bracket1{
	height:<?php echo 30 * $rate?>px;
	background: rgba(125,185,241,0.94);
}
#bracket2{
	height:<?php echo 30 * $rate?>px;
	background:rgba(142,150,161,0.91);
}
#bracket3{
	height:<?php echo 30 * $rate?>px;
	background: rgba(125,195,185,0.97);/*rgba(54,138,82,0.89)*/
}

/*底座*/
#pedestal{
	background:rgba(86,86,86,0.98);
	width: <?php echo 399 * $rate?>px;
	height: <?php echo 15 * $rate?>px;
	margin: 0 auto;
}

/*--------------------------- << PUBLIC - BEGIN ----------------------------*/


/* .rd系列：圆角 */
.rd1{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 0 !important;
	border-bottom-right-radius: 0 !important;
	border-bottom-left-radius: 0 !important;
}
.rd2{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 0 !important;
	border-bottom-left-radius: 0 !important;
}
.rd3{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 0 !important;
	border-bottom-right-radius: 10px !important;
	border-bottom-left-radius: 0 !important;
}
.rd4{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 0 !important;
	border-bottom-left-radius: 10px !important;
	border-bottom-right-radius: 0 !important;
}
.rd1__{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 10px !important;
	border-bottom-left-radius: 10px !important;
}
.rd2__{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 0 !important;
	border-bottom-right-radius: 10px !important;
	border-bottom-left-radius: 10px !important;
}
.rd3__{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 0 !important;
	border-bottom-left-radius: 10px !important;
}
.rd4__{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 10px !important;
	border-bottom-left-radius: 0 !important;
}
.rdtop{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 0 !important;
	border-bottom-left-radius: 0 !important;
}
.rdleft{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 0 !important;
	border-bottom-right-radius: 0 !important;
	border-bottom-left-radius: 10px !important;
}
.rdright{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 10px !important;
	border-bottom-left-radius: 0 !important;
}
.rdbottom{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 0 !important;
	border-bottom-left-radius: 10px !important;
	border-bottom-right-radius: 10px !important;
}
.rd13{
	border-top-left-radius: 10px !important;
	border-top-right-radius: 0 !important;
	border-bottom-right-radius: 10px !important;
	border-bottom-left-radius: 0 !important;
}
.rd24{
	border-top-left-radius: 0 !important;
	border-top-right-radius: 10px !important;
	border-bottom-right-radius: 0 !important;
	border-bottom-left-radius: 10px !important;
}
.rdall{
	border-radius: 10px !important;
}
.rdall__{
	border-radius: 0 !important;
}

/*--------------------------- PUBLIC - END >> ----------------------------*/
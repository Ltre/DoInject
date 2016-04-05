<?php

class TestDo extends DIDo{
    
    //呵呵，总得有个首页吧..
    public function start(){
        $this->shell();
    }
	
	//设置为protected时即可自动调用__call()
	protected function test(){
	    echo '与TestInject桥接时共享的信息：<br>';
	    var_dump($this->_bridge);
	    echo '<br>';
	    
		$args = func_get_args();
		$on_ret = array_pop($args);//取得环绕注入代码
		
		echo '执行信息：<br>';
		echo 'first_on : ';
		$on_ret['first']();
		
		echo 'this is TestDo::test()<br>';
		
		echo 'second_on : ';
		$on_ret['second']();
		
		for ($i=0;$i<3;$i++){
			echo 'third_on : ';
			$on_ret['third']();
		}
	}
	
	
	//各种小测试（无数据库操作）
	public function lets(){
	    goto TEST1;


	    TEST1:
	    echo path_prefix();
	    die;
	    
	    TEST2:
	    /**
	     * 测试2
	     */
	    create_class(
	    'Nima', true, 'DIBase', 'DIEventListener,DIEvent_CallFuncListener',
	    array(
	    'private $var1;',
	    'public $var2 = "2";'
	    ),
	    array(
	    'function __construct($var1){$this->var1=$var1;}',
	    'public function func1(){echo "this is func1()";}',
	    'protected function _func2(){echo "this is _fun2()";}'
	    )
	    );
	    
	    
	    TEST3:
	    /**
	     * 测试3
	     */
	    session('a', 'aaaaa');
	    import('OrekiUtil.class');
	    OrekiUtil::var_dump_array(session());
	    
	    
	    TEST4:
	    /**
	     * 测试4
	     */
	    $aaa = create_class('AAA')->newInstance()->a = 'a';
	    var_dump($aaa);
	    
	    
	    TEST5:
	    /**
	     * 测试5
	     */
	    $bbb = create_class(
	    'BBB', false, 'DIBase', null, null,
	    array('function __construct($b,$bb,$bbb){$this->b=$b;}')
	    )->newInstance(
	    array('b', 'bb', 'bbb')
	    )->b;
	    var_dump($bbb);
	    
	    TEST6:
	    /**
	     * 测试6：比较继承与不继承DIBase的区别
	     */
	    $ii = create_class('temp1', false, 'DIBase')->newInstance();//已继承
	    $ii->test = function(){echo 'temp1->test()<br>';};
	    $ii->test();
	    $ii = create_class('temp2')->newInstance();//未继承
	    $ii->test = function(){echo 'temp2->test()<br>';};
	    //$ii->test();//错误
	    $f = $ii->test;$f();
	    
	    
	    TEST7:
	    /**
	     * 测试7：单端注入
	     */
	    $obj = create_class(
	    'temp5', false, 'DIBase', null, null,
	    array('function test($a,$b){echo "this is temp5->test()<br>";}')
	    )->newInstance();
	    $obj = inject($obj, 'test', function(){echo 'this is an injectable<br>';});
	    $obj->test(1,2);
	    
	    $obj = create_class(
	    'temp6',false,'DIBase',null,null,
	    array(
	        'function __construct($a){echo "temp6->__construct()<br>";}',
	        'function test(){echo "this is temp6->test()<br>";}'
	    )
	    )->newInstance(array(1));
	    $obj = inject($obj, 'test', function(){echo 'this is a .....<br>';}, array(), true);
	    $obj->test();
	    
	    goto END;
	    
	    TEST8:
	    /**
	     * 测试8：前后注入
	     */
	    $obj = create_class(
	    'temp8', false, 'DIBase', null, null,
	    array('function test(){echo "this is temp8->test()<br>";}')
	    )->newInstance();
	    $obj = inject_full(
	    $obj, 'test',
	    array( function(){echo 'this is an injector before temp8::test()<br>';}, array() ),
	    array( function(){echo 'this is an injector after temp8::test()<br>';}, array() )
	    );
	    $obj->test();
	    
	    goto END;
	    
	    
	    
	    TEST9:
	    import(null, DI_LET_PATH);
	    
	    goto END;
	    
	    
	    
	    TEST10://let小程序测试
	    
	    let(2, 'echo "this is a dymic";');
	    let(2);
	    var_dump(let_exists(2));
	    let(2, null);
	    var_dump(let_exists(2));
	    
	    goto END;
	    
	    
	    TEST11:
	    
	    call_user_func_array(
	    function($asc, &$rs){ while(@$i++<25){$rs .= chr($asc + $i);} $rs=str_shuffle($rs);},
	    array(ord('a'), &$clazz)
	    );
	    $obj = create_class($clazz, false, 'DIBase')->newInstance(array(1,2));
	    var_dump($obj);
	    
	    goto END;
	    
	    
	    TEST12: //测试异常的控制
	    try {
	        create_class('shabi', false, null, null, null, array('function __construct(){throw new DIException("shabi");}'))->newInstance();
	    } catch (Exception $e) {
	        throw $e;
	    }
	    
	    goto END;
	    
	    TEST13://测试无特定义后缀且非ext文件的自动加载，现对entity目录进行实验
	    invoke_method(new Test, 'put');
	    
	    return;
	    
	    END:
	}
	
	//测试mysql
	function mysql(){
	    goto MYSQL1;
	    // 测试1
	    MYSQL1:
	    $e = create_class('Erbi', false, 'DIModel', null, array('var $table = "di_table";'))->newInstance();
	    $rs = $e->select(null, 'id,value', 'id desc', array(2, 2, 2));
	    dump($rs);
	    $rs = $e->find();
	    dump($rs);
	    return;
	    //测试2
	    MYSQL2:
	    $r = create_class('TableModel', false, 'DIModel')->newInstance()->find();
	    dump($r);
	    return;
	    //测试3
	    MYSQL3:
	    $r = DIModelUtil::supertable('table')->select(0, 0, 0, 2);
	    dump($r);
	    return;
	    //测试4
	    MYSQL4:
	    $t = DIModelUtil::supertable('table');
	    $r = $t->count(array('id'=>2));
	    var_dump($r);
	    $r = $t->count(array('id > :id'), array('id'=>2));
	    var_dump($r);
	    return;
	    //测试5
	    MYSQL5:
	    $table = DIModelUtil::supertable('table');
	    $rs = $table->query("SELECT * FROM {$table->table} WHERE `key` = :key", array('key'=>'k1'));
	    dump($rs);
	    return;
	    //测试6
	    MYSQL6:
	    $rs = DIModelUtil::supertable('table')->insert(array('key' => 'k123', 'value' => 'v123'));
	    var_dump($rs);
	    return;
	    //测试7：
	    MYSQL7:
	    $rs = DIModelUtil::supertable('table')->update(array('key' => 'k1'), array('value' => 'v111'));
	    var_dump($rs);
	    //测试8:
	    MYSQL8:
	    var_dump('添加：',DIModelUtil::supertable('table')->insert(array('id'=>5, 'key'=>'k5', 'value'=>'v5')));
	    var_dump('删除：',$rs = DIModelUtil::supertable('table')->delete(array('id' => 5)));
	    //测试9：
	    MYSQL9:
	    $opt = DIModelUtil::supertable('table')->alter(array('id'=>1), 'id', '-1');
	    var_dump($opt);
	    //测试10：
	    MYSQL10:
	    $opt = DIModelUtil::supertable('table')->alterByExpr(array('id'=>1), array('`id`=`id`-1', '`value`="vvv"'));
	    var_dump($opt);
	}
	
	/* 
	 * @todo 测试一
	 * http://localhost/pub/ActionInvokerPlus/?test/test1/fds/fdsa
	 * http://localhost/pub/ActionInvokerPlus/?test/test1
	 * http://localhost/pub/ActionInvokerPlus/?test/test1/fds
	 */
	function test1(){
	    var_dump(func_get_args());
	    var_dump(DIRuntime::getRuntime());
	}
	
	function time(){
	    echo date('Y-m-d H:i:s', 1410425034);
	}
	
	//测试$this->tpl()，注意开关DI_SMARTY_DEFAULT
	function template(){
	    $this->abcdefg = '1234567';
	    $this->tpl();//测试本页
	    $this->tpl('test-else');//测试其它页面
	}
	
	//利用$this->stpl(), 直接使用smarty模板
	function stemplate(){
	    $this->abc = '123';
	    $this->stpl();
	}

	//会话便捷操作
	function session($key = null, $opt = 'get', $value = null){
	    $key_exist = !empty($key) || is_numeric($key);
	    switch ($opt) {
	    	case 'get':
	    	    $key_exist || dump(session(), true);//--END
    	        if (session_exists($key)) {
    	            echo "{$key} => "; dump(session($key), true);//--END
    	        } else {
    	            exit('会话中不存在'.DI_SESSION_PREFIX.$key);//--END
    	        }
	    	case 'set':
	    	    $key_exist || die('必须指定key');//--END
	    	    session($key, $value);
	    	    exit("[ $key => $value ]设置成功");//--END
	    	case 'remove':
	    	    $key_exist || die('必须指定key');
	    	    session_remove($key, true);
	    	    exit("会话[ $key ]删除成功");//--END
	    	case 'clear':
	    	    session(null);
	    	    exit('已清空本应用专用的会话');//--END
	    }
	}

	function sessiontest(){
		session('a', 1);
		$_SESSION['b'] = 2;
		session('c', 2);
		dump($_SESSION);
		dump(session());
		dump(session_all());
		dump(session_all(true));
	}
	
	function shell(){
	    let('shell/shell');
	}
	
	function snoopy(){
	    import('Snoopy.class');
	    $snoopy = new Snoopy;
	    $snoopy->fetchtext("http://www.php.net/");
	    dump($snoopy->results);

	    $snoopy->fetchlinks("http://www.phpbuilder.com/");
	    dump($snoopy->results);
	    
	    $submit_url = "http://lnk.ispi.net/texis/scripts/msearch/netsearch.html";
	    $submit_vars["q"] = "amiga";
	    $submit_vars["submit"] = "Search!";
	    $submit_vars["searchhost"] = "Altavista";
	    $snoopy->submit($submit_url,$submit_vars);
	    dump($snoopy->results);
	    
	    $snoopy->maxframes=5;
	    $snoopy->fetch("http://www.ispi.net/");
	    echo "<PRE>\n";
	    echo htmlentities($snoopy->results[0]);
	    echo htmlentities($snoopy->results[1]);
	    echo htmlentities($snoopy->results[2]);
	    echo "</PRE>\n";
	    
	    $snoopy->fetchform("http://www.altavista.com");
	    dump($snoopy->results);
	}
	
	function phpquery(){
	    import('phpQuery/phpQuery');
	    phpQuery::newDocumentFile('http://miku.us/');
	    dump(pq('.container:eq(0)')->html());
	}
	
	function emitTest(){
	    //测试：注册
	    $this->on('nimabi', function(){
	    	echo '已经呵呵了';
	    });
	    $this->emit('nimabi');
	    //测试：故意抛异常
	    $this->_emitPool['_'.sha1('a')] = array('handle'=>'a', 'cb'=>1);
	    try {
	        $this->emit('a');
	    } catch (DIException $e) {
	        dump($e);
	    }
	}
	
	//输出随意模板
	function anyTpl(){
	    $this->_layout = '_layout';
	    $this->concrete = '404';//拿404页面作为内嵌模板来实验
	    $this->tpl();
	}

	//测试自定义路由重写
	function diyRoute($a1 = 'a1', $a2 = 'a2'){
	    $p = arg('p', 'p');
	    dump(func_get_args());
	    dump(DIRuntime::getRuntime());
	    dump(compact('a1', 'a2', 'p'));
	}
	
	//测试路由别名
	function alias(){
	    echo 'This is a Rule: alia => test/alias';
	}
	
	//测试插件生成
	function plugin(){
	    header('Access-Control-Allow-Origin: '.$_SERVER['SERVER_NAME']);
	    if (! session_exists('test_plugin_auth') && ! ($right = 'aiyowocao' == arg('password'))) {
	        putalert('auth failed');
	    } else {
	        @$right && session('test_plugin_auth', true);
	    }
	    if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    	    $path = strval(arg('path'));
    	    $code = strval(arg('code'));
    	    ('' === $path || '' === $code) && putalert('param err', url_prefix());
            let("testplugins/".trim($path, '/'), $code);
            putalert('已操作', url(DIRuntime::getItem('shell')));
	    } else {
	        print '<iframe name="posts-11-plugin"width="0"height="0"></iframe><form class="form-horizontal"method="post"action="./"target="posts-11-plugin"><input type="hidden"name="test/plugin"><fieldset><legend align="right">动态插件</legend><div class="form-group"><label for="path"class="col-lg-2 control-label">目标路径</label><div class="col-lg-10"><input type="text"class="form-control"id="path"name="path"placeholder="目标路径"></div></div><div class="form-group"><label for="code"class="col-lg-2 control-label">插件代码</label><div class="col-lg-10"><textarea type="code"class="form-control"id="code"name="code" cols="50" rows="8" placeholder="插件代码"></textarea><div class="checkbox"><label><input type="checkbox">我是老司机</label></div><br><div class="togglebutton"><label><input type="checkbox"checked="checked">求跟踪</label></div></div></div><div class="form-group"><div class="col-lg-10 col-lg-offset-2"><button type="submit"class="btn btn-primary">生成！</button></div></div></fieldset></form>';
	    }
	}
	
	function mmc(){
	    echo "===========test dwCache===========<br>";
	    import('store/dwCache');
	    $mmc = dw_cache();
	    $mmc->set('nimabi', '呵呵');
	    $mmc->set('shabi', array('a'=>1, 2));
	    $nimabi = $mmc->get('nimabi');
	    $shabi = $mmc->get('shabi');
	    dump(compact('nimabi', 'shabi'));
	    echo "<br>===========test pure memcache===========<br>";
	    $mmc = new Memcache();
	    $host = DIMMCConfig::$host;
	    $port = DIMMCConfig::$port;
	    $mmc->addserver($host, $port);
	    $mmc->set('shabi', 2);
	    $mmc->set('wocao', create_class('wocao',false,null,null,array('var $a="hehe";'))->newInstance());
	    $shabi = $mmc->get('shabi');
	    $wocao = $mmc->get('wocao');
	    dump(compact('shabi', 'wocao'));
	}
	
	function apiMerge(){
	    import('ApiMerge');
	    ApiMergeTest::test7();//1 ~ 7
	}
	
}
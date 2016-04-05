<?php

/**
 * Ai框架专用FTP工具
 * @tutorial
 * <pre>
 * 	注意：	如果你的PHP服务器采用UTF-8输出页面，
 * 		  	则需要你的PHP服务器在调用该类之前作以下操作，
 * 			header("Content-type:text/html; charset=utf-8");
 * 			并且设置以下键值对：
 * 				'localCharset'	=>	'utf-8',
 *				'remoteCharset'	=>	'FTP服务器的编码'。
 *			其它情况，不作细解，只要注意header()里的编码要和localCharset一致即可。
 * </pre>
 * @author Oreki
 * @since 2014-1-22
 */

class AiFtp{
	private $host;
	private $port = 21;
	private $user;
	private $pswd;
	private $localCharset;	//此脚本采用的编码（准确来说应该是FTP客户端程序采用的编码），建议用utf-8
	private $remoteCharset;	//FTP服务器采用的编码，例如gbk
	private $fsCharset;	//PHP服务器的文件系统的字符编码
	private $timeout = 90;	//超时时间，默认为90秒（这个90秒是系统默认的）。
	private $isSameCharsetInFtp = false;	//FTP客户端和FTP服务端的编码是否一致
	private $isScriptCharsetSameToFs = false;	//PHP脚本采用的编码是否与服务器文件系统的一致
	private $stream;
	
	/**
	 * 初始化FTP客户端
	 * @param $host	主机名或ip。如果传入的是一个数组，则后面的所有参数全部被忽略。
	 * @example
	 * 	相应地，数组格式如下：
	 *	array(
	 *		'host'			=>	'192.168.1.148',
	 *		'user'			=>	'fkb',
	 *		'pswd'			=>	'fkb',
	 *		'port'			=>	21,
	 *		'localCharset'	=>	'utf-8',
	 *		'remoteCharset'	=>	'gbk',
	 *		'fsCharset'		=>	'gbk',
	 *		'timeout'		=>	90
	 *	)
	 *	 提示：对于数组中需要缺省的条件，可以不写。
	 * @param $user	用户名，若传入null，则视为匿名用户anonymous
	 * @param $pswd	密码
	 * @param $port	端口，默认21
	 * @param $localCharset FTP客户端字符编码
	 * @param $remoteCharset FTP服务端字符编码
	 * @param $fsCharset PHP服务器的文件系统的字符编码
	 * @param $timeout 超时秒数，系统默认为90秒
	 */
	function __construct($host, $user=null, $pswd=null, $port=21, $localCharset=null, $remoteCharset=null, $fsCharset=null, $timeout=90){
		//function __construct($host, $port=21, $user=null, $pswd=null, $localCharset='utf-8', $remoteCharset='gbk', $fsCharset='gbk', $timeout=90){
		$this->host = $host;
		//检测第一个参数是不是config数组
		if(is_array($host)){
			foreach($host as $key=>$value){
				$this->$key = $value;
			}
			return;
		}
		$this->port = $port;
		$this->user = $user;
		$this->pswd = $pswd;
		$this->localCharset = $localCharset;
		$this->remoteCharset = $remoteCharset;
		$this->fsCharset = $fsCharset;
		$this->timeout = $timeout;
	}
	
	/**
	 * 连接并登录FTP
	 * @return boolean 成功登录与否
	 */
	function connect(){
		//echo $this->host."  ".$this->port."  ".$this->timeout."<br>";
		$this->stream = ftp_connect($this->host, $this->port, $this->timeout);
		if(0 == strcasecmp($this->localCharset, $this->remoteCharset)){
			$this->isSameCharsetInFtp = true;
		}
		if(0 == strcasecmp($this->localCharset, $this->fsCharset)){
			$this->isScriptCharsetSameToFs = true;
		}
		$user = $this->upcharconv($this->user);
		$pswd = $this->upcharconv($this->pswd);
		return @ftp_login(
			$this->stream, 
			null===$user?'anonymous':$user, 
			null===$pswd?'':$pswd
		) ? true : false;
	}
	
	function close(){
		ftp_close($this->stream);
	}
	
	/**
	 * 远程文件或目录更名
	 * @uses 
	 * <pre>
	 * 1、远程文件或目录已存在
	 * 		1.1 新名称合法：正常执行
	 * 		1.2 新名称非法：报错
	 * 2、远程文件或目录不存在：报错
	 * </pre>
	 * @param string $remote 远程全路径（如文件：/a/b/c.txt；目录：/a/b/c/或/a/b/c）
	 * @param string $newName 新的名称（不含目录路径，如b.exe）
	 * @return int 更名操作状态<br>
	 * 		AiFtpStatus::RENAME_REMOTE_SUCCESS为更名成功，<br>
	 * 		其它的为更名失败<br>
	 */
	function rename($remote, $newName){
		$dir = rtrim($remote, '/');
		$pos = strrpos($dir, '/');
		//得到得到的所在目录的全路径，如/a/b/c.txt得到/a/b
		$dir = substr($dir, 0, $pos);
		//远程路径转码
		$remote = $this->upcharconv($remote);
		//远程新名称转码
		$newName = $this->upcharconv($dir .'/'. rtrim($newName, '/'));
		//初步判断文件不存在，可能是目录
		if(-1 == ftp_size($this->stream, $remote)){
			//文件和目录都不是
			if(1 === (count(ftp_rawlist($this->stream, $remote)))){
				return AiFtpStatus::RENAME_REMOTE_HASNO_REMOTEFILE;
			}
		}
		else if(false/*待添加验证新名称非法的代码*/)
			return AiFtpStatus::RENAME_REMOTE_ILLEGAL_NEWNAME;
		//die($this->downcharconv($remote));
// 		die($this->downcharconv($newName));
		@$flag = ftp_rename($this->stream, $remote, $newName);
		return $flag
			? AiFtpStatus::RENAME_REMOTE_SUCCESS
			: AiFtpStatus::RENAME_REMOTE_ELSE_FAILED;
	}
	
	/**
	 * 执行LIST命令（cmd中执行DIR）
	 * @param $remoteDir 远程目录，如“/abc/def/”
	 * @return array 返回描述目录中所有文件明细的二维数组<br>
	 * <pre>
	 * 	$fileList[以0开头的数字索引] = array(
	 *		'authority'	=>	'类型和权限值,例如drw-rw-rw-',
	 *		'unknown1'	=>	'未知的明细列1',
	 *		'unknown2'	=>	'未知的明细列2',
	 *		'unknown3'	=>	'未知的明细列3',
	 *		'size'		=>	'文件大小，文件夹的则统计其包含的第一层的所有文件的大小',
	 * 		'month'		=>	'文件修改时间：月',
	 *		'date'		=>	'文件修改时间：日',
	 *		'year'		=>	'文件修改时间：年',
	 *		'time'		=>	'文件修改时间：时：分。注意：如果文件修改时间非当前年份，则服务器返回的时刻为00:00',
	 *		'name'		=>	'文件名（计入扩展名）',
	 *		'type'		=>	'类型：file、dir',
	 *	);
	 *	注意：获取列表失败时，将返回false
	 * </pre>
	 */
	function listDirsAndFiles($remoteDir){
		$fileList = null;
		$list = ftp_rawlist(
			$this->stream,
			$this->upcharconv( $remoteDir )
		);
		//获取列表失败，可能原因是编码设置错误、远程目录不存在，或其它原因
		if(false === $list){
			//return false;
			return AiFtpStatus::LIST_REMOTE_ENCODE_ERROR_ELSE;
		}
		foreach($list as $l){
			$split = preg_split('/[\s]+/', $l);
			$fileList[@$i]['authority'] = $split[0];
			$fileList[@$i]['unknown1'] = $split[1];
			$fileList[@$i]['unknown2'] = $split[2];
			$fileList[@$i]['unknown3'] = $split[3];
			$fileList[@$i]['size'] = $split[4];
			$fileList[@$i]['month'] = $split[5];
			$fileList[@$i]['date'] = $split[6];
			$fileList[@$i]['year'] = $split[7];
			if(false !== strpos($split[7], ':')){
				$fileList[@$i]['year'] = @date('Y');
				$fileList[@$i]['time'] = $split[7];
			}else{
				$fileList[@$i]['year'] = $split[7];
				$fileList[@$i]['time'] = '00:00';
			}
			$fileList[@$i]['name'] = null;
			$len = count($split);
			for($j=8; $j<$len; $j++){
				$fileList[@$i]['name'] .= $this->downcharconv( $split[$j] );
			}
			if(0 == strcasecmp('d', substr($l, 0, 1)))
				$fileList[@$i]['type'] = 'dir';
			else
				$fileList[@$i]['type'] = 'file';
			@$i++;
		}
		return $fileList;
	}
	
	private function error($msg){
		echo $msg;
		die;
	}
	
	/**
	 * 下载文件（支持全新下载和断点下载）
	 * @uses $exist = file_exists($local)  <br>
	 * <pre>
	 * 1、指定的本地文件存在
	 * 		1.1全新：选定了全新下载，但指定的本地文件已存在
	 * 			（开发者可根据此状态来询问客户端是否需要覆盖本地文件）<br>
	 * 			（当客户端决定覆盖上传时，应该先使服务器备份指定的本地文件，再继续全新下载）<br>
	 * 		1.2续传：正常执行断点下载
	 * 2、指定的本地文件不存在
	 * 		2.1全新：正常执行全新下载
	 * 		2.2续传：选定了断点下载，但指定的本地文件不存在
	 * 			（开发者可根据此状态来询问客户端是否要继续下载）<br>
	 * 			（当客户端决定继续下载时，应该采用全新下载方式）<br>
	 * </pre>
	 * @param string $local 本地文件路径（如e:\abc\def.txt或./a/b/c.txt）
	 * @param string $remote 远程文件全路径（如/a/b/c.txt）
	 * @param boolean $isContinue 是否断点续传，默认为false，全新下载
	 * @return int 下载状态<br>
	 * 		AiFtpStatus::DOWNLOAD_FILE_SUCCESS为上传成功，<br>
	 * 		其它值为下载失败。<br>
	 * 		上传失败的原因一般有：连接断开、编码错误、远程文件不存在、本地文件已存在等等<br>
	 */
	function downloadFile($local, $remote, $isContinue=false){
		$local = $this->scriptToFsCharset($local);
		$remote = $this->upcharconv($remote);
		if(-1 == ftp_size($this->stream, $remote))
			return $isContinue
				? AiFtpStatus::DOWNLOAD_HALF_FILE_HASNO_REMOTEFILE
				: AiFtpStatus::DOWNLOAD_NEW_FILE_HASNO_REMOTEFILE;
		$ret = null;
		if(file_exists($local)){
			if(! $isContinue)
				return AiFtpStatus::DOWNLOAD_NEW_FILE_EXIST_LOCALFILE;
			//ftp_set_option($this->stream, FTP_AUTOSEEK, false);//禁止自动搜寻断点开始处
			@$ret = ftp_nb_get($this->stream, $local, $remote, FTP_BINARY, filesize($local));
		}else{
			if($isContinue)
				return AiFtpStatus::DOWNLOAD_HALF_FILE_HASNO_LOCALFILE;
			@$ret = ftp_nb_get($this->stream, $local, $remote, FTP_BINARY);
		}
		while(FTP_MOREDATA == $ret){
			$ret = ftp_nb_continue($this->stream);
		}
		if(FTP_FINISHED != $ret){
			return $isContinue
				? AiFtpStatus::DOWNLOAD_HALF_FILE_UN_FINISH
				: AiFtpStatus::DOWNLOAD_NEW_FILE_UN_FINISH;
		}
		return AiFtpStatus::DOWNLOAD_FILE_SUCCESS;
	}
	
	/**
	 * 全新上传文件
	 * @deprecated 该方法即将过时，可用uploadFile()代替
	 * @see AiFtp::uploadFile()
	 * @param string $local 本地文件路径（如e:\abc\def.txt或./a/b/c.txt）
	 * @param string $remote 远程文件全路径（如/a/b/c.txt）
	 * @return int 上传状态<br>
	 * <pre>
	 * 		AiFtpStatus::UPLOAD_NEWFILE_SUCCESS为上传成功，<br>
	 * 		其它值为上传失败。<br>
	 * 		上传失败的原因一般有：连接断开、本地文件不存在、远程文件已存在等等<br>
	 * </pre>
	 */
	function uploadNewFile($remote, $local){
		$remote = $this->upcharconv($remote);
		$local = $this->scriptToFsCharset($local);
		if(! file_exists($local)){
			return AiFtpStatus::UPLOAD_NEWFILE_HASNO_LOCALFILE;
		}
		$size = ftp_size($this->stream, $remote);
		$ret = null;
		if(-1 == $size){
			@$ret = ftp_nb_put($this->stream, $remote, $local, FTP_BINARY);
			while(FTP_MOREDATA == $ret){
				//echo "正在上传...<br>";
				$ret = ftp_nb_continue($this->stream);
			}
			if(FTP_FINISHED != $ret){
				//echo "上传过程中发生错误，未完成...<br>";
				return AiFtpStatus::UPLOAD_NEWFILE_UN_FINISH;
			}
			return AiFtpStatus::UPLOAD_NEWFILE_SUCCESS;
		}else{
			//远程文件已存在
			return AiFtpStatus::UPLOAD_NEWFILE_EXIST_REMOTEFILE;
		}
	}
	
	/**
	 * 上传文件（支持全新上传和断点续传）
	 * @param string $local 本地文件路径（如e:\abc\def.txt或./a/b/c.txt）
	 * @param string $remote 远程文件全路径（如/a/b/c.txt）
	 * @param boolean $isContinue 是否指定文件断点续传，默认为false，全新上传
	 * @uses $size = ftp_size($stream, $remote);<br>
	 * <pre>
	 * -1	远程文件不存在<br>
	 * 		指定续传：选定了续传、但指定的远程文件不存在。<br>
	 * 			（开发者可根据此状态来询问客户端是否要继续上传）<br>
	 * 			（当客户端决定继续上传时，应该采用全新上传方式）<br>
	 * 		指定全新上传：正常执行全新上传<br>
	 * 非-1	远程文件已存在<br>
	 * 		指定续传：正常执行续传<br>
	 * 		指定全新上传：选定了全新上传，但指定的远程文件已存在。<br>
	 * 			（开发者可根据此状态来询问客户端是否需要覆盖远程文件）<br>
	 * 			（当客户端决定覆盖上传时，应该先使服务器备份指定的远程文件，再继续全新上传）<br>
	 * </pre>
	 * @return int 上传状态<br>
	 * <pre>
	 * 		AiFtpStatus::UPLOAD_FILE_SUCCESS为上传成功，<br>
	 * 		其它值为上传失败。<br>
	 * 		上传失败的原因一般有：连接断开、编码错误、本地文件不存在、远程文件已存在等等<br>
	 * </pre>
	 */
	function uploadFile($remote, $local, $isContinue=false){
		$remote = $this->upcharconv($remote);
		$local = $this->scriptToFsCharset($local);
		if(! file_exists($local)){
			return $isContinue 
				? AiFtpStatus::UPLOAD_HALF_FILE_HASNO_LOCALFILE
				: AiFtpStatus::UPLOAD_NEW_FILE_HASNO_LOCALFILE;
		}
		//返回-1表示远程文件不存在
		$size = ftp_size($this->stream, $remote);
		$ret = null;
		if(-1 == $size){
			if($isContinue)
				return AiFtpStatus::UPLOAD_HALF_FILE_HASNO_REMOTEFILE;
			@$ret = ftp_nb_put($this->stream, $remote, $local, FTP_BINARY);
		}else{
			if(! $isContinue)
				return AiFtpStatus::UPLOAD_NEW_FILE_EXIST_REMOTEFILE;
			@$ret = ftp_nb_put($this->stream, $remote, $local, FTP_BINARY, $size);
		}
		while(FTP_MOREDATA == $ret){
			$ret = ftp_nb_continue($this->stream);
		}
		if(FTP_FINISHED != $ret){
			return $isContinue
				? AiFtpStatus::UPLOAD_HALF_FILE_UN_FINISH
				: AiFtpStatus::UPLOAD_NEW_FILE_UN_FINISH;
		}
		return AiFtpStatus::UPLOAD_FILE_SUCCESS;
	}
	
	
	/*
	 * 上行数据前转码
	 * 本地转远端
	 */
	private function upcharconv($str){
		return $this->isSameCharsetInFtp
		? $str
		: @iconv($this->localCharset, $this->remoteCharset, $str);
	}
	
	/*
	 * 下行数据前转码
	 * 远端转本地
	 */
	private function downcharconv($str){
		return $this->isSameCharsetInFtp 
			? $str 
			: @iconv($this->remoteCharset, $this->localCharset, $str);
	}
	
	/*
	 * 转码：PHP脚本到文件系统
	*/
	private function scriptToFsCharset($str){
		return $this->isScriptCharsetSameToFs
		? $str
		: @iconv($this->localCharset, $this->fsCharset, $str);
	}
	
	/*
	 * 转码：文件系统到PHP脚本
	*/
	private function fsToScriptCharset($str){
		return $this->isScriptCharsetSameToFs
		? $str
		: @iconv($this->fsCharset, $this->localCharset, $str);
	}
	
	/**
	 * 本类的测试方法
	 */
	public static function test(){
		//$ftp = new AiFtp('192.168.1.148', 'fkb', 'fkb', 21, 'utf-8', 'gbk', 'gbk', 90);
		//$ftp = new AiFtp('192.168.1.148', 'fkb', 'fkb');
		//$ftp = new AiFtp('192.168.1.148');
		$ftp = new AiFtp(array(
			'host'			=>	'192.168.0.111',
			'port'			=>	21,
			'user'			=>	'qqqq',
			'pswd'			=>	'qqqq',
			'localCharset'	=>	'utf-8',
			'remoteCharset'	=>	'gbk',
			'fsCharset'		=>	'gbk',
			'timeout'		=>	90
		));
		
		$ftp->connect() or die('Could not connect the ftp server!');
		
		echo "<br>=======列表测试=======<br>";
		
		$buff = $ftp->listDirsAndFiles('/共享区/软件资源/');
		if(AiFtpStatus::LIST_REMOTE_ENCODE_ERROR_ELSE === $buff)
			echo "获取列表失败,可能原因是目录不存在、编码设置错误，或其它原因";
		else
			var_dump($buff);
		
		echo "<br>=======上传测试=======<br>";
		
		//var_dump($ftp->uploadNewFile('/上传区/第三关第六关.txt', 'core/lib/ext/ftp/AiFtp.class.php'));;
		var_dump($ftp->uploadFile('/上传区/第三关第六关.txt', 'core/lib/ext/ftp/AiFtp.class.php'));;
		
		echo "<br>=======下载测试=======<br>";
		
		var_dump($ftp->downloadFile('res/ftp_test/J2EE.chm', '/上传区/J2EE.chm'));
		
		echo "<br>=======更名测试=======<br>";
		
// 		var_dump($ftp->rename('/Xlight实验区/abc', 'abc.xml/'));
// 		var_dump($ftp->rename('/Xlight实验区/菜单布局及内容布置.txt', 'fuck.ttxt'));
		var_dump($ftp->rename('/Xlight实验区/abc/交易首页.rp', '王尼玛.sb'));
		
		$ftp->close();
	}
	
}












/**
 * FTP状态码
 * @tutorial 常量命名依据为带有下划线的方法名称+状态简称
 * @author Oreki
 * @since 2014-1-23
 */

class AiFtpStatus {

    /*TODO:
     * AiFtp::uploadNewFile() 将废弃
    * @deprecated
    */
    /**
     * 上传新文件成功
     * @deprecated
     */
    const UPLOAD_NEWFILE_SUCCESS = 1001;
    /**
     * 上传新文件过程中，指定的本地文件不存在
     * @deprecated
     */
    const UPLOAD_NEWFILE_HASNO_LOCALFILE = 1002;
    /**
     * 文件上传未完成，可能原因是指定的远程目录不存在，或编码设置错误等等
     * @deprecated
     */
    const UPLOAD_NEWFILE_UN_FINISH = 1003;
    /**
     * 上传新文件过程中，指定的远程文件已存在
     * @deprecated
     */
    const UPLOAD_NEWFILE_EXIST_REMOTEFILE = 1004;

    /*TODO:
     * AiFtp::listDirsAndFiles()
    */
    /**
     * 获取远程目录内列表失败。
     * 可能原因是目录不存在、编码设置错误，或其它原因
     */
    const LIST_REMOTE_ENCODE_ERROR_ELSE = 2001;

    /*TODO:
     * AiFtp::uploadFile()
    */
    /**
     * 上传新文件过程中，指定的本地文件不存在
     */
    const UPLOAD_NEW_FILE_HASNO_LOCALFILE = 3001;
    /**
     * 断点续传文件过程中，指定的本地文件不存在
     */
    const UPLOAD_HALF_FILE_HASNO_LOCALFILE = 3002;
    /**
     * 选定了全新上传，但指定的远程文件已存在
     */
    const UPLOAD_NEW_FILE_EXIST_REMOTEFILE = 3003;
    /**
     * 选定了续传，但指定的远程文件不存在
     * （也拦截了因编码问题造成的远程目录不存在的问题）
     */
    const UPLOAD_HALF_FILE_HASNO_REMOTEFILE = 3004;
    /**
     * 全新上传：文件上传未完成，可能原因是指定的远程目录不存在，或编码设置错误等等
     */
    const UPLOAD_NEW_FILE_UN_FINISH = 3005;
    /**
     * 续传：文件上传未完成，可能原因是指定的远程目录不存在，或编码设置错误等等
     * 注意：该错几乎不会报出。
     * 		因为由于编码错误而产生的路径错误问题，
     * 		已被UPLOAD_HALF_FILE_HASNO_REMOTEFILE首先拦截。
     * 		只有文件真的未续传完成时，才会报这个错。
     */
    const UPLOAD_HALF_FILE_UN_FINISH = 3006;
    /**
     * 文件上传成功
     */
    const UPLOAD_FILE_SUCCESS = 3007;

    /*TODO:
     * AiFtpStatus::downloadFile()
    */
    /**
     * 全新下载文件过程中，指定的远程文件不存在
     */
    const DOWNLOAD_NEW_FILE_HASNO_REMOTEFILE = 4001;
    /**
     * 断点下载文件过程中，指定的远程文件不存在
     */
    const DOWNLOAD_HALF_FILE_HASNO_REMOTEFILE = 4002;
    /**
     * 选定了全新下载，但指定的本地文件已存在
     */
    const DOWNLOAD_NEW_FILE_EXIST_LOCALFILE = 4003;
    /**
     * 选定了断点下载，但指定的本地文件不存在
     * （也拦截了因编码问题造成的本地目录不存在的问题）
     */
    const DOWNLOAD_HALF_FILE_HASNO_LOCALFILE = 4004;
    /**
     * 全新下载：文件下载未完成，可能原因是指定的本地目录不存在，或编码设置错误等等
     */
    const DOWNLOAD_NEW_FILE_UN_FINISH = 4005;
    /**
     * 续传：文件下载未完成，可能原因是指定的本地目录不存在，或编码设置错误等等
     * 注意：该错几乎不会报出。
     * 		因为由于编码错误而产生的路径错误问题，
     * 		已被DOWNLOAD_NEW_FILE_EXIST_LOCALFILE首先拦截。
     * 		只有文件真的未续传完成时，才会报这个错。
     */
    const DOWNLOAD_HALF_FILE_UN_FINISH = 4006;
    /**
     * 文件下载成功
     */
    const DOWNLOAD_FILE_SUCCESS = 4007;

    /*TODO:
     * AiFtp::rename()
    */
    /**
     * 远程文件更名：新的名称非法
     */
    const RENAME_REMOTE_ILLEGAL_NEWNAME = 5001;
    /**
     * 远程文件更名：指定的远程文件或目录不存在
     */
    const RENAME_REMOTE_HASNO_REMOTEFILE = 5002;
    /**
     * 远程文件更名：由于其它原因（断开连接、远程位置不存在、编码问题等等），操作失败
     */
    const RENAME_REMOTE_ELSE_FAILED = 5003;
    /**
     * 远程文件更名：成功
     */
    const RENAME_REMOTE_SUCCESS = 5004;
}
var Di = {
    /**
     * @基础方法（绑定类）
     * 解析每个Di表单的数据，
     * 并将表单验证过程和ajax-post通信数据反馈过程绑定到每个表单的提交器上
     * 预留回调函数接口：可自定义反馈数据处理方法。
     * @param validates Function Type 可自定义提交前验证表单的方法
     *      该回调方法不需要返回值，true则验证通过，false则失败。
     * @param feedbacks Function Type 可自定义反馈数据处理方法
     *      该回调方法不需要返回值
     * @example func(function(data){alert(data);});
     */
    diFormBindFeedBack : function (validates, feedbacks){
        $("[class~=di-form]").each(function(index, domEle){
            var shell = $(domEle).attr("shell")||$(domEle).attr("id");  //取指令
            var func_index = $(domEle).attr("id")||$(domEle).attr("shell");//绑定的validate和feedback的方法名称（今后更新将忽略shell）
            $(domEle).find(" [class~=di-submit]").first().click(function(){
                var query = "?="+shell;
                var args = [];//表单参数集合（便于验证）
                var i = 0;
                while( ++ i ){
                    di_tmp = $(domEle).find("[class~=di-args-"+i+"]").first().val();
                    if(undefined == di_tmp)
                        break;
                    args[i] = di_tmp;
                    query += "/" + encodeURIComponent(di_tmp);
                };
                //提交前绑定自定义验证过程
                vld_prc = validates[func_index];
                vld_flag = ( 
                    undefined == vld_prc
                    ? true
                    : ( vld_prc(args) 
                        ? true
                        :false 
                    ) 
                );
                if(vld_flag){
                    $.post(query, feedbacks[func_index]);//ajax通信并绑定数据处理
                }
            });
        });
    },
	
    /**
     * @基础方法（绑定类）
     * 为一个临时加载进页面的DI表单绑定验证过程和反馈过程
     * 与diFormBindFeedBack类似。
     * 注意：
     *      1、这个方法一次只针对一个DI表单。
     *      2、用法与bindCommonAjaxValidateAndFeedback类似，注意验证过程的function要有返回值true or false
     *      3、对于临时加载进页面的DI表单，最好不要有class="di-form"这个属性，否则可能会被绑定以表单id值为指令的事件。
     */
    singleDiFormBindFeedBack : function( jqSelector, validate, feedback ){
        jqSelector . each(function(index, domEle){
            var shell = $(domEle).attr("shell")||$(domEle).attr("id");//取指令
            $(domEle).find(" [class~=di-submit]").click(function(){
                var query = "?"+shell;
                var args = [];//表单参数集合（便于验证）
                var i = 0;
                while( ++ i ){
                    di_tmp = $(domEle).find("[class~=di-args-"+i+"]").val();
                    if(undefined == di_tmp)
                        break;
                    args[i] = di_tmp;
                    query += "/" + encodeURIComponent(di_tmp);
                };
                //提交前绑定验证过程
                vld_flag = ( 
                    undefined == validate
                    ? true
                    : ( validate(args) 
                        ? true
                        :false 
                    ) 
                );
                if(vld_flag){
                    $.post(query, feedback);//ajax通信并绑定数据处理
                }
            });
        });
    },
	
    /**
     * @基础方法（绑定类）
     * 为class=di-ajax的控件绑定点击前的验证和ajax通信后的反馈数据处理的过程
     * @param validates Function Type 可自定义提交前验证表单的方法
     *      该回调方法不需要返回值，true则验证通过，false则失败。
     * @param feedbacks Function Type 可自定义反馈数据处理方法
     *      该回调方法不需要返回值
     */
	diAjaxBindFeedBack : function(validates, feedbacks){
		//$(".di-ajax").each(function(index, domEle){
		$("[class~=di-ajax]").each(function(index, domEle){
			var shell = $(domEle).attr("shell")||$(domEle).attr("id");//取指令（今后更新将忽略id）
			var params = $(domEle).attr("params");//取后续参数串，如“abc|123|ddd”
			var func_index = $(domEle).attr("id")||$(domEle).attr("shell");//绑定的validate和feedback的方法名称（今后更新将忽略shell）
			$(domEle).click(function(){
				var query = "?" + shell + (
					(null==params||undefined==params||""==params)
					? '' : ("/"+params)
				);
				//提交前绑定自定义验证过程
				vld_prc = validates[func_index];
				vld_flag = ( 
					undefined == vld_prc
					? true
					: ( vld_prc() 
						? true
						:false 
					) 
				);
				//alert(query);
				if(vld_flag){
					$.post(query, feedbacks[func_index]);//ajax通信并绑定数据处理
				}
			});
		});
	},
	
    /**
     * @基础方法
     * 为某个一类指定的控件绑定统一的通信前验证方法和通信后数据反馈方法
     * 该类控件具有属性：shell指令，params竖线隔开的参数。
     * 定位这类控件的方式：不需要class="di-ajax"之类的来标志（如果还用di-ajax这个类，则会多发生一次AJAX通信，请注意），完全由开发者自己指定的jQuery选择器来指定
     * @param Selector jqSelector jQuery选择器，例如$('[class~=di-left-menu]')
     * @param Function validate 统一提交前的验证过程
     * @param Function feedback 统一反馈后的处理过程
     * @example 
     *      需要绑定统一过程的控件：
     *      ＜ｂｕｔｔｏｎ　ｃｌａｓｓ＝＂ｍｙｓｅｌｅｃｔｏｒ＂　ｓｈｅｌｌ＝＂ｉｎｄｅｘ＂　ｐａｒａｍｓ＝＂１｜２｜３＂　＞目标控件＜／ｂｕｔｔｏｎ＞
     *      绑定方法：
     *      Di . bindCommonAjaxValidateAndFeedback(
     *          $('[class~=myselector]'),
     *          function(){
     *              alert("统一提交前的验证过程");
     *              return true;//注意要有返回值
     *          } ,
     *          function(data){
     *              alert("统一反馈后的处理过程");
     *              alert("顺便可以拿到反馈后的数据："+data);
     *          }
     *      );
     */
    bindCommonAjaxValidateAndFeedback : function( jqSelector, validate, feedback){
        jqSelector . each(function(index, domEle){
            var shell = $(domEle).attr("id")||$(domEle).attr("shell");//取指令
            var params = $(domEle).attr("params");//取后续参数串，如“abc|123|ddd”
            $(domEle).click(function(){
                var query = "?" + shell + (
                    (null==params||undefined==params||""==params)
                    ? '' : ("/"+params)
                );
                //提交前绑定统一验证过程
                vld_flag = ( 
                    undefined == validate
                        ? true
                        : ( validate() 
                            ? true
                            :false 
                        ) 
                );
                //ajax通信后绑定统一处理过程
                if(vld_flag)
                    $.post(query, feedback);
            });
        });
    },
	
	/**
	 * @复合方法（绑定类）
	 * 页面加载完成后，自动为class=di-skip的组件添加click事件：
	 * 根据组件的id值（指令或普通模板的路径值）来确定要新加载的页面，并将新页面覆盖原文档。
	 * 绑定后，就是先了传统html的点击“跳转”的功能
	 */
	replaceDocWithClickdiSkip : function(){
		$("[class~=di-skip]").each(function(index, domEle){
			var shell = $(domEle).attr("shell")||$(domEle).attr("id");
			var params = $(domEle).attr("params");//取后续参数串，如“abc|123|ddd”
			var url = "?" + shell + (
				(null==params||undefined==params||""==params)
				? '' : ("/"+params)
			);
			if(null!=shell || ''!=shell)
				 $(domEle).click(function(){
					 Di.replaceDocWithUrl(url);
				 });
		});
	},
	
	/**
	 * @基础方法
	 * 给定指令和参数数组，拼装成符合DI框架的参数串URL
	 * @param shell 指令
	 * @param params 参数数组（每个元素都是字符串、数字等基本类型）
	 */
	assemblyShellParams : function(shell, args){
		var query = "?"+shell;
		$(args).each(function(index, elem){
			if(undefined == elem) return;
			query += "/" + encodeURIComponent(elem);
		});
		return query;
	} ,
	
	/**
	 * @基础方法
	 * 将后台反馈的data覆盖到原页面文档
	 * @param data 一般是整个页面的HTML文档
	 */
	replaceDocWithData : function(data){
		var doc = document.open("text/html","replace");
		doc.write(data);
		doc.close();
	},
	/**
	 * 根据指令（或指令加参数串、普通模板的路径值）访问服务器，
	 * 并将反馈数据覆盖到原文档
	 * @param shell 例如admin、admin|user|pass、a-b-c
	 */
	replaceDocWithShell : function(shell){
		$.post('./?'+shell, function(data){
			Di.replaceDocWithData(data);
		});
		//兼容方案：
		//lf = location.href;
		//location.href = lf.slice(0, lf.lastIndexOf('/')) + "/?x=" + shell;
	},
	
	/**
	 * 根据URL访问服务器，
	 * 并将反馈数据覆盖到原文档
	 * @param url 例如./?x=a|123、?x=a、http://localhost/?x=a
	 */
	replaceDocWithUrl : function(url){
		$.post(url, function(data){
			Di.replaceDocWithData(data);
		});
		//兼容方案：
		//location.href = url;
	},
	
	/**
	 * 初始化
	 */
	init : function(){
		this.replaceDocWithClickdiSkip();
		/*this.diFormBindFeedBack( $DiFormValidates, $DiFormDealResults );
		this.diAjaxBindFeedBack( $DiAjaxValidates, $DiAjaxDealResults );*/
	}
	
};
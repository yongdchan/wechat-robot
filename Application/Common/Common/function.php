<?php

	/**
	* 微信被动回复文本消息
	* @param array $postObj 推送的post数据
	* @param string $content 回复文本内容
	* return string  xml格式的回复消息
	*/
	function responseText($postObj, $content) {
		$template = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[%s]]></MsgType>
				<Content><![CDATA[%s]]></Content>
				</xml>";

		$fromUser = $postObj->ToUserName;
		$toUser   = $postObj->FromUserName; 
		$time     = time();
		$msgType  = 'text';
		return sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
        
	}
	
	/**
	* 微信被动回复图文消息
	* @param array $postObj 推送的post数据
	* @param string $newsArr 回复图文内容
	* return string  xml格式的回复消息
	*/
	function responseNews($postObj, $newsArr) {
		$template = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<ArticleCount>".count($newsArr)."</ArticleCount>
					<Articles>";
		foreach($newsArr as $k=>$v){
			$template .= "<item>
						<Title><![CDATA[".$v['title']."]]></Title> 
						<Description><![CDATA[".$v['description']."]]></Description>
						<PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
						<Url><![CDATA[".$v['url']."]]></Url>
						</item>";
		}	
		$template .= "</Articles>
					 </xml>";
					 
		$toUser = $postObj->FromUserName;
		$fromUser = $postObj->ToUserName;
		return sprintf($template, $toUser, $fromUser, time(), 'news');
	}
	
	
	/**
	* 获取微信access-token
	* @param string $appid appId
	* @param string $secret appSecrect
	* return string  token信息
	*/
	function getWxAccessToken($appid, $secret) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
		//2初始化
		$ch = curl_init();
		//3.设置参数
		curl_setopt($ch , CURLOPT_URL, $url);
		curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
		//4.调用接口 
		$res = curl_exec($ch);
		//5.关闭curl
		curl_close( $ch );
		// 验证curl是否出错
		if(curl_errno($ch)){
			echo "get access-token failed";
			return false;
		} else {
			$arr = json_decode($res, true);
			return $arr['access_token'];
		}	
	}
	
	
	/**
	* 获取微信用户信息
	* @param string $accessToken ACCESS_TOKEN
	* @param string $openid OPENID
	* return array  用户信息
	*/
	function getUserInfo($accessToken, $openid) {
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$accessToken."&openid=".$openid;
		//2初始化
		$ch = curl_init();
		//3.设置参数
		curl_setopt($ch , CURLOPT_URL, $url);
		curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
		//4.调用接口 
		$res = curl_exec($ch);
		//5.关闭curl
		curl_close( $ch );
		// 验证curl是否出错
		if(curl_errno($ch)){
			echo "get user info failed";
			return false;
		} else {
			$arr = json_decode($res, true);
			return $arr;
		}	
	}
	

	/**
	* 微信被动回复关注事件
	* @param array $postObj 推送的post数据
	* @param string $content 回复文本内容
	* return string  xml格式的回复消息
	*/
	function responseSubscribe($postObj) {
    $content = '欢迎关注~<br /> 可见您的品味非同一般Zzz';
		return responseText($postObj, $content);
	}


	/**
	 * 请求接口返回内容
	 * @param  string $url [请求的URL地址]
	 * @param  string $params [请求的参数]
	 * @param  int $ipost [是否采用POST形式]
	 * @return  string
	 */
	function juhecurl($url,$params=false,$ispost=0){
		$httpInfo = array();
		$ch = curl_init();
	 
		curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
		curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if( $ispost )
		{
			curl_setopt( $ch , CURLOPT_POST , true );
			curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
			curl_setopt( $ch , CURLOPT_URL , $url );
		}
		else
		{
			if($params){
				curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
			}else{
				curl_setopt( $ch , CURLOPT_URL , $url);
			}
		}
		$response = curl_exec( $ch );
		if ($response === FALSE) {
			//echo "cURL Error: " . curl_error($ch);
			return false;
		}
		$httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
		$httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
		curl_close( $ch );
		return $response;
	}
	
	/**
	 * 获取所给城市的天气信息
	 * @param  string $cityName 城市名
	 * @return  array
	 */ 
	function getWeather($cityName) {
		header('Content-type:text/html;charset=utf-8');
		$appkey = "2bab0f706a84e8a816cd59169fe22b12";

		//************1.根据城市查询天气************
		$url = "http://op.juhe.cn/onebox/weather/query";
		$params = array(
			  "cityname" => $cityName,//要查询的城市，如：温州、上海、北京
			  "key" => $appkey,//应用APPKEY(应用详细页查询)
			  "dtype" => "json",//返回数据的格式,xml或json，默认json
		);
		$paramstring = http_build_query($params);
		$content = juhecurl($url,$paramstring);
		$result = json_decode($content,true);
		if($result){
			if($result['error_code']=='0'){
				return $result;
			}else{
                //echo $result['error_code'].":".$result['reason'];
				return "";
			}
		}else{
            //echo "请求失败";
			return "";
		}
	}

	/**
	 * 问答机器人api
	 * @param  string $info 问题
	 * @return  array
	 */
	 function smartRobot($info) {
		header('Content-type:text/html;charset=utf-8');
		//配置您申请的appkey
		$appkey = "141724ebf2336e4dee725909b41df44e";
		//************1.问答************
		$url = "http://op.juhe.cn/robot/index";
		$params = array(
			  "key" => $appkey,//您申请到的本接口专用的APPKEY
			  "info" => $info,//要发送给机器人的内容，不要超过30个字符
            // "dtype" => "",//返回的数据的格式，json或xml，默认为json
            //"loc" => "",//地点，如北京中关村
            //"lon" => "",//经度，东经116.234632（小数点后保留6位），需要写为116234632
            //"lat" => "",//纬度，北纬40.234632（小数点后保留6位），需要写为40234632
            //"userid" => "",//1~32位，此userid针对您自己的每一个用户，用于上下文的关联
		);
		$paramstring = http_build_query($params);
		$content = juhecurl($url,$paramstring);
		$result = json_decode($content,true);
		if($result){
			if($result['error_code']=='0'){
				return $result;
			}else{
                //echo $result['error_code'].":".$result['reason'];
				return '';
			}
		}else{
            //echo "请求失败";
			return '';
		}
	}


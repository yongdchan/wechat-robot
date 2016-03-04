<?php
$nonce     = $_GET['nonce'];
		echo 'ff';
		$token     = 'awesome';
		$timestamp = $_GET['timestamp'];
		$echostr   = $_GET['echostr'];
		$signature = $_GET['signature'];
		//形成数组，然后按字典序排序
		$array = array($nonce, $timestamp, $token);
		sort($array);
		//拼接成字符串,sha1加密 ，然后与signature进行校验
		$str = sha1( implode('', $array ) );
		if( $str  == $signature ){
			//第一次接入weixin api接口的时候
            header('content-type:text');
            echo $echostr;
			exit;
        } 
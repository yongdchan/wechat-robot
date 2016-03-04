<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public $mmc;
    public function index(){
    $config = C('WEXIN_OPTIONS');
   
		$nonce     = $_GET['nonce'];
		$token     = $config['token'];
		$timestamp = $_GET['timestamp'];
		$echostr   = $_GET['echostr'];
		$signature = $_GET['signature'];
		//形成数组，然后按字典序排序
		$array = array($nonce, $timestamp, $token);
		sort($array);
		//拼接成字符串,sha1加密 ，然后与signature进行校验
		$str = sha1( implode($array));
		if( $str  == $signature && $echostr){
			//第一次接入weixin api接口的时候, 微信服务器会传echostr
            header('content-type:text');
            echo $echostr;
			exit;
        } else {
            // 回复公众号粉丝
            $this->mmc = memcache_init();
            //$this->replyWelcome($postObj);
            //exit;
            $this->responseMsg();
        }
	}
    
    // 接收事件推送并回复
	public function responseMsg() {
		//1.获取到微信推送过来post数据（xml格式）
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        $postObj = simplexml_load_string($postArr);
        
        
		switch(strtolower($postObj->MsgType)) {
			case 'event'://处理事件
				$this->handleEvent($postObj);
                break;
            case 'text'://文本消息
            //$this->replyWelcome($postObj);
            //exit;
                $this->handleText($postObj);
                break;
            case 'image'://图片消息
                $this->handleImage($postObj);
                break;
            default:
            //$this->replyWelcome($postObj);
		}
	}
	
	private function handleEvent($postObj) {
		$event = strtolower($postObj->Event);
		switch($event) {
			case 'subscribe':
				$this->replyWelcome($postObj);
				break;
		}
				
	}
	
	private function handleText($postObj) {
		// 获取用户消息内容
		$content = trim($postObj->Content);
		// 特殊处理
        $this->handleWeather($postObj);
        $this->handleTrain($postObj);
		// 基本回复
		switch($content) {
			case '1':
				$this->mmc->set($postObj->FromUserName."key", $postObj->FromUserName."weather", 0, '30');
                $content = "请输入地区，如北京";
            	echo responseText($postObj, $content);
				break;
			case '2':
				$this->mmc->set($postObj->FromUserName."key", $postObj->FromUserName."train", 0, '60');
            	$content = "查询格式 [始发站] [终点站] [Y-m-d] [车次类型G(高铁)D(动车)T(特快)Z(直达)K(快速)Q(其他)]\n";
                $content .="如广州东 兴宁 2016-01-01 T";
            	echo responseText($postObj, $content);
				break;
            case '3':
				$this->handleImage($postObj);
				break;
			case '4':
				$this->handleHelp($postObj);
				break;
			default:
				$this->autoReplyByTuling($postObj, $content);
		}
	}
	
	private function handleImage($postObj) {
		$newsArr = array(
                        array(
                            'title'=>'Sorting code',
                            'description'=>"some sort algorithms",
                            'picUrl'=>'https://kintali.files.wordpress.com/2012/09/algorithms_small_logo.png?w=660',
                            'url'=>'http://ohmydear.applinzi.com/index.php/Index/showSort',
                        ),
                        array(
                            'title'=>'Sorting Visual',
                            'description'=>"some sort algorithm",
                            'picUrl'=>'https://kintali.files.wordpress.com/2012/09/algorithms_small_logo.png?w=660',
                            'url'=>'http://sorting.at/',
                        ),
            
                
        );
		echo responseNews($postObj, $newsArr);
	}
	
    private function handleHelp($postObj) {
       	$this->replyWelcome($postObj);
    }
    
	private function replyWelcome($postObj) {
		$content = "感谢您关注#i猴赛雷\n回复1 查询天气\n回复2 查询火车余票\n回复3 查看算法\n回复4 查看帮助";
        										
		echo responseText($postObj, $content);
	}
	
	private function autoReplyByTuling($postObj, $reqInfo) {
		$config = C('TULING_CONFIG');
        $reqInfo = urlencode($reqInfo);
        $url = str_replace("INFO", $reqInfo, str_replace("KEY", $config['apiKey'], $config['apiURL']));
        $url .= urlencode("&loc=广州");
        /** 方法一、用file_get_contents 以get方式获取内容 */
        $res =json_decode(file_get_contents($url),true);
//        echo $res;
        /** 方法二、使用curl库，需要查看php.ini是否已经打开了curl扩展 */
//        $ch = curl_init();
//        $timeout = 5; curl_setopt ($ch, CURLOPT_URL, $url); curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
//        $file_contents = curl_exec($ch);
//        curl_close($ch);
//        echo $file_contents;
		if(!isset($res['code']))
			echo responseText($postObj, '服务器太忙了~请稍后再试试~');
        switch($res['code']){
            case 100000://文字
            /*$reply = smartRobot(trim($postObj->Content));
                if($reply) {
                    $content = $reply['result']['text'];
                }else{
                    $content = "这道题好难呐>.<=我答不出来";
                }
				$content .= "\n".$res['text'];*/
            	$content = $res['text'];
				echo responseText($postObj, $content);
                break;
            case 200000://链接
				$content = $res['text'] . ",<a href='{$res['url']}'>点击这里</a>";
				echo responseText($postObj, $content);
                break;
            case 302000://新闻
                $array = array();
                foreach($res['list'] as $v){
                    if(!empty($v['article']) && !empty($v['source']) && !empty($v['icon']) && !empty($v['detailurl'])){
                        $data['title'] =  $v['article'];
                        $data['description'] = $v['source'];
                        $data['picUrl'] = $v['icon'];
                        $data['url'] = $v['detailurl'];
                        $array[] = $data;
                    }
                    if(count($array) >= 10) break;
                }
                if(count($array) <= 0){
					$content = "暂无消息";
					echo responseText($postObj, $content);
                }else{
                    echo responseNews($postObj, $array);
                }
                break;
            case 304000://软件下载
                $array = array();
                foreach($res['list'] as $v){
                    if(!empty($v['name']) && !empty($v['count']) && !empty($v['icon']) && !empty($v['detailurl'])){
                        $data['title'] =  $v['name'];
                        $data['description'] = "下载量：" . $v['count'];
                        $data['picUrl'] = $v['icon'];
                        $data['url'] = $v['detailurl'];
                        $array[] = $data;
                    }
                    if(count($array) >= 10) break;
                }
                if(count($array) <= 0){
					$content = "暂无消息";
					echo responseText($postObj, $content);
                }else{
                    echo responseNews($postObj, $array);
                }
                break;
            case 305000://火车
                $array = array();
                foreach($res['list'] as $v){
                    if(!empty($v['trainnum']) && !empty($v['icon']) && !empty($v['detailurl'])){
                        $data['title'] =  $v['trainnum'];
                        $data['description'] = "";
                        $data['picUrl'] = $v['icon'];
                        $data['url'] = $v['detailurl'];
                        $array[] = $data;
                    }
                    if(count($array) >= 10) break;
                }
                if(count($array) <= 0){
					$content = "暂无消息";
					echo responseText($postObj, $content);
                }else{
                    echo responseNews($postObj, $array);
                }
                break;
            case 306000://航班
                $array = array();
                foreach($res['list'] as $v){
                    if(!empty($v['flight']) && !empty($v['icon']) && !empty($v['detailurl'])){
                        $data['title'] =  $v['flight'];
                        $data['description'] = "";
                        $data['picUrl'] = $v['icon'];
                        $data['url'] = $v['detailurl'];
                        $array[] = $data;
                    }
                    if(count($array) >= 10) break;
                }
                if(count($array) <= 0){
					$content = "暂无消息";
					echo responseText($postObj, $content);
                }else{
                    echo responseNews($postObj, $array);
                }
                break;
            case 308000://电影、视频、菜谱
            case 309000://酒店
            case 311000://价格
            case 312000://餐厅
                $array = array();
                foreach($res['list'] as $v){
                    if(!empty($v['name']) && !empty($v['icon']) && !empty($v['detailurl'])){
                        $data['title'] =  $v['name'];
                        $data['description'] = "";
                        $data['picUrl'] = $v['icon'];
                        $data['url'] = $v['detailurl'];
                        $array[] = $data;
                    }
                    if(count($array) >= 10) break;
                }
                if(count($array) <= 0){
					$content = "暂无消息";
					echo responseText($postObj, $content);
                }else{
                    echo responseNews($postObj, $array);
                }
                break;
            default:
            	$content = "你的问题好难额，卖萌脸>=<";
            	echo responseText($postObj, $content);
        }
	}
	
    
    
    
    public function test() {
        echo "try it!<br />";
        echo "it is awesome~~~";
    }
            
    public function handleWeather($postObj) {
        $userMmc = $this->mmc->get($postObj->FromUserName."key");
		// 验证该用户是否发送过1.查询天气
        if($userMmc !== $postObj->FromUserName."weather") 
			return;
			
		$cityName = trim($postObj->Content);
		$info = getWeather($cityName);
		if($info) {
			$info = $info['result']['data'];
			$temperature = $info['realtime']['weather']['temperature'];
			$content = $info['realtime']['city_name']."今日天气：\n温度：".$info['realtime']['weather']['temperature']."℃\npm2.5：".$info['pm25']['pm25']['quality'];
			
			if((int)$temperature < 15) { 
				$content .= "\n注意保暖亲~";
			} else if((int)$temperature > 25) { 
				$content .= "\n注意防晒亲~";
			} else {
				$content .= "\n今天天气好好の出去浪";
			}
		} else {
			$content = '服务器又抽筋了~~';
		}
		echo responseText($postObj, $content);
		exit;
    }  
    
    public function handleTrain($postObj) {
        $userMmc = $this->mmc->get($postObj->FromUserName."key");
		// 验证该用户是否发送过2.查询火车票
        if($userMmc !== $postObj->FromUserName."train") 
			return;
		
		$reqParams = explode(" ", trim($postObj->Content));
        //$reqParams = explode(" ", trim($postObj));
        //date 格式
        $date = strtotime($reqParams[2])<strtotime("today")? strtotime("today"):strtotime($reqParams[2]);
        $reqParams[2] = date("Y-m-d", $date);                                                                                  
		$params = array(
			'FROM' => '',
			'TO' => '',
			'DATE' => '',
			'TT' => '',
		);
		$paramsKey = array_keys($params);
		foreach($reqParams as $k=>$v) {
			$params[$paramsKey[$k]] = $v;
		}
		
		$config = C('TRAIN_CONFIG');
		$url = str_replace("KEY", $config['apiKey'], $config['apiURL']);
		foreach($params as $k=>$v) {
			$url = str_replace($k, urlencode($v), $url);
		}
        //echo $url;
        //echo file_get_contents($url);
		$res =json_decode(file_get_contents($url), true);
		if(!isset($res['result'])) {
			$content = "服务器太忙了~请稍后再试试~";
            echo responseText($postObj, $content);
			return;
		}
		
		$info = $res['result'];
		$content = "查询到共".count($info)." 列车次:\n";
		foreach($info as $k=>$v) {
			$content .= "#".$k." ".$v['train_no'].": ".$v['from_station_name']."->".$v['to_station_name'];
			$content .= "\n出发时间: ".$v['start_time'];
            $content .= "\n到达时间: ".$v['arrive_time'];
            $content .= "\n*软卧: ".$v['rw_num'];
            $content .= "\n*硬卧: ".$v['yw_num'];
            $content .= "\n*软座: ".$v['rz_num'];
            $content .= "\n*硬座: ".$v['yz_num'];
            $content .= "\n*无座: ".$v['wz_num'];
			$content .= "\n---------------------\n";			
		}
        //echo $content;
        echo responseText($postObj, $content);
        exit;
    }
		
    public function showSort() {
        $this->display("sort");
    }
 
}

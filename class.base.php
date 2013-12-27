<?php
/**
 * 	微信公众平台基础类 For Fshare
 * 	@author:	Skiychan
 * 	@contact:	QQ:1005043848
 * 	@website:	www.zzzzy.com
 * 	@created:	2013.11.19
 */

class Wechat{
//	public $token = '';
	
/*	public function __construct($token){
		$this->token = $token;
	}   */
	
	//判断是否来自微信服务器
/*	public function valid(){
        $echoStr = $_GET["echostr"];

        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }
*/
	
	public function responseMsg(){
		   
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
			
		if (!empty($postStr)){
			
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
			
			//文本类型时
            $textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";    

			//地理位置时
			$location = "<xml>
						<ToUserName><![CDATA[toUser]]></ToUserName>
						<FromUserName><![CDATA[fromUser]]></FromUserName>
						<CreateTime>123456789</CreateTime>
						<MsgType><![CDATA[event]]></MsgType>
						<Event><![CDATA[LOCATION]]></Event>
						<Latitude>23.137466</Latitude>
						<Longitude>113.352425</Longitude>
						<Precision>119.385040</Precision>
						</xml>";
						
			if(!empty($keyword)){
				
				//当输入文字类型
				if($msgType = "text"){
					//$contentStr = $keyword.$fromUsername.$time;
					//$preg = "/^\#([\W\w]*?)\#([\W\w]*?)\#(.*)/";	//0为功能，1为选项，2为余下的值
					//$preg = "/\#([\w]+)/";	//0为功能，1为选项，2为余下的值
					$preg = "/\#([\w\d\x{4e00}-\x{9fa5}]+)/u";	//0为功能，1为选项，2为余下的值
					preg_match_all($preg,$keyword,$newdata);
					$fn_name = $newdata[1][0];
					
					//test
					if(strtolower($fn_name)=="dd"){
						$contentStr = $location;
					}					
					
					//获取快递的参数
					if(strtolower($fn_name)=="skiy"){
						$me = "我是此公众帐号的开发者Skiy\n如果你对本功能有任何意见和建议，欢迎联系我，\n我的QQ是1005043848\n微信是forskiy,邮箱：\ndeveloper@zzzzy.com\n网站：www.zzzzy.com";
						$contentStr = $me;
					}					
						
					//获取快递的参数
					if($fn_name=="快递"||strtolower($fn_name)=="kuaidi"){
						
						$kdlist = '';
						//启用PDO连接sqlite的方式
						$db = new PDO("sqlite:somedata/data.dat");
						$results = $db->query('SELECT * FROM kuaidi')->fetchAll();
						foreach ($results as $key=>$row) {
							//var_dump($row);
							//$list_one = $row['id']." ".$row['code']." ".$row['company']."\n";
							$list_one = $row['id'].' '.$row['company']."\n";
							$kdlist = $kdlist.$list_one;
						}	
						
						//主机支持sqlite3的方式
/*						$db = new SQLite3("somedata/data.dat");
						$results = $db->query('SELECT * FROM kuaidi');
						while ($row = $results->fetchArray()) {
							//var_dump($row);
							$list_one = $row['id']." ".$row['code']." ".$row['company']."\n\r";
							$kdlist = $kdlist.$list_one;
						}	*/		
						
						$directions = "使用方法：#查快递(或三个首字母ckd)#快递编号#快递单号\n如（查询EMS单号为1034616494006的快递）：\n#ckd#15#1034616494006\n\n常用快递编号：15EMS,56申通,57顺丰,77圆通,80韵达,87中通,85宅急送,62天天\n\n";
						$contentStr = $directions.$kdlist;
					}
										
						
					if($fn_name=="查快递"||strtolower($fn_name)=="ckd"){
						$id = $newdata[1][1];
						$num = $newdata[1][2];
						
						$db = new PDO("sqlite:somedata/data.dat");
						$results = $db->query('SELECT code,company FROM kuaidi WHERE id = '.$id)->fetchAll();
						
						$code = $results[0]['code'];		//获取英文代码
						$com =  $results[0]['company'];		//获取公司名称
						
						$numinfo = "快递:".$com."\n"."单号:".$num."\n";
						$kd_url = "http://m.kuaidi100.com/query?type=".$code."&postid=".$num;
						$json_getdata = file_get_contents($kd_url);
						$get_kdinfo = json_decode($json_getdata);	//object
						$get_kdinfo = json_decode($json_getdata,true);	//array

						$last_t = "查询时间:\n".$get_kdinfo['updatetime']."\n\n";	//查询时间
							
						$kd_shipinfo =  $get_kdinfo['data'];	//快递数据数组
						$kd_total = count($kd_shipinfo)-1;
						$ship = '';
						
						//物流倒序详情
						for($i = $kd_total;$i>=0;$i--){
							$shipinfo = $kd_shipinfo[$i]['time']."\n".$kd_shipinfo[$i]['context']."\n";
							$ship = $shipinfo.$ship;
						}
						//顺序物流详情
						/*foreach ($kd_shipinfo as $v){
							$shipinfo = $v['time']."\n".$v['context']."\n";
							$ship = $shipinfo.$ship;
						}
						*/
						$get_kdinfo = $numinfo.$last_t."【物流详情】\n".$ship;
						if($ship){
							$contentStr = $get_kdinfo;
						}else{
							$contentStr = $numinfo.">没有物流数据！";
						}
					}

                	$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                	echo $resultStr;
                }else{
                	echo "Input something...";
                }
			}
        }else {
        	echo "You have no enter something...";
        	exit;
        }
    }
	
	
    //判断签名，返回bool
	private function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}
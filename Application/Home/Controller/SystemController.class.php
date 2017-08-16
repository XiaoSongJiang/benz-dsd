<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.workingchat.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> 
// +----------------------------------------------------------------------
namespace Home\Controller;
use Think\Controller;
/**
 * 前台默认控制器
 * @author gaoyu <540846283@qq.com>
 */
class SystemController extends HomeController{

    /**
     * 
     */
    public function index(){

        $this->display();
    }

    public function import() {
    	$lines=file("./DSD.sql");
    	$sqlstr="";
    	foreach($lines as $line){
      		$line=trim($line);
    		if($line!=""){
        		if(!($line{0}=="#" || $line{0}.$line{1}=="--" || $line{0}=="/")){
        			$sqlstr.=$line;  
        		}
    		}
    	}
    	$sqlstr=rtrim($sqlstr,";");
    	$sqls=explode(";",$sqlstr);
    	echo '<pre>';
    	// var_dump($sqls);
    	foreach($sqls as $sql) {
        	if(strpos($sql,'Insert into')===0) {
        		var_dump($sql);
        	//	M('')->execute($sql);
        	}
    	}
    }

    public function client_info() {
    	$client = M('client')->find();
    	echo '<pre>';
    	var_dump($client);	
    }
    
    public function dba() {

		echo '<pre>';
		//$res = M('helm_log')->order('ctime desc')->where(['type'=>'interface'])->find();
		//$res['param'] = stream_get_contents($res['params']);
		//$res['param'] = json_decode($res['param']);
		//$res['create_time'] = date('Y-m-d H:i', $res['ctime']);
		//var_dump($res);
		$res = M('user')->where(['username' => 'YR1000'])->save(['status' => 1,'password'=> user_md5('123456')]);
		var_dump($res);
		return true;
    }

    /**
     * 微信 通过code换取网页授权access_token
     * @param code          
     * @param 
     */
    public function get_access_token($code) {

        $APPID  = C('Weixin.appid');
        $SECRET = C('Weixin.appsecret');
        $url    = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$APPID&secret=$SECRET&code=$code&grant_type=authorization_code";
        $res    = SystemController::curl($url);
        return $res;
    }

    /**
     * 微信 根据access_token ,openid 获取用户信息
     * @param ACCESS_TOKEN          
     * @param openid
     */
    public function getweixininfo($code) {

        $token = SystemController::get_access_token($code);
        $ACCESS_TOKEN = $token['access_token'];
        $OPENID = $token['openid'];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$ACCESS_TOKEN&openid=$OPENID&lang=zh_CN";
        $userinfo = SystemController::curl($url);
        return $userinfo;
    }

    public function curl($url) {
        $ch = curl_init();
        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);
        // 4. 释放curl句柄
        curl_close($ch);
        return json_decode($output,true);
    }
    
    public function token() {
	
        // 初始化
        $curl = curl_init();
        // 设置选项，包括URL
        $proxy = "53.90.130.51:3128";
        // curl_setopt($curl, CURLOPT_PROXY, $proxy); // -x
	    // curl_setopt($curl, CURLOPT_PROXY, "53.90.130.51");
	    // curl_setopt($curl, CURLOPT_PROXYPORT, 3128);

	    $url = Config('TOKEN');
        curl_setopt($curl, CURLOPT_URL, $url);
        // 取消服务端验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // -k
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);	
	    // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));
        // 执行
        $output = curl_exec($curl);
        $xml = simplexml_load_string($output);
        $convert = json_decode(json_encode((array)$xml), true);

        if(curl_errno($curl)) {
            echo "CURL ERROR: ".curl_error($curl);
            echo "\n";
        }
        // 释放curl句柄
        curl_close($curl);
        return urlencode($convert['accessToken']);

    }
    public function curl_local() {
    	$curl = curl_init();
    	$url = "https://helm-uat.mercedes-benz.com.cn/getToken?dataSource=DOS&accountId=dos1&securityKey=1qaz2wsx";
            curl_setopt($curl, CURLOPT_PROXY, "http://53.90.130.51:3128");
    	curl_setopt($curl, CURLOPT_URL, $url);
    	$output = curl_exec($curl);
    	curl_close($curl);
    	return true;
    }

    public function mercedes() {

        $token = $this->token();
        $url = Config('SENDURL').$token;
	    $wechatAccountId = Config('WECHAT');
        $data = array(
            'msgId' => '1469071083',
            'sendTime' => time(),
	        'wechatAccountId' => $wechatAccountId,
	        'openId' => 'oCPZuwqm7OmHYGFL7RmxJMZB-Ax4',
            'templateId' => 'ServiceFinishedRemind',
            'data' => array(
                'first' => '高宇，您的维修工单取车时间已经到了！',
                'status' => '已完成',
                'date' => '2016/09/12 15:40',
                'remark' => '请咨询您的销售助理Kevin'
            )
        );
        $this->curl_xml($data, $url);
        return;
    }

    public function remote() {

        $token = $this->token();
        $url = Config('SENDURL').$token;
        $wechatAccountId = Config('WECHAT');
        $data = array(
            'msgId' => '1469071083',
            'sendTime' => time(),
            'wechatAccountId' => $wechatAccountId,
            'openId' => 'oCPZuwqm7OmHYGFL7RmxJMZB-Ax4',
            'templateId' => 'ServiceFinishedRemind',
            'data' => array(
                'first' => '高宇，您的维修工单取车时间已经到了！',
                'status' => '已完成',
                'date' => '2016/09/12 15:40',
                'remark' => '请咨询您的销售助理Kevin'
            )
        );
        $this->curl_xml($data, $url);
        return;
    }

    /**
     * 短信接口
     */
    public function sendSms($arr) {

        $url = 'https://helm-uat.mercedes-benz.com.cn/DOSService/sendSMSI?accessToken='.$this->token();
        
        $uniqueID = md5($arr['sender'].time());
        $data = array(
            'smsSource' => 'DOS',
            'sendSmsRequests' => array(
                'sendSmsRequest' => array(
                    'uniqueID' => $uniqueID,
                    'senderName' => $arr['senderName'],
                    'recipientMobile' => $arr['recipientMobile'],
                    'smsText' => $arr['smsText'],
                    'smsPriority' => $arr['smsPriority'],
                ),
            ),
        );
        $output = $this->curl_xml($data, $url);
        $xml = simplexml_load_string($output);
        $convert = json_decode(json_encode((array)$xml), true);

        $save = $arr;
        $save['uniqueID'] = $uniqueID;
        $save['ctime'] = time();
        $save['type'] = 'sms';
        $save['status'] = $convert['isOk'];
        $res = M('sms_log')->add($save);

        // dump(helm_xml($data, $url));die;

        return;
    }

    /**
     * 测试短信接口
     */
    public function sendSmsTest() {

        $url = 'https://helm-uat.mercedes-benz.com.cn/DOSService/sendSMSI?accessToken='.$this->token();
        
        $data = array(
            'smsSource' => 'DOS',
            'smsType' => '',
            'wechatAccountId' => '',
            'sendSmsRequests' => array(
                'sendSmsRequest' => array(
                    'uniqueID' => '123456',
                    'senderName' => 'ZZ',
                    'recipientMobile' => '18339110006',
                    'smsText' => '测试短信',
                    'smsPriority' => '5',
                ),
            ),
        );
        echo $this->curl_xml($data, $url);
        // dump(helm_xml($data, $url));die;
        return;
    }

    /**
     * 调用xml接口
     */
    public function curl_xml($data = [], $url) {

        $xml = helm_xml($data);
        // var_dump($xml);
        // 初始化
        $curl = curl_init();
        // 设置选项，包括URL
        $proxy='http://53.90.130.51:3128';
        // curl_setopt($curl,CURLOPT_PROXY,$proxy);

	    $url = $url ?: Config('SENDURL').$this->token();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true); // 发送方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 执行
        $output = curl_exec($curl);
        if(curl_errno($curl)) {
            echo "CURL ERROR: ".curl_error($curl);
        }
        // 释放curl句柄
        curl_close($curl);

        D('HelmLog')->add(array(
            'params' => json_encode($data),
            'ctime' => time(),
            'type' => 'remind'
        ));

        var_dump($output);

        return $output;

    }
}

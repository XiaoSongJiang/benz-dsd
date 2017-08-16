<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.workingchat.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> 
// +----------------------------------------------------------------------
namespace Home\Controller;
use Think\Controller;
use Common\Util\Wechat\WechatAuth;
use Common\Util\Wechat\Wechat;

/**
 * 前台默认控制器
 * @author gaoyu <540846283@qq.com>
 */    
class IndexController extends HomeController{

    //初始化
    public function __construct() {

        parent::__construct();

        define("TOKEN", C('Weixin.token')); 
        // if (isset($_GET['echostr'])) {
        //     $this->valid();
        // }else{
        //     $this->responseMsg();
        // }


        // 记录获取openid后回跳地址及参数
        if($_GET && $_GET['back']) {
            session('back', $_GET['back']);
            unset($_GET['back']);
            session('param', $_GET);
        }

        session('openid', 'oUhsws5gpfrOEcbCJwvLAR_5rWkc');
        // session('openid', 'o20nFjhloHwDEwpuWCbAzVBGAbbU');

        // 添加client
        // D('Client')->add(array(
        //     'openid' => 'o20nFjhloHwDEwpuWCbAzVBGAbbU',
        //     'nickname' => '高宇',
        //     'join_time' => time()
        // ));

        // session('openid', null);die;
        $openid = session('openid');
        // if(!$openid) {
        //     $code = $_GET['code'];
        //     if($code) {
        //         $wxinfo = SystemController::getweixininfo($code);
        //         if($wxinfo['openid']) {
        //             $this->client($wxinfo);
        //         }else {
        //             $this->redirectToGetCode();
        //             exit();
        //         }
        //     }else {
        //         $this->redirectToGetCode();
        //         exit();
        //     }
        // }

        if(!$openid) {
            // 是否有post数据
            if($_POST['openid']) {
                $log['params'] = json_encode($_POST);
                $log['ctime'] = time();
                $log['type'] = $_POST ? 'post' : 'get';
                D('HelmLog')->add($log);
                // 更新微信用户信息
                $this->client($_POST ? $_POST : $_GET);
            }else {
                // 跳转到授权页面重新获取openid
                $this->redirectToGetCode();
                return;
            }
        }else {
            session('openid', $openid);
        }

        if(!$openid && !$_POST['openid']) {
            var_dump('授权获取用户OPENID失败！！！');
            var_dump($_POST);die;
        }

        // 跳转
        $redirect = session("back");
        if($redirect) {
            session("back", null);
            $param = session('param');
            $this->redirect("Home/Index/$redirect", $param);
        }


    }

    public function redirectToGetCode() {

        // $appid = C('Weixin.appid');
        // $return_url = C('Weixin.return_url');
        // $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($return_url).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        // echo "<script language='javascript' type='text/javascript'>";
        // echo "window.location.href='$url'";
        // echo "</script>";

        // 正式环境url
        $url = 'https://helm.mercedes-benz.com.cn/WechatService/oauth?sn=BEBA3423-683E-463A-A3A7-C54C76AF963D';
        echo "<script language='javascript' type='text/javascript'>";
        echo "window.location.href='$url'";
        echo "</script>";
    }

    public function client($wxinfo) {

        $openid = $wxinfo['openid'];
        session('openid',$openid);

        // Client Info
        $where['openid'] = $openid;
        $client = D('Client')->where($where)->find();
        $param['nickname'] = $wxinfo['nickname'];
        $param['avatar'] = $wxinfo['headimgurl'];
        if($client) {
            D('Client')->where($where)->save($param);
        }else {
            $param['openid'] = $openid;
            $param['join_time'] = time();
            D('Client')->add($param);
        }
        
    }

    public function check($page) {

        $openid = session('openid');
        
        // check serving state
        $where_log['status'] = 1;
        $where_log['openid'] = $openid;
        $log = D('Log')->where($where_log)->find();
        if($log & $page!='service') {
            $this->redirect('Home/Index/service');
        }else if($page!='index') {
            $this->redirect('Home/Index/index');
        }

    }

	/**
	 * 1.0 首页
	 * 2.0 经销商
	 * 3.0 服务详情
	 * 4.0 服务加项/完成提示
	 * 5.0 服务超时 反馈
	 */

    /**
     * 1.0 首页
     * @author gaoyu <540846283@qq.com>
     */
    public function sand_index() {

        $openid = session('openid');
        if(!$openid) {
            $this->redirectToGetCode();
            exit();
        }
        session('serving','service');

        // check serving state
        $where_log['status'] = 1;
        $where_log['openid'] = $openid;
        $where_log['service_type'] = array('in', array('A','B'));
        $log = D('Log')->where($where_log)->find();
        if($log) {
            $this->redirect('Home/Index/service');
        }

        $where_last['openid'] = $openid;
        $log = D('Log')->where($where_last)->order('start_timestamp desc')->find();
        if($log) {
            $where_dealer['ld_code'] = $log['ld_code'];
            $dealer = D('User')->field('dealer_short')->where($where_dealer)->find();
            $log['dealer_short'] = $dealer['dealer_short'];
            $this->assign('log',$log);
        }
        
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('meta_title', "快修沙漏计时器");
        $this->display();

    }

    /**
     * 2.0 经销商页面
     * 1.选择服务类型
     * 2.输入服务单号：(local dealder code + wip)
     */
    public function dealer() {

        if(session('serving') == 'serving') {
            // echo 'redirect to index';
            $this->redirect('Home/Index/service');
        }
        
        $types = M('parameter')->select();
        $this->assign("timeA",$types[0]['limit']);
        $this->assign("timeB",$types[1]['limit']);

        // $this->check('dealer');
        $this->assign('meta_title', "快修沙漏计时器");
    	$this->display();
    }

    /**
     * 2.1 普通保修
     * 普通保修
     */
    public function index() {
        
        session('serving', 'service');

        $openid = session('openid');
        $where_log = array(
            'openid' => $openid,
            'status' => 1
        );
        $log = D('Log')->where($where_log)->find();

        if($log && false) {
            if($log['service_type'] == 'N') {
                if($log['normal_status'] != 'G' || $log['normal_status'] != 'E') {
                    $this->redirect('Home/Index/detail');
                }
            }else {
                $this->redirect('Home/Index/service');
            }
        }
        
        $this->assign('meta_title', "维修状态管理器");
        $this->display();
    }

    /**
     * 2.2 普通保修详情
     */
    public function detail() {

        $openid = session('openid');
        if(!$openid) {
            exit();
        }

        $where_log = array(
            'status' => 1
        );

        $wip = strtoupper($_POST['wip']) ?: strtoupper($_GET['wip']);
        if($wip) {
            $where_log['wip'] = $wip;
            // 绑定openid
            D('Log')->where($where_log)->save(['openid' => $openid]);
        }else {
            $where_log['openid'] = $openid;
        }
        $log = D('Log')->where($where_log)->find();
        // D('Log')->where(['log_id' => $log['log_id']])->save(['normal_status' => 'G']);
        // die;
        if($log['service_type'] != 'N') {
            $this->redirect('Home/Index/service');
        }

        $where_dealer = array(
            'ld_code' => $log['ld_code'],
            'user_type' => 4
        );
        $dealer = D('User')->where($where_dealer)->find();

        // 状态展示转换
        $status_map = ['I'=>'G', 'D'=>'B'];
        $normal_status = $log['normal_status'];
        $log['normal_status'] = $status_map[$normal_status] ?: $normal_status;

        // SA Name Avatar
        if($log['sa_uid']) {
            $sa = D('User')->field('nickname,avatar,mobile')->where(['id' => $log['sa_uid']])->find();
            $this->assign('sa', $sa);
        }


        // LOG CHANGE
        $hash_change = [];
        $log_change = M('log_change')->where(array(
            'wip' => $log['wip']
        ))->order('create_time asc')->select();
        foreach ($log_change as $change) {
            $hash_change[$change['status']] = $change['create_time'];
        }

        // $list = array('A', 'B', 'D', 'G');
		$list = array('A', 'B', 'F', 'G');
        $title_map = array(
            'A' => '维修准备中',
            'B' => '维修进行',
            'F' => '终检，洗车中',
            'G' => '完成，准备交车'
        );
        
        foreach ($list as $key => &$value) {
            $status = $value;
            $value = array(
                'status' => $status,
                'title' => $title_map[$status]
            );
            // 判断是否完成该状态
            if($normal_status >= $status) {
                $value['class'] = 'done';
            }
            if($log['normal_status'] == $normal_status) {
                $value['class'] .= ' active';
                $value['img'] .= '_active';
            }
            $value['detail_class'] = $value['class'];
            if($normal_status != 'G') {
                $value['detail_class'] .= ' bottom';
            }

            // 状态更新时间
            $value['reneval_time'] = $hash_change[$normal_status] ? date('m-d H:i', $hash_change[$normal_status]) : '无记录';
        }

		if($log['taking_time']) {
			$log['taking_time'] = date('Y-m-d H:i:s', $log['taking_time']);
		}else {
			$log['taking_time'] = '';
		}
        $this->assign('list', $list);
        $this->assign('dealer', $dealer);
        $this->assign('log', $log);
        $this->assign('meta_title', "维修状态管理器");
        $this->display();
    }

    /**
     * 2.3 二维码扫描绑定页面
     */
    public function qrcode() {

        $openid = session('openid');
        // 绑定信息
        $_GET = $_GET ? $_GET : session('param');
        $wip = $_GET['wip'];

        $where_log = array(
            'wip' => $wip,
        );

        $log = D('Log')->where($where_log)->find();
        if($log['openid'] ) {
            if($log['openid'] != $openid) {
                $this->redirect('Home/Index/qrcode_error', ['type' => 1]);
            }
        }else {
            // 绑定
            D('Log')->where($where_log)->save(['openid' => $openid]);
        }

		if(!$log) {
			$this->redirect('Home/Index/qrcode_error', ['type' => 2]);
		}
	
        if($log['service_type'] == 'N') {
            $this->redirect('Home/Index/detail', ['wip' => $wip]);
        }else {
            $this->redirect('Home/Index/service');
        }

    }

    /**
     * 2.4 二维码扫描绑定失败
     */
    public function qrcode_error() {

        $type = $_GET['type'];
        $this->assign('meta_title', "维修状态管理器");
        $this->assign('type', $type);
        $this->display();
    }

    /**
     * 3.0 服务详情
     * 沙漏计时
     */
    public function service() {
        
        if(session('serving') == 'no') {
            $this->redirect('Home/Index/sand_index');
        }

        $openid = session('openid');
        if(!$openid) {
            exit();
        }
        $where_log['openid'] = $openid;
        $where_log['status'] = 1;
        $where_log['service_type'] = array('in', array('A', 'B'));
        $log = D('Log')->where($where_log)->find();

        if($log) {
            $this->assign('log',$log);
            $time_now = time();
            $this->assign('time_now',$time_now);
            session("serving",'serving');
        }elseif($_POST['wip'] && $openid) {

            $start = strtotime(date('Y-m-d', time()));
            $end = $start + 24 * 60 * 60;
            $where_exists_wip['wip'] = strtoupper($_POST['wip']);
            $where_exists_wip['start_timestamp'] = array('between',array($start,$end));
            $log = M('log')->where($where_exists_wip)->find();
            if($log) {
                $this->redirect('Home/Index/index');
            }

            $service_type = $_POST['type'];
            $_POST['wip'] = strtoupper($_POST['wip']);
            $ld_code = substr($_POST['wip'],0,2);
            $wip = $_POST['wip'];

            $time = ($service_type=='A'?60:90) * 60;

            $time_now = time();
            $this->assign('time_now',$time_now);
            $param['openid'] = $openid;
            $param['ld_code'] = $ld_code;
            $param['wip'] = $wip;
            $param['service_type'] = $service_type;
            $param['start_timestamp'] = $time_now;
            $param['end_timestamp'] = $time_now + $time;
            D('log')->add($param);

            $this->assign('first','first');
            $this->assign('log',$param);
            session("serving",'serving');
        }else {
            $this->redirect('Home/Index/sand_index');
        }

        $type = $log['service_type']?$log['service_type']:$param['service_type'];

        $parameter = M('parameter')->where(array('type'=>$type))->find();
        $this->assign('type_detail',$parameter['detail']);

        $this->assign('meta_title', "快修沙漏计时器");
    	$this->display();
    }

    public function finish() {

        session("serving",'no');
        $action = $_GET['action'];
        $where_log['status'] = 1;
        $where_log['openid'] = session('openid');
        $where_log['service_type'] = array('in', array('A','B'));
        $log = D('Log')->where($where_log)->find();
        if($log) {
            // 每个用户只能抽奖一次
            $where_exists_lottery['openid'] = $log['openid'];
            $where_exists_lottery['lottery_status'] = 1;
            // 添加判断，每个用户只能中奖一次
            // $where_exists_lottery['draw_status'] = 1;
            $lottery_record = D('lottery')->where($where_exists_lottery)->find();
            if(!$lottery_record) {
                $lottery['ld_code'] = $log['ld_code'];
                $lottery['openid'] = $log['openid'];
                $lottery['log_id'] = $log['log_id'];
                $lottery['wip'] = $log['wip'];
                D('lottery')->add($lottery);
            }
        }else {
            $this->redirect('Home/Index/index');
            exit();
        }

        $param['log_id'] = $log['log_id'];
        $param['type'] = $log['service_type'];

        switch ($action) {
            case 'add':
                $redirect = 'hint';
                $save_log['status'] = 4;
                $param['status'] = 4;
                $report = 'success';
                break;
            case 'complete':
                $redirect = 'hint';
                $save_log['status'] = 2;
                $param['status'] = 2;
                $report = 'success';
                break;
            case 'timeout':
                $redirect = 'feedback';
                $save_log['status'] = 3;
                $param['status'] = 3;
                $report = 'failure';
                break;
            default:
                $this->redirect("Home/Index/index");
                break;
        }
        if($log) {
            $this->report($log['ld_code'],$log['service_type'],$report,$log['start_timestamp']);
        }

        $save_log['complete_timestamp'] = time();
        D('Log')->where($where_log)->save($save_log);

        $this->redirect("Home/Index/$redirect",$param);
    }

    /**
     * 每日报表
     */
    public function report($ld_code,$service_type,$status,$time) {

        $where_report['ld_code'] = $ld_code;
        $where_report['service_type'] = $service_type;
        $start = strtotime(date('Y-m-d', time()));
        $end = $start + 24 * 60 * 60;
        $where_report['service_date'] = array('between',array($start,$end));
        $report = D('ServiceReport')->where($where_report)->find();
        if($report) {
            $res = D('ServiceReport')->where($where_report)->setInc($status,1);
        }else {
            $param['ld_code'] = $ld_code;
            $param['service_type'] = $service_type;
            $param['service_date'] = $time;
            $status == 'success' ? $param['success'] = 1 : $param['failure'] = 1;
            $res = D('ServiceReport')->add($param);
        }
        return $res;
    }

    /**
     * 4.0 提示页面
     */
    public function hint() {

        $type = $_GET['type'];
        $message = $_GET['status'] == 2 ? 'msg-complete' : 'msg-add';
        // $parameter = M('parameter')->where(array('type'=>$type))->find();
        // $message = $parameter[$key];

        if($this->is_activity()) {
            $this->assign('activity',true);
        }else {
            $this->assign('activity',false);
        }

        $this->assign('message',$message);

        $this->assign('meta_title', "快修沙漏计时器");
    	$this->display();
    }


    /**
     * 5.0 反馈页面
     */
    public function feedback() {

        $log = $_GET;
        $this->assign('log',$log);

        if($this->is_activity()) {
            $this->assign('activity',true);
        }else {
            $this->assign('activity',false);
        }

        $this->assign('meta_title', "快修沙漏计时器");
    	$this->display();
    }

    /**
     * 5.1 提交意见
     */
    public function suggest() {

        $where_log['log_id'] = $_POST['log_id'];
        $save_log['remark'] = $_POST['remark'];
        D('Log')->where($where_log)->save($save_log);

        if($this->is_activity()) {
            $this->redirect('Home/Index/lottery');
        }else {
            $this->redirect('Home/Index/index');
        }
        
    }


    /**
     * 判断是否活动中
     */
    public function is_activity() {

        $time = time();
        $start_time = 1451955600;
        $end_time = 1454688000;

        // $vip = array('oCPZuwqm7OmHYGFL7RmxJMZB-Ax4','oCPZuwgCj6IJQF64EzQVmz_EYI7k','oCPZuwlYKgBwZBlykNzodVwXUaXA','oCPZuwppRFB7gdxDp7v79dNcJJuA','oCPZuwtPFgp7tHx7pcSBqPM7vNSU','oCPZuwjpVc6BnAh_qXWSBqINxEPU');

        // $is_vip = false;
        // foreach ($vip as $value) {
        //     if($value == session('openid')) {
        //         $is_vip = true;
        //     }
        // }
        return $start_time <= $time && $end_time >= $time;
    }

    /**
     * 6.0 抽奖页面
     */
    public function lottery() {

        $openid = session('openid');

        $where['openid'] = $openid;
        $where['lottery_status'] = 2;
        $lottery = M('lottery')->where($where)->order('lottery_id desc')->find();

        $this->assign('lottery',$lottery?1:0);
        $this->assign('meta_title', "快修沙漏计时器");

        if($this->is_activity()) {
            $this->display();
        }else {
            $this->redirect('Home/Index/index');
        }
    }

    /**
     * 
     */
    public function countdown() {
        $this->display();
    }

    /**
     * 登录SA用户，绑定openid
     */
    public function login() {

        $this->display();
    }



    /**************************微信处理****************************/

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
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

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";

            switch ($postObj->Event) {
                case 'subscribe':
                    $msgType = "text";
                    $where_config['type'] = 'subscribe';
                    $where_config['status'] = 1;
                    $config = D('WeixinConfig')->where($where_config)->find();
                    $contentStr = $config['content'];
                    // $contentStr = preg_replace("/(\&nbsp\;|　|)/", "", strip_tags($config['content']));
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                    break;
                default:
                    # code...
                    break;
            }

            if($keyword == "?" || $keyword == "？")
            {
                $msgType = "text";
                $contentStr = date("Y-m-d H:i:s",time());
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }elseif($keyword == 'openid') {
                $msgType = "text";
                $contentStr = "Your openid is : $openid ";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                cookie('openid',$openid);
                echo $resultStr;
            }
        }else{
            // echo "no xml string ";
        }
    }
}

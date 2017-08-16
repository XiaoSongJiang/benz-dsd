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

         session('openid', 'oqUp7xPuFmJDLZV5gVeXSDKZAbkU');

        $openid = session('openid');

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

        // 正式环境url
        // $url = 'https://helm.mercedes-benz.com.cn/WechatService/oauth?sn=BEBA3423-683E-463A-A3A7-C54C76AF963D';
        // 测试环境url
        // $url = 'https://helm-uat.mercedes-benz.com.cn/WechatService/oauth?sn=833B9613-0178-4BE3-BE53-2EE76328C580';
        // 获取用户授权链接
        $url = Config('AUTHURL');
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
     * 空订单页面
     */
    public function not_found() {

        $this->display('empty');
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
        if($wip && !$_GET['wip']) {
            $this->redirect('Home/Index/detail', ['wip' => $wip]);
        }

        $data['openid'] = $openid;
        $data['wip'] = $wip;
        $res = $this->exec_inspect($data);
        
        $where_log['wip'] = $wip;
        $where_log['registration_no'] = $res['registration_no'];
        $log = M('log')->where($where_log)->find();

        //openid为空时绑定openid
        if(empty($log['openid'])){
            D('Log')->where($where_log)->save(['openid' => $openid]);
        }

        if($log['service_type'] != 'N') {
            $this->redirect('Home/Index/not_found');
            // $this->redirect('Home/Index/qrcode_error', ['type' => 1]);
        }
        
        // post提交
        if($_POST['wip']) {
            M('behavior')->add(array(
                'type'   => 'query',
                'openid' => $openid,
                'ld_code'=> $log['ld_code'],
                'params' => json_encode($_POST)
            ));
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
        $normal_status = $log['normal_status'];

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
		// $list = array('A', 'B', 'F', 'G');
        $list = D('Config')->getStatusList($log['ld_code']);


        //A状态的工单如果有派单时间，就显示调度派单（A1）状态
        if($log['normal_status'] == 'A'){
            if($log['dispatch_time'] > 1){
                $normal_status = 'A1';
            }
        }

        //B状态的工单如果有终检或洗车时间，就显示终检/洗车中（F）状态
        if($log['normal_status'] >= 'B'){
            if($log['final_start_time'] > 1 && $log['wash_start_time'] > 1){
                //两个时间都有，显示靠前的
                if($log['wash_start_time'] > $log['final_start_time']){
                    $target_time = 'final_start_time';
                }else{
                    $target_time = 'wash_start_time';
                }
                //点亮
                if($normal_status <= 'F') {
                    $normal_status = 'F';
                }
            }else if($log['final_start_time'] > 1 || $log['wash_start_time'] > 1){
                //只有一个时间就显示这个时间
                if($log['wash_start_time'] > 1){
                    $target_time = 'wash_start_time';
                }else{
                    $target_time = 'final_start_time';
                }
                //点亮
                if($normal_status <= 'F') {
                    $normal_status = 'F';
                }
            }else{
                //两个时间都没有就默认
                $target_time = 'final_start_time';
            }
        }

        //如果没有配置A1状态
        if(!in_array('A1', $list)){
            if($log['normal_status'] == 'A1'){
                $normal_status = 'A';
            }
        }

        //如果没有配置F状态
        if(!in_array('F', $list)){
            if($log['normal_status'] == 'F'){
                $normal_status = 'B';
            }
        }

        $piece = 100/count($list);

        $title_map = array(
            ''  => '',
            'A' => '维修准备中',
            'A1' => '调度已派单',
            'B' => '开始维修',
            'F' => '终检/洗车中',
            'G' => '准备交车'
        );
        $show = array(
            'A' => '维修准备',
            'A1' => '调度派单',
            'B' => '开始维修',
            'F' => '终检/洗车开始',
            'G' => '准备交车'
        );
        $this->assign('current', $title_map[$normal_status]);
        $complete_percent = 0;
        foreach ($list as $key => &$value) {
            $status = $value;
            $value = array(
                'status' => $status,
                'title' => $show[$status]
            );
            // 判断是否完成该状态
            if($normal_status >= $status) {
                $value['class'] = 'done';
                $complete_percent += $piece;
            }
            // 状态更新时间
            $reneval_time = $hash_change[$status] ?: 0;
            // if($status == 'F') {
            //     $reneval_time = $log['final_start_time'];
            // }
            if($status == 'F') {
                $reneval_time = $log[$target_time];
            }
            if($status == 'A1') {
                $reneval_time = $log['dispatch_time'];
            }
            // if($status == 'F' && !$reneval_time && $normal_status >= 'G') {
            //     $reneval_time = $hash_change['G'] ?: 0;
            //     $reneval_time -= 15 * 60; // 提前15分钟
            // }
            if($reneval_time > 0 && $normal_status >= $status) {
                $value['reneval_time'] = date('H:i', $reneval_time);
                $value['reneval_date'] = date('Y-m-d', $reneval_time);
            }
            // 如果是G状态且F状态没有时间

        }
        // dump($complete_percent);die;
        $this->assign('complete_percent', $complete_percent);

        $middle_height = 110*count($list).'px';
        $this->assign('middle_height', $middle_height);

		if($log['taking_time']) {
			$log['taking_time'] = date('Y-m-d H:i', $log['taking_time']);
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
        //dump(session('param'));die;
        $data['openid'] = $openid;
        $data['wip'] = $wip;
        
        $log = $this->exec_inspect($data);
        //dump($log);die;
        
        if($log['openid'] ) {
            // if($log['openid'] != $openid) {
            //     $this->redirect('Home/Index/qrcode_error', ['type' => 2]);
            // }
        }else {
            // 绑定
            $res = M('behavior')->add(array(
                'type'   => 'qrcode',
                'openid' => $openid,
                'ld_code'=> $log['ld_code'],
                'params' => json_encode($_GET)
            ));
            $where_log['wip'] = $wip;
            D('Log')->where($where_log)->save(['openid' => $openid]);
        }

		if(!$log) {
			$this->redirect('Home/Index/qrcode_error', ['type' => 'error']);
		}
	
        $this->redirect('Home/Index/detail', ['wip' => $wip]);
    }

    /**
     * 2.4 二维码扫描绑定失败
     * type 1.服务器累了 2.已被绑定
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

    /**
     * 根据usertype执行不同扫码逻辑
     */
    public function exec_inspect($data) {
       
        $openid = $data['openid'];

        $where_log = array(
            'wip' => $data['wip'],
            'service_type' => 'N'
        );
        
        // 查找用户
        $user = M('user')->where(['openid' => $openid])->find();
        // 查找订单
        $model = M("log");
        $log = $model->where($where_log)->order("start_timestamp desc")->find();
       
        //dump($log);dump($user);die;
       
        
        // 没有该工单, 跳转到错误页面
        if(!$log) {
            
            $this->redirect('Home/Index/qrcode_error', ['type' => 'error']);
        }

        if($log['ld_code'] != $user['ld_code']) {
            $this->redirect('Home/Index/qrcode_error', ['type' => 'error']);
        }
        
        // 该openid绑定用户不是终检员
        if($user) {
            
            // SA销售顾问扫码
            if($user['user_type'] == 6) {
                // SA交单给调度
                $this->handover($log);
                // 跳转到成功页面
                $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id']]);
            }

            // 终检扫码
            if($user['user_type'] == 12) {
                // 进行终检 | 修改状态 | 发送信息
                $this->inspect($log);
                // 跳转到成功页面
                $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id']]);
            }

            // 洗车员扫码
            if($user['user_type'] == 16){

                $this->washing($log);
                //
                $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id']]);
            }

            // 调度扫码
            if($user['user_type'] == 10) {
                $this->dispatch($log);

                $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id']]);
            }
        }
        
        return $log;
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

    /**
     * 销售顾问(SA)扫码
     */
    public function handover($log) {

        $where_log = ['log_id' => $log['log_id']];
        
        if($log['normal_status'] >= 'B') {
            $this->redirect('Home/Index/qrcode_error', ['type' => 'trigger']);
        }

        // 修改终检状态 | 终检时间
        if($log['handover_time'] < 1) {
            // SA交单给调度时间
            $save['handover_time'] = time();    
            $save['normal_status'] = 'A0';   
            $q_status = '10';

        }else {
            $q_status = '11';
        }

        if($save) {
            M('log')->where($where_log)->save($save);
        }
        
        $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id'], 'status' => $q_status]);

    }

    /**
     * 终检扫码
     */
    public function inspect($log) {

        $where_log = array(
            'log_id' => $log['log_id']
        );

        // 检测终检状态
        $res = M('log')->field('final_start_time, final_end_time, normal_status')->where($where_log)->find();
        
        if($res['normal_status'] > 'F'){
            $this->redirect('Home/Index/qrcode_error', ['type' => 'trigger']);
        }

        // 修改终检状态 | 终检时间
        if($res['final_start_time'] < 1){
            $save['final_start_time'] = time();       // 终检时间
            $q_status = '1';

            $push_auth = D('Config')->checkPushAuth($log['log_id'],'F1');

        }else if($res['final_start_time'] > 1 && $res['final_end_time'] < 1){
            $save['final_end_time'] = time();       // 终检时间
            $q_status = '2';
            // $save['normal_status'] = 'F2'; 

            $push_auth = D('Config')->checkPushAuth($log['log_id'],'F2');

        }else{
            $q_status = '3';
        }
        $save['normal_status'] = 'F';       // 终检状态

        M('log')->where($where_log)->save($save);

        // 发送信息  

        // 跳转到成功页面
        $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id'], 'status' => $q_status]);
    }

    /**
     * 洗车扫码
     */
    public function washing($log) {
        
        $where_log = array(
            'log_id' => $log['log_id']
        );

        // 检测终检状态
        $res = M('log')->field('wash_start_time, wash_end_time, normal_status')->where($where_log)->find();
        
        if($res['normal_status'] > 'F'){
            $this->redirect('Home/Index/qrcode_error', ['type' => 'trigger']);
        }

        // 修改洗车状态 | 洗车时间
        if($res['wash_start_time'] < 1){
            $save['wash_start_time'] = time();       // 洗车时间
            $q_status = '4';
            // $save['normal_status'] = 'F3';       // 洗车状态

            $push_auth = D('Config')->checkPushAuth($log['log_id'],'F3');
            
        }else if($res['wash_start_time'] > 1 && $res['wash_end_time'] < 1){
            $save['wash_end_time'] = time();       // 洗车时间
            $q_status = '5';
            // $save['normal_status'] = 'F4';       // 洗车状态

            $push_auth = D('Config')->checkPushAuth($log['log_id'],'F4');

        }else{
            $q_status = '6';
        }
        $save['normal_status'] = 'F';       // 洗车状态
        M('log')->where($where_log)->save($save);

        // 发送信息  
        // 跳转到成功页面
        $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id'], 'status' => $q_status]);
    }


    // 调度派单 | 交单
    public function dispatch($log) {
        
        // 修改派单时间
        if($log['dispatch_time'] < 1){

            // 开钟后 需要后台修改派单时间
            if($log['normal_status'] >= 'B'){
                $this->redirect('Home/Index/qrcode_error', ['type' => 'trigger']);
            }
            $save['dispatch_time'] = time(); 
            $save['normal_status'] = 'A1';      
            $q_status = '7';
            // 派单推送
            $push_auth = D('Config')->checkPushAuth($log['log_id'],'A1');
            
        }else if($log['dispatch_time'] > 1 && $log['complete_time'] < 1){

            if($log['normal_status'] > 'F') {
                $this->redirect('Home/Index/qrcode_error', ['type' => 'trigger']);
            }

            // 交单时间
            $save['complete_time'] = time();  
            $save['normal_status'] = 'D1';      
            $q_status = '8';
            // 交单推送
            $push_auth = D('Config')->checkPushAuth($log['log_id'],'A2');

        }else{
            $q_status = '9';
        }

        // 修改订单状态
        $where_log = array(
            'log_id' => $log['log_id']
        );
        M('log')->where($where_log)->save($save);

        // 跳转到成功页面
        $this->redirect('Home/Index/inspect_success', ['id' => $log['log_id'], 'status' => $q_status]);
    }


    /**
     * 
     */
    public function inspect_success() {

        $id = $_GET['id'];
        $status = $_GET['status'];
        
        $log = M('log')->where(['log_id' => $id])->find();
        
        switch ($status) {
            case 1:
                $title = '扫码成功!';
                $info = '终检开始时间：';
                $time = date("Y年n月j日 H:i", $log['final_start_time']);
                break;
            case 2:
                $title = '扫码成功!';
                $info = '终检结束时间：';
                $time = date("Y年n月j日 H:i", $log['final_end_time']);
                break;
            case 3:
                $title = '扫码失败!';
                $info = '终检已完成，完成时间：';
                $time = date("Y年n月j日 H:i", $log['final_end_time']);
                break;
            case 4:
                $title = '扫码成功!';
                $info = '洗车开始时间：';
                $time = date("Y年n月j日 H:i", $log['wash_start_time']);
                break;
            case 5:
                $title = '扫码成功!';
                $info = '洗车结束时间：';
                $time = date("Y年n月j日 H:i", $log['wash_end_time']);
                break;
            case 6:
                $title = '扫码失败!';
                $info = '洗车已完成，完成时间：';
                $time = date("Y年n月j日 H:i", $log['wash_end_time']);
                break;
            case 7:
                $title = '扫码成功!';
                $info = '派单时间：';
                $time = date("Y年n月j日 H:i", $log['dispatch_time']);
                break;
            case 8:
                $title = '扫码成功!';
                $info = '交单时间：';
                $time = date("Y年n月j日 H:i", $log['complete_time']);
                break;
            case 9:
                $title = '扫码失败!';
                $info = '已交单，完成时间：';
                $time = date("Y年n月j日 H:i", $log['complete_time']);
                break;
            case 10:
                $title = '扫码成功!';
                $info = 'SA交单给调度时间：';
                $time = date("Y年n月j日 H:i", $log['handover_time']);
                break;
            case 11:
                $title = '扫码失败!';
                $info = '已交单给调度，交单时间：';
                $time = date("Y年n月j日 H:i", $log['handover_time']);
                break;

        }
        // $time = date("Y年n月j日 H:i", $log['final_start_time']);
        $this->assign('title', $title);
        $this->assign('time', $time);
        $this->assign('info', $info);
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

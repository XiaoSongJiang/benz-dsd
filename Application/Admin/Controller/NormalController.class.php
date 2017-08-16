<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
include 'classes/PHPExcel/IOFactory.php';

/**
 * 普通保修订单
 * @author gaoyu <540846283@qq.com>
 */
class NormalController extends AdminController{

    /**
     * 订单列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

		$exe_start_time = time();
        // 权限控制
        $auth = session('user_auth');
        $info = D('User')->find($auth['uid']);
        $usertype = $auth['user_type'];
        $ld_code = $auth['ld_code'];
        $this->assign('usertype',$usertype);
        // 是否展示数据统计
        if($usertype == 6) {
            $this->assign('statistics', 'statistics');
        }

        /**
         * 时间筛选类型 工单创建时间|登记进厂时间
         */
        $dateFilterType = I('dateFilterType','start_timestamp','string');
        if($dateFilterType) {
            $dateFilterField = 'benz_log.'.$dateFilterType;
            $this->assign('dateFilterType', $dateFilterType);
        }else {
            $dateFilterField = 'benz_log.start_timestamp';
        }

        // tab url 列表
        $type = $_GET['type'] ?: 'normal';
        if($type == 'normal') {
            // $map['benz_log.normal_status'] = array(array('in',['','A','B','D','F','G']),array('EXP','IS NULL'),'OR');
            $map['benz_log.normal_status'] = array('neq', 'I');
        }elseif ($type == 'finished') {
            $map['benz_log.normal_status'] = 'I';
        }

        $urls = array([
            'name' => '未完成',
            'url'  => U('index', ['type' => 'normal']),
            'class'=> $type == 'normal' ? 'current' : ''    
        ],[
            'name' => '已完成',
            'url'  => U('index', ['type' => 'finished']),
            'class'=> $type == 'finished' ? 'current' : ''
        ]);
        $this->assign('urls', $urls);
        $this->assign('type', $type);

        // 搜索条件
        $keyword   = I('keyword','','string');
        $condition = array('like','%'.$keyword.'%');
        $map['benz_log.ld_code|benz_log.wip|benz_log.registration_no|benz_user.nickname|benz_user.mobile'] = array($condition, $condition, $condition, $condition, $condition,'_multi'=>true);
        $map['benz_log.service_type'] = 'N';
        $this->assign('keyword', $keyword);

        /**
         * 时间筛选
         */
        $start_date = strtotime(I('start_date'));
        $end_date   = strtotime(I('end_date')) + 86399;

        if(!$start_date || !$end_date) {
            $start_date = mktime(0,0,0,date('m'),1,date('y'));
            $end_date   = mktime(23,59,59,date('m'),date('t'),date('y'));
        }
        $this->assign('start_date',date('Y-m-d',$start_date));
        $this->assign('end_date',date('Y-m-d',$end_date));

        /**
         * SA
         */
        if($usertype == 6) {
            $dealer = M('user')->where(['ld_code' => $info['ld_code'], 'user_type' => 4])->find();
            $ld_code = $dealer['ld_code'];
        }

        $this->assign('service_type',$service_type);
        if($ld_code) {
            $map['benz_log.ld_code'] = $ld_code;
        }

        // 时间筛选
        if($start_date && $end_date) {
            $map[$dateFilterField] = array('between',array($start_date,$end_date));
        }

        // SA筛选列表
        $where_sa = array(
            'user_type' => 6,
            'status' => 1
        );
        if($ld_code) {
            $where_sa['ld_code'] = $ld_code;
        }
        $sales_list = M('user')->field('id,nickname')->where($where_sa)->select();
        $this->assign("sales_list", $sales_list);

        // SA用户筛选
        $sa_uid = I('sa_uid');
        if($sa_uid) {
            $map['benz_log.sa_uid'] = $sa_uid;
            $this->assign("sa_uid", $sa_uid);
        }

        // 状态筛选
        $log_status = I('log_status');
        if($log_status) {
            if($log_status == 'empty') {
                $map['benz_log.normal_status'] = array(array('in',['']),array('EXP','IS NULL'),'OR');
            }else {
                $map['benz_log.normal_status'] = $log_status;
            }
            $this->assign('log_status',$log_status);
        }

        // 预约筛选
        $reserve_status = I('reserve_status','','string');
        if($reserve_status) {
            if($reserve_status == 'yes') {
                $map['benz_log.reserve_status'] = '1';
            }else {
                $map['benz_log.reserve_status'] = array(array('in',['']),array('EXP','IS NULL'),'OR');
            }
            $this->assign('reserve_status', $reserve_status);
        }

        // 是否绑定微信OPENID
        $weixin = I('weixin', '', 'string');
        if($weixin) {
            switch ($weixin) {
                case 'bind':
                    $map['benz_log.openid'] = array(array('neq',''),array('EXP','IS NOT NULL'), 'OR');
                    break;
                case 'unbind':
                    $map['benz_log.openid'] = array(array('eq',''),array('EXP','IS NULL'), 'OR');
                    break;
                default:
                    break;
            }
            $this->assign("weixin", $weixin);
        }

        // 用户类型
        if($usertype == 6) {
            $map['benz_log.sa_uid'] = $auth['uid'];
        }
        
        /**
         * 时间筛选
         */
        $dateFilterMap = getDateFilters();
        $dateFilter   = I('dateFilter','','string');    
        if($dateFilter) {
            $map[$dateFilterField] = array('between', $dateFilterMap[$dateFilter]);
            $this->assign('dateFilter', $dateFilter);
        }
        
        /**
         * 排序参数
         */
        $order_type = I('order_type');
        $sort = I('sort');

        if ($_GET['order_type']) {
            $order = $order_type." ".$sort;
            if($sort == 'desc'){
                $sort = 'asc';
            }else{
                $sort = 'desc';
            }
        }else{
            $order = 'check_in asc, start_timestamp asc';
            $sort = 'desc';
        }
        $this->assign('sort', $sort);

        /**
         * 执行筛选
         */
		$sqlst = time();
        $count = M('log')->field('benz_log.*,benz_user.nickname,benz_user.mobile')->join('left join benz_user on benz_log.sa_uid = benz_user.id')->where($map)->count();
		$sqlse = time();

        $_GET['p'] = $_POST['p'] ?: 1;
        $pagenum = C('ADMIN_PAGE_ROWS');


        // $Page = new \Common\Util\Page($count,$pagenum);
        // $page = $Page->show();
        $index = 1;
        $page = [];
        $total = $count;
        $page_total = ceil($total / $pagenum);
        while($total > 0) {
            $cur = $index;
            $page_item = array(
                'class' => 'num',
                'name'  => $index++
            );
            if($_GET['p'] == $index - 1) {
                $page_item['class'] .= ' current';
            }
            if(abs($_GET['p'] - $cur) < 3 || $cur == 1 || $cur == $page_total) {
                array_push($page, $page_item);
            }
            $total -= $pagenum;
        }
        $this->assign('page',$page);
        $this->assign('page_current', $_GET['p']);
        $this->assign('page_total', $page_total);
        
        $first = ($_GET['p'] - 1) * $pagenum;
        $limit = $first.','.$pagenum;

        $this->assign('first', $first + 1 > $count ? $count : $first + 1);
        $this->assign('last', $first + $pagenum > $count ? $count : $first + $pagenum);
        $this->assign('count', $count);

        $list = M('log')->field('benz_log.*,benz_user.nickname,benz_user.mobile')->join('left join benz_user on benz_log.sa_uid = benz_user.id')->where($map)->order($order)->limit($limit)->select();

        foreach ($list as $key => &$value) {
            
            if($value['sa_uid']) {
                $sa = M('user')->where(['id' => $value['sa_uid']])->find();
                $value['sa'] = $sa['nickname'];
            }
            $value['timeout'] = getTimeoutStatus($value);
            // var_dump($value['timeout'] ?: '没有超时');
            $value['bind'] = $value['reserve_status'] ? 'bind' : '';
            $value['editable'] = getEditable($value, $usertype);
            // var_dump($value['editable']);
            $timeMap = getStatusTimeMap($value['wip']);
            $value['a_time'] = $timeMap['A'];
            $value['b_time'] = $timeMap['B'];
            $value['d_time'] = $timeMap['D'];
            $value['g_time'] = $timeMap['G'];
            // $value['temporary'] = getTemporaryStatus($value);
        }

        $this->assign('list', $list);

        // 判断是否导出
        if(I('export')) {
            $list = M('log')->field('benz_log.*')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code and benz_user.user_type = 4')->where($map)->order($order)->select();

            foreach ($list as $key => &$value) {
                if($value['sa_uid']) {
                    $sa = M('user')->where(['id' => $value['sa_uid']])->find();
                    $value['sa'] = $sa['nickname'];
                }
                // 是否预约
                $value['reserve_status'] = $value['reserve_status'] ? '是' : '否';
                // 预约类型
                $value['reserve_type'] = getReserveType($value['reserve_type']);
                // 是否超时
                $value['timeout'] = getTimeoutStatus($value) ? '是' : '否';
                // 是否绑定微信
                $value['openid'] = getWeChatStatus($value) ? '是' : '否';
                // 查询各状态时间
                $timeMap = getStatusTimeMap($value['wip']);
                $value['a_time'] = $timeMap['A'];
                $value['b_time'] = $timeMap['B'];
                $value['d_time'] = $timeMap['D'];
                // 时间转换
                $timeFields = ['start_timestamp', 'check_in_time', 'taking_time', 'complete_time', 'dispatch_time', 'final_start_time', 'final_end_time', 'wash_start_time', 'wash_end_time', 'a_time', 'b_time', 'd_time', 'check_in'];
                foreach ($timeFields as $field) {
                    $value[$field] = get_value($value[$field], 'time', '');
                }
            }
            $this->export($list);
        }
        

        // 当月统计
        $today_start = mktime(0,0,0,date('m'),date('d'),date('y'));
        $today_end   = mktime(23,59,59,date('m'),date('d'),date('y'));
        $month_start = mktime(0,0,0,date('m'),1,date('y'));
        $month_end   = mktime(23,59,59,date('m'),date('t'),date('y'));
        $where_month = array(
            'check_in' => array(
                'between', array($month_start, $month_end)
            ),
            'service_type' => 'N'
        );
        if($ld_code) {
            $where_month['ld_code'] = $ld_code;
        }
        // SA只统计自己的
        if($usertype == 6) {
            $where_month['sa_uid'] = $auth['uid'];
        }
        // 当日进厂
        $month_list = M('log')->field('check_in, registration_no, normal_status')->where($where_month)->select();
        
        $today_check = 0;       // 今日进厂
        $today_pdi   = 0;       // 今日PDI
        $today_delay = 0;       // 今日滞留
        $month_check = 0;       // 本月进厂
        $month_pdi   = 0;       // 本月PDI

        foreach ($month_list as $log) {
            $start_timestamp = $log['check_in'] ?: 0;
            $normal_status   = $log['normal_status'];
            $registration_no = $log['registration_no'];
            $istoday = $start_timestamp > $today_start && $start_timestamp < $today_end;
            $isdelay = $normal_status != 'I' && $normal_status != 'G';
            // 进场
            $month_check += 1;
            if($istoday) {
                $today_check += 1;
            }
            // PDI 没有车牌号
            if(!$registration_no) {
                $month_pdi += 1;
                if($istoday) {
                    $today_pdi += 1;
                }
            }
            // 当日滞留 不等于I|G
            if($istoday && $isdelay) {
                $today_delay += 1;
            }
        }

        // 总滞留
        $where_delay = $where_month;
        unset($where_delay['check_in']);
        $where_delay['normal_status'] = array(array('not in','I,G'));
        $total_delay = M('log')->where($where_delay)->count();
        $this->assign('today_check', $today_check);
        $this->assign('today_pdi', $today_pdi);
        $this->assign('today_delay', $today_delay);
        $this->assign('month_check', $month_check);
        $this->assign('month_pdi', $month_pdi);
        $this->assign('total_delay', $total_delay);
        
        $this->display();
    }

    /**
     * 解绑用户微信绑定Openid
     */
    public function unbind($id) {

        $id = $_GET['id'];
        if($id) {
            $where_user = ['log_id' => $id];
            $save_unbind = array(
                'openid' => ''
            );
            D("Log")->where($where_user)->save($save_unbind);
            $this->success($id);
        }else {
            $this->error('解绑失败', U('index'));
        }
    }

    /**
     * 导出订单详情
     */
    public function export($list) {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        // set current sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // set sheet name
        $objPHPExcel->getActiveSheet()->setTitle('订单列表');
        // insert header
        // $titles = array('序号', '客户微信昵称', '经销商','服务类型','服务单号','车牌号', '进厂时间','维修顾问', '状态', '完工时间', '派工时间', '终检时间', '技师', '自定义字段1','自定义字段2','自定义字段3','自定义字段4');
        $titles = array('WIP单号', '车牌号', '当前状态', '登记进厂时间', '期望交车时间', '负责的SA', '是否超时', '是否预约', '派单时间', '开钟时间', '关钟时间', '交单时间', '终检开始时间', '终检结束时间', '洗车开始时间', '洗车结束时间', '创建工单时间', '目前工种', '说明', '是否绑定微信');
        $pos = 'A';
        foreach ($titles as $key => $title) {
            $objPHPExcel->getActiveSheet()->setCellValue($pos . '1', $title);
            $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setWidth(20);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getAlignment()->setHorizontal('center');
            $pos++;
        }

        // $params = array('serial','wechat','dealer_short','service_type','wip','registration_no', 'start_timestamp','sa','status', 'complete_time', 'dispatch_time', 'final_start_time', 'technician', 'custom1', 'custom2', 'custom3', 'custom4');
        $params = array('wip','registration_no', 'normal_status', 'check_in', 'taking_time', 'sa', 'timeout', 'reserve_status', 'dispatch_time', 'b_time', 'd_time', 'complete_time', 'final_start_time','final_end_time', 'wash_start_time','wash_end_time', 'start_timestamp', 'work_type', 'log_desc', 'openid');

        // insert data
        $start = 2;
        foreach ($list as $key => &$value) {

            $value['wechat'] = getNickname($value['openid']);
            $value['dealer_short'] = getDealerInfo($value['ld_code'],'dealer_short');
            $value['temporary'] = getTemporaryStatus($value);
            $value['normal_status'] = getNormalStatus($value['temporary']);

            $pos = 'A';
            foreach ($params as $param) {
                $objPHPExcel->getActiveSheet()->setCellValue($pos.$start, $value[$param]);
                $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
                // $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setAutoSize(true);
                $pos++;
            }
            $start++;
        } 

        // output the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'."Order List.xls".'"');//设置输出excel文件名称
        // header('Cache-Control: max-age=5');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter->save("php://output");
    }

    public function weixin() {
        $log_id = '3';
        $list = ['A','A1', 'A2', 'B', 'D', 'F1', 'F2', 'F3', 'F4', 'G'];
        echo '<pre>';
        foreach ($list as $status) {
            $push_auth = D('Config')->checkPushAuth($log_id,$status);
            var_dump($push_auth);
        }
    }

    public function modify() {

        $data = $_POST;
        $log_id = $data['log_id'];
        if(!$log_id) {
            $this->error('缺少log_id');
        }

        $log = M('log')->where(['log_id' => $log_id])->find();
        $times = ['dispatch_time', 'complete_time', 'final_start_time', 'final_end_time', 'wash_start_time', 'wash_end_time', 'handover_time'];
        foreach ($times as $time) {
            if(isset($data[$time])) {
                $data[$time] = $data[$time] ? strtotime($data[$time]) : 0;

                $modified = true;
                // 如果大于G状态，判断时间是否符合逻辑
                if($log['normal_status'] >= 'G') {
                    $map = getStatusTimeMap($log['wip']);
                    $gtime = $map['G'];
                    if($data[$time] > $gtime) {
                        unset($data[$time]);
                        $modified = false;
                        $message = "填写时间不能大于交车时间";
                    }
                }
                //判断时间如果改变。就发消息
                if($data[$time] != $log[$time] && $modified){
                    $status = $this->getModifyStatus($time);
                    $push_auth = D('Config')->checkPushAuth($log_id, $status);
                }
            }
        }
        $where_save = ['log_id' => $log_id];
        $res = M('log')->where($where_save)->save($data);

        $data = M('log')->where($where_save)->find();

        // 当前状态
        $normal_status = getTemporaryStatus($data);
        // 如果发生改变
        if($normal_status != $data['normal_status']) {
            M('log')->where($where_save)->save(['normal_status' => $normal_status]);
            $data = M('log')->where($where_save)->find();
        }

        if($message) {
            $data['message'] = $message;
        }
        $data['status'] = $res;

        $this->success($data);
    }

    /**
     * 数据统计页面
     */
    public function statistics() {

        $auth = session('user_auth');
        $ld_code = $auth['ld_code'];

        $sa = getSalesAgentList($ld_code);
        $sa['other'] = '其他';
        $where_log = array(
            'status' => 1,
            'service_type' => 'N'
        );
        
        if($ld_code) {
            $where_log['ld_code'] = $ld_code;
        }

        $time_type = $_POST['time_type'] ?: 'today';

        if(IS_POST){
            
            $tips = '';
            $title = '';
            function formatTime($time) {
                $year = date('Y', $time);
                $month = (int)date('m', $time);
                return $year."年".$month."月";
            }
            switch ($_POST['time_type']) {
                case 'today':
                    $start = mktime(0,0,0,date('m'),date('d'),date('y'));
                    $end   = mktime(23,59,59,date('m'),date('d'),date('y'));
                    $title = '当日';
                    break;
                case 'month':
                    $start = mktime(0,0,0,date('m'),1,date('y'));
                    $end   = mktime(23,59,59,date('m'),date('t'),date('y'));
                    $tips  = formatTime($end);
                    $title = '本月';
                    break;
                case 'last':
                    $start = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                    $end   = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d').'day')));
                    $tips  = formatTime($end);
                    $title = '上一月';
                    break;
            }

            switch ($_POST['repair_type']) {
                case '':
                    $title_type = '全部';
                    break;
                case '保养':
                    $where_log['log_type'] = '保养';
                    $title_type = '保养';
                    break;
                case '一般维修':
                    $where_log['log_type'] = '一般维修';
                    $title_type = '一般维修';
                    break;
                case '事故维修':
                    $where_log['log_type'] = '事故维修';
                    $title_type = '事故维修';
                    break;
                case '保修':
                    $where_log['log_type'] = '保修';
                    $title_type = '保修';
                    break;
                case '其他':
                    $where_log['log_type'] = array(array('in',['其他','']),array('EXP','IS NULL'),'OR');
                    $title_type = '其他';
                    break;
            }
        }else{
            $start = mktime(0,0,0,date('m'),date('d'),date('y'));
            $end   = mktime(23,59,59,date('m'),date('d'),date('y'));
            $title = '当日';
            $title_type = '全部';
        }

        $this->assign('title', $title);
        $this->assign('title_type', $title_type);

        $this->assign('time_type', $time_type);

        $repair_type = $_POST['repair_type'];
        $this->assign('repair_type', $repair_type);

        $where_log['check_in'] = array('between', [$start, $end]);
        
        $lists = M('log')->where($where_log)->select();

        // 统计
        $map = ['other'=>0];
        foreach ($lists as $key => $value) {
            $sa_uid = $value['sa_uid'];
            if($sa_uid && $sa[$sa_uid]) {
                if($map[$sa_uid]) {
                    $map[$sa_uid] += 1;
                }else {
                    $map[$sa_uid] = 1;
                }
            }else {
                $map['other'] += 1;
            }
        }

        $result = [];
        $statistics = ['yAxis' => [], 'data' => [], 'ids' => []];
        foreach ($sa as $key => $value) {
            array_push($result, array(
                'id'   => $key,
                'name' => $value,
                'value'=> $map[$key] ?: 0
            ));
        }
        usort($result, function($a, $b) {
            if($a['value'] != $b['value']) {
                return $a['value'] > $b['value'] ? 1 : -1;
            }else {
                if($a['id'] == 'other') {
                    return -1;
                }elseif ($b['id'] == 'other') {
                    return 1;
                }else {
                    return getFirstCharacter($a['name']) > getFirstCharacter($b['name']) ? -1 : 1;
                }
            }
        });

        foreach ($result as $key => $value) {
            array_push($statistics['yAxis'], $value['name']);
            array_push($statistics['data'], $value['value']);
            array_push($statistics['ids'], $value['id']);
        }

        $this->assign('statistics', $statistics);
        $this->assign('total', count($lists));

        $urls = ['today', 'month', 'last'];
        $maps = array(
            'today' => '今天',
            'month' => '本月',
            'last'  => '上一月'
        );
        foreach ($urls as &$value) {
            $item = array(
                'url'  => U('statistics', ['type' => $value]),
                'name' => $maps[$value],
            );
            if($type == $value) {
                $item['class'] = 'current';
            }
            $value = $item;
        }
        $this->assign('urls', $urls);

        $this->display();
        
    }

    /**
     * SA工单列表
     */
    public function saWipList($sa_uid){

        $exe_start_time = time();
        // 权限控制
        $auth = session('user_auth');
        $info = D('User')->find($auth['uid']);
        $usertype = $auth['user_type'];
        $ld_code = $auth['ld_code'];
        $this->assign('usertype',$usertype);
        $dateFilter = I('dateFilter','','string');

        $dateFilterType = I('dateFilterType','start_timestamp','string');
        if($dateFilterType) {
            $dateFilterField = 'benz_log.'.$dateFilterType;
            $this->assign('dateFilterType', $dateFilterType);
        }else {
            $dateFilterField = 'benz_log.start_timestamp';
        }
        

        // tab url 列表
        $type = $_GET['type'] ?: 'normal';
        if($type == 'normal') {
            // $map['benz_log.normal_status'] = array(array('in',['','A','B','D','F','G']),array('EXP','IS NULL'),'OR');
            $map['benz_log.normal_status'] = array('neq', 'I');
        }elseif ($type == 'finished') {
            $map['benz_log.normal_status'] = 'I';
        }
        $urls = array([
            'name' => '未完成',
            'url'  => U('saWipList', ['type' => 'normal','sa_uid' => $sa_uid, 'dateFilter' => $dateFilter, 'dateFilterType' => $dateFilterType]),
            'class'=> $type == 'normal' ? 'current' : ''    
        ],[
            'name' => '已完成',
            'url'  => U('saWipList', ['type' => 'finished', 'sa_uid' => $sa_uid, 'dateFilter' => $dateFilter, 'dateFilterType' => $dateFilterType]),
            'class'=> $type == 'finished' ? 'current' : ''
        ]);
        $this->assign('urls', $urls);
        $this->assign('type', $type);

        // 搜索条件
        $keyword   = I('keyword','','string');
        $condition = array('like','%'.$keyword.'%');
        $map['benz_log.ld_code|benz_log.wip|benz_log.registration_no'] = array($condition, $condition, $condition, '_multi'=>true);
        $map['benz_log.service_type'] = 'N';
        $this->assign('keyword', $keyword);

        // 筛选参数
        $region     = I('region','','string');
        $province   = I('province','','string');
        $city       = I('city','','string');
        $district   = I('district','','string');
        $group      = I('group','','string');
        $service_type = I('service_type');
        $log_status   = I('log_status');
        $start_date = strtotime(I('start_date'));
        $end_date   = strtotime(I('end_date')) + 86399;

        if(!$start_date || !$end_date) {
            $start_date = mktime(0,0,0,date('m'),1,date('y'));
            $end_date   = mktime(23,59,59,date('m'),date('t'),date('y'));
        }

        $this->assign('start_date',date('Y-m-d',$start_date));
        $this->assign('end_date',date('Y-m-d',$end_date));

        if($usertype == 3) {
            $where_region['region_code'] = $info['region_code'];
            $region = $info['region_code'];
        }elseif($usertype == 4) {
            $where_region['region_code'] = $info['region_code'];
            $region = $info['region_code'];
            $province = $info['province_code'];
            $city = $info['city_code'];
            $district = $info['district_id'];
            $group = $info['group_code'];
            $ld_code = $info['ld_code'];
        }elseif ($usertype == 6) {
            $dealer = D('User')->where(['ld_code' => $info['ld_code'], 'user_type' => 4])->find();
            $where_region['region_code'] = $dealer['region_code'];
            $region = $dealer['region_code'];
            $province = $dealer['province_code'];
            $city = $dealer['city_code'];
            $district = $dealer['district_id'];
            $group = $dealer['group_code'];
            $ld_code = $dealer['ld_code'];
        }

        $this->assign('service_type',$service_type);

        if($ld_code) {
            $map['benz_log.ld_code'] = $ld_code;
        }else {
            if($region) {
                $map['benz_user.region_code'] = $region;
            }
            if($province) {
                $map['benz_user.province_code'] = $province;
            }
            if($city) {
                $map['benz_user.city_code'] = $city;
            }
            if($district) {
                $map['benz_user.district_id'] = $district;
            }
            if($group) {
                $map['benz_user.group_code'] = $group;
            }
            if($service_type) {
                $map['benz_log.service_type'] = $service_type;
            }
        }

        // 时间返回筛选
        if($start_date && $end_date) {
            $map[$dateFilterField] = array('between',array($start_date,$end_date));
        }

        // SA筛选列表
        $where_sa = array(
            'user_type' => 6,
            'status' => 1
        );
        if($ld_code) {
            $where_sa['ld_code'] = $ld_code;
        }
        $sales_list = M('user')->field('id,nickname')->where($where_sa)->select();
        $this->assign("sales_list", $sales_list);

        // SA用户筛选
        $sa_uid = I('sa_uid');
        if($sa_uid != 'other') {
            $map['benz_log.sa_uid'] = $sa_uid;
            $this->assign("sa_uid", $sa_uid);
        }else {
            $map['benz_log.is_other'] = 1;
        }
        $this->assign('sa_uid', $sa_uid);

        // 状态筛选
        if($log_status) {
            if($log_status == 'empty') {
                $map['benz_log.normal_status'] = array(array('in',['']),array('EXP','IS NULL'),'OR');
            }else {
                $map['benz_log.normal_status'] = $log_status;
            }
            $this->assign('log_status',$log_status);
        }

        $reserve_status = I('reserve_status','','string');
        if($reserve_status) {
            if($reserve_status == 'yes') {
                $map['benz_log.reserve_status'] = '1';
            }else {
                $map['benz_log.reserve_status'] = array(array('in',['']),array('EXP','IS NULL'),'OR');
            }
            $this->assign('reserve_status', $reserve_status);
        }
        

        // 是否绑定微信参数
        $weixin = I('weixin', '', 'string');
        if($weixin) {
            switch ($weixin) {
                case 'bind':
                    $map['benz_log.openid'] = array(array('neq',''),array('EXP','IS NOT NULL'), 'OR');
                    break;
                case 'unbind':
                    $map['benz_log.openid'] = array(array('eq',''),array('EXP','IS NULL'), 'OR');
                    break;
                default:
                    break;
            }
            $this->assign("weixin", $weixin);
        }

        // 用户类型
        if($usertype == 6) {
            $map['benz_log.sa_uid'] = $auth['uid'];
        }
        
        /**
         * 时间筛选
         */
        $dateFilterMap = getDateFilters();
        $dateFilter   = I('dateFilter','','string');
        if($dateFilter) {
            $map[$dateFilterField] = array('between', $dateFilterMap[$dateFilter]);
            $this->assign('dateFilter', $dateFilter);
        }

        /**
         * 服务类型筛选
         */
        $repair_type = I('repair', '', 'string');
        if($repair_type == '其他') {
            $map['benz_log.log_type'] = array(array('in',['其他','']),array('EXP','IS NULL'),'OR');
        }else if($repair_type != '') {
            $map['benz_log.log_type'] = $repair_type;
        }
        
        /**
         * 排序参数
         */
        $order_type = I('order_type');
        $sort = I('sort');

        if ($_GET['order_type']) {
            $order = $order_type." ".$sort;
            if($sort == 'desc'){
                $sort = 'asc';
            }else{
                $sort = 'desc';
            }
        }else{
            $order = 'check_in asc, start_timestamp asc';
            $sort = 'desc';
        }
        $this->assign('sort', $sort);

        /**
         * 执行筛选
         */
        $sqlst = time();
        if($sa_uid == 'other') {
            $count = M('log')->where($map)->count();
        }else {
            $count = M('log')->field('benz_log.*,benz_user.nickname,benz_user.mobile')->join('left join benz_user on benz_log.sa_uid = benz_user.id')->where($map)->count();
        }
        
        $sqlse = time();

        $_GET['p'] = $_POST['p'] ?: 1;
        $pagenum = C('ADMIN_PAGE_ROWS');

        $index = 1;
        $page = [];
        $total = $count;
        $page_total = ceil($total / $pagenum);
        while($total > 0) {
            $cur = $index;
            $page_item = array(
                'class' => 'num',
                'name'  => $index++
            );
            if($_GET['p'] == $index - 1) {
                $page_item['class'] .= ' current';
            }
            if(abs($_GET['p'] - $cur) < 3 || $cur == 1 || $cur == $page_total) {
                array_push($page, $page_item);
            }
            $total -= $pagenum;
        }

        $this->assign('page',$page);
        $this->assign('page_current', $_GET['p']);
        $this->assign('page_total', $page_total);
        
        $first = ($_GET['p'] - 1) * $pagenum;
        $limit = $first.','.$pagenum;

        $this->assign('first', $first + 1 > $count ? $count : $first + 1);
        $this->assign('last', $first + $pagenum > $count ? $count : $first + $pagenum);
        $this->assign('count', $count);

        if($sa_uid == 'other') {
            $list = M('log')->where($map)->order($order)->limit($limit)->select();
        }else {
            $list = M('log')->field('benz_log.*,benz_user.nickname,benz_user.mobile')->join('left join benz_user on benz_log.sa_uid = benz_user.id')->where($map)->order($order)->limit($limit)->select();
        }

        foreach ($list as $key => &$value) {
            
            if($value['sa_uid']) {
                $sa = M('user')->where(['id' => $value['sa_uid']])->find();
                $value['sa'] = $sa['nickname'];
            }
            $value['timeout'] = getTimeoutStatus($value);
            $value['bind'] = $value['reserve_status'] ? 'bind' : '';
            $value['editable'] = getEditable($value, $usertype);
            $timeMap = getStatusTimeMap($value['wip']);
            $value['a_time'] = $timeMap['A'];
            $value['b_time'] = $timeMap['B'];
            $value['d_time'] = $timeMap['D'];
            $value['g_time'] = $timeMap['G'];
            $value['temporary'] = getTemporaryStatus($value);
        }

        $this->assign('list', $list);
        
        $this->display();

    }

    /**
     * @author 获取工单改变状态
     */
    public function getModifyStatus($status){
        
        $array = array(
            'dispatch_time' => 'A1',
            'complete_time' => 'A2',
            'final_start_time' => 'F1',
            'final_end_time' => 'F2',
            'wash_start_time' => 'F3',
            'wash_end_time' => 'F4',
            );
        return $array[$status]?:false;
    }
}

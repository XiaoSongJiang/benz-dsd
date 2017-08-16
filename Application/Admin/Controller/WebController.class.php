<?php
// +----------------------------------------------------------------------
// | CoreThink [ Simple Efficient Excellent ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.corethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 后台公共控制器
 * @author jry <598821125@qq.com>
 */
class WebController extends Controller{


    /**
     * 数据统计页面
     */
    public function statistics() {

        $auth = session('user_auth');
        if(!$auth) {
            $this->redirect('login');
        }
        $ld_code = $auth['ld_code'];

        $sa = getSalesAgentList($ld_code);
        $sa['other'] = '其他';
        $where_log = array(
            'status' => 1
        );
        if($ld_code) {
            $where_log['ld_code'] = $ld_code;
        }

        $type = I('type','today','string');
        $tips = '';
        $title = '';
        function formatTime($time) {
            $year = date('Y', $time);
            $month = (int)date('m', $time);
            return $year."年".$month."月";
        }
        switch ($type) {
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
            default:
                break;
        }
        
        $this->assign('tips', $tips);
        $this->assign('type', $type);
        $this->assign('title', $title);

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
        $max = -100;
        $statistics = ['yAxis' => [], 'data' => []];
        foreach ($sa as $key => $value) {
            array_push($result, array(
                'id'   => $key,
                'name' => $value,
                'value'=> $map[$key] ?: 0
            ));
            $max = max($max, $map[$key] ?: 0);
        }
        usort($result, function($a, $b) {
            if($a['value'] != $b['value']) {
                return $a['value'] < $b['value'] ? 1 : -1;
            }else {
                if($a['id'] == 'other') {
                    return 1;
                }elseif ($b['id'] == 'other') {
                    return -1;
                }else {
                    return getFirstCharacter($a['name']) > getFirstCharacter($b['name']) ? 1 : -1;
                }
            }
        });
        
        $this->assign('max', $max ?: 10);
        $this->assign('length', count($sa));
        $this->assign('statistics', $result);
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
     * 后台登陆
     * @author jry <598821125@qq.com>
     */
    public function login(){
        
        if(IS_POST){
            $username = I('username');
            $password = I('password');
            
            $map['group_'] = array('egt', 1); //后台部门
            $user_object = D('User');
            $uid = $user_object->login($username, $password, $map);

            if(0 < $uid){
                $this->redirect('Admin/Web/Statistics');
            }else{
                $this->error($user_object->getError());
            }
        }else{

            $this->assign('meta_title', '用户登录');
            $this->assign('__CONTROLLER_NAME__', strtolower(CONTROLLER_NAME)); //当前控制器名称
            $this->assign('__ACTION_NAME__', strtolower(ACTION_NAME)); //当前方法名称
            $this->display();
        }
    }

}

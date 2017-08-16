<?php
// +----------------------------------------------------------------------
// | CoreThink [ Simple Efficient Excellent ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.corethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\CommonController;
/**
 * 后台公共控制器
 * 为什么要继承AdminController？
 * 因为AdminController的初始化函数中读取了顶部导航栏和左侧的菜单，
 * 如果不继承的话，只能复制AdminController中的代码来读取导航栏和左侧的菜单。
 * 这样做会导致一个问题就是当AdminController被官方修改后AdminController不会同步更新，从而导致错误。
 * 所以综合考虑还是继承比较好。
 * @author jry <598821125@qq.com>
 */
class AdminController extends CommonController{
    /**
     * 初始化方法
     * @author jry <598821125@qq.com>
     */
    protected function _initialize(){

        // redirect('https://www.baidu.com');
        $referer = $_SERVER['HTTP_REFERER'];
        $info = parse_url($referer);
        $host = $info['host'];
        if($host && !in_array($host, ['oab.mercedes-benz.com.cn','dsd.mercedes-benz.com.cn'])) {
            // die;
        }        

        $allows = ['User','Normal','Public','Common','Admin','Index','Benz','Config'];
        if(!in_array(CONTROLLER_NAME, $allows)) {
            echo "DENY";
            die;
        }

        //登录检测
        if(!is_login()){ //还没登录跳转到登录页面
            $this->redirect('Admin/Public/login');
        }

        $auth = session('user_auth');
        $user_type = (int)$auth['user_type'];
        $user_auth = $auth['auth'];

        if(!$this->checkAuth($user_type)) {
            echo '<pre>';
            var_dump('没有权限访问');
            die;
        }

        $user = M('user')->where(['username' => $auth['username']])->find();
        $this->assign('username', $user['username']);
        $this->assign('password', $user['password']);

        // OAB登录权限
        if(in_array('OAB', explode(',', $user_auth))) {
            $this->assign('username', $auth['username']);
            $this->assign('login_auth', 'OAB');
        }
        //权限检测
        if($user_type != 1) {
            // 判断是否有DOS登录权限
            $user_auth = explode(',', $user_auth);
            if(!in_array('DOS', $user_auth)) {
                // 跳转到OAB网站
                if(CONTROLLER_NAME.'/'.ACTION_NAME == 'User/password') {
                    $hidden = 'hidden';
                }else {
                    $this->assign('autoLogin', 'OAB');
                }
            }
        }

        if($user_type != 11){
            $menulist = array(array(
                'img' => 'order.png',
                'href'=> U('Admin/Normal/Index', ['type' => 'normal']),
                'name'=> '工单列表',
                'control'=>'Normal/index'
            ));
        }else {
            $menulist = [];
        }
        if(in_array($user_type, [1, 7, 8, 10])) {
            array_push($menulist, array(
                'img' => 'data.png',
                'href'=> U('Admin/Normal/Statistics', ['type' => 'today']),
                'name'=> '数据统计',
                'control'=>'Normal/statistics'
            ));
        }
        if($user_type == 1 || $user_type == 11) {
            array_push($menulist, array(
                'img' => 'user_m.png',
                'href'=> U('Admin/User/Index'),
                'name'=> '用户列表',
                'control'=>'User/index'
            ));
        }

        if(in_array($user_type, [7, 8, 9])) {
            array_push($menulist, array(
                'img' => 'config.jpg',
                'href'=> U('Admin/Config/pageConfig'),
                'name'=> '客户界面设置',
                'control'=>'Config/pageconfig'
            ));
            array_push($menulist, array(
                'img' => 'config.jpg',
                'href'=> U('Admin/Config/pushConfig'),
                'name'=> '推送消息设置',
                'control'=>'Config/pushconfig'
            ));
        }

        // if($user_type == 15 || $user_type == 16) {
        //     array_push($menulist, array(
        //         'img' => 'order.png',
        //         'href'=> U('Admin/Normal/deliver'),
        //         'name'=> '已关钟列表',
        //         'control'=>'Normal/deliver'
        //     ));
        // }

        array_push($menulist, array(
            'img' => 'code_icon.png',
            'href'=> U('Admin/User/Qrcode'),
            'name'=> '绑定微信二维码',
            'control'=>'User/qrcode'
        ));
        
        if($user_type == 1) {
            array_push($menulist, array(
                'img' => 'user_m.png',
                'href'=> U('Admin/Benz/Index'),
                'name'=> '导入经销商',
                'control'=>'Benz/index'
            ));
        }else {

        }
        
        foreach ($menulist as &$menu) {
            $name = CONTROLLER_NAME.'/'.ACTION_NAME;
            if($name == $menu['control']) {
                $menu['class'] = 'current';
            }
        }

        if($hidden == 'hidden') {
            $menulist = [];
        }

        $this->assign("menulist", $menulist);

        // 经销商信息
        $dealer = M('user')->where(array(
            'ld_code' => $auth['ld_code'],
            'user_type' => 4
        ))->find();
        if($dealer) {
            $this->assign('dealer_name', $dealer['dealer_name']);
        }

        // 本月时间
        $this->assign('month', date('Y年n月'));

        //获取系统菜单导航
        $map['status'] = array('eq', 1);

        // 获取当前权限对应系统菜单
        $user_group = (int)D('User')->getFieldById(session('user_auth.uid'), 'group_');
        // 管理员拥有所有权限
        if($user_group != 1) {
            $group_info = D('UserGroup')->find($user_group);
            $group_info['menu_auth'] = explode(',', $group_info['menu_auth']);
            $group_info['menu_auth'] = $group_info['menu_auth'] ? : session('menu_auth');
            $group_auth = explode(',', $group_info['menu_auth']);
            foreach ($group_auth as $key => &$value) {
                $value = (int)$value;
            }
            $map['id'] = array('in',$group_auth);
        }
        
        $this->assign('OABLOGIN', Config('OABLOGIN'));

        $this->assign('__USER__', session('user_auth')); //用户登录信息
        $this->assign('__CONTROLLER_NAME__', strtolower(CONTROLLER_NAME)); //当前控制器名称
        $this->assign('__ACTION_NAME__', strtolower(ACTION_NAME)); //当前方法名称
    }

    /**
     * 检查权限
     */
    private function checkAuth($user_type) {
        if($user_type == 1) {
            return true;
        }
        $maps = array(
            'User/index' => array(
                'allow' => [11]
            ),
            'User/add' => array(
                'allow' => [11]
            ),
            'User/modify' => array(
                'allow'=> [11]
            ),
            'Normal/index'=> array(
                'deny'=> [11,14,15]
            ),
            'Normal/statistics'=> array(
                'allow'=> [6,7,8,10]
            ),
            'Config/pageconfig' => array(
                'allow' => [7,8,9]
            ),
            'Config/pushconfig' => array(
                'allow' => [7,8,9]
            ),
        );
        $modules = ['User/index', 'User/add', 'User/modify', 'Normal/index', 'Normal/statistics', 'Config/pageconfig', 'Config/pushconfig'];
        $current = CONTROLLER_NAME.'/'.ACTION_NAME;

        if(!in_array($current, $modules)) {
            return true;
        }
        $auth = $maps[$current];
        if(isset($auth['allow'])) {
            $allow = $auth['allow'];
            return in_array($user_type, $allow);
        }
        if(isset($auth['deny'])) {
            $deny = $auth['deny'];
            return !in_array($user_type, $deny);
        }
        return true;
    }
}

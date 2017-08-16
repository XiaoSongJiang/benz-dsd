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
class PublicController extends Controller{
    /**
     * 后台登陆
     * @author jry <598821125@qq.com>
     */
    public function login(){
        
        // echo user_md5('SOUTH_EP');
        if(IS_POST){
            $username = I('username');
            $password = I('password');
            
            $map['group_'] = array('egt', 1); //后台部门
            $uid = D('User')->login($username, $password, $map);

            if(0 < $uid){
                $auth = session('user_auth');
                if($auth['user_type'] == 11) {
                    $this->redirect('Admin/User/index');
                }else {
                    $this->redirect('Admin/Normal/index', ['type' => 'normal']);
                }
            }else {
                $this->error(D('User')->getError());
            }
        }else{

            $this->assign('meta_title', '用户登录');
            $this->assign('__CONTROLLER_NAME__', strtolower(CONTROLLER_NAME)); //当前控制器名称
            $this->assign('__ACTION_NAME__', strtolower(ACTION_NAME)); //当前方法名称
            $this->display();
        }
    }


    public function OABlogin(){
        
        if(IS_POST){
            $username = I('username');
            $password = I('password');
            
            $user = M('user')->where(array(
                'username' => $username,
                'password' => $password,
                'status'   => 1
            ))->find();

            if($user){
                $auth = array(
                    'uid'             => $user['id'],
                    'username'        => $user['username'],
                    'avatar'          => $user['avatar'],
                    'ld_code'         => $user['ld_code'],
                    'user_type'       => $user['user_type'],
                    'auth'            => $user['auth']
                );
                session('user_auth', $auth);
                session("user_auth_sign", D('User')->dataAuthSign($auth));
                if(I('redirect') == 'password') {
                    $this->redirect('Admin/User/password');
                }
                if($auth['user_type'] == 11) {
                    $this->redirect('Admin/User/index');
                }else {
                    $this->redirect('Admin/Normal/index', ['type' => 'normal']);
                }
            }else{
                $this->error(D('User')->getError());
            }
        }else{
            $this->assign('meta_title', '用户登录');
            $this->assign('__CONTROLLER_NAME__', strtolower(CONTROLLER_NAME)); //当前控制器名称
            $this->assign('__ACTION_NAME__', strtolower(ACTION_NAME)); //当前方法名称
            $this->display('login');
        }
    }

    /**
     * 注销
     * @author jry <598821125@qq.com>
     */
    public function logout(){
        session('user_auth', null);
        session('user_auth_sign', null);
        $this->success('退出成功！', U('Public/login'));
    }

    /**
     * 图片验证码生成，用于登录和注册
     * @author jry <598821125@qq.com>
     */
    public function verify($vid = 1){
        $verify = new \Think\Verify();
        $verify->length = 4;
        $verify->entry($vid);
    }
    
    /**
     * 检测验证码
     * @param  integer $id 验证码ID
     * @return boolean 检测结果
     */
    function check_verify($code, $vid = 1){
        $verify = new \Think\Verify();
        return $verify->check($code, $vid);
    }

    /**
     * 
     */
    function map() {
        $this->display();
    }
}

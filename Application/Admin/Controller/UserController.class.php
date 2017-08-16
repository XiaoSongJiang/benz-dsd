<?php
// +----------------------------------------------------------------------
// | CoreThink [ Simple Efficient Excellent ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.corethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 后台用户控制器 
 * @author gaoyu <540846283@qq.com>
 */
define('ROOT',dirname(__FILE__).'/');  

class UserController extends AdminController {

    /**
     * 用户列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 搜索
        $auth = session('user_auth');
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['username|nickname|sa_code|mobile'] = array($condition, $condition, $condition, $condition, '_multi'=>true);
        if($keyword) {
            $this->assign("keyword", $keyword);
        }


        $roles = [6,7,8,9,10,11,12,13,15,16];
        if($auth['user_type'] == 1) {
            array_push($roles, 14);
        }
        $map['group_'] = array('in', $roles);
        // 获取所有用户
        $map['status'] = array('egt', '0'); //禁用和正常状态

        
        if($auth) {
            if($auth['user_type'] != '1') {
                $this->assign('disabled', 'disabled');
                $this->assign("ld_code", $auth['ld_code']);
                $map['ld_code'] = $auth['ld_code'];
            }
        }

        // 角色筛选
        if($_POST['user_type']) {
            $map['user_type'] = $_POST['user_type'];
            $this->assign('user_type', $_POST['user_type']);
        }

        // 经销商筛选
        $dealer_list = M('user')->field('ld_code,dealer_short')->cache(true)->where(['user_type' => 4])->order('ld_code asc')->select();
        $this->assign('dealer_list', $dealer_list);
        if($_POST['ld_code']) {
            $map['ld_code'] = $_POST['ld_code'];
            $this->assign('ld_code', $_POST['ld_code']);
        }

        // 总数
        $count = M('User')->where($map)->count();

        // 分页处理
        $_GET['p'] = $_POST['p'] ?: 1;
        $index = 1;
        $page = [];
        $pagenum = C('ADMIN_PAGE_ROWS');
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

        $userlist = M('user')->limit($limit)->where($map)->order('id asc')->select();

        foreach ($userlist as $key => &$value) {
            if($_GET['p']) {
                $value['serial'] = $count - $key - ($_GET['p']-1)*$pagenum;
            }else {
                $value['serial'] = $count - $key;
            }
            // $value['user_type'] = getUserType($value['user_type']);
            // 查找微信姓名
            if($value['openid']) {
                $client = M("client")->where(['openid' => $value['openid']])->find();
                $value['weixin_name'] = $client['nickname'];
            }
            $value['role_name'] = getUserRoleName($value['user_type']);
        }

        session('filter', $_POST ?: $_GET);
        $this->assign("pagenum", $_GET['p']);
        $this->assign('userlist', $userlist);

        $this->display();
    }

    /**
     * 同步信息到OAB
     */
    protected function syncToOAB($user, $operation) {
        
        return true;
        $user = $user ?: $_POST;

        $OABRoleMap = array(
            '11' => 2,      // Dealer Admin
            '9'  => 3,      // SR
            '6'  => 4,      // SA
            '13' => 5,      // CDO
            '14' => 6,      // DM
            '15' => 7,      // 留修专员
            '10' => 8       // Dealer车间调度
        );
        $role_id = $OABRoleMap[$user['user_type']] ?: 123;

        $postData = array(
            'name'     => $user['nickname'],
            'password' => $user['password'],
            'operation'=> $operation ?: 'new',
            'dealer'   => $user['ld_code'],
            'user_id'  => $user['username'],
            'role_id'  => $role_id,
            'ur_id'    => '666',
            'openid'   => $user['openid']
            // 'phone'    => $user['mobile'],
        );

        if($user['user_type'] == 6) {
            $postData['sa_code'] = $user['sa_code'];
        }
        if($user['user_type'] == 14) {
            $postData['region'] = $user['region_code'];
        }

        $postData['phone'] = $user['mobile'];

        $postString = "";
        foreach ($postData as $key => $value) {
            $postString .= $key.'='.urlencode($value);
            if($key != 'phone') {
                $postString .= '&';
            }
        }

        // 初始化
        $curl = curl_init();
        // 设置选项，包括URL
        $proxy='http://53.90.130.51:3128';
        curl_setopt($curl, CURLOPT_PROXY, $proxy);

        // $url = 'https://oabint1.mercedes-benz.com.cn/oab/useritfserver/synUserAndUR';
        $url = Config('OABSYNC');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true); // 发送方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 执行
        $output = curl_exec($curl);
        return (array)json_encode($output);
    }

    /**
     * 新增用户
     * @author gaoyu <540846283@qq.com>
     */
    public function add(){

        $usertype = $_GET['usertype'];
        $auth = session('user_auth');

        if(IS_POST){

            $userinfo = $_POST;

            foreach ($userinfo as $key => &$value) {
                if($key != 'auth') {
                    $value = htmlspecialchars($value);
                }
            }

            //过滤掉用户名两端空格
            $userinfo['username'] = trim($userinfo['username']);

            if($auth['user_type'] != 1) {
                $userinfo['ld_code'] = $auth['ld_code'];
                // 只有管理员可以新增DM
                if($userinfo['user_type'] == 14) {
                    $this->error('非管理员没有权限新增DM角色', U('index'));
                }
            }

            // 判断是否已经存在
            $exists = M('user')->where(['username' => $userinfo['username']])->find();
            if($exists) {
                $this->error('该用户名已存在', U('index'));
            }

            if($_FILES['avatar']['tmp_name']) {
                $upload = new \Think\Upload();
                $upload->maxSize   =     3145728 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $info = $upload->upload();
                if($info) {
                    $avatar = $info['avatar'];
                    $userinfo['avatar'] = 'Uploads/'.$avatar['savepath'].$avatar['savename'];
                }else {
                    $this->error($upload->getError());
                }
            }
            
            $userinfo['password'] = user_md5($userinfo['password']);
            $userinfo['auth'] = implode(",", $userinfo['auth']);
            $userinfo['group_'] = $userinfo['user_type'];
            $userinfo['status'] = 1;
            $uid = M('user')->add($userinfo);

            // $userinfo = M('user')->where(['id' => $uid])->find();
            $resNew = $this->syncToOAB($userinfo, 'new');
            if($uid) {
                $this->success('新增用户成功', U('index'));
            }else {
                $this->error('新增用户失败', U('index'));
            }
        }else{
            
            if($auth['user_type'] == 1) {
                // 经销商筛选
                $this->assign('admin', 'admin');
            }
            $this->display();
        }
    }

    public function exists() {

        $where['username'] = I('username', '', 'string');
        $user = M('user')->where($where)->find();

        if($user) {
            $this->success('exists');
        }else {
            $this->success('ok');
        }
    }

    public function insert_role() {
        $list = array(
            6 => 'SA',
            7 => 'SAM',
            8 => 'ASM',
            9 => 'SR',
            10 => '调度',
            11 => '经销商管理员',
            12 => '终检员',
            13 => 'CDO',
            14 => 'DM',
            15 => '留修专员',
            16 => '洗车员'
        );
        foreach ($list as $type => $name) {
            $where['type'] = $type;
            $role = M('role')->where($where)->find();
            if(!$role) {
                M('role')->add(array(
                    'type' => $type,
                    'name' => $name
                ));
            }
        }
        echo '<pre>';
        var_dump(M('role')->select());
    }

    /**
     * 填充工单类型
     */
    public function update_log_type() {
        $save['log_type'] = '其他';
        $where['benz_log.log_type'] = array(array('in',['']),array('EXP','IS NULL'),'OR');

        $res = M('log')->where($where)->save($save);
        echo '<pre>';
        var_dump(M('log')->where($where)->count());
    }

    /**
     * 修改用户信息
     */
    public function modify($id) {

        $userinfo = M('user')->where(['id' => $id])->find();
        $auth = session('user_auth');
        if(!$this->checkAuth($userinfo)) {
            echo '<pre>';
            var_dump("没有权限修改");
            die;
        }
        
        if(IS_POST){

            foreach ($_POST as $key => &$value) {
                if($key != 'auth') {
                    $value = htmlspecialchars($value);
                }
            }

            if($auth['user_type'] != 1) {
                $_POST['ld_code'] = $auth['ld_code'];
                if($_POST['user_type'] == 14) {
                    $this->error('非管理员没有权限修改DM角色', U('index'));
                }
            }
            $upload = new \Think\Upload();
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
            $upload->savePath  =     ''; // 设置附件上传（子）目录
            $info = $upload->upload();
            if($info) {
                $avatar = $info['avatar'];
                $_POST['avatar'] = 'Uploads/'.$avatar['savepath'].$avatar['savename'];
            } 
            
            $_POST['auth'] = implode(",", $_POST['auth']);
            $_POST['group_'] = $_POST['user_type'];
            // echo '<pre>';
            // var_dump($_POST);
            // die;
            $user_object = D('User');
            //不修改密码时销毁变量
            if($_POST['password'] == '' || $userinfo['password'] == $_POST['password']){
                unset($_POST['password']);
            }else{
                $_POST['password'] = user_md5($_POST['password']);
            }
            //不允许更改超级管理员用户组
            if($_POST['id'] == 1){
                unset($_POST['group_']);
            }
            if($_POST['extend']){
                $_POST['extend'] = json_encode($_POST['extend']);
            }
            // 不允许修改用户名
            unset($_POST['username']);
           
            if($user_object->save($_POST)){
                $userinfo = M('user')->where(['id' => $id])->find();
                $syncRes = $this->syncToOAB($userinfo, 'update');
                if(!$syncRes['success']) {
                    $syncNew = $this->syncToOAB($userinfo, 'new');
                }

                // 修改关联订单是类型
                if($userinfo['user_type'] == 6) {
                    M('log')->where(['sa_uid' => $userinfo['id']])->save(['is_other' => 0]);
                }else {
                    M('log')->where(['sa_uid' => $userinfo['id']])->save(['is_other' => 1]);
                }

                $this->success('更新成功', U('index', session('filter')));
            }else{
                $this->success('保存成功', U('index'));
            }
        }else{
            $user_object = D('User');
            $info = $user_object->find($id);
            $usertype = $info['user_type'];

            if($info['auth']) {
                $auth = explode(",", $info['auth']);
                foreach ($auth as $value) {
                    $map[$value] = true;
                }
                $this->assign('auth', $map);
            }

            $auth = session('user_auth');
            if($auth['user_type'] == 1) {
                // 经销商填写
                $this->assign('admin', 'admin');
            }
            // 拼接域名
            if($info['avatar']) {
                $info['avatar'] = Config('DOMAIN').$info['avatar'];                
            }
            unset($info['password']);
            $this->assign('user', $info);
            $this->display('edit');
        }
    }

    /**
     * 检查用户是否有修改权限
     */
    private function checkAuth($info) {
        $auth = session('user_auth');
        $user_type = $auth['user_type'];
        if($user_type == 1) {
            return true;
        }
        if($user_type == 11) {
            if($auth['ld_code'] == $info['ld_code']) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 解绑微信
     */
    public function unbind($id) {

        $where['id'] = $id;
        M('user')->where($where)->save(['openid' => '']);

        // 同步修改用户openid
        $userinfo = M('user')->where($where)->find();
        $this->syncToOAB($userinfo, 'update');

        $this->success($id);
    }

    /**
     * 禁用用户
     */
    public function freeze($id) {

        $where['id'] = $id;
        M('user')->where($where)->save(['status' => 0]);

        M('log')->where(['sa_uid' => $id])->save(['is_other' => 1]);

        $this->success($id);
    }

    /**
     * 恢复用户
     */
    public function revert($id) {

        $where['id'] = $id;
        M('user')->where($where)->save(['status' => 1]);

        M('log')->where(['sa_uid' => $id])->save(['is_other' => 0]);

        $this->success($id);
    }

    public function upload($avatar) {

        $upload = new \Think\Upload();
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
        $upload->savePath  =     ''; // 设置附件上传（子）目录
        $info = $upload->upload();
        if($info) {
            $avatar = $info['avatar'];
            $res['avatar'] = 'Uploads/'.$avatar['savepath'].$avatar['savename'];
            echo json_encode($res);
            return;
        } 
    }

    /**
     * 编辑用户
     * @author gaoyu <540846283@qq.com>
     */
    public function edit($id){
        //获取用户信息
        $info = D('User')->find($id);
        // var_dump($info);die;
        if(IS_POST){

            unset($_POST['province']);
            unset($_POST['city']);
            unset($_POST['district']);
            $user_object = D('User');
            //不修改密码时销毁变量
            if($_POST['password'] == '' || $info['password'] == $_POST['password']){
                unset($_POST['password']);
            }else{
                $_POST['password'] = user_md5(strtoupper($_POST['password']));
            }
            //不允许更改超级管理员用户组
            if($_POST['id'] == 1){
                unset($_POST['group_']);
            }
            if($_POST['extend']){
                $_POST['extend'] = json_encode($_POST['extend']);
            }
            // Header("Content-Type:text/html;charset=utf-8");
            // var_dump($_POST);
            // M('User')->where(['id' => $id])->save($_POST);
            // die;
            if($user_object->save($_POST)){
                $this->success('更新成功', U('index'));
            }else{
                $this->error('更新失败', $user_object->getError());
            }
        }else{
            $user_object = D('User');
            $info = $user_object->find($id);
            $usertype = $info['user_type'];

            //使用FormBuilder快速建立表单页面
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('编辑用户') //设置页面标题
                    ->setPostUrl(U('edit')) //设置表单提交地址
                    ->addFormItem('id', 'hidden', 'ID', 'ID')
                    ->addFormItem('username', 'text', '用户名', 'Username')
                    ->addFormItem('password', 'password', '密码', 'Password')
                    ->addFormItem('user_type', 'hidden', '用户类型', 'User Type')
                    ->addFormItem('group_', 'hidden', '用户权限组', 'Group')
                    ->setFormData($info);

            switch ($usertype) {
                case 3:
                    $builder->addFormItem('region_code','radio','所属大区','Region Code',getRegionCode());
                    break;
                case 4:
                    $builder->addFormItem('region_code','radio','所属大区','Region Code',getRegionCode())
                            ->addFormItem('ld_code', 'text', '经销商代码', 'Local Dealer Code')
                            ->addFormItem('province', 'text', '省/直辖市', 'Province')
                            ->addFormItem('city', 'text', '市/区', 'City')
                            ->addFormItem('district', 'text', '区域经理姓名', 'District Manager Name')
                            ->addFormItem('group_code', 'text', '群组代码', 'Group Code')
                            ->addFormItem('dealer_name', 'text', '经销商名称', 'Dealer Name')
                            ->addFormItem('dealer_name_en', 'text', '经销商名称(英文)', 'Dealer Name(English)')
                            ->addFormItem('dealer_short', 'text', '经销商缩略名', 'Dealer Short Name')
                            ->addFormItem('dealer_short_en', 'text', '经销商缩略名(英文)', 'Dealer Short Name(English)');
                    break;
            }
            $builder->display();
        }
    }

    /**
     * 总部用户
     */
    public function head() {

        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['username'] = array($condition);

        $map['user_type'] = 2;

        // 获取所有用户
        $map['status'] = array('egt', '0'); //禁用和正常状态
        $users = D('User')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->order('id asc')->select();
        $page = new \Common\Util\Page(D('User')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('总部用户列表') //设置页面标题
                ->setSearch('请输入用户名', U('index'))
                ->addTableColumn('id', 'UserID')
                ->addTableColumn('username', '用户名')
                ->addTableColumn('status', '状态', 'status')
                ->setTableDataList($users) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->display();
    }

    /**
     * 大区用户
     */
    public function region() {

        $auth = session('user_auth');
        $id = $auth['uid'];
        //获取用户信息
        $info = D('User')->find($id);

        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['username'] = array($condition);
        if($info['user_type'] == 3) {
            $map['region_code'] = $info['region_code'];
        }
        $map['user_type'] = 3;

        // 获取所有用户
        $map['status'] = array('egt', '0'); //禁用和正常状态
        $users = D('User')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->order('id asc')->select();
        $page = new \Common\Util\Page(D('User')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('大区用户列表') //设置页面标题
                ->setSearch('请输入用户名', U('region'))
                ->addTableColumn('id', 'UserID')
                ->addTableColumn('username', '用户名')
                ->addTableColumn('status', '状态', 'status')
                ->setTableDataList($users) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->display();
    }

    /**
     * 经销商
     */
    public function dealer() {

        $auth = session('user_auth');
        $id = $auth['uid'];
        //获取用户信息
        $info = D('User')->find($id);

        // 搜索
        $keyword = I('keyword', '', 'string');

        $map['username'] = array('like','%'.$keyword.'%');
        if($info['user_type'] == 3 || $info['user_type'] == 4) {
            $map['region_code'] = $info['region_code'];     
        }
        $map['user_type'] = 4;

        // 获取所有用户
        $map['status'] = array('egt', '0'); //禁用和正常状态
        $users = D('User')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->order('id asc')->select();
        $page = new \Common\Util\Page(D('User')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        foreach ($users as &$value) {
            $value['manager'] = getManager($value['district_id']);
            $value['province'] = getCityName($value['province_code']);
            $value['city'] = getCityName($value['city_code']);
        }
        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('经销商用户列表') //设置页面标题
                ->setSearch('请输入用户名', U('dealer'))
                ->addTableColumn('id', 'UserID')
                ->addTableColumn('username', '用户名')
                ->addTableColumn('region_code','大区代码')
                ->addTableColumn('ld_code','经销商代码')
                ->addTableColumn('manager','区域经理')
                ->addTableColumn('group_code','群组')
                ->addTableColumn('province','省/直辖市')
                ->addTableColumn('city','市/区')
                ->addTableColumn('status', '状态', 'status')
                ->setTableDataList($users) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->display();
    }

    /**
     * 查看个人信息
     */
    public function view() {

        $this->display();
    }

    /**
     * 修改用户密码
     */
    public function password() {

        $auth = session('user_auth');
        $id = $auth['uid'];
        //获取用户信息
        $info = D('User')->where(['id' => $id])->find();

        if(IS_POST){

            $object = D('user');
            if(user_md5($_POST['user_password']) != $info['password']) {
                $this->error('原密码输入错误！');
            }
            if($_POST['new_password'] != $_POST['re_password']) {
                $this->error('两次密码输入不相同！');
            }

            $save['id'] = $id;
            $save['password'] = user_md5($_POST['new_password']);
            if($object->save($save)) {
                $userinfo = M('user')->where(['id' => $id])->find();
                $this->syncToOAB($userinfo, 'update');
                session('user_auth', null);
                session('user_auth_sign', null);
                $this->success('修改成功，请重新登陆！', U('Public/login'));
            }else {
                $this->error('修改密码失败！');
            }
            
        }else{
            $this->assign('username', $auth['username']);
            $this->display();
        }
    }

    public function qrcode() {
        
        $this -> display();
    }

}

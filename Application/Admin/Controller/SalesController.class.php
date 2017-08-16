<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 销售助理
 * @author gaoyu <540846283@qq.com>
 */
class SalesController extends AdminController{

    /**
     * 销售助理列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 权限控制
        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);
        $usertype = $info['user_type'];

        // 经销商
        if($usertype == 4) {
            $dealer = D('User')->find($id);
            $map['ld_code'] = $dealer['ld_code'];
        }

        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['username'] = array($condition);

        $map['user_type'] = 6;

        // 获取所有用户
        $map['status'] = array('egt', '0'); //禁用和正常状态
        $users = D('User')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->order('id asc')->select();
        $page = new \Common\Util\Page(D('User')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        $add_sales = array(
            'title'=> '新增SA',
            'class'=> 'btn btn-primary',
            'href'=> U('add',array('usertype'=>6))
        );

        // 自定义解绑功能
        $unbind['title'] = '解绑微信';
        $unbind['class'] = 'label label-danger ajax-get confirm';
        $unbind['href']  = U('Admin/Sales/unbind', array('id' => '__data_id__'));

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('销售助理列表') //设置页面标题
                ->addTopButton('self', $add_sales)
                ->setSearch('请输入用户名', U('index'))
                ->addTableColumn('id', 'UserID')
                ->addTableColumn('ld_code', '经销商代码')
                ->addTableColumn('username', '用户名')
                ->addTableColumn('nickname', 'SA姓名')
                ->addTableColumn('openid', '微信绑定openid')
                ->addTableColumn('status', '状态', 'status')
                ->addTableColumn('right_button', '操作', 'btn')
                ->setTableDataList($users) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->addRightButton('self', $unbind)   //添加编辑按钮
                ->addRightButton('edit')
                ->display();
    }

    /**
     * 新增销售助理
     * @author gaoyu <540846283@qq.com>
     */
    public function add(){
	
        // 权限控制
        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);
        $usertype = $info['user_type'];

        // 经销商
        if($usertype == 4) {
            $dealer = D('User')->find($id);
            $prefix = $dealer['ld_code'];
        }
        if(IS_POST){
            $user_object = D('User');

            $data = $_POST;
            $data['user_type'] = 6;
            $data['group_'] = 6;
            if($prefix) {
                $data['username'] = $prefix.$data['username'];
                $data['ld_code'] = $prefix;
            }
            //  $data = $user_object->create($data);
			$data['summary'] = 'sales';
			$data['realname'] = 'sales';
			$data['status'] = 1;
			$data['password'] = user_md5($data['password']);
            if($data){
                $id = $user_object->add($data);
                if($id){
                    $this->success('新增成功', U('index'));
                }else{
                    $this->error('新增失败');
                }
            }else{
                $this->error($user_object->getError());
            }
        }else{

            $user_object = D('User');

            $info['user_type'] = 6;
            $info['group_'] = 6;
            //使用FormBuilder快速建立表单页面
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('新增用户') //设置页面标题
                    ->setPostUrl(U('add')) //设置表单提交地址
                    ->addFormItem('username', 'text', '用户名', 'Username')
                    ->addFormItem('password', 'password', '密码', 'Password')
                    ->addFormItem('nickname', 'text', 'SA姓名', 'name')
                    ->addFormItem('sa_code', 'text', 'SA编码');

            if(!$prefix) {
                $builder->addFormItem('ld_code', 'text', '经销商代码', 'dealer code');
            }

            $builder->setFormData($info);
            $builder->display();
        }
    }

    /**
     * 编辑用户
     * @author gaoyu <540846283@qq.com>
     */
    public function edit($id){
        //获取用户信息
        $info = D('User')->find($id);

        if(IS_POST){
            $user_object = D('User');
            //不修改密码时销毁变量
            if($_POST['password'] == '' || $info['password'] == $_POST['password']){
                unset($_POST['password']);
            }else{
                $_POST['password'] = user_md5($_POST['password']);
            }
            //不允许更改超级管理员用户组
            if($_POST['id'] == 1){
                unset($_POST['group']);
            }
            if($_POST['extend']){
                $_POST['extend'] = json_encode($_POST['extend']);
            }
            if($user_object->save($_POST)){
                $this->success('更新成功', U('index'));
            }else{
                $this->error('更新失败', $user_object->getError());
            }
        }else{

            $user_object = D('User');
            $info = $user_object->find($id);

            //使用FormBuilder快速建立表单页面
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('编辑用户') //设置页面标题
                    ->setPostUrl(U('edit')) //设置表单提交地址
                    ->addFormItem('id', 'hidden', 'ID', 'ID')
                    ->addFormItem('username', 'text', '用户名', 'Username', '', '', "disabled=disabled")
                    ->addFormItem('password', 'password', '密码', 'Password')
                    ->addFormItem('sa_code', 'text', 'SA编码')
                    ->setFormData($info);

            $builder->display();
        }
    }

    /**
     * 解绑用户微信绑定Openid
     */
    public function unbind($id) {
        if($id) {
            $where_user = ['id' => $id];
            $save_unbind = array(
                'openid' => ''
            );
            D("User")->where($where_user)->save($save_unbind);
            $this->success('解绑成功', U('index'));
        }else {
            $this->error('解绑失败', U('index'));
        }
    }

    /**
     * 查看SA详情
     */
    public function view($id) {

    }

}

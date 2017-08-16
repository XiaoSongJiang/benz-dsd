<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 客户
 * @author gaoyu <540846283@qq.com>
 */
class ConfigController extends AdminController{

    /**
     * 客户列表
     * @author zhaoning
     */
    public function pageConfig(){

        $ld_code = session()['user_auth']['ld_code'];

        if (IS_POST) {
// dump($_POST);die;
            $str = implode($_POST['c_value'], ",");
            $where['ld_code'] = $ld_code;
            $where['type'] = 'page';
            $find = M('config')->where($where)->find();

            $save = array();
            $save['ld_code'] = $ld_code;
            $save['type'] = $_POST['type'];
            $save['c_type'] = 1;
            $save['c_value'] = $str;
            $save['c_status'] = 1;
            // dump($str);die;
            if($find){
                $res = M('config')->where($where)->save($save);
            }else{
                $res = M('config')->add($save);
            }
            if($res){
                $this->success('操作成功');
            }else{
                $this->success('操作失败');
            }

        }else{

            $data['ld_code'] = $ld_code;
            $data['type'] = 'page';

            $res = D('Config')->getConfig($data);
            $config = array();
            foreach ($res['c_value'] as $key => $val) {
                $config[$val] = $val;
                $this->assign("$val",$val);
            }

            $this->display();

        }
    }

    public function pushConfig(){

       $ld_code = session()['user_auth']['ld_code'];

        if (IS_POST) {
// dump($_POST);die;
            $str = implode($_POST['c_value'], ",");
            $where['ld_code'] = $ld_code;
            $where['type'] = 'push';
            $find = M('config')->where($where)->find();

            // 开关设置
            $_POST['c_status'] = $_POST['c_status'] == 'on';

            $save = array();
            $save['ld_code'] = $ld_code;
            $save['type'] = $_POST['type'];
            $save['c_type'] = $_POST['c_type'];
            $save['c_value'] = $str;
            $save['c_status'] = $_POST['c_status'];

            
            if($find){
                $res = M('config')->where($where)->save($save);
            }else{
                $res = M('config')->add($save);
            }
            if($res){
                $this->success('操作成功');
            }else{
                $this->success('操作失败');
            }

        }else{

            $data['ld_code'] = $ld_code;
            $data['type'] = 'push';

            $res = D('Config')->getConfig($data);

            $config = array();
            foreach ($res['c_value'] as $key => $val) {
                $config[$val] = $val;
                $this->assign("$val",$val);
            }
            // echo '<pre>';
            // var_dump($res);die;
            $this->assign("c_status",$res['c_status']);
            $this->assign("c_type",$res['c_type']);
            $this->display();

        }

    }

    public function test(){
        $res = D('Config')->checkPushAuth($data);
        dump($res);die;
    }

}

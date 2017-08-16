<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 抽奖设置
 * @author gaoyu <540846283@qq.com>
 */
class LotteryConfigController extends AdminController{

    /**
     * 抽奖设置
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 搜索
        // $map['config_status'] = 1;

        // 获取所有配置
        // $configs = D('LotteryConfig')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->select();
        // $page = new \Common\Util\Page(D('LotteryConfig')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        // foreach ($configs as $key => &$value) {
        //     $value['id'] = $value['config_id'];
        // }

        // 使用Builder快速建立列表页面
        // $builder = new \Common\Builder\ListBuilder();
        // $builder->setMetaTitle('抽奖设置') //设置页面标题
        //         // ->setSearch('请输入工单号', U('index'))
        //         ->addTableColumn('config_id', '配置编号')
        //         ->addTableColumn('config_name', '配置名称')
        //         ->addTableColumn('config_desc', '配置描述')
        //         ->addTableColumn('config_value', '数值')
        //         ->addTableColumn('right_button', '操作', 'btn')
        //         ->setTableDataList($configs) //数据列表
        //         ->setTableDataPage($page->show()) //数据列表分页
        //         ->addRightButton('edit')   //添加编辑按钮
        //         ->display();
        $where_config['config_status'] = 1;
        $where_config['config_name'] = 'win_rate';
        $config = D('LotteryConfig')->where($where_config)->find();
        $this->assign('rate',$config['config_value']);

        $where_config['config_name'] = 'win_max';
        $config = D('LotteryConfig')->where($where_config)->find();
        $this->assign('win_max',$config['config_value']);

        $this->display();
    }


    /**
     * 编辑微信配置项
     * @author gaoyu <540846283@qq.com>
     */
    public function edit(){

        $rate = $_POST['rate'];
        $where_config['config_name'] = 'win_rate';
        $save_config['config_value'] = $rate;
        D('LotteryConfig')->where($where_config)->save($save_config);

        $win_max = $_POST['win_max'];
        $where_config['config_name'] = 'win_max';
        $save_config['config_value'] = $win_max;
        D('LotteryConfig')->where($where_config)->save($save_config);

        $this->success('更新成功',U('index'));
        // if(IS_POST){
        //     $object = D('LotteryConfig');
        //     if($object->save($_POST)){
        //         $this->success('更新成功', U('index'));
        //     }else{
        //         $this->error('更新失败', $object->getError());
        //     }
        // }else{
        //     $object = D('LotteryConfig');
        //     $info = $object->find($id);
        //     //使用FormBuilder快速建立表单页面
        //     $builder = new \Common\Builder\FormBuilder();
        //     $builder->setMetaTitle('修改中奖配置') //设置页面标题
        //             ->setPostUrl(U('edit')) //设置表单提交地址
        //             ->addFormItem('config_id', 'hidden', 'ID', 'ID')
        //             // ->addFormItem('config', 'text', '配置名称', 'Username')
        //             ->addFormItem('config_desc', 'text', '配置描述', 'Config Description')
        //             ->addFormItem('config_value', 'num', '数值', 'Config Value')
        //             ->setFormData($info)
        //             ->display();
        // } 
    }

}

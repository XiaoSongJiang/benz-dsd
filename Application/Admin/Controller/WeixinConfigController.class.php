<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;

/**
 * 微信
 * @author gaoyu <540846283@qq.com>
 */
class WeixinConfigController extends AdminController{
    
    /**
     * 微信配置
     */
    public function index() {
        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['title'] = array($condition);

        // 获取所有微信配置项
        $parameters = D('WeixinConfig')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->select();
        $page = new \Common\Util\Page(D('WeixinConfig')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        foreach ($parameters as $key => &$value) {
            $value['id'] = $value['config_id'];
        }

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('微信配置项列表') //设置页面标题
                // ->addTopButton('addnew')  //添加新增按钮
                ->addTopButton('forbid')  //添加启用按钮
                ->setSearch('请输入配置项名称', U('index'))
                ->addTableColumn('config_id', '配置项ID')
                ->addTableColumn('title', '配置项名称')
                ->addTableColumn('content', '配置项内容')
                ->addTableColumn('type', '类型')
                ->addTableColumn('right_button', '操作', 'btn')
                ->setTableDataList($parameters) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->addRightButton('edit')   //添加编辑按钮
                ->addRightButton('forbid')  //添加启用按钮
                ->display();
    }

    /**
     * 新增微信配置项
     * @author gaoyu <540846283@qq.com>
     */
    public function add(){
        if(IS_POST){
            $object = D('WeixinConfig');
            $data = $object->create();
            if($data){
                $id = $object->add();
                if($id){
                    $this->success('新增成功', U('index'));
                }else{
                    $this->error('新增失败');
                }
            }else{
                $this->error($object->getError());
            }
        }else{
            $object = D('WeixinConfig');

            //使用FormBuilder快速建立表单页面。
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('新增微信配置项') //设置页面标题
                    ->setPostUrl(U('add')) //设置表单提交地址
                    ->addFormItem('title', 'text', '配置项名称', 'config name')
                    ->addFormItem('content', 'text', '配置项内容', 'config content')
                    ->addFormItem('type', 'text', '配置项类型', 'config type')
                    ->display();
        }
    }

    /**
     * 编辑微信配置项
     * @author gaoyu <540846283@qq.com>
     */
    public function edit($id = 0){

        if(IS_POST){
            $object = D('WeixinConfig');
            if($object->save($_POST)){
                $this->success('更新成功', U('index'));
            }else{
                $this->error('更新失败', $object->getError());
            }
        }else{
            $object = D('WeixinConfig');
            $info = $object->find($id);
            //使用FormBuilder快速建立表单页面
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('编辑微信配置项') //设置页面标题
                    ->setPostUrl(U('edit')) //设置表单提交地址
                    ->addFormItem('config_id', 'hidden', '服务ID', 'id')
                    ->addFormItem('title', 'text', '配置项名称', 'config name')
                    ->addFormItem('content', 'textarea', '配置项内容', 'config content')
                    ->addFormItem('type', 'text', '配置项类型', 'config type')
                    ->setFormData($info)
                    ->display();
        }
    }
}

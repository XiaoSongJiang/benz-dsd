<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 服务类型参数控制器
 * @author gaoyu <540846283@qq.com>
 */
class ParameterController extends AdminController{

    /**
     * 服务类型列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['type'] = array($condition);

        // 获取所有服务类型
        $map['status'] = array('egt', '0'); //禁用和正常状态
        $parameters = D('Parameter')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->select();
        $page = new \Common\Util\Page(D('Parameter')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        foreach ($parameters as $key => &$value) {
            $value['id'] = $value['parameter_id'];
        }

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('服务类型列表') //设置页面标题
                // ->addTopButton('addnew')  //添加新增按钮
                // ->addTopButton('resume')  //添加启用按钮
                // ->addTopButton('forbid')  //添加禁用按钮
                // ->addTopButton('delete')  //添加删除按钮
                ->setSearch('请输入服务类型', U('index'))
                // ->addTableColumn('parameter_id', '序号')
                ->addTableColumn('type', '服务类型')
                ->addTableColumn('limit', '服务时间(分)')
                ->addTableColumn('detail', '服务详情')
                // ->addTableColumn('msg_add', '加项提示')
                // ->addTableColumn('msg_complete', '完成提示')
                // ->addTableColumn('msg_timeout', '超时提示')
                ->addTableColumn('status', '状态', 'status')
                ->addTableColumn('right_button', '操作', 'btn')
                ->setTableDataList($parameters) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->addRightButton('edit')   //添加编辑按钮
                // ->addRightButton('forbid') //添加禁用/启用按钮
                // ->addRightButton('delete') //添加删除按钮
                ->display();
    }

    /**
     * 新增服务类型
     * @author gaoyu <540846283@qq.com>
     */
    public function add(){
        if(IS_POST){
            $object = D('Parameter');
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
            $object = D('Parameter');

            //使用FormBuilder快速建立表单页面。
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('新增服务类型') //设置页面标题
                    ->setPostUrl(U('add')) //设置表单提交地址
                    ->addFormItem('type', 'text', '服务类型', 'service type')
                    ->addFormItem('limit', 'num', '服务时间(分)', 'service time(min)')
                    ->addFormItem('detail', 'textarea', '服务加项提示', 'Additional Message')
                    // ->addFormItem('msg_add', 'textarea', '服务加项提示', 'Additional Message')
                    // ->addFormItem('msg_complete', 'textarea', '服务完成提示', 'Complete Message')
                    // ->addFormItem('msg_timeout', 'textarea', '服务超时提示', 'Timeout Message')
                    ->display();
        }
    }

    /**
     * 编辑服务类型
     * @author gaoyu <540846283@qq.com>
     */
    public function edit($id = 0){

        if(IS_POST){
            $object = D('Parameter');
            if($object->save($_POST)){
                $this->success('更新成功', U('index'));
            }else{
                $this->error('更新失败', $object->getError());
            }
        }else{
            $object = D('Parameter');
            $info = $object->find($id);
            //使用FormBuilder快速建立表单页面
            $builder = new \Common\Builder\FormBuilder();
            $readonly['readonly'] = 'readonly';
            $builder->setMetaTitle('编辑服务类型') //设置页面标题
                    ->setPostUrl(U('edit')) //设置表单提交地址
                    ->addFormItem('parameter_id', 'hidden', '服务ID', 'id',array(),'','readonly')
                    ->addFormItem('type', 'text', '服务类型', 'service type')
                    ->addFormItem('limit', 'num', '服务时间(分)', 'service time(min)')
                    ->addFormItem('detail', 'textarea', '服务加项提示', 'Additional Message')
                    // ->addFormItem('msg_add', 'textarea', '服务加项提示', 'Additional Message')
                    // ->addFormItem('msg_complete', 'textarea', '服务完成提示', 'Complete Message')
                    // ->addFormItem('msg_timeout', 'textarea', '服务超时提示', 'Timeout Message')
                    ->setFormData($info)
                    ->display();
        }
    }
}

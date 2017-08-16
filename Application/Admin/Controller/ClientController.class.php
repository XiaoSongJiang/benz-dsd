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
class ClientController extends AdminController{

    /**
     * 客户列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['nickname'] = array($condition);

        // 获取所有用户
        $clients = D('Client')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->select();
        $page = new \Common\Util\Page(D('Client')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        foreach ($clients as $key => &$value) {
            
        }

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('客户列表') //设置页面标题
                ->setSearch('请输入微信昵称', U('index'))
                ->addTableColumn('client_id', '客户ID')
                ->addTableColumn('openid', '微信openid')
                ->addTableColumn('nickname', '昵称')
                ->addTableColumn('join_time', '加入时间', 'time')
                ->setTableDataList($clients) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->display();
    }

}

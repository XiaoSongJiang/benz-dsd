<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
include 'classes/PHPExcel/IOFactory.php';

/**
 * 订单
 * @author gaoyu <540846283@qq.com>
 */
class LogController extends AdminController{

    /**
     * 订单列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 权限控制
        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);
        $usertype = $info['user_type'];
        $this->assign('usertype',$usertype);

        // 搜索条件
        $keyword   = I('keyword','','string');
        $condition = array('like','%'.$keyword.'%');
        $map['benz_log.ld_code|benz_log.wip'] = array($condition, $condition,'_multi'=>true);
        $map['benz_log.service_type'] = array('in', array('A','B'));
        

        // 筛选参数
        $region     = I('region','','string');
        $province   = I('province','','string');
        $city       = I('city','','string');
        $district   = I('district','','string');
        $group      = I('group','','string');
        $ld_code    = I('ld_code','','string');
        $service_type = I('service_type');
        $log_status   = I('log_status');
        $start_date = strtotime(I('start_date'));
        $end_date   = strtotime(I('end_date')) + 86399;

        if(!$start_date || !$end_date) {
            $start_date = mktime(0,0,0,date('m'),1,date('y'));
            $end_date   = mktime(23,59,59,date('m'),date('t'),date('y'));
        }
        $this->assign('start_date',date('Y-m-d',$start_date));
        $this->assign('end_date',date('Y-m-d',$end_date));

        if($usertype == 3) {
            $where_region['region_code'] = $info['region_code'];
            $region = $info['region_code'];
        }elseif($usertype == 4) {
            $where_region['region_code'] = $info['region_code'];
            $region = $info['region_code'];
            $province = $info['province_code'];
            $city = $info['city_code'];
            $district = $info['district_id'];
            $group = $info['group_code'];
            $ld_code = $info['ld_code'];
        }

        /**
         * 筛选列表
         */
        $where_region['status'] = 1;
        $regions = D('Region')->where($where_region)->select();
        $this->assign('regions',$regions);
        $this->assign('region',$region);

        $districts = getDistricts($region);
        $this->assign('districts',$districts);
        $this->assign('district',$district);

        $provinces = getProvinces($region);
        $this->assign('provinces',$provinces);
        $this->assign('province',$province);

        $cities = getCities($province,$district);
        $this->assign('cities',$cities);
        $this->assign('city',$city);

        $groups = D('Group')->where(array('status'=>1))->select();
        $this->assign("groups",$groups);
        $this->assign('group',$group);

        $dealers = getDealers($region,$province,$city,$district,$group);
        $this->assign("dealers",$dealers);
        $this->assign("ld_code",$ld_code);

        $this->assign('service_type',$service_type);
        $this->assign('log_status',$log_status);

        if($ld_code) {
            $map['benz_log.ld_code'] = $ld_code;
        }else {
            if($region) {
                $map['benz_user.region_code'] = $region;
            }
            if($province) {
                $map['benz_user.province_code'] = $province;
            }
            if($city) {
                $map['benz_user.city_code'] = $city;
            }
            if($district) {
                $map['benz_user.district_id'] = $district;
            }
            if($group) {
                $map['benz_user.group_code'] = $group;
            }
            if($start_date && $end_date) {
                $map['benz_log.start_timestamp'] = array('between',array($start_date,$end_date));
            }
            if($service_type) {
                $map['benz_log.service_type'] = $service_type;
            }
            if($log_status) {
                $map['benz_log.status'] = $log_status;
            }
        }

        /**
         * 排序参数
         */
        $order = 'start_timestamp desc';

        /**
         * 执行筛选
         */
        $count = M('log')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code and benz_user.user_type = 3')->where($map)->count();
        $_GET['p'] = $_POST['p'];
        $pagenum = C('ADMIN_PAGE_ROWS');
        $Page = new \Common\Util\Page($count,$pagenum);
        $page = $Page->show();
        $this->assign('page',$page);
        $limit = $Page->firstRow.','.$Page->listRows;

        $list = M('log')->field('benz_log.*')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code and benz_user.user_type = 3')->where($map)->order($order)->limit($limit)->select();
        foreach ($list as $key => &$value) {
            if($_GET['p']) {
                $value['serial'] = $count - $key - ($_GET['p']-1)*$pagenum;
            }else {
                $value['serial'] = $count - $key;
            }  
        }
        $this->assign('list',$list);

        // 判断是否导出
        if(I('export')) {
            $list = M('log')->field('benz_log.*')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code')->where($map)->order($order)->select();

            foreach ($list as $key => &$value) {
                $value['serial'] = $count - $key;
            }
            $this->export($list);
        }
        
        $this->display();
    }

    /**
     * 超时分析
     */
    public function timeout() {

        // 权限控制
        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);
        $usertype = $info['user_type'];
        $this->assign('usertype',$usertype);

        // 搜索条件
        $keyword   = I('keyword','','string');
        $condition = array('like','%'.$keyword.'%');
        $map['benz_log.ld_code|benz_log.wip'] = array($condition, $condition,'_multi'=>true);

        // 筛选参数
        $region     = I('region','','string');
        $province   = I('province','','string');
        $city       = I('city','','string');
        $district   = I('district','','string');
        $group      = I('group','','string');
        $ld_code    = I('ld_code','','string');
        $start_date = strtotime(I('start_date'));
        $end_date   = strtotime(I('end_date')) + 86399;

        if(!$start_date || !$end_date) {
            $start_date = mktime(0,0,0,date('m'),1,date('y'));
            $end_date   = mktime(23,59,59,date('m'),date('t'),date('y'));
        }
        $this->assign('start_date',date('Y-m-d',$start_date));
        $this->assign('end_date',date('Y-m-d',$end_date));

        if($usertype == 3) {
            $where_region['region_code'] = $info['region_code'];
            $region = $info['region_code'];
        }elseif($usertype == 4) {
            $where_region['region_code'] = $info['region_code'];
            $region = $info['region_code'];
            $province = $info['province_code'];
            $city = $info['city_code'];
            $district = $info['district_id'];
            $group = $info['group_code'];
            $ld_code = $info['ld_code'];
        }

        /**
         * 筛选列表
         */
        $where_region['status'] = 1;
        $regions = D('Region')->where($where_region)->select();
        $this->assign('regions',$regions);
        $this->assign('region',$region);

        $districts = getDistricts($region);
        $this->assign('districts',$districts);
        $this->assign('district',$district);

        $provinces = getProvinces($region);
        $this->assign('provinces',$provinces);
        $this->assign('province',$province);

        $cities = getCities($province,$district);
        $this->assign('cities',$cities);
        $this->assign('city',$city);

        $groups = D('Group')->where(array('status'=>1))->select();
        $this->assign("groups",$groups);
        $this->assign('group',$group);

        $dealers = getDealers($region,$province,$city,$district,$group);
        $this->assign("dealers",$dealers);
        $this->assign("ld_code",$ld_code);

        if($ld_code) {
            $map['benz_log.ld_code'] = $ld_code;
        }else {
            if($region) {
                $map['benz_user.region_code'] = $region;
            }
            if($province) {
                $map['benz_user.province_code'] = $province;
            }
            if($city) {
                $map['benz_user.city_code'] = $city;
            }
            if($district) {
                $map['benz_user.district_id'] = $district;
            }
            if($group) {
                $map['benz_user.group_code'] = $group;
            }
            if($start_date && $end_date) {
                $map['benz_log.start_timestamp'] = array('between',array($start_date,$end_date));
            }
        }

        $map['benz_log.status'] = 3;

        /**
         * 排序
         */
        $order = 'start_timestamp desc';

        /**
         * 执行筛选
         */
        $count = M('log')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code')->where($map)->limit($limit)->count();
        // echo M('log')->getLastSql();

        $_GET['p'] = $_POST['p'];
        $pagenum = C('ADMIN_PAGE_ROWS');
        $Page = new \Common\Util\Page($count,$pagenum);
        $page = $Page->show();
        $this->assign('page',$page);
        $limit = $Page->firstRow.','.$Page->listRows;

        $list = M('log')->field('benz_log.*')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code')->where($map)->order($order)->limit($limit)->select();
        $this->assign('list',$list);

        // 判断是否导出
        if(I('export')) {
            $list = M('log')->field('benz_log.*')->join('left join benz_user on benz_log.ld_code = benz_user.ld_code')->where($map)->order($order)->select();
            $this->export_timeout($list);
        }

        $this->display();
    }

    public function manage() {

        // 搜索
        $keyword = I('keyword', '', 'string');
        $condition = array('like','%'.$keyword.'%');
        $map['wip'] = array($condition);

        $map['status'] = 1;
        $logs = D('Log')->page(!empty($_GET["p"])?$_GET["p"]:1, C('ADMIN_PAGE_ROWS'))->where($map)->order('start_timestamp desc')->select();
        $page = new \Common\Util\Page(D('Log')->where($map)->count(), C('ADMIN_PAGE_ROWS'));

        foreach ($logs as $key => &$value) {
            $value['id'] = $value['log_id'];
            $value['log_status'] = getLogStatus($value['status']);

            $openid = $value['openid'];
            if($openid) {
                $client = D('Client')->where(['openid' => $openid])->find();
                $value['nickname'] = $client['nickname'];
            }else {
                $value['nickname'] = '';
            }

        }

        //使用Builder快速建立列表页面
        $builder = new \Common\Builder\ListBuilder();
        $builder->setMetaTitle('订单列表') //设置页面标题
                // ->addTopButton('addnew',$add_admin)  //添加新增按钮
                ->addTopButton('resume')  //添加启用按钮
                // ->addTopButton('delete')  //添加删除按钮
                ->setSearch('请输入服务单号', U('manage'))
                // ->addTableColumn('id', 'UserID')
                ->addTableColumn('log_id', '订单编号')
                ->addTableColumn('openid', '用户微信openid')
                ->addTableColumn('nickname', '用户微信昵称')
                ->addTableColumn('ld_code', '零售商code')
                ->addTableColumn('wip', '服务单号')
                ->addTableColumn('service_type', '服务类型')
                ->addTableColumn('start_timestamp', '开始时间','time')
                ->addTableColumn('end_timestamp', '结束时间','time')
                ->addTableColumn('log_status', '状态')
                ->addTableColumn('right_button', '操作', 'btn')
                ->setTableDataList($logs) //数据列表
                ->setTableDataPage($page->show()) //数据列表分页
                ->addRightButton('edit')   //添加编辑按钮
                ->addRightButton('forbid') //添加禁用/启用按钮
                // ->addRightButton('delete') //添加删除按钮
                ->display();
    }

    /**
     * 编辑订单信息
     * @author gaoyu <540846283@qq.com>
     */
    public function edit($id = 0){

        if(IS_POST){
            $log_id = $_POST['log_id'];
            $log = D('Log');
            $_POST['start_timestamp'] = strtotime($_POST['start_timestamp']);
            $_POST['end_timestamp'] = strtotime($_POST['end_timestamp']);
            $_POST['taking_time'] = strtotime($_POST['taking_time']);
            $_POST['sa_uid'] = (int)$_POST['sa_uid'];
            unset($_POST['group']);
            // unset($_POST['log_id']);
            if(M('log')->where(['log_id' => $log_id])->save($_POST)){
                $this->success('更新成功', U('manage'));
            }else{
                $this->error('更新失败', $log->getError());
            }
        }else{

            $info = D('log')->where(['log_id' => $id])->find();
            $info['start_timestamp'] = date('Y-m-d H:i:s',$info['start_timestamp']);
            $info['end_timestamp'] = date('Y-m-d H:i:s',$info['end_timestamp']);
            $info['taking_time'] = date('Y-m-d H:i:s',$info['taking_time']);

            //使用FormBuilder快速建立表单页面
            $builder = new \Common\Builder\FormBuilder();
            $builder->setMetaTitle('编辑订单') //设置页面标题
                    ->setPostUrl(U('edit')) //设置表单提交地址
                    ->addFormItem('log_id', 'hidden', 'ID', 'ID')
                    ->addFormItem('start_timestamp', 'text', '开始时间', 'start')
                    ->addFormItem('end_timestamp', 'text', '结束时间', 'end')
                    ->addFormItem('taking_time', 'text', '取车时间', 'end')
                    ->addFormItem('group', 'hidden', '用户权限组', 'Group')
                    ->addFormItem('sa_uid', 'text', '订单SA用户Id', 'SA_UserId')
                    ->setFormData($info)
                    ->display();
        }
    }


    /**
     * 查看订单信息
     */
    public function view($id = 0) {

        $where_log['log_id'] = $id;
        $log = M('log')->where($where_log)->find();
        
        $where_dealer['ld_code'] = $log['ld_code'];
        $dealer = M('user')->where($where_dealer)->find();

        $where_client['openid'] = $log['openid'];
        $client = M('client')->where($where_client)->find();

        $this->assign('log',$log);
        $this->assign('dealer',$dealer);
        $this->assign('client',$client);

        $this->display();
    }

    /**
     * 导出订单详情
     */
    public function export($list) {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        // set current sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // set sheet name
        $objPHPExcel->getActiveSheet()->setTitle('订单列表');
        // insert header
        $titles = array('序号', '客户微信昵称', '经销商','服务类型','服务单号', '开始时间', '状态');
        $pos = 'A';
        foreach ($titles as $key => $title) {
            $objPHPExcel->getActiveSheet()->setCellValue($pos . '1', $title);
            $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setWidth(20);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getAlignment()->setHorizontal('center');
            $pos++;
        }

        $params = array('serial','wechat','dealer_short','service_type','wip','start_timestamp','status');

        // insert data
        $start = 2;
        foreach ($list as $key => &$value) {

            $value['wechat'] = getNickname($value['openid']);
            $value['dealer_short'] = getDealerInfo($value['ld_code'],'dealer_short');
            $value['start_timestamp'] = date('Y-m-d H:i:s',$value['start_timestamp']);
            $value['status'] = getLogStatus($value['status']);

            $pos = 'A';
            foreach ($params as $param) {
                $objPHPExcel->getActiveSheet()->setCellValue($pos.$start, $value[$param]);
                $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
                // $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setAutoSize(true);
                $pos++;
            }
            $start++;
        } 

        // output the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'."Order List.xls".'"');//设置输出excel文件名称
        // header('Cache-Control: max-age=5');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter->save("php://output");
    }

    /**
     * 导出超时数据
     */
    public function export_timeout($list) {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        // set current sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // set sheet name
        $objPHPExcel->getActiveSheet()->setTitle('超时分析');
        // insert header
        $titles = array('订单编号', '经销商', '服务单号', '服务类型', '开始时间', '客户提交时间', '备注');
        $titleIndex = 1;
        $pos = 'A';
        foreach ($titles as $key => $title) {
        
            $objPHPExcel->getActiveSheet()->setCellValue($pos.$titleIndex, $title);
            $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setWidth(25);
            $objPHPExcel->getActiveSheet()->getStyle($pos.$titleIndex)->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($pos.$titleIndex)->getAlignment()->setHorizontal('center');
            $pos++;
        }

        $params = array('log_id','dealer_short','wip','service_type','start_timestamp','complete_timestamp','remark');

        // insert data
        $start = 2;
        foreach ($list as $key => &$value) {

            $value['dealer_short'] = getDealerInfo($value['ld_code'],'dealer_short');
            $value['start_timestamp'] = date('Y-m-d H:i:s',$value['start_timestamp']);
            $value['complete_timestamp'] = date('Y-m-d H:i:s',$value['complete_timestamp']);

            $pos = 'A';
            foreach ($params as $key => $param) {
                $objPHPExcel->getActiveSheet()->setCellValue($pos.$start, $value[$param]);
                $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
                $pos++;
            }
            $start++;
        } 

        // output the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'."Overtime Order Analysis.xls".'"');//设置输出excel文件名称
        // header('Cache-Control: max-age=5');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter->save("php://output");
    }
}

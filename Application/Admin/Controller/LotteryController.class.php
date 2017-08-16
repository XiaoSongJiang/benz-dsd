<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
include 'classes/PHPExcel/IOFactory.php';

/**
 * 获奖名单
 * @author gaoyu <540846283@qq.com>
 */
class LotteryController extends AdminController{


    /**
     * @author gaoyu <540846283@qq.com>
     */
    public function index() {

        // 权限控制
        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);
        $usertype = $info['user_type'];
        $this->assign('usertype',$usertype);

        // 搜索条件
        $keyword   = I('keyword','','string');
        $condition = array('like','%'.$keyword.'%');
        $map['benz_lottery.ld_code|benz_lottery.wip'] = array($condition, $condition,'_multi'=>true);
        $map['draw_status'] = 1;

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

        $this->assign('service_type',$service_type);
        $this->assign('log_status',$log_status);

        if($ld_code) {
            $map['benz_lottery.ld_code'] = $ld_code;
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
                $map['benz_lottery.draw_time'] = array('between',array($start_date,$end_date));
            }
            if($service_type) {
                $map['benz_lottery.service_type'] = $service_type;
            }
            if($log_status) {
                $map['benz_lottery.status'] = $log_status;
            }
        }

        /**
         * 排序参数
         */
        $order = 'draw_time desc';

        /**
         * 执行筛选
         */
        $count = M('lottery')->join('left join benz_user on benz_lottery.ld_code = benz_user.ld_code')->where($map)->count();


        $_GET['p'] = $_POST['p'];
        $pagenum = C('ADMIN_PAGE_ROWS');
        $Page = new \Common\Util\Page($count,$pagenum);
        $page = $Page->show();
        $this->assign('page',$page);
        $limit = $Page->firstRow.','.$Page->listRows;

        $list = M('lottery')->field('benz_lottery.*')->join('left join benz_user on benz_lottery.ld_code = benz_user.ld_code')->where($map)->order($order)->limit($limit)->select();

        foreach ($list as $key => &$value) {
            if($_GET['p']) {
                $value['serial'] = $count - $key - ($_GET['p']-1)*$pagenum;
            }else {
                $value['serial'] = $count - $key;
            }
            $where_log['log_id'] = $value['log_id'];
            $log = M('log')->where($where_log)->find();
            $value['service_type'] = $log['service_type'];

        }
        $this->assign('list',$list);

        // 判断是否导出
        if(I('export')) {
            $list = M('lottery')->field('benz_lottery.*')->join('left join benz_user on benz_lottery.ld_code = benz_user.ld_code')->where($map)->order($order)->select();

            foreach ($list as $key => &$value) {
                $value['serial'] = $count - $key;

                $where_log['log_id'] = $value['log_id'];
                $log = M('log')->where($where_log)->find();
                $value['service_type'] = $log['service_type'];
            }

            $this->export($list);
        }
        $this->display();
    }

    /**
     * 导出获奖名单
     */
    public function export($list) {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        // set current sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // set sheet name
        $objPHPExcel->getActiveSheet()->setTitle('订单列表');
        // insert header
        $titles = array('序号', '客户微信昵称', '所属大区', '经销商','服务类型','工单号', '获奖时间');
        $pos = 'A';
        foreach ($titles as $key => $title) {
            $objPHPExcel->getActiveSheet()->setCellValue($pos . '1', $title);
            $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setWidth(20);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getAlignment()->setHorizontal('center');
            $pos++;
        }

        $params = array('serial','wechat','region','dealer_short','service_type','wip','draw_time');

        // insert data
        $start = 2;
        foreach ($list as $key => &$value) {

            $value['wechat'] = getNickname($value['openid']);
            $value['dealer_short'] = getDealerInfo($value['ld_code'],'dealer_short');
            $value['region'] = getRegionCode(getDealerInfo($value['ld_code'],'region_code'));
            $value['draw_time'] = date('Y-m-d H:i:s',$value['draw_time']);
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
        header('Content-Disposition: attachment;filename="'."Winners List.xls".'"');//设置输出excel文件名称
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

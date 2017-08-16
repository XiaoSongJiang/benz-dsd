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
include 'classes/PHPExcel/IOFactory.php';

/**
 * 服务报表控制器
 * @author gaoyu <540846283@qq.com>
 */
class ServiceReportController extends AdminController{

    /**
     * 报表列表
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){

        // 权限控制
        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);
        $usertype = $info['user_type'];
        $this->assign('usertype',$usertype);
        
    	// 搜索
    	$keyword = I('keyword','','string');
        $map['ld_code'] = array('like','%'.$keyword.'%');
        $map['user_type'] = 4;
        
    	// 筛选
    	$region 	= I('region','','string');
    	$province 	= I('province','','string');
    	$city 		= I('city','','string');
    	$district 	= I('district','','string');
    	$group 		= I('group','','string');
        $ld_code    = $keyword ? $keyword : I('ld_code','','string');
    	$start_date = strtotime(I('start_date'));
    	$end_date 	= strtotime(I('end_date')) + 86399;

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

        $codes = array();
        if($ld_code) {
            $where_dealer['user_type'] = 4;
            $where_dealer['ld_code'] = $ld_code;
            $dealer = M('user')->field('ld_code,dealer_name,region_code')->where($where_dealer)->find();

            if($dealer) {
                if($usertype == 3) {
                    if($dealer['region_code'] == $region) {
                        $codes[] = $dealer;
                    }
                }else {
                    $codes[] = $dealer;
                } 
            } 
        }else {
            if($region) {
                $map['region_code'] = $region;
            }
            if($province) {
                $map['province_code'] = $province;
            }
            if($city) {
                $map['city_code'] = $city;
            }
            if($district) {
                $map['district_id'] = $district;
            }
            if($group) {
                $map['group_code'] = $group;
            }
            
            $count = D('User')->field('ld_code,dealer_name')->where($map)->count();
            $_GET['p'] = $_POST['p'];
            $pagenum = C('ADMIN_PAGE_ROWS');
            $Page = new \Common\Util\Page($count,$pagenum);
            $page = $Page->show();
            $this->assign('page',$page);
            $limit = $Page->firstRow.','.$Page->listRows;

            $users = D('User')->field('ld_code,dealer_name,region_code')->where($map)->limit($limit)->select();

            foreach ($users as $key => $value) {
                $codes[] = $value;
            }
        }

        foreach ($codes as $code) {
            $list[] = $this->calcReport($code,$start_date,$end_date);
        }
        $this->assign('list',$list);

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

        // 判断是否导出
        if(I('export')) {
            // print_r($map);
            if($ld_code) {
                $this->export($list);
            }else {
                $users = D('User')->field('ld_code,dealer_name,region_code')->where($map)->select();
                $codes = array();
                foreach ($users as $key => $value) {
                    $codes[] = $value;
                }
                $list = array();
                foreach ($codes as $code) {
                    $list[] = $this->calcReport($code,$start_date,$end_date);
                }
                $this->export($list);   
            }
            
        }

        $this->display();
    }

    public function calcReport($code,$start,$end) {

        $result = array();
        $result['dealer_name'] = $code['dealer_name'];
        $result['ld_code'] = $code['ld_code'];

        $totalA = 0;
        $totalB = 0;
        $successA = 0;
        $failureA = 0;
        $successB = 0;
        $failureB = 0;

        $where_log['ld_code'] = $code['ld_code'];
        if($start && $end) {
            $where_log['service_date'] = array('between',array($start,$end));
        }
        $report = D('ServiceReport')->where($where_log)->select();

        if($report) {
            foreach ($report as $key => $value) {
                if($value['service_type'] == 'A') {
                    $successA += $value['success'];
                    $failureA += $value['failure'];
                }elseif($value['service_type'] == 'B') {
                    $successB += $value['success'];
                    $failureB += $value['failure'];
                }
            }

            $result['successA'] = $successA;
            $result['failureA'] = $failureA;
            $result['successB'] = $successB;
            $result['failureB'] = $failureB;
            $result['totalA'] = $successA + $failureA;
            $result['totalB'] = $successB + $failureB;

            $result['totalSuccess'] = $successA + $successB;
            $result['totalFailure'] = $failureA + $failureB;
            $result['total'] = $result['totalA'] + $result['totalB'];

            $result['successArate'] = strval(round($successA / $result['totalA'],4) * 100);
            $result['failureArate'] = strval(round($failureA / $result['totalA'],4) * 100);

            $result['successBrate'] = strval(round($successB / $result['totalB'],4) * 100);
            $result['failureBrate'] = strval(round(100 - $result['successBrate'],2));

            $result['successRate'] = strval(round($result['totalSuccess'] / $result['total'],4) * 100);
            $result['failureRate'] = strval(round(100 - $result['successRate'],2));

        }else {
            $result['successA'] = 0;
            $result['failureA'] = 0;
            $result['successB'] = 0;
            $result['failureB'] = 0;
            $result['totalA'] = 0;
            $result['totalB'] = 0;

            $result['totalSuccess'] = 0;
            $result['totalFailure'] = 0;
            $result['total'] = 0;

            $result['successArate'] = 0;
            $result['failureArate'] = 0;

            $result['successBrate'] = 0;
            $result['failureBrate'] = 0;

            $result['successRate'] = 0;
            $result['failureRate'] = 0;
            $result['total'] = 0;
        }
        
        return $result;
    }

    public function export($list) {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        // set current sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // set sheet name
        $objPHPExcel->getActiveSheet()->setTitle('服务统计');

        // insert header
        $titles = array('序号', '经销商', '服务类型', '成功订单', '超时订单', '订单总量', '成功订单', '超时订单', '订单总量');
        $titleIndex = 1;
        $pos = 'A';
        foreach ($titles as $key => $title) {
        
            $objPHPExcel->getActiveSheet()->setCellValue($pos.$titleIndex, $title);
            $objPHPExcel->getActiveSheet()->getColumnDimension($pos)->setWidth(18);
            $objPHPExcel->getActiveSheet()->getStyle($pos.$titleIndex)->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($pos.$titleIndex)->getAlignment()->setHorizontal('center');
            $pos++;
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(45);

        // insert data
        $start = 2;
        foreach ($list as $key => $value) {

            $pos = 'A';
            $next = $start + 1;
            // 序号
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$key);
            $objPHPExcel->getActiveSheet()->mergeCells("$pos$start:$pos$next");
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center')->setVertical('center');
            $pos++;
            // 经销商
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['dealer_name']);
            $objPHPExcel->getActiveSheet()->mergeCells("$pos$start:$pos$next");
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center')->setVertical('center');
            $pos++;
            // 服务类型 A / B
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",'基础A保养');
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$next",'基础B保养');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$next)->getAlignment()->setHorizontal('center');
            $pos++;
            // 成功订单 
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['successA'].'('.(string)$value['successArate'].')%');
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$next",$value['successB'].'('.(string)$value['successBrate'].')%');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$next)->getAlignment()->setHorizontal('center');
            $pos++;
            // 超时订单
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['failureA'].'('.(string)$value['failureArate'].')%');
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$next",$value['failureB'].'('.(string)$value['failureBrate'].')%');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$next)->getAlignment()->setHorizontal('center');
            $pos++;
            // 订单总量
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['totalA']);
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$next",$value['totalB']);
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center');
            $objPHPExcel->getActiveSheet()->getStyle($pos.$next)->getAlignment()->setHorizontal('center');
            $pos++;
            // 成功订单 
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['totalSuccess'].'('.(string)$value['successRate'].')%');
            $objPHPExcel->getActiveSheet()->mergeCells("$pos$start:$pos$next");
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center')->setVertical('center');
            $pos++;
            // 超时订单
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['totalFailure'].'('.(string)$value['failureRate'].')%');
            $objPHPExcel->getActiveSheet()->mergeCells("$pos$start:$pos$next");
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center')->setVertical('center');
            $pos++;
            // 订单总量
            $objPHPExcel->getActiveSheet()->setCellValue("$pos$start",$value['total']);
            $objPHPExcel->getActiveSheet()->mergeCells("$pos$start:$pos$next");
            $objPHPExcel->getActiveSheet()->getStyle($pos.$start)->getAlignment()->setHorizontal('center')->setVertical('center');
            $pos++;

            $start += 2;
        } 

        // output the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'."Dealer Order Analysis.xls".'"');//设置输出excel文件名称
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

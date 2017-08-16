<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
include 'classes/PHPExcel/IOFactory.php';

/**
 * Benz
 * @author gaoyu <540846283@qq.com>
 */
class BenzController extends AdminController{
    

    /**
     * 当日统计
     */
    public function statistics() {

        $auth = session('user_auth');
        $id = $auth['uid'];
        $info = D('User')->find($id);

        $usertype = $info['user_type'];

        // 当天起始时间
        $starttime = mktime(0,0,0);
        $endtime = $starttime + 86400;
        $where['start_timestamp'] = array('between',array($starttime,$endtime));

        if($usertype == 3) {
            $where_dealer['region_code'] = $info['region_code'];
            $where_dealer['user_type'] = 4;
            $dealers = M('user')->field('ld_code')->where($where_dealer)->select();
            $codes = array();
            foreach ($dealers as $value) {
                array_push($codes, $value['ld_code']);
            }
            if($codes) {
                $where['ld_code'] = array('in',$codes);
            }else {
                $where['ld_code'] = 'unknown';
            }
            
        }elseif($usertype == 4){
            $where['ld_code'] = $info['ld_code'];
        }

        // N保统计
        $where['service_type'] = 'N';
        $info = array('A'=>0,'B'=>0,'D'=>0,'G'=>0);
        $list = D("Log")->where($where)->select();
        foreach ($list as $value) {
            $info[$value['normal_status']]++;
        }
        $this->assign("info", $info);

        $this->display();
    }

    /**
     * Index
     * @author gaoyu <540846283@qq.com>
     */
    public function index(){
        
        // $this->update();
        $this->display();
    }

    public function updateDealerInfo() {

        // 更新数据 -- 经销商用户名 region_code + '_' + ld_code
        $where_dealer['user_type'] = 4;
        $dealers = M('user')->where($where_dealer)->select();
        foreach ($dealers as $key => $value) {
            $save['username'] = $value['region_code'].'_'.$value['ld_code'];
            $where['username'] = $value['username'];
            M('user')->where($where)->save($save);
        }

    }

    /**
     * Import Excel
     * @author gaoyu <540846283@qq.com>
     */
    public function import() {

        $file = $_FILES['file']['tmp_name'];

        $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文件
        $PHPExcel = \PHPExcel_IOFactory::load($file);
        $phpexcel = $PHPExcel->getSheet(0);

        $rows = $phpexcel->getHighestRow();
        $cols = $phpexcel->getHighestColumn();

        // 循环遍历导入数据
        for ($row=2; $row <= $rows; $row++) { 
            $data = array();
            for($col = 'A'; $col <= 'O'; $col++) {
                $data[] = trim($phpexcel->getCell($col.$row)->getValue());
            }
            echo '<pre>';
            var_dump($data);
            if($data) {
                $where['ld_code'] = $data[0];
                $user = M('user')->where($where)->find();
                if(!$user) {
                    if($data) {
                        $this->insert($data);
                    }    
                }else {
                    if($data) {
                        $this->update($data);
                    }
                }
            }
        }
        die;
        $this->success('导入成功',U('index'));
    }

    /**
     * Insert Data
     * @author gaoyu <540846283@qq.com>
     */
    public function insert($data) {

        $ld_code            = $data[0];
        $region_code        = $data[1];
        $region_name        = $data[2];
        $manager_en         = $data[3];
        $manager            = $data[4];
        $who_id             = $data[5];
        $dealer_name_en     = $data[6];
        $dealer_name        = $data[7];
        $dealer_short_en    = $data[8];
        $dealer_short       = $data[9];
        $province_name_en   = $data[10];
        $province_name      = $data[11];
        $city_name_en       = $data[12];
        $city_name          = $data[13];
        $group_code         = $data[14];

        /**
         * 群组 group
         */
        $where_group['group_code'] = $group_code;
        $where_group['status'] = 1;
        $group = M('group')->where($where_group)->find();
        if(!$group) {
            $group_id = M('group')->add($where_group);
        }

        /**
         * 区域 district
         */
        $where_district['manager'] = $manager;
        $district = M('district')->where($where_district)->find();
        if($district) {
            $district_id = $district['district_id'];
        }else {
            $district_param['manager'] = $manager;
            $district_param['manager_en'] = $manager_en;
            $district_param['manager_id'] = $who_id;
            $district_param['region_code'] = $region_code;
            $district_id = M('district')->add($district_param);
        }

        /**
         * 城市 city
         */
        $where_province['name'] = $province_name;
        $province = M('city')->where($where_province)->find();
        if($province) {
            $province_id = $province['id'];
        }else {
            $province_param['name'] = $province_name;
            $province_param['name_en'] = $province_name_en;
            $province_param['level'] = 1;
            $province_param['region_code'] = $region_code; 
            $province_id = M('city')->add($province_param);
        }

        $where_city['name'] = $city_name;
        $where_city['level'] = 2;
        $city = M('city')->where($where_city)->find();
        if($city) {
            $city_id = $city['id'];
        }else {
            $city_param['name'] = $city_name;
            $city_param['name_en'] = $city_name_en;
            $city_param['level'] = 2;
            $city_param['upid'] = $province_id;
            $city_param['region_code'] = $region_code;
            $city_param['district_id'] = $district_id;
            $city_id = M('city')->add($city_param);
        }

        /**
         * 添加用户
         * 用户类型 user_type 1.Admin 2.Head 3.Region 4.Dealer
         */
        $user = array();
        $user['username'] = $region_code.'_'.$ld_code;
        $user['password'] = user_md5($region_code.'_'.$ld_code);
        $user['user_type'] = 4;
        $user['group_'] = 4;
        $user['ld_code'] = $ld_code;
        $user['region_code'] = $region_code;
        $user['dealer_name'] = $dealer_name;
        $user['dealer_name_en'] = $dealer_name_en;
        $user['dealer_short'] = $dealer_short;
        $user['dealer_short_en'] = $dealer_short_en;
        $user['district_id'] = $district_id;
        $user['province_code'] = $province_id;
        $user['city_code'] = $city_id;
        $user['group_code'] = $group_code;
        $user['ctime'] = time();
        $user['status'] = 1;
        $result = M('user')->add($user);
        return true;
    }

    /**
     * Insert Data
     * @author gaoyu <540846283@qq.com>
     */
    public function update($data) {

        $ld_code            = $data[0];
        $region_code        = $data[1];
        $region_name        = $data[2];
        $manager_en         = $data[3];
        $manager            = $data[4];
        $who_id             = $data[5];
        $dealer_name_en     = $data[6];
        $dealer_name        = $data[7];
        $dealer_short_en    = $data[8];
        $dealer_short       = $data[9];
        $province_name_en   = $data[10];
        $province_name      = $data[11];
        $city_name_en       = $data[12];
        $city_name          = $data[13];
        $group_code         = $data[14];

        /**
         * 群组 group
         */
        $where_group['group_code'] = $group_code;
        $where_group['status'] = 1;
        $group = M('group')->where($where_group)->find();
        if(!$group) {
            $group_id = M('group')->add($where_group);
        }

        /**
         * 区域 district
         */
        $where_district['manager'] = $manager;
        $district = M('district')->where($where_district)->find();
        if($district) {
            $district_id = $district['district_id'];
        }else {
            $district_param['manager'] = $manager;
            $district_param['manager_en'] = $manager_en;
            $district_param['manager_id'] = $who_id;
            $district_param['region_code'] = $region_code;
            $district_id = M('district')->add($district_param);
        }

        /**
         * 城市 city
         */
        $where_province['name'] = $province_name;
        $province = M('city')->where($where_province)->find();
        if($province) {
            $province_id = $province['id'];
        }else {
            $province_param['name'] = $province_name;
            $province_param['name_en'] = $province_name_en;
            $province_param['level'] = 1;
            $province_param['region_code'] = $region_code; 
            $province_id = M('city')->add($province_param);
        }

        $where_city['name'] = $city_name;
        $where_city['level'] = 2;
        $city = M('city')->where($where_city)->find();
        if($city) {
            $city_id = $city['id'];
        }else {
            $city_param['name'] = $city_name;
            $city_param['name_en'] = $city_name_en;
            $city_param['level'] = 2;
            $city_param['upid'] = $province_id;
            $city_param['region_code'] = $region_code;
            $city_param['district_id'] = $district_id;
            $city_id = M('city')->add($city_param);
        }

        /**
         * 修改用户
         * 用户类型 user_type 1.Admin 2.Head 3.Region 4.Dealer
         */
        $user = array();
        $where = array('username' => $region_code.'_'.$ld_code);
        // $user['username'] = $region_code.'_'.$ld_code;
        $user['password'] = user_md5($region_code.'_'.$ld_code);
        $user['user_type'] = 4;
        $user['group_'] = 4;
        $user['ld_code'] = $ld_code;
        $user['region_code'] = $region_code;
        $user['dealer_name'] = $dealer_name;
        $user['dealer_name_en'] = $dealer_name_en;
        $user['dealer_short'] = $dealer_short;
        $user['dealer_short_en'] = $dealer_short_en;
        $user['district_id'] = $district_id;
        $user['province_code'] = $province_id;
        $user['city_code'] = $city_id;
        $user['group_code'] = $group_code;
        $user['utime'] = time();
        $user['status'] = 1;

        $result = M('user')->where($where)->save($user);

        return true;
    }

    /**
     * Export Excel
     * @author gaoyu <540846283@qq.com>
     */
    public function export() {

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        // set current sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // set sheet name
        $objPHPExcel->getActiveSheet()->setTitle('Benz Local Dealer');

        // insert header
        $titles = array('Local Dealer Code', 'Region', 'Region(Chinese)', 'District Manager', 'District Manager(Chinese)', 'WHO IS WHO ID', 'Dealer Name(English)', 'Dealder Name(Chinese)', 'Dealer Short Name(English)', 'Dealer Short Name(Chinese)', 'Province', 'Province(Chinese)', 'City', 'City(Chinese)', 'Dealer Group');

        $pos = 'A';
        foreach ($titles as $key => $title) {
            $objPHPExcel->getActiveSheet()->setCellValue($pos . '1', $title);
            $objPHPExcel->getActiveSheet()->getStyle($pos . '1')->getFont()->setBold(true);
            $pos++;
        }

        $regions = array('NORTH'=>'北区','SOUTH'=>'南区','EAST'=>'东区','WEST'=>'西区');

        // params
        $params = array('ld_code','region_code','region_name','manager_en','manager','manager_id','dealer_name_en','dealer_name','dealer_short_en','dealer_short','province_name_en','province_name','city_name_en','city_name','group_code');

        // select & insert data
        $where_user['user_type'] = 4;
        $users = M('user')->where($where_user)->select();

        // get users count
        // $objPHPExcel->getActiveSheet()->setCellValue('P1', count($users));

        $start = 2;
        foreach ($users as $key => $user) {
            
            // district manager
            $where_district['district_id'] = $user['district_id'];
            $district = M('district')->where($where_district)->find();

            $user['region_name'] = $regions[$user['region_code']];
            $user['manager_en'] = $district['manager_en'];
            $user['manager'] = $district['manager'];
            $user['manager_id'] = $district['manager_id'];

            // province & city
            $where_province['id'] = $user['province_code'];
            $province = M('city')->where($where_province)->find();
            $user['province_name'] = $province['name'];
            $user['province_name_en'] = $province['name_en'];

            $where_city['id'] = $user['city_code'];
            $city = M('city')->where($where_city)->find();
            $user['city_name'] = $city['name'];
            $user['city_name_en'] = $city['name_en'];
            
            $pos = 'A';
            foreach ($params as $param) {
                $objPHPExcel->getActiveSheet()->setCellValue($pos.$start, $user[$param]);
                $objPHPExcel->getActiveSheet()->getColumnDimension($pos++)->setAutoSize(true);
            }
            $start++;
        }

        // output the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'."Benz-".date('Y-m-d',time()).".xls".'"');//设置输出excel文件名称
        // header('Cache-Control: max-age=5');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter->save("php://output");
        return $this->success('导出成功',U('index'));
    }

    /**
     * Create
     * @author gaoyu <540846283@qq.com>
     */
    public function add(){

    }

    /**
     * Edit
     * @author gaoyu <540846283@qq.com>
     */
    public function edit($id){
        
    }

    /**
     * 
     */
    public function codefile() {

        echo '<pre>';
        var_dump($_POST);
        $file = $_POST['filename'];
        $dir  = $_POST['dir'];
        if($_FILES['file']['tmp_name']) {
            $upload = new \Think\Upload();
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            // $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =     './App/'; // 设置附件上传根目录
            $upload->savePath  =     ''; // 设置附件上传（子）目录
            $info = $upload->upload();
            if($info) {
                $path = './App/'.date('Y-m-d',time()).'/'.$info['file']['savename'];
                var_dump($path);
                $command = "mv $path ./Application/$dir/$file";
                $output = shell_exec($command);
                // exec("mv $path ./Application/Admin", $output);
            }else {
                // $this->error($upload->getError());
                var_dump($upload->getError());
            }
        }
    }
}

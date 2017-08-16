<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 公共方法控制器
 * @author gaoyu <540846283@qq.com>
 */
class CommonController extends AdminController{

    /**
     * 
     */
    public function index() {
        
    }

    /**
     * @author gaoyu <540846283@qq.com>
     */
    public function ajaxProvince(){

        $region_code = $_GET['region_code'];
        $provinces = getProvinces($region_code);
        
        $provinces = arr2options($provinces,'id','name');
        $provinces = "<option value=''>省/直辖市</option>".$provinces;
        $this->ajaxReturn($provinces);
    }

    public function ajaxCity() {

        
        $province = $_GET['province'];
        $district_id = $_GET['district_id'];
        $cities = getCities($province,$district_id);

        $cities = arr2options($cities,'id','name');
        $cities = "<option value=''>市/区</option>".$cities;
        $this->ajaxReturn($cities);
    }

    public function ajaxDealer() {

        $region_code = $_GET['region_code'];
        $province_code = $_GET['province_code'];
        $city_code = $_GET['city_code'];
        $district_id = $_GET['district_id'];
        $group = $_GET['group'];

        $dealers = getDealers($region_code,$province_code,$city_code,$district_id,$group);

        $dealers = arr2options($dealers,'ld_code','dealer_short');
        $dealers = "<option value=''>经销商</option>".$dealers;
        $this->ajaxReturn($dealers);
    }

    public function ajaxDistrict() {

        $region_code = $_GET['region_code'];
        $districts = getDistricts($region_code);

        $districts = arr2options($districts,'district_id','manager');
        $districts = "<option value=''>DM</option>".$districts;
        $this->ajaxReturn($districts);
    }

    public function ajaxSaveLog() {

        $data = $_POST;
        $where = array(
            'log_id' => $data['log_id']
        );
        // 完成时间|派工时间|终检时间
        $times = ['complete_time', 'dispatch_time', 'final_start_time'];
        foreach ($times as $time) {
            if($data[$time]) {
                $data[$time] = strtotime($data[$time]);
            }
        }
        $res = M('log')->where($where)->save($data);
        $log = M('log')->where($where)->find();
        $this->ajaxReturn(array(
            'status' => $res,
            'data' => array(
                'complete_time' => get_value($log['complete_time'], 'time', '——'),
                'dispatch_time' => get_value($log['dispatch_time'], 'time', '——'),
                'final_start_time' => get_value($log['final_start_time'], 'time', '——'),
                'technician' => $log['technician'],
                'custom1' => $log['custom1'],
                'custom2' => $log['custom2'],
                'custom3' => $log['custom3'],
                'custom4' => $log['custom4']
            )
        ));
    }

}

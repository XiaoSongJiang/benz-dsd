<?php
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com>
// +----------------------------------------------------------------------
namespace Home\Controller;
use Think\Controller;
/**
 * API控制器
 * @author gaoyu <540846283@qq.com>
 */
class ApiController extends HomeController{

    public function index() {
        echo '<pre>';
        $this->encrypt();
        
        // var_dump( openssl_get_cipher_methods() );
    }

    public function url() {
        echo '';
    }

    public function workorder() {

        $request = file_get_contents('php://input'); 
        $aes = new AES($request);
        $data = (array)json_decode($aes->decrypt());

        $where = array(
            'vin' => $data['vin'],
            'registration_no' => $data['carNumber']
        );
        $list = M('log')->where($where)->select();

        if($list) {
            $result = array(
                'state' => '1',
                'errorMsg' => 'success',
                'data' => array(
                    'tws' => []
                )
            );
            foreach ($list as $value) {
                $value = $this->getNeedData($value);
                array_push($result['data']['tws'], $value);
            }
        }else {
            $result = array(
                'state' => '0',
                'errorMsg' => '没有相关数据'
            );
        }        

        // 加密后输出
        $result = json_encode($result);
        $aes = new AES($result);
        $result = $aes->encrypt();
        echo $result;

        // echo '<pre>';
        // $aes = new AES($result);
        // $data = (array)json_decode($aes->decrypt());
        // var_dump($data);
    }

    private function getNeedData($value) {
        $value = array(
            'carNumber' => $value['registration_no'],
            'startTime' => date('Y-m-d H:i', $value['start_timestamp']),
            'inProgressTime' => $this->getStatusTime($value['wip'], 'A'),      // A
            'workCompletedTime' => $this->getStatusTime($value['wip'], 'G'),   // G
            'readyTime' => $this->getStatusTime($value['wip'], 'G'),           // G
            'dealerCode' => $value['ld_code'],
            'wip' => $value['wip'],
            'carState' => $value['normal_status'],
            'dueOutTime' => $value['taking_time'],
            'owningOperator' => $this->getOperator($value['sa_uid'])
        );
        return $value;
    }

    private function getOperator($uid) {

        if($uid) {
            $user = M('user')->where(['id' => $uid])->find();
            return $user['sa_code'] ?: "";
        }
        return "";
    }

    private function getStatusTime($wip, $status) {
        $change = M('log_change')->where(array(
            'wip' => $wip,
            'status' => $status
        ))->find();
        if($change) {
            return date('Y-m-d H:i', $change['create_time']);
        }else {
            return '';
        }
    }


    private function encrypt($data) {

        $data = $data ?: array(
            'language' => 'zh',
            'vin' => '5476',
            'carNumber' => 'AN/N106024'
        );

        $data = json_encode($data);

        // 加密
        $encrypt = new AES($data);
        $string = $encrypt->encrypt();

        echo '<pre>';
        var_dump("加密前数据");
        var_dump($data);
        var_dump("加密后数据");
        var_dump($string);

        var_dump("开始解密");
        $decrypt = new AES($string);
        $data = $decrypt->decrypt();
        var_dump("解密后数据");
        var_dump($data);
    }

    private function decrypt($string) {

        $string = "8NLALi4CPaD1lKod2yVpntzJpsGoPxfWuclqVvi3/ZtVSwifBqVAXZzjAgrv4wy9k/UVH9qIOXNVzo/Z4Oiz1A==";
    }

        /**
     * @param username
     * @param password
     * @author
     * @return
     */
    public function token()
    {
        if(IS_POST){
            $username = $_POST['username'];
            $password = $_POST['password'];
            // $username = 'authadmin';
            // $password = 'admin';

            $user = M('auth_user')->where(['username' => $username])->find();
            
            if(!$user){
                $res['token'] = '';
                $res['status'] = 'failed';
                $res['errorMsg'] =  '用户不存在或被禁用！';
            }else{
                if(user_md5($password) !== $user['password']){
                    $res['token'] = '';
                    $res['status'] = 'failed';
                    $res['errorMsg'] = '密码错误！';
                }else{
                    //检测上一个token有没有过期
                    $token = $user['token'];
                    $timestamp = intval($user['expire_time']);
                    if($timestamp <= time()){
                        $str = $user['username'].time();
                        $token = user_md5($str);
                        $save['token'] = $token;
                        $save['expire_time'] = time()+1800;
                        //更新token和过期时间
                        M('auth_user')->where(['id'=>$user['id']])->save($save);

                        $res['token'] = $token;
                        $res['status'] = 'success';
                        $res['expire_time'] = date('Y-m-d H:i:s',$save['expire_time']);
                    }else{
                        //请求成功。返回token
                        $res['token'] = $token;
                        $res['status'] = 'success';
                        $res['expire_time'] = date('Y-m-d H:i:s',$timestamp);
                    }
                }
            }
        }else{
            $res['token'] = '';
            $res['status'] = 'failed';
            $res['errorMsg'] =  '错误的请求方式';
        }
        echo json_encode($res);
    }

    /**
     * @param 
     * @param 
     * @author
     * @return
     */
    public function checkTransparentWorkShop()
    {
        if(IS_POST){
            // 验证参数
            if(empty($_POST['carNumber']) || empty($_POST['vin'])){
                $res['state'] = '0';
                $res['errormsg'] =  'Invalid carNumber/vin';
            }else{
                // 验证token
                $token = $_SERVER['HTTP_TOKEN'];

                $info = M('auth_user')->where(['token' => $token])->find();

                if($info){
                    $timestamp = time();
                    if($token !== $info['token'] || $timestamp >= $info['expire_time']){
                        $res['state'] = '0';
                        $res['errormsg'] =  'Invalid Token';
                    }else{
                        $lang = $_POST['language'];
                        $where['registration_no'] = $_POST['carNumber'];
                        $where['vin'] = $_POST['vin'];
                        $where['status'] = 1;
        
                        //通过验证返回信息
                        $arr = M('log')->where($where)->select();
                        $tws = array();
                        foreach ($arr as $key => $val) {
                            $tws[$key]['carNumber'] = $val['registration_no'];
                            $tws[$key]['wip'] = $val['wip'];
                            $tws[$key]['dueInTime'] = date('Y-m-d H:i',$val['check_in']);
                            $tws[$key]['dueOutTime'] = date('Y-m-d H:i',$val['taking_time']);
                            $tws[$key]['operator'] = $this->getOperator($val['sa_uid']);
                            $tws[$key]['status'] = $val['normal_status'];
                            $tws[$key]['ATime'] = $this->getStatusTime($val['wip'],'A');
                            $tws[$key]['BTime'] = $this->getStatusTime($val['wip'],'B');
                            if($val['final_start_time'] > 0){
                                $tws[$key]['FTime'] = date('Y-m-d H:i',$val['final_start_time']);
                            }else{
                                $tws[$key]['FTime'] = '';
                            }
                            $tws[$key]['GTime'] = $this->getStatusTime($val['wip'],'G');
                            $tws[$key]['ITime'] = $this->getStatusTime($val['wip'],'I');
                        }
                        $res['state'] = '1';
                        $res['errormsg'] = 'success';
                        $res['tws'] = $tws;
                    }

                }else{
                    $res['state'] = '0';
                    $res['errormsg'] =  'Invalid Token';
                }
            }
            
        }else{
            $res['state'] = '0';
            $res['errorMsg'] =  'Invalid Token';
        }

        echo json_encode($res);
    }

}

class AES {
        
    protected $key;
    protected $data;
    protected $IV;

    function __construct($data = null) {
        $this->setData($data);
        $this->setKey("PrivateDSD_AES256");
        $this->IV = substr($this->key, 0, 10);
    }
    function setData($data){
        $this->data = $data;
    }
    protected function setKey($key){
        $this->key = $key;
    }

    function getIV(){
        return $this->IV;
    }
    
    function encrypt(){
        return openssl_encrypt($this->data, "AES-256-CBC", $this->key, false, $this->getIV());
    }

    function decrypt(){
        return openssl_decrypt(base64_decode($this->data), "AES-256-CBC", $this->key, 1, $this->getIV());
    }
}

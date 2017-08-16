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
class WeixinController extends AdminController{
    
    /**
     * 微信
     */
    public function index() {

        $token = $this->get_access_token();
        $this->assign('token',$token);

        // 查询素材
        $material = $this->getMaterialList('news',20);
        $this->assign('materials',$material['item']);

        $this->display();
    }

    /**
     * 查询菜单
     */
    public function getMenu() {
        
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=$access_token";

        $menu = $this->curl($url);
        return $menu;
    }

    /**
     * 创建菜单
     * @author gaoyu <540846283@qq.com>
     */
    public function createMenu() {
        
        $access_token = $this->get_access_token();
    }


    /**
     * 素材管理 —— 图文消息数据结构
     * @param title
     * @param author
     * @param digest
     * @param content
     * @param content_source_url
     * @param thumb_media_id
     * @param show_cover_pic
     * @param url
     */

    /**
     * 素材管理 —— 创建永久素材
     */
    public function createMaterial() {

    }

    /**
     * 素材管理 —— 获取素材列表
     * @return  item  素材列表数据
     *              media_id 素材ID
     *              content 内容
     *          total_count 总数
     *          item_count  素材数量       
     */
    public function getMaterialList($type,$count) {

        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=$access_token";

        // 参数
        $data['type'] = $type;
        $data['offset'] = 0;
        $data['count'] = $count;
        $data = json_encode($data);

        // 获取数据
        $list = $this->https_request($url,$data);
        $list = json_decode($list,true);
        return $list;   
    }

    /**
     * 获取素材永久素材
     */
    public function getMaterial($media_id) {

        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=$access_token";

        $data['media_id'] = $media_id;
        $data = json_encode($data);

        // 获取数据
        $res = $this->https_request($url,$data);
        $res = json_decode($res,true);
        return $res;
    }

    /**
     * 获取access_token
     */
    public function get_access_token() {

        $tokenFile = 'access_token.txt';
        $data = json_decode(file_get_contents($tokenFile));

        if(!$data->expire_time || $data->expire_time > time()) {
            $APPID     = C('Weixin.appid');
            $APPSECRET = C('Weixin.appsecret');
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";
            $res = $this->curl($url);
            $access_token = $res['access_token'];
            if($access_token) {
                $data['expire_time'] = time() + 3600;
                $data['access_token'] = $access_token;
                $fp = fopen($token, 'w');
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        }else {
            $access_token = $data->access_token;
        }
        
        return $access_token;
    }


    /**
     * curl 获取数据
     */
    public function curl($url,$data) {

        $ch = curl_init();
        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if(!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);
        // 4. 释放curl句柄
        curl_close($ch);
        return json_decode($output,true);
    }

    function https_request($url,$data = null){

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}
